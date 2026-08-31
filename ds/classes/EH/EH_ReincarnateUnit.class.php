<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2009/08/27 07:39:52 $';

/**
 * Обработчик события реинкарнации военных юнитов. 
 *  
 */
class EH_ReincarnateUnit extends EH_Event
{
	/**
	 * Сота на которой производим
	 *
	 * @var DE_Comb
	 */
	private $combObject;
	
	/**
	 * Юнит который производим
	 *
	 * @var DE_Unit
	 */
	private $unit;
	
	/**
	 * Колличество юнитов которое поставленно для тернировки сейчас в ивенте
	 *
	 * @var integer
	 */
	private $countToTrain;
	
	/**
	 * Дата 
	 *
	 * @var GB_Date
	 */
	private $chekDate;
	
	function __construct()
	{
		EH_Event::EH_Event ();
	}

	function handleEvent($eqid)
	{		
		$this->eqid = $eqid;
		
		$this->chekDate = $this->_getChekTime();
		
		// берем параметры эвента
		if(!$this->getEventParams())
		{
			return false;
		}
		
		//Сколько юнитов на реинкарнации
		$this->countToTrain = (int)$this->eventparams["CountToReincarnate"];
		assert($this->countToTrain >= 0);

		//Сота на котором сторим
		$this->combObject = DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"]);
		
		//Юнит который строим
		$this->unit = DE_Unit::CreateFromID((int)$this->eventparams["ID_Unit"]);				
		
		// если закончено
		if ($this->_isTimeToHandle())
		{
			$this->_handle();
		} else { // не закончено		
			$this->_preHandle();
		}
		
		return true;
	}
	
	private function _handle()
	{		
		//ДОбавляем в домашку что наделали оглаеды.
		ME_AddUnitsToHomeArmy::AddUnit($this->combObject, $this->unit, (float)$this->countToTrain, $this->chekDate, false);						

		//  прибиваем эвент
		$this->setTrash();
			
		// Проверяем защиту новичков
		GL_NewbDefendTestArmyPower::SetNeedCheckCurrentUserInstanceFlag();
		
		// Отметка в трассе апкипа
		DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combObject, $this->chekDate);
		
		// название соты
		$messparams[0] = "{b} ".$this->combObject->getName()."{/b}";
		
		// Количество реинкарнированых юнитов
		$messparams[1] = "{b} ".$this->countToTrain."{/b}";
		
		// Название юнита
		$messparams[2] = "{b} ".$this->unit->getLocalizedName()."{/b}";
		
		// Системное сообщение
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "REINCARNATE_UNIT_MESS", $messparams );
	}
	
	private function _preHandle()
	{
		$messparams[0] = "{b} ".$this->countToTrain."{/b}";
		$messparams[1] = "{b} ".$this->unit->getLocalizedName()."{/b}";
		$messparams[2] = "{b} ".$this->combObject->getName()."{/b}";
		$event = DE_EventQueue::CreateFromID((int)$this->eqid);
		$messparams[3] = "{b} ".$event->getEndDate()->getSystemMessageFormat()."{/b}";
		
		// Системное сообщение
		// % % находяться на ренкарнации на соте %. Время окончания %.
		DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "REINCARNATE_UNIT_IN_PROGRESS_MESS", $messparams );
	}
}

?>