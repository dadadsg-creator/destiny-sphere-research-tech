<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * закладка просмотра сообщений
 */
class PN_MessageOverview extends UA_GetBuildMenu 
{
	/**
	 * Enter description here...
	 *
	 * @var TI_MessageOverview
	 */
	private $transportInput;
	
	static public $allowedTypes = array('in', 'out');
	
	function __construct() 
	{
		UA_GetBuildMenu::UA_GetBuildMenu();
	}

	public function getPanel()
	{
		assert(in_array($this->messageType, self::$allowedTypes));
		
		$this->transportInput = new TI_MessageOverview($this->tab);
		$this->transportInput->setType($this->messageType);

		$this->_getMessages();
	
		SU_OutputHandler::Singleton()->addString(TL_MessageOverview::FormMenu($this->transportInput), 'xmldata');
		
		return true;
	}
	
	private function _getMessages()
	{
		$out = false;
		if($this->messageType == 'out')
		{
			$out = true;
		}
		
		$userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
		$messages = DS_Message::GetMessages($userInstance, $out);
		
		$this->transportInput->setMessages($messages);
	}
}

?>