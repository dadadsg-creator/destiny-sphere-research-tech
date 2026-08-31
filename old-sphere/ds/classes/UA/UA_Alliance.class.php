<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_Alliance extends UA_Action 
{
	static private $allowedScopes = array('INTERNAL_ALLIANCE', 'PRIVATE');
	
	function UA_Alliance()
	{
		UA_Action::UA_Action();
	}
		
	private function _sendAlMessage($fromUserInstanceID, $subjectname, $messagename, array $messageparams, $scope, $toUserInstanceID = false, $allianceID = 0)
	{
		assert(is_int($fromUserInstanceID));
		assert(is_int($allianceID));
		assert($toUserInstanceID === false || is_int($toUserInstanceID));
		assert(!empty($subjectname));
		assert(!empty($messagename));
		assert(in_array($scope, self::$allowedScopes));
		
		$subject = GL_EncodeGameMessage::Encode(DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage($subjectname, array(), null, null, false));
		$message = GL_EncodeGameMessage::Encode(DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage($messagename, $messageparams, null, null, false), $this->langId);
		$messageScopeID = DS_Message::GetMessageScopeIDByName($scope);
		assert($messageScopeID > 0);
		
		$msg = new UA_SendMessage();
			
		if($toUserInstanceID)
		{
			$msg->to = DE_UserInstance::CreateFromID($toUserInstanceID)->getUser()->getName();
		}
		
		if($allianceID)
		{
			$msg->allianceID = $allianceID;
		}
		
		$msg->uiid = (int)$fromUserInstanceID;
		$msg->messageScopeID = (int)$messageScopeID;
		$msg->messageScopeName = $scope;
		$msg->subject = $subject;
		$msg->messagetext = $message;
		$msg->original_messagetext = $msg->convert_to_utf8($message);
				
		$res = $msg->checkAction();
		$res = $msg->performAction(false);
	}
	
	function sendAllianceMessage($allianceID, $id_language, $fromUserInstanceID, $subjectname, $messagename, $messageparams) 
	{		
		$this->_sendAlMessage($fromUserInstanceID, $subjectname, $messagename, $messageparams, 'INTERNAL_ALLIANCE', false, (int)$allianceID);
		
		return true;
	}
	
	function sendPrivateMessage($toUserInstanceID, $id_language, $fromUserInstanceID, $subjectname, $messagename, $messageparams) 
	{
		$this->_sendAlMessage($fromUserInstanceID, $subjectname, $messagename, $messageparams, 'PRIVATE', (int)$toUserInstanceID);
		
		return true;
	}

}
?>