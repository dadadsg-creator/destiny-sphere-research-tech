<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_StatPVPTransfer extends UC_StatBase
{
	function __construct($data)
	{
		parent::__construct($data);
	}
	
	public function formStat()
	{
		$res = array();
		
		$res['DATE'] = $this->data['add_date'];
		
		$user = DE_User::CreateFromId((int)$this->data['id_user_to']);
		
		$res['ACTION'] = $this->_getText($this->data['action_name']).' <b>'.$user->getName().'</b>';;
		
		if($this->data['gc_amount'] > 0)
		{
			$res['INFO'] = $this->_getText('GOLD_CREDITS').': '.$this->data['gc_amount'];
		}
		
		if($this->data['sc_amount'] > 0)
		{
			$res['INFO'] = $this->_getText('SILVER_CREDITS').': '.$this->data['sc_amount'];
		}	
		
		return $res;
	}
}

?>