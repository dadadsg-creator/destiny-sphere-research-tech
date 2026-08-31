<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_RefreshCombDefendArmy
{
	
	/**
	 *  Ёто когда повл€етьс€ нова€ группа юнитов в домашке то она пытаетьс€ прикрепитьс€
	 * к армии защитной этого комба елси они есть в пам€ти.
	 *
	 * @param DE_UnitsGroup $unitsGroup
	 */
	static public function ExternalRefreshUnitGroups(DE_Comb $comb, DE_UnitsGroup $unitsGroup)
	{					
		$combDefendArmy = GB_SingletonObjectStorage::GetIFExists('DE_CombDefendArmy', $comb->getID());
		
		if(!($combDefendArmy instanceof DE_CombDefendArmy))
		{
			return;
		}
		
		$combDefendUnitsGroupCollection = $combDefendArmy->getCombDefendUnitGroupsCollection();
		$combDefendUnitsGroupCollection->externalImportUnitsGroup($unitsGroup);
	}
	
	/**
	 * ј это комбова€ арми€ спрашивает не по€вилось ли чего нового что можно к себе прилететь
	 *
	 * @param DE_CombDefendArmy $combDefendArmy
	 */
	static public function InternalRefreshUnitGroups(DE_CombDefendArmy $combDefendArmy)
	{
		 $unitsGroupArray = GB_SingletonObjectStorage::GetObjectsOfClass('DE_UnitsGroup', true);
		 
		 foreach($unitsGroupArray as $k=>$unitsGroup)
		 {
		 	$army = $unitsGroup->getArmy();		 	
		 			 	
		 	if(!$army->isDefault() && ($army->getBaseCombID() == $combDefendArmy->getID()))
		 	{
		 		continue;	
		 	}
		 	
		 	$combDefendUnitsGroupCollection = $combDefendArmy->getCombDefendUnitGroupsCollection();
		 	
		 	if(!$combDefendUnitsGroupCollection->haveSimplUnitsGroup($unitsGroup))
		 	{
		 		$combDefendUnitsGroupCollection->externalImportUnitsGroup($unitsGroup);
		 	}
		 }
	}
	
	/**
	 * «десь арми€ прибывшай на соту пытаетьс€ присоденеитьс€ к арми€м  защитников
	 *
	 * @param DE_Army $army
	 */
	static public function ExternalRefreshArmys(DE_Comb $comb, DE_Army $army)
	{
		$combDefendUnitsGroupCollection = DC_CombDefendUnitGroups::CreateFromCombID($comb->getID());
		$combDefendArmysCollection = DC_CombDefendArmys::CreateFromCombID($comb->getID());
		
		if($combDefendArmysCollection->isMember($army))
		{
			return;
		}
		
		$combDefendArmysCollection->externalAddArmy($army);
		
		$unitsGroupCollection = $army->getUnitsGroupCollection();
		$unitsGroupCollection->resetNext();		
		while($unitsGroup = $unitsGroupCollection->next())
		{
			$combDefendUnitsGroupCollection->externalImportUnitsGroup($unitsGroup);
		}
		
	}		
}
?>