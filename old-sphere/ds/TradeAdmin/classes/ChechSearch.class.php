<?php

class ChechSearch
{
	static public function GetWorldsList()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "SELECT * FROM ds_worldcollection";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg) && count($res) > 0);
		
		return $res;	
	}
	
	static public function GetWorldDataByShortName($worldShortName)
	{
		assert(!empty($worldShortName));
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "SELECT * FROM ds_worldcollection WHERE world_identifier = '".$worldShortName."'";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg) && count($res) == 1);
		
		return $res[0];	
	}
	
	static public function GetMoneySummOnWorld($serverName, $worldShortName)
	{
		assert(is_string($serverName) && !empty($serverName));
		assert(is_string($worldShortName) && !empty($worldShortName));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID($common->getDBIDByName($serverName));
		
		$sql = "SELECT
    				sum(ma.gc_amount) AS gc_sum,
    				sum(ma.sc_amount) AS sc_sum
				FROM ds_money_account AS ma
				WHERE ma.world_identifier = '".$worldShortName."'";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg) && count($res) == 1);
		
		return $res[0];	
	}
	
	static public function GetAllAccountsInWorld($serverName, $worldShortName)
	{
		assert(is_string($serverName) && !empty($serverName));
		assert(is_string($worldShortName) && !empty($worldShortName));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID($common->getDBIDByName($serverName));
		
		$sql = "SELECT
    				u.id_user,
    				u.username
				FROM 
					ds_money_account AS ma
					INNER JOIN ds_user AS u ON (u.id_user = ma.id_user)
				WHERE ma.world_identifier = '".$worldShortName."'";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function CountBalance($userID, $worldShortName, $serverName)
	{
		assert(is_int($userID) && $userID >= 0);
		assert(is_string($worldShortName) && !empty($worldShortName));
		assert(is_string($serverName) && !empty($serverName));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID($common->getDBIDByName($serverName));
		
		$sql = "SELECT money_accaunt_balance ($userID, '$worldShortName', 0) AS res";    				
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg) && count($res) == 1);
		
		return $res[0];
	}
	
}

?>