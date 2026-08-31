<?php $CVS_Version = '$Revision$ ; $Date$';

class EH_ArmyMovementFrom extends EH_Army
{			
	
	function __construct()
	{
		parent::__construct();	
	}
	
	public function handleEvent ( $eqid )
	{
		if(!parent::handleEvent($eqid))
		{
			return true;
		}
		
		if($this->_isTimeToHandle())
		{

			$this->_handle();
			$this->setTrash ();
		}else{
			$this->_preHandle();
		}

		return true;
	}
	
	private function _handle()
	{
		//пишем мессагу что арми€ вернулась на соту
		$messparams[0] = "{b} ".$this->army->getName()."{/b}";
		$messparams[1] = "{b} ".$this->combTo->getFullName()."{/b}";
		$LocolizedStealingResourcesList = ME_ResourceStealing::GetLocolizedStealingResourcesList($this->army);
		//≈сли ничего не потер€ли по дороге
		if ($LocolizedStealingResourcesList['loosed'] == " - ") {
			$messparams[2] = "{b} ".$LocolizedStealingResourcesList['stealing']."{/b}";
			DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ATTACK_MESS3', $messparams, 1, "GREEN");
		} else {
			$messparams[2] = "{b} ".$LocolizedStealingResourcesList['loaded']."{/b}";
			$messparams[3] = "{b} ".$LocolizedStealingResourcesList['loosed']."{/b}";
			DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ATTACK_MESS6', $messparams, 1, "GREEN");
		}
		
		//–азгружаем ресурсы что награбили и что отсалис ь от апкипа
		ME_ResourceStealing::UnloadResource($this->combTo, $this->combFrom, $this->army);				
				
		//„истим атрибуты армии
		//$this->army->getAttributes()->deleteAttributes();

		//”биваем экшен армии
		ME_CreateArmyAction::DeleteAction($this->army);
		
		//—тавим теекущию соту пустой
		$this->army->setCurentComb(new DE_CombNull('NULL'));
				
		$event = DE_EventQueue::CreateFromID((int)$this->eqid);

		DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combTo, $event->getEndDate());
		
		ME_RefreshCombDefendArmy::ExternalRefreshArmys($this->combTo, $this->army);
		
		DE_ReinforcementMarks::CreateFromID($this->combTo->getID())->addMark(GB_TypeFactory::CreateBase('GB_Date', $this->eventendtime), $this->army->getArmyPower());
	}//END OF METHOD
		
	private function _preHandle()
	{				
		$messparams[0] = "{b} ".$this->army->getName()."{/b}";
		$messparams[1] = "{b} ".$this->combTo->getFullName()."{/b}";
		$messparams[2] = "{b} ".$this->combFrom->getFullName()."{/b}";
		$messparams[3] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
		$messparams[4] = "{b} ".$this->_getTimePeriodToEnd()->getDSFormat()."{/b}";
		$LocolizedStealingResourcesList = ME_ResourceStealing::GetLocolizedStealingResourcesList($this->army);
		$messparams[5] = "{b} ".$LocolizedStealingResourcesList['stealing']."{/b}";
				
		// системное сообщение
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ATTACK_MESS2', $messparams, 1, "GREEN", 'NOTFORSTORE');
	}//END OF METHOD
		
	public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
	{	
		if($event->getEndDate()->value > $chekDate->value)
		{
			return false;
		}
		
		//Ёто условие удалено так как смысла не имеет. »вент это прив€зан только кодному человеку и должен быть обработан всегда
		/*$userInstanceTo = DE_Comb::CreateFromID((int)$event->getAttributes()->getAttribute('ID_CombTo'))->getUserInstance();
		
		if($refreshedInstance->getID() == $userInstanceTo->getID())
		{
			return true;	
		}else{
			return false;
		}*/			

		return true;	
	}	
}//END OF CLASS


?>