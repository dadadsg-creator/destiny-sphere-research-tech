<?php $CVS_Version = '$Revision$ ; $Date$';
	  
/**
 * Класс обработчик движения армии на оборону
 *
 */
class EH_ArmyMovementToDefend extends EH_Army
{	
	/**
	 * Инстанс игрока на которого едем
	 *
	 * @var DE_UserInstance
	 */
	private $userInstanceTo;
	
	/**
	 * Инстанс игрока который едет
	 *
	 * @var unknown_type
	 */
	private $userInstanceFrom;
		
	public function __construct()
	{
		parent::__construct();
	}
	
	public function handleEvent ( $eqid )
	{
		parent::handleEvent($eqid);	
			
		if($this->_isTimeToHandle())
		{
			$this->_handle();	
			
			$event = DE_EventQueue::CreateFromID($eqid);
			$event->setIsTrash(true);		
			$event->commit();		
		}else{
			$this->_preHandle();
		}
		
		return true;
	}
	
	private function _handle()
	{				
		//Если рефреш проводит не хозяин соты на которой бой то внешний рефреш делаем
		if($GLOBALS['params']['refreshedUiid'] != $this->combTo->getUserInstance()->getID())
		{			
			assert($this->refreshUserinstance($this->combTo->getUserInstance()->getID(), 1, $this->eqid));		
		}
		
		$event = DE_EventQueue::CreateFromID((int)$this->eqid);
		
		if(!GL_CanDefendComb::CanDefend($this->combFrom, $this->combTo, $event->getEndDate()))
		{
			$this->_createComeBackEvent();			
			return true;
		}
		
		$this->army->setCurentComb($this->combTo);
			
		$roundStartTime = GB_TypeFactory::CreateConstant('GB_Date', $this->eventendtime);		
		$roundEndTime = GB_TypeFactory::CreateMaster('GB_Date', $this->eventendtime + 10000000);				
		$times = new GB_DatePeriod($roundStartTime, $roundEndTime);		
		
		$eventParticipants = new GB_EventParticipants;
				
		$actionData = new GB_ArmyActionCreationData('IN_DEFEND');
		
		$actionData->setArmy($this->army);
		$actionData->setDatePeriod($times);		
		$actionData->setParticipants($eventParticipants );
		$actionData->setPath(new GB_TravelPath($this->combFrom, $this->combTo));		
			
		ME_CreateArmyAction::CreateAction($actionData);

		// системное сообщение о постановки на защиту		
		$messparams[0] = "{b} ".$this->army->getName()."{/b}";
		$messparams[1] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
		$messparams[2] = "{b} ".$this->combTo->getFullName()."{/b}";
		$messparams[3] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
		
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'DEFEND_MESS3', $messparams, 1, "GREEN");		
		
		//Пометили что на соте с которой отправленно изменился апкип армии
		DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combFrom, $event->getEndDate());

		//Смотри есть ли какие-то защитные армик которым можно присоедениться
		ME_RefreshCombDefendArmy::ExternalRefreshArmys($this->combTo, $this->army);
		
		//ДЕнлаем пометку для военого лога о прибытие подкрепления.
		DE_ReinforcementMarks::CreateFromID($this->combTo->getID())->addMark($roundStartTime, $this->army->getArmyPower());
	}		
	
	private function _preHandle()
	{
		$period = $this->_getTimePeriodToEnd()->getWithParts();	
				
		$messparams[0] = "{b}".$this->army->getName()."{/b}";
		$messparams[1] = "{b}".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
		$messparams[2] = "{b}".$this->combTo->getFullName()."{/b}";
		$messparams[3] = "{b}".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
		$messparams[4] = "{b}".$this->_getTimePeriodToEnd()->getDSFormat()."{/b}";
		
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'DEFEND_MESS2', $messparams, 1, "GREEN", 'NOTFORSTORE');		
	}
	
	public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
	{	
		if($event->getEndDate()->value > $chekDate->value)
		{
			return false;
		}	
		
		$userInstanceTo = DE_Comb::CreateFromID((int)$event->getAttributes()->getAttribute('ID_CombTo'))->getUserInstance();
		
		if($refreshedInstance->getID() == $userInstanceTo->getID())
		{
			return true;	
		}
		
		
		return false;					
	}	
}//END OF CLASS