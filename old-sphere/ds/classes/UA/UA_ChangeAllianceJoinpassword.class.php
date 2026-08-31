<?php $CVS_Version = '$Revision$ ; $Date$';



// команда сменить пароль на вступление в альянс






class UA_ChangeAllianceJoinpassword extends UA_Alliance {



	public $joinpassword;

	public $id_alliance;

	public $alliancetag;

	public $errorname;



	function UA_ChangeAllianceJoinpassword () {

		UA_Alliance::UA_Alliance ();

		$this->errorname = array ();

	}



	// проверяем параметры

	function checkLocalParams () {



		global $common;





		// новый пароль

		$this->joinpassword = trim ( utf8_decode ( $this->root->get_content() ) );

		

		if ( empty ( $this->joinpassword ) ) {

			$this->errormsg .= " SYSTEM ERROR UA_ChangeAllianceJoinpassword::checkLocalParams3";

			return false;

		}

		

		if ( preg_match ( '/[\'\"\`\!\@\#\$\%\^\*\(\)\;\:\{\}\[\]\<\>\?]/', $this->joinpassword ) ) {

			$this->errorname[] = 'JOINPASSWORD_HAS_WRONG_SYMBOLS';

		}



		if ( strlen ( $this->joinpassword ) < 3 ) {

			$this->errorname[] = 'JOINPASSWORD_TO_SHORT';

		}



		if ( strlen ( $this->joinpassword ) > 30 ) {

			$this->errorname[] = 'JOINPASSWORD_TO_LONG';

		}

		

		return true;

	}

	

	// проверяем допустимость действия

	function checkAction () {



		global $common;

		global $params;



		$common->clearLocalCache ( $params["uiid"] );



		if ( sizeof ( $this->errorname ) > 0 ) return true;



		// доступно только лидеру альянса

		$sql = sprintf ( "select a.id_alliance,

			a.alliancetag

			from ds_userhasalliancestatus uhas,

			ds_alliance a

			where uhas.id_alliance = a.id_alliance

			and uhas.id_userinstance = %d",

			$params["uiid"] );



		$res = $common->getResultArray ( "id_alliance_alliancetag_id_userinstance_".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );

		if ( !$res ) {

			$this->errormsg .= " SYSTEM ERROR UA_ChangeAllianceJoinpassword::checkAction1";

			

			return false;

		}



		$this->id_alliance = $res[0]['id_alliance'];

		$this->alliancetag = $res[0]['alliancetag'];



		return true;

	}

	

	// выполняем команду

	function performAction () {



		global $params;

		global $common;



		if ( sizeof ( $this->errorname ) == 0 ) {

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

			// ставим новый пароль

			$sql =sprintf ( "update ds_alliance

					set joinpassword = '%s'

					where id_alliance = %d",

					$this->joinpassword,

					$this->id_alliance );



			$res = $common->executeQuery ( $sql );

			if ( $res === false ) {

				$this->errormsg .= " SYSTEM ERROR UA_ChangeAllianceJoinpassword::performAction1";

				

				return false;

			}



			$messageparams[0] = "{b} ".$this->alliancetag."{/b}";



			DE_SystemMessages::AddMessageFromAction(__CLASS__, "NEW_ALLIANCE_JOINPASSWORD_MESS", $messageparams );



		} else { // any error



			// выводим сообщение и выходим

			$messparams[0] = "";

			for ( $i=0; $i < sizeof ( $this->errorname ); $i++ ) {

				DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->errorname[$i], $messparams );

			}

		}



		$res = "";

		$sql = "";



		// минимальный рефреш
		
		if ( $_SESSION['profi_game'] ){
			$noCellData = true;
		} else {
			$noCellData = false;
		}
		
		if ( !$this->refresh ( 2, "", "", "", $noCellData ) ) {

			return false;

		}



		return true;

	}

}



?>