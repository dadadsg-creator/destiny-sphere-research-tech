<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс для локинга в сиситеме
 * 
 */
 class SU_Locker extends BS_Base 
 {
	/** 
	 * ID БД в которой работаем 
	 * 
	 * */
	private $DBID;	

	/** 
	 * 
	 * Массив UIID залоченных 
	 * 
	 * */
	private $locked = array();	

	/** 
	 * 
	 * Тип объекта локом которого занимаемся 
	 * 
	 * */
	private $lockObject;	

	/**
	 * Enter description here...
	 *
	 * @var SU_LockerStoreWork
	 */
	private $storeWorkManager;
	
	/**
	 * Атрибт с разыми экземплярами локеров
	 *
	 * @var array
	 */
	static private $instances = array();	
	
	/**
	 * Сведения битого лок файла
	 *
	 * @var string
	 */
	static public $coruptedLockFileData = '';

	private $storeManagerType = 'MySQL';
	//private $storeManagerType = 'Files';
	
	static public $allowedStoreObjectTypes = array
	(
		'REFRESH' => array('TABLE' => 'ds_locking'), //Не используеться в данный момент
		'USERACTION' => array('TABLE' => 'ds_locking'), 
		'EVENT' => array('TABLE' => 'ds_locking'), 
		'ACTION' => array('TABLE' => 'ds_locking'), //На самомо деле тоже нафиг ненужно.  
		'MONEY_ACCOUNT' => array('TABLE' => 'ds_locking'), 
		'TEST_SYSTEM_FUNCTION' => array('TABLE' => 'ds_locking'), 
		'EVENT_QUEUE' => array('TABLE' => 'ds_locking'), 		
	);
			
	public function __construct($lockObjectName, $DBID, $lockTimeOut = null)
	{						
		parent::__construct();
		
		assert(key_exists($lockObjectName, self::$allowedStoreObjectTypes));
		$this->lockObject = $lockObjectName;
		
		$this->DBID = (int)$DBID;
		assert($this->DBID >= 0);
		
		assert(is_null($lockTimeOut) || (is_int($lockTimeOut) && $lockTimeOut > 0));		
		if(is_null($lockTimeOut))
		{
			assert(defined('USERACTION_LOCK_TIME_OUT'));
			$lockTimeOut = (int)constant('USERACTION_LOCK_TIME_OUT');					
			assert($lockTimeOut > 0);			
		}		
		assert(is_int($lockTimeOut) && $lockTimeOut > 0);
		
		switch ($this->storeManagerType)
		{
			case 'Files':
				$this->storeWorkManager = new SU_LockerStoreWorkFiles($lockObjectName, $DBID, $lockTimeOut);							
				break;
			case 'MySQL': 
				$this->storeWorkManager = new SU_LockerStoreWorkMySQL($lockObjectName, $DBID, $lockTimeOut);							
				break;
			default:
				ds_error("Unknown storeManagerName = ".$this->storeManagerType);
		}	
		$this->lockCollector();	
	}
	
	static public function Create($lockObjectName, $DBID)
	{
		assert(!empty($lockObjectName));
		assert($DBID >= 0);
		
		$key = $lockObjectName."_".$DBID;
		
		if(!key_exists($key, self::$instances))
		{
			self::$instances[$key] = new SU_Locker($lockObjectName, $DBID);
		}
		return self::$instances[$key];
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $lockObjectName
	 * @return SU_Locker
	 */
	static public function CerateForCurrentDB($lockObjectName)
	{
		assert(!empty($lockObjectName));
		
		assert(isset($_SESSION) && key_exists("dbId", $_SESSION));
		
		$DBID = (int)$_SESSION['dbId'];
		return self::Create($lockObjectName, $DBID);
	}
	
	static public function TestLockFilesInAllObjects()
	{		
		foreach(self::$instances as $k=>$locker)
		{
			if(!$locker->testLockFiles())
			{
				return false;
			}
		}
		
		return true;
	}
	
	static public function UnlockAllInAllObjects()
	{
		foreach(self::$instances as $k=>$locker)
		{
			$locker->unlockAll();		
		}
		
		return true;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return boolean
	 */
	public function lockWithTest($id)
	{
		assert(!empty($id) || $id == 0);
		
		// Инициализируем первую попытку проверить на залоченность
		$attempt = 0;
		
		while ($this->isLocked($id) && $attempt < 10) {
			// Ждем 5 секунд
			sleep(5);
			//$out = $this->getLockInfo($id);
			//$out['attempt'] = $attempt;
			//SU_DebugTools::LogAll($out, 'UserLockingErrors1.log', 1, 1);
			$attempt ++;
		}
		
		if($this->isLocked($id))
		{
			return false;
		}
		
		if(!$this->lock($id))
		{
			return false;
		}
		
		return true;
	}
	
	public function isLocked($id)
	{					
		assert(!empty($id) || $id == 0);	
						
		//Если я залочил это объект то меня это не ипёт.
		if(key_exists($id, $this->locked))
		{
			return false;
		}
		//return true;
		return $this->storeWorkManager->isLocked($id);
	}
	
	public function lock($id) 
	{
		assert(!empty($id) || $id == 0);								

		//Если я залочил это объект то меня это не ипёт.
		if(key_exists($id, $this->locked))
		{
			return true;
		}
		
		if(!$this->storeWorkManager->lock($id))
		{
			return false;
		}
		
		$this->locked[$id] = 1;
		
		return true;
	}
	
	public function unlock($id) 
	{
		assert(!empty($id) || $id == 0);	
		assert(key_exists($id, $this->locked));
							
		$this->storeWorkManager->unlock($id);
				
		unset($this->locked[$id]);	
							
		return true;
	}

	public function testLockFiles() 
	{				
		if($this->storeManagerType == 'MySQL')
		{
			return true;
		}
			
		foreach ($this->locked as $id=>$v )
		{				

			$lockData = $this->storeWorkManager->getLockInfo($id);
					
			if(empty($lockData['SETER_INFO']))
			{
				self::$coruptedLockFileData = "NOT EXISTS DATA FOR: ".$lockData['ID'];
				return false;
			}
			
			//Проверяем не попртил ли кто наш файл 						
			if(count($lockData["SETER_INFO"]) > 1)
			{						
				self::$coruptedLockFileData = $lockData;									
				return false;
			}
		}
					
		return true;	
	}	
	
	public function unlockAll() 
	{			
		foreach($this->locked as $id=>$v)
		{	
			$this->unlock($id);			
		}		
	}
	
	public function lockCollector()
	{
		$this->storeWorkManager->lockCollector();
	}
	
	public function getLockInfo($id) 
	{
		assert(!empty($id) || $id == 0);	
		
		return $this->storeWorkManager->getLockInfo($id);
	}
	
	public function __destruct()
	{
		parent::__destruct();
		$this->unlockAll();
	}

 }
?>