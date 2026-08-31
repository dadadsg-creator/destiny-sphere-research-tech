<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Менеджер занимающийся биванием армии если не хватило какго либо из ресурсов.
 *
 */
class ME_KillArmysForUpkeep
{
	//Список ресурсов которые надо вернуть после помирания юнитов.
	static private $returnResources = array(
		'KADERIUM' => 0,
		'NANO_CRYSTAL' => 0,
		'FOOD' => 0,
		'PRODIUM' => 0,
		'STASIS_ENERGY' => 0
	);

	//Сколко надо убить юнитов в кокретной юнтито группе. пропорция.
	static private $killPart;

	//Сколько какого юнита зваалить надо
	static private $unitKillPart = array();

	//Сила армии до и после помтательства
	static private $power = array('OLD'=>0, 'NEW'=>0);
	
	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	static private $comb;
	
	/**
	 * Основной метод
	 *
	 * @param DE_Comb $comb
	 * @param array $notEnoughtResource
	 * @return boolean
	 */
	static public function Exec(DE_Comb $comb, $notEnoughtResource)
	{
		assert(is_array($notEnoughtResource) && count($notEnoughtResource)>0);

		//Так как мы статик то почистили свои атрибуты
		self::_reset();

		//Собственно начинаем мочить юнитов
		self::_killUnits($comb, $notEnoughtResource);

		return true;
	}
	
	/**
	 * Возвращет общую пропорцию бивания армии
	 *
	 * @return float
	 */
	static public function FlushKillPart()
	{
		return self::$power['NEW'] / self::$power['OLD'];
	}

	/**
	 * Ресетим атрибуты класса для повторгно использования
	 *
	 */
	static private function _reset()
	{
		foreach(self::$returnResources as $k=>$v)
		{
			self::$returnResources[$k] = 0;
		}
				
		self::$killPart = 0;
	
		self::$unitKillPart = array();

		self::$power = array('OLD'=>0, 'NEW'=>0);
	}

	
	/**
	 * Возвращает сколько ресурсов надо венуть
	 *
	 * @return unknown
	 */
	static public function FlushReturnResources()
	{
		return self::$returnResources;
	}

	/**
	 * Рассчет пропорции по которой надо убить юниты
	 *
	 * @param DE_UnitsGroup $unitGroup
	 * @param unknown_type $notEnoughtResource
	 * @return unknown
	 */
	static private function _getPartForKill(DE_UnitsGroup $unitGroup, $notEnoughtResource)
	{
		assert(is_array($notEnoughtResource) && count($notEnoughtResource)>0);

		$unit = $unitGroup->getUnit();
		$unitAttributes = $unit->getAttributes();

		self::$unitKillPart[$unit->getID()] = 0;

		$turnUpkeepResourceList = $unitAttributes->getAttributeNamesByTypeName('TURN_UPKEEP');

		if (count($turnUpkeepResourceList)<=0)
		{
			return 0;
		}

		$part = array();

		foreach ($turnUpkeepResourceList as $value)
		{
			if (key_exists($value, $notEnoughtResource))
			{
				$part[] = 1 - ($notEnoughtResource[$value]['HAVE'] / $notEnoughtResource[$value]['NEED']);
			}
		}

		if (count($part)<=0)
		{
			return 0;
		}

		rsort($part);

		self::$unitKillPart[$unit->getID()] = $part[0];
		return $part[0];
	}

	static private function _killUnits(DE_Comb $comb, $notEnoughtResource)
	{
		assert(is_array($notEnoughtResource) && count($notEnoughtResource)>0);

		$allArmysCollection = DC_CombArmys::CreateFromCombID($comb->getID());

		$allArmysCollection->resetNext();
		while($army = $allArmysCollection->next())
		{
			if(!GL_LiveUpkeep::IsArmyOnUpkeep($army))
			{
				continue;
			}

			$unitsGroupCollection = DC_ArmyUnitGroups::CreateFromArmyID($army->getID());

			$unitsGroupCollection->resetNext();

			while($unitsGroup = $unitsGroupCollection->next())
			{
				if (key_exists($unitsGroup->getUnit()->getID(), self::$unitKillPart))
				{
					self::$killPart = self::$unitKillPart[$unitsGroup->getUnit()->getID()];
				} else {
					self::$killPart = self::_getPartForKill($unitsGroup, $notEnoughtResource);
				}

				self::$power['OLD'] = $unitsGroup->getAmount() * $unitsGroup->getUnit()->getUnitPower();

				self::_setNewAmmount($unitsGroup, self::$killPart);

				self::$power['NEW'] = $unitsGroup->getAmount() * $unitsGroup->getUnit()->getUnitPower();

				if($unitsGroup->getAmount() == 0)
				{
					$unitsGroup->delete();
					$unitsGroupCollection->resetNext();
				}
								
			}

			if($army->getArmyPower() == 0 && !$army->getIsDefault())
			{
				$army->delete();
				$allArmysCollection->resetNext();
			}
		}
	}

	static private function _setNewAmmount(DE_UnitsGroup $unitsGroup, $killPart)
	{
		$oldAmount = $unitsGroup->getAmount();
		$newAmount = floor($oldAmount - ($oldAmount * $killPart));


		if($newAmount < 0)
		{
			$newAmount = 0;
		}

		$killDelta = $oldAmount - $newAmount;

		if($killPart > 0)
		{

			$resourcesForReturn = GL_ReturnUnitResourcesAfterDie::Count($unitsGroup, $killDelta);

			foreach(self::$returnResources as  $resource => $value)
			{
				if(!key_exists($resource, $resourcesForReturn))
				{
					continue;
				}

				self::$returnResources[$resource] = $value + $resourcesForReturn[$resource];
			}
		}
		
		//Логирование помирания юнитов по апкипу
		
		
		$unitsGroup->setAmount($newAmount);
	}
}

?>