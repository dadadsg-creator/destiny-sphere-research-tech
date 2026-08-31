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
$_SESSION["world"] = 2;

SU_DebugTools::TimerStart('TEST');

$_SERVER['HTTP_X_FORWARDED_FOR'] = '11.22.33.44, 55.66.77.88';

echo "<xmp>";

/*********** BEGIN WORK AREA **************/

$RD = new RD_RefreshDemon(0);
$RD->refreshUser(631); // bono

/*********** END WORK AREA **************/
echo "</xmp>";
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();
?>