<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_PagePaymantServices extends UC_PageBase
{
	private $data = array();
	
	function __construct($data)
	{
		parent::__construct();
		$this->data = $data;
	}
	
	public function out()
	{
		if(!$this->_getLang("payment_services"))
		{
			echo "<!-- ERROR: lang file not found -->";
		}
		
		if(key_exists('MSG', $this->data) && $this->data['MSG'] != 'OK')
		{
			$this->data['MSG'] = $this->lang[$this->data['MSG']];
		} 
		
		//SU_DebugTools::LogAll('data='.var_export($this->data, true),'uc.log');
		$this->tpl->assign('PARAMS', $this->data);
		
		$out = $this->tpl->fetch('payment_services.tpl');		
		
		return $out;
	}
}

?>