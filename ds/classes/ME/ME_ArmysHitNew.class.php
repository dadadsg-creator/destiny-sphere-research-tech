<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * 
 *Менеджер удара армий друг под другу
 *
 */
class ME_ArmysHitNew
{
	static private $forLog = '';
	
	static public function Exec (DE_Army $armyAttack, DE_CombDefendArmy $armyDefend, $attakerHaveHit = true, $defenderHaveHit = true)
	{		
		assert(is_bool($attakerHaveHit));
		assert(is_bool($defenderHaveHit));
		
		//Расчет силы удара атакующего
		self::$forLog .= "\n	ATACKERS_HIT\n";
		if($attakerHaveHit)
		{
			self::_armyHitArmy($armyAttack, $armyDefend);	
		}
				
		//Расчет силы удара обороняющегося
		self::$forLog .= "\n	DEFENDER_HIT\n";
		if($defenderHaveHit)
		{
			self::_armyHitArmy($armyDefend, $armyAttack);	
		}		
				
//SU_DebugTools::LogAll('Обсчет потерь у атакующего', 'Annita.log');		
		//Обсчет потерь у атакующего
		self::$forLog .= "\n	ATACKER_LOSES\n";
		self::_countLoses($armyAttack);
//SU_DebugTools::LogAll('Обсчет потерь у обороняющегося', 'Annita.log');		
		
		//Обсчет потерь у обороняющегося
		self::$forLog .= "\n	DEFENDER_LOSES\n";
		self::_countLoses($armyDefend);
		
		//Распределение потерь по подгруппам обороняющегося
		self::$forLog .= "\n	DEFENDER_SUBGROUP_LOSES\n";
		self::_countDefenderLoses($armyDefend);		
		DE_BattleSystemLog::Insert(self::$forLog);
		self::$forLog = '';
	}
	
	static private function _armyHitArmy(DE_ArmyInterface $armyFrom, DE_ArmyInterface $armyTo)
	{		
		self::$forLog .= "ARMYS ['".$armyFrom->getUserInstance()->getUser()->getName()."'=>'".$armyFrom->getName()."'] -> ['".$armyTo->getUserInstance()->getUser()->getName()."'=>'".$armyTo->getName()."']\n";
		$collection = $armyFrom->getUnitsGroupCollection();		
		$collection->resetNext();
						
		while($unitGroup = $collection->next())
		{
//SU_DebugTools::LogAll($unitGroup->getUnit()->getName(), 'Annita.log');	
			self::$forLog .= "HIT ['".$armyTo->getUserInstance()->getUser()->getName()."'=>'".$armyTo->getName()."'] <- ['".$armyFrom->getName()."'=>'".$unitGroup->getUnit()->getName()."(".$unitGroup->getAmount().")']\n";
//SU_DebugTools::LogAll($unitGroup->getGroupWarPower(), 'AnnitaArm.log');		
			$armyTo->reciveHit($unitGroup->getGroupWarPower());					
		}
	}
		
	static private function _countLoses(DE_ArmyInterface $army)
	{
		self::$forLog .= "FOR ARMY ['".$army->getUserInstance()->getUser()->getName()."'=>'".$army->getName()."']\n";

		if(!$army->haveHits())
		{
			return;
		}

		$collection = $army->getUnitsGroupCollection();
		$recivedWarPowers = $army->fetchHits();
		
		$collection->resetNext();	
		while($unitsGroup = $collection->next())
		{		 
	
			self::_countUnitGroupLoses($unitsGroup, $recivedWarPowers);			
		}
	}

	/**
	 * Рассчет потерь юнит группы после удара
	 *
	 * @param DE_UnitsGroupInterface $unitsGroup
	 * @param unknown_type $recivedWarPowers
	 */
	static private function _countUnitGroupLoses (DE_UnitsGroupInterface $unitsGroup, $recivedWarPowers)
	{		
		assert(is_array($recivedWarPowers));
		
		self::$forLog .= "UNIT GROUP ['".$unitsGroup->getArmy()->getName()."'=>'".$unitsGroup->getUnit()->getName()."(".$unitsGroup->getAmount().")']\n\n";
		
		//Суммарный полученны демедж ставим изначальное значение
		$recivedDamage = 0; 
				
		//Если ударов на группу небыло то ничгеон е делаем
		if(count($recivedWarPowers) == 0)
		{
			return;
		};
		//Пребераем все удары что бы рассчитать суммарны полученный демедж группой
		foreach($recivedWarPowers as $k=>$hitedWarPower)
		{
//SU_DebugTools::LogAll($unitsGroup, 'AnnitaArm.log');										
			$recivedDamageTmp = GL_CountRecivedDamage::Count($hitedWarPower, $unitsGroup, &self::$forLog);
			$recivedDamage += $recivedDamageTmp;
			self::$forLog .= "RECIVE DAMAGE = ".$recivedDamageTmp." FROM ['".$hitedWarPower->getUnitsGroup()->getArmy()->getName()."'=>'".$hitedWarPower->getUnitsGroup()->getUnit()->getName()."(".$hitedWarPower->getUnitsGroup()->getAmount().")']\n\n";
		}
		//Рaсчитали потери
		$loses = (float)GL_CountUnitGroupLoses::Count($recivedDamage ,$unitsGroup); // Сколько нитов потеряли
		self::$forLog .= "recivedDamage = $recivedDamage\n";
		if(DE_GlobalSettings::Get('BATTLE_CALC_USE_DEFENCE_FIELD'))
		{
			self::$forLog .= "unit hitpoints = ".($unitsGroup->getGroupWarPower()->getOneHitpoint() * (1 + $unitsGroup->getGroupWarPower()->getDefenceField()))."\n";
		} else {
			self::$forLog .= "unit hitpoints = ".$unitsGroup->getGroupWarPower()->getOneHitpoint()."\n";
		}
		self::$forLog .= "SUMMARY LOSES = $loses\n";		
		
		//Занесли в группу потери
		$unitsGroup->setLoses($loses);		
		
		//Ресетим военные характиритики группы
		$unitsGroup->resetGroupWarPower();
		self::$forLog .= "*** \n";
	}
	
	static private function _countDefenderLoses(DE_CombDefendArmy $armyDefend)
	{
		$collection = $armyDefend->getCombDefendUnitGroupsCollection();
		$collection->resetNext();
		  
		while($combDefendUnitsGroup = $collection->next())
		{
			self::_countLosesOfCombDefendUnitsGroup($combDefendUnitsGroup);
		}
	}	
	
	static private function _countLosesOfCombDefendUnitsGroup(DE_CombDefendUnitGroup $combDefendUnitsGroup)
	{
		self::$forLog .= "COMB DEFEND UNIT GROUP ['".$combDefendUnitsGroup->getUnit()->getName()."(".$combDefendUnitsGroup->getAmount().")'] \n";
		$defendLoses = $combDefendUnitsGroup->getLoses();
		self::$forLog .= "SUMMARY LOSES = $defendLoses \n";

		if($defendLoses == 0)
		{
			return;
		}
		$unitsGroups = $combDefendUnitsGroup->getUnitsGroupCollection()->getArray();
		
		srand((float)microtime() * 1000000);
		shuffle($unitsGroups);
		
		SU_DebugTools::LogAll($unitsGroups, 'AnnitaArm.log');	
		
		foreach($unitsGroups as  $k=>$unitsGroup)
		{
			$amount = $unitsGroup->getAmount();
			
			if($amount<=$defendLoses)
			{			
				self::$forLog .= "UNIT GROUP ['".$unitsGroup->getArmy()->getName()."' => '".$unitsGroup->getUnit()->getName()."(".$unitsGroup->getAmount().")'] = ".$amount."\n";					
				$unitsGroup->setLoses((float)$amount);
				$defendLoses -= $amount;
			}else{
				self::$forLog .= "UNIT GROUP ['".$unitsGroup->getArmy()->getName()."' => '".$unitsGroup->getUnit()->getName()."(".$unitsGroup->getAmount().")'] = ".$defendLoses."\n";					
				$unitsGroup->setLoses((float)$defendLoses);
				$defendLoses = 0;
			}
		}
		self::$forLog .= "*** \n";
		
	}
}

?>