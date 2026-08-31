<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/20 08:35:27 $';

ini_set('memory_limit', '16M');
set_time_limit(0);

require_once 'Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

/*********** BEGIN WORK AREA **************/

$a = new SMS_BuyGameCredits($_REQUEST);

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();

?>