<?php $CVS_Version = '$Revision$ ; $Date$';

class TI_EconomicalOverviewIncoming extends TI_BaseMenu 
{   

    private $baseString = array(
        'VENDORS_COUNT' => 0,
        'BASE_PRODUCTION' => 0,     
        'RESEARCH_BONUS' => 0,
        'LOCAL_ARTS_BONUS' => 0,
        'COMB_ART_BONUS' => 0,
        'COMPLEX_ART_BONUS' => 0,
        'ALIANCE_ART_BONUS' => 0,
        'RELIGION_BONUS' => 0,
        'RACE_BONUS' => 0,
        'WORLD_BONUS' => 0,
        'PERSONAL_BONUS' => 0,
        'COLONY_BONUS' => 0,
        'TOTAL_PRODUCTION' => 0
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