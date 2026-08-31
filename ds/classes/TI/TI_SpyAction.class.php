<?php $CVS_Version = '$Revision$ ; $Date$';

class TI_SpyAction extends TI_BaseMenu 
{
	public $freeZondsCount;
	
	public $repairZondsCount;
	
	public $inDefenceZondsCount;
	
	public $inResearchZondsCount;
	
	
	public $maxDefenceGroupSize;
	
	public $maxDefenceTime;
	
	public $maxDefenceGroups;
	
	
	public $maxResearchGroupSize;
		
	public $maxResearchGroups;
	
	
	public $groups = array();
	
	public $groupsInDefence = array();
	
	public $groupsInResearch = array();
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Unit
	 */
	public $zondUnit;
	
	public $turnTime;
	
	public $currrentUnit;
		
	public $actions = array();
	
	public $maxSpeed;	
	
	public $currentRing;	
	
	function __construct($buildingID, $tabName)
	{
		parent::__construct($buildingID, $tabName);
	}
	
	public function addAction(DE_SpyAction $action)
	{
		$this->actions[] = $action;
	}
	
	public function setZondUnit(DE_Unit $unit)
	{
		$this->zondUnit[] = $unit;
		
		$this->currrentUnit = $unit->getName();
	}
	
	
	public function addGroup(DE_SpyZondGroup $group)
	{
		$this->groups[] = $group;
	}
	
	
	public function addGroupInDefence($zondCount, GB_Date $endTime)
	{
		assert(is_int($zondCount) && $zondCount > 0);
		
		$this->groupsInDefence[] = array('AMOUNT' => $zondCount, 'END_TIME' => $endTime);
	}
	
	public function addGroupInResearch($zondCount, GB_Date $endTime, DE_Comb $combTo, $action, $logID)
	{
		assert(is_int($zondCount) && $zondCount > 0);
		assert(is_int($logID) && $logID >= 0);
		assert(in_array($action, array('MOVE_TO', 'MOVE_FROM')));
		
		$this->groupsInResearch[] = array('AMOUNT' => $zondCount, 'END_TIME' => $endTime, 'COMB' => $combTo, 'ACTION' => $action, 'LOG' => $logID);
	}
	
	
	
	public function setMaxDefenceGroupSize($size)
	{
		assert(is_int($size) && $size >= 0);
		
		$this->maxDefenceGroupSize = $size;
	}
	
	public function setMaxDefenceTime($size)
	{
		assert(is_int($size) && $size >= 0);
		
		$this->maxDefenceTime = $size;
	}
	
	public function setMaxDefenceGroups($size)
	{
		assert(is_int($size) && $size >= 0);
		
		$this->maxDefenceGroups = $size;
	}
	
	
	
	public function setMaxResearchGroupSize($size)
	{
		assert(is_int($size) && $size >= 0);
		
		$this->maxResearchGroupSize = $size;
	}
	
	public function setMaxResearchGroups($size)
	{
		assert(is_int($size) && $size >= 0);
		
		$this->maxResearchGroups = $size;
	}
	
	
	
	public function setFreeZondsCount($count)
	{
		assert(is_int($count) && $count >= 0);
		
		$this->freeZondsCount[$this->currrentUnit] = $count;
	}
	
	public function setRepairZondsCount($count)
	{
		assert(is_int($count) && $count >= 0);
		
		$this->repairZondsCount[$this->currrentUnit] = $count;
	}
	
	public function setInDefenceZondsCount($count)
	{
		assert(is_int($count) && $count >= 0);
		
		$this->inDefenceZondsCount[$this->currrentUnit] = $count;
	}
	
	public function setInResearchZondsCount($count)
	{
		assert(is_int($count) && $count >= 0);
		
		$this->inResearchZondsCount[$this->currrentUnit] = $count;
	}
	
	public function setTurnTime($turnTime)
	{
		assert(is_float($turnTime) && $turnTime > 0);
		$this->turnTime = $turnTime;
	}
	
	public function setMaxSpeed($speed)
	{
		assert(is_int($speed) && $speed > 0);
		$this->maxSpeed = $speed;
	}
	
	public function setCurrentRing($ring)
	{
		assert(is_int($ring) && $ring > 0);
		$this->currentRing = $ring;
	}
}

?>