<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ћенюшка написани€ сообщени€
 *
 */
class PN_CreateMessage extends UA_GetBuildMenu 
{
	private $rootXML;
	
	private $docXML;
	
	public $messageType;
	
	public $messageID;
	
	function __construct() 
	{
		UA_GetBuildMenu::UA_GetBuildMenu();
	}

	function getPanel()
	{
		$this->_initXMLDoc();

		$this->_getMessageScopes();

		$this->_getMessageData();
		
		$this->_getTabs();	

		SU_OutputHandler::Singleton()->addString($this->docXML->dump_mem(), 'xmldata');
		
		return true;
	}
	
	/**
	 * —оздание объекта DOM_XML
	 *
	 */
	private function _initXMLDoc()
	{
		$xml = '<?xml version="1.0" encoding="utf-8"?><createmessage />';
		$this->docXML = domxml_open_mem($xml);
		$this->rootXML = $this->docXML->document_element();
	}
	
	/**
	 * —писок скопов сообщений доступных данному игроку
	 *
	 */
	private function _getMessageScopes()
	{
		$scopes = DS_Message::GetUserAllowedMessageScopes($this->uiid);
		foreach($scopes as $k => $scope)
		{
			$ch = $this->rootXML->new_child("messagescope");
			$ch->set_attribute("id", $scope['id_messagescope']);
			$ch->set_attribute("name", $scope['messagescopename']);
			$ch->set_attribute("ln", $scope['localizedmessagescope']);
		}
	}
	
	/**
	 * ≈сли отвечает или цитирует письмо, то передаем данные по этому письму
	 *
	 */
	private function _getMessageData()
	{
		if($this->messageType != 'reply' && $this->messageType != 'quote')
		{
			if(!empty($this->name))
			{
				$ch = $this->rootXML->new_child("name");
				$elem = $this->docXML->create_text_node(urlencode($this->name));
				$ch->append_child($elem);
			}
			
			return;
		}
		
		assert($this->messageID > 0);
		assert(DS_Message::UserHaveMessage($this->uiid, $this->messageID));
		
		$message = DS_Message::GetMessageByID($this->messageID);
			
		// отправл€ем им€ игрока, от которого пришло письмо
		$ch = $this->rootXML->new_child("name");
		$elem = $this->docXML->create_text_node(urlencode($message['sender']));
		$ch->append_child($elem);
		
		// отправл€ем тему
		$ch = $this->rootXML->new_child("subj"); //'Re:	';	
		$elem = $this->docXML->create_text_node('05206503A020'.urlencode($message['subject']));
		$ch->append_child($elem);
		
		// если цитируем, то отправл€ем текст
		if($this->messageType == 'quote')
		{
			$ch = $this->rootXML->new_child("quotatext");
			// замена при нормальной работе почты не понадотс€, но письмо может и левое прийти откуда-то, 
			// например от бота или напр€мую в базе вставлено, так что чистим на вс€кий случай
			$replace = array("[", "]", "'", "\"", "&");
			$elem = $this->docXML->create_text_node(urlencode(str_replace($replace, '', $message['messagetext'])));
			$ch->append_child($elem);
		}
	}
	
	/**
	 * «акладки дл€ менюшки нового сообщени€
	 *
	 */
	private function _getTabs()
	{
		if($this->bid != "") 
		{
			$this->getBuildingTabs($this->rootXML, $this->bid, $this->langId, $this->tab);
		} else {
			$this->getChildTabs($this->rootXML, $this->langId, $this->tab);
		}
	}
}

?>