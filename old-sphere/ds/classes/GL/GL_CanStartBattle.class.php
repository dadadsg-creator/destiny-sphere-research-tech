<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CanStartBattle
{
	static public $testResult;
	
	static public function CanStart(DE_Army $attackArmy, DE_CombDefendArmy $defendArmy)
	{
		$worldSettings = DE_WorldSettings::CreateForCurentWorld();
		
		$calcUnitsWithNullDamage = (bool)$worldSettings->get('BATTLE_CALC_UNITS_WITH_NULL_DAMAGE');
		
		$attackerArmyPower = $attackArmy->getArmyPower($calcUnitsWithNullDamage);
		assert($attackerArmyPower >= 0);
		
		$defenderArmyPower = $defendArmy->getArmyPower($calcUnitsWithNullDamage);
		assert($defenderArmyPower >= 0);
		
		if($defenderArmyPower <= $worldSettings->get('MIN_ARMY_STRENGTH') * $attackerArmyPower)
		{
			self::$testResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'A'), 'DEFENDER_TOO_WEEK_FOR_BATTLE');
			return false;
		}
						
		return true;
	}
}

?>