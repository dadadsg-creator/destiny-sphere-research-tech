<?php

abstract class MU_CheckAccountsManager
{	
	protected $worldData;
	
	abstract public function exec();
	
	public function __construct(array $worldData)
	{
		assert(count($worldData) > 0);
		
		$this->worldData = $worldData;
	}
	
	static public function out($msg)
	{
		echo SU_DebugTools::GetInternalDate()." - ".$msg."\n";
		flush();
	}
}


?>