<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отмена строительства
class UA_CancelBuilding extends UA_Building {

	public $bindeqid;

	function UA_CancelBuilding ()
	{
		UA_Building::UA_Building ();
	}

	// провер€ем параметры
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

	// провер€ем допустимость действи€
	function checkAction()
	{

		global $params;
		global $common;

		// здание строитс€
		$sql = sprintf ( "select underconstruction
				from ds_attachedbuilding
				where id_attachedbuilding = %d
				and id_building = %d
				and id_cell = %d",
				$this->attachedbid,
				$this->bid,
				$this->id_cell );

		$res = $common->getResultArray ( "is_undercontruction_id_attachedbuilding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		assert(empty($common->errormsg));

		if ( $res[0]['underconstruction'] == 'f' )
		{
			$this->errormsg .= " SYSTEM ERROR UA_CancelBuilding::checkAction2";
			return false;
		}

		return true;
	}

	// выполн€ем действие
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
		
		ME_CancelBuilding::Cancel($this->attachedbid);

		// название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

		// название здани€
		$raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);
		$messparams[1] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

		// ”меньшаем значение идущих строек
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS', -(float)1);

		// системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_BUILDING_MESS", $messparams );
		
		//–естартим вид соты	
		CR_CombView::CreateFromCombID((int)$this->comb)->resetBuildings(DE_Cell::CreateFromID($this->id_cell)->getCellTypeID());
		
		// рефреш с актуализацией соты и ресурсов
		if ( !$this->refresh ( 2, "", 1, 1 ) )
		{
			return false;
		}

		return true;
	}
}

?>