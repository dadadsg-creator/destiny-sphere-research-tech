<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_TransferArtefactWork
{
	static public function AddArtfactsToTransferEventFromComb(DE_EventQueue $event, $artefactID, $amount, DE_Comb $comb, $artefactBirthTime, $artefactDeathTime)
	{
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
			
			DS_Artefact::AttachArtefactInstanceToEvent($event, $artefactInstanceID);			
			
			DS_Artefact::DeleteArtefactFromCombStore($comb, $artefactInstanceID);									
		}
	}
	
	static public function AddArtfactsFromEventToComb($eventQueueID, DE_Comb $comb)
	{
		assert(is_int($eventQueueID) && $eventQueueID > 0);
				
		$artefactInstances = DS_Artefact::GetAllArtefactsFromEvent($eventQueueID);
		
		foreach($artefactInstances as $k=>$artefactData)
		{
			$artefactInstanceID = (int)$artefactData['id_artefactinstance'];
			assert($artefactInstanceID > 0);
			
			DS_Artefact::AddArtefactToCombStore($comb, $artefactInstanceID);
		}
	}
}

?>