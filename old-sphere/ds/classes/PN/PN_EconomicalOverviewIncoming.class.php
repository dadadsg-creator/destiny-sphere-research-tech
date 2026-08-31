<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2010/07/07 08:54:12 $';

// закладка убежища, обзор производтва на соте
class PN_EconomicalOverviewIncoming extends UA_GetBuildMenu
{
    private $outData;

    private $combAttributes;

    private $ba = array();
    private $modifierValue;

    public function __construct()
    {
        parent::UA_GetBuildMenu ();
    }

    public function getPanel ()
    {
        assert(key_exists('profi_game', $_SESSION));
        assert($_SESSION['profi_game'] == 1);

        $this->outData = new TI_EconomicalOverviewIncoming($this->bid, $this->tab);

        $this->combAttributes= DE_CombAttributes::CreateFromCombID((int)$this->comb);

        $this->combObject = DE_Comb::CreateFromID((int)$this->comb);

        $this->modifierValue = DE_WorldSettings::CreateForCurentWorld()->get('LOCAL_ARTEFACT_OLD_STYLE');

        if ($this->modifierValue) {
            $combProductionBonuses = DE_CombProductionBonus::CreateFromCombID($this->comb);
            foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
                $this->ba[$resourceName] = $combProductionBonuses->getBonus ($resourceName, 'BONUS_ABSOLUTE') + $bonusAbsolute * GL_CountCombProductionOfResource::CountWorldBonus($this->combObject, $resourceName);
        }
        else {
            foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
                $this->ba[$resourceName] = 0;
        }

        $this->_getBaseProduction();
        $this->_getLocalArtBonus();
        $this->_getResearchBonus();
        $this->_getCombArtBonus();
        $this->_getComplexArtBonus();
        $this->_getAlianceArtBonus();
        $this->_getReligionBonus();
        $this->_getRaceBonus();
        $this->_getWorldBonus();
        $this->_getPersonalBonus();
        $this->_getColonyBonus();
        $this->_formSummary();

        SU_OutputHandler::Singleton()->addString(TL_MenuEconomicalOverviewIncoming::FormMenu($this->outData), 'xmldata');

        return true;
    }

    public function _formSummary()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $out = (float)GL_CountCombProductionOfResource::Count($this->combObject, $resourceName);
            $this->outData->deltaParam($resourceName, 'TOTAL_PRODUCTION', $out);
        }
    }

    private function _getBaseProduction()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $rs = DS_Economical::GetBaseResourceProduction((int)$this->comb, $resourceName);
            $this->outData->setParam($resourceName, 'BASE_PRODUCTION', $this->combAttributes->getAttribute('TURN_PRODUCTION', $resourceName));
            //$this->outData->setParam($resourceName, 'BASE_PRODUCTION', (float)$rs['production']);
            $this->outData->setParam($resourceName, 'VENDORS_COUNT', (float)$rs['colvo']);
        }
    }

    private function _getResearchBonus()
    {
        $baseBonus = (float)$this->worldSettings->get('RESEARCH_BASE_BONUS');

        // бонус науки
        $res = DS_Economical::GetResearchBonus($this->params['uiid']);

        if(count($res)>0)
        {
            foreach($res as $k=>$v)
            {
                $researchBonus = ($baseBonus / $v['exchangerate']) * ($v['level'] - 1);
                $bonus = ($this->outData->getParam($v['attributename'], 'BASE_PRODUCTION') + $this->ba[$v['attributename']]) * $researchBonus;

                $this->outData->setParam($v['attributename'], 'RESEARCH_BONUS', (float)$bonus);
            }
        }else{
            foreach($this->resources as $k=>$v)
            {
                $this->outData->setParam($v, 'RESEARCH_BONUS', (float)0);
            }
        }
    }

    private function _getLocalArtBonus()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $absolute = DS_Economical::GetLocalArtBonusForVendorBuilding($this->comb, $resourceName, 'BONUS_ABSOLUTE');
            $relative = DS_Economical::GetLocalArtBonusForVendorBuilding($this->comb, $resourceName, 'BONUS_RELATIVE');

            $bonus = (float) ($this->outData->getParam($resourceName, 'BASE_PRODUCTION') + $this->ba[$resourceName]) * $relative + $absolute;

            $this->outData->setParam($resourceName, 'LOCAL_ARTS_BONUS', $bonus);
        }
    }

    private function _getCombArtBonus()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $absolute = DS_Economical::GetBuildingArtResorceBonus($this->comb, 'ARTEFACT_TOWER', $resourceName, 'BONUS_ABSOLUTE', 'COMB');
            $relative = DS_Economical::GetBuildingArtResorceBonus($this->comb, 'ARTEFACT_TOWER', $resourceName, 'BONUS_RELATIVE', 'COMB');

            $bonus = ($this->outData->getParam($resourceName, 'BASE_PRODUCTION') +$this->ba[$resourceName]) * $relative + $absolute;

            $this->outData->setParam($resourceName, 'COMB_ART_BONUS', (float)$bonus);
        }
    }


    private function _getComplexArtBonus()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $absolute = DS_Economical::GetComplexArtefactResourceBonus($this->comb, $resourceName, 'BONUS_ABSOLUTE');
            $relative = DS_Economical::GetComplexArtefactResourceBonus($this->comb, $resourceName, 'BONUS_RELATIVE');

            $bonus = ($this->outData->getParam($resourceName, 'BASE_PRODUCTION') + $this->ba[$resourceName]) * $relative + $absolute;

            $this->outData->setParam($resourceName, 'COMPLEX_ART_BONUS', (float)$bonus);
        }
    }

    private function _getAlianceArtBonus()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $absolute = DS_Economical::GetAlianceArtefactResourceBonus($this->params['uiid'], $resourceName, 'BONUS_ABSOLUTE');
            $relative = DS_Economical::GetAlianceArtefactResourceBonus($this->params['uiid'], $resourceName, 'BONUS_RELATIVE');

            $bonus = ($this->outData->getParam($resourceName, 'BASE_PRODUCTION') + $this->ba[$resourceName]) * $relative + $absolute;

            $this->outData->setParam($resourceName, 'ALIANCE_ART_BONUS', (float)$bonus);
        }
    }

    private function _getReligionBonus()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $absolute = DS_Economical::GetReligionResourceBonus($this->params['uiid'], $resourceName, 'BONUS_ABSOLUTE');
            $relative = DS_Economical::GetReligionResourceBonus($this->params['uiid'], $resourceName, 'BONUS_RELATIVE');

            $bonus = ($this->outData->getParam($resourceName, 'BASE_PRODUCTION') + $this->ba[$resourceName]) * $relative + $absolute;

            $this->outData->setParam($resourceName, 'RELIGION_BONUS', (float)$bonus);
        }
    }

    private function _getRaceBonus()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $absolute = DS_Economical::GetRaceResourceBonus($this->params['uiid'], $resourceName, 'BONUS_ABSOLUTE');
            $relative = DS_Economical::GetRaceResourceBonus($this->params['uiid'], $resourceName, 'BONUS_RELATIVE');

            $bonus = ($this->outData->getParam($resourceName, 'BASE_PRODUCTION') + $this->ba[$resourceName]) * $relative + $absolute;

            $this->outData->setParam($resourceName, 'RACE_BONUS', (float)$bonus);
        }
    }

    private function _getWorldBonus()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $relative = GL_CountCombProductionOfResource::CountWorldBonus($this->combObject, $resourceName);
            $bonus = ($this->outData->getParam($resourceName, 'BASE_PRODUCTION') + DS_Economical::GetLocalArtBonusForVendorBuilding($this->comb, $resourceName, 'BONUS_ABSOLUTE')) * $relative;
            $this->outData->setParam($resourceName, 'WORLD_BONUS', (float)$bonus);
        }
    }

    /**
     * ¬ременый бонус пользовател€ (покупаетс€)
     *
     */
    private function _getPersonalBonus()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $combProduction = (float)GL_CountCombProductionOfResource::Count($this->combObject, $resourceName);
            $bonusColony = GL_CountCombProductionOfResource::CountColonyBonus($this->combObject, $resourceName);
            $relative = DS_Economical::GetUserBonus($this->combObject->getUserInstance(), $resourceName);
            
            $bonusColony = $combProduction * $bonusColony / (1 + $bonusColony);

            $bonus = ($combProduction - $bonusColony)* $relative / (1 + $relative);

            $this->outData->setParam($resourceName, 'PERSONAL_BONUS', (float)$bonus);
        }
    }

    /**
     * Ѕонус производства на захваченной колонии
     *
     */
    private function _getColonyBonus()
    {
        foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
        {
            $combProduction = (float)GL_CountCombProductionOfResource::Count($this->combObject, $resourceName);
            $relative = GL_CountCombProductionOfResource::CountColonyBonus($this->combObject, $resourceName);

            $bonus = $combProduction * $relative / (1 + $relative);

            $this->outData->setParam($resourceName, 'COLONY_BONUS', (float)$bonus);
        }
    }
}



?>