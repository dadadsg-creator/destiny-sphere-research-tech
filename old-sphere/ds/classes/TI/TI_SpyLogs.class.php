<?php $CVS_Version = '$Revision$ ; $Date$';

class TI_SpyLogs extends TI_BaseMenu 
{
	public $logs = array();
	
	public $actions = array();
	
	public $combs = array();
	
	
	public $selectedActions = array();
	
	public $selectedCombs = array();
	
	public $userName = '';
	
	
	public $time = '4:00:00:00';
	
	public $timeMin;
	
	public $timeMax;
	
	
	function __construct($buildingID, $tabName)
	{
		parent::__construct($buildingID, $tabName);
	}
	
	public function addLog(DE_SpyLog $log)
	{
		$this->logs[] = $log;
	}
	
	public function addAction(DE_SpyAction $action)
	{
		$this->actions[] = $action;
	}
	
	public function addComb(DE_Comb $comb)
	{
		$this->combs[] = $comb;
	}
	
	public function setSelectedAction($actionID)
	{
		assert(is_int($actionID) && $actionID > 0);
		$this->selectedActions[] = $actionID;
	}
	
	public function setSelectedColony($combID)
	{
		assert(is_int($combID) && $combID > 0);
		$this->selectedCombs[] = $combID;
	}
	
	
	public function setUserName(DE_User $user)
	{
		$this->userName = $user->getName();
	}
	
	public function setTime(GB_TimePeriod $period)
	{
		$periodParts = $period->getWithParts();
		$this->time = implode(':', $periodParts);
	}
	
	public function setTimeMin($time)
	{
		assert(is_string($time));
		$this->timeMin = $time;
	}
	
	public function setTimeMax($time)
	{
		assert(is_string($time));
		$this->timeMax = $time;
	}
}

?>