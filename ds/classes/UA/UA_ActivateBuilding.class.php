<?php $CVS_Version = '$Revision$ ; $Date$';

// команда активироваь здание



class UA_ActivateBuilding extends UA_Building {

	function ActivateBuilding() {
		UA_Building::UA_Building ();
	}

	// провер€ем пришедшие параметры
	function checkLocalParams () {

		$this->cellnumber = (int)$this->root->get_attribute("c");
		assert($this->cellnumber > 0);
		
		$cellData = ME_CombViewGetCellIDAndBuildigIDByCell::Exec($this->cellnumber, true);
		
		$this->id_cell = $cellData['CELL_ID'];
		assert(is_int($this->id_cell) && $this->id_cell > 0);

		$this->groundid = $cellData['GROUND_TYPE_ID'];
		assert(is_int($this->groundid) && $this->groundid > 0);
		
		
		$this->attachedbid = $cellData['ATTACHED_BUILDING_ID'];
		assert(is_int($this->attachedbid) && $this->attachedbid >= 0);
			
		$this->bid = $cellData['BUILDING_ID'];
		assert(is_int($this->bid) && $this->bid >= 0);
		return true;
	}

	// провер€ем допустимо ли действие
	function checkAction () {

		global $params;
		global $common;

		// не активно, не строитс€, не активируетс€
		$sql = sprintf ( "select id_attachedbuilding
				from ds_attachedbuilding
				where id_attachedbuilding = %d
				and active = false
				and underconstruction = false
				and inactivation = false
				and id_building = %d
				and id_cell = %d",
				$this->attachedbid,
				$this->bid,
				$this->id_cell );

		$res = $common->getResultArray ( "id_attachedbuilding_id_attachedbuilding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_ActivateBuilding::checkAction1";

			return false;
		}

		// если здание не onlyasupgrade - провер€ем наличие необходимых зданий
		$sql = sprintf ( "select onlyasupgrade
				from ds_building
				where id_building = %d",
				$this->bid );

		$res = $common->getResultArray ( "only_as_upgrade_id_building_".$this->bid, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_ActivateBuilding::checkAction2";

			return false;
		}

		if ( $res[0]['onlyasupgrade'] == 'f' ) {
			if ( !$this->isNeedsBuildingPresent ( $this->comb, $this->bid ) ) {
				return false;
			}
		}

		$res = "";

		return true;
	}

	// выполн€ем действие
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
		
		// получаем врем€ окончани€ эвента
		if ( !$this->getEventEndTime () ) {
			return false;
		}

		// название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

		// название здани€
		$raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);

		$messparams[1] = "{b} ".@$raceBuilding->getLocalizedName()."{/b}";

		// указываем что началась активаци€
		$sql = sprintf ( "update only DS_AttachedBuilding
				set inactivation = true
				where ID_AttachedBuilding = %d",
				$this->attachedbid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR - UA_ActivateBuilding::performAction3";

			return false;
		}

		// указываем врем€ начала и окончани€
		$sql = sprintf ( "update only ds_attachedbuilding
				set starttime = '%s',
				endtime = '%s'
				where id_attachedbuilding = %d",
				$this->starttime,
				$this->endtime,
				$this->attachedbid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR - UA_ActivateBuilding::performAction4";

			return false;
		}

		// создаем эвент
		$this->createEvent('ACTIVATE_BUILDING');

		$sql = sprintf ( "insert into DS_AttachedBuildingBindEventQueue
				(ID_AttachedBuilding, ID_EventQueue)
				values (%d, %d)",
				$this->attachedbid,
				$this->eqid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR - UA_ActivateBuilding::performAction7";

			return false;
		}

		// добавл€ем системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "START_ACTIVATE_BUILDING_MESS", $messparams );
			
		//–естартим вид соты	
		CR_CombView::CreateFromCombID((int)$this->comb)->resetBuildings(DE_Cell::CreateFromID($this->id_cell)->getCellTypeID());

		if ( !$this->refresh ( 2, "", 1 ) ) {
			return false;
		}

		return true;
	}

	// выдает врем€ окончани€ эвента
	function getEventEndTime () {

		global $common;
		global $params;

		$sql = sprintf( "select now() as starttime,
				now()+
				((text(bha.value*(( ".$this->worldSettings->get('TURN_TIME')."*60 )/
				(".$this->worldSettings->get('REACTIVATION_DIVISOR')."))))::interval) as endtime
				from ds_building b,
				ds_buildinghasattribute bha,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where b.id_building = bha.id_building
				and bha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'PRODUCTION_COSTS'
				and atn.attributename = 'CONSTRUCTION_TIME'
				and b.id_building = %d",
				$this->bid );

		$res = $common->getResultArray ( "activate_building_start_time_end_time", $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - UA_ActivateBuilding::getEventEndTime1";

			return false;
		}

		$this->endtime = $res[0]['endtime'];
		$this->starttime = $res[0]['starttime'];

		$res = "";

		return true;
	}
}

?>