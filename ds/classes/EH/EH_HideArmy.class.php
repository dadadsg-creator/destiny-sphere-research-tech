<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2010/07/07 08:46:10 $';

class EH_HideArmy extends EH_Army
{
	function __construct()
	{
		EH_Event::EH_Event ();
	}
	
	public function handleEvent($eqid)
	{
		if(!parent::handleEvent($eqid))
		{
			return true;
		}

		if($this->_isTimeToHandle())
		{
			$this->_handle();			
		}else{
			$this->_preHandle();
		}

		return true;
	}
	
	private function _handle()
	{
		//пишем мессагу что армия вернулась на соту
		$messparams[0] = $this->army->getName();
		$messparams[1] = $this->combTo->getFullName();		
		
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'HIDE_ARMY_RETURN', $messparams, 1, "GREEN"); 		
				
		//Убиваем экшен армии
		ME_CreateArmyAction::DeleteAction($this->army);
		
		//Ставим теекущию соту пустой
		$this->army->setCurentComb(new DE_CombNull('NULL'));
				
		$event = DE_EventQueue::CreateFromID((int)$this->eqid);
		
		DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combTo, $event->getEndDate());
		
		ME_RefreshCombDefendArmy::ExternalRefreshArmys($this->combTo, $this->army);
		
		DE_ReinforcementMarks::CreateFromID($this->combTo->getID())->addMark(GB_TypeFactory::CreateBase('GB_Date', $this->eventendtime), $this->army->getArmyPower());
		
		$this->setTrash();
	}
	
	private function _preHandle()
	{				
		$messparams[0] = $this->army->getName();
		$messparams[1] = $this->combTo->getFullName();		
		$messparams[2] = $this->_getTimePeriodToEnd()->getDSFormat();		
				
		// системное сообщение
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'HIDE_ARMY_RETURN_NOT_END', $messparams, 1, "GREEN", 'NOTFORSTORE');
	}//END OF METHOD
		
	public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
	{	
		if($event->getEndDate()->value > $chekDate->value)
		{
			return false;
		}				

		return true;	
	}	
}


?>