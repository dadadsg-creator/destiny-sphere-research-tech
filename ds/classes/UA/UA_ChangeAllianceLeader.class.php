<?php $CVS_Version = '$Revision$ ; $Date$';


// команда смена лидера альянса







class UA_ChangeAllianceLeader extends UA_Alliance {



	public $id_alliance;

	public $errorname;

	public $newleader;

	public $newleadername;

	public $alliancetag;



	function UA_ChangeAllianceLeader () {

		UA_Alliance::UA_Alliance ();

		$this->errorname = array ();

	}



	// проверяем параметры

	function checkLocalParams () {



		global $common;



		// id нового лидера

		$this->newleader = $this->root->get_attribute ( "newleader" );

		if ( empty( $this->newleader ) ) {

			$this->errormsg .= " SYSTEM ERROR - UA_ChangeAllianceLeader::checkLocalParams1";

			return false;

		}



		return true;

	}

	

	// проверяем допустимость действия

	function checkAction () {



		global $common;

		global $params;



		$common->clearLocalCache ( $params["uiid"] );



		// доступно только для лидера альянса

		$sql = sprintf ( "select a.id_alliance,

			a.alliancetag

			from ds_userhasalliancestatus uhas,

			ds_alliance a

			where uhas.id_alliance = a.id_alliance

			and uhas.id_userinstance = %d",

			$params["uiid"] );



		$res = $common->getResultArray ( "id_alliance_id_userinstance_".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );

		if ( !$res ) {

			$this->errormsg .= " SYSTEM ERROR UA_ChangeAllianceLeader::checkAction1";

			

			return false;

		}



		$this->id_alliance = $res[0]['id_alliance'];

		$this->alliancetag = $res[0]['alliancetag'];



		// состоит ли новый лидер в том же альянсе

		$sql = sprintf ( "select uha.id_alliance,

			u.username

			from ds_userhasalliance uha,

			ds_user u,

			ds_userinstance ui

			where uha.id_userinstance = ui.id_userinstance

			and ui.id_user = u.id_user

			and ui.id_userinstance = %d

			and uha.id_alliance = %d

			and ui.id_userinstance != %d",

			$this->newleader,

			$this->id_alliance,

			$params["uiid"] );



		$res = $common->getResultArray ( "id_alliance_id_userinstance_id_userinstance_".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );

		if ( !$res ) {

			$this->errormsg .= " SYSTEM ERROR UA_ChangeAllianceLeader::checkAction2";

			

			return false;

		}



		$this->newleadername = $res[0]['username'];

		

		return true;

	}



	// выполняем команду	

	function performAction () {



		global $params;

		global $common;


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
		
		// меняем лидера

		$sql =sprintf ( "update ds_userhasalliancestatus

				set id_userinstance = %d

				where id_userinstance = %d

				and id_alliance = %d",

				$this->newleader,

				$params['uiid'],

				$this->id_alliance );



		$res = $common->executeQuery ( $sql );

		if ( !$res ) {

			$this->errormsg .= " SYSTEM ERROR UA_UA_ChangeAllianceLeader::performAction1";

			

			return false;

		}



		$messageparams[0] = "{b} ".$this->newleadername."{/b}";

		$messageparams[1] = "{b} ".$this->alliancetag."{/b}";



		// системное сообщение

		DE_SystemMessages::AddMessageFromAction(__CLASS__, "NEW_ALLIANCE_LEADER_MESS", $messageparams );



		$messageparams = "";



		$messageparams[0] = $this->newleadername;



		// отправить письмо всем членам альянса о том что сменился лидер

		if ( !$this->sendAllianceMessage ( $this->id_alliance,

						$params["lang"],

						$params["uiid"],

						'ALLIANCE_NEW_LEADER_SUBJECT',

						'ALLIANCE_NEW_LEADER_TEXT',

						$messageparams ) ) {

			return false;

		}



		$res = "";

		$sql = "";



		// минимальный рефреш
		
		//if ( $_SESSION['profi_game'] ){
			$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}
		
		if ( !$this->refresh ( 2, "", "", "", $noCellData ) ) {

			return false;

		}



		return true;

	}

}



?>