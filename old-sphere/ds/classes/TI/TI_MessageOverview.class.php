<?php $CVS_Version = '$Revision$ ; $Date$';

class TI_MessageOverview extends TI_BaseMenu
{
	public $type;
	
	public $messages;
	
	public function __construct($tabName)
	{
		parent::__construct(1, $tabName);								
	}
	
	public function setType($type)
	{
		assert(in_array($type, PN_MessageOverview::$allowedTypes));
		$this->type = $type;
	}
	
	public function setMessages(array $messages)
	{
		$this->messages = $messages;
	}
}

?>