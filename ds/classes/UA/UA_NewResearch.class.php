<?php $CVS_Version = '$Revision$ ; $Date$';

// команда начать новое исследование
class UA_NewResearch extends UA_Research {

	function UA_NewResearch () {
		UA_Research::UA_Research ();
	}

	function checkLocalParams () {

		global $common;
		global $params;

		// название науки
		$this->sciencename = $this->root->get_attribute ( "id" );
		if ( $this->sciencename == ""  ) {
			$this->errormsg .= " SYSTEM ERROR UA_Research::checkLocalParams1";
			return false;
		}

		// получаем id
		if ( !$this->getScienceID ( $this->sciencename ) ) {
			return false;
		}

		return true;
	}

	// проверяем допустимость действия
	function checkAction () {

		global $params;
		global $common;

		// уже идет исследование на соте
		if ( !$this->isCombHasResearch ( $this->comb ) ) {
			return false;
		}

		// уже идет исследование вообще
		if ( !$this->isUserHasResearch ( $params['uiid'] ) ) {
			return false;
		}
		
		// сколько есть в наличии ученых
		// сколько ученых на всех сотах
		$userCombsCollection = DC_UserInstanceCombs::CreateFromUserInstanceID((int)$params['uiid']);
			
		$this->scientists = 0;
			
		$userCombsCollection->resetNext();
		while($comb = $userCombsCollection->next())
		{
			$this->scientists += $comb->getAttributes()->getAttribute('CURRENT_AMOUNT', 'SCIENTIST');
		}	
					
		if ( $this->scientists == 0 ) 
		{
			$this->errormsg .= " SYSTEM ERROR UA_Research::checkAction2";
			return false;
		}
		
		// выключена наука
		if ( $this->isBranchDisabled ( $this->world, $this->branchid ) ) {
			return false;
		}
		
		if( $this->isScienceDisabled ( $this->scienceid, $this->branchid ) ) {
			return false;
		}

		// уже есть такая наука
		if ( !$this->isUserHasScience ( $this->scienceid, $params["uiid"] ) ) {
			return false;
		}

		// присутствуют необходимые здания
		if ( !$this->isNeedsBuildingPresent ( $this->scienceid, $this->comb ) ) {
			return false;
		}

		// достаточно науки
		if ( !$this->isScienceEnough ( $params["uiid"], $this->scienceid ) ) {
			return false;
		}

		$res = "";

		return true;
	}

	// выполняем действие
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
		
		// время окончания эвента
		if ( !$this->getEventEndTime() ) {
			return false;
		}

		// название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

		// название науки
		$sql = sprintf ( " select 
					ltl.text as sciencename,
					s.level
				from DS_LocalizedTextLanguage ltl,
				DS_Science s
				where s.ID_LocalizedName = ltl.ID_LocalizedText
				and ltl.ID_Language = %d
				and s.ID_Science = %d",
				$params["lang"],
				$this->scienceid );
		$res = $common->getResultArray ( "sciencename_id_language_id_science_".$params["lang"]."_".$this->scienceid, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if(!$res) 
		{
			$this->errormsg .= " SYSTEM ERROR UA_NewResearch::performAction2";
			
			return false;
		}

		$messparams[1] = "{b} ".$res[0]['sciencename']."{/b}";
		
		if($this->endtimeObject->value >= 2147000000)
		{			
			$messparams[0] = "{b} ".$res[0]['sciencename']."{/b}";
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "UA_NEW_RESEARCH_ERROR_NOT_ENOUGH_SCIENTISTS", $messparams, 0, 'BLUE'); 	
			return true;
		}
		
		// создаем эвент	
		$this->createEvent('NEW_RESEARCH');
		
		// записываем юзеру науку
		$sql = sprintf ( "insert into DS_UserHasScience ( ID_Science, ID_UserInstance, inresearch, starttime, endtime ) 
				values ( %d, %d, true, '%s', '%s' )",
				$this->scienceid,
				$params["uiid"],
				$this->starttime,
				$this->endtime );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR UA_Research::performAction3";
			
			return false;
		}

		$sql = sprintf ( "insert into DS_CombBindEventQueue 
				( ID_EventQueue, ID_Comb ) values ( %d, %d )",
				$this->eqid, 
				$this->comb );
		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR UA_Research::performAction6";
			
			return false;
		}

		$sql = sprintf ( "insert into DS_UserHasScienceBindEventQueue 
				( ID_Science, ID_UserInstance, ID_EventQueue ) 
				values ( %d, %d, %d )",
				$this->scienceid, 
				$params["uiid"],
				$this->eqid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR UA_Research::performAction7";
			
			return false;
		}

		// системное собщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "START_RESEARCH_MESS", $messparams, 0, 'BLUE' ); 

		// минимальный рефреш
		if(!$this->refresh(2, '', '', 1, true)) 
		{
			return false;
		}

 		return true;
	}

	function getEventEndTime () {

		global $common;
		global $params;
		
		$raceID = DE_UserInstance::CreateFromID((int)$params['uiid'])->getRaceID();
		$raceAttributes = DE_RaceAttributes::CreateFromRaceID($raceID);		

		if($raceAttributes->haveAttribute('BONUS_RELATIVE', 'SCIENCE')) 
		{
			$science_bonus = $raceAttributes->getAttribute('BONUS_RELATIVE', 'SCIENCE');
		} else {
			$science_bonus = (float)0;
		}
		
		$sql = "SELECT 
					researchcosts
				FROM ds_science
				WHERE id_science = ".$this->scienceid;
		$res = $common->getResultArray("RESERCH_COST_BY_SCIENCE_ID_".$this->scienceid, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		assert(empty($comon->errormsg));
		
		$reserachCost = (float)$res[0]['researchcosts'];
		
		$startDateObject = GB_TypeFactory::CreateBase('GB_Date', time());
		
		$delta = round($this->worldSettings->get('TURN_TIME') * 60 * ($reserachCost / ($this->scientists + $this->scientists * $science_bonus)));
		
		$startDateObject = GB_TypeFactory::CreateBase('GB_Date', time());
		$newTimeStamp = time() + $delta;

		$endDateObject = GB_TypeFactory::CreateBase('GB_Date', $newTimeStamp);
		
		$this->starttime = $startDateObject->getPgFormat();
		$this->endtime = $endDateObject->getPgFormat();
		$this->endtimeObject = $endDateObject;
				
		return true;
	}
}

?>