<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * команда удаления сообщения
 *
 */
class UA_DeleteMessage extends UA_Action 
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
			/*
			if(!DS_Message::UserHaveMessage($this->uiid, (int)$messageID))
			{
				unset($this->messageList[$k]);
			}
			*/
		}
		
		return true;
	}
	
	function performAction() 
	{
		foreach ($this->messageList as $k => $messageID)
		{
			DS_Message::DeleteUserMessage($this->uiid, (int)$messageID);
		}
		
		return true;
	}
}

?>