<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2009/02/16 10:27:22 $';

/**
 * Обработка евента возвращения зондов с разведки
 *
 */
class EH_SpyMoveFromResearch extends EH_Event 
{
	public $eqid;
	
	/**
	 * Откуда вылетали на разведку (и куда возвращаются)
	 *
	 * @var DE_Comb
	 */
	private $combFrom;
	
	/**
	 * Сота, которую разведывали
	 *
	 * @var DE_Comb
	 */
	private $combTo;
	
	/**
	 * Евент
	 *
	 * @var DE_EventQueue
	 */
	private $event;
	
	/**
	 * Юнит
	 *
	 * @var DE_Unit
	 */
	private $unit;
	
	/**
	 * Кол-во зондов
	 *
	 * @var int
	 */
	private $amount;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Зонды возвращаются просто на соту
	 *
	 * @param int $eqid
	 * @return bool
	 */
	public function handleEvent($eqid)
	{
		assert($eqid > 0);
		parent::handleEvent($eqid);
		$this->eqid = $eqid;
		
		$this->event = DE_EventQueue::CreateFromID($eqid);
			
		$eventAttributes = $this->event->getAttributes();
		$this->combFrom = DE_Comb::CreateFromID((int)$eventAttributes->getAttribute('ID_CombFrom'));
		$this->combTo = DE_Comb::CreateFromID((int)$eventAttributes->getAttribute('ID_CombTo'));
		$this->unit = DE_Unit::CreateFromID((int)$eventAttributes->getAttribute('ID_Unit'));
		$this->amount = (int)$eventAttributes->getAttribute('Amount');
		
		if($this->_isTimeToHandle())
		{
			$this->_handle();	
			
			$this->event->setIsTrash(true);		
			$this->event->commit();				
		}else{
			$this->_preHandle();
		}
		
		return true;
	}
	
	private function _handle()
	{
		$combAttributes = $this->combFrom->getAttributes();
		$combAttributes->deltaAttribute('AWAY_AMOUNT', $this->unit->getName(), -$this->amount);
		$combAttributes->deltaAttribute('CURRENT_AMOUNT', $this->unit->getName(), $this->amount);
		
		$messparams = array();
		$messparams[0] = $this->combFrom->getFullName();
		$messparams[1] = $this->combTo->getFullName();
		$messparams[2] = $this->combTo->getUserInstance()->getUser()->getName();
		$messparams[3] = $this->amount;
		
		// На соту % с разведки соты % игрока % вернулась группа из % зондов
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "SPY_RETURNED_FROM_RESEARCH", $messparams, 2, 'GREEN', $this->combFrom->getUserInstanceID());
	}
	
	private function _preHandle()
	{
		$messparams = array();
		$messparams[0] = $this->amount;
		$messparams[1] = "&#34;".$this->unit->getUnitNameLocolized()."&#34;";
		$messparams[2] = $this->combTo->getFullName();
		$messparams[3] = $this->combTo->getUserInstance()->getUser()->getName();
		$messparams[4] = $this->combFrom->getFullName();
		$messparams[5] = $this->event->getEndDate()->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta());
		
		// Возвращается группа из {b}%{/b} зондов {b}%{/b} с разведки соты {b}%{/b} игрока {b}%{/b} на соту {b}%{/b}. Время прибытия: {b}%{/b}
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "SPY_MOVING_FROM_RESEARCH", $messparams, 2, 'GREEN', $this->combFrom->getUserInstanceID());
	}
	
	public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $checkDate)
	{
		return false;
	}
}

?>