<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2007/12/07 13:57:14 $';

/**
 * $Author: 40min $
 * $Date: 2007/12/07 13:57:14 $
 * $Revision: 1.6 $
 *
 *
 */

/*

Ahtung!!!
Gate protection is off -- only for testing

*/

require_once($_SERVER['DOCUMENT_ROOT']."/ds/config/gp_config.php");

class BG_Common
{
	private $secret_password = 'secretmasafaka';
	private  $result_data = array();

	protected $error_status = 0;
	protected $incoming_info;
	protected $common;
	protected $id_world;
	protected $db_id;
	protected $id_worldcollection;
	static public $out = '';

	public function __construct($data)
	{
    if(!is_array($data) ||
			sizeof($data) == 0)
			$this->throwException('wrong incoming data');


		$this->incoming_info = $data;


		//Если биллинг работает локально, то пропускаем проверку
		if (@$GLOBALS['billing_script_local_mode'] == false)
		{
			if(!$this->checkSign())
				$this->throwException('wrong sign');

			try {
				$this->checkGateProtection();
			}catch (Exception $e){
				GK_Logger::Error($e, true);
				$this->throwException('wrong sign2');
			}
		}
	}

	protected function throwException($msg)
	{
		throw new Exception($msg);
	}

	protected  function connectToNeededDB($worldname=false)
	{
		if(!$this->loadWorldInfo($worldname))
			return false;

		if(!$this->common->setDbId($this->db_id))
		{
			$this->error_status = 140;
			return false;
		}

		$_SESSION["dbId"] = $this->db_id;

		return true;
	}

	protected function loadWorldInfo($worldname=false)
	{
		if(	isset($this->id_world) &&
			isset($this->db_id ))
			return true;

		if(!$worldname)
		{
			// за ради совместимости
			if(empty($this->incoming_info['worldname']))
			{
				$this->error_status = 110;
				return false;
			}
			$worldname = $this->incoming_info['worldname'];
		}

		$this->common = DS_Common::CreateForCurentDB();

		$sql = "SELECT
						wc.dbname,
						wc.id_world,
						wc.id_worldcollection
				FROM
				    ds_worldcollection wc
				WHERE
				     wc.worldname='".addslashes($worldname)."'";
		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");
		if( !empty($this->common->errormsg) ||
			 empty($res[0]['dbname']) ||
			 empty($res[0]['id_worldcollection']) ||
			 empty($res[0]['id_world']) )
		{
			$this->error_status = 120;
			return false;
		}

		$this->id_world = $res[0]['id_world'];

		$_SESSION['world'] = $this->id_world;
		$this->id_worldcollection = $res[0]['id_worldcollection'];
		$this->db_id = $this->common->getDbIdByName($res[0]['dbname']);

		if($this->db_id === false)
		{
			$this->error_status = 130;
			return false;
		}

		return true;
	}

	private function checkSign()
	{
		if(!array_key_exists('sign',$this->incoming_info))
			return false;

		$sign = $this->createSign($this->incoming_info);

		if(strcmp($sign,$this->incoming_info['sign']) != 0)
			return false;

		return true;
	}

	// здесь функционал мастера
	private function checkGateProtection()
	{
		return ;
		if(!key_exists(md5('gate_key'), $this->incoming_info))
			$this->throwException("lost gate protection key");

		if(!key_exists(md5('gate_magic'), $this->incoming_info))
			$this->throwException("lost gate protection magic");

		$key = $this->incoming_info[md5('gate_key')];
		$incoming_magic = $this->incoming_info[md5('gate_magic')];

		// проверка gate_magic
		$slave = GK_SlaveSearch::GetSlaveByMagic($incoming_magic);
		if(!$slave)
			$this->throwException("can't get slave by magic");

		try {
			if(!GK_GateKeyController::CheckKey($key, $slave[0]['id_slave']))
				throw new Exception('Wrong key protection');
		}catch (Exception $e)
		{
			GK_Logger::Error($e, false);
			throw new Exception('Wrong key protection2');
		}
	}

	protected function createSign($data)
	{
		ksort($data);
		reset($data);

		$sign_string = '';

		while (list($k, $v) = each($data))
		{
			if(strcmp($k,'sign') != 0)
				$sign_string .= $k.'='.$v.';';
		}

		$sign_string .= $this->secret_password;

		return  md5($sign_string);
	}

	protected function outputAnswer()
	{
		$this->addToResult('error_status',$this->error_status);
		$sign = $this->createSign($this->result_data);

		foreach ($this->result_data as $k=>$v)
		{
			self::$out .= $k.'='.$v.';';
		}

		self::$out .= "sign=".$sign;
	}


	protected function addToResult($name,$value)
	{
		$this->result_data[$name]=$value;
	}

	public function GetResult()
	{
		 return $this->result_data;
	}

}
?>