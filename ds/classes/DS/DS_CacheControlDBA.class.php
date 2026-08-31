<?php $CVS_Version = '$Revision$ ; $Date$';
/**
Всего:  485730
*/
class DS_CacheControlDBA extends DS_CacheDBA 
{		
	private $cacheOperations;
		
	private $filesData = array();
	
	private $totalData = array();	
	
	public function __construct($cacheStorePath)
	{							
		parent::__construct($cacheStorePath);					
		$this->cacheOperations = new DS_CacheOperationsDBA($cacheStorePath);
	}
	
	public function main($data)
	{
		$action = @$data['action'];
		
		switch($action)
		{
			case 'delete':
				$this->_delete();
				break;
			case 'clear':
				$this->_clear();
				break;
			case 'stat':
				$this->_showStat();
				break;
			case 'sync':
				$this->_syncAll();
				break;
			default:
				$this->_empty();
		}						
	}

	private function _clear()
	{
		echo $this->_getTitle();
		echo "START CLEAR CACHE!<br>\n\r";
		$this->cacheOperations->clearAll();
	}

	private function _syncAll()
	{
		echo $this->_getTitle();
		echo "START SYNC CACHE!<br>\n\r";
		$this->cacheOperations->syncAll();
	}
	
	private function _delete()
	{
		echo $this->_getTitle();
		echo "START DELETE CACHE!<br>\n\r";
		$this->cacheOperations->deleteAll();
	}
	
	private function _empty()
	{
		echo $this->_getTitle();
	}
	
	private function _showStat()
	{		
		$this->_formData();
		$out = $this->_formOutput();
		
		return $out;
	}
	
	private function _formData()
	{		
		$conn = null;
		$totalExistFilesCount = 0; //Общее колличество существующих баз				
		$totalFilesSize = 0; //Суммарный размер баз				
		$totalRecordsStored = 0; //Общее колличестов хранимых записей	
			
		$maxRecordsCount = 0; //Максимальное колво записей
		$maxRecordsCountName = ''; //Имя бд в колторой максимальное колличество записей
		
		$maxSize = 0; //Максимальный размер
		$maxSizeName = ''; //Имя бд в колторой макасимальный размер

		for($i=0; $i < parent::$devisor; $i++)
		{	
			//Иницилизируем переменные
			$size = 0;
			$keysCount = 0;
			$keys = array();
			
			
			//Собираем данные
			$fileName = $this->cacheStorePath.'/'.$i."_".self::$baseFileName;
			
			if(file_exists($fileName))				
			{
				$size = filesize($fileName);
				
				$totalExistFilesCount++;
				$totalFilesSize += $size;
				
				$conn = dba_open($fileName, "rdt", self::$dbaDriverName);	
			}else{
				$conn = false;
			}
			
			//Изучаем данную БД								
			if($conn)
			{				
				$key = dba_firstkey($conn);
				while ($key != false) 
				{    			
        			$keys[] = $key;
        			$key = dba_nextkey($conn);
    			}
				$keysCount = count($keys); 
				dba_close($conn);   			   			
			}else{
				$keysCount = 'N/A';
			}
						
			$totalRecordsStored += $keysCount;
			
			if($maxRecordsCount < $keysCount)
			{
				$maxRecordsCount = $keysCount;
				$maxRecordsCountName = $i;
			}
			
			if($maxSize < $size)
			{
				$maxSize = $size;
				$maxSizeName = $i;
			}						
						
			//Заносим результаты	
			$this->filesData[$i]['SIZE'] = number_format($size);				
			$this->filesData[$i]['KEYS_COUNT'] = $keysCount;									
			
		}//END OF FORE		
		
		$this->totalData['MAX_FILES_COUNT'] = parent::$devisor;
		$this->totalData['EXISTS_FILES_COUNT'] = $totalExistFilesCount;
		$this->totalData['TOTALS_RECORDS_STORED'] = $totalRecordsStored;
		$this->totalData['TOTALS_FILES_SIZE'] = $totalFilesSize;
		$this->totalData['---------------'] = "--------------";
		$this->totalData['AVER_FILES_SIZE'] = $totalFilesSize / $totalExistFilesCount;
		$this->totalData['AVER_RECORDS_COUNT'] = $totalRecordsStored / $totalExistFilesCount;		
		$this->totalData['MAX_RECORDS_COUNT'] = $maxRecordsCount;
		$this->totalData['MAX_RECORDS_COUNT_NAME'] = $maxRecordsCountName;
		$this->totalData['MAX_SIZE'] = $maxSize;
		$this->totalData['MAX_SIZE_NAME'] = $maxSizeName;
	}
	
	private function _formOutput()
	{				
		$out = $this->_getTitle();	
		
		$out .= "<table border=1 width=600>";
		$out .= "<tr><th colspan=2>TOTAL STATISTIC [".$_SERVER['SERVER_NAME']."]</th></tr>";			
		$out .= "<tr><th>PARAM</th><th>VALUE</th></tr>";		
		foreach($this->totalData as $k=>$v)
		{
			if(is_numeric($v))
			{
				$val = number_format($v);
			}else{
				$val = $v;
			}
			
			$out .= "<tr>";	
			$out .= "<td align=left>".$k."</td>";
			$out .= "<td align = right>".$val."</td>";			
			$out .= "</tr>";	
		}		
		$out .= "</table><br>";
			
		
		$out .= "<table border=1 width=600>";
		$out .= "<tr><th colspan=3>FILES STATISTIC</th></tr>";			
		$out .= "<tr><th>FILE</th><th>SIZE</th><th>RECORDS</th></tr>";		
		foreach($this->filesData as $k=>$v)
		{				
			$keysColor = 'white';
			if($v['KEYS_COUNT'] > $this->totalData['AVER_RECORDS_COUNT'])
			{
				if($v['KEYS_COUNT'] > 2 * $this->totalData['AVER_RECORDS_COUNT'])
				{
					$keysColor = 'red';
				}else{
					//$keysColor = 'green';
				}
			}else{
				if($v['KEYS_COUNT'] < $this->totalData['AVER_RECORDS_COUNT'] / 2)
				{
					//$keysColor = 'blue';
				}else{
					//$keysColor = 'green';					
				}
			}
			
			$out .= "<tr>";	
			$out .= "<td align=center>".$k."</td>";
			$out .= "<td align = right >".$v['SIZE']."</td>";
			$out .= "<td align = right bgcolor=$keysColor >".$v['KEYS_COUNT']."</td>";
			$out .= "</tr>";	
		}		
		$out .= "</table>";
		
		echo $out;
	}		
	 
	private function _getTitle()
	{
		$out  = "<h1>DataBase Cache Control</h1>\r\n";
		$out .= "-------------------------------------------------------<br>\r\n";
		$out .= "<b><a href=\"javascript: if (confirm('Delete all cache files?')) document.location.href='".$_SERVER['PHP_SELF']."?action=delete'\">DeleteAll</a>&nbsp&nbsp|&nbsp&nbsp</b>";
		$out .= "<b><a href=\"javascript: if (confirm('Clear garbage files?')) document.location.href='".$_SERVER['PHP_SELF']."?action=clear'\">Clear</a>&nbsp&nbsp|&nbsp&nbsp</b>";
		$out .= "<b><a href=\"javascript: if (confirm('Sync statistic?')) document.location.href='".$_SERVER['PHP_SELF']."?action=sync'\">Sync</a>&nbsp&nbsp|&nbsp&nbsp</b>";
		$out .= "<b><a href=\"javascript: if (confirm('Show statistic?')) document.location.href='".$_SERVER['PHP_SELF']."?action=stat'\">Statistic</a>&nbsp</b><br>\r\n";
		$out .= "-------------------------------------------------------<br>\r\n";
		
		return $out;
	}
}


?>