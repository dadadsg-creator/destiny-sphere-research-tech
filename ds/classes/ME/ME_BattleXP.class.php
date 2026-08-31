<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_BattleXP
{
    public static function Add(DE_Battle $battle)
    {
        //$battleXP = GL_BattleXP::Count($battle);
        $battleXP = ME_BattleXPManager::Count($battle);

        //SU_DebugTools::LogAll(GL_BattleXP_new::$debugInfo, __CLASS__, 0);

        if ($battle->getDefender()->getUserInstance()->getUser()->getIsUserNpc() != 't') {
            $battle->getAttacker()->getUserInstance()->getBattleXP()->deltaXP($battleXP['ATTACKER']);
            $battle->getAttacker()->getUserInstance()->getBattleXP()->deltaXPAbsolute($battleXP['ATTACKER']);
        }
        else {
            $battle->getAttacker()->getUserInstance()->getBattleXP()->deltaNpcXP($battleXP['ATTACKER']);
        }
        $battle->getAttacker()->setBattleXP($battleXP['ATTACKER']);

        $defendersCollection = $battle->getDefendersCollection();
        $defendersCollection->resetNext();
        while($defender = $defendersCollection->next())
        {
            // Для захватываемых колоний
            if ($defender->getUserInstance()->getUserInstanceID() == 0) {
                continue;
            }

            // Если данный защитник получил БО в этом бою
            if (key_exists($defender->getID(), $battleXP['DEFENDER'])) {
                $deltaXP = (float)$battleXP['DEFENDER'][$defender->getID()];
                if ($battle->getAttacker()->getUserInstance()->getUser()->getIsUserNpc() != 't') {
                    $defender->getUserInstance()->getBattleXP()->deltaXP($deltaXP);
                    $defender->getUserInstance()->getBattleXP()->deltaXPAbsolute($deltaXP);
                }
                else {
                    $defender->getUserInstance()->getBattleXP()->deltaNpcXP($deltaXP);
                }
                $defender->setBattleXP($deltaXP);
            }
        }

        return $battleXP;
    }//END OF METHOD

}


?>