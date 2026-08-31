<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CombArmyArtefactBonus
{
    static public function BaseComb(DE_ArmyInterface $army)
    {
        if ($army->getInAction())
        {
            switch ($army->getArmyAction()->getActionType()) 
            {
                case 'IN_BATTLE': //Уже вваезеалось в бой на чужой соте
                    return $army->getBaseComb();
                    break;
                case 'IN_DEFEND': //Стоит в обороне на соте
                    return $army->getCurentComb();
                    break;                  
                case 'MOVE_TO_ATTACK': //Это когда еще тока на подезде армия и сейчас вбоя тока вступает
                    return $army->getBaseComb();
                    break;
                case 'MOVE_FROM':
$str = 'Unkhown army action name = '.$army->getArmyAction()->getActionType()." for army ".$army->getName()."(".$army->getID().")";
SU_DebugTools::LogAll($str, 'MOVE_FROM.log');
                    return $army->getBaseComb();
                    break;
                default:
                    return $army->getBaseComb();
                    // ds_error('Unkhown army action name = '.$army->getArmyAction()->getActionType()." for army ".$army->getName()."(".$army->getID().")");
            }
        }
        else 
        {
            //эта армия стоит на домашней соте
            return $army->getBaseComb();
        }
    
    }
}

?>