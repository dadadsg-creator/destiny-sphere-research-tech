<?php

abstract class MU_CheckAccountsManager
{	
	protected $worldData;
	
	static public $worldName;
	
	abstract public function exec();
	
	public function __construct(array $worldData)
	{
		assert(count($worldData) > 0);
		
		$this->worldData = $worldData;
	}
	
	static public function out($msg, $logIt = false)
	{
		assert(is_bool($logIt));
		
		$str = SU_DebugTools::GetInternalDate()." - ".$msg."\n";
		
		if($logIt)
		{
			assert(!empty(self::$worldName));
			SU_DebugTools::WriteToLog($str, 'CheckMoneyAccounts_'.self::$worldName.'.log');
		}
		
		echo $str;
		flush();
	}
	
	static public function processError($msg, $userName, $checkName)
	{
		assert(!empty($msg));
		assert(!empty($userName));
		assert(!empty(self::$worldName));
		assert(!empty($checkName));
		
		$str = "$userName (".self::$worldName.") IN CHECK: $checkName ERROR:".$msg." <<<<<<<<<<<<<<<<<<<<<<<<<<<<<< \n";
		SU_DebugTools::LogAll($str, 'MoneyAccountErrors.log');
		
		echo $str;
		flush();
	}	
}


?>