<?php
require_once("../Admin/include/class/anyDB/addon/QueryHelper.php");
require_once('include/GameDown.inc.php');

ini_set('memory_limit', '256M');
set_time_limit(0);

$sapi = php_sapi_name();
if( $sapi != "apache" )
{
	if( isset( $argv[1] ) && !empty( $argv[1] ) )
	{
		$_SERVER['DOCUMENT_ROOT'] = $argv[1];
	}else{

		exit();
	}
}

require_once 'Main.php';

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


/********** Проверка закрытости игры **********/


if (GameDown())
{
 	ds_error ("Game is closed. Pleas try later.", false);
	exit();
}


/********** END **********/


SU_DebugTools::TimerStart('TEST');
echo "<xmp>";
/*********** BEGIN WORK AREA **************/

//$common = DS_Common::CreateForCurentDB();

$syncUsers = new SyncUsers();
$syncUsers->Start();
$array = $syncUsers->getCounts();

$str = '';
foreach ($array as $key => $value)
{
	$str .= $key . '-' . $value . ';';
}

if (!is_null($syncUsers->GetError()))
{

	$error = "Errors: ".$syncUsers->GetError()."\n";
	$str .= $error;
	echo $error;
}
/*********** END WORK AREA **************/
$time = SU_DebugTools::TimerStop('TEST');

echo "</xmp>";
echo "<hr>WORK AREA TIME: ".$time;


$str .= "\n\nWORK AREA TIME: ".$time;
SU_DebugTools::LogAll($str,'SyncUsers.log',0,0);

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();


?>