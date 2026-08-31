<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CreateArmyAction
{	
	static public function CreateAction(GB_ArmyActionCreationData $creationData)
	{	
		
		self::DeleteAction($creationData->getArmy());
			
		$creationData->getArmy()->setInAction(true);
		
		$options["ID_Army"] = $creationData->getArmy()->getID();		
		$options["ID_CombFrom"] = $creationData->getPath()->getCombFrom()->getID();
		$options["ID_CombTo"] = $creationData->getPath()->getCombTo()->getID();		
	
		
		
		$actionName = $creationData->getActionName();
		
		if($actionName == 'IN_BATTLE')
		{
			$options["ID_Battle"] = $creationData->getBattle()->getID();			
			$options["Round"] = $creationData->getRound()->getRoundNumber();		
		}
		
		$creationData->getArmy()->getArmyAction()->setNewActionData(				
			$creationData->getPath()->getCombFrom(),
			$creationData->getPath()->getCombTo(),
			$creationData->getDatePeriod(),
			$creationData->getActionName()
		); 			
		
		$eventName = $creationData->getEventName();
		
		if(!empty($eventName))
		{			
			$eventQueueID = DE_EventQueue::CreateInDB($creationData->getEventName(), $creationData->getParticipants()->getParticipantsArray(), $options, $creationData->getDatePeriod())->getID();
			$creationData->getArmy()->setBindEventID($eventQueueID);
			return $eventQueueID;
		}else{
			return true;
		}	
								
	}
	
	static public function DeleteAction(DE_Army $army)
	{
		$army->setInAction(false);
		
		$army->getArmyAction()->setActionAsNull();		
		
		$eventQueue = DE_EventQueue::CreateFromArmy($army);

		if(!$eventQueue->isNull())
		{
			$eventQueue->setIsTrash(true);
		}
				
		$army->setBindEventID(0);		
	}
}


?>