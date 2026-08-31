<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * 
 * Кэшер запросов на селект к БД.
 * 
 */
class DS_CacheBinary extends BS_Base
{
		
	/** Базовый папка для файлов кеша */
	public $cacheStorePath = '';		
			
	/** Делитель для формирования имени папки */
	static public $devisorFirst = 1024;
	
	/** Делитель вторичный для распределения имен файлов внутри папки */
	static public $devisorSecond = 1024;
	
	/** На сколько сдвигаеться номер папки цеклически в случае чета или нечета первичного номера */
	static public $move = 10;
	
	/** Экземпляр кешера */
	static private $instance;
				
	static public $baseFileName = 'cache.db';
	
	static private $dbaDriverName = "flatfile";
	
	public function __construct()
	{				
		parent::__construct();
		
		$this->cacheStorePath = constant('DB_CACHE_PATH');				
		
		$this->_invariant();
	}
	
	static public function Singleton()
	{
		if(!(self::$instance instanceof DS_Cache))
		{
			self::$instance = new DS_Cache();
		}
				
		return self::$instance;
	} 
	
	
	
	public function set($mark, $lifeTime, $data)
	{	
//DebugTools::LogAll('START_SET = '.$mark);	
		assert(!empty($lifeTime));
		assert(!empty($mark));		
		
		$indexDataTmp = array(
			'CREATION_TIME' => time(),
			'LIFE_TIME' => $lifeTime,
			'DATA' => $data
		);		
		$indexData = serialize($indexDataTmp);
											
		$connIndex = dba_open($this->formFileName($mark), "c", self::$dbaDriverName);
		assert(is_resource($connIndex));				
		
		assert(dba_replace($mark, $indexData, $connIndex));		
				
		dba_close($connIndex);			
	}
	
	public function get($mark)
	{		
//DebugTools::LogAll('START_GET_'.$mark);
		assert(!empty($mark));
				
		$fileName = $this->formFileName($mark);				
		$fileID = $this->formID($mark);		
		
		//Еси нет такой базы вобще.
		if(!file_exists($fileName))
		{
			return false;
		}
		
		//Если етсь то подулючаемся к индексу
		if(key_exists($fileID, $this->connections))
		{
			$connIndex = $this->connections[$fileID];
			assert(is_resource($connIndex));					
		}else{
			$connIndex = dba_open($fileName, "r", self::$dbaDriverName);
			assert(is_resource($connIndex));					
		
			$this->connections[$fileID] = $connIndex;
		}
			
		//Если такой метки нет
		if(!dba_exists($mark, $connIndex))
		{
			return false;
		}else{
			$indexData = unserialize(dba_fetch($mark, $connIndex));
			assert(count($indexData) == 3);
			
			$creationTime = (int)$indexData['CREATION_TIME'];
			assert($creationTime > 0);
			
			$lifeTime = (int)$indexData['LIFE_TIME'];
			assert($lifeTime > 0);			
			
			$data = $indexData['DATA'];
			assert(!empty($data));
		}
		
		//Устарели данные
		if(($creationTime + $lifeTime) < time())
		{
			return false;
		}
											
		return $data;		
	}	
	
	public function formFileName($mark)
	{
		assert(!empty($mark));
		
		return $this->cacheStorePath."/".$this->formID($mark)."_".self::$baseFileName;
	}
	
	public function formID($mark)
	{		
		assert(!empty($mark));
		
		$markSumm = 0;
		
		for($i=0; $i<strlen($mark);$i++)
		{
			$markSumm += ord($mark[$i]);
		}
	
		$tmpFolder = (int)fmod($markSumm, self::$devisor);
			
		for($i=9; $i>0;$i--)
		{
			if(fmod($markSumm, $i) == 0)
			{	
				$newFolder = $tmpFolder + $i*self::$move;
			}
		}
		
		if($newFolder > self::$devisor)
		{
			$tmpFolder = $newFolder - self::$devisor;
		}elseif($newFolder < 0){
			$tmpFolder = self::$devisor + $newFolder;
		}	
				
		return 	(int)$tmpFolder;	
	}
	
	private function _invariant()
	{
		assert(is_string($this->cacheStorePath) && !empty($this->cacheStorePath));				
		assert(self::$move < self::$devisor);
	}
			
	public function __destruct()
	{
		parent::__destruct();
		
		foreach($this->connections as $folderName => $conn)
		{			
			if(is_resource($conn))
			{
				dba_close($conn);
			}		
		}
	}
	
}

?>