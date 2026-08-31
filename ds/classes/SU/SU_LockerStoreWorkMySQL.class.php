<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_LockerStoreWorkMySQL extends SU_LockerStoreWork
{	
	/**
	 * @var DS_MySQLWork
	 */
	private $dbWork;
	
	private $tableName;// = 'ds_locking';
	
	public function __construct($lockObjectName, $DBID, $lockTimeOut)
	{
		assert(!empty($lockObjectName));
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($lockTimeOut) && $lockTimeOut > 0);
		parent::__construct($lockObjectName, $DBID, $lockTimeOut);
		
		$this->tableName = SU_Locker::$allowedStoreObjectTypes[$lockObjectName]['TABLE'];
		$this->dbWork = DS_MySQLWork::Singleton();
		$this->dbWork->connect('LOCKING');
	}
		
	public function lock($id) 
	{
		assert(!empty($id) || $id == 0);								
				
		$lockerData  = SU_DebugTools::FormIndeteficationString()." ".SU_DebugTools::GetInternalDate();				
		try {
			$this->dbWork->connect('LOCKING');
		}catch(SU_MySQLException $e){
			ds_error('ERROR: '.$e->getMessage());
		}
		
		$mark = $this->formID($id);
		
		$addDate = GB_TypeFactory::CreateBase('GB_Date', time());
		
		$expireDate = GB_TypeFactory::CreateBase('GB_Date', $addDate->value + $this->lockTimeOut);
		
		try {
			$sql = "INSERT INTO ".$this->tableName()." 
					(mark, add_date, expire_date, locker_data)
					VALUES
					('$mark', '".$addDate->getPgFormat()."', '".$expireDate->getPgFormat()."', '$lockerData')";		
			$this->dbWork->exec($sql);
		}catch(SU_MySQLException $e){
			if(strpos($e->getMessage(), 'Duplicate entry') === false)
			{
				ds_error('ERROR: '.$e->getMessage());
			}else{
				return false;
			}
		}
		
		return true;
	}//END OF METHOD lock ( $uiid )
	
	public function unlock($id) 
	{
		assert(!empty($id) || $id == 0);			

		$mark = $this->formID($id);	
		
		try {
			$sql = "DELETE FROM ".$this->tableName()." WHERE mark = '$mark'";		
			$this->dbWork->exec($sql);	
		}catch(SU_MySQLException $e){
			ds_error('ERROR: '.$e->getMessage());
		}
										
		return true;
	}//END OF METHOD unlock ( $uiid )
	
	public function isLocked($id)
	{
		assert(!empty($id) || $id == 0);			
				
		$lockData = $this->getLockInfo($id);
		
		if($lockData["IS_EMPTY"] == 1)
		{
			return false;
		}

		if($lockData['TIME_LEFT_AFTER_SET'] > $this->lockTimeOut) 
		{
			$this->unlock($id);			
			return false;
		}
		
		return true;			
	}
	
	
	public function getLockInfo($id) 
	{
		assert(!empty($id) || $id == 0);
		
		$mark = $this->formID($id);	
		
		$result = array
		(
			"ID" => $mark,			
			"TIME_LEFT_AFTER_SET" => 0,
			"SETER_INFO" => "",
			"IS_EMPTY" => 1
		);
		
		$sql = "SELECT * FROM ".$this->tableName()." WHERE mark = '$mark'";
		$rs = $this->dbWork->get($sql);
		
		if(count($rs) == 0)
		{
			return $result;
		}

		$lockDate = GB_TypeFactory::CreateBase('GB_Date', $rs[0]['add_date']);		
		$lockerData = array($rs[0]['locker_data']);
		
		$result["TIME_LEFT_AFTER_SET"] = time() - $lockDate->value;
		$result["SETER_INFO"] = $lockerData;
		$result["IS_EMPTY"] = 0;
		
		return $result;
	}//END OF METHOD isOld ( $uiid )	
	
	/******************** PRIVATE ************/	
	public function lockCollector()
	{
		list($usec, $sec) = explode(' ', microtime());
    	$gen = (float) $sec + ((float) $usec * 100000);
		srand($gen);
		$randval = rand(0, 200);
		
		$goodVals = array(100);
		
		if(!in_array($randval, $goodVals))
		{
			return;
		}
		
		$nowDate = GB_TypeFactory::CreateBase('GB_Date', time());
		try {
			$sql = "DELETE FROM ".$this->tableName()." WHERE expire_date < '".$nowDate->getPgFormat()."'";
			$this->dbWork->exec($sql);
		}catch(SU_MySQLException $e){
			ds_error('ERROR: '.$e->getMessage());
		}
	}
	
	private function tableName()
	{
		return "".$this->tableName."_".$this->DBID;
	}
	
	/**
	 * Метод формирует имя файла для лока с полным путем
	 * Параметры:
	 * $uiid - UIID для лока
	 */
	private function formID($id)
	{
		assert(!empty($id) || $id == 0);		
		
		return  constant('CACHING_PREFIX')."_".$this->lockObjectName.'_'.$this->DBID."_".$id;		
	}//END OF METHOD fileName ( $uiid )
	
	
}
?>