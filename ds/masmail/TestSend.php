<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:28:26 $';

ini_set('memory_limit', '128M');
set_time_limit(1200);

require_once './include/Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;

SU_DebugTools::TimerStart('TEST');
/*********** BEGIN WORK AREA **************/

require_once('./classes/Send.class.php');

$common = DS_Common::CreateForCurentDB();

$sender = new Send($common);

$common->executeQuery('BEGIN');
$sender->send();
$common->executeQuery('COMMIT');



/*********** END WORK AREA **************/
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');
?>