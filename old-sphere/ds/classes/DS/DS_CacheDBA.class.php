<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 *
 * Кэшер запросов на селект к БД.
 *
 */
class  DS_CacheDBA extends BS_Base implements DS_CacheInterface 
{

	/**
	 * Путь для хранненния файлов кеша. корневая диреткория
	 *
	 * @var string
	 */
	protected $cacheStorePath = '';

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

	private $connections = array();

	static public $baseFileName = 'cache.db';

	static public $dbaDriverNamePriority = array("db3", "db4", "flatfile");
	
	static public $dbaDriverName = null;

	public function __construct()
	{
		parent::__construct();
						
		$this->cacheStorePath = constant('DB_CACHE_PATH');

		//Выбераем драйвер с котрым будем работать по приоритетам		
		$allowDriverNames = dba_handlers();
		
		foreach(self::$dbaDriverNamePriority as $k=>$driverName)
		{
			if(in_array($driverName, $allowDriverNames))
			{
				self::$dbaDriverName = $driverName;
				break;
			}
		}
		$this->_invariant();
	}

	static public function Singleton()
	{
		if(!(self::$instance instanceof DS_CacheDBA))
		{
			self::$instance = new DS_CacheDBA();
		}

		return self::$instance;
	}

	public function set($mark, $lifeTime, $data)
	{
		assert(!empty($lifeTime));
		assert(!empty($mark));
				
		GP_SystemStatistic::DeltaParam('DB_CACHE', 'SET_COUNT', 1);		
		SU_DebugTools::TimerStart('DS_CACHE_SET');	
		
		$indexDataTmp = array(
			'CREATION_TIME' => time(),
			'LIFE_TIME' => $lifeTime,
			'DATA' => $data
		);
		$indexData = serialize($indexDataTmp);

		$connIndex = dba_open($this->formFileName($mark), "cdt", self::$dbaDriverName);

		if(!$connIndex)
		{
			return false;
		}

		assert(is_resource($connIndex));

		if(!dba_replace($mark, $indexData, $connIndex))
		{
			return false;
		}

		dba_close($connIndex);
		
		$timeDelta = SU_DebugTools::TimerStop('DS_CACHE_SET');		
		GP_SystemStatistic::DeltaParam('DB_CACHE', 'SUMM_SET_TIME', $timeDelta);
		return true;
	}

	public function get($mark)
	{	
		assert(!empty($mark));
				
		GP_SystemStatistic::DeltaParam('DB_CACHE', 'GET_COUNT', 1);
		SU_DebugTools::TimerStart('DS_CACHE_GET');

		$fileName = $this->formFileName($mark);
		$fileID = $this->formID($mark);

		//Еси нет такой базы вобще.
		if(!file_exists($fileName))
		{
			return false;
		}

		//Если етсь то подулючаемся к индексу
		$connIndex = @dba_open($fileName, "rdt", self::$dbaDriverName);
		if(!$connIndex)
		{
//SU_DebugTools::LogAll(__METHOD__." RETURN FALSE CANT OPEN");
			return false;
		}

		//Если такой метки нет
		if(!dba_exists($mark, $connIndex))
		{
			dba_close($connIndex);
//SU_DebugTools::LogAll(__METHOD__." RETURN FALSE NOT MARK");
			$timeDelta = SU_DebugTools::TimerStop('DS_CACHE_GET');
			GP_SystemStatistic::DeltaParam('DB_CACHE', 'COUNT_MISSES', 1);
			GP_SystemStatistic::DeltaParam('DB_CACHE', 'SUMM_GET_TIME', $timeDelta);
			return false;
		}else{
			$indexData = unserialize(dba_fetch($mark, $connIndex));
			assert(count($indexData) == 3);

			$creationTime = (int)$indexData['CREATION_TIME'];
			assert($creationTime > 0);

			$lifeTime = (int)$indexData['LIFE_TIME'];
			assert($lifeTime > 0);

			$data = $indexData['DATA'];
					
			//assert(!empty($data));
		}

		//Устарели данные
		if(($creationTime + $lifeTime) < time())
		{
			dba_close($connIndex);
//SU_DebugTools::LogAll(__METHOD__." RETURN FALSE TO OLD");

			$timeDelta = SU_DebugTools::TimerStop('DS_CACHE_GET');
			GP_SystemStatistic::DeltaParam('DB_CACHE', 'COUNT_MISSES', 1);
			GP_SystemStatistic::DeltaParam('DB_CACHE', 'SUMM_GET_TIME', $timeDelta);
			return false;
		}

		dba_close($connIndex);
//SU_DebugTools::LogAll(__METHOD__." RETURN FALSE RETURN TRUE");

		$timeDelta = SU_DebugTools::TimerStop('DS_CACHE_GET');
		GP_SystemStatistic::DeltaParam('DB_CACHE', 'COUNT_MACHES', 1);
		GP_SystemStatistic::DeltaParam('DB_CACHE', 'SUMM_GET_TIME', $timeDelta);
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

		$markSumm = $markSumm * 3;

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