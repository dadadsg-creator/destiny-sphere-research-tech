<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
if (!$UserManager || !$UserManager->CheckSuperAdmin()) {
	headerLocation('/Admin/');
}

require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

session_register("LanguageID");
if (isset($change_lang_id)) $LanguageID = $change_lang_id;
if (!isset($LanguageID)) $LanguageID = "ru";

require_once "./common.php";

$Options[ForbidListTable] = 1;
$se = new SimpleEdit($Table, $Fields, $Options);

if (!$action) $action = "edit";
$Params[action] = $action;
$Params[ID] = $ID;

?>



<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title><? echo local('ADMIN_EDIT_DYNAMIC_COMPONENT'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
</head>

<SCRIPT language=JavaScript>
<!--
css = '"/Admin/styles/ie.css">';
nn4 = (document.layers);
ie4 = (document.all);
if (nn4) { css = '"/Admin/styles/nn.css">';}
document.write('<LINK REL="stylesheet" TYPE="text/css" HREF=' + css);
//-->
</SCRIPT>

<script src="/Admin/js/menu.js"></script>
<body>
<?=$se->ProcessEvent($Params)?>

<?
if (($action=="update") && !$se->UpdateError) {
	echo "<script language=JavaScript>close();opener.location.href=opener.location.href;</script>";
}
?>
</body>
</html>


