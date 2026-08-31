<?php $CVS_Version = '$Revision$ ; $Date$';

require_once 'Main.php';
require_once (constant('INCLUDE_PATH').'/Smarty/Smarty.class.php');

if (stripos($_SERVER['HTTP_HOST'], 'ds.klase.lt')!==false)
{
  header("Location: /?p=shop");
  die();
}
// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 1;

//Настройка работы  ASSERT
//assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

SU_GameSessionWork::SessionContinue();

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

//define('DEBUG_MODE', 1);

/*********** BEGIN WORK AREA **************/

$a = new SMS_Controller(array_merge($_POST, $_GET));

?>