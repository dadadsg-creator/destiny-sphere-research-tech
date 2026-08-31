<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '128M');
set_time_limit(1300);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

SU_DebugTools::TimerStart('TEST');

/*********** BEGIN WORK AREA **************/
$_SESSION["dbId"] = 0;

$viewBattleLogs = new SU_ViewBattleSystemLogs();
echo $viewBattleLogs->main($_REQUEST);


/*********** END WORK AREA **************/

echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');
?>