<?php $CVS_Version = '$Revision$ ; $Date$';

// команда выйти из альянса
class UA_LeaveAlliance extends UA_Alliance 
{
	public $id_alliance;
	public $joinpassword;
	public $errorname;
	
	function UA_LeaveAlliance() 
	{
		UA_Alliance::UA_Alliance ();
		$this->errorname = array ();
	}

	// проверяем параметры
	function checkLocalParams() 
	{		
		$this->id_alliance = (int)$this->root->get_attribute ( "id_alliance" );
		assert($this->id_alliance > 0);		

		return true;
	}

	// проверяем допустимость действия
	function checkAction() 
	{				
		$userInstance = DE_UserInstance::CreateFromID($this->params["uiid"]);

		if ($userInstance->getAlliance()->getId() != $this->id_alliance)
		{
			return false;
		}
		
		return true;
 	}

 	// выполнить дейтвие
	function performAction()
	{
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
		
		$alliance = DE_Alliance::CreateFromID((int)$this->id_alliance);
		
		//Проверка попытки выйти из глобального альянса из игры.
		if($alliance->isGlobal())
		{
			$messparams[0] = $alliance->getName();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'ALLIANCE_TRY_EXPELL_GLOBAL', $messparams);
			
			return true;
		}
		
		// разрешено ли выходить из альянса
		if(DE_WorldSettings::CreateForCurentWorld()->get('DISABLE_LEAVE_ALLIANCE'))
		{
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "LEAVE_ALLAINCE_DISABLED", $messparams);
			return true;
		}
		
		$userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);

		//Проверяем, является ли игрок ли игрок лидером альянса
		$res = DS_Alliance::IsUserLeader($alliance->getID(), (int)$this->uiid);
		if(count($res) > 0)
		{
			$leader = 1;
		} else {
			$leader = 0;
		} 	

		ME_ExpelAlliance::Exec($alliance, $userInstance);
		
		// имя альянса
		$messparams[0] = $alliance->getName();

		// имя ушедшего игрока
		$messageparams[0] = $userInstance->getUser()->getName();
							
		if(!$this->sendAllianceMessage($this->id_alliance, $this->langId, 0, 'ALLIANCE_LEAVE_MEMBER_SUBJECT', 'ALLIANCE_LEAVE_MEMBER_TEXT', $messageparams))
		{
			return false;
		}

		// системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "LEAVE_ALLIANCE_MESS", $messparams);
		
		// если уходит лидер
		// если еще остались люди в альянсе - назначаем нового лидера
		// иначе распускаем альянс
 		if($leader == 1)
 		{
 			$member = DS_Alliance::GetMemberExceptThis($this->id_alliance, $this->uiid);

 			if(count($member) > 0) 
 			{ // назначаем нового лидера

 				$newLeaderID = $member[0]['id_userinstance'];

				assert(DS_Alliance::SetNewAllianceLeader($newLeaderID, $this->id_alliance));

				$messageparams[0] = $member[0]['username'];

				if(!$this->sendAllianceMessage($this->id_alliance, $this->langId, 0, 'ALLIANCE_NEW_LEADER_SUBJECT', 'ALLIANCE_NEW_LEADER_TEXT',	$messageparams)) 
				{
					return false;
				}
			} else { // распускаем альянс
				assert(DS_Alliance::DeleteAlliance($this->id_alliance));

				// системное сообщение
				DE_SystemMessages::AddMessageFromAction(__CLASS__, "DISMISS_ALLIANCE_MESS", $messparams );
			}
 		}
		
		//if($_SESSION['profi_game'])
		//{
			$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}
		
		// минимальный рефреш
		if(!$this->refresh(2, "", "", "", $noCellData))
		{
			return false;
		}

 		return true;
	}
}

?>