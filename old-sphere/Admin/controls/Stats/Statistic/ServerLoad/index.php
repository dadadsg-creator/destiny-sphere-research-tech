<?
ini_set('memory_limit', '32M');
set_time_limit(0);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

echo SU_ServerLoadStat::MainInterface();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
				
