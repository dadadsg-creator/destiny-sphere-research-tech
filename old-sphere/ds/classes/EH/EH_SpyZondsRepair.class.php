<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2009/02/16 10:27:22 $';

/**
 * Евент починки зондов
 *
 */
class EH_SpyZondsRepair extends EH_Event 
{
	/**
	 * Сота
	 *
	 * @var DE_Comb
	 */
	private $combFrom;
	
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
	 * Кол-во
	 *
	 * @var unknown_type
	 */
	private $amount;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function handleEvent($eqid)
	{
		assert($eqid > 0);
		parent::handleEvent($eqid);
		$this->eqid = $eqid;
			
		$this->event = DE_EventQueue::CreateFromID($eqid);
		$eventAttributes = $this->event->getAttributes();
		
		$this->combFrom = DE_Comb::CreateFromID((int)$eventAttributes->getAttribute('ID_CombFrom'));
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
		$messparams[0] = $this->amount;
		$messparams[1] = $this->unit->getUnitNameLocolized();
		$messparams[2] = $this->combFrom->getFormatedAddress();
		
		// % зондов "%" были отремонтированы на соте %
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "SPY_ZONDS_REPAIRED", $messparams, 2, 'GREEN');
	}
	
	private function _preHandle()
	{
		$messparams = array();
		$messparams[0] = $this->combFrom->getFormatedAddress();
		$messparams[1] = $this->amount;
		$messparams[2] = $this->unit->getUnitNameLocolized();
		$messparams[3] = $this->event->getEndDate()->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta());
		
		// На соте % % зондов "%" находятся в ремонте. Время окончания %
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "SPY_ZONDS_IN_REPAIR", $messparams, 2, 'GREEN');
	}
	
	public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $checkDate)
	{
		return false;
	}
}

?>