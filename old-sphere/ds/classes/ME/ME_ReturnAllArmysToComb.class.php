<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_ReturnAllArmysToComb
{
	static public function ReturnArmys(DE_Comb $comb, GB_Date $checkDate)
	{

		$combArmysCollection = DC_CombArmys::CreateFromCombID($comb->getID());

		ME_ReturnArmysFromCollection::Exec($combArmysCollection, $checkDate);
	}
}


?>