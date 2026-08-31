<?php $CVS_Version = '$Revision$ ; $Date$';

// команда вступить в аль€нс
class UA_JoinAlliance extends UA_Alliance 
{

	public $id_alliance;

	public $joinpassword;

	public $errorname;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Alliance
	 */
	private $alliance;

	function UA_JoinAlliance() 
	{
		parent::__construct();
		$this->errorname = array();
	}

	function checkLocalParams() 
	{
		// id аль€нса
		$this->id_alliance = $this->root->get_attribute("id_alliance");
		if(empty($this->id_alliance))
		{
			$this->errormsg .= " SYSTEM ERROR UA_JoinAlliance::checkLocalParams1";
			return false;
		}

		// пароль дл€ вступлени€ в аль€нс
		$ch = $this->root->get_elements_by_tagname("joinpassword");
		$node = $ch[0];
		$this->joinpassword = utf8_decode($node->get_content());

		if(@preg_match('/[\'\"\`\~\!\@\#\$\%\^\*\(\)\<\>\[\]\{\}]/', $this->joinpassword))
		{
			$this->errorname[] = 'JOINPASSWORD_HAS_WRONG_SYMBOLS';
			return true;
		}

		return true;
	}

	function checkAction()
	{
		// провер€ем не состоит ли юзер в другом аль€нсе
		$userAlliance = DE_Alliance::CreateFromUserInstanceID((int)$this->uiid);
		assert($userAlliance->isEmpty());
		
		if(count($this->errorname) > 0)
		{
			return true;
		}
		
		// провер€ем пароль
		$this->alliance = DE_Alliance::CreateFromID((int)$this->id_alliance);
		if($this->alliance->getPassword() != $this->joinpassword)
		{
			$this->errorname[] = 'INCORRECT_JOINPASSWORD';
		}
		
		// проверка максимального кол-ва участников аль€нса
		if(($this->alliance->getMembersCount() + 1) > DE_WorldSettings::CreateForCurentWorld()->get('MAX_ALLIANCE_MEMBERS'))
		{
			$this->errorname[] = 'ALLIANCE_HAS_MAX_MEMBERS';
		}

		// провер€ем, есть ли у игрока посольство
		/*if()
		{
			$this->errorname[] = 'NO_ALLIANCE_CONNECTOR';
		}*/

		return true;
	}

	// выполн€ем действие
	function performAction () {

		global $params;
//		global $common;

		if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
		{
			$this->_vocationModeAlert();			
			return true;
		}

		if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
		{
			$this->_shildModeAlert();			
			return true;
		}
		
		$userInstance = DE_UserInstance::CreateFromID((int)$params["uiid"]);
		
		//»сли игрок состоит  вглобальном аль€нсе провер€ем, не пытаетс€ ли он вступить в другой аль€нс
		$glibalAllianceId = DS_Alliance::IsUserInGlobslAlliance($userInstance->getUser()->getName());
		if (!empty($glibalAllianceId)) {

			if($glibalAllianceId != $this->alliance->getGlobalAllianceId())
			{
				$globalAlliance = DE_GlobalAlliance::CreateFromID((int)$glibalAllianceId);
				$messparams[0] = $globalAlliance->getShortName();
				DE_SystemMessages::AddMessageFromAction(__CLASS__, 'ALLIANCE_CAN_JOIN_ONLY_GLOBAL', $messparams);
				return true;
			}

		} else {
			//ѕроверка попытки вступлен в глобальный аль€нс из игры. ≈сли та кто посылаем вежливо.
			if($this->alliance->isGlobal())
			{
				$messparams[0] = $this->alliance->getName();
				DE_SystemMessages::AddMessageFromAction(__CLASS__, 'ALLIANCE_TRY_JOIN_GLOBAL', $messparams);

				return true;
			}
		}

		//if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')	
		  //SU_DebugTools::LogAll('messparams='.$this->alliance->getName(),'oauth.log');
		if(count($this->errorname) == 0)
		{
			ME_AddUserToAlliance::Add($userInstance, $this->alliance);

			if ((constant('DEFAULT_LANGUAGE_SHORT') == 'L') && $_SESSION['oauth_token'])
			  $odata = MakeOAuthNotification($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'] , array('type' => 'alliance', 'name'=>$this->alliance->getName()));    
			
			// посылаем письмо всем членам аль€нса
			$messageparams[0] = $userInstance->getUser()->getName();

			if(!$this->sendAllianceMessage($this->id_alliance, $params["lang"], 0, 'ALLIANCE_NEW_MEMBER_SUBJECT', 'ALLIANCE_NEW_MEMBER_TEXT', $messageparams)) 
			{
				return false;
			}

			$messparams[0] = "{b} ".$this->alliance->getName()."{/b}";
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "JOIN_ALLIANCE_MESS", $messparams );
			
			// снимаем защиту нового игрока
			if($userInstance->haveNewbDefend())
			{
				$userInstance->clearNewbDefend();
				$messparams	= array();
				DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_NEWB_DEFEND", $messparams, 0, 'YELLOW');
			}

		} else { 
			// если ошибка отправл€ем системное собщение и выходим
			$messparams[0] = "";
			foreach($this->errorname as $k => $errorName)
			{
				DE_SystemMessages::AddMessageFromAction(__CLASS__, $errorName, $messparams);
			}
		}

		// минимальный рефреш
		//if( $_SESSION['profi_game'] )
		//{
			$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}
		
		if(!$this->refresh(2, "", "", "", $noCellData)) 
		{
			return false;
		}

 		return true;
	}
}

?>