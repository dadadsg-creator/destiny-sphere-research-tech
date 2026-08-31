<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка убежища, обзор производства/потребления на соте

class PN_EconomicalOverviewOutcoming extends UA_GetBuildMenu 
{
    private $statistic;
    
    private $resourcesNotForOut = array();
    
    static private $resourceColours = array
    (
        'FOOD' =>  '#63B263',
        'KADERIUM' => '#FFC77B',
        'PRODIUM' => '#9C9E4A',
        'NANO_CRYSTAL' => '#6396E7',
        'STASIS_ENERGY' =>  '#B5B28C'       
    );
    
    private $baseStrings = array(
        'STAT_NON_PRODUCTION_BUILDINGS' => NULL,
        'STAT_KADERIUM_PRODUCTION' => NULL,
        'STAT_NANO_CRYSTAL_PRODUCTION' => NULL,
        'STAT_FOOD_PRODUCTION' => NULL,
        'STAT_PRODIUM_PRODUCTION' => NULL,
        'STAT_STASIS_ENERGY_PRODUCTION' => NULL,
        'STAT_WORKERS' => NULL,
        'STAT_SCIENTISTS' => NULL,
        'STAT_ARHIOLOGYSTS' => NULL,
        'STAT_ARMY' => NULL,    
        'STAT_SUMMARY' => NULL          
    );  
    
    private $combObject;
    
    private $archiolog;
    
    private $scientist;
    
    private $worker;
    
    public function __construct()
    {
        parent::UA_GetBuildMenu ();
        
        $this->_initStat();
        
        assert(key_exists('lang', $_SESSION));
        $this->lang = (int)$_SESSION['lang'];
        
        $this->combObject = DE_Comb::CreateFromID((int)$this->comb);
        $this->archiolog = DE_Unit::CreateFromName('ARCHEOLOGIST');
        $this->scientist = DE_Unit::CreateFromName('SCIENTIST');
        $this->worker = DE_Unit::CreateFromName('WORKER');      
                
        
        $this->_invariant();
    }

    public function getPanel () 
    {   
        assert(key_exists('profi_game', $_SESSION));
        assert($_SESSION['profi_game'] == 1);   
                
        $this->_invariant();
            
        $this->_getWokersCosts();
        $this->_getArchiologystCosts();
        $this->_getScientistsCosts();
        
        $this->_getResourceCost('STAT_KADERIUM_PRODUCTION', 'KADERIUM', 'STASIS_ENERGY');
        $this->_getResourceCost('STAT_NANO_CRYSTAL_PRODUCTION', 'NANO_CRYSTAL', 'STASIS_ENERGY');
        $this->_getResourceCost('STAT_FOOD_PRODUCTION', 'FOOD', 'STASIS_ENERGY');
        $this->_getResourceCost('STAT_PRODIUM_PRODUCTION', 'PRODIUM', 'STASIS_ENERGY');
        $this->_getResourceCost('STAT_STASIS_ENERGY_PRODUCTION', 'STASIS_ENERGY', 'STASIS_ENERGY');
        
        $this->_getSimplBuildingsCosts('STASIS_ENERGY');
        $this->_getSimplBuildingsCosts('FOOD');
        
        $this->_getArmyUpkeep();
        
        foreach($this->statistic as $stringName=>$param)
        {
            if($stringName == 'STAT_SUMMARY')
            {
                continue;
            }           
            
            foreach($param as $resource => $value)
            {
                $this->statistic['STAT_SUMMARY'][$resource] += $value;
            }
        }       

        $out = $this->_createView();    
        
        SU_OutputHandler::Singleton()->addString($out, 'xmldata');  

        return true;    
    }
    
    private function _getWokersCosts()
    {       
        $val = $this->combObject->getAttributes()->getAttribute('CURRENT_AMOUNT', 'WORKER', true) *
               $this->worker->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD');     

        $this->statistic['STAT_WORKERS']['FOOD'] = $val;
    }
    
    private function _getScientistsCosts()
    {       
        $val = $this->combObject->getAttributes()->getAttribute('CURRENT_AMOUNT', 'SCIENTIST', true) *
               $this->scientist->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD');
        $this->statistic['STAT_SCIENTISTS']['FOOD'] = $val;
    }
    
    
    private function _getArchiologystCosts()
    {       
        $val = (
                    $this->combObject->getAttributes()->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', true) +
                    $this->combObject->getAttributes()->getAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', true)
               ) * $this->archiolog->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD');
        $this->statistic['STAT_ARHIOLOGYSTS']['FOOD'] = $val;
    }
    
    private function _getSimplBuildingsCosts($cost)
    {       
        assert(in_array($cost, GB_ResourcesList::$allResources));   
        
        $rs = DS_Economical::GetSimplBuildingsCost($this->comb, $cost);
        
        $this->statistic['STAT_NON_PRODUCTION_BUILDINGS'][$cost] = $rs;
    }
    
    private function _getArmyUpkeep()
    {
        $upkeep = GL_LiveUpkeep::CountComb($this->combObject);
        
        foreach(GB_ResourcesList::$allResources as $k=>$resorceName)
        {
            if(key_exists($resorceName, $upkeep))
            {
                $this->statistic['STAT_ARMY'][$resorceName] = $upkeep[$resorceName];
            }           
        }       
    }
    
    private function _getResourceCost($baseKey, $resource, $cost)
    {
        assert(in_array($resource, GB_ResourcesList::$allResources));
        assert(in_array($cost, GB_ResourcesList::$allResources));
        assert(!empty($baseKey));
        
        $rs = DS_Economical::GetResourceProductionCost ($this->comb, $resource, $cost);
        
        $this->statistic[$baseKey][$cost] = $rs;
    }
    
    private function _createView()
    {
        //$localization = DE_InterfaceLocalization::CreateForCurentLang();
        
        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><table/>\n";
        $doc = domxml_open_mem($xml);
        $root = $doc->document_element();   
                
        $out = "<table>";       
        $out .= "<tr><td width=\"38\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" alpha=\"50\"> - </td>";       
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            if(in_array($resourceName, $this->resourcesNotForOut))
            {
                continue;
            }
            $out .= "<img width=\"12\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" alpha=\"80\">".$resourceName."</img>";
        }
        $out .= "</tr>";
        
        foreach($this->baseStrings as $paramName => $v)
        {
            //$localization->get($paramName)
            $out .= "<tr>";
            $out .= "<td width=\"38\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" alpha=\"80\"><FONT COLOR=\"#FFFFFF\" FACE=\"Arial\"><b>{:".$paramName.":}</b></FONT></td>";
            foreach(GB_ResourcesList::$allResources as $k => $resourceName)
            {
                if(in_array($resourceName, $this->resourcesNotForOut))
                {
                    continue;
                }
                
                $out .= "<td width=\"12\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" style=\"center\" alpha=\"80\"><b><FONT COLOR=\"".self::$resourceColours[$resourceName]."\" FACE=\"Arial\">".number_format(ceil($this->statistic[$paramName][$resourceName]), 0, '.', ' ')."</FONT></b></td>";
            }
            $out .= "</tr>";
        }               
                
        // закладки
        $this->getBuildingTabs($root, $this->bid, $this->lang, $this->tab);
                        
        $forOut = str_replace('<table>', $out, $doc->dump_mem());
        
        return $forOut;         
    }
    
    private function _initStat()
    {       
        foreach($this->baseStrings as $string=>$v)
        {           
            foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
            {
                $this->statistic[$string][$resourceName] = 0;
            }
            
        }
    }
    
    private function _testStat()
    {
        //assert(count($this->statistic) == count($this->baseStrings));         
    }
    
    protected function _invariant()
    {       
        assert(is_array($this->baseStrings) && count($this->baseStrings)>0);
        
        assert($this->combObject instanceof DE_Comb);
        assert($this->worker instanceof DE_Unit);
        assert($this->archiolog instanceof DE_Unit);
        assert($this->scientist instanceof DE_Unit);
        
        $this->_testStat();
    }
    
    
}



?>