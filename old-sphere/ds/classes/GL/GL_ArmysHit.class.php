<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_ArmysHit
{
    static public function Count (DE_Army $armyAttack, DE_CombDefendArmy $armyDefend)
    {

        //”дар атакующего
        GL_ArmysHit::ArmyHitArmy($armyAttack, $armyDefend);
                
        //”дар оборон€ющегос€
        GL_ArmysHit::ArmyHitArmy($armyDefend, $armyAttack);
        
        /*
        //ќбсчет потерь у атакующего
        GL_ArmysHit::countLoses($armyAttack);
        
        //ќбсчет потерь у оборон€ющегос€
        GL_ArmysHit::countLoses($armyDefend);
        */
                
    }
    
    static private function ArmyHitArmy(DE_ArmyInterface $armyFrom, DE_ArmyInterface $armyTo)
    {
        $armyFrom->getUnitsGroupCollection()->resetNext();
        while($unitGroup = $armyFrom->getUnitsGroupCollection()->next())
        {
            GL_ArmysHit::groupHitToArmy($unitGroup, $armyTo);
        }
    }
    
    static private function groupHitToArmy(DE_UnitsGroupInterface $unitGroupHited, DE_ArmyInterface $armyTo)
    {
        $armyTo->getUnitsGroupCollection()->resetNext();
        while($unitGroup = $armyTo->getUnitsGroupCollection()->next())
        {                       
            $hitRecived = new DE_UnitsGroupHitRecived($unitGroupHited->outputHit(), GL_SeparateDemageBetweenUnitGroups::separate($unitGroup));
            $unitGroup->reciveHit($hitRecived);
        }
    }
    
    static private function countLoses(DE_ArmyInterface $army)
    {
        $army->getUnitsGroupCollection()->resetNext();
        while($unitGroup = $armyTo->getUnitsGroupCollection()->next())
        {                       
            
        }
    }
    
}

?>