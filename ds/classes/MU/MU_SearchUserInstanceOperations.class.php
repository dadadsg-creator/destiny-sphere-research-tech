<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_SearchUserInstanceOperations
{
	static public function DeltaUserInstanceAccount(DE_UserInstance $userInstance, $delta, $creditName)
	{
		assert(is_float($delta));
		assert(key_exists($creditName, GB_MoneySumm::$allowCreditName));
		
		$common = DS_Common::CreateForCurentDB();
		
		$fildName = GB_MoneySumm::$allowCreditName[$creditName];
		assert(!empty($fildName));
		
		if($delta > 0)
		{
			$deltaString = " + ".$delta;
		}else{
			$deltaString = $delta;
		}
		
		$sql = "UPDATE ds_money_account_userinstance
				SET $fildName = $fildName $deltaString
				WHERE id_userinstance = ".$userInstance->getID();
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));		
	}
	
	static public function InsertRezerv(DE_UserInstance $userInstance, $summ, $creditName, $reasonName, $additionalID, GB_Date $expireDate)
	{
		assert(is_float($summ));
		assert(!empty($reasonName));
		assert(is_int($additionalID) && $additionalID > 0);
		assert(key_exists($creditName, GB_MoneySumm::$allowCreditName));
		
		$common = DS_Common::CreateForCurentDB();
		
		if($creditName == 'GC')
		{
			$summGC = $summ;
			$summSC = 0;
		}else{
			$summGC = 0;
			$summSC = $summ;
		}
		
		$sql = "SELECT * FROM ds_money_reserv_reason WHERE reason_name = '".$reasonName."'";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg) && count($res) == 1);

		assert(key_exists('id_reason', $res[0]));
		$reasonID = (int)$res[0]['id_reason'];
		assert($reasonID > 0);
		
		$sql = "INSERT INTO ds_money_account_userinstance_reserve
				(
					id_userinstance, 
					gc_amount, 
					sc_amount, 
					id_additional, 
					id_reason, 
					expire_date
				)VALUES(
					".$userInstance->getID().",
					".$summGC.",
					".$summSC.",
					".$additionalID.",					
					".$reasonID.",
					'".$expireDate->getPgFormat()."'
				)";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
	}
	
	static public function GetReservData(DE_UserInstance $userInstance, $reasonName, $additionalID)
	{
		$common = DS_Common::CreateForCurentDB();
		
		$sql = "SELECT *
				FROM 
					ds_money_account_userinstance_reserve AS uir
					INNER JOIN ds_money_reserv_reason AS rr ON (rr.id_reason = uir.id_reason)
				WHERE 
					uir.id_userinstance = ".$userInstance->getID()."
					AND
					rr.reason_name = '".$reasonName."'
					AND
					uir.id_additional = ".$additionalID."";	
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg) && count($res) == 1);
		
		return $res[0];
	}	
	
	static public function DeleteReservData(DE_UserInstance $userInstance, $reasonName, $additionalID)
	{
		$common = DS_Common::CreateForCurentDB();
		
		$sql = "
				DELETE FROM ds_money_account_userinstance_reserve
				WHERE 
					 id_reserv  = (
					 	SELECT uir.id_reserv
						FROM 
							ds_money_account_userinstance_reserve AS uir
							INNER JOIN ds_money_reserv_reason AS rr ON (rr.id_reason = uir.id_reason)
						WHERE 
							uir.id_userinstance = ".$userInstance->getID()."
							AND
							rr.reason_name = '".$reasonName."'
							AND
							uir.id_additional = ".$additionalID."					 	
					 )";	
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));	
	}
	
	static public function InsertAction($actionTypeName, $additionalDataID)
	{
		assert(!empty($actionTypeName));
		assert(is_int($additionalDataID) >= 0);
		
		$common = DS_Common::CreateForCurentDB();
		
		$actionTypeData = self::GetActionTypeDataByName($actionTypeName);
		
		assert(key_exists('id_money_action', $actionTypeData));
		$typeID = (int)$actionTypeData['id_money_action'];
		assert($typeID > 0);
		
		$sql = "select nextval('ds_money_account_userinstance_action_id_userinstance_action_seq') as id";
		$res = $common->getResultArray ( "", $sql, "", "", "" );	
		assert(empty($common->errormsg) && count($res) == 1);		
		
		$id = (int)$res[0]['id'];
		assert($id > 0);	
		
		$sql = "INSERT INTO ds_money_account_userinstance_actions
				(id_userinstance_action, id_action_type, addition_data_id)
				VALUES
				($id, $typeID, $additionalDataID)";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $id;
	}
	
	static public function InsertBalance($actionID, $summDelta, $creditName, DE_UserInstance $userInstance)
	{		
		assert(is_int($actionID) && $actionID > 0);
		assert(is_float($summDelta));
		assert(key_exists($creditName, GB_MoneySumm::$allowCreditName));
		
		if($creditName == 'GC')
		{
			$summGC = $summDelta;
			$summSC = 0;
		}else{
			$summGC = 0;
			$summSC = $summDelta;
		}
		
		$common = DS_Common::CreateForCurentDB();
						
		$sql = "INSERT INTO ds_money_account_userinstance_balance
				(
					gc_delta,
					sc_delta,
					id_userinstance,
					id_userinstance_action
				)VALUES(
					".$summGC.",
					".$summSC.",
					".$userInstance->getID().",
					".$actionID."
				)";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
	}
	
	static public function GetActionTypeDataByName($actionTypeName)
	{
		assert(!empty($actionTypeName));		
		
		$common = DS_Common::CreateForCurentDB();
		
		$sql = "SELECT * 
				FROM 
					ds_money_action_types AS at
					INNER JOIN ds_money_action_scopes AS sp ON (sp.id_scope = at.id_scope)
				WHERE at.action_name = '".$actionTypeName."'";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg) && count($res) == 1);
						
		return $res[0];
	}
	
	static public function GetCurrentAmmountOnAccount(DE_UserInstance $userInstance)
	{			
		$common = DS_Common::CreateForCurentDB();
		
		$sql = "SELECT gc_amount, sc_amount 
				FROM ds_money_account_userinstance 
				WHERE id_userinstance = '".$userInstance->getID()."'";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg) && (count($res) == 1 || count($res) == 0));
					
		if(count($res) == 0)
		{
			$out = array(
			'GC' => (float)0,
			'SC' => (float)0,
		);
		}else{
			$out = array(
				'GC' => (float)$res[0]['gc_amount'],
				'SC' => (float)$res[0]['sc_amount'],
			);			
		}
		
		
		return $out;
	}
	
}

?>