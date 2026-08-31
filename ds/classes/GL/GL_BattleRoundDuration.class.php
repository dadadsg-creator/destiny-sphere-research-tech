<?php $CVS_Version = '$Revision$ ; $Date$';


class GL_BattleRoundDuration extends GameBase
{	
	static public function Count (DE_Army $attackArmy, DE_Comb $comb)
	{

		$worldSettings = DE_WorldSettings::CreateForCurentWorld();
		
		$baseRoundTime = $worldSettings->get('BATTLE_ROUND_TIME_MULTIPLIER') * $worldSettings->get('TURN_TIME') * 60;
									
		
		$defendArmy = DE_CombDefendArmy::CreateFromCombID($comb->getID());				
		
		$roundTime = 0;

		if($attackArmy->getArmyPower() > $defendArmy->getArmyPower())
		{					
			$roundTime = $defendArmy->getArmyPower() / $attackArmy->getArmyPower() * 
							$baseRoundTime * ($attackArmy->getArmyStars() / 2);
		}else{						
			$roundTime = $attackArmy->getArmyPower() / $defendArmy->getArmyPower() * 
							$baseRoundTime * ($defendArmy->getArmyStars() / 2);
		}

		assert ($roundTime >= 0);
		
		if($roundTime<1)
		{
			$roundTime = 1;
		}
		
		$limitMax = $worldSettings->get('MAX_BATTLE_ROUND_DURATION_IN_TURNS') * $worldSettings->get('TURN_TIME') * 60;
		if($roundTime > $limitMax)
		{
			$roundTime = $limitMax;
		}
		
		$limitMin = $worldSettings->get('MIN_BATTLE_ROUND_DURATION_IN_TURNS') * $worldSettings->get('TURN_TIME') * 60;
		if($roundTime < $limitMin)
		{
			$roundTime = $limitMin;
		}
		
		return GB_TypeFactory::CreateConstant('GB_TimePeriod', (int)round($roundTime));						
	}
}

?>