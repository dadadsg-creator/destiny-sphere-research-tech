<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Менеджер занимающийся убиванием армии если не хватило какго либо из ресурсов.
 *
 */
class ME_KillArmysForUpkeep2
{
	/**
	 * Списко армий на апкипе из которых берем юнитгруппы
	 * 
	 * array(DE_Army, .......);
	 *
	 * @var unknown_type
	 */
	static private $sorceArmys = array();
	
	/**
	 * Список групп юнитов которые мы сейчас расчитываем по апкипу
	 *
	 * array('ID_UNIT' => array(DE_UnitGroup, DE_UnitGroup ...... ))
	 * 
	 * @var array()
	 */
	static private $sorceUnitGroupList = array();
	
	/**
	 * Справочные данные по юнитам кторые есть в армии.
	 * Сколько ресурсов потребляет за турн и сколько возвращает в случае смерти.
	 *
	 * array('ID_UNIT' => array
	 * 	(
	 * 		'UPKEEP' => array(RESOURCE_NAME => VALUE, .....),
	 * 		'RETURN' => array(RESOURCE_NAME => VALUE, .....),
	 * 		'POWER' => UNIT_POWER,
	 * 		'NAME' => UNIT_NAME	 	 
	 * 	)
	 * )
	 * 
	 * @var array()
	 */
	static private $unitResorcesList = array();	
	
	/**
	 * Используеться для сортировки юнитов при выборке для апкипа
	 *
	 * array(UNIT_ID => POWER, ....)
	 * 
	 * @var array()
	 */
	static private $unitPowers = array();
	
	/**
	 * Список каких юнитов и сколько есть
	 *
	 * array(ID_UNIT => array ('AMOUNT'=> AMOUNT, 'DEAD' => DEAD), ........);
	 * 
	 * @var array
	 */
	static private $haveUnits = array();
	
	/**
	 * Ресурсы которые нужны на этот период.
	 *
	 * array(RESOURCE_NAME => VALUE, .......)
	 * @var array()
	 */
	static private $needResources = array();
	
	/**
	 * Ресурсы которые есть на соте.
	 *
	 * array(RESOURCE_NAME => VALUE, .......)
	 * @var array()
	 */
	static private $haveResources = array();
	
	/**
	 * То что было на соте до начала обсчета
	 * Исопльзуеться для рассчета того сколько надо вернуть.
	 * @var array()
	 */
	static private $combResources = array();
	
	/**
	 * Ресурсы которые вернем после смерти.
	 *
	 * array(RESOURCE_NAME => VALUE, .......)
	 * @var array()
	 */
	static private $returnResources = array();
	
	/**
	 * Колличество трнов за которое идет рассчет
	 *
	 * @var integer
	 */
	static private $turns = 0;	
	
	/**
	 * Сота на которой все проиходит
	 *
	 * @var DE_Comb
	 */
	static private $comb;
	
	/**
	 * Сколько сейчас предусматриваеться итераций
	 *
	 * @var integer
	 */
	static private $iterationsCount = 5;
	
	/**
	 * Номер текущей итерации
	 *
	 * @var integer
	 */
	static private $currentIterationNumber = 1;
	
	/**
	 * Какой объем армии погиб в процентах.
	 *
	 * @var float
	 */
	static private $deadProcent = 0;
		
	/**
	 * Сколько каких юнитов убито
	 *
	 * @var float
	 */
	static private $deadUnits = array();
	
	/**
	 * Флаг досрочного выхода из итерций убийств
	 *
	 * @var boolean
	 */
	static private $endOfDeathFlag = false;
	
	static private $log = '';
	
	static public function Exec(DE_Comb $comb, $needResources, $haveResources, $turns)
	{		
		assert(is_array($needResources));
		assert(is_array($haveResources));
		assert($turns > 0);
		
		self::$log = '';
		self::$deadUnits = array();
			
		self::_init($comb, $needResources, $haveResources, $turns);		

		//Если апкипит ьнечего то выходим
		if(count(self::$haveUnits) == 0)
		{
			return;
		}
		
		
		for(self::$currentIterationNumber = 1; self::$currentIterationNumber <= self::$iterationsCount; self::$currentIterationNumber++)
		{
self::$log .= "\n------------ ITERATION ".self::$currentIterationNumber."----------------\n\n";

			if(self::$endOfDeathFlag)
			{//Если гасить больше некого то выходим
				break;
			}
			self::_killUnits();
		}			

		self::_normolizeLoses();
		
self::$log .= "DEAD_PROCENT: ".self::$deadProcent."\n";		

		self::_upplyLosesToUnitGroups();
		
		self::_cleanEmptyArmys();
		
		self::_formReturnResources();
self::$log .= "Return resources:\n".print_r(self::$returnResources, 1);

//SU_DebugTools::LogAll(self::$log, 'AnnitaUpkeep.log');
	}
	
	static public function GetReturnResorces()
	{
		return self::$returnResources;
	}
	
	static public function GetDeadProcent()
	{
		return self::$deadProcent;
	}
	
	static public function GetDeadUnits()
	{
		return self::$deadUnits;
	}
	
	/**
	 * Формируем список ресурсов которые надо вернуть
	 *
	 */
	static private function _formReturnResources()
	{				
		foreach(self::$haveResources as $resourceName => $value)		
		{		
			$currentAmount = self::$combResources[$resourceName];
				
			if(key_exists($resourceName, self::$needResources))
			{
				self::$haveResources[$resourceName] -= self::$needResources[$resourceName];
			}
			
			if(self::$haveResources[$resourceName] < 0)
			{
				self::$haveResources[$resourceName] = 0;
			}
			
			self::$returnResources[$resourceName] = round(self::$haveResources[$resourceName] - $currentAmount);
		}
	}
	
	/**
	 * После рассчета потерь округляем их значение
	 * и вычисяем сколько проценто вармии сдохло
	 *
	 */
	static private function _normolizeLoses()
	{
		$lifePower = 0;
		$deadPower = 0;

		//Нормализовали потери
		foreach(self::$haveUnits as $unitID => $unitData)		
		{
			self::$haveUnits[$unitID]['AMOUNT'] = floor($unitData['AMOUNT']);
			self::$haveUnits[$unitID]['DEAD'] = ceil($unitData['DEAD']);
			
			$lifePower += self::$haveUnits[$unitID]['AMOUNT'] * self::$unitResorcesList[$unitID]['POWER'];
			$deadPower += self::$haveUnits[$unitID]['DEAD'] * self::$unitResorcesList[$unitID]['POWER'];
		}
		
		//Посчитали процент сколько сдохло
		self::$deadProcent = round((($deadPower / ($lifePower + $deadPower)) * 100), 2);
		
		if(self::$deadProcent == 0 && $deadPower > 0)
		{
			self::$deadProcent = 0.01;
		}
	}
	
	/**
	 * Мы рассчитали потери групп юнитов тперь ихх применяем
	 *
	 */
	static public function _upplyLosesToUnitGroups()
	{
		srand((float)microtime() * 1000000);
		shuffle(self::$sorceUnitGroupList);
		
		foreach(self::$sorceUnitGroupList as $k=>$unitGroup)		
		{
			$deadUnits = self::$haveUnits[$unitGroup->getUnit()->getID()]['DEAD'];
			$amount = $unitGroup->getAmount();
			
			if($deadUnits <= 0)
			{
				continue;
			}
				
			if($amount <= $deadUnits)
			{
				$delta = -$amount;
			}else{
				$delta = -$deadUnits;
			}
			
			$unitGroup->deltaAmount($delta);
						
			// Для системного сообщения
			if (!key_exists($unitGroup->getUnit()->getLocalizedName(), self::$deadUnits)) {
				self::$deadUnits[$unitGroup->getUnit()->getLocalizedName()] = 0;
			}
			self::$deadUnits[$unitGroup->getUnit()->getLocalizedName()] += -$delta;
//SU_DebugTools::LogAll("Убили $amount ($amount) юнитов ".$unitGroup->getUnit()->getLocalizedName(), 'AnnitaUpkeep.log');
			
			//Логируем помирание юнита
			DE_LogUnitsDeathOnComb::CreateFromID(self::$comb->getID())->log('UPKEEP', $unitGroup->getUnit(), (float)$amount, (float)-$delta);
			
			if($unitGroup->getAmount() == 0)
			{
				$unitGroup->delete();
			}
		}		
	}
	
	/**
	 * Вычищаем пустые армии которые появились в результате помирания бнитов
	 *
	 */
	static private function _cleanEmptyArmys()
	{
		foreach(self::$sorceArmys as $k => $army)		
		{
			if($army->getArmyPower() == 0 && !$army->getIsDefault())
			{
				$army->delete();
			}
		}
	}
	
	/**
	 * Соновной метод убийства юнитов
	 *
	 */
	static private function _killUnits()
	{		
		asort(self::$unitPowers);		

		$haveUnitsForKillFlag = false;
		foreach(self::$unitPowers as $unitID=>$v)		
		{
			$unitData = self::$haveUnits[$unitID];
			
			$amount = $unitData['AMOUNT'];			

			if($amount == 0 || $unitID == 0)
			{
				continue;
			}

			$killPart = self::_countKillPart($unitID);
			$killUnitsCount = $amount * $killPart;
			
			if($killUnitsCount > 0)
			{
				$haveUnitsForKillFlag = true;
			}
			
self::$log .= "START KILL UNITS [".self::$unitResorcesList[$unitID]['NAME']."]($unitID) Amount: $amount Kills: $killUnitsCount KillPart: $killPart\n";				

			self::$haveUnits[$unitID]['AMOUNT'] -= $killUnitsCount;
			self::$haveUnits[$unitID]['DEAD'] += $killUnitsCount;

			self::_returnResourcesFromDeath($unitID, $killUnitsCount);
		}
		
		if(!$haveUnitsForKillFlag)
		{
			//Так как некого больше гасить
			self::$endOfDeathFlag = true;
		}
	}	
	
	
	/**
	 * Возвращаем ресурсы за смерть юнитов
	 *
	 * @param integer $unitID
	 * @param integer $killUnitsCount
	 */
	static private function _returnResourcesFromDeath($unitID, $killUnitsCount)
	{
		assert($unitID > 0);
		assert($killUnitsCount >= 0);
		
		if($killUnitsCount == 0)
		{
			return;
		}
		
		//Возвращаем ресурсы посл смерти юнитов 
		foreach(self::$unitResorcesList[$unitID]['RETURN'] as $resourceName => $value)		
		{
			$delta = $killUnitsCount * $value;
			self::$haveResources[$resourceName] += $delta;
		}
				
		//Уменяшаем апкип на умершие юниты
		$unitUpkeep = GL_LiveUpkeep::CountUnit(DE_Unit::CreateFromID((int)$unitID), $killUnitsCount);
		foreach($unitUpkeep as $resourceName => $value)		
		{
			$delta = $value * (self::$turns / self::$iterationsCount);

			self::$needResources[$resourceName] -= $delta;
			if(self::$needResources[$resourceName] < 0)
			{
				self::$needResources[$resourceName] = 0;
			}			
		}
	}
	
	/**
	 * Рассчитываем кокое колличество юнитов должно было умереть с учетом колличества итераций
	 *
	 * @param integer $unitID
	 * @return unknown
	 */
	static private function _countKillPart($unitID)
	{
		assert($unitID > 0);
		
		$part = array();

		foreach(self::$unitResorcesList[$unitID]['UPKEEP'] as $resourceName => $value)		
		{
			if(self::$haveResources[$resourceName] < self::$needResources[$resourceName])
			{
				$part[] = 1 - (self::$haveResources[$resourceName] / self::$needResources[$resourceName]);
			}
		}
		
		if(count($part) <= 0)
		{
			return 0;
		}
		
		rsort($part);

		//Учитываем колличетсво итерараций
		if(self::$currentIterationNumber == self::$iterationsCount)
		{
			$out = (float)$part[0];		
		}else{
			$out = (float)($part[0] / self::$iterationsCount);	
		}
		
		
		return $out;
	}
	
	/**
	 * инициализация атрибутов объекта
	 *
	 * @param DE_Comb $comb
	 * @param unknown_type $needResources
	 * @param unknown_type $haveResources
	 * @param unknown_type $turns
	 */
	static private function _init(DE_Comb $comb, $needResources, $haveResources, $turns)
	{
		assert(is_array($needResources));
		assert(is_array($haveResources));
		assert($turns > 0);
		
		//инициализировали простые атрибуты
		self::$combResources = array();
		self::$unitPowers = array();
		self::$endOfDeathFlag = false;
		self::$deadProcent = 0;
		self::$currentIterationNumber = 1;
		self::$turns = $turns;
		self::$needResources = $needResources;
		self::$haveResources = $haveResources;
		self::$combResources = $haveResources;
		self::$comb = $comb;
		self::$returnResources = array
		(
			'KADERIUM' => 0,
			'NANO_CRYSTAL' => 0,
			'FOOD' => 0,
			'PRODIUM' => 0,
			'STASIS_ENERGY' => 0
		);
		
		//Теперь перебираем все армии кторые пиписанны к этой соте
		$allArmysCollection = DC_CombArmys::CreateFromCombID(self::$comb->getID());
		$allArmysCollection->resetNext();
		while($army = $allArmysCollection->next())
		{
			//Если не на апкипе то то гда лесом
			if(!GL_IsArmyOnUpkeepOfComb::Count(self::$comb, $army))
			{
				continue;
			}
			
			self::$sorceArmys[] = $army;
			
			$unitsGroupCollection = DC_ArmyUnitGroups::CreateFromArmyID($army->getID());
			$unitsGroupCollection->resetNext();
			while($unitsGroup = $unitsGroupCollection->next())
			{
				self::$sorceUnitGroupList[] = $unitsGroup;	
				
				if(key_exists($unitsGroup->getUnit()->getID(), self::$haveUnits))
				{
					self::$haveUnits[$unitsGroup->getUnit()->getID()]['AMOUNT'] += $unitsGroup->getAmount();
				}else{
					self::$haveUnits[$unitsGroup->getUnit()->getID()]['AMOUNT'] = $unitsGroup->getAmount();
					self::_initUnitData($unitsGroup->getUnit());					
				}		
				
				self::$haveUnits[$unitsGroup->getUnit()->getID()]['DEAD'] = 0;		
			}						
		}		
	}
	
	/**
	 * Инициализируем сведения по 
	 *
	 * @param DE_Unit $unit
	 */
	static private function _initUnitData(DE_Unit $unit)
	{
		$unitAttributes = $unit->getAttributes();
		
		$out = array
		(
			'UPKEEP' => array(),
	  		'RETURN' => array(),
	  		'POWER' => $unitAttributes->getStasis(),
	  		'NAME' => $unit->getName()
		);
		
		$attributeNames = $unitAttributes->getAttributeNamesByTypeName('TURN_UPKEEP');
		foreach($attributeNames as $k => $resourceName)				
		{			
			$out['UPKEEP'][$resourceName] = $unitAttributes->getAttribute('TURN_UPKEEP', $resourceName);									
		}		
				
		$returnResources = GL_ReturnUnitResourcesAfterDie::CountUnit($unit, 1);
		foreach($returnResources as $resourceName => $value)		
		{			
			$out['RETURN'][$resourceName] = $value;									
		}
		
		self::$unitResorcesList[$unit->getID()] = $out;
		
		self::$unitPowers[$unit->getID()] = $unitAttributes->getStasis();
	}
}

?>