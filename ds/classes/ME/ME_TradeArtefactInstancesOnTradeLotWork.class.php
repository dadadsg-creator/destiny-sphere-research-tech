<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_TradeArtefactInstancesOnTradeLotWork
{
	static public function AddArtfactsToTradeLotFromComb($tradeLotID, $artefactID, $amount, DE_Comb $comb, $artefactBirthTime, $artefactDeathTime)
	{
		assert(is_int($tradeLotID) && $tradeLotID > 0);
		assert(is_int($artefactID) && $artefactID > 0);
		assert(is_float($amount) && $amount > 0);
		
		$artefacts = DS_Artefact::GetArtefactsFromCombStore($comb, $artefactID, $artefactBirthTime, $artefactDeathTime);		
		assert(count($artefacts) >= $amount);
		
		$counter = 0;
		foreach($artefacts AS $k=>$artefactData)
		{
			if($counter >= $amount)
			{
				break;
			}else{
				$counter++;
			}
			
			$artefactInstanceID = (int)$artefactData['id_artefactinstance'];
			assert($artefactInstanceID > 0);
			
			DS_Trade::AddNewArtefactInstanceToLot($tradeLotID, $artefactInstanceID);			
			
			DS_Artefact::DeleteArtefactFromCombStore($comb, $artefactInstanceID);									
		}
	}
	
	static public function AddArtfactsToEventFromTradeLot($tradeLotID, $eventQueueID)
	{
		assert(is_int($tradeLotID) && $tradeLotID > 0);
		assert(is_int($eventQueueID) && $eventQueueID > 0);
		
		$artefactInstances = DS_Trade::GettAllArtefactInstanceToLot($tradeLotID);
		foreach($artefactInstances as $k=>$artefactData)
		{
			$artefactInstanceID = (int)$artefactData['id_artefactinstance'];
			assert($artefactInstanceID > 0);
			
			DS_Trade::BindArtefactEvent($artefactInstanceID, $eventQueueID);
		}
	}
	
	static public function AddArtfactsFromEventToComb($eventQueueID, DE_Comb $comb)
	{
		assert(is_int($eventQueueID) && $eventQueueID > 0);
		
		$event = DE_EventQueue::CreateFromID($eventQueueID);
		
		if($event->getEventName() == 'TRADING')
		{
			$artefactInstances = DS_Trade::GettAllArtefactInstanceToLot((int)$event->getAttributes()->getAttribute('ID_TradeLot'));	
		}else{
			$artefactInstances = DS_Trade::GetAllArtefactsFromEvent($eventQueueID);
		}
		
		foreach($artefactInstances as $k=>$artefactData)
		{
			$artefactInstanceID = (int)$artefactData['id_artefactinstance'];
			assert($artefactInstanceID > 0);
			
			DS_Artefact::AddArtefactToCombStore($comb, $artefactInstanceID);
		}
	}
	
	static public function AddArtfactsToHistoryFromTradeLot($tradeLotID)
	{
		assert(is_int($tradeLotID) && $tradeLotID > 0);
		
		$artefactInstances = DS_Trade::GettAllArtefactInstanceToLot($tradeLotID);
		foreach($artefactInstances as $k=>$artefactData)
		{
			$artefactInstanceID = (int)$artefactData['id_artefactinstance'];
			assert($artefactInstanceID > 0);
			
			DS_Trade::AddArtefactToTradeHistory($tradeLotID, $artefactInstanceID);
		}
	}
}


?>

