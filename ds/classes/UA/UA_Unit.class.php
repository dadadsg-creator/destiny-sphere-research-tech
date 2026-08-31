<?php $CVS_Version = '$Revision$ ; $Date$';

// общие функции для классов работающий с юнитами


class UA_Unit extends UA_Action {

	public $bid;
	public $attachedbid;
	public $counttotrain;
	public $timetotrainone;
	public $id_unit;
	public $unitname;
	public $id_attributename;
	public $bindeqid;
	public $id_cell;

	function UA_Unit () {
		UA_Action::UA_Action ();
	}

	// параметры эвента
	function createEventParams () {

		global $params;

		$this->eventparams["CountToTrain"] = $this->counttotrain;
		$this->eventparams["ID_UserInstance"] = $params["uiid"];
		$this->eventparams["ID_Language"] = $params["lang"];
		$this->eventparams["ID_Comb"] = $this->comb;
		$this->eventparams["TimeToTrainOne"] = $this->timetotrainone;
		$this->eventparams["ID_Unit"] = $this->id_unit;
		$this->eventparams["AddToTrain"] = 0;

		return true;
	}

	// id аттрибута по названию юнита
	function getAttributeNameID ( $id_unit ) {

		global $common;

		$sql = sprintf ( "select atn.id_attributename
				from ds_attributename atn
				where upper(attributename) = 
				(select upper(unitname)
				from ds_unit where id_unit = %d)",
				$id_unit );

		$res = $common->getResultArray ( "id_attributename_id_unit_".$id_unit, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_Unit::getUnitID2-1";
			
			return false;
		}

		$this->id_attributename = $res[0]['id_attributename'];

		return true;
	}
}

?>