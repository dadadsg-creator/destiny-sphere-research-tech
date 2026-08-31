<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '512M');
set_time_limit(0);

require_once '../Main.php';
//В ЭТОМ СКРИПТЕ ПО КРОНУ В 1 час ночи запускается обсчёт статистики игроков
//пришедших от партнёров, чтобы потом эту статистику отдавать по XML

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

SU_DebugTools::TimerStart('TEST');

/*********** BEGIN WORK AREA **************/
function StartPatch()
{
  $GameLeads = new MP_GameLeads(DS_Common::CreateForCurentDB(0));
  $GameLeads->patch();

  $pd = new MP_PartnerData(DS_Common::CreateForCurentDB(0), 115);
  $pd->patch();  
}

StartPatch();
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

?>