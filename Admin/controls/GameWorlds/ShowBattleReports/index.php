<?php

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

ini_set('memory_limit', '128M');
set_time_limit(5000);

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=BattleLogs><font size=3><b>HELP</b></font></a></p>';

$viewBattleLogs = new SU_ViewBattleSystemLogs();
echo $viewBattleLogs->main($_REQUEST, "trueFlag");

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>