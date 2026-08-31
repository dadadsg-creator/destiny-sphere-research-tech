<?php

class MU_CheckAccountsManagerBalance extends MU_CheckAccountsManager
{	
	
	public function __construct(array $worldData)
	{
		parent::__construct($worldData);
	}
	
	public function Exec()
	{		

		$allUsers = MU_CheckAccountsSearch::GetAllAccountsInWorld($this->worldData['dbname'], $this->worldData['world_identifier']);		

		foreach($allUsers as $k=>$userData)
		{
			$str = "	Test user: ".$userData['username'];
			self::out($str);
			
			$res = MU_CheckAccountsSearch::CountBalance((int)$userData['id_user'], $this->worldData['world_identifier'], $this->worldData['dbname']);
			
			$balanceRes = $res['res'];
					
			if($balanceRes == 'OK')
			{
				$str = "	OK";
				self::out($str);
			}else{				
				self::processError($balanceRes, $userData['username'], 'InnerWorldBalance');
			}
		}
	}
}

?>