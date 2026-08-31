<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_PageChangeCoords extends UC_PageBase 
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
		if(!$this->_getLang("user_cabinet_payment_services"))
		{
			echo "<!-- ERROR: lang file not found -->";
		}
		
		if(key_exists('MSG', $this->data) && $this->data['MSG'] != 'OK')
		{
			$this->data['MSG'] = $this->lang[$this->data['MSG']];
		}
		
		$this->assignParams();
		
		return $this->tpl->fetch('change_coords.tpl');
	}
}

?>