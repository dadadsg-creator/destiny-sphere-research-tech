<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_KickAllArmysFromCombOnDefend
{
	static public function ReturnArmys(DE_Comb $comb, GB_Date $checkDate)
	{

		$combArmysCollection = DC_CombDefendArmys::CreateFromCombID($comb->getID());

		ME_ReturnArmysFromCollection::Exec($combArmysCollection, $checkDate);
	}
}


?>