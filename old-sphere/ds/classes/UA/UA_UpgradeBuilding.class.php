<?php $CVS_Version = '$Revision$ ; $Date$';

// команда апгрейдить здание
class UA_UpgradeBuilding extends UA_Building {

	public $tobuild;

	public $isResourceEnought;

	function UA_UpgradeBuilding() {
		UA_Building::UA_Building();
	}

	// проверяем параметры
	function checkLocalParams()
	{
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
	function checkAction () {

		global $params;
		global $common;

		// активно, не апгрейдится
		$sql = sprintf ( "select active,
				inupgrade
				from ds_attachedbuilding
				where id_attachedbuilding = %d
				and id_building = %d
				and id_cell = %d",
		$this->attachedbid,
		$this->bid,
		$this->id_cell );

		$res = $common->getResultArray ( "active_inupgrade_id_attachedbuilding_".$this->attachedbid, $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - UA_UpgradeBuilding::checkAction1";

			return false;
		}

		if ( $res[0]['active'] == 'f' || $res[0]['inupgrade'] == 't' ) {
			$this->errormsg .= " SYSTEM ERROR - UA_UpgradeBuilding::checkAction2";
			return false;
		}


		$where = "";
		$cache = "";
		// Cмотрим, является ли сота захватываемой
		if (DE_Comb::CreateFromID((int)$this->comb)->getIsCapture()) {
			$where = "
					AND NOT EXISTS
						(
							SELECT *
							FROM ds_disabledbuilding_colony
							WHERE 
								id_world = ".$_SESSION['world']."
								AND
								id_building = bcbu.id_to_building							
						)
                 		";
			$cache = "colony";
		}

		// в какое здание можно апгрейдить
		$sql = sprintf ( "
				select id_to_building
				from 
					ds_buildingcanbeupgraded bcbu,
					ds_buildingcanbuildbyrace bcbr
				where 
					bcbu.id_to_building = bcbr.id_building
					and 
					id_from_building = %d
					and 
					bcbr.id_race = %d
					AND NOT EXISTS
						(
							SELECT *
							FROM ds_disabledbuilding
							WHERE 
								id_world = ".$_SESSION['world']."
								AND
								id_building = bcbu.id_to_building							
						)
					$where",
		$this->bid,
		$params["race"] );
	
		$res = $common->getResultArray ( "id_to_building_id_building_id_race_".$cache.$this->bid."_".$params["race"]."_".$_SESSION['world'], $sql, "", "FULL_PREFIX", "GLOBAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - UA_UpgradeBuilding::checkAction3";

			return false;
		}

		assert(count($res) > 0);

		$this->tobuild = $res[0]['id_to_building'];

		// хватает ли одномоментных строек
		if ( !$this->isSimiltaneousBuildsEnough ( $this->comb ) ){
			return false;
		}

		// есть ли необходимые здания
		if ( !$this->isNeedsBuildingPresent ( $this->comb, $this->bid ) ) {
			return false;
		}

		// хватает ли ресурсов
		if ( !$this->isResourcesEnoughToUpgrade ( $this->world, $this->comb, $this->tobuild ) ) {
			$this->isResourceEnought = false;
		} else {
			$this->isResourceEnought = true;
		}

		// хватает ли науки
		if ( !$this->isScienceEnough ( $params["uiid"], $this->bid ) ) {
			return false;
		}

		return true;
	}

	// выполняем действие
	function performAction()
	{

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

		if(!$this->isResourceEnought)
		{
			$this->processNotEnoughtResources();
			return true;
		}

		//Здание нельзя начинать апгредйить пока в нем строяться юниты
		$sql = "SELECT * FROM ds_unitinproduction WHERE id_attachedbuilding = ".$this->attachedbid;
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if(count($res) > 0)
		{
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANT_UPGRADE_BUILDING_WITH_UNITS", $messparams );
			return true;
		}

		// время окончания эвента
		if ( !$this->getEventEndTime () ) {
			return false;
		}

		// название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

		// название здания откуда
		$raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);
		$messparams[1] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

		// название здания куда
		$raceBuilding = DE_Building::CreateFromID((int)$this->tobuild)->getRaceBuilding((int)$params["race"]);
		$messparams[2] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

		// добавляем системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "START_UPGRADE_BUILDING_MESS", $messparams );


		// начинаем апгрейд
		$sql = sprintf ( "update only ds_attachedbuilding
				set starttime = '%s', endtime = '%s',
				inupgrade = true
				where id_attachedbuilding = %d",
		$this->starttime,
		$this->endtime,
		$this->attachedbid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR - UA_UpgradeBuilding::performAction4";

			return false;
		}

		// вычитаем ресурсы
		$buildingToAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$this->tobuild);
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

		$attributeNames = $buildingToAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');
		foreach($attributeNames as $k=>$attributeName)
		{
			if(!$combAttributes->haveAttribute('CURRENT_AMOUNT', $attributeName))
			{
				continue;
			}

			$delta = -(float)round($buildingToAttributes->getAttribute('PRODUCTION_COSTS', $attributeName) / $this->worldSettings->get('UPGRADE_COSTS_DIVISOR'));

			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, $delta);
		}

		// увеличиваем кол-во текущих строек
		$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS', (float)1);

		//Убираем атрибуты старого здания из соты
		$buildingFromAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$this->bid);

		//Если в нем есть люди то этот атрибут оставляем
		if($buildingFromAttributes->haveAttribute('MAX_AMOUNT', 'WORKER'))
		{
			$combAttributes->deltaAttribute('MAX_AMOUNT', 'WORKER', $buildingFromAttributes->getAttribute('MAX_AMOUNT', 'WORKER'));
		}

		//Убираем атрибуты сторого здания
		ME_AddBuildingAttributesToComb::AddAttributes($buildingFromAttributes, $combAttributes, 'minus');

		//Убираем арты старого здания из бонусов на период апдейта
		ME_AddBuildingAttributesToComb::AddArtefactAttributes($this->attachedbid, $combAttributes, 'minus');

		// системное сообщение
		$this->createEvent('UPGRADE_BUILDING');

		$sql = sprintf ( "insert into DS_AttachedBuildingBindEventQueue (ID_AttachedBuilding, ID_EventQueue)
				values (%d, %d)",
		$this->attachedbid, $this->eqid);

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR - UA_UpgradeBuilding::performAction9";

			return false;
		}

		//Рестартим вид соты
		CR_CombView::CreateFromCombID((int)$this->comb)->resetBuildings(DE_Cell::CreateFromID($this->id_cell)->getCellTypeID());

		// рефреш с актуализацие соты и ресурсов
		if ( !$this->refresh ( 2, "", 1, 1 ) ) {
			return false;
		}
		
		$current = $combAttributes->getAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS');
		$maxBuilding = $combAttributes->getAttribute('MAX_AMOUNT', 'SIMILTANEOUS_BUILDS') + $this->worldSettings->get('SIMILTANEOUS_BUILDS_ADDON');

		if ($current == $maxBuilding) {
			$alertmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('MAX_SYMULTANEOSLY_BUILDINDS', array($maxBuilding), null, null, false);

			$alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$alertmsg.']]></txt></swloader>';

			SU_OutputHandler::Singleton()->addReadyString($alertText);
		}

		return true;
	}

	function createEventParams () {

		global $params;

		$this->eventparams["ID_BuildingFrom"] = $this->bid;
		$this->eventparams["ID_BuildingTo"] = $this->tobuild;
		$this->eventparams["ID_Cell"] = $this->id_cell;
		$this->eventparams["ID_AttachedBuilding"] = $this->attachedbid;
		$this->eventparams["ID_Language"] = $params["lang"];
		$this->eventparams["ID_Comb"] = $this->comb;
		$this->eventparams["ID_UserInstance"] = $params["uiid"];
		$this->eventparams["ID_World"] = $this->world;

		return true;
	}

	// время окончания эвента
	function getEventEndTime() {

		global $common;
		global $params;

		$sql = sprintf ( "select now() as starttime, now()+
			(select (text((".$this->worldSettings->get('TURN_TIME')."*60*bha.value/
			(".$this->worldSettings->get('UPGRADE_COSTS_DIVISOR')."))))::interval) as endtime
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
		$this->tobuild);

		$res = $common->getResultArray ( "starttime_endtime", $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - UA_UpgradeBuilding::getEventEndTime1";

			return false;
		}

		$this->endtime =  $res[0]['endtime'];
		$this->starttime =  $res[0]['starttime'];

		return true;
	}

	function processNotEnoughtResources()
	{
		$messparams = array();
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "UPGRADE_BUILD_NOT_ENOUGHT_RESOURCES", $messparams);

		// рефреш с актуализацией соты и ресурсов
		assert($this->refresh(2, "", 1, 1));
	}
}

?>