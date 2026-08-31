<?php $CVS_Version = '$Revision$ ; $Date$';

// команда деактивировать здание
class UA_DeactivateBuilding extends UA_Building
{

	public $todeact;
	public $destr_flag;

	function UA_DeactivateBuilding () {
		UA_Building::UA_Building ();
	}

	// проверяем параметры
	function checkLocalParams ()
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
	function checkAction ()
	{
		global $params;
		global $common;

		// можно ли деактивировать здание
		$sql = sprintf ( "select 1 as deact
				from ds_attachedbuilding ab,
				ds_attachedbuildinghasattribute atbha,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where ab.id_attachedbuilding = atbha.id_attachedbuilding
				and atbha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'CURRENT_FLAG'
				and atn.attributename = 'DEACTIVEABLE'
				and ab.id_attachedbuilding = %d",
		$this->attachedbid );

		$res = $common->getResultArray ( "is_deactiveable_id_attachedbuilding_".$this->attachedbid, $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::checkAction1";

			return false;
		}

		// здание активно, не активируется, не строится, не апгрейдится
		$sql = sprintf ( "select id_attachedbuilding
				from ds_attachedbuilding
				where id_attachedbuilding = %d
				and active = true
				and inactivation = false
				and underconstruction = false
				and inupgrade = false
				and id_building = %d
				and id_cell = %d",
		$this->attachedbid,
		$this->bid,
		$this->id_cell );

		$res = $common->getResultArray ( "is_active_id_attachedbuilding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::checkAction2";

			return false;
		}

		return true;
	}

	// выполняем действие
	function performAction ()
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

		$this->destr_flag = 0;

		// моментально деактивируется здание или нет
		$sql = sprintf ( "select bha.value
				from ds_buildinghasattribute bha,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where bha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and att.id_attributetype = a.id_attributetype
				and atn.attributename = 'LONG_DEACTIVATION'
				and att.typename = 'PERMANENT_FLAG'
				and bha.id_building = %d",
		$this->bid );

		$res = $common->getResultArray ( "value_long_deactivation_permanent_flag_id_building_".$this->bid, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::performAction1";

			return false;
		}

		if ( sizeof ( $res ) > 0 )
		{ // если не моментально - создаем эвент

			if ( !$this->getEventEndTime () ) {
				return false;
			}

			// название соты
			$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

			// название здания
			$raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);
			$messparams[1] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

			// указаваем что началать деактивация
			$sql = sprintf ( "update only DS_AttachedBuilding
					set inactivation = true
					where ID_AttachedBuilding = %d",
			$this->attachedbid );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::performAction4";

				return false;
			}

			// указываем время начала и окончания
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
			$this->createEvent('DEACTIVATE_BUILDING');

			$sql = sprintf ( "insert into DS_AttachedBuildingBindEventQueue
					(ID_AttachedBuilding, ID_EventQueue)
					values (%d, %d)",
			$this->attachedbid,
			$this->eqid );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::performAction7";

				return false;
			}

			// системное сообщение
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "START_DEACTIVATE_BUILDING_MESS", $messparams, null, null, (int)$this->uiid);

			$res = "";
			$sql = "";

		} else {
			// моментальная деактивация

			// если это не деактивация зависимых зданий при сносе здания
			// вносим в список само это здание
			if ( $this->destr_flag != 1 )
			{
				$this->todeact[$this->attachedbid] = $this->bid;
			}

			// заносим в список все зависимые здания
			if ( !$this->getBuildingToDeact ( $this->bid, $this->comb ) )
			{
				return false;
			}
			// деактивируем все здания из списка
			if ( !$this->deactivate ( $this->todeact, $this->comb, $params["uiid"], $params["lang"] ) )
			{
				return false;
			}
		}

		$this->todeact = array();


		//Рестартим вид соты
		CR_CombView::CreateFromCombID((int)$this->comb)->resetBuildings(DE_Cell::CreateFromID($this->id_cell)->getCellTypeID());

		// рефреш с актуализацией соты и ресурсов
		if ( !$this->refresh ( 2, "", 1, 1 ) ) {
			return false;
		}

		return true;
	}

	// возвращает список зависимых зданий, которые можно деактивировать
	function getBuildingToDeact ( $id_building, $id_comb ) {

		global $common;

		$sql = sprintf ( "select id_attachedbuilding
				from ds_attachedbuilding ab
				where ab.id_comb = %d
				and ab.id_building = %d
				and ab.active = true
				and inupgrade = false",
		$id_comb,
		$id_building );

		$res = $common->getResultArray ( "is_only_one_building_id_comb_id_building".$id_comb."_".$id_building, $sql, "", "", "" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::getBuildingToDeact1";

			return false;
		}

		if ( sizeof ( $res ) == 0 ) return true;
		if ( isset ( $this->todeact[$res[0]['id_attachedbuilding']] ) ) return true;

		/*if ( sizeof ( $res ) == 1 ) {

		$sql = sprintf ( "select bnb.id_building_1 as id_building,
		ab.id_attachedbuilding
		from ds_buildingneedsbuilding bnb,
		ds_attachedbuilding ab
		where bnb.id_building_1 = ab.id_building
		and bnb.id_building_2 = %d
		and ab.id_comb = %d
		and ab.active = true
		and ab.busy = false
		and ab.inupgrade = false
		and ab.inactivation = false
		and ab.underconstruction = false
		order by ab.id_attachedbuilding desc",
		$id_building, $id_comb );

		$res = $common->getResultArray ( "buildings_to_deactvate", $sql, "", "", "" );
		if ( $res === false ) {
		$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::getBuildingToDeact2";

		return false;
		}

		for ( $i=0; $i<sizeof ( $res ); $i++ )
		{

		$sql = sprintf ( "select 1 as deact
		from ds_attachedbuilding ab,
		ds_attachedbuildinghasattribute atbha,
		ds_attribute a,
		ds_attributename atn,
		ds_attributetype att
		where ab.id_attachedbuilding = atbha.id_attachedbuilding
		and atbha.id_attribute = a.id_attribute
		and a.id_attributename = atn.id_attributename
		and a.id_attributetype = att.id_attributetype
		and att.typename = 'CURRENT_FLAG'
		and atn.attributename = 'DEACTIVEABLE'
		and ab.id_attachedbuilding = %d",
		$res[$i]['id_attachedbuilding'] );

		$res2 = $common->getResultArray ( "is_deactiveable_id_attachedbuilding_".$res[$i]['id_attachedbuilding'], $sql, "", "", "GLOBAL_CACHE" );
		if ( $res2 === false ) {
		$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::getBuildingToDeact3";

		return false;
		}

		if ( @$res2[0]['deact'] != 1 ) continue;

		$this->todeact[$res[$i]['id_attachedbuilding']] = $res[$i]['id_building'];

		if ( !$this->getBuildingToDeact ( $res[$i]['id_building'], $id_comb ) )
		{
		return false;
		}
		}
		}*/

		return true;
	}

	// деактивирует здания из списка
	function deactivate ( $todeact, $id_comb, $id_userinstance, $id_language )
	{
		global $common;

		// если список пустой - выходим
		if ( sizeof ( $todeact ) == 0 )
		{
			return true;
		}

		$comb = DE_Comb::CreateFromID((int)$id_comb);
		$combAttributes = $comb->getAttributes();


		$userInstance = DE_UserInstance::CreateFromID((int)$id_userinstance);
		$id_race = $userInstance->getRaceID();
		$id_world = $userInstance->getRaceID();

		$messparams[0] = "{b} ".$comb->getName()."{/b}";

		// по списку
		foreach ( $todeact as $id_attachedbuilding => $id_building )
		{

			// здание активно и не апгрейдится
			$sql = sprintf ( "select id_attachedbuilding
					from ds_attachedbuilding
					where id_attachedbuilding = %d
					and active = true
					and inupgrade = false",
			$id_attachedbuilding );

			$res = $common->getResultArray ( "is_active_id_attachedbuilding_".$id_attachedbuilding, $sql, "", "", "" );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::deactivate1";

				return false;
			}

			// если нет - идем дальше
			if ( sizeof ( $res ) == 0 )
			{
				continue;
			}

			$building = DE_Building::CreateFromID((int)$id_building);
			$buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$id_building);

			// название здания
			$raceBuilding = DE_Building::CreateFromID((int)$id_building)->getRaceBuilding((int)$id_race);
			$messparams[1] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

			// системное сообщение
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "DEACTIVATE_BUILDING_MESS1", $messparams, null, null, (int)$id_userinstance);

			// деактивируем здание
			$sql = sprintf ( "update only DS_AttachedBuilding
					set active = false
					where ID_AttachedBuilding = %d",
			$id_attachedbuilding );

			$res = $common->executeQuery ( $sql );
			if ( $res === false )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::deactivate5";

				return false;
			}
			
			//уменьшаем upkeep/production соты
			ME_AddBuildingAttributesToComb::AddAttributes($buildingAttributes, $combAttributes, 'minus');

			//Убираем арты всталенные из осчетоы.
			ME_AddBuildingAttributesToComb::AddArtefactAttributes($id_attachedbuilding, $combAttributes, 'minus');

			// отмена тренировки юнитов
			$unitInProduction = DE_UnitInProduction::CreateFromID((int)$id_attachedbuilding);

			if(!$unitInProduction->isEmpty())
			{ // юниты производятся

				$los = $this->worldSettings->get('MIN_RES_LOST_ON_CANCEL_UNIT');

				$unitAttributes = DE_UnitAttributes::CreateFromUnitID($unitInProduction->getUnitID());

				$attributeNames = $unitAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');

				foreach($attributeNames as $k=>$attributeName)
				{
					switch($attributeName)
					{
						case 'CONSTRUCTION_TIME':
							continue;
							break;
						case 'WORKER':
							$currentWorkers = $combAttributes->getAttribute('CURRENT_AMOUNT', $attributeName);
							$maxWorkers = $combAttributes->getAttribute('MAX_AMOUNT', $attributeName);
							$delta = (float)round($unitAttributes->getAttribute('PRODUCTION_COSTS', $attributeName));

							if(($delta + $currentWorkers)>$maxWorkers)
							{
								$delta = $maxWorkers - $currentWorkers;
							}
							break;
						default:
							$delta = (float)round($unitAttributes->getAttribute('PRODUCTION_COSTS', $attributeName) * $unitInProduction->getAmount() * $los);
					}

					if($combAttributes->haveAttribute('CURRENT_AMOUNT', $attributeName))
					{
						$combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, $delta);
					}
				}

				$unitInProduction->delete();

				// название соты
				$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";
				$messparams[1] = "{b} ".$unitInProduction->getAmount()."{/b}";
				$messparams[2] = "{b} ".$unitInProduction->getUnit()->getLocalizedName()."{/b}";

				// Пишем системное сообщение
				// На соте % строительство % % отменено.
				DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_TRAIN_UNIT_MESS", $messparams, 0, 'GREEN',  (int)DE_Comb::CreateFromID((int)$this->comb)->getUserInstanceID());
			}

			// прибиваем эвент
			$sql = "
				SELECT id_eventqueue 
				FROM ds_attachedbuildingbindeventqueue
				WHERE id_attachedbuilding = ".$id_attachedbuilding;
			$res = $common->getResultArray("", $sql);
			assert(empty($common->errormsg));

			if(count($res) > 0)
			{
				$event = DE_EventQueue::CreateFromID((int)$res[0]['id_eventqueue']);
				$event->setIsTrash(true);
			}
		}

		return true;
	}

	// время окончания эвента
	function getEventEndTime ()
	{
		global $common;
		global $params;

		$sql = sprintf( "select now() as starttime,
				now()+
				(select (text(bha.value*((".$this->worldSettings->get('TURN_TIME')."*60)/
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
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_DeactivateBuilding::getEventEndTime1";

			return false;
		}

		$this->endtime = $res[0]['endtime'];
		$this->starttime = $res[0]['starttime'];

		$res = "";

		return true;
	}
}

?>