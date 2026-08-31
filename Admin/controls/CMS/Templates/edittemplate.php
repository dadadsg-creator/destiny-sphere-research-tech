<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
if (!$UserManager || !$UserManager->CheckSuperAdmin()) {
	headerLocation ("/Admin/");
}
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once "./common.php";

$Options[ForbidListTable] = 1;
$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;

?>
<html>
<head>
	<title><? echo local('ADMIN_ADMIN_CONSOLE'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
</head>
<body>
<table border="0" cellpadding="2" cellspacing="0">
	<tr><td bgcolor="#636E88" width="80%"><b><? echo local('ADMIN_MOULD_EDIT'); ?></b></td>
		<td bgcolor="#636E88" width="20%">&nbsp;<a href="javascript: close();" alt="<? echo local('ADMIN_CLOSE'); ?>"><? echo local('ADMIN_CLOSE'); ?></a>&nbsp;</td>
	</tr>
	<tr><td colspan="2"><?=$se->ProcessEvent($Params)?></td></tr>
</table>
<?
if (($action=="update") && !$se->UpdateError) {
	echo "<script language=JavaScript>close();opener.location.href=opener.location.href;</script>";
}
?>
</body>
</html>

