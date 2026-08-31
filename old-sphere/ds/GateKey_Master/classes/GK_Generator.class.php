<?php


class GK_Generator
{
	static private $slave;
	
	static private $countToGen = 0;
	
	static public $keys = array();
	
	static private $keyLength = 32;
	
	static private $request;
	
	static public $keysRenewInterval = 3600;
	
	static public $fake = false;
	
	static public function Exec()
	{
		$slaveList = GK_SlaveSearch::GetAllSlaves();

		foreach($slaveList as $k => $slave)
		{
			try
			{
				if(self::GenKeysForSlave((int)$slave['id_slave']))
				{
					if(constant('GATE_KEY_DEBUG'))
					{
						echo "<pre>";
						echo "Keys gen for slave '".self::$slave->getName()."' OK.\nFake: ".self::$fake;
						echo "</pre>";
					}
				}
			} catch (Exception $e) {
				GK_Logger::Error($e);
			}
		}
		
		try
		{
			self::_garbageCollector();
		} catch (Exception $e) {
			GK_Logger::Error($e);
		}
	}
	
	static public function GenKeysForSlave($slaveID)
	{	
		self::$slave = new GK_Slave($slaveID);

		self::_setupRequest();
		
		self::$countToGen = self::_getCountKeys();

		self::_genKeys();

		$expireDate = date("Y-m-d H:m:i", time()+2*self::$keysRenewInterval);
		
		if(self::$fake)
		{	
			self::_sendKeysToSlave($expireDate);
			return true;
		}
		
		GK_SearchBase::startTrans();
		
		GK_KeyMasterSearch::storeKeysOnMaster(self::$keys, $slaveID, $expireDate);
		
		GK_SlaveSearch::UpdateLastKeysAddDate($slaveID);
		
		self::_sendKeysToSlave($expireDate);
		
		GK_SearchBase::closeTrans('COMMIT');
		
		return true;
	}
	
	static private function _genKeys()
	{
		for($i=0; $i<self::$countToGen; $i++)
		{
			self::$keys[$i] = self::_genRandomKey();
		}
		
		if(time() - self::$slave->getLastKeysAddDate() < self::$keysRenewInterval)
		{
			self::$keys[array_rand(self::$keys)] = md5(date('Y-d-m'));
			self::$fake = true;
			return;
		}
		
		self::$fake = false;
	}
	
	static private function _genRandomKey()
	{
		$vals = "0123456789abcdef";
		
		$i = 0;
		$key = "";
		while (strlen($key) < self::$keyLength)
		{
			mt_getrandmax();
			$num = rand() % strlen($vals);
			$tmp = substr($vals, $num + 4, 1);
			$key = $key.$tmp;
			$tmp = "";
		}
		
		return $key;
	}
	
	static private function _setupRequest()
	{
		self::$request = new GK_HTTPRequest();

		self::$request->setOption('method', 'POST');
		self::$request->setVar(md5('gate_magic'), md5(self::$slave->getName().date('Y-d-m')));
		
		self::$request->addHeader("Content-type: application/x-www-form-urlencoded");
		if(self::$slave->isHtaccess())
		{
			self::$request->addHeader("Authorization: Basic ".self::$slave->getHtaccess());
		}
	}
	
	static private function _sendKeysToSlave($expireDate)
	{	
		$keysData = implode(',', self::$keys);
		
		self::$request->setVar(md5('gate_action'), md5('send_client_keys'));
		
		self::$request->setVar(md5('gate_keys'), $keysData);
		
		self::$request->setVar(md5('gate_keys_expire_date'), $expireDate);
		
		$res = self::$request->get(self::$slave->getRecieverScript());
		
		if($res != md5(date('Y-d-m')) && !self::$fake)
		{
			GK_SearchBase::closeTrans('ROLLBACK');
			throw new Exception(
					"Failed send keys to slave ".self::$slave->getName().
					"\nSlave returned: ".$res.
					"\nMagic send: ".md5(self::$slave->getName().date('Y-d-m'))
				);
		}

		return true;
	}
	
	static private function _getCountKeys()
	{
		self::$request->setVar(md5('gate_action'), md5('get_count_keys_needed'));
	
		$count = self::$request->get(self::$slave->getRecieverScript());
		
		if((int)$count <= 0)
		{
			throw new Exception(
					"Can't get needed keys count from slave ".self::$slave->getName().
					"\nSlave returned: $count\n".
					"Magic send: ".md5(self::$slave->getName().date('Y-d-m'))."\n"
				);
		}
		
		return $count;
	}
	
	static private function _garbageCollector()
	{
		if(rand(1, 50) == 7)
		{
			GK_KeyMasterSearch::DeleteOldKeys();
		}
	}
}

?>