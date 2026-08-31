<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '16M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 1;

SU_DebugTools::TimerStart('TEST');
//
/*********** BEGIN WORK AREA **************/

$v = new SU_UpdateUnitVsUnit($_POST, $_FILES);

/*********** END WORK AREA **************/
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();
?>