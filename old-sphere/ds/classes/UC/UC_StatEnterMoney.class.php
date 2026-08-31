<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_StatEnterMoney extends UC_StatBase
{
	function __construct($data)
	{
		parent::__construct($data);
	}
	
	public function formStat()
	{
		$res = array();
		
		$res['DATE'] = $this->data['add_date'];
		$res['ACTION'] = $this->_getText($this->data['action_name']);
		
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