<?php

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=InterfaceLocalization><font size=4><b>HELP</b></font></a></p>';

// COMMON SECTION
ini_set('memory_limit', '16M');
set_time_limit(0);

//require_once '../Main.php';

assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);
// END COMMON SECTION

require_once './LocalizedText.class.inc';

$dbh = DS_Common::CreateForCurentDB();
$lth = new LocalizedTextHandler();

$lth->handleRequest();
$lth->display('root.tpl');

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>