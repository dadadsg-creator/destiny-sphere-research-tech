<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * 
 * Кэшер запросов на селект к БД.
 * 
 */
class  DS_CacheFiles extends BS_Base
{
	
	/**
	 * Путь для хранненния файлов кеша. корневая диреткория
	 *
	 * @var string
	 */
	protected $cacheStorePath = '';		
	
	
	/**
	 * Время еа которое старим файл искусственно
	 *
	 * @var integer
	 */
	protected $timeDecriment = 10000000;

	
	/**
	 * Массив с кешом в памяти того что уже извлекалось из фалов
	 *
	 * @var array()
	 */
	static private $memoryCache = array();
	
	
	/**
	 * Количество подпапок для хранения кеша
	 *
	 * @var integer
	 */
	static public $devisor = 1024;
	
	/**
	 * На сколько сдвигаеться номер папки цеклически в случае чета или нечета первичного номера
	 *
	 * @var integer
	 */
	static public $move = 10;
	
	static private $instance;
	
	public function __construct()
	{				
		parent::__construct();
		
		$this->cacheStorePath = constant('DB_CACHE_PATH');				
		
		$this->_invariant();
	}
	
	static public function Singleton()
	{
		if(!(self::$instance instanceof DS_CacheFiles))
		{
			self::$instance = new DS_CacheFiles();
		}
							
		return self::$instance;
	} 
	
	private function setToMemory($mark, $data)
	{		
		GP_SystemStatistic::DeltaParam('DS_CACHE', 'MEMORY_CACHE_PUTS', 1);		
		$memoryStart = memory_get_usage();
		self::$memoryCache[$mark] = $data;
		$memoryUsed = memory_get_usage() - $memoryStart;
		GP_SystemStatistic::DeltaParam('DS_CACHE', 'MEMORY_CACHE_USE MEMORY', $memoryUsed);								
	}		
	
	private function getFromMemory($mark)
	{		
		if (array_key_exists($mark, self::$memoryCache))
		{
			GP_SystemStatistic::DeltaParam('DS_CACHE', 'MEMORY_CACHE_HITS', 1);
			//GP_SystemStatistic::DeltaParam('DS_CACHE', 'MEMORY_CACHE_HIT_MARK_'.$mark, 1);			
			return self::$memoryCache[$mark];
		}
		else 
		{
			return false;
		}
	}

	public function set($mark, $lifeTime, $data)
	{
		$this->_invariant();
		
		assert(!empty($lifeTime));
		assert(!empty($mark));		
		
		//$this->setToMemory($mark, $data);

		$toFile = $data."\n\r".crc32($data);
		
		if(file_exists($this->_formFileName($mark)))
		{
			$GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = true;
			unlink($this->_formFileName($mark));
			$GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = false;
		}
		
		if (!$handle = fopen($this->_formFileName($mark), 'a')) 
		{
        	 trigger_error("Cannot create file for cache store", E_USER_ERROR);
    	}
    	
    	flock($handle,2);
    	
    	if (fwrite($handle, $toFile) === FALSE) 
    	{
    		trigger_error("Cannot store data in file for cache store", E_USER_ERROR);        	
    	}
    	
        flock($handle,3);
    	fclose($handle);
    	
        $GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = true;
    	chmod ($this->_formFileName($mark), 0777);		
    	
    	$setTime = time() + $lifeTime - $this->timeDecriment;
    	touch($this->_formFileName($mark), $setTime);    
    	$GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = false;
    	
    	GP_SystemStatistic::DeltaParam('DS_CACHE', 'PUTS', 1);    	
	}
	
	public function get($mark)
	{
		$this->_invariant();
		assert(!empty($mark));
		
		if ($row = $this->getFromMemory($mark))
		{
			GP_SystemStatistic::DeltaParam('DS_CACHE', 'HIT', 1);    				
			return $row;
		}
		
		if(!file_exists($this->_formFileName($mark)))
		{
			GP_SystemStatistic::DeltaParam('DS_CACHE', 'MISSED', 1); 			
			return '';
		}else{
			
			$GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = true;
			$fileemTime = filemtime($this->_formFileName($mark));
			$GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = false;
			
			if($fileemTime < (time() - $this->timeDecriment))
			{
				$GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = true;
				unlink($this->_formFileName($mark));
				$GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = false;
				
				GP_SystemStatistic::DeltaParam('DS_CACHE', 'MISSED', 1); 				
				return false;
			}else{
				$GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = true;
				$out = file($this->_formFileName($mark));
				$GLOBALS["DS_ERROR_HANDLER_STOP_E_WARNING"] = false;

				if(count($out)!=2)
				{
					GP_SystemStatistic::DeltaParam('DS_CACHE', 'MISSED', 1); 	
					return false;
				}

				if(crc32(trim($out[0])) != trim($out[1]))
				{
					GP_SystemStatistic::DeltaParam('DS_CACHE', 'MISSED', 1); 	
					return false;
				}
				
				//$this->setToMemory($mark, $out[0]);
				GP_SystemStatistic::DeltaParam('DS_CACHE', 'HIT', 1); 	
				return $out[0];
			}
     	}		
	}	
	
	public function _formFileName($mark)
	{		
		
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
		
		$path = $this->cacheStorePath.'/'.(string)$tmpFolder;		
			
		if(!file_exists($path))
		{
			mkdir($path);	
			chmod ($path, 0777);		
		}		
		
		return 	$path.'/'.$mark.".cache";	
	}
	
	private function _invariant()
	{
		assert(is_string($this->cacheStorePath) && !empty($this->cacheStorePath));
		assert(is_int($this->timeDecriment) && $this->timeDecriment>0);
		assert(is_array(self::$memoryCache));
		assert(self::$move < self::$devisor);
	}
			
	public function __destruct()
	{
		parent::__destruct();
	}
	
}

?>