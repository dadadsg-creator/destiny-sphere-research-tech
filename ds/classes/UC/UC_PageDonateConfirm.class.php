<?php

class UC_PageDonateConfirm extends UC_PageBase 
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
		
		$this->tpl->assign('CONFIRM', 'DONATE');
				
		return true;
	}
	
	public function out()
	{
		if(!$this->_getLang("index"))
		{
			echo "<!-- ERROR: lang file not found -->";
		}
		
		if(key_exists('MSG', $this->data) && $this->data['MSG'] != 'OK')
		{
			$this->data['MSG'] = $this->lang[$this->data['MSG']];
		}
		
		$this->assignParams();
		
		return $this->tpl->fetch('user_cabinet_confirm.tpl');
	}
}

?>