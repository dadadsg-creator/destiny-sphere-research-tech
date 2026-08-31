<?php

class GK_GateKeyController
{
	static public function GetNewKey($keyReceiverName)
	{
		if(empty($keyReceiverName))
			throw new Exception('GK_GateKeyController::GetNewKey empty receiver name');
			
		$slave = GK_SlaveSearch::GetSlaveByName($keyReceiverName);
		if(!$slave)
			throw new Exception('GK_GateKeyController::no such slave');
		try {
			$key = GK_KeySearch::getKey($slave[0]['id_slave']);
		}catch (Exception $e){
			throw new Exception($e->getMessage());
		}
		GK_KeySearch::markKeyAsSend($key);
		
		return $key;
	}
	
	
	static public function CheckKey($key, $slaveID)
	{
		$isValid = GK_KeySearch::checkKey($key, $slaveID);
		if($isValid)
		{
			GK_KeySearch::markKeyAsRecieved($key);
		} elseif(strlen(constant('GATE_BAD_KEY_DB_TABLE')) != 0)  {
			GK_KeySearch::addToBadKeys($key, $slaveID);
		}
		
		return $isValid;
	}
	
}

?>