<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_LockerStoreWorkFiles  extends SU_LockerStoreWork
{	
	/**
	 * Путь куда кладем фалы локинга
	 *
	 * @var string
	 */
	private $lockPath;		
	
	public function __construct($lockObjectName, $DBID, $lockTimeOut)
	{
		assert(!empty($lockObjectName));
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($lockTimeOut) && $lockTimeOut > 0);
		
		parent::__construct($lockObjectName, $DBID, $lockTimeOut);
				
		assert(defined ('LOCK_PATH'));
		$this->lockPath = constant('LOCK_PATH')."/";
	}
		
	public function lock($id) 
	{
		assert(!empty($id) || $id == 0);								
				
		$lockerData  = SU_DebugTools::FormIndeteficationString()." ".SU_DebugTools::GetInternalDate()."\n";				
		
		$fileName = $this->fileName($id);
		
		assert(file_put_contents($fileName, $lockerData, FILE_APPEND));							

		return true;
	}
	
	public function unlock($id) 
	{
		assert(!empty($id) || $id == 0);			

		$fileName = $this->fileName($id);	
			
		@unlink($fileName);

		return true;
	}
	
	public function isLocked($id)
	{
		assert(!empty($id) || $id == 0);			
				
		if($this->isOld($id)) 
		{
			$this->unlock($id);			
			return false;
		}
		
		clearstatcache();
		$fileStat = @stat($this->fileName($id));
		
		if($fileStat)
		{
			$out = true;
		}else{
			$out = false;
		}		
		
		return $out;			
	}
	
	
	public function getLockInfo($id) 
	{
		assert(!empty($id) || $id == 0);
		
		$fileName = $this->fileName($id);
		
		$result = array
		(
			"ID" => $fileName,			
			"TIME_LEFT_AFTER_SET" => 0,
			"SETER_INFO" => ""
		);
		
		if(!file_exists($this->fileName($id))) 
		{						
			return $result;
		}
		
		$fileStat = stat($fileName);
		$fileCreateTime = $fileStat['ctime'];
		
		$result["TIME_LEFT_AFTER_SET"] = time() - $fileCreateTime;
		$result["SETER_INFO"] = file($fileName);
		
		return $result;
	}
		
	public function lockCollector()
	{
		$randval = rand(0, 200);
		
		if($randval = 100)
		{
			$this->cronLockCollector();
		}
	}
	
	public function cronLockCollector()
	{					
		$str  = "LOCK PATH: ".constant('LOCK_PATH')."\n";
		$str .= "LOCK TIMEOUT: ".constant('USERACTION_LOCK_TIME_OUT')."\n";
		$str .= "START TIME: ".SU_DebugTools::GetInternalDate()."\n";
		
		$countTotal = 0;
		$countUnlinked = 0;
		
		if($handle = opendir($this->lockPath)) 
		{	
   			while(false !== ($file = readdir($handle))) 
   			{ 
    			if($file == '.'  || $file == '..')
    			{
    				continue;
    			}
    			
    			$countTotal++;
    			
    			if(!file_exists($this->lockPath.'/'.$file)) 
    			{
    				continue;
    			}
    			
        		if((mktime() - @filectime($this->lockPath.'/'.$file)) > $this->lockTimeOut) 
        		{					
        			$countUnlinked++;
					@unlink($this->lockPath.'/'.$file);
				}
    		}
	
    		closedir($handle); 
		} else {
			$str .= "ERROR: Unable to open dir <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< !!!";
		}
		
		$str .= "\n";
		$str .= "TOTAL LOCK FILES: ".$countTotal."\n";
		$str .= "LOCK FILES DELETED: ".$countUnlinked."\n";				
		
		SU_DebugTools::LogAll($str, "LockFileCollector.log");
	}
		
	private function fileName($id, $getShort = false)
	{
		assert(!empty($id) || $id == 0);		

		if($getShort)
		{
			return  $this->lockObjectName.'_'.$this->DBID."_".$id;
		}else{
			return  $this->lockPath.$this->lockObjectName.'_'.$this->DBID."_".$id.'.lock';
		}
		
	}
		
	private function isOld($id) 
	{
		assert(!empty($id) || $id == 0);		
		
		clearstatcache();
		$fileStat = @stat($this->fileName($id));

		if(!$fileStat) 
		{		
			return false;
		}
				
		$fileCreateTime = $fileStat['ctime'];
		
		if((time() - $fileCreateTime) > $this->lockTimeOut) 
		{		
			return true;	
		}else{		
			return false;
		}

	}	
}
?>