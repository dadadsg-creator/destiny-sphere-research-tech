<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_PageStat extends UC_PageBase
{
	private $data;
	
	function __construct($data)
	{
		parent::__construct();
		$this->data = $data;
	}
	
	public function out()
	{
		if(!$this->_getLang("stat"))
		{
			echo "<!-- ERROR: lang file not found -->";
		}
		
		if(key_exists('MSG', $this->data) && !empty($this->data['MSG']))
		{
			$this->data['MSG'] = $this->lang[$this->data['MSG']];
		}
		
		$this->tpl->assign('PARAMS', $this->data);
		
		$out = $this->tpl->fetch('user_cabinet_stat.tpl');		
		
		return $out;
	}
}

?>