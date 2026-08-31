<?php
/**
 * Контроллер занимаеться формированием вида соты.
 * Обеспечыивает ее кехированием и т.п.
 * 
 *
 */
class CR_CombView extends CR_Base
{
	/**
	 * Сота для которой формируем внешний вид.
	 *
	 * @var DE_Comb
	 */
	private $comb;
	
	/**
	 * Флаг того что передать надо ячейки
	 *
	 * @var boolean
	 */
	private $withCells = false;
	
	/**
	 * Флаг того что надо передать здания
	 *
	 * @var boolean
	 */
	private $withBuildings = false;
	
	/**
	 * Список ресетов кеша разых частей вида соты
	 * 
	 * KEY = CELL_TYPE_ID-B|C
	 * 
	 * @var array
	 */
	private $cacheResetList = array();
	
	/**
	 * Ссылки на все контроллеры созданные.
	 * Используеться для глобального коммита
	 *  изменений кеша 
	 *
	 * @var array
	 */
	static private $instances = array();
	
	/**
	 * Конструктор.
	 *
	 * @param int $combID
	 */
	public function __construct($combID)
	{
		assert(is_integer($combID) && $combID>0);						
		parent::__construct($combID);
		
		$this->comb = DE_Comb::CreateFromID($combID);
	}
	
	/**
	 * Создание контроллера на основание ID соты
	 *
	 * @param integer $combID
	 * @return CR_CombView
	 */
	static public function CreateFromCombID($combID)
	{
		assert(is_int($combID) && $combID > 0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);
	}
	
	/**
	 * Установка флага того что надо передать ячйки
	 *
	 * @param boolean $val
	 */
	public function setWithCells($val = true)
	{
		assert(is_bool($val));
		
		$this->withCells = $val;
	}
	
	/**
	 * Усановка флагао того чтонадо передать список зданий
	 *
	 * @param boolean $val
	 */
	public function setWithBuildings($val = true)
	{
		assert(is_bool($val));
		
		$this->withBuildings = $val;
	}
	
	/**
	 * Усановка флагао того чтонадо передать список зданий
	 *
	 * @param boolean $val
	 */
	public function setWithSend($val = true)
	{
		assert(is_bool($val));
		
		$this->withBuildings = $val;
	}
	
	/**
	 * Освнонйо метод возвращающий результат в виде вида соты.
	 * Требует чо бы был задан хотя бы один параметр для передачи задния или ячейки 
	 * иначе вызывать всеравно бессмыслено. В конце после своей работы рестартит все флаги.
	 * 
	 * Это формируется для конкретного части соты.
	 * 
	 * Результат сразу же кладеться в аккамулятор выходной
	 */
	public function createView($cellTypeID)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);
		assert($this->withBuildings || $this->withCells);
		
		//рестартим то что накопилось в изменениях кеша.
		$this->commitCacheResets();
			
		$cellTypeName = DS_Comb::GetCellTypeNameByID($cellTypeID);

		$out = new TI_CombView($this->comb->getID(), $cellTypeName);
		
		//Формируем мелкие атрибуты нужные для вида соты
		$this->_formViewDetails($out);
		
		//Если нужно то передаем ячейки
		if($this->withCells)
		{
			$out->cells = $this->_getCells($cellTypeID); 
		}
		
		//если нужно передаем здания
		if($this->withBuildings)
		{
			$out->buildings = $this->_getBuildings($cellTypeID);
		}
		
		//ЗАпихиваем все на вывод
		SU_OutputHandler::Singleton()->addString(TL_CombView::Form($out), 'refresh');
		
		$this->_resetFlags();
	}
	
	private function _formViewDetails(TI_CombView $out)
	{
		$out->combMark = DE_World::CreateForCurrentWorld()->getShortName()."_".$this->comb->getCombRing()->getNumber()."_".$this->comb->getComplexNumber()."_".$this->comb->getNumber();
		
		$out->turnTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60 * 60 * 1000;
		
		if(DS_Comb::HaveOpenCentralGate($this->comb->getID()))  
		{
			$out->gateStatus = 1;
		}else{
			$out->gateStatus = 0;
		}
	}
	
	private function _getCells($cellTypeID)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);
		
		$mark = $this->_formCacheMarkCells($cellTypeID);
		
		$data = DS_Cache::Singleton()->get($mark);
		
		if(empty($data))
		{
			$data = ME_CombViewFormCells::Exec($this->comb, $cellTypeID);
			DS_Cache::Singleton()->set($mark, 3600, $data);
		}
		
		return $data;	
	}
	
	private function _getBuildings($cellTypeID)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);
		
		$mark = $this->_formCacheMarkBuildings($cellTypeID);
		
		$data = DS_Cache::Singleton()->get($mark);
		
		if(empty($data))
		{
			$data = ME_CombViewFormBuildings::Exec($this->comb, $cellTypeID);
			DS_Cache::Singleton()->set($mark, 3600, $data);	
		}
	
		return $data;
	}
	
	
	/**
	 * Формирование меток кеша для харнения ячеек
	 *
	 * @param Int $cellTypeID
	 * @return string
	 */
	private function _formCacheMarkCells($cellTypeID)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);
		
		return "COMB_VIEW_CELLS_".DE_World::CreateForCurrentWorld()->getShortName()."_".$this->comb->getID()."_".$cellTypeID;
	}
	
	/**
	 * Формирование меток кеша для харнения зданий
	 *
	 * @param Int $cellTypeID
	 * @return string
	 */
	private function _formCacheMarkBuildings($cellTypeID)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);
		
		return "COMB_VIEW_BUILDINGS_".DE_World::CreateForCurrentWorld()->getShortName()."_".$this->comb->getID()."_".$cellTypeID;
	}
	
	/**
	 * Рестарт кэша ячеек в нужной части соты
	 *
	 * @param integer $cellTypeID
	 */
	public function resetCells($cellTypeID)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);
		
		$this->cacheResetList[$key = $cellTypeID."|C"] = 1;
	}
	
	/**
	 * Рестар кеша зданий для нужной части соты
	 *
	 * @param integer $cellTypeID
	 */
	public function resetBuildings($cellTypeID)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);
		
		$this->cacheResetList[$key = $cellTypeID."|B"] = 1;
	}
	
	/**
	 * Коммит всех рестартов кеша котоырй накопились пока только в контроллере.
	 *
	 */
	public function commitCacheResets()
	{
		assert(is_array($this->cacheResetList));
		
		foreach($this->cacheResetList AS $val=>$k)
		{
			$tmp = explode('|', $val);
			
			$cellTypeID = (int)$tmp[0];
			assert($cellTypeID > 0);
			
			$type = (string)$tmp[1];
				
			if($type == 'B')
			{
				$mark = $this->_formCacheMarkBuildings($cellTypeID);
			}elseif ($type == 'C'){
				$mark = $this->_formCacheMarkCells($cellTypeID);
				// Сброс собственных кешей объекта DE_CombCells
				DE_CombCells::CreateFromCombIDAndCellTypeID($this->comb->getCombID(), $cellTypeID)->reset();
			}else{
				assert(false);
			}

			DS_Cache::Singleton()->delete($mark);
		}
	}
	
	/**
	 * Глобальный метод рестартещий кеши по всем экземпляров просмотра соты.
	 *
	 */
	static public function GlobalCacheReset()
	{
		assert(is_array(self::$instances));
		
		foreach(self::$instances as $k=>$object)
		{
			$object->commitCacheResets();
		}
	}
	
	/**
	 * Метод рестарта всех флагов.
	 *
	 */
	private function _resetFlags()
	{
		$this->withCells = false;
		$this->withBuildings = false;
	}
}

?>