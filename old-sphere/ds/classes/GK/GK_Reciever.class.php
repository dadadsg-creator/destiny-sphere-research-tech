<?php

class GK_Reciever
{
	static private $action;
	
	static private $keys = '';
	
	static private $expireDate;
	
	static public function Exec($data)
	{
		if(!array($data))
		{
			throw new Exception("'DATA' must be array");
		}
		
		self::_checkParams($data);
		
		switch(self::$action)
		{
			case md5('get_count_keys_needed'):
				die(constant('GATE_KEY_COUNT'));
				// GK_LogicKeysCount::getCount();
				break;
		
			case md5('send_client_keys'):
				if(empty(self::$keys))
				{
					throw new Exception("Wrong keys data");
				}
				if(empty(self::$expireDate))
				{
					throw new Exception("Wrong expire date");
				}
				if(self::RecieveKeys(self::$keys, trim($data[md5('gate_magic')])) == 'OK')
				{
					die(md5(date('Y-d-m')));
				}
				die(md5(rand(0, 500)));
				break;	
				
			default:
				throw new Exception("Unknown action");
				break;
		}
	}
	
	static private function _checkParams($data)
	{
		if(!key_exists(md5('gate_action'), $data))
		{
			throw new Exception("No action in request");
		}
		
		self::$action = $data[md5('gate_action')];
		
		if(!key_exists(md5('gate_magic'), $data))
		{
			throw new Exception("No magiv in request");
		}
				
		if(key_exists(md5('gate_keys'), $data))
		{
			self::$keys = $data[md5('gate_keys')];
		}
		
		if(key_exists(md5('gate_keys_expire_date'), $data))
		{
			self::$expireDate = $data[md5('gate_keys_expire_date')];
		}
	}
	
	static public function RecieveKeys($keys, $incoming_magic)
	{
		$keysArr = explode(',', $keys);
		if(count($keysArr) < 3)
		{
			throw new Exception("Wrong incoming keys count");
		}
		
		GK_SearchBase::startTrans();
		
		
		$trueParam = md5(date('H-Y-d-Y-m-d-d-DD').' vot takoi vot herovii den');
		
		foreach($keysArr as $k => $key)
		{
			if($key == $trueParam)
			{
				$slave = GK_SlaveSearch::GetSlaveByMagic($incoming_magic);
				if(!$slave)
					throw new Exception("GK_Reciever::RecieveKeys error in getting slave by magic");
					
				GK_KeySearch::storeKeys($keysArr, $slave[0]['id_slave'], self::$expireDate);
				
				GK_SearchBase::closeTrans('COMMIT');
		
				return 'OK';
			}
		}
		
		return 'FAKE';			
	}
	
	static private function _garbageCollector()
	{
		if(rand(1, 50) == 7)
		{
			GK_KeySearch::DeleteOldKeys();
		}
	}
}

?>