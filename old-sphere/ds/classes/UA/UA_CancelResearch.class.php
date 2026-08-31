<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отмена исследования



class UA_CancelResearch extends UA_Research {

	public $bindeqid;

	function UA_CancelResearch () {
		UA_Research::UA_Research ();
	}		

	// проверка параметров
	function checkLocalParams () {

		// название науки
		$this->sciencename = $this->root->get_attribute ("id");
		if ( empty ( $this->sciencename ) ) {
			$this->errormsg .= " SYSTEM ERROR UA_CancelResearch::checkLocalParams1";
			return false;
		}
		
		// получить id науки
		if ( !$this->getScienceID ( $this->sciencename ) ) {
			return false;
		}
		return true;
	}

	// проверяем допустимость действия
	function checkAction() {

		global $params;
		global $common;

		// идет ли исследование науки
		$sql = sprintf ( "select uhsbeq.id_eventqueue
				from ds_userhassciencebindeventqueue uhsbeq,
				ds_userhasscience uhs
				where uhsbeq.id_userinstance = uhs.id_userinstance
				and uhsbeq.id_science = uhs.id_science
				and uhs.id_userinstance = %d
				and uhs.id_science = %d
				and uhs.inresearch = true",
				$params["uiid"],
				$this->scienceid );

		$res = $common->getResultArray ( "id_eventqueue_id_userinstance_id_science_".$params["uiid"]."_".$this->scienceid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( !$res ) {
			$this->errormsg = "SYSTEM ERROR - UA_CancelResearch::checkAction1";
			
			return false;
		}

		$this->bindeqid = $res[0]['id_eventqueue'];

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

		// название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

		// название науки
		$sql = sprintf ( " select text as sciencename
				from DS_LocalizedTextLanguage ltl,
				DS_Science s
				where s.ID_LocalizedName = ltl.ID_LocalizedText
				and ltl.ID_Language = %d
				and s.ID_Science = %d",
				$params["lang"],
				$this->scienceid );

		$res = $common->getResultArray ( "sciencename_id_language_id_science_".$params["lang"]."_".$this->scienceid, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res ) {
			$this->errormsg = "SYSTEM ERROR - UA_CancelResearch::performAction2";
			
			return false;
		}

		$messparams[1] = "{b} ".$res[0]['sciencename']."{/b}";

		// снимаем эвент
		$sql = sprintf ( "delete from ds_userhassciencebindeventqueue
				where id_userinstance = %d
				and id_science = %d",
				$params["uiid"],
				$this->scienceid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR - UA_CancelResearch::performAction3";
			
			return false;
		}

		// удаляем науку
		$sql = sprintf ( "delete from ds_userhasscience
				where id_userinstance = %d
				and id_science = %d",
				$params["uiid"],
				$this->scienceid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR - UA_CancelResearch::performAction4";
			
			return false;
		}

		// прибиваем эвент
		DE_EventQueue::CreateFromID((int)$this->bindeqid)->setIsTrash(true);			

		// отправляем сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_RESEARCH_MESS2", $messparams, 0, 'BLUE' );

		$res = "";
		$sql = "";
		
		//if ( $_SESSION['profi_game'] ){
			$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}
		
		// минимальный рефреш
		if ( !$this->refresh ( 2, "", "", "", $noCellData ) ) {
			return false;
		}

		return true;
	}	
}

?>