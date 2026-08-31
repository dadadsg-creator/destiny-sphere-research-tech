<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2008/01/17 07:47:51 $';

/**
 * $Author: 40min $
 * $Date: 2008/01/17 07:47:51 $
 * $Revision: 1.1 $
 * 
 * Класс гейта для предоставления премиума и доступа к анализатору
 *
 */
class BG_PremiumAndAnaliz extends BG_Common 
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
		
		$common = DS_Common::CreateForCurentDB(0);		
		$common->startTrans();

		if(!PU_ExecChangePremiumDays::Exec($user, 
										$this->incoming_info['days'],
										'BILLING', 
										$this->incoming_info['paymentcode']))
		{
			$common->closeTrans('rollback');
			$this->error_status = 2;
			$this->outputAnswer(); 
			return;
		}
		
		if(!PU_ExecChangeAnalizerPremium::Exec($user, 
										$this->incoming_info['days'],
										'BILLING', 
										$this->incoming_info['paymentcode']))
		{
			$common->closeTrans('rollback');
			$this->error_status = 3;
			$this->outputAnswer(); 
			return;
		}
		
		$common->closeTrans('commit');
		
		$this->error_status = 0;
		$this->outputAnswer(); 
		return;
	}
}
?>