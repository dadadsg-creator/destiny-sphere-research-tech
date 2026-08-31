<?php

class CheckManagerBalance extends CheckManager
{	
	
	public function __construct(array $worldData)
	{
		parent::__construct($worldData);
	}
	
	public function Exec()
	{		

		$allUsers = ChechSearch::GetAllAccountsInWorld($this->worldData['dbname'], $this->worldData['world_identifier']);		

		foreach($allUsers as $k=>$userData)
		{
			$str = "	Test user: ".$userData['username'];
			self::out($str);
			
			$res = ChechSearch::CountBalance((int)$userData['id_user'], $this->worldData['world_identifier'], $this->worldData['dbname']);
			
			$balanceRes = $res['res'];
					
			if($balanceRes == 'OK')
			{
				$str = "	OK";
				self::out($str);
			}else{
				$str = "	$balanceRes <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!";
				self::out($str);
			}
		}
	}
}

?>