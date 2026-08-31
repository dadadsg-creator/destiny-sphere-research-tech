<?php

ini_set('memory_limit', '16M');
set_time_limit(1200);

require_once './include/Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;

SU_DebugTools::TimerStart('TEST');
/*********** BEGIN WORK AREA **************/

/*require_once $_SERVER['DOCUMENT_ROOT'].'/ds/masmail/classes/Smarty/Smarty.class.php';
$tpl2 = new Smarty;
$tpl2->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/masmail/templates/";
$tpl2->compile_dir = $_SERVER['DOCUMENT_ROOT']."/ds/masmail/templates_c/";
$tpl2->compile_check = true;
$tpl2->debugging = true;


$tpl2->assign("test",'YOOOOOOOOOOOOOOOOO!!!!!!!!');

echo $tpl2->fetch('test.tpl.html');*/

print_r($_SERVER); 


/*********** END WORK AREA **************/
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');
?>