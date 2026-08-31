<?php $CVS_Version = '$Revision$ ; $Date$';
ini_set('memory_limit', '16M');
set_time_limit(0);

$sapi = php_sapi_name();
if($sapi == "apache")
{
	die("Only for command line mode!");
}

exec("cd /home/www/htdocs/ds");

require_once 'Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

/*********** BEGIN WORK AREA **************/

SU_Locker::cronLockCollector();
?>