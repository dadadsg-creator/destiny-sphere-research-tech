<?php

/**
 * $Author: 40min $
 * $Date: 2008/03/18 09:43:09 $
 * $Revision: 1.5 $
 * 
 * Класс гейта для получения информации о группе пользователей
 *
 */

class BG_UserDataPack extends BG_Common
{
	public function __construct($data)
	{
		parent::__construct($data);
	}
	
	public function proceed(){
		
		$userIDs = split(',', $this->incoming_info['user_ids']);
		
		if(!$userIDs){
			$this->error_status = 1;
			$this->outputAnswer();
			return ;
		}
		
		$resultArray = array();
		foreach ($userIDs as $id){
			
			$id = intval($id);
			if(empty($id))
				continue;
						
			$user = DE_User::CreateFromID($id);
			if( !is_a($user, 'DE_User') )
				continue;
				
			$email = $user->getEmail();
			$password = $user->getPassword();
			$login = $user->getName();
				
			if(!empty($email) && !empty($password)){
				
				$resultArray[] = $id.":".$login.":".$email.":".$password;
			}
		}
		
		$userData = join(',', $resultArray);
		$userData = serialize($userData);
		$userData = urlencode($userData);
		
		$this->addToResult('userdata', $userData);
		$this->outputAnswer(); 
		return;
	}
}

?>