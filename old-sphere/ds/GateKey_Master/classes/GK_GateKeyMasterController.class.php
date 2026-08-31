<?php

class GK_GateKeyMasterController
{
	static public function GetNewKey($slaveID)
	{
		$key = GK_KeyMasterSearch::getKey($slaveID);
		GK_KeyMasterSearch::markKeyAsSend($key);
		
		return $key;
	}
	
	static public function CheckKey($key, $slaveID)
	{
		$isValid = GK_KeyMasterSearch::checkKey($key, $slaveID);
		if($isValid)
		{
			GK_KeyMasterSearch::markKeyAsRecieved($key);
		} elseif(strlen(constant('GATE_BAD_KEY_DB_TABLE')) != 0) {
			GK_KeyMasterSearch::addToBadKeys($key, $slaveID);
		}
		
		return $isValid;
	}
}

?>