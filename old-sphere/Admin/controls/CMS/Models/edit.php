<?
if (!$ID){
	headerLocation('index.php');
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";


$conn_id = getMysqlConnection();
$query = "SELECT Title FROM AssemblyTemplate WHERE ID=$ID AND (Lang_id='$LanguageID' OR Lang_id='' OR Lang_id is NULL)";
$result = @mysql_db_query(DBName,$query,$conn_id);
if ($result) {
	$row = mysql_fetch_row($result);
	$AssemblyTemplateTitle = $row[0];
} else $err = "<font color=red>".local('ADMIN_DATABASE_ACCESS_ERROR')."</font><br><br>";

$query = "SELECT ID,Title FROM AssemblyTemplate WHERE Lang_id='$LanguageID' OR Lang_id='' OR Lang_id is NULL ORDER BY Title";
$result = @mysql_db_query(DBName,$query,$conn_id);
if ($result) {
	while ($row = mysql_fetch_row($result)) {
		$Templates[$row[0]] = $row[1];
	}
}

if ($err) {
	echo $err;
	?>
	
	<a href="index.php"><img src="/Admin/img/button-back-to-list.gif" width="160" height="25" alt="<? echo local('ADMIN_BACK_TO_LIST'); ?>" border="0"></a>
	<?
} else {
	?>
	<table border="0">
		<tr><td width=200><a href="index.php"><img src="/Admin/img/button-back-to-list.gif" width="160" height="25" alt="<? echo local('ADMIN_BACK_TO_LIST'); ?>" border="0"></a></td>
			<form name="ChangeTemplate" action="<?=$PHP_SELF?>" method="post">
			<td>
			  <select name="ID" onchange="javascrip: document.ChangeTemplate.submit()">
			  	<?
				while (list($key,$val) = each($Templates)) {
					echo "<option value=\"".$key."\" ".(($ID == $key) ? "selected" : "").">".$val."</option>\n";
				}
				?>
			  </select>
			</td>
			</form>  
		</tr>
	</table>
	<br>
	
	<?	$work_dir = ($LanguageID == "en") ? "/eng/" : "/"; ?>
	<IFRAME id="DocIFRAME" src="<?=$work_dir?>admin.php?AssemblyTemplateID=<?=$ID?>&<?=session_name()."=".session_id()?>" width="100%" height="800" scrolling="auto" frameborder="1">
	  [<? echo local('ADMIN_DONT_SUPPORT_IFRAME_MESSAGE'); ?>]
	</IFRAME>
	
	<?
}
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
