<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2007/11/06 14:47:50 $';

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
				".constant('GATE_INTERLOCUTOR')."
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
				".constant('GATE_INTERLOCUTOR')."
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
				".constant('GATE_INTERLOCUTOR')."
			";
		
		$res = $common->getResultArray($sql);
		
		if(!empty($common->error))
		{
			throw new Exception("DB ERROR: ".$common->error);
		}
		
		return $res;
	}
	
		
	static public function GetSlaveByMagic($incoming_magic)
	{
		$common = self::getCommon();
		
		$today = date('Y-d-m');
		$sql = "SELECT
						*
					FROM
						".constant('GATE_INTERLOCUTOR')."
					WHERE
						
						md5(name || '".$today."') = '".$incoming_magic."'";
		
		$res = $common->getResultArray($sql);
		
		if(!empty($common->error))
		{
			throw new Exception("DB ERROR: ".$common->error);
		}
		
		return $res;
	}
	
	static public function GetSlaveByName($name)
	{
		$common = self::getCommon();
		
		$sql = "SELECT
						*
					FROM
						".constant('GATE_INTERLOCUTOR')."
					WHERE
						name = '".$name."'";
		
		$res = $common->getResultArray($sql);
		
		if(!empty($common->error))
		{
			throw new Exception("DB ERROR: ".$common->error);
		}
		
		return $res;
	}
}

?>