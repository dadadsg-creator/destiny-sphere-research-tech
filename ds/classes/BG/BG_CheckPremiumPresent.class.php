<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *
 */

class BG_CheckPremiumPresent extends BG_Common 
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
		
		if(!key_exists('uid', $this->incoming_info)) 
		{
			$this->error_status = 1;
			$this->outputAnswer(); 
			return;
		}
		
		$uid = (int)$this->incoming_info['uid'];
		if($uid <= 0)
		{
			$this->error_status = 2;
			$this->outputAnswer(); 
			return;
		}
		
		$presentGiven = DS_Premium::CheckPremiumPresent($uid);
		
		$this->addToResult('premium_given', $presentGiven);
		
		$this->outputAnswer(); 
		return;
	}
}

?>