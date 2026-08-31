<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_LogUnitsDeath extends DS_Base
{
	static public function GetDeathReasonByID($reasonName)
	{
		assert(is_string($reasonName) && !empty($reasonName));
		
		$common = self::getCommon();
		
		$sql = "SELECT * FROM ds_units_death_reasons WHERE reason_name = '".$reasonName."'";
		$res = $common->getResultArray (self::FormMark(__METHOD__, $reasonName), $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");
		assert(empty($common->errormsg) && count($res) == 1);	
		
		return (int)$res[0]['id_death_reason'];
	}
	
	static public function InsertRecord($deathReasonID, DE_Comb $comb, DE_UserInstance $userInstance, DE_Unit $unit, GB_Date $checkDate, 
											$beforeAmount, $deathAmount)
	{
		assert(is_numeric($beforeAmount) && $beforeAmount > 0);
		assert(is_numeric($deathAmount) && $deathAmount > 0);
		assert(is_int($deathReasonID) && $deathReasonID > 0);
				
		$common = self::getCommon();
		
		$sql = "INSERT INTO ds_units_death_log
				(
					id_comb,
					id_userinstance,
					id_unit,
					before_amount,
					death_amount,
					check_date,
					id_death_reason
				) VALUES (
					".$comb->getID().",
					".$userInstance->getID().",
					".$unit->getID().",
					".$beforeAmount.",
					".$deathAmount.",
					'".$checkDate->getPgFormat()."',
					".$deathReasonID."					
				)";
		
		$res = $common->getResultArray ("", $sql);
		assert(empty($common->errormsg));			
	}
	
	
}

?>