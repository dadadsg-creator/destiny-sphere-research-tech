<?php $CVS_Version = '$Revision$ ; $Date$';

class TI_EconomicalOverview extends TI_BaseMenu 
{	

	private $baseString = array(
		'BUILDINGS_COUNT' => 0,
		'BASE_PRODUCTION' => 0,
		'SUMMARY_PRODUCTION' => 0,
		'SUMMARY_USING' => 0,		
		'BALANCE' => 0
	);
	
	private $statistic = array();
	
	public function __construct($buildingID, $tabName)
	{
		parent::__construct($buildingID, $tabName);
		
		$this->_initStat();
		
		$this->_invariant('construct');
	}
	
	public function getStatistic()
	{
		return $this->statistic;
	}
	
	public function setParam($resourceName, $paramName, $value)
	{
		assert(key_exists($paramName, $this->baseString));		
		assert(in_array($resourceName, GB_ResourcesList::$allResources));
		assert(is_float($value));

		assert(key_exists($resourceName, $this->statistic));	
		assert(key_exists($paramName, $this->statistic[$resourceName]));

		$this->statistic[$resourceName][$paramName] = $value;
	}
	
	public function deltaParam($resourceName, $paramName, $delta)
	{
		assert(key_exists($paramName, $this->baseString));		
		assert(in_array($resourceName, GB_ResourcesList::$allResources));
		assert(is_float($delta));

		assert(key_exists($resourceName, $this->statistic));	
		assert(key_exists($paramName, $this->statistic[$resourceName]));

		$this->statistic[$resourceName][$paramName] += $delta;
	}
	
	public function getParam($resourceName, $paramName)
	{
		assert(key_exists($paramName, $this->baseString));		
		assert(in_array($resourceName, GB_ResourcesList::$allResources));		

		assert(key_exists($resourceName, $this->statistic));	
		assert(key_exists($paramName, $this->statistic[$resourceName]));

		return $this->statistic[$resourceName][$paramName];
	}
		
	private function _initStat()
	{		
		foreach(GB_ResourcesList::$allResources as $k=>$resourcName)
		{			
			$this->statistic[$resourcName] = $this->baseString;
		}
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
		
		assert(is_array($this->statistic));
		assert(count($this->statistic) == count(GB_ResourcesList::$allResources));
	}
	
}

?>