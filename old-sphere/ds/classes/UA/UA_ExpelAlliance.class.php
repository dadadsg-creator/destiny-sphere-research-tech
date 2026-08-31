<?php $CVS_Version = '$Revision$ ; $Date$';
// команда исключить игрока из своего аль€нса

class UA_ExpelAlliance extends UA_Alliance
{
	public $id_alliance;
	public $joinpassword;
	public $errorname;
	public $id_userinstance;

	function UA_ExpelAlliance ()
	{
		UA_Alliance::UA_Alliance ();
		$this->errorname = array ();
	}

	// провер€ем параметры
	function checkLocalParams ()
	{
		// id игрока, которого исключаем
		$this->id_userinstance = $this->root->get_attribute ( "id_userinstance" );
		if ( empty ( $this->id_userinstance ) )
		{
			$this->errormsg .= " SYSTEM ERROR UA_ExpelAlliance::checkLocalParams1";
			return false;
		}

		// id аль€нса
		$this->id_alliance = $this->root->get_attribute ( "id_alliance" );
		if ( empty ( $this->id_alliance ) )
		{
			$this->errormsg .= " SYSTEM ERROR UA_ExpelAlliance::checkLocalParams2";
			return false;
		}
		return true;
	}

	// провер€ем допустимость действи€
	function checkAction ()
	{
//		global $common;
		global $params;

		$res = DS_Alliance::IsUserLeader($this->id_alliance, $params["uiid"]);
		
		if(!$res)
		{
			$this->errormsg .= " SYSTEM ERROR UA_ExpelAlliance::checkAction1";
			return false;
		}

		$res = DS_Alliance::IsUserInAlliance($this->id_alliance, $this->id_userinstance);

		if(!$res)
		{
			$this->errormsg .= " SYSTEM ERROR UA_ExpelAlliance: ".$this->id_userinstance." not a member of alliance ".$this->id_alliance; // UA_ExpelAlliance::checkAction2
			return false;
		}
		
		if( $this->id_userinstance == $this->uiid )
		{
			$this->errormsg .= "Can't expel yourself";
			return false;
		}
		
 		return true;
 	}

 	// выполн€ем действие
	function performAction()
	{
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
		
		$alliance = DE_Alliance::CreateFromID((int)$this->id_alliance);
		
		//ѕроверка попытки выйти из глобального аль€нса из игры.
		if($alliance->isGlobal())
		{
			$messparams[0] = $alliance->getName();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'ALLIANCE_TRY_EXPELL_GLOBAL', $messparams);
			
			return true;
		}

		$userInstance = DE_UserInstance::CreateFromID((int)$this->id_userinstance);		
		
		// им€ исключаемого
		$messparams[0] = "{b} ".$userInstance->getUser()->getName()."{/b}";
		$messparams[1] = "{b} ".$alliance->getName()."{/b}";
		
		// пишем игроку письмо что он исключен
		if(!$this->sendPrivateMessage($this->id_userinstance, $this->langId, $this->uiid, 'ALLIANCE_EXPEL_MEMBER_SUBJECT', 'ALLIANCE_EXPEL_MEMBER_TEXT', $messparams))
		{
			return false;
		}
		
		ME_ExpelAlliance::Exec($alliance, $userInstance);

		// системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "EXPEL_ALLIANCE_MESS", $messparams );

		// минимальный рефреш
		//if ( $_SESSION['profi_game'] )
		//{
			$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}

		$this->_sendHelper();
		
		if(!$this->refresh(2, "", "", "", $noCellData))
		{
			return false;
		}
		
		return true;
	}
	
	private function _sendHelper()
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><delalliancemember />";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		$ch = $root->new_child("member");
		$ch->set_attribute("id", $this->id_userinstance);
		$ch->set_attribute("name", '');
		$root->append_child($ch);
		
		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'helper' );		
	}
}
?>