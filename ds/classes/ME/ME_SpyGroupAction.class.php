<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_SpyGroupAction 
{
	/**
	 * Контейнер с данными для создания действия
	 *
	 * @var GB_SpyActionCreationData
	 */
	static private $creationData;
	
	/**
	 * Евент
	 *
	 * @var DE_EventQueue
	 */
	static private $eventQueue;
	
	/**
	 * Создание действия группы зондов
	 *
	 * @param GB_SpyActionCreationData $creationData
	 * @return int
	 */
	static public function CreateGroupAction(GB_SpyActionCreationData $creationData)
	{
		self::$creationData = $creationData;
		
		// создаем евент
		self::_createEvent();
		
		// создаем запись в базе с описанием действия
		self::_createZondsGroup();
		
		return (int)self::$eventQueue->getID();
	}
	
	static private function _createEvent()
	{
		$eventparams = array();
		$participants = array();
		
		$eventparams["Amount"] = self::$creationData->getZondsAmount();
		$eventparams["ID_Unit"] = self::$creationData->getUnit()->getID();
		
		if(!is_null(self::$creationData->getOneRound()))
		{
			$eventparams["OneRound"] = self::$creationData->getOneRound();
		}
		
		$eventparams["ID_CombFrom"] = self::$creationData->getCombFrom()->getCombID();
		$participants[] = self::$creationData->getCombFrom()->getUserInstanceID();
		
		// если есть сота куда летим, то добавляем ее к атрибутам евента
		  // а хозяина соты в участники евента
		if(!is_null(self::$creationData->getCombTo()))
		{
			$eventparams["ID_CombTo"] = self::$creationData->getCombTo()->getCombID();
			if(self::$creationData->getCombtoOwnerParticipateInEvent())
			{
				$participants[] = self::$creationData->getCombTo()->getUserInstanceID();
			}
		}
		
		self::$eventQueue = DE_EventQueue::CreateInDB(self::$creationData->getActionType()->getEvent()->getEventName(), $participants, $eventparams, self::$creationData->getPeriod());
	}
	
	static private function _createZondsGroup()
	{
		// изменяем атрибуты соты, если надо
		if(self::$creationData->needChangeAttributes())
		{
			$combAttributes = self::$creationData->getCombFrom()->getAttributes();
			$combAttributes->deltaAttribute('CURRENT_AMOUNT',self::$creationData->getUnit()->getName(), -self::$creationData->getZondsAmount());
			$combAttributes->deltaAttribute('AWAY_AMOUNT', self::$creationData->getUnit()->getName(), self::$creationData->getZondsAmount());
		}
		
		DS_SpyAction::InsertSpyActionGroup(self::$creationData, self::$eventQueue);
	}
}

?>