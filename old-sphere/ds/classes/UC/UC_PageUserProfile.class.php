<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_PageUserProfile extends UC_PageBase 
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
		if(!UC_PageBase::_getLang("user_profile"))
		{
			echo "<!-- ERROR: lang file not found -->";
		}
		
		if(key_exists('MSG', $this->data) && $this->data['MSG'] != 'OK')
		{
			$this->data['MSG'] = $this->lang[$this->data['MSG']];
		}
		
		$this->assignParams();
		
		return $this->tpl->fetch('user_profile_index.tpl');
	}
}

?>