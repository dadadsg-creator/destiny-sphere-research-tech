<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Показ текста письма
 *
 */
class UA_ShowMail extends UA_Action
{
	public $messageID;
	
	function __construct()
	{
		UA_Action::UA_Action();
	}
	
	/**
	 * проверка параметров
	 */
	function checkLocalParams()
	{
		$this->messageID = (int)$this->root->get_attribute("id");
		assert($this->messageID > 0);
		
		return true;
	}
	
	/**
	 * проверяем допустимость действия
	 */
	function checkAction()
	{
		// свое ли письмо пытается прочитать игрок
		assert(DS_Message::UserHaveMessage((int)$this->uiid, $this->messageID));
		
		return true;
	}
	
	/**
	 * выполняем действие
	 */
	function performAction()
	{		
		$message = DS_Message::GetMessageByID($this->messageID);
		assert(key_exists('messagetext', $message));
		assert(key_exists('id_from_userinstance', $message));
		assert(key_exists('id_messagescope', $message));
		$messageText = $message['messagetext'];
 
		// отметить сообщение как прочитанное
		$readDate = DS_Message::MarkMessageAsRead($this->uiid, $this->messageID);
		
		$xml = '<?xml version="1.0" encoding="utf-8"?><message />';
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		$root->set_attribute('id', $this->messageID);
		
		if($readDate instanceof GB_Date)
		{
			$root->set_attribute('dateread', $readDate->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta()));
			
			// при первом прочтении письма оно будет удалено у остальных мастеров
			$scopeName = DS_Message::GetMessageScopeNameByID((int)$message['id_messagescope']);
			if($scopeName == 'GAME_HELPER' || $scopeName == 'GAME_MASTER')
			{
				DS_Message::DeleteMessageFromAllExceptUser($this->messageID, $this->uiid);
			}
		}
		
		$replace = array("[", "]", "'", "\"", "&");
		$messageText = str_replace($replace, '', $messageText);
		$elem = $doc->create_cdata_section(urlencode($messageText));
		
		$root->append_child($elem);
		
		SU_OutputHandler::Singleton()->addString($doc->dump_mem(), 'xmldata');
		
		return true;
	}
}

?>