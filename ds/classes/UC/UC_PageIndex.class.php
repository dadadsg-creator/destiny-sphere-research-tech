<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_PageIndex extends UC_PageBase
{
	private $data;
	
	function __construct($data)
	{
		parent::__construct();
		$this->data = $data;
	}
	
	private function assignParams()
	{
		return true;
	}
	
	public function out()
	{
		if(!$this->_getLang("index"))
		{
			echo "<!-- ERROR: lang file not found -->";
		}
		
		if(key_exists('MSG', $this->data) && !empty($this->data['MSG']))
		{
			$this->data['MSG'] = $this->lang[$this->data['MSG']];
		}
		
		$show_enter_cred_form = false;
		if (isset($_SESSION["popmog"]))
		  $show_enter_cred_form = true;

//		echo "popmog=".$_SESSION["popmog"].",show_enter_cred_form={$show_enter_cred_form}<br>";
		  
		  
		$this->tpl->assign('PARAMS', $this->data);
		$this->tpl->assign('uid', $_SESSION['uid']);
		$this->tpl->assign('gameid', constant('BILLING_PARTNER_ID'));
				
    $this->tpl->assign('show_enter_cred_form', $show_enter_cred_form);    
		$out = $this->tpl->fetch('user_cabinet_index.tpl');		
				
		return $out;
	}
	
}

?>