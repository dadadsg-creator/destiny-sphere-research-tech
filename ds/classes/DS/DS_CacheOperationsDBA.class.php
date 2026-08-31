<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_CacheOperationsDBA extends DS_CacheDBA
{
	private $goodFilesCounter = 0;
	private $badFilesCounter = 0;
	private $goodFoldersCounter = 0;
	private $badFoldersCounter = 0;
	private $totalFiles = 0;
	private $totalFolders = 0;
	private $execTime = 0;
	
	public function __construct($cacheStorePath)
	{
		parent::__construct($cacheStorePath);
	}

	private function _resetCounters()
	{
		$this->goodFilesCounter = 0;
		$this->badFilesCounter = 0;

		$this->goodFoldersCounter = 0;
		$this->badFoldersCounter = 0;

		$this->goodRowsCounter = 0;
		$this->badRowsCounter = 0;

		$this->totalFiles = 0;
		$this->totalFolders = 0;
		$this->totalRows = 0;

	}

	private function _formReport()
	{
		$out  = "<br>\r\n------------------------ SUMMARY ACTION STATISTIC [".$_SERVER['SERVER_NAME']."] --------<br>\r\n";
		$out .= "DELETED_ROWS = ".number_format($this->badRowsCounter)." from ".number_format($this->totalRows)."<br>\r\n";		
		$out .= "------------------------------------------------------------------------------------------------<br>\r\n";
		
$fileInput = "
------------------------------------------
SERVER_NAME  [".$_SERVER['SERVER_NAME']."] 
DATE = ".date('Y-m-d H:i:s')."
SCRIPT = ".@$_SERVER['SCRIPT_FILENAME'] ."
EXEC_TIME = ".$this->execTime."
DELETE RECORDS ".number_format($this->badRowsCounter)." FROM ".number_format($this->totalRows)."
";
		SU_DebugTools::WriteToLog($fileInput, 'CacheCollector.log');	
		
		echo $out;
	}


	public function clearAll()
	{
		SU_DebugTools::TimerStart(__FILE__);
		
		$this->_resetCounters();

		$handle = opendir($this->cacheStorePath);
		if (!$handle)
		{
			return;
		}


		/*echo "<b>Scan dir '/".$this->cacheStorePath."/'</b><br>\r\n";
		flush();*/
						
		$d = dir($this->cacheStorePath);

		while (false !== ($entry = $d->read()))
		{
			$badCounter = 0;
			$summary = 0;

			if ($entry == "." || $entry == "..")
			{
				continue;
			}


			$fp = dba_open($d->path.$entry,'wdt', self::$dbaDriverName);
			if (!$fp)
			{
				continue;
			}

			$key = dba_firstkey($fp);
			
			$keys_array = array();
			while ($key != false)
			{
				$keys_array[] = $key;
				$key = dba_nextkey($fp);
			}

			if(count($keys_array)<=0)
			{
				continue;
			}

			foreach ($keys_array as $key)
			{
				$row = dba_fetch($key,$fp);

				$array = unserialize($row);

				$endTime = $array['CREATION_TIME']+$array['LIFE_TIME'];

				if (time() > $endTime)
				{
					//Эту запись пускаем на удаление
					//echo "Эта запись на удаление";
					dba_delete($key, $fp);

					$badCounter++;
					$this->badRowsCounter++;
				}
				$summary++;
				$this->totalRows++;
			}
			/*echo "&nbsp;&nbsp;&nbsp;Scan file <b>".$entry."</b><br>\r\n";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Deleted rows ".$badCounter." from ".$summary."<br>\r\n";
			flush();*/

			unset($keys_array);
			dba_optimize($fp);
			dba_close($fp);

		}
		$d->close();
		
		$this->execTime = SU_DebugTools::TimerStop(__FILE__);
		$this->_formReport();
	}//END OF METHOD CollectAll ()

	public function deleteAll()
	{
		$this->_resetCounters();

		//Удаляем все файлики из папки в которй хранится кэш!

		$d = dir($this->cacheStorePath);
		while (false !== ($entry = $d->read()))
		{
			if ($entry == "." || $entry == "..")
			{
				continue;
			}
			unlink($d->path.$entry);
			$this->totalFiles++;

		}
		echo "SERVER NAME [".$_SERVER['SERVER_NAME']."]<br>\n\r";
		echo "TOTAL FILES ".$this->totalFiles++."<br>\n\r";
		echo "END DELETE CACHE!<br>\n\r";
	}	

	public function syncAll()
	{
		$this->_resetCounters();
		
		$d = dir($this->cacheStorePath);
		while (false !== ($entry = $d->read()))
		{
			SU_DebugTools::TimerStart($d->path.$entry);

			if ($entry == "." || $entry == "..")
			{
				continue;
			}
			$fp = dba_open($d->path.$entry,'wdt', self::$dbaDriverName);
			dba_sync($fp);
//			unlink($d->path.$entry);
			$this->totalFiles++;
			echo $d->path.$entry." exec time ".SU_DebugTools::TimerStop($d->path.$entry)."<br>\n";
		}
		echo "SERVER NAME [".$_SERVER['SERVER_NAME']."]<br>\n\r";
		echo "TOTAL FILES ".$this->totalFiles++."<br>\n\r";
		echo "END SYNC CACHE!<br>\n\r";
	}	
}


?>