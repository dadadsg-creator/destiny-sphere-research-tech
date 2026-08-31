<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '16M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

/*********** BEGIN WORK AREA **************/

$out = '';


$sendMailFlag = false;

$out .= "SERVER_NAME: ".$_SERVER['SERVER_NAME']."\n\n";

foreach ($GLOBALS['LOGING_DEBUG_LOGS_PATH'] as $pathName => $path)
{
	
	$out .= $pathName." : $path\n";
	$filesCount = 0;
	$filesSize = 0;
	
	if (!($handle = opendir($path))) 
	{
		$out .= "CANT OPEN\n";
		continue;
	}
    			
	//Сперва формируем список файлов
	while ($file = readdir($handle)) 
	{
	   	if($file == '.' || $file == '..' || is_dir($file)) 
	   	{
	   		continue;
	   	}
		    	
	   	$filesCount++;
	   	$filesSize += filesize($path.$file);
	}
		
	$freeSpace = disk_free_space($path);	
	
	if($freeSpace < 200000000)
	{
		$sendMailFlag = true;	
		$warning = "\nWARNING! Not enought space on disc!";
	}else{
		$sendMailFlag = false;
		$warning = "";
	}
	
	$out .= "COUNT: ".number_format($filesCount, 0, ".", " ")." SIZE: ".number_format($filesSize, 0, ".", " ")." FREE: ".number_format($freeSpace, 0, ".", " ")."$warning\n\n";
}

//SU_TestTableData::Exec();

$sapi = php_sapi_name();
if( $sapi != "apache" )
{
	if($sendMailFlag)
	{		
		error_log($out, 1, "fedchin@destinysphere.com");
		error_log($out, 1, "codesh@destinysphere.com");
	}

	SU_DebugTools::LogAll($out, 'LogFilesSize.log');
}else{
	echo "<xmp>".$out."</xmp>";
}

?>