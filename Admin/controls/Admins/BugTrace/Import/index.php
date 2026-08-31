<?
ini_set('memory_limit', '128M');
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_without_cvs.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once "File.class.php";

define('PARSER','|&&&&&|');

if (defined('LOGING_BUG_TRACE_PATH'))
{
	if ( is_dir(constant('LOGING_BUG_TRACE_PATH')) )
	{
		$d = dir(constant('LOGING_BUG_TRACE_PATH'));
		
		$files_count = 0;
		while($entry=$d->read())
		{
			
			if ($entry != ".." && $entry != "." )
			{
				$files_count++;
				$conn = getMysqlConnection();
				$file = new File(constant('LOGING_BUG_TRACE_PATH').$entry);
				//var_dump($file);
				foreach ($file->getInArray() as $value)
				{
					$value = trim($value);
					$value = str_replace("<br>","\n",$value);
					
					$row = explode(constant('PARSER'), $value);

					$sql = "SELECT ID FROM bug_trace WHERE DATETIME = '".$row[0]."' AND USERNAME = '".$row[1]."' AND UIID = '".$row[2]."';";
					$result = mysql_db_query(DBName,$sql,$conn);
					if ($result === false)
					{
						continue;
					}
					
					$rows = mysql_fetch_array($result);
					if ( !key_exists('ID', $rows))
					{
						$sql = "
INSERT INTO `bug_trace` ( `ID` , `DATETIME` , `USERNAME` , `UIID` , `WORLDID` , `DBNAME` , `ERRORTYPE` , `ERRORTEXT` , `PHPCODE` , `BACKTRACE` , `XMLDATA` , `FULLTXT` ) 
VALUES (
'', '".$row[0]."', '".$row[1]."', '".$row[2]."', '".$row[3]."', '".$row[4]."', '".$row[5]."', '".$row[6]."', '".addslashes($row[7])."', '".addslashes($row[8])."', '".addslashes($row[9])."', '".addslashes($row[10])."'
);

						
						
						";
						
						$result = mysql_db_query(DBName,$sql,$conn);
						
					}
					
					
					
					
				}
				$file->deleteFile();
				echo $entry."<br>";
			}
		}

		if ($files_count == 0)
		{
			echo ShowCMSMessage('No files for parse!', 2);
		}

	} else {
		echo ShowCMSMessage(constant('LOGING_BUG_TRACE_PATH').' is not a dir!', 2);
	}



} else {
	echo ShowCMSMessage('Not defined LOGING_BUG_TRACE_PATH', 2);
}






require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";


?>