<?php
ini_set('memory_limit', '16M');
set_time_limit(0);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
//require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=TransferSearch><font size=3><b>HELP</b></font></a></p>';

$common = DS_Common::CreateForCurentDB(0);
$common->setDbId(0);

$searchControl = new SI_ControlSearchStoreUser($_REQUEST);
$searchControl->search();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>