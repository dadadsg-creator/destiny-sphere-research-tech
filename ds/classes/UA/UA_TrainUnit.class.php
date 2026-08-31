<?php $CVS_Version = '$Revision$ ; $Date$';

// команда тренировать ученых или археолов


class UA_TrainUnit extends UA_Unit 
{
	public $timetotrainone;
	
	public $isResourceEnought = true;
	
	public $eventQueueID;
	
	private $spyUnits = false;
	
	private $newMax;
	
	function UA_TrainUnit() 
	{
		parent::UA_Unit();
	}	

	// проверяем параметры
	function checkLocalParams() 
	{
		// сколько тренировать
		$this->counttotrain = (int)$this->root->get_attribute ( "count" );
	
		$this->cellnumber = (int)$this->root->get_attribute("c");
		assert($this->cellnumber > 0);
		
		$this->_getCellInfo();
		
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
		if($this->counttotrain == "" || $this->counttotrain <= 0)
		{
			return true;
		}

		//Проверяем можно ли сторить эти юниты в этом зании
		assert(DS_UnitTrain::IsUnitProducableInBuilding($this->id_unit, $this->bid));
		
		// текущее кол-во этих юнитов
		$unitcurrent = $this->combAttributes->getAttribute('CURRENT_AMOUNT', $this->attributeName);		

		// сколько юнитов в процессе тренировки
		$unitinprogress = (int)DS_UnitTrain::GetCountUnitInProduction($this->unit, $this->combObject);

		// сколько юнитов в пути	
		if($this->combAttributes->haveAttribute('AWAY_AMOUNT', $this->attributeName))
		{
			$unitaway = $this->combAttributes->getAttribute('AWAY_AMOUNT', $this->attributeName);
		}else{
			$unitaway = 0;
		}	
		
		$maxCountTemp = array();
		
		// максимальное кол-во юнитов	
		if($this->combAttributes->haveAttribute('MAX_AMOUNT', $this->attributeName))
		{	
			$unitmax = $this->combAttributes->getAttribute('MAX_AMOUNT', $this->attributeName);
		} else {
			// у шпионских юнитов максимальное кол-во от модифаера зависит
			$unitmax = GL_SpyMaxCountZondsOnComb::Get($this->combObject, $this->unit);
		}
		
		$unitall = $unitcurrent + $unitinprogress + $unitaway + $this->counttotrain;
		
		$this->newMax = $unitmax - ($unitcurrent + $unitinprogress + $unitaway);

		// если текущее + переданное кол-во юнитов больше максимального,
		// уменьшаем кол-во запускающихся в тренировку
		if($unitall > $unitmax)
		{
			$this->counttotrain = $this->newMax;
			assert($this->counttotrain  >= 0);
		}		
		
		$maxCountTemp[] = $this->newMax;
		
		// на сколько хватает ресурсов		
		foreach(GB_ResourcesList::$allBuildResources as $k => $resourceName)
		{
			if(!$this->unitAttributes->haveAttribute('PRODUCTION_COSTS', $resourceName))
			{
				continue;
			}
			
			$prodValue = $this->unitAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);
			if($prodValue == 0)
			{
				continue;
			}			
			
			$maxCount = floor($this->combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName) / $prodValue);	
			
			$maxCountTemp[] = $maxCount;
		}
		
		$resAllow = min($maxCountTemp);
		
		$this->newMax = $resAllow;

		if($resAllow == 0) 
		{
			$this->isResourceEnought = false;
			$this->newMax = 0;
		}elseif($this->counttotrain > $resAllow){
			$this->counttotrain = $resAllow;
			$this->newMax = 0;
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
		
		if($this->counttotrain == "" || $this->counttotrain <= 0) 
		{
			if(!$this->refresh( 2, "", "", "", true)) 
			{
				return false;
			}

			return true;
		}

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
		if(!$this->spyUnits || !$_SESSION['profi_game'])
		{
			return;
		}
		
		$countToTrain = DE_EventQueue::CreateFromID($this->eventQueueID)->getAttributes()->getAttribute('CountToTrain');
		
		$str  = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
		$str .= '<trainspyok id="'.$this->unit->getName().'" new_max="'.$this->newMax.'" inaction="'.$countToTrain.'"/>';
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
		if($this->spyUnits)
		{
			$price = GL_SpyCountZondPrice::Count($this->combObject, $this->unit);
		}
		
		foreach(GB_ResourcesList::$allBuildResources as $k=>$resourceName)
		{
			if(!$this->unitAttributes->haveAttribute('PRODUCTION_COSTS', $resourceName))
			{
				continue;
			}	
			
			if($this->spyUnits)
			{
				assert(key_exists($resourceName, $price));
				$delta = -(float)$price[$resourceName];
			} else {
				$prodValue = $this->unitAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);			
				$delta = -(float)($this->unitAttributes->getAttribute('PRODUCTION_COSTS', $resourceName) * $this->counttotrain); 
			}
			
			$this->combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, $delta);						
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