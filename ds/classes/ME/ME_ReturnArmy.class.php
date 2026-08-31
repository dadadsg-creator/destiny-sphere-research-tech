<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_ReturnArmy
{
	/**
	* @return 
	* @param 
	* @ Отзыв всех армии с этой соты, куда-либо летящих(в атаку или защиту), либо стоящих на защите
	*/
	static public function ME_ReturnAllArmysToComb(DE_Comb $comb, GB_Date $checkDate)
	{
		$combArmysCollection = DC_CombArmys::CreateFromCombID($comb->getID());
//SU_DebugTools::LogAll($combArmysCollection, 'Annita.log');	

		ME_ReturnArmysFromCollection::Exec($combArmysCollection, $checkDate);
	} //END OF METHOD
		
	/**
	* @return 
	* @param 
	* @ Отзыв всех армий с данной соты, летящих или стоящих на защите
	*/
	static public function ME_KickAllArmysFromCombOnDefend(DE_Comb $comb, GB_Date $checkDate)
	{
		$combArmysCollection = DC_CombDefendArmys::CreateFromCombID($comb->getID());

		ME_ReturnArmysFromCollection::Exec($combArmysCollection, $checkDate);
	} //END OF METHOD		
	
	/**
	* @return 
	* @param 
	* @ Отзыв всех армий летящих на за эту соту(в атаку или защиту), или стоящих на защите этой соты
	*/
	static public function ME_ReturnAllArmysFromComb(DE_Comb $comb, GB_Date $checkDate)
	{
		$combArmysCollection = DC_ArmyAttackOrDefendComb::CreateFromCombID($comb->getID());
		
		ME_ReturnArmysFromCollection::Exec($combArmysCollection, $checkDate);
	} //END OF METHOD
}


?>