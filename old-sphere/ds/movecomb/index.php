<?php
ini_set('memory_limit', '16M');
set_time_limit(0);

require_once './include/Main.php';
require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 0);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 1;

DebugTools::TimerStart('TEST');
/*********** BEGIN WORK AREA ************/

$mainControl = new SI_ControlMainInterface();
$mainControl->main($_REQUEST);

/*********** END WORK AREA **************/
//echo "</xmp>";
echo "<hr>WORK AREA TIME: ".DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();
?>