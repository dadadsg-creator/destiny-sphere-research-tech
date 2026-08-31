<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Отметить список сообщений как прочтенные
 *
 */
class UA_MarkMailAsRead extends UA_Action 
{
	private $messageList;
	
	function __construct()
	{
		UA_Action::UA_Action();
	}
	
	function checkLocalParams() 
	{
		// id сообщений
		$this->messageList = $this->root->get_attribute("id_message");
		assert(!empty($this->messageList));
		
		$this->messageList = explode(',', $this->messageList);
		
		return true;
	}
	
	function checkAction() 
	{
		foreach ($this->messageList as $k => $messageID)
		{
			settype($this->messageList[$k], 'integer');
			assert($this->messageList[$k] > 0);
		}
		
		return true;
	}
	
	function performAction() 
	{
		$readDate = GB_TypeFactory::CreateBase('GB_Date', time());
		
		foreach ($this->messageList as $k => $messageID)
		{
			DS_Message::MarkMessageAsRead($this->uiid, (int)$messageID);
		}
		
		$this->_sendHelper($readDate);
		
		return true;
	}
	
	private function _sendHelper(GB_Date $readDate)
	{
		$helper = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<readmess date=\"".$readDate->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta())."\"/>";
		SU_OutputHandler::Singleton()->addString($helper, 'helper');
	}
}

?>