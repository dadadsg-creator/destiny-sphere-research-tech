<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

require_once "./common.php";

$Options = array("Order"=>"Title");

$Table['Actions']['edit'] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
$Table['Actions']["delete"] = array ("Caption"=>"<img src='/Admin/img/knopka_del.gif' border='0' alt='".local('ADMIN_DELETE')."'>","Width"=>"20","Confirmation"=>array("Template"=>local('ADMIN_DELETE_MOULD_TYPE')." '[#Title#]' ?"));

$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;

$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
