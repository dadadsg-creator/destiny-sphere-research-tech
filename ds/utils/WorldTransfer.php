<?php
ini_set('memory_limit', '16M');
set_time_limit(0);

/*$sapi = php_sapi_name();
if($sapi == "apache")
{
	die("Only for command line mode!");
}
*/
require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регитрация финализатора
register_shutdown_function ('finilazer');
/*
$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 2;
*/
/*********** BEGIN WORK AREA **************/

ob_start();
SU_ExecWorldTransfer::Exec();
ob_end_flush();
?>