<?php

class GK_Slave
{
	private $id;
	
	private $name;
	
	private $use_htaccess = false;
	
	private $htaccess = '';
	
	private $use_ip_limit = false;
	
	private $allowed_ip = '';
	
	private $domain;
	
	private $recieverScript;
	
	private $lastKeysAddDate;
	
	public function __construct($slaveID)
	{
		if(!is_int($slaveID) || $slaveID < 1)
		{
			throw new Exception("GK_Slave: invalid ID ".$slaveID);
		}
		
		$data = GK_SlaveSearch::GetSlaveData($slaveID);
		
		if(count($data) == 0)
		{
			throw new Exception("GK_Slave: no data for slave ".$slaveID);
		}
		
		$this->id = $slaveID;
		
		if(
			!key_exists('name', $data) ||
			!key_exists('use_htaccess', $data) ||
			!key_exists('htaccess_login', $data) ||
			!key_exists('htaccess_password', $data) ||
			!key_exists('use_ip_limit', $data) ||
			!key_exists('allowed_ip', $data) ||
			!key_exists('domain', $data) ||
			!key_exists('reciever_script', $data) ||
			!key_exists('last_keys_add_date', $data)
		  )
		{
			throw new Exception("GK_Slave: wrong data for slave ".$slaveID);
		}
		
		$this->name = $data['name'];
		
		if($data['use_htaccess'] == 't')
		{
			$this->use_htaccess = true;
			$this->htaccess = base64_encode($data['htaccess_login'].":".$data['htaccess_password']);
		}
		
		if($data['use_ip_limit'] == 't')
		{
			$this->use_ip_limit = true;
			$this->allowed_ip = $data['allowed_ip'];
		}
		
		$this->domain = $data['domain'];
		
		$this->recieverScript = $data['reciever_script'];
		
		if(empty($data['last_keys_add_date']))
		{
			$this->lastKeysAddDate = 1;
		} else {
			$this->lastKeysAddDate = strtotime($data['last_keys_add_date']);
		}
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getDomain()
	{
		return $this->domain;
	}
	
	public function isHtaccess()
	{
		return $this->use_htaccess;
	}
	
	public function getHtaccess()
	{
		return $this->htaccess;
	}
	
	public function getRecieverScript()
	{
		return 'http://'.$this->domain.'/'.$this->recieverScript;
	}
	
	public function getLastKeysAddDate()
	{
		return $this->lastKeysAddDate;
	}
	
}

?>