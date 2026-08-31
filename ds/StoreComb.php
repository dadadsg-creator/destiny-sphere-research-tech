<?php
ini_set('memory_limit', '128M');
set_time_limit(1300);

require_once 'Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

SU_DebugTools::TimerStart('TEST');

/*********** BEGIN WORK AREA **************/
$_SESSION["dbId"] = 0;

/*
$interface = new SI_ControlMainInterface();
echo $interface->main($_REQUEST);
*/

echo "<xmp>";
$common = DS_Common::CreateForCurentDB();

$common->startTrans();

$common->executeQuery("set STATEMENT_TIMEOUT to 0;");
assert(empty($common->errormsg));

SI_Base::SetCommon($common);
$userInstanceResult = SI_ControlStoreUserInstance::Exec(1000147);

/*SI_ExecRestoreComb::SetTemplateMode(true);
$coords = array('RING'=> 3, 'COMPLEX' => 1, 'COMB' => 1);
SI_ExecRestoreComb::Exec(2, 1000147, $coords,$combStored);*/


//file_put_contents('UI_templates/aaaa', );
file_put_contents('UI_templates/ui_template_1', serialize($userInstanceResult));


$common->closeTrans('COMMIT');
echo "</xmp>";

/*********** END WORK AREA **************/

echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');
?>