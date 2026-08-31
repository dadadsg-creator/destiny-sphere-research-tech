<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '512M');
set_time_limit(3540);

$sapi = php_sapi_name();
require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 2;

/*********** BEGIN WORK AREA **************/
$sapi = php_sapi_name();

if($sapi != "apache")
{
	assert(key_exists(0, $_SERVER['argv']));
	
	$worldsList = $_SERVER['argv'][1];	
	$wordsArray = explode(',', $worldsList);
		
	$inputData = array(
		'ACTION' => 'check',
		'WORLD_S_NAME' => $wordsArray
	);	
	
}else{
	
	$inputData = $_REQUEST;
	
}

MU_CheckAccountsMainController::Exec($inputData);

/*********** END WORK AREA **************/
$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();
?>