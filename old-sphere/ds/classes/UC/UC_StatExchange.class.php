<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_StatExchange extends UC_StatBase
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
		$res['INFO'] = $this->_getText('GOLD_CREDITS').': '.$this->data['gc_amount'].'; '.$this->_getText('EXCHANGE_RATE').': '.$this->data['exchenge_course'].':1';
			
		return $res;
	}
}
?>