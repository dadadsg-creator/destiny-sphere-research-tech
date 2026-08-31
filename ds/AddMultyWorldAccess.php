<?php
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

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 1;

DebugTools::TimerStart('TEST');

echo "<xmp>";
/*********** BEGIN WORK AREA **************/

$filename = $_SERVER['DOCUMENT_ROOT'].'/ds/AllowMultyWorldAccess.cfg';

$users = file($filename);

$common = DS_Common::CreateForCurentDB(0);

$common->startTrans();

echo "COUNT OF USERS : ".count($users)."\n";

foreach($users as $k => $userName)
{
	$userName = substr($userName, 0, strlen($userName) - 1);
	
	$sql = "
			INSERT INTO ds_multiworldaccess
				(id_user, adddate)
			SELECT 
				id_user,
				now()
			FROM
				ds_user
			WHERE
				username = '".$userName."'
		";	
	
	$common->executeQuery($sql);
	assert(empty($common->errormsg));
	
	echo $userName."\n";
	flush();
}

$common->closeTrans("ROLLBACK");

/*********** END WORK AREA **************/
echo "</xmp>";
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

//GB_SingletonObjectStorage::CommitAllCommitable();
?>