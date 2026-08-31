<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс гейта для совершения операций с премиум-доступом
 *
 */
class BG_Premium2 extends BG_Common 
{
	public function __construct($data)
	{
		parent::__construct($data);		
	}
	
	public function proceed()
	{		
		if($this->error_status != 0)
		{
			return;
		}
		
		if(!array_key_exists('uid',$this->incoming_info) ||
			!array_key_exists('days',$this->incoming_info) ||
			!array_key_exists('paymentcode',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer(); 
			return;
		}
		
		settype($this->incoming_info['uid'], 'integer');
		$user = DE_User::CreateFromID($this->incoming_info['uid']);

		if(!PU_ExecChangePremiumDays::Exec($user, 
										$this->incoming_info['days'],
										'BILLING', 
										$this->incoming_info['paymentcode']))
		{
			$this->error_status = 2;
			$this->outputAnswer(); 
			return;
		}
		
		$this->error_status = 0;
		$this->outputAnswer(); 
		return;
	}
}
?>