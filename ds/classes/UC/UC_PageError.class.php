<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_PageError extends UC_PageBase 
{
	private $data;
	
	function __construct($data)
	{
		parent::__construct();
		$this->data = $data;
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
		} else {
			$this->data['MSG'] = $this->lang['ERROR'];
		}
		
		SU_ClientKey::setKey(false);
		
		$this->data['CK'] = $_SESSION['client_key'];
		$this->data['S_NAME'] = session_name();
		$this->data['S_ID'] = session_id();
		
		$this->tpl->assign('PARAMS', $this->data);
		
		$out = $this->tpl->fetch('user_cabinet_error.tpl');		
		
		return $out;
	}
}

?>