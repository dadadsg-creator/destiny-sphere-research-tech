<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_PageLoginEnter extends UC_PageBase
{
	private $data;
	
	function __construct($data)
	{
		parent::__construct();
		$this->data = $data;
	}
	
	public function out()
	{
		if(!$this->_getLang("login"))
		{
			echo "<!-- ERROR: lang file not found -->";
		}
			
		$this->tpl->assign('PARAMS', $this->data);
		
		$out = $this->tpl->fetch('user_cabinet_login.tpl');		
		
		return $out;
	}
}

?>