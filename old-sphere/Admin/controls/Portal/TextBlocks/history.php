<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_cvs_history.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once "./common.php";

$CVHistory = new CVHistory($Table, $Fields, $Options, $Permissions);

?>
<a href="index.php"><img src="/Admin/img/button-back-to-list.gif" width="160" height="25" alt="<? echo local('ADMIN_BACK_TO_LIST'); ?>" border="0"></a><br><br>
<?

$CVHistory->ProcessEvent();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
