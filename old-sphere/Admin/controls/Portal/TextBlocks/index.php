<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_cvs.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once "./common.php";

$CVEdit = new CVEdit($Table, $Fields, $Options, $Permissions);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/change_work_category.php";

$CVEdit->ProcessEvent();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>