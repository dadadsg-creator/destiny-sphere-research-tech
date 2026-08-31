<?php
ini_set('memory_limit', '128M');
set_time_limit(0);

require_once('../Main.php');

/** Параметры доступа к БД */
$GLOBALS['dbWorldAccess'] = array (

/*
		0 => array ( //База с нулевым инлдексом считаеться мастером
			'host' => 'db0',
			'dbname' => 'destiny1',
			'user' => 'dsgame',
			'password' => 'REDACTED_DB_PASSWORD',
			'server' => 'master',
			'dir' => '/ds/'
		),
*/		
		1 => array (
			'host' => 'db1',
			'dbname' => 'destiny1',
			'user' => 'dsgame',
			'password' => 'REDACTED_DB_PASSWORD',
			'server' => 'new_worlds',
			'dir' => '/ds/'
		),
/*		
		2 => array (
			'host' => 'db2',
			'dbname' => 'destiny1',
			'user' => 'dsgame',
			'password' => 'REDACTED_DB_PASSWORD',
			'server' => 'ganymed',
			'dir' => '/ds/'
		)
*/
	);


//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 1;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 3;

echo "<xmp>";
/*********** BEGIN WORK AREA **************/

DU_ClearWorld::Clear(1, 3);

/************ END WORK AREA ***************/
echo "</xmp>";
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();
?>