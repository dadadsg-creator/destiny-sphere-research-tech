<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '512M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

SU_DebugTools::TimerStart('TEST');

echo "<h1>Server times</h1>";

echo "<xmp>";
/*********** BEGIN WORK AREA **************/


$timeHTTP = GB_TypeFactory::CreateBase('GB_Date', time());

echo $timeHTTP->getPgFormat()." - HTTP(".$_SERVER['SERVER_NAME'].")\n";
echo "------------------------------------\n";

assert(key_exists('dbWorldAccess', $GLOBALS));
foreach ($GLOBALS['dbWorldAccess'] as $k=>$v)
{		
	$_SESSION["dbId"] = $k;
	
	$timeDB = GB_TypeFactory::CreateBase('GB_Date', DS_Misc::GetNow());	
	
	echo $timeDB->getPgFormat()." - DB(".$v['server'].")\n";	
}	

/*********** END WORK AREA **************/
echo "</xmp>";
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

?>