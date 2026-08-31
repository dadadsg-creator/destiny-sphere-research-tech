<?php

/**
 * Здесь фактически формируем переменную рефреш
 *
 */

class ME_CombViewFormTotal
{
	static public function Exec(DE_Comb $comb, $cellTypeID, $isNeedCells = true)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);	
		assert(is_bool($isNeedCells));
		
		$cellTypeName = DS_Comb::GetCellTypeNameByID($cellTypeID);
		$turnTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60 * 60 * 1000;
		
		if(DS_Comb::HaveOpenCentralGate($comb->getID()))
		{
			$gateStatus = 1;
		}else{
			$gateStatus = 0;
		}
		
		$out = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<refresh location=\"".$cellTypeName."\" id_comb=\"".$comb->getID()."\" time=\"".$turnTime."\" gate=\"".$gateStatus."\">";
		
		if($isNeedCells || true)
		{
			$out .= "<sota>".ME_CombViewFormCells::Exec($comb, $cellTypeID)."</sota>"; 
		}
		
		$out .= "<build>".ME_CombViewFormBuildings::Exec($comb, $cellTypeID)."</build>";
		
		$out .= "</refresh>";
		return $out;
	}
}

?>