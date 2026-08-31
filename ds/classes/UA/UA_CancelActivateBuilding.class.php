<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отмена активации здания



class UA_CancelActivateBuilding extends UA_Building {

	public $bindeqid;

	function UA_CancelBuild () {
		UA_Building::UA_Building ();
	}

	// проверяем пришедшие параметры
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

	// проверяем допустимость действия
	function checkAction() {

		global $common;
		global $params;

		// здание активируется, не активно
		$sql = sprintf ( "select id_attachedbuilding
				from ds_attachedbuilding
				where id_attachedbuilding = %d
				and inactivation = true
				and active = false
				and id_building = %d
				and id_cell = %d",
				$this->attachedbid,
				$this->bid,
				$this->id_cell );

		$res = $common->getResultArray ( "is_inactive_id_attachedbuilding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_CancelActivateBuilding::checkAction1";
			
			return false;
		}

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

		// название здания		
		$raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);
		$messparams[1] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

		// получаем id_eventqueue
		$sql = sprintf ( "select ab.ID_AttachedBuilding,
				abbeq.ID_EventQueue
				from DS_AttachedBuilding ab,
				DS_AttachedBuildingBindEventQueue abbeq,
				DS_EventQueue eq,
				DS_Event e
				where ab.ID_AttachedBuilding = abbeq.ID_AttachedBuilding
				and abbeq.ID_EventQueue = eq.ID_EventQueue
				and eq.ID_Event = e.ID_Event
				and eq.isTrash = false
				and e.EventName = 'ACTIVATE_BUILDING'
				and ab.ID_AttachedBuilding = %d",
				$this->attachedbid );

		$res = $common->getResultArray ( "id_attachedbulding_id_eventqueue_id_attachedbuilding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_CancelActivateBuilding::performAction3";
			
			return false;
		}

		$this->bindeqid = $res[0]['id_eventqueue'];

		// убираем знак того что здание активируется
		$sql = sprintf ( "update only DS_AttachedBuilding
				set inactivation = false
				where ID_AttachedBuilding = %d",
				$this->attachedbid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR UA_CancelActivateBuilding::performAction4";
			
			return false;
		}

		// прибиваем эвент
		DE_EventQueue::CreateFromID((int)$this->bindeqid)->setIsTrash(true);		

		DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_ACTIVATE_BUILDING_MESS", $messparams );

		//Рестартим вид соты	
		CR_CombView::CreateFromCombID((int)$this->comb)->resetBuildings(DE_Cell::CreateFromID($this->id_cell)->getCellTypeID());

		// рефреш с актуализацией ресурсов
		if ( !$this->refresh ( 2, "", 1 ) ) {
			return false;
		}

		return true;
	}
}

?>