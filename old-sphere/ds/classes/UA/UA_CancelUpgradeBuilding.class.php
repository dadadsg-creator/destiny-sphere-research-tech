<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отмены апгрейда здания



class UA_CancelUpgradeBuilding extends UA_Building
{

	public $bindeqid;
	public $tobuild;

	function UA_CancelUpgradeBuilding ()
	{
		UA_Building::UA_Building ();
	}

	// проверка параметров
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

	// проверка допустимости действия
	function checkAction()
	{
		global $params;
		global $common;		
		
		// здание апгрейдится
		$sql = 
			"	SELECT
					bcbu.id_to_building,
					ab.ID_AttachedBuilding,
					abbeq.ID_EventQueue
				FROM
					DS_AttachedBuilding AS ab
					INNER JOIN ds_building AS b ON (b.id_building = ab.id_building)
					INNER JOIN ds_buildingcanbeupgraded AS bcbu ON (bcbu.id_from_building = b.id_building)
					INNER JOIN ds_buildingcanbuildbyrace AS bcbbr ON (bcbbr.id_building = bcbu.id_to_building)
					INNER JOIN DS_AttachedBuildingBindEventQueue AS abbeq ON (abbeq.id_attachedbuilding = ab.id_attachedbuilding)
					INNER JOIN DS_EventQueue AS eq ON (eq.ID_EventQueue = abbeq.ID_EventQueue)					
					INNER JOIN DS_Event AS e ON (e.ID_Event = eq.ID_Event)
				WHERE
					eq.isTrash = false
					AND
					ab.inupgrade is true
					AND
					e.EventName = 'UPGRADE_BUILDING'
					AND
					ab.ID_AttachedBuilding = ".$this->attachedbid."
					AND
					ab.id_building = ".$this->bid."
					AND
					ab.id_cell = ".$this->id_cell."
					AND
					bcbbr.id_race = ".$_SESSION['race']."";		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		assert(count($res) == 1);

		assert(key_exists('id_eventqueue', $res[0]));
		$this->bindeqid = (int)$res[0]['id_eventqueue'];
		assert($this->bindeqid > 0);
		
		assert(key_exists('id_to_building', $res[0]));
		$this->tobuild = (int)$res[0]['id_to_building'];
		assert($this->tobuild > 0);

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
		
		ME_CancelUpgradeBuilding::Cancel((int)$this->attachedbid);

		// название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

		//название здания из которого
		$raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);
		$messparams[1] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

		// название здания в которое
		$raceBuilding = DE_Building::CreateFromID((int)$this->tobuild)->getRaceBuilding((int)$params["race"]);
		$messparams[2] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

		//Уменьшаем значение идущих строек
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS', -(float)1);

		// добавляем системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_UPGRADE_BUILDING_MESS", $messparams );

		//Рестартим вид соты	
		CR_CombView::CreateFromCombID((int)$this->comb)->resetBuildings(DE_Cell::CreateFromID($this->id_cell)->getCellTypeID());

		// рефреш с актуализацией соты и ресурсов
		if ( !$this->refresh ( 2, "", 1, 1 ) ) {
			return false;
		}

		return true;
	}
}

?>