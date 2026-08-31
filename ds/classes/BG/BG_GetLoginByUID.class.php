<?php $CVS_Version = '$Revision$ ; $Date$';

class BG_GetLoginByUID extends BG_Common 
{
	public function __construct($data)
	{
		parent::__construct($data);		
	}
	
	/**
	 * Получаем логин пользователя по ИД
	 *
	 */
	public function proceed()
	{
		if($this->error_status != 0)
		{
			return;
		}
		
		if(!key_exists('uid',$this->incoming_info)) 
		{
			$this->error_status = 1;
			$this->outputAnswer(); 
			return;
		}
		
		if((int)$this->incoming_info['uid'] <= 0)
		{
			$this->error_status = 2;
			$this->outputAnswer(); 
			return;
		}
		
		$user = DE_User::CreateFromID((int)$this->incoming_info['uid']);
		
		$this->addToResult('login', $user->getName());
		
		$this->outputAnswer(); 
		return;
	}
}

?>