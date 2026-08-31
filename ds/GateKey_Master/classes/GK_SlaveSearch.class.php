<?php

class GK_SlaveSearch extends GK_SearchBase
{
	static public function GetSlaveData($slaveID)
	{
		if(!is_int($slaveID) || $slaveID < 1)
		{
			throw new Exception("Parameter 'SLAVE_ID' must be int and greater then 0");
		}
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				*
			FROM
				ds_gate_slave
			WHERE
				id_slave = $slaveID
			";
		$res = $common->getResultArray($sql);
		if(!empty($common->error))
		{
			throw new Exception("DB ERROR: ".$common->error);
		}
		
		if(count($res) == 0)
		{
			return array();
		}
		
		return $res[0];
	}
	
	static public function UpdateLastKeysAddDate($slaveID)
	{
		if(!is_int($slaveID) || $slaveID < 1)
		{
			throw new Exception("Parameter 'SLAVE_ID' must be int and greater then 0");
		}
		
		$common = self::getCommon();
		
		$sql = "
			UPDATE 
				ds_gate_slave
			SET
				last_keys_add_date = NOW()
			WHERE
				id_slave = $slaveID
			";
		
		$common->executeQuery($sql);
		
		if(!empty($common->error))
		{
			throw new Exception("DB ERROR: ".$common->error);
		}
	}
	
	static public function GetAllSlaves()
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				id_slave
			FROM
				ds_gate_slave
			";
		
		$res = $common->getResultArray($sql);
		
		if(!empty($common->error))
		{
			throw new Exception("DB ERROR: ".$common->error);
		}
		
		return $res;
	}
}

?>