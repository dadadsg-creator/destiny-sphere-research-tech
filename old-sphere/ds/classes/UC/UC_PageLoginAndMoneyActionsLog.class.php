<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_PageLoginAndMoneyActionsLog extends UC_PageBase 
{
	private $data;
	
	function __construct(array $data)
	{
		parent::__construct();
		$this->data = $data;
	}
	
	private function assignParams()
	{		
		$this->tpl->assign('PARAMS', $this->data);
				
		return true;
	}
	
	public function out()
	{
		if(!$this->_getLang("login_and_money_aclions_log"))
		{
			echo "<!-- ERROR: lang file not found -->";
		}
		
		if(key_exists('MSG', $this->data) && $this->data['MSG'] != 'OK')
		{
			$this->data['MSG'] = $this->lang[$this->data['MSG']];
		}
		
		$this->assignParams();
		
		return $this->tpl->fetch('login_and_money_actions_log.tpl');
	}
}

?>