<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * ВЫчищаем из армии потери
 */
class ME_CleanArmyLooses
{
	static private $forLog;
	
	static public function exec(DE_Army $atacker, DE_CombDefendArmy $defender)
	{
		self::$forLog .= "\n	CLEAN LOSES OF ATTACKER\n";		
		self::$forLog .= "CLEAN ARMY '".$atacker->getName()."'\n";
		
		self::ClearGroups($atacker->getUnitsGroupCollection());
		
		self::$forLog .= "\n	CLEAN LOSES OF DEFENDER\n";								
		$defender->getCombDefendArmysCollection()->resetNext();
		while($defendArmy = $defender->getCombDefendArmysCollection()->next())
		{
			self::$forLog .= "CLEAN ARMY ".$defendArmy->getName()."\n";			
			self::ClearGroups($defendArmy->getUnitsGroupCollection());										
		}


		$defender->getCombDefendUnitGroupsCollection()->resetNext();
		while($combDefendGroup = $defender->getCombDefendUnitGroupsCollection()->next())
		{
			self::$forLog .= "CLEAN DEFEND UNITS GROUP '".$combDefendGroup->getUnit()->getUnitName()."'(".$combDefendGroup->getAmount().") \n";			
			if($combDefendGroup->getAmount() == 0)
			{		
				$key = $defender->getCombDefendUnitGroupsCollection()->getCurrentPosition();			
				self::$forLog .= "DEFEND UNITS GROUP '".$combDefendGroup->getUnit()->getUnitName()."' (".$combDefendGroup->getAmount().") IS DELETED!\n";
				$combDefendGroup->delete();				
				$defender->getCombDefendUnitGroupsCollection()->setPointerToPosition($key);
			}			
		}					
		
		DE_BattleSystemLog::Insert(self::$forLog);
		self::$forLog = '';
	}
	
	static public function ClearGroups(DC_CollectionInterface $unitsGroupsCollection)
	{
		$forDelete = array();
		$unitsGroupsCollection->resetNext();
		while($unitsGroup = $unitsGroupsCollection->next())
		{
			self::$forLog .= "CLEAN UNITS GROUP '".$unitsGroup->getArmy()->getName()."' => '".$unitsGroup->getUnit()->getUnitName()."' (".$unitsGroup->getAmount().")\n";				
			self::_applyeLoses($unitsGroup);
			
			if($unitsGroup->getAmount() == 0)
			{				
				self::$forLog .= "UNITS GROUP '".$unitsGroup->getArmy()->getName()."' => '".$unitsGroup->getUnit()->getUnitName()."' (".$unitsGroup->getAmount().")[".$unitsGroup->getID()."] MUST BE DELETED\n";								
				$forDelete[] = $unitsGroup;				
			}
		}
		
		foreach($forDelete as $k=>$unitsGroup)
		{
			self::$forLog .= "UNITS GROUP '".$unitsGroup->getArmy()->getName()."' => '".$unitsGroup->getUnit()->getUnitName()."'[".$unitsGroup->getID()."] (".$unitsGroup->getAmount().") IS DELETED!\n";											
			$unitsGroup->delete();
		}
	}
	
	static private function  _applyeLoses(DE_UnitsGroup $unitsGroup)
	{			
		assert($unitsGroup instanceof DE_UnitsGroup);
				
		$oldAmount = $unitsGroup->getAmount();
		$loses = $unitsGroup->getLoses();
		$newAmount = $unitsGroup->getAmount() - $unitsGroup->getLoses();
		
		$unitsGroup->setLoses((float)0);	
		$unitsGroup->setAmount((float)$newAmount);		
		
		self::$forLog .= "APPLY LOSES FOR UNITS GROUP '".$unitsGroup->getArmy()->getName()."' => '".$unitsGroup->getUnit()->getUnitName()."' (oldAmount = $oldAmount loses = $loses newAmount = $newAmount)\n";						
		
		// Если потери не меньше 1 и у нас включено логирование потерь и потери не у игрока DestinySphere
		if (DE_WorldSettings::CreateForCurentWorld()->get('CALC_ARMY_WEIGHT') && $loses >= 1 && $unitsGroup->getArmy()->getUserInstance()->getUserInstanceID() != 0) {
			// Переводим потери в прод
			$delta = floor($loses)*($unitsGroup->getUnit()->getStazis()/10);
			assert($delta > 0);
			
			$armyWeight = DE_ArmyWeight::CreateFromUserInstanceID($unitsGroup->getArmy()->getUserInstance()->getUserInstanceID());
			$armyWeight->deltaArmyWeightLost($delta);
			
		}
		
		
		
		return $newAmount;
	}		
}


?>