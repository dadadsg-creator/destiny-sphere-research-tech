<?php $CVS_Version = '$Revision$ ; $Date$';

// обработчик трансферов ресурсов между сотами

class EH_TransferResource extends EH_Event
{
	public $checktime;

	private $categoryname;
	
	private $combFrom;
	
	private $combTo;
	
	function EH_TransferResource()
	{
		EH_Event::EH_Event();
	}
	
	function handleEvent($eqid)
	{
		// если внешний рефреш
		if(!$this->checktime)
		{
			$this->checktime = 'now()';
		}

		$this->eqid = $eqid;

		// получаем параметры эвента
		if(!$this->getEventParams())
		{
			return false;
		}

		// сотa откуда
		$this->combFrom = DE_Comb::CreateFromID((int)$this->eventparams["ID_CombFrom"]);

		// сотa куда
		$this->combTo = DE_Comb::CreateFromID((int)$this->eventparams["ID_CombTo"]);
		
		$this->categoryname = DS_Trade::GetTradeCategoryNameByID((int)$this->eventparams['ID_Category']);

		// если еще не время, то отсылаем сообщение и выходим
		if(!$this->_isTimeToHandle())
		{
			$this->_preHandle();
			return true;
		}
		
		// добавляем ресурсы на соту
		switch ($this->categoryname)
		{
			case "RESOURCE":					
				$st = ST_Attributes::Create();

				$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->eventparams["ID_CombTo"]);
				$attributeName = $st->getAttributeNameByAttributeNameID((int)$this->eventparams["ID_Article"]);
				$delta = $this->eventparams["Amount"];
				
				$messparams[1] = "{b} ".DE_Localization::CreateForCurentLang()->get((int)$st->getAttributeLocalizedName($attributeName))."{/b}";
				
				//Проверка отслеживания ситуации когда передали на соту архов, ученых или еще чего и хватает дли для этого места.					
				$personTypes = array('WORKER', 'SCIENTIST', 'ARCHEOLOGIST');	
				if(in_array($attributeName, $personTypes))
				{
					// Максимум может быть на соте
					$maxAmount = $combAttributes->getAttribute('MAX_AMOUNT', $attributeName);
					// Текущее количество на соте
					$currentAmount = $combAttributes->getAttribute('CURRENT_AMOUNT', $attributeName);
					
					// добовляем к текущему кол-ву еще и археологов, которые в загуле
					if($attributeName == 'ARCHEOLOGIST')
					{
						$currentAmount += $combAttributes->getAttribute('AWAY_AMOUNT', $attributeName);
					}
					
					// Сколько можно максимум добавить на соту
					$maxDelta = $maxAmount - $currentAmount;
					
					// Затычка
					if ($maxDelta < 0) {
						$maxDelta = 0;
					}
					
					if($maxDelta < $delta)
					{	
						//Логируем помирание юнита
						DE_LogUnitsDeathOnComb::CreateFromID($this->combTo->getID())->log('TRANSFER_TO_NO_PLACE', DE_Unit::CreateFromName($attributeName), $currentAmount, ($delta - $maxDelta));
						
						$messparams1[0] = "{b} ".$this->combTo->getName()."{/b}";;
						$messparams1[1] = $delta - $maxDelta;
						$messparams1[2] = $messparams[1];
						
						// На соте % не хватило места для % %
						DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "NO_PLACE", $messparams1);

						// Меняем количество челяди, которое будет добавлено
						$delta = $maxDelta;
					}
				}	

				// Добавляем ресы на соту
				$combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, (float)$delta);			
				 
				break;
					
			case "ARTEFACT":
				ME_TransferArtefactWork::AddArtfactsFromEventToComb($this->eqid, $this->combTo);
				$messparams[1] = "{b} ".DE_Artefact::CreateFromArtefactID((int)$this->eventparams["ID_Article"])->getLocalizedName()."{/b}";
				$delta = $this->eventparams["Amount"];
				break;

			case "UNIT":
				$unit = DE_Unit::CreateFromID((int)$this->eventparams["ID_Article"]);
				ME_AddUnitsToHomeArmy::AddUnit($this->combTo, $unit, (float)$this->eventparams["Amount"], GB_TypeFactory::CreateBase('GB_Date', $this->eventendtime));
				$messparams[1] = "{b} ".$unit->getLocalizedName()."{/b}";
				$delta = $this->eventparams["Amount"];
				break;
		}

		$messparams[0] = "{b} ".$delta."{/b}";
		$messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
		$messparams[3] = "{b} ".$this->combTo->getName()."{/b}";

		// системное сообщение
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "FINISH_TRANSFER_RESOURCE_MESS", $messparams);

		$this->setTrash ();
		
		//Логируем доставку
		DS_Transfer::LogTransferDeliver($this->eqid);
		
		return true;
	}
	
	private function _preHandle()
	{
		$messparams[0] = "{b} ".$this->eventparams["Amount"]."{/b}";

		switch ($this->categoryname)
		{
			case "RESOURCE":		
				$st = ST_Attributes::Create();
				$attributeName = $st->getAttributeNameByAttributeNameID((int)$this->eventparams["ID_Article"]);				
				$messparams[1] = "{b} ".DE_Localization::CreateForCurentLang()->get((int)$st->getAttributeLocalizedName($attributeName))."{/b}";
				break;

			case "ARTEFACT":
				$messparams[1] = "{b} ".DE_Artefact::CreateFromArtefactID((int)$this->eventparams["ID_Article"])->getLocalizedName()."{/b}";
				break;

			case "UNIT":
				$messparams[1] = "{b} ".DE_Unit::CreateFromID((int)$this->eventparams["ID_Article"])->getUnitNameLocolized()."{/b}";
				break;
		}
		
		$timeParts = $this->_getTimePeriodToEnd()->getWithParts();

		$messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
		$messparams[3] = "{b} ".$this->combTo->getName()."{/b}";
		$messparams[4] = $timeParts['DAYS'];
		$messparams[5] = $timeParts['HOURS'];
		$messparams[6] = $timeParts['MINUTES'];
		$messparams[7] = $timeParts['SECONDS'];

		// системное сообщение
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_TRANSFER_RESOURCE_MESS", $messparams, null, null, 'NOACCUMULATE');
	}
}

?>