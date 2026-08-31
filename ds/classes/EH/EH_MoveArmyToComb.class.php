<?php $CVS_Version = '$Revision$ ; $Date$';

class EH_MoveArmyToComb extends EH_Army
{
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
		$event = DE_EventQueue::CreateFromID((int)$this->eqid);
		
		//Убиваем экшен армии
		ME_CreateArmyAction::DeleteAction($this->army);
		
		//Ставим теекущию соту пустой
		$this->army->setCurentComb(new DE_CombNull('NULL'));
		
		$this->army->setBaseComb($this->combTo);
		
		DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combTo, $event->getEndDate());
		
		ME_RefreshCombDefendArmy::ExternalRefreshArmys($this->combTo, $this->army);
		
		DE_ReinforcementMarks::CreateFromID($this->combTo->getID())->addMark(GB_TypeFactory::CreateBase('GB_Date', $this->eventendtime), $this->army->getArmyPower());
	
		//пишем мессагу что армия вернулась на соту
		$messparams[0] = "{b}".$this->army->getName()."{/b}";
		$messparams[1] = "{b}".$this->combTo->getFullName()."{/b}";
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ARMY_MOVE_TO_COMB_MESS3', $messparams, 1, "GREEN");
	}
	
	private function _preHandle()
	{
		$period = $this->_getTimePeriodToEnd()->getWithParts();	
				
		$messparams[0] = "{b}".$this->army->getName()."{/b}";
		$messparams[1] = "{b}".$this->combTo->getFullName()."{/b}";
		$messparams[2] = "{b}".$this->combFrom->getFullName()."{/b}";
		$messparams[3] = "{b}".$this->_getTimePeriodToEnd()->getDSFormat()."{/b}";
		
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ARMY_MOVE_TO_COMB_MESS2', $messparams, 1, "GREEN", 'NOTFORSTORE');		
	}
	
	public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $checkDate)
	{	
		if($event->getEndDate()->value > $checkDate->value)
		{
			return false;
		}
		
		return true;
	}
}

?>