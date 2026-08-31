<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *  ласс гейта дл€ проверки уникальности login'а пользовател€
 *
 */
class BG_CheckLoginUniq extends BG_Common 
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

		$common = DS_Common::CreateForCurentDB(0);
		
		if(VD_UserName::CheckIfExists($this->incoming_info['login'], $common) === false)
		{
			$this->addToResult('login_exists', 0);
		} else {
			$this->addToResult('login_exists', 1);
		}
		
		$this->outputAnswer(); 
		return;
	}
}

?>