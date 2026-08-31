<?php $CVS_Version = '$Revision$ ; $Date$';

// команда тренировать ученых или археолов


class UA_TrainSpyUnit extends UA_Unit 
{
	public $timetotrainone;
	
	public $isResourceEnought = true;
	
	public $eventQueueID;
	
	function UA_TrainUnit() 
	{
		parent::UA_Unit();
	}	

	// проверяем параметры
	function checkLocalParams() 
	{
		// сколько тренировать
		$this->counttotrain = (int)$this->root->get_attribute ( "count" );
		assert($this->counttotrain > 0);
	
		$this->cellnumber = (int)$this->root->get_attribute("c");
		assert($this->cellnumber > 0);
		
		$this->_getCellInfo();
		
		// ИД юнита приходит при строительстве шпионских юнитов
		$this->id_unit = $this->root->get_attribute("id");
		assert(!empty($this->id_unit) && preg_match('/^[A-Z_]+$/', $this->id_unit));
		
		$this->unit = DE_Unit::CreateFromName($this->id_unit);
		$this->id_unit = $this->unit->getID();
		
		$this->attributeName = $this->unit->getName();
		
		$this->combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		
		$this->unitAttributes = $this->unit->getAttributes();
		
		$this->timetotrainone = (int)($this->unitAttributes->getAttribute('PRODUCTION_COSTS', 'CONSTRUCTION_TIME') * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60);
		
		$this->combObject = DE_Comb::CreateFromID((int)$this->comb);
		
		return true;
	}
	
	private function _getCellInfo()
	{
		$cellData = ME_CombViewGetCellIDAndBuildigIDByCell::Exec($this->cellnumber, true);
		
		$this->id_cell = $cellData['CELL_ID'];
		assert(is_int($this->id_cell) && $this->id_cell > 0);

		$this->groundid = $cellData['GROUND_TYPE_ID'];
		assert(is_int($this->groundid) && $this->groundid > 0);
		
		$this->attachedbid = $cellData['ATTACHED_BUILDING_ID'];
		assert(is_int($this->attachedbid) && $this->attachedbid >= 0);
			
		$this->bid = $cellData['BUILDING_ID'];
		assert(is_int($this->bid) && $this->bid >= 0);
	}

	// проверяем допустимость действия
	function checkAction() 
	{		
		//Проверяем можно ли сторить эти юниты в этом зании
		assert(DS_UnitTrain::IsUnitProducableInBuilding($this->id_unit, $this->bid));
		
		// список шпионских юнитов
		$spyUnits = DS_SpyMenu::GetSpyUnits();
		
		$unitcurrent = 0;
		$unitinprogress = 0;
		$unitaway = 0;
		
		// проверяем текущее кол-во всех шпионских юнитов
		foreach ($spyUnits as $k => $unit) 
		{
			// данные о юните
			$unitID = (int)$unit['id_unit'];
			$unit = DE_Unit::CreateFromID($unitID);
			
			// текущее кол-во этих юнитов
			$unitcurrent += $this->combAttributes->getAttribute('CURRENT_AMOUNT', $unit->getName());		

			// сколько юнитов в процессе тренировки
			$unitinprogress += (int)DS_UnitTrain::GetCountUnitInProduction($unit, $this->combObject);

			// сколько юнитов в пути	
			if($this->combAttributes->haveAttribute('AWAY_AMOUNT', $unit->getName()))
			{
				$unitaway += $this->combAttributes->getAttribute('AWAY_AMOUNT', $unit->getName());
			}
		}
				
		$unitmax = GL_SpyMaxCountZondsOnComb::Get($this->combObject, $this->unit);
		
		$unitall = $unitcurrent + $unitinprogress + $unitaway + $this->counttotrain;
		
		$newMax = $unitmax - ($unitcurrent + $unitinprogress + $unitaway);

		// если текущее + переданное кол-во юнитов больше максимального,
		// уменьшаем кол-во запускающихся в тренировку
		if($unitall > $unitmax)
		{
			$this->counttotrain = (int)$newMax;
			assert($this->counttotrain  >= 0);
		}		
		
		$maxCountTemp = array();
		$maxCountTemp[] = (int)$newMax;
		$maxByRes = (int)GL_SpyCountZondPrice::GetMaxByResource($this->combObject, $this->unit);
		$maxCountTemp[] = $maxByRes;
		$resAllow = min($maxCountTemp);

		if($resAllow == 0) 
		{
/*
$msg = "
unitmax = $unitmax
unitall = $unitall
newMax = $newMax
resAllow = $resAllow
maxByRes = $maxByRes
";
SU_DebugTools::LogAll($msg, __CLASS__, 0);
*/
			$this->isResourceEnought = false;
		}elseif($this->counttotrain > $resAllow){
			$this->counttotrain = $resAllow;
		}
		
		return true;
	}
	
	// выполняем действие
	function performAction() 
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
		
		if(!$this->isResourceEnought)
		{
			$this->processNotEnoughtResources();
			return true;
		}
		
		// шпионаж отключен
		if(!DE_WorldSettings::CreateForCurentWorld()->get('SPY_ENABLED'))
		{
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'SPY_DISABLED', array());
			return true;
		}
		
		assert($this->counttotrain > 0);

		// время окончания эвента
		if(!$this->getEventEndTime()) 
		{
			return false;
		}
		
		// текущий евент строительства юнитов, если есть
		$this->eventQueueID = DS_UnitTrain::GetUnitTrainEventQueueID($this->unit, $this->combObject);

		if($this->eventQueueID == 0) 
		{	
			$msg = "START_TRAIN_UNIT_MESS";	
			// создаем новый евент
			$this->_createEvent();
		} else { 
			$msg = "ADD_TRAIN_UNIT_MESS";
			// апдейтим существующий эвент
			$this->_updateEvent();			
		}
		
		// вычитаем ресурсы			
		$this->_getResourceForProduction();
		
		// название соты
		$messparams[0] = "{b} ".$this->combObject->getName()."{/b}";

		// сколько тренируем
		$messparams[1] = "{b} ".$this->counttotrain."{/b}";
		
		// название юнита
		$messparams[2] = "{b} ".$this->unit->getUnitNameLocolized()."{/b}";
		
		// системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, $msg, $messparams );
				
		// рефреш с актуализацией ресурсов
		if(!$this->refresh(2, "", "", 1, true)) 
		{
			return false;
		}
		
		$this->_sendHelper();

		return true;
	} 

	/**
	 * Хелпер только для шпионских юнитов
	 *
	 */
	private function _sendHelper()
	{
		if(!$_SESSION['profi_game'])
		{
			return;
		}
		
		$spyUnits = DS_SpyMenu::GetSpyUnits();
		$combAttributes = $this->combObject->getAttributes();
		
		// максимум юнитов - общий, поэтому смотрим сколько всего есть "шпионов"
		$comb = DE_Comb::CreateFromID($this->comb);
		$curAmountTotal = 0;

		foreach ($spyUnits as $k => $unit) 
		{
			// данные о юните
			$unitID = (int)$unit['id_unit'];
			$unit = DE_Unit::CreateFromID($unitID);
			if($unit->getID() == $this->unit->getID())
			{
				$trainingAmount = DE_EventQueue::CreateFromID($this->eventQueueID)->getAttributes()->getAttribute('CountToTrain');;
			} else {
				$trainingAmount = DS_UnitTrain::GetCountUnitInProduction($unit, $this->combObject);
			}
			$curAmountTotal += $combAttributes->getAttribute('CURRENT_AMOUNT', $unit->getName()) + $combAttributes->getAttribute('AWAY_AMOUNT', $unit->getName());
			$curAmountTotal += $trainingAmount;
		}		

		$str  = '<?xml version="1.0" encoding="utf-8" ?>'."\n<trainspyok>";
		
		foreach ($spyUnits as $k => $unit)
		{
			$unit = DE_Unit::CreateFromID((int)$unit['id_unit']);
			$unitmax = GL_SpyMaxCountZondsOnComb::Get($this->combObject, $unit);
			
			if($unit->getID() == $this->unit->getID())
			{
				$trainingAmount = DE_EventQueue::CreateFromID($this->eventQueueID)->getAttributes()->getAttribute('CountToTrain');
			} else {
				$trainingAmount = DS_UnitTrain::GetCountUnitInProduction($unit, $this->combObject);
			}
		
			$unitmax -= $curAmountTotal;

			$maxCountTemp = array();
			$maxCountTemp[] = $unitmax;
			$maxCountTemp[] = GL_SpyCountZondPrice::GetMaxByResource($this->combObject, $unit, $this->counttotrain);
			$unitmax = min($maxCountTemp);		

			$awail = 1;
			if ($unitmax == 0) {
				// Доступность тренировки хотябы одного занда
				$awail = 0;
				// Тут ставим 1, чтобы можно было посмотреть, сколько ресов не хватает для тренировки одного зонда
				$unitmax = 1;
			}

			$str .= '<zond id="'.$unit->getName().'" new_max="'.$unitmax.'"  awail="'.$awail.'" inaction="'.$trainingAmount.'" new_xp="'.DE_BattleXP::CreateFromID($this->combObject->getUserInstanceID())->getXP().'"/>';
		}
		
		$str .= '</trainspyok>';
			
		SU_OutputHandler::Singleton()->addString($str , 'helper');
		
	}
	
	private function _createEvent()
	{
		// создаем новый эвент
		$this->createEvent('TRAIN_UNIT');
		
		DE_EventQueue::CreateFromID($this->eqid)->attachToComb($this->combObject);
		
		$this->eventQueueID = $this->eqid;
	}
	
	private function _updateEvent()
	{
		// апдейтим параметры эвента
		$eventOptions = DE_EventQueueAttributes::CreateFromEventID($this->eventQueueID);
		$eventOptions->deltaAttribute('CountToTrain', $this->counttotrain);			
			
		//Изменяем время окончания ивента
		$event = DE_EventQueue::CreateFromID($this->eventQueueID);
		$endDate = $event->getEndDate();
			
		$endDate->value = $endDate->value + $this->timetotrainone * $this->counttotrain;

		$event->setEndDate($endDate);
	}
	
	private function _getResourceForProduction()
	{
		// снимаем ресурсы
		$price = GL_SpyCountZondPrice::Count($this->combObject, $this->unit, $this->counttotrain);
		foreach($price as $resourceName => $value)
		{
			$this->combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -$value);						
		}
		
		// снимаем военный опыт
		if($this->unit->getName() == 'SPY_ZOND_EXPLORER')
		{
			$battleXPPrice = (float)DE_WorldSettings::CreateForCurentWorld()->get('SPY_ZOND_EXPLORER_BATTLE_XP_COST');
			$battleXP = DE_BattleXP::CreateFromID($this->combObject->getUserInstanceID());
			$battleXP->minusXP($battleXPPrice * $this->counttotrain);
		}
	}
	
	// время окончания эвента
	function getEventEndTime() 
	{
		$startDate = GB_TypeFactory::CreateBase('GB_Date', time());		
		$this->starttime = $startDate->getPgFormat();
		
		$startDate->value += (int)($this->timetotrainone * $this->counttotrain);
		$this->endtime = $startDate->getPgFormat();  
		
		return true;
	}

	function createEventParams() 
	{
		$this->eventparams["CountToTrain"] = $this->counttotrain;				
		$this->eventparams["ID_Comb"] = $this->comb;		
		$this->eventparams["ID_Unit"] = $this->id_unit;		
		$this->eventparams["TrainedCountAccumulator"] = 0;		

		return true;
	}
	
	function processNotEnoughtResources()
	{		
		$messparams = array();
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "TRAIN_UNIT_NOT_ENOUGHT_RESOURCES", $messparams);

		// рефреш с актуализацией соты и ресурсов
		assert($this->refresh(2, "", 1, 1));	
	}
}

?>