<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_LogRerourceStealingTax extends DS_Base
{
	static public function InsertRecord(DE_Comb $comb, DE_UserInstance $userInstance, $resource, $resourceData, GB_Date $checkDate)
	{
		assert(!empty($resource));
		assert(is_array($resourceData));
		
		$common = self::getCommon();
		
		$sql = "INSERT INTO ds_resource_stealing_tax_log
				(
					id_comb,
					id_userinstance,
					resource,
					stealing_tax,
					before_amount,
					loosed_amount,
					id_comb_from,
					id_userinstance_from,
					check_date
				) VALUES (
					".$comb->getID().",
					".$userInstance->getID().",
					'".$resource."',
					".$resourceData['stealing_tax'].",
					".$resourceData['before_amount'].",
					".$resourceData['loosed_amount'].",
					".$resourceData['comb_from'].",
					".$resourceData['ui_from'].",
					'".$checkDate->getPgFormat()."'
				)";
		$res = $common->getResultArray ("", $sql);
		assert(empty($common->errormsg));			
	}
}
?>