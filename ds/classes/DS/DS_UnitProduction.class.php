<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_UnitProduction extends DS_Base
{	
	static public function GetUnitInProductionByAttachedBuildingID($attachedBuildingID)
	{
		assert(is_int($attachedBuildingID) && $attachedBuildingID > 0);

		$common = self::getCommon();

		$sql = "SELECT * FROM ds_unitinproduction WHERE id_attachedbuilding = ".$attachedBuildingID;

		$res = $common->getResultArray ("", $sql);
		assert(empty($common->errormsg) && (count($res)==0 || count($res)==1));
		
		if(count($res) > 0)
		{
			return $res[0];
		}else{
			return false;
		}				
	}//END OF METHOD	
	
	static public function DeleteUnitInProductionByAttachedBuildingID($attachedBuildingID)
	{
		assert(is_int($attachedBuildingID) && $attachedBuildingID > 0);

		$common = self::getCommon();

		$sql = "DELETE FROM ds_unitinproduction WHERE id_attachedbuilding = ".$attachedBuildingID;

		$res = $common->getResultArray ("", $sql);
		assert(empty($common->errormsg) && (count($res)==0 || count($res)==1));
				
	}//END OF METHOD	
	
	static public function UpdateUnitInProduction(DE_UnitInProduction $production)
	{		
		$common = self::getCommon();

		$sql = "UPDATE ds_unitinproduction
				SET 
					starttime = '".$production->getStartDate()->getPgFormat()."',
					endtime = '".$production->getEndDate()->getPgFormat()."',
					amount = ".$production->getAmount()."
				WHERE
					id_attachedbuilding = ".$production->getAttachedBuildingID();
		$res = $common->getResultArray ("", $sql);
		assert(empty($common->errormsg) && (count($res)==0 || count($res)==1));
				
	}//END OF METHOD	
}


?>