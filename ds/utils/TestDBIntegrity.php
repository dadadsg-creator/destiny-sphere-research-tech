<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '512M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);
/*********** BEGIN WORK AREA **************/


if(isset($_REQUEST['action']))
		{
			$action = $_REQUEST['action'];
		}else{
			$action = '';
		}
		
		if(isset($_REQUEST['dbid']))
		{
			$dbid = (int)$_REQUEST['dbid'];
		}else{
			$dbid = (int)0;
		}
		
		$out = "<h1>TestDBIntegrity</h1>"; 
				
		
		$out .= '<b>Select DB server to test';							
		$out .= '<table width=300 border=1>';							
		foreach($GLOBALS['dbWorldAccess'] as $k=>$v)
		{
			$out .= '<tr>';
			$out .= '<td>&nbsp;'.$v['server'].'&nbsp;</td><td align=center> <a href="?action=test&dbid='.$k.'">Validate </a> </td>';
			$out .= '</tr>';
		}
		$out .= '</table>';
				
		echo $out;
		
		if($action ==  'test')
		{

echo "<xmp>";
$test = new SU_TestDBIntegrity($dbid);
$test->test();
echo "</xmp>";
		}
		




?>