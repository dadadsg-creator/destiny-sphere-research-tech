<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_Terraforming extends UA_Action
{

	private $cellID;
	
	private $groundTypeToName;	
			
	private $rettraformingAttributes;
	
	private $terraformingID;
	
	private $attachedBuildingID;
	
	function __construct()
	{
		UA_Action::UA_Action();
	}

	// проверяем параметры
	function checkLocalParams()
	{
		$this->cellnumber = (int)$this->root->get_attribute("c");
		assert($this->cellnumber > 0);
		
		$cellData = ME_CombViewGetCellIDAndBuildigIDByCell::Exec($this->cellnumber, true);
		
		$this->cellID = $cellData['CELL_ID'];
		assert(is_int($this->cellID) && $this->cellID > 0);
		
		$this->attachedBuildingID = $cellData['ATTACHED_BUILDING_ID'];
		assert(is_int($this->attachedBuildingID) && $this->attachedBuildingID >= 0);
		
		//В какой тип почви трансофрмируем
		$this->groundTypeToName = $this->root->get_attribute("type");
		assert(!empty($this->groundTypeToName));
		
		return true;
	}

	// проверяем допустимость действия
	function checkAction ()
	{		
		//Проверяем что это ячейка та на которой стоит здание и что это сота данного инстанса
		$groundTypeIDFrom = DS_Terraforming::ValidateCellIDInAction($this->cellID, $this->comb);		
		assert($groundTypeIDFrom > 0);
		
		//Првоеряем можем ли мы трансформировать в это ттип почвы
		$this->terraformingID = DS_Terraforming::ValidateTerraformingType($groundTypeIDFrom, $this->groundTypeToName, $_SESSION['uiid']);
		assert($this->terraformingID > 0);
		
		$this->terraformingAttributes = DE_TerraformingAttributes::CreateFromTerraformingID($this->terraformingID);
		return true;
	}

	// выполняем действие
	function performAction ()
	{

		global $params;

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
		
		//Вычетаем ресусры за терраформинг
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		
		$attributeNames = $this->terraformingAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');						
		foreach ($attributeNames as $k => $resourceName)
		{
			if (!$combAttributes->haveAttribute('CURRENT_AMOUNT', $resourceName))
			{
				continue;
			}
			
			$needResourceCount = $this->terraformingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);
			$haveResourceCount = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);
			
			if( $needResourceCount <= $haveResourceCount)
			{
				$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -$needResourceCount);
			}else{
				ds_error("Not enough resources for terraforming this cell. RESOURCE: $resourceName NEED: $needResourceCount HAVE: $haveResourceCount");
			}
		}		
		
		
		//Изменяем значение на целе типа грунта
		DS_Terraforming::SetNewGroundTypeOnCellByTerraformingID($this->terraformingID, $this->cellID);
		
		//Рестартим кэш вида соты
		CR_CombView::CreateFromCombID((int)$this->comb)->resetCells(DE_Cell::CreateFromID($this->cellID)->getCellTypeID());
		
		//Расчитываем сколько оно должно идти
		$workTime = (int)($this->terraformingAttributes->getAttribute('PRODUCTION_COSTS', 'CONSTRUCTION_TIME') * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60);
		assert($workTime > 0);
		
		$startDate = GB_TypeFactory::CreateBase('GB_Date', time());
		$endDate = GB_TypeFactory::CreateBase('GB_Date', time() + $workTime);
		
		$this->starttime = $startDate->getPgFormat();
		$this->endtime = $endDate->getPgFormat();
				
		//Создаем ивент
		$this->createEvent('TERRAFORMING');
		
		//Биндем ивент		
		DS_Terraforming::BindEventQueue($this->cellID, $this->eqid);
		
		//Пишем мессагу
		$messparams = array();
		DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TERRAFORMING_START', $messparams, 0);
		
		//Запрещаем удаление здания
		DS_Building::DeleteAttachedBuildingAttribute($this->attachedBuildingID, 'CURRENT_FLAG', 'DESTROYABLE');
		
		// рефреш с актуализацией ресурсов
		assert($this->refresh(2, "", "", 1, 0));
				
		return true;
	}
	
	function createEventParams () 
	{

		global $params;

		$this->eventparams["ID_Cell"] = $this->cellID;
		$this->eventparams["ID_Comb"] = $this->comb;	
		$this->eventparams["ID_Terraforming"] = $this->terraformingID;			

		return true;
	}
}

?>