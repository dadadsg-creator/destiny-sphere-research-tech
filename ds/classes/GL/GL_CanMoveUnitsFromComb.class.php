<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CanMoveUnitsFromComb
{
	static public function IsCan(DE_Comb $comb)
	{
		$rs = DS_Battle::NotEndedBattlesOnComb($comb);
		
		if(count($rs) > 0)
		{
			return false;
		}else{
			return true;
		}
	}
}

?>