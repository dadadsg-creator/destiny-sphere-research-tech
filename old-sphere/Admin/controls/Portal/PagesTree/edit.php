<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";

$ActiveMenu = ($preview==1) ? local('ADMIN_PAGE_PREVIEW') : local('ADMIN_ASSIGNING_ELEMENTS_PAGES');

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";

if (!$UserManager->CheckPermission("pages_edit_page")) {
		echo "<font color=red>".local('ADMIN_NOT_RIGHTS_FOR_EDIT_INFO')."</font>";
} else {

	$conn_id = getMysqlConnection();
	if ($ID) {
		$query = "SELECT P.Title, AT.ID, AT.Title FROM Pages P LEFT JOIN AssemblyTemplate AT on P.AssemblyTemplate_id=AT.id WHERE P.ID='$ID'";
		$result = @mysql_db_query(DBName,$query,$conn_id);
		if ($result) {
			if ($row = mysql_fetch_row($result)) {
				$PageTitle = $row[0];
				$AssemblyTemplateID = $row[1];
				$TemplateTitle = $row[2];
			} else $err = local('ADMIN_PAGE_NOT_FOUND_IDENT', $ID);
		} else $err = local('ADMIN_ERROR_ACCESS_DB');
	} else $err = local('ADMIN_PAGE_NOT_SET_ID');

	?>
	<script language="JavaScript">
		function Edit() {
			frm = document.getElementById("DocIFRAME");
			frm.src='/t/3/index.php?AssemblyTemplateID=<?=$AssemblyTemplateID?>&p=<?=$ID?>';
		}
		function View() {
			frm = document.getElementById("DocIFRAME");
			frm.src='/t/3/view.php?p=<?=$ID?>';
		}
	</script>



	<table width="100%"><tr>
		<td width="250">
			<table>
				<tr><td>
						<a href="/Admin/controls/pages/?"><img src="/Admin/img/button-back-to-list.gif" width="160" height="25" alt="<? echo local('ADMIN_BACK_TO_LIST'); ?>" border="0"></a>
					</td>
				</tr>
				<tr><td class="maintxt">
						<?=($preview == 1) ? "<a href=\"?ID=$ID\">".local('ADMIN_ENABLE_EDIT_MODE')."</a>" : "<a href=\"?ID=$ID&preview=1\">".local('ADMIN_VIEW_MODE')."</a>";?>
					</td>
				</tr>
			</table>
		</td>
		<td>
			<table cellpadding="5" cellspacing="0" border="0">
				<tr><td width="100" class="td">local('ADMIN_PAGE').":</td>
					<td class="td"><?echo ($PageTitle) ? "<b>".$PageTitle."</b>" : "<font color=red><b>".local('ADMIN_ERROR').":</b></font>&nbsp;&nbsp;".$err;?></td>
				</tr>
				<tr><td width="100" class="td">local('ADMIN_MODEL').":</td>
					<td class="td"><?echo ($TemplateTitle) ? "<b>".$TemplateTitle."</b>" : "<font color=red>".local('ADMIN_NOT_SET')."</font>";?></td>
				</tr>
			</table>
		</td>
	</tr></table>

	<br>


	<?if (!$err && $TemplateTitle) {
		$work_dir = ($LanguageID == "en") ? "/eng/" : "/";
	?>
	<IFRAME id="DocIFRAME" src="<?=($preview==1) ? $work_dir."?p=$ID" : $work_dir."easyadmin.php?p=$ID";?>&<?=session_name()."=".session_id()?>" width="100%" height="800" scrolling="auto" frameborder="1">
	  [<? echo local('ADMIN_DONT_SUPPORT_IFRAME_MESSAGE'); ?>]
	</IFRAME>
	<?}?>

	<br><br><br>
	<?
}
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>