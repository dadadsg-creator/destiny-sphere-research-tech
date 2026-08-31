<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отменить тренировку юнитов



class UA_CancelTrainUnit extends UA_Unit 
{
	private $spyUnits = false;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Unit
	 */
	private $unit;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Comb
	 */
	private $combObject;
	
	function __construct() 
	{
		parent::UA_Unit();
		$this->combObject = DE_Comb::CreateFromID((int)$this->comb);
	}

	// проверить параметры
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
	
		// ИД юнита приходит при строительстве шпионских юнитов
		$this->id_unit = $this->root->get_attribute("id");
		if($this->id_unit == '')
		{
			// если ИД юнита не указано, то находим какой тип юнитов можно строить в домике
			$this->id_unit = DS_UnitTrain::GetProducableUnitByIdAttachedBuilding($this->attachedbid);
			$this->unit = DE_Unit::CreateFromID($this->id_unit);
		} else {
			$this->unit = DE_Unit::CreateFromName($this->id_unit);
			$this->id_unit = $this->unit->getID();
			$this->spyUnits = true;
		}

		return true;
	}

	// проверяем допустимость действия
	function checkAction() 
	{		
		$this->bindeqid = DS_UnitTrain::GetUnitTrainEventQueueID($this->unit, $this->combObject);
		$eventQueue = DE_EventQueue::CreateFromID($this->bindeqid);
		$this->counttotrain = $eventQueue->getAttributes()->getAttribute('CountToTrain');

		return true;
	}

	// выполняем команду
	function performAction ()
	{		
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
		
		$los = $this->worldSettings->get('MIN_RES_LOST_ON_CANCEL_UNIT');

		// возвращаем часть ресурсов
		$unitAttributes = DE_UnitAttributes::CreateFromUnitID((int)$this->id_unit);
		
		$attributeNames = $unitAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');
		
		if($this->spyUnits)
		{
			$price = GL_SpyCountZondPrice::Count($this->combObject, $this->unit, (int)$this->counttotrain, -(int)$this->counttotrain);
		}
		
		foreach ($attributeNames as $k=>$attributeName)
		{
			if ( $attributeName == 'CONSTRUCTION_TIME' || $attributeName == 'WORKER')
			{
				continue;
			}
			
			if($this->spyUnits)
			{
				assert(key_exists($attributeName, $price));
				$value = round($price[$attributeName] * $los);
			} else {
				$value = $unitAttributes->getAttribute('PRODUCTION_COSTS', $attributeName);
				$value = round(($value * $this->counttotrain )* $los );
			}

			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, $value);
		}

		// Возвращаем рабочих
		// текущее кол-во рабочих
		$current = $combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER');

		// максимальное кол-во рабочих
		$max = $combAttributes->getAttribute('MAX_AMOUNT', 'WORKER');

		// сколько вернуть
		if($current + $this->counttotrain > $max)
		{
			$ret = $max - $current;
		} else {
			$ret = $this->counttotrain;
		}

		// возвращаем рабочих
		$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'WORKER', (float)$ret);

		//  для шпионов - военный опыт, если надо
		if($this->spyUnits)
		{
			if($this->unit->getName() == 'SPY_ZOND_EXPLORER')
			{
				$battleXPPrice = (float)DE_WorldSettings::CreateForCurentWorld()->get('SPY_ZOND_EXPLORER_BATTLE_XP_COST');
				$battleXP = DE_BattleXP::CreateFromID($this->combObject->getUserInstanceID());
				$battleXP->deltaXP($battleXPPrice * $this->counttotrain * $los);
			}
		}

		// прибиваем эвент
		DE_EventQueue::CreateFromID((int)$this->bindeqid)->setIsTrash(true);	
				

		// название соты
		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

		// название юнита				
		$messparams[2] = "{b} ".DE_Unit::CreateFromID($this->id_unit)->getLocalizedName()."{/b}";

		$messparams[1] = "{b} ".$this->counttotrain."{/b}";

		// системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_TRAIN_UNIT_MESS", $messparams);

		$res = "";
		$sql = "";

		$this->_sendSpyHelper();
		
		// рефреш с актуализацией ресурсов
		assert($this->refresh(2, "", "", 1, true));
		
		return true;
	}
	
	private function _sendSpyHelper()
	{
		if(!$this->spyUnits || !$_SESSION['profi_game'])
		{
			return;
		}
		
		$spyUnits = DS_SpyMenu::GetSpyUnits();
		$combAttributes = $this->combObject->getAttributes();
		$unitmax = GL_SpyMaxCountZondsOnComb::Get($this->combObject, $this->unit);
		
		foreach ($spyUnits as $k => $unit) 
		{
			$unit = DE_Unit::CreateFromID((int)$unit['id_unit']);
			if($unit->getID() == $this->unit->getID())
			{
				$trainingAmount = 0;
			} else {
				$trainingAmount = DS_UnitTrain::GetCountUnitInProduction($unit, $this->combObject);
			}
			$unitmax -= $combAttributes->getAttribute('CURRENT_AMOUNT', $unit->getName()) + $combAttributes->getAttribute('AWAY_AMOUNT', $unit->getName());
			$unitmax -= $trainingAmount;
		}
		
		$maxCountTemp = array();
		$maxCountTemp[] = (int)$unitmax;
		$maxCountTemp[] = (int)GL_SpyCountZondPrice::GetMaxByResource($this->combObject, $this->unit, -$this->counttotrain);
		
		$unitmax = min($maxCountTemp);

		$str  = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
		$str .= '<trainspyok id="'.$this->unit->getName().'" new_max="'.$unitmax.'" inaction="0" new_xp="'.DE_BattleXP::CreateFromID($this->combObject->getUserInstanceID())->getXP().'"/>';

		SU_OutputHandler::Singleton()->addString($str , 'helper');
	}
}

?>