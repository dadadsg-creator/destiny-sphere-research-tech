<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/07/01 08:46:26 $';

/**
 * Класс результатов работы класса GL_ResourceStealing/ Показывает сколько ресусрсов было украджено, сколько было сохранено в хранилищах.
 */

class DE_ResourcesForStealing extends BS_Base
{
	
	/**
	 * Ресурсы доступные на соте. ИСпользуються для проверки коректности.
	 *
	 * @var unknown_type
	 */
	private $combResources = array();
	
	/**
	 * Ресурсы которые можно спереть с соты.
	 * @var array
	 */
	private $resourceForSteal = array();
	
	/**
	 * Ресурсы сохраненные в храниолище.	 
	 * @var array
	 */
	private $resourceForStore = array();
	
	
	/**
	 * Ресурсы украденные с соты
	 * @var array
	 */
	private $stealedResources = array();
		
	/**
	 * Текущая емкость армии. Уменьшаеться по мере загрузки
	 * @var int
	 */
	private $armyCurentCapicity;
	
	/**
	 * Армия в которую грабим.	 
	 * @var DE_Army
	 */
	private $army;
	
	/**
	 * Сота скоторой грабим.	 
	 * @var DE_Comb
	 */
	private $comb;
	
	/**
	 * Коснтуртор класса.
	 *
	 * @param DE_Army $army
	 * @param DE_Comb $comb
	 */
	public function __construct(DE_Army $army, DE_Comb $comb)
	{
		parent::__construct();
		
		$combAttributes = $comb->getAttributes();
		foreach(GB_ResourcesList::$stealingResources as $k=>$resourceName)
		{
			$this->resourceForSteal[$resourceName] = 0;
			$this->resourceForStore[$resourceName] = 0;
			$this->stealedResources[$resourceName] = 0; 
			$this->combResources[$resourceName] = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true);
			
			//У нас на комбе иногда может получиться отрицательное значение ресов
			/*if($this->combResources[$resourceName] < 0)
			{
				$this->combResources[$resourceName] = 0;
			}*/			
		}
		
		$this->armyCurentCapicity = $army->getCapacity();
		assert($this->armyCurentCapicity >= 0);
		$this->army = $army;

		$this->comb = $comb;
		
		$this->_invariant('construct');

	}
	
	/**
	 * Возвращаессылку на соту которую грабим
	 * @return DE_Comb
	 */
	public function getComb()
	{
		$this->_invariant();
		
		return $this->comb;
	}
	
	/**
	 * Возвращает ссылку н аобъект армии которая грабит	 
	 * @return DE_Army
	 */
	public function getArmy()
	{
		$this->_invariant();
		
		return $this->army;
	}
	
	/**
	 * Задаем ресуср для сохранения
	 *
	 * @param string $resourceName
	 * @param int $value
	 */
	public function setResourceForStore($resourceName, $value)
	{		
		assert(in_array($resourceName, $this->resourceForSteal));
		assert($value >= 0);
		
		//Проверка корректности
		assert($value <= $this->combResources[$resourceName]);
		
		$this->resourceForStore[$resourceName] = $value;
	}
	
	/**
	 * Прибаляем награбленного ресусра. Попутно уменьшаетьсяколличество ресурса для грабежа и падает емкость армии
	 *
	 * @param string $resourceName
	 * @param int $value
	 */
	public function deltaStealedResource($resourceName, $value)
	{
		//Общие проверки целостности
		$this->_invariant();
		
		assert(key_exists($resourceName, $this->resourceForSteal));
		assert($value >= 0);
		
		//Првроверка погрешности дельты которая может возникать из-за всяких округдений.
		if($value > $this->resourceForSteal[$resourceName])
		{
			$value = $this->resourceForSteal[$resourceName];
		}
		
		$this->stealedResources[$resourceName] += $value;
		
		$stazisDelta = DE_Resource::CreateFromName($resourceName)->convertToStazis($value);
		$this->armyCurentCapicity = ceil($this->armyCurentCapicity - $stazisDelta);
		
		//Иногда из-за округлений уходит.
		if($this->armyCurentCapicity < 0)
		{
			$this->armyCurentCapicity = 0;
		}
		
/*$str = "
RESOURCE_NAME = ".$resourceName."
DELTA = ".$value."
DELTA_STAZIS = ".$stazisDelta."
CapicityBefore = ".$pre."
CapicityAfter = ".$this->armyCurentCapicity."";
DebugTools::LogUIID(10343, $str, __CLASS__);*/	

		$this->resourceForSteal[$resourceName] = $this->resourceForSteal[$resourceName] - $value;
		
		$delta = $this->combResources[$resourceName] - $this->resourceForStore[$resourceName];
		
		assert($this->armyCurentCapicity >= 0);
		assert($this->resourceForSteal[$resourceName] >= 0);
		
	}
	
	/**
	 * Задем ресурс для грабежа
	 *
	 * @param string $resourceName
	 * @param значение $value
	 */
	public function setResourceForSteal($resourceName, $value)
	{
		$this->_invariant();
		assert(in_array($resourceName, $this->resourceForSteal));
		assert($value >= 0);
		
		$this->resourceForSteal[$resourceName] = $value;		
		
		assert($this->resourceForSteal[$resourceName] <= $this->combResources[$resourceName]);
	}
	
	/**
	 * Возвращает колличество ресурса которое достпуно для грабежа
	 *
	 * @param string $resourceName
	 * @return int
	 */
	public function getResourceForSteal($resourceName)
	{
		$this->_invariant();
		assert(key_exists($resourceName, $this->resourceForSteal));
		
		return $this->resourceForSteal[$resourceName];
	}
	
	/**
	 * Возвращает колличество ресурса которое разграбленно
	 *
	 * @param string $resourceName
	 * @return int
	 */
	public function getStealedResources($resourceName)
	{
		$this->_invariant();
		assert(key_exists($resourceName, $this->resourceForSteal));
		
		return $this->stealedResources[$resourceName];
	}
	
	/**
	 * Возвращает ресурс доступнфы для грабежа в стазисе
	 *
	 * @param string $resourceName
	 * @return int
	 */
	public function getResourceForStealInStazis($resourceName)
	{
		$this->_invariant();
		assert(key_exists($resourceName, $this->resourceForSteal));
		
		return floor($this->resourceForSteal[$resourceName] * DE_Resource::CreateFromName($resourceName)->getExchangeRate());
	}
		
	/**
	 * Возвращает имя ресурса которого меньше всего но не 0
	 *
	 * @return string
	 */
	public function getMinResourceForStealName()
	{
		$this->_invariant();
		
		natsort($this->resourceForSteal);
		
		foreach($this->resourceForSteal as $resourceName=>$value)
		{
			if(floor($value) > 0)
			{
				return $resourceName;
			}
		}	
		
		return '';	
	}
	
	/**
	 * Считает колличество типов ресурсов которые еще можно ограбить.
	 * @return boolean
	 */
	public function countNotEmptyResourcesForSteal()
	{
		$this->_invariant();

		$counter = 0;	
				
		foreach($this->resourceForSteal as $resourceName=>$value)
		{
			if(floor($value) > 0)
			{
				$counter++;
			}
		}	
		
		return $counter;	
	}
	
	/**
	 * Текущая свободная емкость армии
	 *
	 * @return int
	 */
	public function getCurentCapicity()
	{
		$this->_invariant();
		
		return $this->armyCurentCapicity;		
	}
	
	/**
	 * Проверка целостности класса
	 *
	 */
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		assert($this->armyCurentCapicity>=0);
		assert(is_array($this->resourceForSteal) && (count($this->resourceForSteal) == count(GB_ResourcesList::$stealingResources)));
		assert(is_array($this->resourceForStore) && (count($this->resourceForStore) == count(GB_ResourcesList::$stealingResources)));
		assert(is_array($this->stealedResources) && (count($this->stealedResources) == count(GB_ResourcesList::$stealingResources)));
		
		assert($this->comb instanceof DE_Comb);
		assert($this->army instanceof DE_Army);
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}		
}

?>