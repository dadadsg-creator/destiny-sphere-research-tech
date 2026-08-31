<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CancelSpy
{
	//static public $dateTo;

	static public function Cancel(DE_SpyZondGroup $spyZondGroup)
	{
		$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
		$return = $spyZondGroup->getAmount();
				
		// сколько времени прошло с момента начала похода
		$passedTime = GB_TypeFactory::CreateBase('GB_TimePeriod', (int)($checkDate->value - $spyZondGroup->getEventQueue()->getStartDate()->value));
		//Сколько общее время пути
		//$travelTime = GB_TypeFactory::CreateBase('GB_TimePeriod', (int)($spyZondGroup->getEventQueue()->getStartDate()->value - $spyZondGroup->getEventQueue()->getEndDate()->value));
	
		$creationData = new GB_SpyActionCreationData();
		$creationData->setActionType(DE_SpyAction::CreateFromName('MOVE_BACK'));
		$creationData->setCombFrom($spyZondGroup->getCombFrom());
		$creationData->setCombTo($spyZondGroup->getCombTo());
		$creationData->setPeriod(new GB_DatePeriod($checkDate, GB_TypeFactory::CreateBase('GB_Date', (int)($checkDate->value + $passedTime->value))));
		$creationData->setUnit($spyZondGroup->getUnit());
		$creationData->setZondsAmount($return);
		$creationData->setNeedChangeAttributes(false);
		
		$creationData->setCombtoOwnerParticipateInEvent(false); // не надо чтобы в евенте участвовал владелец разведанной соты
		
		$newEventQueueID = ME_SpyGroupAction::CreateGroupAction($creationData);
		
		$spyZondGroup->getEventQueue()->setIsTrash(true);		
		$spyZondGroup->getEventQueue()->commit();	
	}
}


?>`