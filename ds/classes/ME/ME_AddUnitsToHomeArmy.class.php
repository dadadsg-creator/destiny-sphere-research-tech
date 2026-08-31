<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_AddUnitsToHomeArmy
{
	static public function AddUnit(DE_Comb $comb, DE_Unit $unit, $amount, GB_Date $addDate, $increaseWeight = false)
	{
		assert(is_float($amount) && $amount>0);
		
		$addPower = $unit->getUnitPower() * $amount;
		DE_ReinforcementMarks::CreateFromID($comb->getID())->addMark($addDate, $addPower);
		
		$homeArmy = $comb->getHomeArmy();

		$unitsGroup = $homeArmy->getUnitsGroupCollection()->GetUnitGroupByUnit($unit);
		if($unitsGroup instanceof DE_UnitsGroup)
		{
			$unitsGroup->deltaAmount($amount);
		}else{
			$unitsGroup = DE_UnitsGroup::CreateInDB($homeArmy, $unit, $amount);
				
			GB_SingletonObjectStorage::RefreshObjectInCollections($unitsGroup);													
			ME_RefreshCombDefendArmy::ExternalRefreshUnitGroups($comb, $unitsGroup);
		}

		// Если надо увеличить значение веса армии игрока
		if ($increaseWeight && DE_WorldSettings::CreateForCurentWorld()->get('CALC_ARMY_WEIGHT')) {
			// Переводим стазис в прод
			$addPower = $addPower/10;
			assert($addPower > 0);
			
			$armyWeight = DE_ArmyWeight::CreateFromUserInstanceID($comb->getUserInstanceID());
			
			$armyWeight->deltaArmyWeightBuilt($addPower);
		}
	}
	
	static public function GetUnit(DE_Comb $comb, DE_Unit $unit, $amount)
	{		
		assert(is_float($amount) && $amount>0);
		
		$homeArmy = $comb->getHomeArmy();
		
		$unitsGroup = $homeArmy->getUnitsGroupCollection()->GetUnitGroupByUnit($unit);
		
		assert($unitsGroup instanceof DE_UnitsGroup);
		
		if($amount > $unitsGroup->getAmount())
		{
			$amount = $unitsGroup->getAmount();
		}
		
		$unitsGroup->deltaAmount(-$amount);
		
		if($unitsGroup->getAmount() == 0)
		{
			$unitsGroup->delete();
		}
	}	
}




?>