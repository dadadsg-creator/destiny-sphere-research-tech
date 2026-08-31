<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CancelAttack
{
	static public $eventQueueID;
	
	static public $actionData;
	
	static public $returnResources;
	
	static public $armyAction;
	
	static public function Cancel(DE_Army $army, GB_Date $checkDate)
	{
		self::$armyAction = $army->getArmyAction();
		
		assert(!self::$armyAction->isNull());		
		assert(self::$armyAction->getActionType() == 'MOVE_TO_ATTACK' || self::$armyAction->getActionType() == 'IN_BATTLE');
		
		if(self::$armyAction->getEndDate()->value < $checkDate->value)
		{
			return true;
		}
		
		$combTo = self::$armyAction->getCombTo();
		$combFrom = self::$armyAction->getCombFrom();				
		$endDate = self::$armyAction->getEndDate();
		$startDate = self::$armyAction->getStartDate();

		$armyEvent = DE_EventQueue::CreateFromArmy($army);			
		
		if($armyEvent->isLockedForHandl())
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $armyEvent->getID(), 'EVENT_QUEUE');
		}
		
		//Удаляем старый ивент
		$armyEvent->setIsTrash(true);
		
		// сколько времени прошло с момента начала похода	
		$passedTime = GB_TypeFactory::CreateBase('GB_TimePeriod', (int)($checkDate->value - $startDate->value));		
		
		//Сколько общее время пути
		$travelTime = GB_TypeFactory::CreateBase('GB_TimePeriod', (int)$endDate->value - $startDate->value);
		//Высчитываем сколько надо вернуть борохла
		self::$returnResources = GL_FullUnitsMovementCost::CountCancelAttack($army, $combFrom, $combTo, $passedTime,  $travelTime, $startDate);

		//Приписываем ресурсы для возврата армией
		$armyAttributes = $army->getAttributes();	
		
		foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k=>$resorceName)
		{
			$resourceValue = (float)self::$returnResources->getReturn($resorceName);
			
			if($resourceValue != 0)
			{
				$armyAttributes->deltaAttribute('CURRENT_AMOUNT', $resorceName, $resourceValue);			
			}			
		}
				
		
		//Формируем сведения для создания нового ивента и экшена
		$dateFrom = GB_TypeFactory::CreateBase('GB_Date', $checkDate->value);
		$dateTo = GB_TypeFactory::CreateBase('GB_Date', $checkDate->value + $passedTime->value);
		$datePeriod = new GB_DatePeriod($dateFrom, $dateTo);
		
		$eventParticipants = new GB_EventParticipants;
		$eventParticipants->setParticipant($army->getUserInstance());
				
		self::$actionData = new GB_ArmyActionCreationData('MOVE_FROM');
		
		self::$actionData->setArmy($army);
		self::$actionData->setDatePeriod($datePeriod);		
		self::$actionData->setParticipants($eventParticipants );
		self::$actionData->setPath(new GB_TravelPath($combTo, $combFrom));
					
		 
		self::$eventQueueID = ME_CreateArmyAction::CreateAction(self::$actionData);
		
		return true;
	}		
}



?>