<?php
// handles file submition, if there was an error, it will return error message.
// Otherwise, it will upload product info to temporary database tables, and return result.
// Parameters: 
				// $new_file: file path 
				// $user_id: seller id 
				// $owner: current admin user index_no
// Return: 
				//result["status"]( 0:successed or 1:failed ) and result["error"] or result["warning"] message.
function submit_file($new_file, $user_id, $owner){

	$result = array();  // result["status"]( 0:successed or 1:failed ) and result["error"] or result["warning"] message.
	$result["status"] = 0;

	$xlsx_path = "../files/goods_xlsx";	
	demo_check();
	// if file submited check file extension
	if ($img = $new_file[name]) {
		if (!preg_match("/\.(xlsx)$/i", $img)) {
			$result["status"] = 1;
			$result["error"] = "ERROR: The file is not in xlsx format.";
			return $result;
		}

		$new_file_urlencode = time().".xlsx";
	
		$xlsx_upload_path = "$xlsx_path/$new_file_urlencode";
		move_uploaded_file($new_file[tmp_name], $xlsx_upload_path);
		chmod($xlsx_upload_path, 0606);
	} else {
		$result["status"] = 1;
		$result["error"] = "ERROR: Please Attach the Excel file.";
		return $result;
	}	
	// check if it is regular file 
	if(!is_file($xlsx_upload_path)){
		$result["status"] = 1;
		$result["error"] = "ERROR: $xlsx_upload_path is not found in ther server";
		return $result;
	} else {
		try{
			// Parse products info, then store into DB
			require_once $_SERVER['DOCUMENT_ROOT'].'/library/PHPExcel_1/Classes/PHPExcel.php';
			$objReader = PHPExcel_IOFactory::createReader('Excel2007');
			$objReader->setReadDataOnly(true);

			$objPHPExcel = $objReader->load($xlsx_upload_path);
			unlink($xlsx_upload_path);
			$dataSheet = $objPHPExcel->getSheet(0);
			$dataSheet->unfreezePane();
			$dataSheet->removeColumn("AE",7);

			// get info from data table and option table
			$data = array();
			$options = array();
		
			$highestRow = $dataSheet->getHighestRow(); 
			$highestColumn = $dataSheet->getHighestColumn();
			
			for ($i = 16; $i<$highestRow; $i++){
				if (!is_null($dataSheet->getCellByColumnAndRow(0, $i)->getValue())) {
					$arr = array();
					for ($j = 0; $j < 25; $j++){
						array_push($arr, mysql_real_escape_string(trim($dataSheet->getCellByColumnAndRow($j,$i)->getValue())));
					}
					//array_push($data,$arr);
					$data[$arr[0]] = $arr;
				}
				if (!is_null($dataSheet->getCellByColumnAndRow(26, $i)->getValue())) {
					$arr = array();
					for ($j = 26; $j < 30; $j++){
						array_push($arr, mysql_real_escape_string(trim($dataSheet->getCellByColumnAndRow($j,$i)->getValue())));
					}
					array_push($options,$arr);
				}
			}
			$result = validateBulkInput($data, $options, $owner, $result);

			if ($result["status"] === 0){
				// insert data to temp DB
				$dataCount = insert_goods_tmp_from_array($data, $user_id, $owner); // located in function.php

				// insert options to tmep DB
				$optionCount = insert_goods_option_temp_from_array($options, $owner); // located in function.php

				$result["success"] = "File submition succeedeed! $dataCount products are ready to upload.";
			}
		
		} catch (exception $e){
			$err = "ERROR: ";
			$err .= $e->getMessage();
			$result["error"] = $err;
	    	$result["status"] = 1;
			return $result;
		}

		return $result;
	}
}

// This function validate all $data, $option input, and return result with Error, warning messages. 
// result["status"]: 0(success) or 1(failed)
// result["warning"]: warning message
// result["error"]: error message
function validateBulkInput(&$data, &$options, $owner, $result){
	/*****************ERROR HANDLING*****************/
	$option_quantity_sums = array();

	$wrong_goods_code_in_option = array(); // check if the goods_code in option table does NOT exist in product table. 
	$wrong_quantity_items = array(); // check if sum of "option stock quantities in option talbe" is same as "total # of stock in product table"
	$no_quantity_items = array(); // check if both product table and option table don't have available quantity.

	for ($i = 0; $i<count($options); $i++){
		$op_goods_code = $options[$i][0];
		// generate array with key:goods_code, value:sum of available quantity with same goods_code.
		if (!array_key_exists($op_goods_code, $option_quantity_sums))
			$option_quantity_sums[$op_goods_code] = $options[$i][3];
		else 
			$option_quantity_sums[$op_goods_code] += $options[$i][3];

		// // check if both product table and option table don't have available quantity.
		// if (($options[$i][3] === "" || is_null($options[$i][3])) && $data[$op_goods_code][21] === ""){
		// 	if(!in_array($op_goods_code, $no_quantity_items))
		// 		array_push($no_quantity_items, $op_goods_code);
		// } 
	}
	foreach ($option_quantity_sums as $goods_code => $option_quantity_sum) {
		// check if the goods_code in option table does NOT exist in product table. 
		if(!array_key_exists($goods_code,$data)) { 
			array_push($wrong_goods_code_in_option, $goods_code);
		} 
		// check if sum of "option stock quantities in option talbe" is same as "total # of stock in product table"
		else{
			if ($data[$goods_code][21] === ""){ // if available quantity in product table is empty,
				$data[$goods_code][21] = $option_quantity_sum; //update total stock availability with sum of option availabilities.
			}
			else if ($option_quantity_sum != $data[$goods_code][21])
				array_push($wrong_quantity_items, $goods_code);
		}
	}

	// check if some goods_code in the file already exist in temporary DB, if so, return error message
	$noDuplicate = checkTempExist(array_keys($data), $owner);



	/************ WARNIGN handling ************/
	$noMSRP = array();
	$noSupplyCost = array();
	$noSalePrice = array();
	$SalePriceGtMSRP = array(); //warning for Sale price > MSRP
	$SupplyCostGtSalePrice = array(); // supply cost > sale price
	$noImage = array();
	$noCate = array(); // no categories

	foreach ($data as $goods_code => $row) {
		// check if some MSRP, SalePrice, or SuplyCost are empty
		if ($row[2] === "") 
			array_push($noMSRP,$goods_code);

		if ($row[3] === "")
			array_push($noSupplyCost,$goods_code);

		if ($row[4] === "")
			array_push($noSalePrice,$goods_code);

		if ( $row[4] !== "" && $row[2] !== "" && $row[4] > $row[2])
			array_push($SalePriceGtMSRP, $goods_code);

		if ( $row[3] !== "" && $row[4] !== "" && $row[3] > $row[4] )
			array_push($SupplyCostGtSalePrice, $goods_code);

		if ($row[5] === "" && $row[6] === "" && $row[7] === "" && $row[8] === "" && $row[9] === "" && $row[10] === "" 
			 && $row[11] === "" && $row[12] === "" && $row[13] === "" && $row[14] === "" && $row[15] === "")
			array_push($noImage, $goods_code);
		
		// check if both product table and option table don't have available quantity.
		if ($row[21] === "")
			array_push($no_quantity_items, $goods_code);

		if ($row[24] === "")
			array_push($noCate, $goods_code);
	}


	$errors = array();

	if (count($wrong_goods_code_in_option) > 0) {
		$e1_head = "Following goods_codes in option table does not exist in the Product table: ";
		$e1 = $e1_head.implode(', ', $wrong_goods_code_in_option);
		array_push($errors, $e1);
	}
	if (count($wrong_quantity_items) > 0) {
		$e2_head = "Total available quantity in product table is NOT EQUAL to sum of available quantity in option table for the following items: ";
		$e2 = $e2_head.implode(', ', $wrong_quantity_items);
		array_push($errors, $e2);
	}
	if ($noDuplicate !== 0) {
		$e3 = $noDuplicate;// error message
		array_push($errors, $e3);
	}
	if (count($no_quantity_items) > 0){
		$e4_head = "There is no available quantity for the following items: ";
		$e4 = $e4_head.implode(', ', $no_quantity_items);
		array_push($errors, $e4);
	}

	// if there are any error, report them.
	if (count($errors)>0){
		$result["status"] = 1;
		$result["error"] = "ERROR:<br>".implode('<br>', $errors);
	}


	$warnings = array();

	if (count($noMSRP)>0){
		$w1 = "Following goods does NOT have 'MSRP' value: ";
		$w1 .= implode(', ', $noMSRP);
		array_push($warnings, $w1);
	}
	if (count($noSupplyCost)>0){
		$w2 = "Following goods does NOT have 'Supply Cost' value: ";
		$w2 .= implode(', ', $noSupplyCost);
		array_push($warnings, $w2);
	}
	if (count($noSalePrice)>0){
		$w3 = "Following goods does NOT have 'Sale Price' value: ";
		$w3 .= implode(', ', $noSalePrice);
		array_push($warnings, $w3);
	}
	if (count($SalePriceGtMSRP)>0){
		$w4 = "Following goods have 'Sale Price' greater than MSRP: ";
		$w4 .= implode(', ', $SalePriceGtMSRP);
		array_push($warnings, $w4);
	}
	if (count($SupplyCostGtSalePrice)>0){
		$w5 = "Following goods have 'Supply Cost' greater than Sale Price': ";
		$w5 .= implode(', ', $SupplyCostGtSalePrice);
		array_push($warnings, $w5);
	}
	if (count($noImage)>0){
		$w6 = "Following goods does NOT have any image: ";
		$w6 .= implode(', ', $noImage);
		array_push($warnings, $w6);
	}
	if (count($noCate)>0){
		$w7 = "Following goods does NOT have any category: ";
		$w7 .= implode(', ', $noCate);
		array_push($warnings, $w7);
	}


	// if there are any warnings, generate warning message.
	if (count($warnings)>0){
		$result["warning"] = "WARNING:<br>".implode('<br>', $warnings);
	}

		
	return $result; 
}

?>