<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_PrivatePeace extends UA_Action 
{
	/**
	 * Enter description here...
	 *
	 * @var float
	 */	
	private $nick; //ник игрока, с которым заключается миро	
	
	private $status;//статус мира - принять/отклонить
	
  private $id_userinstance_with_whom; //uiid игрока, с которым заключаем/расторгаем мир	
	function __construct() 
	{
//		SU_DebugTools::LogAll('UA_PrivatePeace construct..'.var_export($this->root ,true),'peace.log');		
		parent::UA_Action();	
	}
	
	function checkLocalParams()
	{
		$this->nick   = $this->root->get_attribute('nick');
		$this->status = $this->root->get_attribute('reason');
		
		return true;
	}

	function checkAction()
	{
		return true;
	}

	function performAction()
	{
		//SU_DebugTools::LogAll('SESSION='.var_export($_SESSION, true), 'peace.log');
    $combid = 0;
    $notfound = false;
    //Мы в контакте и нам вместо ника шлют координаты
		if ((constant("VERSION_NAME") == "VKONTAKTE") && ($this->status == 'PRIVATEPEACE_REQUEST'))
		{
		  list($ring, $complex, $combn) = explode('.', $this->nick);

		  $ring  = intval($ring);
		  $complex = intval($complex);
		  $combn   = intval($combn);

		  //$whith_whom_uiid = 
		  
		  if ($ring && $complex && $combn)
		  {
		    $combid = (int)DS_Comb::GetIDByCordinates($_SESSION['world'], $ring, $complex, $combn);

		    if($combid)
	  	  {
          $this->id_userinstance_with_whom =  DE_Comb::CreateFromID($combid)->getUserInstanceID();
		    }
		    else 
		       $notfound = true; 
		  }
		  else 
		  {
		    $notfound = true; 
		  }
		}
		
				
		if (($_SESSION['s_login']==$this->nick) || ($_SESSION['uiid']==$this->id_userinstance_with_whom))
		{
		  $errmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PEACE_WITH_MYSELF', null, null, null, false);
			
		  $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$errmsg.']]></txt></swloader>';
			
		  SU_OutputHandler::Singleton()->addReadyString($alertText);			
		  return true;
		}
    
		$with_whom_nick = addslashes($this->nick);
    
		//мы в контакте, но по заданным координатам никого не нашли
		if ((constant("VERSION_NAME") == "VKONTAKTE") && $notfound)  
		{
		  $errmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PEACE_NO_USER_WITH_COORDS', array(), null, null, false);
			$errmsg  = str_replace(array('{','}'),array('<','>'), $errmsg);
			
		  $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$errmsg.']]></txt></swloader>';
			
		  SU_OutputHandler::Singleton()->addReadyString($alertText);			
		  return true;
		}		
		
		//мы не в контакте, и по заданному имени никого не нашли
		if ((constant("VERSION_NAME") != "VKONTAKTE") && (!DS_Peace::checkUserExistsInWorld($with_whom_nick, $_SESSION['world'])))
		{
		  $errmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('TRADE_NO_PERSON_FOR_LOT', array($this->nick), null, null, false);
			$errmsg  = str_replace(array('{','}'),array('<','>'), $errmsg);
			
		  $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$errmsg.']]></txt></swloader>';
			
		  SU_OutputHandler::Singleton()->addReadyString($alertText);			
		  return true;
		}
		
		if (constant("VERSION_NAME") != "VKONTAKTE")
		{
		    $user = DE_User::CreateFromName($this->nick);
		    $this->id_userinstance_with_whom = DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($user->getID(), $_SESSION['world']);
		}
		else 
		{
			if ($this->status != 'PRIVATEPEACE_REQUEST')
			{  
		    $this->id_userinstance_with_whom = (int)$this->root->get_attribute('definition');
			}  
		}
		
		$result = 0;
//SU_DebugTools::LogAll('nick='.$this->nick.',status='.$this->status,'nick=',"peace2.log");
		$another_nick = DE_UserInstance::CreateFromID($this->id_userinstance_with_whom)->getUser()->getName();
		//if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
		  // SU_DebugTools::LogAll("status=".$this->status,'peace.log');

		if (($this->status == 'PEACE_CONFIRMATION') || ($this->status == 'PEACE_REJECTING') || 
		($this->status == 'LIST_REQUEST_REJECT_PEACE') || ($this->status=='LIST_REQUEST_CANCEL_REQUEST') )
		{
      $result = DS_Peace::SetPeace($this->id_userinstance_with_whom, $this->status); 
 	    //if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
        // SU_DebugTools::LogAll("result=".$result,'peace.log');

      if ($result > 0) //действие было совершено
      {
         if ($this->status == 'PEACE_CONFIRMATION')
         {
         	  $this->sendMessage(0, 'PRIVATE_PEACE_CONFIRM_SUBJECT', 'PRIVATE_PEACE_CONFIRM', 
            array($_SESSION['s_login']), 'PRIVATE', $this->id_userinstance_with_whom);
            
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "PEACE_ACCEPT_SYSMSG", array($another_nick) );
            //Остановить армии, которые в полёте
            DS_Peace::StopArmy($_SESSION['uiid'], $this->id_userinstance_with_whom);
            DS_Peace::StopArmy($this->id_userinstance_with_whom, $_SESSION['uiid']);
         }
         else if ($this->status == 'PEACE_REJECTING')
         {
         	 $this->sendMessage(0, 'PRIVATE_PEACE_REJECT_SUBJECT', 'PRIVATE_PEACE_REJECT', 
           array($_SESSION['s_login']), 'PRIVATE', $this->id_userinstance_with_whom);       
           DE_SystemMessages::AddMessageFromAction(__CLASS__, "PEACE_DECLINE_INVITE_SYSMSG", array($another_nick) );
         }
         else if ($this->status == 'LIST_REQUEST_REJECT_PEACE')  
         {
           $this->sendMessage(0, 'PRIVATE_PEACE_CANCEL_SUBJECT', 'PRIVATE_PEACE_CANCEL', 
           array($_SESSION['s_login'], DS_Peace::$endDateStr), 'PRIVATE', $this->id_userinstance_with_whom);       
           DE_SystemMessages::AddMessageFromAction(__CLASS__, "PEACE_BREAK_SYSMSG", array($another_nick, DS_Peace::$endDateStr) );
          }
         else if ($this->status == 'LIST_REQUEST_CANCEL_REQUEST')
         {
         	 $this->sendMessage(0, 'PRIVATE_PEACE_CANCEL_REQUEST_SUBJECT', 'PRIVATE_PEACE_CANCEL_REQUEST', 
           array($_SESSION['s_login']), 'PRIVATE', $this->id_userinstance_with_whom);       
           DE_SystemMessages::AddMessageFromAction(__CLASS__, "PEACE_CANCEL_INVITE_SYSMSG", array($another_nick) );
         }
      }
		}
		else if ($this->status == 'PRIVATEPEACE_REQUEST')
    {
    	 $peace = DS_Peace::getPeaceByUiid($this->id_userinstance_with_whom);
       if ($peace && $peace['status']) //мир уже есть с этим игроком       	
       {
		     $errmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PEACE_ALREADY_EXISTS', array($this->nick), null, null, false);
    		 $errmsg  = str_replace(array('{','}'),array('<','>'), $errmsg);

		     $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$errmsg.']]></txt></swloader>';
   			 SU_OutputHandler::Singleton()->addReadyString($alertText);			
       }
       elseif ($peace && !$peace['status'] && !$peace['psec']) //ожидается заключение мира
       {
		     $errmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PEACE_WAITING_CONFIRM', array($this->nick), null, null, false);
    		 $errmsg  = str_replace(array('{','}'),array('<','>'), $errmsg);

		     $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$errmsg.']]></txt></swloader>';
   			 SU_OutputHandler::Singleton()->addReadyString($alertText);			
       }
       elseif ($peace && !$peace['status'] && $peace['psec']) //мир расторгнут
       {
		     $errmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PEACE_WAITING_REJECT', array($this->nick, $peace['peace_end_date']), null, null, false);
    		 $errmsg  = str_replace(array('{','}'),array('<','>'), $errmsg);

		     $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$errmsg.']]></txt></swloader>';
   			 SU_OutputHandler::Singleton()->addReadyString($alertText);			
       }
       else 
       {
         DS_Peace::addPeace($this->id_userinstance_with_whom);
         $this->sendMessage(0, 'PRIVATE_PEACE_REQUEST_SUBJECT', 'PRIVATE_PEACE_REQUEST', 
         array($_SESSION['s_login']), 'PRIVATE', $this->id_userinstance_with_whom);            
         DE_SystemMessages::AddMessageFromAction(__CLASS__, "PEACE_INVITE_SYSMSG", array($with_whom_nick) );
       }
    }


		$menu = new PN_PeacePrivate();
    //$menu = new PN_AllianceManagement();
		$menu->setBID(14);
		$menu->setTAB('PN_PeacePrivate');

		assert($menu->getPanel(true));
		    
		return true;
	}
	

	function sendMessage($fromUserInstanceID, $subjectname, $messagename, array $messageparams, $scope, $toUserInstanceID)
	{
  	$subject = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage($subjectname, array(), null, null, false);
		$message = GL_EncodeGameMessage::Encode(DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage($messagename, $messageparams, null, null, false), $this->langId);
		$messageScopeID = DS_Message::GetMessageScopeIDByName($scope);
		assert($messageScopeID > 0);
		
		$msg = new UA_SendMessage();
		$msg->to = DE_UserInstance::CreateFromID($toUserInstanceID)->getUser()->getName();
		
		
		$msg->uiid = (int)$fromUserInstanceID;
		$msg->messageScopeID = (int)$messageScopeID;
		$msg->messageScopeName = $scope;
		$msg->subject = $subject;
		$msg->messagetext = $message;
		//$msg->original_messagetext = $msg->convert_to_utf8($message);
				
		$res = $msg->checkAction();
		$res = $msg->performAction(false);
	}	
}

?>