<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_SpyActionCreationData extends BS_Base
{
	/**
	 * Enter description here...
	 *
	 * @var integer
	 */
	private $zondsAmount;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_SpyAction
	 */
	private $actionType;
	
	/**
	 * Enter description here...
	 *
	 * @var GB_DatePeriod
	 */
	private $period;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Comb
	 */
	private $combFrom;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Comb
	 */
	private $combTo = null;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Unit
	 */
	private $spyUnit;
	
	/**
	 * Enter description here...
	 *
	 * @var bool
	 */
	private $needChangeAttributes = true;
	
	/**
	 * Enter description here...
	 *
	 * @var integer
	 */
	private $logID = null;
	
	/**
	 * Enter description here...
	 *
	 * @var int
	 */
	private $oneRound = null;
	
	/**
	 * Будет ли владелец соты, на которую летят, участвовать в евенте
	 *
	 * @var bool
	 */
	private $combOwnerParticipateInEvent = true;
	
	public function __construct()
	{
		parent::__construct();	
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $amount
	 */
	public function setZondsAmount($amount)
	{
		assert(is_int($amount) && $amount > 0);
		$this->zondsAmount = $amount;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return integer
	 */
	public function getZondsAmount()
	{
		return $this->zondsAmount;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DE_SpyAction $actionType
	 */
	public function setActionType(DE_SpyAction $actionType)
	{
		$this->actionType = $actionType;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_SpyAction
	 */
	public function getActionType()
	{
		return $this->actionType;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param GB_DatePeriod $period
	 */
	public function setPeriod(GB_DatePeriod $period)
	{
		$this->period = $period;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return GB_DatePeriod
	 */
	public function getPeriod()
	{
		return $this->period;
	}
	
	
	/**
	 * Enter description here...
	 *
	 * @param DE_Comb $combFrom
	 */
	public function setCombFrom(DE_Comb $combFrom)
	{
		$this->combFrom = $combFrom;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getCombFrom()
	{
		return $this->combFrom;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DE_Comb $combFrom
	 */
	public function setCombTo(DE_Comb $combFrom)
	{
		$this->combTo = $combFrom;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getCombTo()
	{
		return $this->combTo;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DE_Unit $unit
	 */
	public function setUnit(DE_Unit $unit)
	{
		$this->spyUnit = $unit;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Unit
	 */
	public function getUnit()
	{
		return $this->spyUnit;
	}
	
	public function setLogID($logID)
	{
		assert(is_int($logID) && $logID > 0);
		$this->logID = $logID;
	}
	
	public function getLogID()
	{
		return $this->logID;
	}
	
	public function setOneRound($oneRound)
	{
		assert(is_null($oneRound) || (is_int($oneRound) && $oneRound >= 0));
		$this->oneRound = $oneRound;
	}
	
	public function getOneRound($pg = false)
	{
		if($pg)
		{
			return $this->oneRound == 1 ? 'true' : 'false';
		} else {
			return $this->oneRound;
		}
	}
	
	public function setNeedChangeAttributes($needChange)
	{
		assert(is_bool($needChange));
		$this->needChangeAttributes = $needChange;
	}
	
	public function needChangeAttributes()
	{
		return $this->needChangeAttributes;
	}
	
	public function setCombtoOwnerParticipateInEvent($participate)
	{
		assert(is_bool($participate));
		
		$this->combOwnerParticipateInEvent = $participate;
	}
	
	public function getCombtoOwnerParticipateInEvent()
	{
		return $this->combOwnerParticipateInEvent;
	}
}

?>