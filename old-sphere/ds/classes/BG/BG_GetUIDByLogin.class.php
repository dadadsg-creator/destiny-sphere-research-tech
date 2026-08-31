<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *  ласс гейта дл€ получени€ UID и мыл€ по логину игрока
 *
 */
class BG_GetUIDByLogin extends BG_Common 
{
	public function __construct($data)
	{
		parent::__construct($data);		
	}
	
	/**
	 * ѕолучаем uid пользовател€ по логину
	 *
	 */
	public function proceed()
	{		

		if($this->error_status != 0)
		{
			return;
		}
		
		if(!key_exists('login',$this->incoming_info)) 
		{
			$this->error_status = 1;
			$this->outputAnswer(); 
			return;
		}

		if(!VD_UserName::Validate($this->incoming_info['login']))
		{
			$this->error_status = 2;
			$this->outputAnswer(); 
			return;
		}
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$uid = VD_UserName::CheckIfExists($this->incoming_info['login'], $common);
		if($uid === false)
		{
			$this->addToResult('uid', 0);
			$this->addToResult('email', '');
		} else {
			$this->addToResult('uid', $uid);
			$user = DE_User::CreateFromID((int)$uid);
			$this->addToResult('email', $user->getEmail());
		}
		
		$this->outputAnswer(); 
		return;
	}
}

?>