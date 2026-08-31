<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка убежища, обзор производтва на соте
class PN_EconomicalOverview extends UA_GetBuildMenu 
{
    private $outData;   
    
    private $combAttributes;
    
    private $combBonuses;
    
    public function __construct()
    {
        parent::UA_GetBuildMenu ();
    }

    public function getPanel () 
    {   
        $this->outData = new TI_EconomicalOverview($this->bid, $this->tab);     
        
        $this->combObject = DE_Comb::CreateFromID((int)$this->comb);
        $this->combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
        $this->combBonuses = DE_CombProductionBonus::CreateFromCombID((int)$this->comb);                            
        $this->archiolog = DE_Unit::CreateFromName('ARCHEOLOGIST');
        $this->scientist = DE_Unit::CreateFromName('SCIENTIST');
        $this->worker = DE_Unit::CreateFromName('WORKER');  
        
        $this->_getProduction();        
        $this->_getUsing();     
        $this->_formSummary();
                    
        SU_OutputHandler::Singleton()->addString(TL_MenuEconomicalOverview::FormMenu($this->outData), 'xmldata');       
        
        return true;
    }   
        
    private function _formSummary()
    {       
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {   
            $out = 
                $this->outData->getParam($resourceName, 'SUMMARY_PRODUCTION')
                -
                $this->outData->getParam($resourceName, 'SUMMARY_USING');
            
            $this->outData->deltaParam($resourceName, 'BALANCE', $out);                             
        }
    }
    
    private function _getProduction()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {           
            $baseProduction = $this->combAttributes->getAttribute('TURN_PRODUCTION', $resourceName);
                        
            $bonusProduction = GL_CountCombProductionOfResource::Count($this->combObject, $resourceName);           

            $this->outData->setParam($resourceName, 'BASE_PRODUCTION', (float)$baseProduction);
                        
            $this->outData->setParam($resourceName, 'SUMMARY_PRODUCTION', (float)$bonusProduction);
            
            $rs = DS_Economical::GetBaseResourceProduction($this->comb, $resourceName);
            $buildingsCount = (float)$rs['colvo'];
            
            $this->outData->setParam($resourceName, 'BUILDINGS_COUNT', (float)$buildingsCount);                             
        }   
    }
    
    private function _getUsing()
    {
        //Инициалиировавлаи массив апкипом армии
        $summaryUpkeep = GL_LiveUpkeep::CountComb($this->combObject);
        
        //Учли потребление энергии здагниями
        $rs = DS_Economical::GetCombBuildingsTurnUpkeep($this->comb);
        foreach ($rs as $row) {
            $summaryUpkeep[$row['attributename']] += $row['upkeep'];
        }
                
        //Учли потредение еы архами и рабочими
        $foodUsing = 
                $this->combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER') 
                *
                $this->worker->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD')
                +
                $this->combAttributes->getAttribute('CURRENT_AMOUNT', 'SCIENTIST') 
                *
                $this->scientist->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD')
                +
                (
                    $this->combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', true) 
                    +
                    $this->combAttributes->getAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', true)
                ) 
                * 
                $this->archiolog->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD');             
        $summaryUpkeep['FOOD'] += $foodUsing;
                
        //Занчели суммарные значения
        foreach($summaryUpkeep as $resourceName => $value)
        {
            $this->outData->setParam($resourceName, 'SUMMARY_USING', (float)$value);        
        }
                    
    }
    
}



?>