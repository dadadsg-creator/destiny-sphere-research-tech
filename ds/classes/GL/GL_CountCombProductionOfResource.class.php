<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CountCombProductionOfResource
{
	static public function Count(DE_Comb $comb, $resourceName)
	{
		assert(!empty($resourceName));
		
		$combProductionBonuses = DE_CombProductionBonus::CreateFromCombID($comb->getID());
		$combAttributes = DE_CombAttributes::CreateFromCombID($comb->getID());
		
		if(!$combAttributes->haveAttribute('TURN_PRODUCTION', $resourceName))
		{
			return 0;
		}
		
		$turnProduction = $combAttributes->getAttribute('TURN_PRODUCTION', $resourceName);
			
		if($turnProduction <= 0)
		{
			return 0;
		}
		
		$bonusAbsolute = $combProductionBonuses->getBonus ($resourceName, 'BONUS_ABSOLUTE');											
		$bonusRelativ = $combProductionBonuses->getBonus ($resourceName, 'BONUS_RELATIVE');
		// бонус, задаваемый модифаерами
	
		$bonusRelativ += self::CountWorldBonus($comb, $resourceName);
		// временный бонус пользователя, покупаемый в кабинете
		// Решили начислять не на чистую продукцию соту, а на продукцию соты с артами (см ниже)
		//$bonusRelativ += DS_Economical::GetUserBonus($comb->getUserInstance(), $resourceName);
		
		$bonusAbsolute += $bonusAbsolute*self::CountWorldBonus($comb, $resourceName);

		$modifaerValue = DE_WorldSettings::CreateForCurentWorld()->get('LOCAL_ARTEFACT_OLD_STYLE');
		if($modifaerValue == 1)
		{
		    $production = round($turnProduction + $bonusAbsolute + ($bonusAbsolute + $turnProduction) * $bonusRelativ);
		}else{
			$production = round($turnProduction + $bonusAbsolute + $turnProduction * $bonusRelativ);
		}
		
		// временный бонус пользователя, покупаемый в кабинете
		$bonusArt = DS_Economical::GetUserBonus($comb->getUserInstance(), $resourceName);
		$production += $production * $bonusArt;

		// бонус захватываемой колонии
		$bonusColony = self::CountColonyBonus($comb, $resourceName);
		$production += $production * $bonusColony;
		
		if($production < 0)
		{
			$production = 0;
		}
		
		return $production;
	}
	
	static public function CountWorldBonus(DE_Comb $comb, $resourceName)
	{
		assert(!empty($resourceName));
		
		if(!in_array($resourceName, GB_ResourcesList::$allResources))
		{
			return;
		}
		
		$modifierName = 'BONUS_';
		
		// Для колоний DestinySphere
		if ($comb->getUserInstanceID() == 0) {
			$modifierName .= 'NON_PREMIUM_';
		} else {
			if($comb->getUserInstance()->getUser()->isProfy())
			{
				$modifierName .= 'PREMIUM_';
			} else {
				$modifierName .= 'NON_PREMIUM_';
			}
		}
		
		$modifierName .= $resourceName.'_';
		
		$modifierName .= $comb->getUserInstance()->getRace()->getName();
		
		return (float)DE_WorldSettings::CreateForCurentWorld()->get($modifierName) / 100;
	}
	
	static public function CountColonyBonus(DE_Comb $comb, $resourceName)
	{
		assert(!empty($resourceName));
		
		if(!in_array($resourceName, GB_ResourcesList::$allResources))
		{
			return;
		}
		
		$modifierName = 'BONUS_';
		
		// Для колоний DestinySphere
		if ($comb->getIsCapture()) {
			
			$modifierName = 'BONUS_COLONY_'.$resourceName;
			
			return (float)DE_WorldSettings::CreateForCurentWorld()->get($modifierName) / 100;
		} else {
			return 0;
		}
	}
}

?>