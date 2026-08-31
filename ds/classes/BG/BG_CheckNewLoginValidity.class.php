<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс гейта для проверки корректности нового ника (login) пользователя
 *
 */

class BG_CheckNewLoginValidity extends BG_Common 

{
	public function __construct($data)
	{
		parent::__construct($data);
		$this->common = DS_Common::CreateForCurentDB();
	}

	/**
	 * Проверяется корректность нового ника
	 *
	 */
	public function proceed()
	{
		if(!array_key_exists('login',$this->incoming_info)) 
		{
			$this->error_status = 1;
			
			$this->outputAnswer();
		}

		if (VD_UserName::Validate($this->incoming_info['login'], true, $this->common)) {
			$this->addToResult('login_correct',1);
		} else {
			$this->addToResult('login_correct',0);
		}
		
		$this->outputAnswer();	
	}
}

?>