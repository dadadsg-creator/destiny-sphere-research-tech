<?php

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

ini_set('memory_limit', '128M');
set_time_limit(5000);

$viewBattleLogs = new SU_ViewBattleSystemLogs();
echo $viewBattleLogs->main($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>