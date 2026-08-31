<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * класс игровой логики для проверки того окончена битва илине т и по какойц причине.
 */

class GL_TestEndBattle
{	
			
	/** Кто победил */
	static public $testResult;
	
	static public $isRetreatStarted = false;
		
	static public function isBattleEnded (DE_Army $attackArmy,  DE_CombDefendArmy $defendArmy, DE_BattleRound $round)
	{
		if($round->getBattle()->isBattleRetreated())
		{
			self::$isRetreatStarted = true;
		}else{
			self::$isRetreatStarted = false;
		}			
		
		if(self::_testRetreat($attackArmy, $defendArmy, $round))
		{
			return true;
		}
		
		if(self::_testEmptyArmy($attackArmy, $defendArmy, $round))
		{
			return true;
		}
		
		if(self::_testLoses($attackArmy, $defendArmy, $round))
		{
			return true;
		}
		
		if(self::_toLongBattle($attackArmy, $defendArmy, $round))
		{
			return true;
		}
		
		if(!self::$isRetreatStarted && self::_testEmptyDemage($attackArmy, $defendArmy, $round))
		{
			return true;
		}
		
		return false;
	}
	
	public function getTestResult()
	{
		return $this->testResult;
	}
	
	static private function _testRetreat(DE_Army $attackArmy,  DE_CombDefendArmy $defendArmy, DE_BattleRound $round)
	{
		$retreatBattleRoundNumber = $round->getBattle()->getRetreatRoundNumber();
		
		if($round->getBattle()->isRetreatWithEmptyDamage())
		{
			$retreatRounds = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_TRANSPORT_RETREAT_ADDITIONAL_ROUNDS');
		} else {
			$retreatRounds = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_RETREAT_ADDITIONAL_ROUNDS');
		}

		if($retreatBattleRoundNumber > 0 && $round->getRoundNumber() >= $retreatBattleRoundNumber + $retreatRounds)
		{
			switch($round->getBattle()->getRetreatSideName()->value)
			{
				case 'A':
					self::$testResult = new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'D'), 'ATTACKER_RETREAT');		
					break;					
				case 'D':
					self::$testResult = new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'A'), 'DEFENDER_RETREAT');
					break;
				default:
					assert(false);				
			}
						
			return true;
		}
		
		return false;
	}
	
	static private function _testEmptyArmy(DE_Army $attackArmy,  DE_CombDefendArmy $defendArmy, DE_BattleRound $round)
	{
		
		if($attackArmy->getArmyPower() == 0)
		{
			self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'D'), 'EMPTY_ARMY');
			return true;
		}
		
		if($defendArmy->getArmyPower() == 0)
		{
			self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'A'), 'EMPTY_ARMY');
			return true;
		}

		if(self::$isRetreatStarted && ($attackArmy->getArmyPower() == 0 || $defendArmy->getArmyPower() == 0))
		{
			switch($round->getBattle()->getRetreatSideName()->value)
			{
				case 'A':
					self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'D'), 'ATTACKER_RETREAT');		
					break;					
				case 'D':
					self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'A'), 'DEFENDER_RETREAT');
					break;
				default:
					assert(false);				
			}
			
			return true;
		}
		
	
		
		return false;
		
	}
			
	static private function _testEmptyDemage(DE_Army $attackArmy,  DE_CombDefendArmy $defendArmy, DE_BattleRound $round)
	{		
		if($attackArmy->haveNullDamage())
		{
			self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'D'), 'EMPTY_DEMAGE');
			return true;
		}
		
		if($defendArmy->haveNullDamage())
		{
			self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'A'), 'EMPTY_DEMAGE');
			return true;
		}
				
		return false;	
	}
	
	/**
	 * Проверка на соотношение весов, все было бы просто если бы не отступление транспортов
	 *
	 * @param DE_Army $attackArmy
	 * @param DE_CombDefendArmy $defendArmy
	 * @param DE_BattleRound $round
	 * @return bool
	 */
	static private function _testLoses(DE_Army $attackArmy,  DE_CombDefendArmy $defendArmy, DE_BattleRound $round)
	{
		if(self::$isRetreatStarted && !$round->getBattle()->isRetreatWithEmptyDamage())
		{
			return false;
		}
				
		$worldSettings = DE_WorldSettings::CreateForCurentWorld();
		
		$attackerRetreat = false;
		$defenderRetreat = false;
		
		// есть ли отступающие в этом бою
		if(self::$isRetreatStarted && $round->getBattle()->isRetreatWithEmptyDamage() && !$worldSettings->get('BATTLE_NULL_DAMAGE_UNITS_CAN_WIN'))
		{
			if($round->getBattle()->getRetreatSideName()->value == 'A')
			{
				$attackerRetreat = true;
			} else {
				$defenderRetreat = true;
			}
		}

		// в этом месте логика слегка запутана
		// если защитник больше по весу атакующего, то
		if($attackArmy->getArmyPower() <=  $defendArmy->getArmyPower() * $worldSettings->get('WIN_CONDITION'))
		{
			// если он оступает, он сразу проигрывает
			if($defenderRetreat)
			{
				self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'A'), 'DEFENDER_RETREAT');
			// иначе сразу выигрывает
			} else {
				self::$testResult = new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'D'), 'BIG_LOSES');
			}
			
			return true;
		}
		
		// тоже самое и с атакером
		if($attackArmy->getArmyPower() * $worldSettings->get('WIN_CONDITION') >= $defendArmy->getArmyPower() )
		{
			if($attackerRetreat)
			{
				self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'D'), 'ATTACKER_RETREAT');
			} else {
				self::$testResult = new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'A'), 'BIG_LOSES');
			}
			
			return true;
		}
		
		return false;
	}	
	
	static private function _toLongBattle(DE_Army $attackArmy,  DE_CombDefendArmy $defendArmy, DE_BattleRound $round)
	{		
		// Если атакуемая сота захватываемая 
		if ($defendArmy->getBaseComb()->getIsCapture()) {
			$MAX_BATTLE_ROUNDS_COUNT = DE_WorldSettings::CreateForCurentWorld()->get('MAX_BATTLE_ROUNDS_COUNT_COLONIES');
		} else {
			$MAX_BATTLE_ROUNDS_COUNT = DE_WorldSettings::CreateForCurentWorld()->get('MAX_BATTLE_ROUNDS_COUNT');
		}
		
		if(self::$isRetreatStarted && ($round->getRoundNumber() >= $MAX_BATTLE_ROUNDS_COUNT))
		{
			switch($round->getBattle()->getRetreatSideName()->value)
			{
				case 'A':
					self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'D'), 'ATTACKER_RETREAT');		
					break;					
				case 'D':
					self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'A'), 'DEFENDER_RETREAT');
					break;
				default:
					assert(false);				
			}
			
			return true;
		}
		
		if($round->getRoundNumber() >= $MAX_BATTLE_ROUNDS_COUNT)
		{
			if($attackArmy->getArmyPower() > $defendArmy->getArmyPower())
			{
				self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'A'), 'BIG_LOSES');
				return true;
			}else{
				self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'D'), 'BIG_LOSES');
				return true;
			}
		}
		
		return false;
	}
}


?>