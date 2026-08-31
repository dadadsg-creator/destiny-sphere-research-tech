<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '4096M');
set_time_limit(1300);

require_once './include/Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;

SU_DebugTools::TimerStart('TEST');
/*********** BEGIN WORK AREA **************/

require_once "./classes/InterfaceController.class.php";

$interface = new InterfaceController;
$interface->exec();

/*********** END WORK AREA **************/
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');
?>