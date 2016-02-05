<?php
include 'functions.php';


/******************* Page Starts from here *******************/

if(!isset($member['index_no']))
{
	goto_url("/login/login.php"); 
} else
	$owner = $member['index_no']; //current user id, owner of upload ready products.

$error = "";
$success = "";
// if 'upload' button is clicked ->
if (isset($_POST['upload'])){
	// move all date from temporary to real database. Then, delete temporary data
	$UploadResult = upload_temp($owner);// located in function.php
	//if there was an error, display error message.
	if ($UploadResult)
		$error = $UploadResult;
	else 
		$success = "Uploading Succeedeed!";
}

// when user submit an excel file: validate then call sumit_File function(located on top)
if (isset($_POST['fileSubmit'])) {
	if (!isset($_POST['user_id']) || $_POST['user_id'] == "" ) {
		$error = "Please Enter Vender ID.";
	}else if (substr($_POST['user_id'], 0, 3) != 'AP-' && $_POST['user_id'] != 'admin' ) {
		$error = "The Seller ID is not correct. Seller ID shold be 'admin' or start with 'AP-'.";
	}else{
		$user_id = $_POST['user_id'];
		$result = submit_file($_FILES[new_file], $user_id, $owner);
		if ($result["status"])
			$error = $result["error"];
		else 
			$success = $result["success"];

		if (isset($result["warning"]))
			$warning = $result["warning"];
	}
	
}


if (isset($_POST['user_id']) && $_POST['user_id'] != "")
	$user_id = $_POST['user_id'];
?>
<h1 class="title">Add Bulk Products</h1>

<!-- display error message -->
<?php if (isset($error) && $error != "") : ?>
	<div class="error">
		<?php echo $error; ?>
	</div>
<?php endif; ?> 

<!-- display success message -->
<?php if (isset($success) && $success != "") : ?>
	<div class="success">
		<?php echo $success; ?>
	</div>
<?php endif; ?> 
<!-- display warning message -->
<?php if (isset($warning) && $warning != "") : ?>
	<div class="warning">
		<?php echo $warning; ?>
	</div>
<?php endif; ?> 

<div class="roundbox-tr">
	<div class="roundbox-tl">
		<div class="roundbox-br">
			<div class="roundbox-bl"> 
				<div class="roundbox-content">
					<table border=0 cellpadding=0 cellspacing=0 width='100%'>
					<col width=40px>
					<col />
					<tr>
						<td style='padding:15px 0 0 20px;vertical-align:top;'><img src='/admin/img/icon_tip.gif'></td>
						<td align=left>
							<table border=0 cellpadding=0 cellspacing=0 width='100%'>
							<tr>
								<td style='padding-top:12px;padding-bottom:10px;vertical-align:top;font-weight:bold;'>You can list multiple items quickly by following the process below.</td>
							</tr>
							<tr height=22>
								<td>1. Please download example file by clicking Example button below.</td>
							</tr>
							<tr height=22>
								<td>2. Open the file. We recommand to use Microsoft Exel program to open the file.</td>
							</tr>
							<tr height=22>
								<td>3. Please read explanation, and examples carefully.</td>
							</tr>
							<tr height=22>
								<td>4. Please fill the third sheet with your own data.</td>
							</tr>
							<tr height=22>
								<td>5. Enter 'Seller ID'. Seller ID shold be 'admin' or start with 'AP-'.</td>
							</tr>
							<tr height=22>
								<td>6. Please choose your edited file by clicking 'Choose File' button below.</td>
							</tr>
							<tr height=22>
								<td>7. Then, submit the edited file by clicking Submit button below.</td>
							</tr>
							<tr height=22>
								<td>8. Once, the file is submited, you can see the products that are ready to upload.</td>
							</tr>
							<tr height=22>
								<td>9. You can inspect, edit, or delete products.</td>
							</tr>
							<tr height=22>
								<td>10. Finally, you can upload all products by clicking upload button.</td>
							</tr>
							</table>
						</td>
					</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<div class='tbl_frm02'>
	<form name=fregiform action="<?=$PHP_SELf;?>" method="post" enctype="multipart/form-data">
	<input type="hidden" name="code" value="<?=$code;?>">
	<input type="hidden" name="mode" value="w">
	<table>
	<colgroup>
		<col width=15%>
		<col width=73%>
		<col width=6%>
		<col width=6%>
	</colgroup>
	<tr>
		<th>Seller ID</th>
		<td>
			<input type='text' name='user_id' value= <?php echo $user_id ?> >
			<button type="button" class="bt2 dty4" onclick="openwindow('supply.php','supply','550','500','no');return false"><span>Seller Search</span></button>
		</td>
	</tr>
	<tr>
		<th>Attached file</th>
		<td><input type='file' id='new_file' name='new_file' size=50 class=frm_file></td>
		<td><button type='submit' name='fileSubmit' class='bt4'><span>Submit</span></button></td>
		<td><button type='button' class='bt4 md4' onclick="location.href='./goods/goods_req_form_bulk_sample.zip';"><span>Example</span></button></td>
	</tr>	
	</table>
	</form>
</div>
<?php
// if the file has been submited, reset the input file to prevent that reloading submit the same file again. 
if (isset($_POST['fileSubmit'])) {
	echo '<script type="text/javascript">'
	   , 'clear_submitted_file;'
	   , '</script>';
}
?>

<!-- DB temp에 있는것 보여주고 클릭하면 상품 디테일 보여 주기  -->
<?php
$tempProductCount = sql_fetch("SELECT count(*) FROM shop_goods_temp where owner = $owner")["count(*)"];
?>

<?php if($tempProductCount > 0) : ?>
	<?php
	$gsca		  = strlen($sca);
	$query_string = "code=$code$qstr";

	// = 실제 페이징
	$q1 = $query_string;

	// = sort_link 
	$q2 = $query_string."&page=$page";


	$sql_common = " from shop_goods_temp a ";
	$sql_search = " where (left(a.user_id,3)='AP-' or a.user_id = 'admin') and a.shop_state='0' and a.owner = $owner ";

	// 분류
	if ($sca) {
	    $sql_common .= " left join shop_goods_cate_temp b on a.index_no=b.goods_idx ";
	    $sql_search .= " and (left(b.gcate,$gsca) = '$sca') ";
	}

	// 검색
	if ($stx && $sfl) {
	    $sql_search .= " and ( ";
	    switch ($sfl) {
	        case "goods_name" :
	            $sql_search .= " (a.$sfl like '%$stx%') ";
	            break;
	        default : 
	            $sql_search .= " (a.$sfl like '$stx%') ";
	            break;
	    }
	    $sql_search .= " ) ";
	}

	// 구분
	if ($sst) {
	    $sql_search .= " and ( ";
	    switch ($sst) {
	        case "g_adm" :
	            $sql_search .= " (a.user_id = 'admin') ";
	            break;
	        case "g_use" :
	            $sql_search .= " (a.user_id != 'admin') ";
	            break;
			default :
				$e_sst = explode("@", $sst);
				$sql_search .= " find_in_set('$e_sst[0]', a.$e_sst[1]) >= 1 ";
				break;
	    }
	    $sql_search .= " ) ";
	}

	// 진열
	if($sop) 
	{	$sql_search .= " and a.isopen='$sop' "; }


	// 재고
	if($p_schsh!='' && $p_dchsh!='')
	{	$sql_search .= " and (a.shop_p >= '$p_schsh' and a.shop_p <= '$p_dchsh')"; }  

	if($p_schsh!='' && $p_dchsh=='')
	{	$sql_search .= " and (a.shop_p >= '$p_schsh')"; }

	if($p_schsh=='' && $p_dchsh!='')
	{	$sql_search .= " and (a.shop_p <= '$p_dchsh')"; }

	if (!$orderby) {
	    $filed = "a.index_no";
	    $sod = "desc";
	} else {
		$sod = $orderby;
	}

	$sql_order = "  order by $filed $sod";

	$sql = " select count(distinct a.index_no) as cnt
			 $sql_common
			 $sql_search ";
	$row = sql_fetch($sql);
	$total_count = $row[cnt];

	$rows = 30;
	$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
	if ($page == "") { $page = 1; }				// 페이지가 없으면 첫 페이지 (1 페이지)
	$from_record = ($page - 1) * $rows;			// 시작 열을 구함
	$num = $total_count - (($page-1)*$rows);

	$sql = " select a.* 
			  $sql_common
			  $sql_search
			  $sql_order
			  limit $from_record, $rows ";
	$result = sql_query($sql);
	?>

	<h1 class="title">Upload Ready Products</h1>
	<div class='tbl_wrap mart5 marb5'>
		<form name="fsearch" method="post">
		<input type="hidden" name='code' value="<?=$code;?>">
		<table class='tbl_head01'>
		<colgroup>
			<col width='7%'>
			<col width='22%'>
			<col width='7%'>
			<col width='22%'>
			<col width='7%'>
			<col width='35%'>
		</colgroup>
		<tr> 
			<th>Sort</th>
			<td>
				<select name=sst>
					<option value=''>Choose</option>
					<optgroup id="optg1"> 
						<option value='g_adm'>Purchased Products</option>
						<option value='g_use'>Commissioned Product</option>
					</optgroup>
					<optgroup id="optg2"> 
						<?=get_main_skin_conf_select($sst) ?>
					</optgroup>	
					<optgroup id="optg3"> 
						<?=get_page_skin_conf_select($sst) ?>
					</optgroup>	
				</select>
				<script>document.fsearch.sst.value='<?=$sst?>';</script>
				<script language="JavaScript">
					document.getElementById("optg1").label = "Sort Products";
					document.getElementById("optg2").label = "Display main page";
					document.getElementById("optg3").label = "Display sub page";
				</script>
			</td>
			<th>Category</th>
			<td colspan='3'>
				<?=get_goods_sca_select('sca', $sca, "style='width:100%'") ?>
			</td>
		</tr>
		<tr> 
			<th>Display</th>
			<td>
				<select name=sop>
					<option value=''>Choose</option>
					<option value='1'>[Display - For Sale]</option>
					<option value='2'>[Display - Sold Out]</option>
					<option value='3'>[Discontinued - Temporary]</option>
					<option value='4'>[Discontinued - Discontinued]</option>
				</select>
				<script>document.fsearch.sop.value='<?=$sop?>';</script>
			</td>		
			<th>Search</th>
			<td>
				<select name=sfl>
					<option value=''>Choose</option>
					<option value='goods_name'>Product Name</option>			
					<option value='goods_code'>Product code</option>
					<option value='user_id'>Business Code</option>
					<option value='maker'>Manufacturer</option>
					<option value='country'>Country of origin</option>
				</select>
				<script>document.fsearch.sfl.value='<?=$sfl?>';</script>
			</td>
			<th>Keyword</th>
			<td><input type=text name=stx style='width:100%' value="<?=$stx;?>" class=frm_input></td>
		</tr>
		</table>

		<table class='mart5'>
		<colgroup>
			<col width='70%'>
			<col width='30%'>
		</colgroup>
		<tr>
			<td class="tal">All : <strong><?=number_format($total_count);?></strong> Views</td>
			<td class="tar"><button type='submit' class='bt4 md5'><span>Search</span></button></td>
		</tr>	
		</table>
		</form>
	</div>

	<form name=fboardlist method=post>
	<input type=hidden name=q1 value="<?=$q1;?>">
	<input type=hidden name=page value="<?=$page;?>">
	<table class='tbl_frm01'>
	<colgroup>
		<col width=35>
		<col width=40>
		<col width=50>
		<col width=70>
		<col width=40>	
		<col />
		<col width=85>
		<col width=70>
		<col width=65>	
	</colgroup>
	<thead>
		<tr>
			<th><input type=checkbox name=chkall value="1" onclick="check_all(this.form)"></th>
			<th>No</th>
			<th>Image</th>
			<th><?=subject_sort_link('a.goods_code',$q2)?><u>Product code</u></a></th>
			<th><?=subject_sort_link('a.user_id',$q2)?><u>Sort</u></a></th>
			<th><?=subject_sort_link('a.goods_name',$q2)?><u>Product Name</u></a></th>
			<th><?=subject_sort_link('a.isopen',$q2)?><u>Display</u></a></th>		
			<th><?=subject_sort_link('a.account',$q2)?><u>Sale Price</u></a></th>
			<th>Manage</th>
		</tr>
	</thead>
	<tbody>
		<?php
		for ($i=0; $row=sql_fetch_array($result); $i++) {
			$list = $i % 2;
			$gd_table = $row[index_no];

			$s_upd = "<a href='goods.php?code=req_form_bulk_update_single&w=u&gd_table=$gd_table$qstr&page=$page&bak=$code'><img src='./image/bt_modify.gif'></a>";
			$s_del = "<a href=\"javascript:post_delete('./goods/goods_req_form_bulk_delete_single.php', '$gd_table');\"><img src='./image/bt_delete.gif'></a>";

			echo "<input type=hidden name=gd_table[$i] value='$gd_table'>";
		?>	
		<tr class='list<?=$list;?>' height=50 align=center>
			<td><input type=checkbox name=chk[] value='<?=$i?>'></td>
			<td class=f_list0><?=$num;?></td>
			<td><a href="../shop/goods_req_form_bulk_view.php?index_no=<?=$gd_table;?>" target="_blank"><img src="<?=is_img($row[simg1]);?>" width="40" height="40"></a></td>
			<td class=f_listb><?=get_text($row[goods_code])?></td>
			<td class=f_list0><?=($row[user_id]=='admin')?"<font color='f77601'>Admin</font>":"<font color='009999'>Supplier</font>";?></td>
			<td class=pd5><input type=text class=frm_input name=goods_name[<?=$i?>] value='<?=get_text($row[goods_name])?>' style='width:98%;'></td>
			<td class=pd5>
				<select id=isopen_<?=$i?> name=isopen[<?=$i?>] style='width:98%'>
					<option value='1'>Display Products</option>
					<option value='2' style='color:#ffffff;background-color:#ea4d07'>Display Sold Out Products</option>
					<option value='3' style='color:#ffffff;background-color:#ea4d07'>Temporarily Out of Stock</option>
					<option value='4' style='color:#ffffff;background-color:#ea4d07'>Do Not Display</option>
				</select>
				<script>document.getElementById('isopen_<?=$i?>').value='<?=$row[isopen]?>';</script>
			</td>
			<td class=pd5>
				<input type=text name=account[<?=$i?>] value='<?=$row[account]?>' class=frm_input style='width:98%;'>
				<input type=hidden name=shop_p[<?php echo $i; ?>] value='<?php echo $row[shop_p]; ?>'>
			</td>
			<td><?=$s_upd?>&nbsp;<?=$s_del?></td>
		</tr>
		<?php
			$num--;
		}

		if ($total_count==0) {
		?>
		<tr><td height='50' colspan="9">No statement found.</td></tr>
		<?php } ?> 
	</tbody>
	</table>
	</form>

	<table class='wfull mart10'>
	<tr>
		<td align="left">			
			<button type='button' class='bt4 md6' onclick="btn_check('update')"><span>Edit Selected</span></button>
			<button type='button' class='bt4 md6' onclick="btn_check('delete')"><span>Delete Selected</span></button>
			<button type='button' class='bt4 md6' onclick="btn_check('deleteAll')"><span>Delete ALL</span></button>
		</td>	
		<td align="right">
			<?php if($total_count > 0) { ?>
			<?=pageing($page, $total_page, $total_count, "$PHP_SELF?$q1&page=");?>
			<?php } ?>
		</td>
	</tr>
	</table>

	<div class='tbl_frm03'>
	<form name=fregiform2 action="<?=$PHP_SELf;?>" method="post" enctype="multipart/form-data">
	<input type="hidden" name="code" value="<?=$code;?>">
	<input type="hidden" name="mode" value="w">
	<table>
	<colgroup>
		<col width=15%>
		<col width=73%>
		<col width=6%>
		<col width=6%>
	</colgroup>
	<tr>
		<td></td>
		<td></td>
		<td></td>
		<td><button type='submit' class='bt4' name='upload'><span>UPLOAD</span></button></td>
	</tr>	
	</table>
	</form>
	</div>



	<script>
	// POST 방식으로 삭제
	function post_delete(action_url, val)
	{
		var f = document.fpost;

		if(confirm("This will be permanently deleted.\n\nAre you sure to continue?")) {
	        f.gd_table.value = val;
			f.action = action_url;
			f.submit();
		}
	}

	function check_all(f)
	{
	    var chk = document.getElementsByName("chk[]");

	    for (i=0; i<chk.length; i++)
	        chk[i].checked = f.chkall.checked;
	}

	function btn_check(act)
	{
		var f = document.fboardlist;

	    if (act == "update") // 선택수정
	    { 
	        f.action = './goods/goods_req_form_bulk_update.php';
	        str = "Edit : ";
	    } 
	    else if (act == "delete") // 선택삭제
	    { 
	        f.action = './goods/goods_req_form_bulk_delete.php';
	        str = "Delete : ";
	    } 
	    else if (act == "deleteAll")
	    {
	    	f.action = './goods/goods_req_form_bulk_deleteAll.php';
	        str = "Delete All : ";
	    }
	    else
	        return;

	    if (act == "deleteAll")
	    {
	        if (!confirm("Are you sure to delete All items?"))
	            return;
	    }
	    else {

		    var chk = document.getElementsByName("chk[]");
		    var bchk = false;

		    for (i=0; i<chk.length; i++)
		    {
		        if (chk[i].checked)
		            bchk = true;
		    }

		    if (!bchk) 
		    {
		        alert(str + "Please select one or more items.");
		        return;
		    }

		    if (act == "delete")
		    {
		        if (!confirm("Are you sure to delete selected items?"))
		            return;
		    }
		}
	    f.submit();
	}
	function clear_submitted_file()
	{
		document.getElementById("new_file").value = "";	
	}
	</script>

	<form name='fpost' method='post'>
	<input type='hidden' name=q1	value="<?=$q1?>">
	<input type='hidden' name=page	value="<?=$page?>">
	<input type='hidden' name=gd_table>
	</form>

<?php endif; ?>
