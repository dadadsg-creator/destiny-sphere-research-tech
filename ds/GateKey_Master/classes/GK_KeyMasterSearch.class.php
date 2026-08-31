<?php

class GK_KeyMasterSearch extends GK_SearchBase
{
	static public function getKey($slaveID)
	{
		$common = self::getCommon();
		
		if(!is_int($slaveID) || $slaveID < 1)
		{
			throw new Exception("Parameter 'SLAVE_ID' must be int and greater then 0");
		}
		
		$sql = "
			SELECT
				key
			FROM
				".constant('GATE_KEY_DB_TABLE')."
			WHERE
				send = 0
				AND
				recieved = 0
				AND
				expire_date >= NOW()
				AND
				id_slave = $slaveID
			LIMIT 1
			";
		$res = $common->getResultArray($sql);
		if(!empty($common->error))
		{
			$msg = "Unable to get key: ".$common->error;
			throw new Exception($msg);
		}
		
		if(count($res) == 0)
		{
			throw new Exception("No unused keys left");
		}
		
		return $res[0]['key'];
	}
	
	static public function markKeyAsSend($key)
	{
		$common = self::getCommon();
		
		if(strlen($key) != 32)
		{
			throw new Exception("Parameter 'KEY' must be string(32)");
		}
		
		$sql = "
			UPDATE
				".constant('GATE_KEY_DB_TABLE')."
			SET
				send = 1
			WHERE
				key = '$key'
			";
		$common->executeQuery($sql);
		if(!empty($common->error))
		{
			$msg = "Unable to update key: ".$common->error;
			GK_SearchBase::closeTrans('ROLLBACK');
			throw new Exception($msg);
		}
	}
	
	static public function storeKeysOnMaster($keys, $slaveID, $expire)
	{
		if(!is_array($keys))
		{
			throw new Exception("Parameter 'KEYS' must be array");
		}
		
		if(!is_int($slaveID) || $slaveID < 1)
		{
			throw new Exception("Parameter 'SLAVE_ID' must be int and greater then 0");
		}
		
		if(empty($expire))
		{
			throw new Exception("Parameter 'EXPIRE' must be not empty string");
		}
		
		$common = self::getCommon();
		
		foreach($keys as $k => $key)
		{
			$sql = "
				INSERT INTO ".constant('GATE_KEY_DB_TABLE')."
					(id_slave, key, expire_date, send, recieved)
				VALUES
					($slaveID, '$key', '$expire', 0, 0)
				";
			$common->executeQuery($sql);
			if(!empty($common->error))
			{
				$msg = "Unable to insert key: ".$common->error;
				GK_SearchBase::closeTrans('ROLLBACK');
				throw new Exception($msg);
			}
		}
	}
	
	static public function checkKey($key, $slaveID)
	{
		$common = self::getCommon();
		
		if(strlen($key) != 32)
		{
			throw new Exception("Parameter 'KEY' must be string(32)");
		}
		
		if(!is_int($slaveID) || $slaveID < 1)
		{
			throw new Exception("Parameter 'SLAVE_ID' must be int and greater then 0");
		}
		
		$sql = "
			SELECT
				count(*) as count
			FROM
				".constant('GATE_KEY_DB_TABLE')."
			WHERE
				key = '$key'
				AND
				id_slave = $slaveID
				AND
				recieved = 0
				AND
				expire_date >= NOW()
			";
		$res = $common->getResultArray($sql);
		if(!empty($common->error))
		{
			$msg = "Unable to get key data: ".$common->error;
			throw new Exception($msg);
		}
		
		if($res[0]['count'] == 1)
		{
			return true;
		}
		
		return false;
	}
	
	static public function markKeyAsRecieved($key)
	{
		$common = self::getCommon();
		
		if(strlen($key) != 32)
		{
			throw new Exception("Parameter 'KEY' must be string(32)");
		}
		
		$sql = "
			UPDATE
				".constant('GATE_KEY_DB_TABLE')."
			SET
				recieved = 1
			WHERE
				key = '$key'
			";
		$common->executeQuery($sql);
		if(!empty($common->error))
		{
			$msg = "Unable to update key: ".$common->error;
			GK_SearchBase::closeTrans('ROLLBACK');
			throw new Exception($msg);
		}
	}
	
	static public function addToBadKeys($key, $slaveID)
	{
		$common = self::getCommon();
		
		if(strlen($key) != 32)
		{
			throw new Exception("Parameter 'KEY' must be string(32)");
		}
		
		if(!is_int($slaveID) || $slaveID < 1)
		{
			throw new Exception("Parameter 'SLAVE_ID' must be int and greater then 0");
		}
		
		$sql = "
			INSERT INTO ".constant('GATE_BAD_KEY_DB_TABLE')."
				(id_slave, key, date)
			VALUES
				($slaveID, '$key', NOW())
			";
		
		$common->executeQuery($sql);
		if(!empty($common->error))
		{
			$msg = "Unable to insert bad key: ".$common->error;
			GK_SearchBase::closeTrans('ROLLBACK');
			throw new Exception($msg);
		}
	}
	
	static public function DeleteOldKeys()
	{
		$common = self::getCommon();
		
		$sql = "
			DELETE FROM
				".constant('GATE_KEY_DB_TABLE')."
			WHERE
				(
					(
						send = 1
						OR
						recieved = 1
					)
					AND
					expire_date < NOW() - '7 DAY'::interval
				)
				OR
				expire_date < NOW() - '14 DAY'::interval
			";
		$common->executeQuery($sql);
		if(!empty($common->error))
		{
			$msg = "Unable to delete old keys: ".$common->error;
			GK_SearchBase::closeTrans('ROLLBACK');
			throw new Exception($msg);
		}
	}
}

?>