<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отмены тренировки военных юнитов



class UA_CancelTrainWarUnit extends UA_Unit {

	function UA_CancelTrainWarUnit () {
		UA_Unit::UA_Unit ();
	}

	// проверяем параметры
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

		global $params;
		global $common;

		// какие юниты тренируются
		$sql = sprintf ( "select id_unit, 
				amount
				from ds_unitinproduction
				where id_attachedbuilding = %d",
				$this->attachedbid );

		$res = $common->getResultArray ( "id_unit_amount_id_attachedbuilding_".$this->attachedbid, $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_CancelTrainWarUnit::checkAction1";
			
			return false;
		}

		$this->id_unit = $res[0]['id_unit'];
		$this->counttotrain = $res[0]['amount'];

		// получаем эвент
		$sql = sprintf ( "select abbeq.id_eventqueue
				from ds_attachedbuildingbindeventqueue abbeq,
				ds_eventqueue eq,
				ds_event e
				where abbeq.id_eventqueue = eq.id_eventqueue
				and eq.id_event = e.id_event
				and e.eventname = 'TRAIN_WAR_UNIT'
				and abbeq.id_attachedbuilding = %d
				and eq.istrash = false",
				$this->attachedbid );

		$res = $common->getResultArray ( "id_eventqueue_id_attachedbuilding_".$this->attachedbid, $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_CancelTrainWarUnit::checkAction2";
			
			return false;
		}

		$this->bindeqid = $res[0]['id_eventqueue'];

		return true;
	}

	// выполняем команду
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
		
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		
		$common->clearLocalCache ( $params["uiid"] );
			
		$los = $this->worldSettings->get('MIN_RES_LOST_ON_CANCEL_UNIT');

		// возвращаем часть ресурсов
		$unitAttributes = DE_UnitAttributes::CreateFromUnitID((int)$this->id_unit);
		
		$attributeNames = $unitAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');
		
		foreach ($attributeNames as $k=>$attributeName)
		{
			if ( $attributeName == 'CONSTRUCTION_TIME' || $attributeName == 'WORKER')
			{
				continue;
			}
			$value = $unitAttributes->getAttribute('PRODUCTION_COSTS', $attributeName);
			$value = round(($value * $this->counttotrain )* $los );
			
			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName,$value);
		}

		// сколько было потрачено рабочих
		$workers = $this->counttotrain * $unitAttributes->getAttribute('PRODUCTION_COSTS', 'WORKER');

		// текущее кол-во рабочих
		$current = $combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER');

		// максимальное кол-во рабочих
		$max = $combAttributes->getAttribute('MAX_AMOUNT', 'WORKER');

		if ( $current + $workers > $max )
		{
			$ret = $max - $current;
		} else {
			$ret = $workers;
		}

		// возвращаем рабочих
		$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'WORKER',$ret);
		
		// прибиваем производство
		$sql = sprintf ( "delete from ds_unitinproduction
				where id_attachedbuilding = %d",
				$this->attachedbid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR UA_CancelTrainWarUnit::performAction6";
			
			return false;
		}

		// прибиваем эвент
		DE_EventQueue::CreateFromID((int)$this->bindeqid)->setIsTrash(true);			

		// отцепляем евент от здания
		$sql = "
			DELETE FROM 
				ds_attachedbuildingbindeventqueue
			WHERE 
				id_attachedbuilding = ".$this->attachedbid;
		$res = $common->executeQuery($sql);
		if($res === false)
		{
			$this->errormsg .= " SYSTEM ERROR UA_CancelTrainWarUnit::performAction8";			
			return false;
		}
		
		// название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

		// название юнита
		$sql = sprintf ( "select ltl.text as unitname
				from ds_unit un,
				ds_localizedtextlanguage ltl
				where un.id_unit = %d
				and un.id_localizedname = ltl.id_localizedtext
				and ltl.id_language = %d",
				$this->id_unit,
				$params["lang"] );

		$res = $common->getResultArray ( "unitname_id_unit_id_language_".$this->id_unit."_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_CancelTrainWarUnit::performAction10";
			
			return false;
		}

		$messparams[2] = "{b} ".$res[0]['unitname']."{/b}";

		$messparams[1] = "{b} ".$this->counttotrain."{/b}";

		// системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_TRAIN_UNIT_MESS", $messparams, 0, 'GREEN' );

		$res = "";
		$sql = "";
		
		//if ( $_SESSION['profi_game'] ){
			$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}
		
		// рефреш с актуализацией соты
		if ( !$this->refresh ( 2, "", "", 1, $noCellData ) ) {
			return false;
		}

		return true;
	}
}

?>