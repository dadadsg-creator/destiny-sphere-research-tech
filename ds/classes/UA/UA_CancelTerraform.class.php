<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отмены апгрейда здания



class UA_CancelTerraform extends UA_Terraforming
{

	public $bindeqid;
	public $tobuild;

	function UA_CancelTerraform ()
	{
		UA_Action::UA_Action();
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
		
		$this->bindeqid = DS_Terraforming::HaveTerraformingOnCell($this->id_cell);
		assert($this->bindeqid > 0);

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
		
		ME_CancelTerraform::Cancel((int)$this->id_cell);

		// название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

		// добавляем системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_TERRAFORMING", $messparams );
		
		//Рестартим вид соты	
		CR_CombView::CreateFromCombID((int)$this->comb)->resetCells(DE_Cell::CreateFromID($this->id_cell)->getCellTypeID());

		// рефреш с актуализацией соты и ресурсов
		assert($this->refresh(2, "", "", 1, 0));

		return true;
	}
}

?>