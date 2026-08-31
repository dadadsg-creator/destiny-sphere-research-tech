<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2009/07/06 12:24:50 $';

// обработчик тренировки юнитов (ученых и археологов)
class EH_TrainUnit extends EH_Event 
{

	public $checktime;

	function EH_TrainUnit () {
		EH_Event::EH_Event ();
	}

	function handleEvent($eqid) 
	{
		$this->eqid = $eqid;
		
		// если внешний рефреш
		if (!$this->checktime) $this->checktime = 'now()';

		// получаем параметры эвента
		if(!$this->getEventParams()) 
		{
			return false;
		}
		
		$unit = DE_Unit::CreateFromID((int)$this->eventparams["ID_Unit"]);
		$unitName = $unit->getName();
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->eventparams["ID_Comb"]);	
		$comb = DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"]);
				
		$timetoTrainOne = (int)ceil(($unit->getAttributes()->getAttribute('PRODUCTION_COSTS', 'CONSTRUCTION_TIME') * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60));
		assert($timetoTrainOne > 0);
		
		$messparams[0] = "{b} ".$comb->getName()."{/b}";
		$messparams[2] = "{b} ".$unit->getUnitNameLocolized()."{/b}";
		
		if($combAttributes->haveAttribute('MAX_AMOUNT', $unitName))
		{
			$max = $combAttributes->getAttribute('MAX_AMOUNT', $unitName);
		} else {
			$max = GL_SpyMaxCountZondsOnComb::Get($comb, $unit);
		}
		
		if($this->_isTimeToHandle()) 
		{ 
			// finished			
			$current = $combAttributes->getAttribute('CURRENT_AMOUNT', $unitName);
			
			if($combAttributes->haveAttribute('AWAY_AMOUNT', $unitName))
			{
				$current += $combAttributes->getAttribute('AWAY_AMOUNT', $unitName);
			}
			
			$ctt = (int)$this->eventparams["CountToTrain"];
			
			if ( ( $current + $ctt ) > $max ) {
				$ret = $max - $current;
				$this->setTrash ();
			} else {
				$ret = $ctt;
			}
			
			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $unitName, (float)$ret);						

			// системное сообщение			
			$messparams[1] = "{b} ".$this->eventparams["CountToTrain"]."{/b}";
			DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "FINISH_TRAIN_UNIT_MESS", $messparams, 0, 'GREEN' );
			
			// прибиваем эвент
			$this->setTrash ();

		} else { 
			// in progress
			$trained = $this->_countTmpTrainedUnitsCount($timetoTrainOne);			
			
			if($trained > $this->eventparams["CountToTrain"])
			{
				$trained = $this->eventparams["CountToTrain"];
			}
			
			if ( $trained > 0 )
			{ 
				// успело натренироваться за это время
				$current = $combAttributes->getAttribute('CURRENT_AMOUNT', $unitName);

				if ( ( $current + $trained ) > $max )
				{
					$ret = $max - $current;
					$this->setTrash ();
				} else {
					$ret = $trained;
				}

				// добавляем их на соту
				$combAttributes->deltaAttribute('CURRENT_AMOUNT', $unitName, (float)$ret);	
				
				$eventAttributes = DE_EventQueueAttributes::CreateFromEventID((int)$this->eqid);
				$eventAttributes->deltaAttribute('CountToTrain', -$trained);									
				
				$event = DE_EventQueue::CreateFromID((int)$this->eqid);
				$newEventStartTime = GB_TypeFactory::CreateBase('GB_Date', $event->getStartDate()->value + $trained * $timetoTrainOne);
				
				$event->setStartDate($newEventStartTime);
				
				// системное сообщение				
				$countTraned = $this->eventparams["CountToTrain"] + $trained;
				
				$unitTrainAcccumulator = $eventAttributes->getAttribute('TrainedCountAccumulator');		
				if($this->checktime != 'now()' || RD_AdeptUserActionToConsole::$commandLineFlag)
				{
					$eventAttributes->deltaAttribute('TrainedCountAccumulator', $trained);
					
					$messparams[1] = "{b} ".$countTraned."{/b}";
					$messparams[3] = "{b} ".$trained."{/b}";
				}else{					
					$eventAttributes->setAttribute('TrainedCountAccumulator', 0);
					
					$countTranedForMessage = $countTraned + $unitTrainAcccumulator;
					$trainedForMessage = $trained + $unitTrainAcccumulator;
					
					$messparams[1] = "{b} ".$countTranedForMessage."{/b}";
					$messparams[3] = "{b} ".$trainedForMessage."{/b}";
				}
								
				DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_TRAIN_UNIT_MESS", $messparams, 'NOACCUMULATE' );
				
				if($this->eventparams["CountToTrain"] <= 0)
				{
					// прибиваем эвент
					$this->setTrash ();
				}								
			} else {
				// Если это зонды
				if ($unitName ='SPY_ZOND_SCOUT' || $unitName ='SPY_ZOND_EXPLORER') {
					// системное сообщение				
					$countTraned = $this->eventparams["CountToTrain"] + $trained;
					$messparams[1] = "{b} ".$countTraned."{/b}";
								
					// На соте % находятся в строительстве % %.
					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROСESS_TRAIN_UNIT_MESS", $messparams, 'NOACCUMULATE' );
				}
			}
		}

		return true;
	}
	
	public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
	{							
		return true;				
	}	
}

?>