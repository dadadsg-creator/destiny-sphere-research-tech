<?php

class GK_SearchBase
{
	static private $transStarted = false;
	
	static protected $common = null;
	
	static public function getCommon()
	{
		if(is_null(self::$common))
		{
			switch(constant('GATE_KEY_DB_TYPE'))
			{
				case 'PostgreSQL':
					self::$common = new GK_DBPostgreSQL();
					break;
			}
		}
		
		return self::$common;
	}
	
	static public function startTrans()
	{
		self::getCommon();
		
		if(self::$transStarted)
		{
			return false;
		}
		
		self::$common->executeQuery('BEGIN');
		if(!empty($common->error))
		{
			throw new Exception("DB ERROR: ".$common->getLastError());
		}
		
		self::$transStarted = true;
		
		return true;
	}
	
	static public function closeTrans($mode)
	{
		$mode = strtoupper($mode);
		
		if(is_null(self::$common) || !self::$transStarted)
		{
			return false;
		}
		
		if($mode != 'COMMIT' && $mode != 'ROLLBACK')
		{
			throw new Exception("Invalid transaction close mode");
		}
		
		self::$common->executeQuery($mode);
		if(!empty($common->error))
		{
			throw new Exception("DB ERROR: ".$common->getLastError());
		}
		
		self::$transStarted = false;
		
		return true;
	}
}

?>