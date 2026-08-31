<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 1;
/************************************************/

require_once "classes/ControllerMain.class.php"; 
require_once "classes/EntityInputParams.class.php"; 
require_once "classes/OutNewForm.class.php";
require_once "classes/Search.class.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Unitdeathlog><font size=3><b>HELP</b></font></a></p>';

EntityInputParams::Singleton();

ControllerMain::Show();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>