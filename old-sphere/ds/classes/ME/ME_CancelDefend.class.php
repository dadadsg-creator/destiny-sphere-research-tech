<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CancelDefend
{
	static private $army;

	static private $checkDate;

	static public $returnMode;

	static public $combFrom;

	static public $combTo;

	static public $returnResources;

	static public $passedTime;

	static public $travelTime;

	static public $dateTo;

	static public function Cancel(DE_Army $army, GB_Date $checkDate)
	{
		self::$army = $army;
		self::$checkDate = 	$checkDate;

		assert(!$army->getArmyAction()->isNull());

		switch ($army->getArmyAction()->getActionType())
		{
			case "MOVE_TO_DEFEND":
				if($army->getArmyAction()->getEndDate()->value < self::$checkDate->value)
				{
					self::_cancelFromDefend();
				}else{
					self::_cancelFromMovement();
				}
				break;

			case "IN_DEFEND":
				self::_cancelFromDefend();
				break;

			default:
				ds_error('Uncorrect army action = '.$army->getArmyAction()->getActionType());
		}

	}

	static private function _cancelFromMovement()
	{

		$armyAction = self::$army->getArmyAction();

		self::$combTo = $armyAction->getCombTo();
		self::$combFrom = $armyAction->getCombFrom();
		$endDate = $armyAction->getEndDate();
		$startDate = $armyAction->getStartDate();

		$armyEvent = DE_EventQueue::CreateFromArmy(self::$army);

		// сколько времени прошло с момента начала похода
		self::$passedTime = GB_TypeFactory::CreateBase('GB_TimePeriod', (int)(self::$checkDate->value - $startDate->value));

		//Сколько общее время пути
		self::$travelTime = GB_TypeFactory::CreateBase('GB_TimePeriod', (int)$endDate->value - $startDate->value);

		//Высчитываем сколько надо вернуть борохла
		self::$returnResources = GL_FullUnitsMovementCost::CountCancelDefend(self::$army, self::$combFrom, self::$combTo, self::$passedTime,  self::$travelTime, $startDate);

		//Приписываем ресурсы для возврата армией
		$armyAttributes = self::$army->getAttributes();

		foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
		{
			$val = (float)self::$returnResources->getReturn($resourceName);
			if($val > 0)
			{
				$armyAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, $val);
			}
		}

		//Убиваем старый ивент
		$armyEvent->setIsTrash(true);

		//Формируем сведения для создания нового ивента и экшена
		$dateFrom = GB_TypeFactory::CreateBase('GB_Date', self::$checkDate->value);
		$dateTo = GB_TypeFactory::CreateBase('GB_Date', self::$checkDate->value + self::$passedTime->value);
		$datePeriod = new GB_DatePeriod($dateFrom, $dateTo);

		self::_createComeBackEvent($datePeriod);

		self::$returnMode = 'FROM_MOVEMENT';
		self::$dateTo = $dateTo;
		//DebugTools::logAll(self::$dateTo, 'ME_CancelDefend.log');

	}

	static private function _cancelFromDefend()
	{
		$armyAction = self::$army->getArmyAction();

		self::$combTo = $armyAction->getCombTo();
		self::$combFrom = $armyAction->getCombFrom();
		$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)self::$army->getAttributes()->getAttribute('CURRENT_AMOUNT', 'MOVE_SPEED'));

		self::$travelTime = GL_TravelTime::Count(self::$combFrom, self::$combTo, $speed);
		$startTime = GB_TypeFactory::CreateBase('GB_Date', time());
		$endTime = GB_TypeFactory::CreateBase('GB_Date', time() + self::$travelTime->value);
		$datePeriod = new GB_DatePeriod($startTime, $endTime);

		self::$army->setCurentComb(new DE_CombNull());

		self::_createComeBackEvent($datePeriod);
		/*
		$combAttributes = self::$combFrom->getAttributes();
		$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)self::$army->getAttributes()->getAttribute('CURRENT_AMOUNT', 'MOVE_SPEED'));
		$date = GB_TypeFactory::CreateBase('GB_Date', time());
		$upkeep = GL_FullUnitsMovementCost::CountPath(self::$army, self::$combFrom, self::$combTo, $speed, $date, 'ATTACK');
		
		foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
		{
			if($combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true) >= $upkeep->getPay($resourceName))
			{					
				$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)(-$upkeep->getPay($resourceName)));
			} else {
				ds_error("Not enough $resourceName to return army from defence. NEED: ".$upkeep->getPay($resourceName)." HAVE: ".$combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true));					
			}
		}
		*/
		self::$returnMode = 'FROM_DEFEND';
		self::$dateTo = $endTime;
	}

	static private function _createComeBackEvent(GB_DatePeriod $datePeriod)
	{
		$armyAction = self::$army->getArmyAction();

		$eventParticipants = new GB_EventParticipants;
		$eventParticipants->setParticipant(self::$army->getUserInstance());

		$actionData = new GB_ArmyActionCreationData('MOVE_FROM');

		$actionData->setArmy(self::$army);
		$actionData->setDatePeriod($datePeriod);
		$actionData->setParticipants($eventParticipants);
		$actionData->setPath(new GB_TravelPath(self::$combTo, self::$combFrom));

		ME_CreateArmyAction::CreateAction($actionData);
	}

}


?>