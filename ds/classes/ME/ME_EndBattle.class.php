<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_EndBattle
{
    static public function Exec(DE_Battle $battle, DE_EndBattleTestResult $reason, GB_Date $endBattleDate, $capture = false)
    {
        //Обрабатываем события, связаные с окончанием боя
        self::_HendlerOccurrences($battle);

        // Поставить дату окончания боя
        $battle->setEndDate($endBattleDate);

        //Пометить победителя
        $battle->setWinner($reason);

        // Если победил атакующий, и мы не захватили колонию, то начинаться грабеж.
        if($reason->getWinnerCode()->value == 'A' && !$capture)
        {
            ME_ResourceStealing::LoadResource($battle);
        }

        // Награда тому, чей уровень меньше
        self::_RewardNewb($battle);
    }

    static private function _HendlerOccurrences(DE_Battle $battle)
    {
        // Перебираем обороняющихся
        $defendersCollection = $battle->getDefendersCollection();
        $defendersCollection->resetNext();
        while($defender = $defendersCollection->next())
        {
            // Перебераем армии обороняющегося
            $partycipantArmysCollection = $defender->getArmiesColletion();
            $partycipantArmysCollection->resetNext();
            while($participantArmy = $partycipantArmysCollection->next())
            {
                // Если армия умерла или не защищает эту соту или участвует в какм-нибудь бое
                if (
                // Проверяем, есть ли обьект DE_Army, так как эту армию уже могли убить в другом бое
                GB_SingletonObjectStorage::GetIFExists('DE_Army', $participantArmy->getArmyID()) === false ||
                GB_SingletonObjectStorage::HaveObjectInGraveYard('DE_Army', $participantArmy->getArmyID()) === true ||
                // Проверяем, нет ли номера раунда, в котором армия погибла
                $participantArmy->getDieRoundNumber() > 0 ||
                // Смотрим, стоит ли армия на защите
                $participantArmy->getArmy()->getArmyAction()->getActionType()  != 'IN_DEFEND' ||
                // Смотрим, не участвует ли эта армия еще в каком-нибудь бое
                count (DS_Battle::NotEndedBattlesOnComb($participantArmy->getArmy()->getCurentComb())) > 1) {
                    continue;
                }

                //Обрабатываем события, связанные с окончанием боя
                ME_ArmyOccurrence::HendlerOccurrence($participantArmy->getArmy(), 'END_BATTLE');
            }
        }
    }


    static private function _RewardNewb(DE_Battle $battle)
    {
        // Если включено вознаграждение за бой со старшим уровнем
        if (DE_WorldSettings::CreateForCurentWorld()->get('REWARD_NEWB_ENABLED') == 1) {

            // Id атакера и защитника
            $attInstance = $battle->getFromComb()->getUserInstance();
            $defInstance = $battle->getComb()->getUserInstance();
            $ataker = $attInstance->getID();
            $defender = $defInstance->getID();
            $attIsNPC = $attInstance->getUser()->getIsUserNpc() == 't';
            $defIsNPC = $defInstance->getUser()->getIsUserNpc() == 't';

            if ($attIsNPC) {
                $recipient =  'ATTACKER';
                $rewardClass = DE_Reward::CreateFromUserInstanceID($defender);
                $leagueDiff = max(0, $defInstance->getLeagueID() - $attInstance->getLeagueID());
            }
            else if ($defIsNPC) {
                $recipient =  'DEFENDER';
                $rewardClass = DE_Reward::CreateFromUserInstanceID($ataker);
                $leagueDiff = max(0, $attInstance->getLeagueID() - $defInstance->getLeagueID());
            }
            else {
                // Смотрим разницу в уровнях
                $atakerLevel = DE_Ranking::CreateFromID($ataker)->getLevel();
                $defenderLevel = DE_Ranking::CreateFromID($defender)->getLevel();
                            
                if ($atakerLevel > $defenderLevel) {
                    // Если трофеи не начисляются при защите соты третьими игроками 
                    $defendEnabled = DE_WorldSettings::CreateForCurentWorld()->get('REWARD_NEWB_WITH_DEFENDER_ENABLED');
                    
                    if ($defendEnabled != 1) {
                            // Если соту кто-то защищал
                            $defendersCollection = $battle->getDefendersCollection();
                            $defendersCollection->resetNext();
                            while($defenderClass = $defendersCollection->next())
                            {
                                if ($defenderClass->getUIID() != $defender) {
                                    return;
                                }
                            }
                    }
                    
                    // считаем потери атакера
                    $recipient =  'ATTACKER';
                    $rewardClass = DE_Reward::CreateFromUserInstanceID($defender);
                } elseif ($defenderLevel > $atakerLevel) {
                    // считаем потери защитника
                    $recipient =  'DEFENDER';
                    $rewardClass = DE_Reward::CreateFromUserInstanceID($ataker);
                } else {
                    // Если уровни равны, выходим
                    return;
                }
            }

            // Коллекция раундов
            $roundsCollection = $battle->getBattleRoundCollection();
            $roundsCollection->resetNext();

            // Награда
            $loosesUnits = array();
            $reward = array();

            // Проходимся по каждому раунду
            while($round = $roundsCollection->next())
            {
                $roundResultCollection = $round->getBattleResultCollection();
                $roundResultCollection->resetNext();

                // Суммируем потерянные юниты
                while($result = $roundResultCollection->next())
                {
                    $participant = $result->getPattleParticipant();

                    // Если это потери того, чей уровень больше
                    if($participant->getParticipantFlag() == $recipient)
                    {
                        if ($result->getLoses() == 0) {
                            continue;
                        }

                        if (key_exists($result->getUnit()->getId(), $loosesUnits)) {
                            $loosesUnits[$result->getUnit()->getId()] += $result->getLoses();
                        } else {
                            $loosesUnits[$result->getUnit()->getId()] = $result->getLoses();
                        }
                    }
                }
            }

            // Если никого не убили
            if (count($loosesUnits) == 0) {
                foreach(GB_ResourcesList::$allResources as $k => $resourceName)
                {
                    $battle->getAttributes()->setAttribute('CURRENT_AMOUNT', 'STEALING_REWARD_'.$resourceName, 0);
                }
                return;
            }

            if (!$attIsNPC && !$defIsNPC) {
                // Максимальная рассматриваемая разница в уровнях
                $Xa = DE_WorldSettings::CreateForCurentWorld()->get('REWARD_NEWB_MAX_LEVEL_DIFFERENCE');
                // Сколько процентов минимально вывозится при разнице больше максимальной
                $Ya = DE_WorldSettings::CreateForCurentWorld()->get('REWARD_NEWB_MAX_PERSENT');
                // "крутость" графика;
                $A = DE_WorldSettings::CreateForCurentWorld()->get('REWARD_NEWB_RATE');

                // Разница в уровнях
                $levelDif = abs($defenderLevel - $atakerLevel);
                // Если разница в уровнях > 25, трофеи равны трофеям при разнице в уровнях, равной 25
                if ($levelDif > 25) {
                    $levelDif = 25;
                }

                // Процент награды
                $rewardTax = 0.5 * ( $A * pow(($levelDif - $Xa), 2) + $Ya);
            }
            else {
                $rewardTax = DE_WorldSettings::CreateForCurentWorld()->get('REWARD_NEWB_FOR_NPC_'.$leagueDiff);
            }

            // Проходимся по каждому убитому юниту и подсыитываем их вес во всех ресах
            foreach ($loosesUnits as $unitId => $amount) {
                $unit = DE_Unit::CreateFromID($unitId);
                $amount = round($amount);

                // высчитываем ресурсы
                foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
                {
                    if(!$unit->getAttributes()->haveAttribute('PRODUCTION_COSTS', $resourceName))
                    {
                        continue;
                    }

                    if (key_exists($resourceName, $reward)) {
                        $reward[$resourceName] += $amount * $unit->getAttributes()->getAttribute('PRODUCTION_COSTS', $resourceName);
                    } else {
                        $reward[$resourceName] = $amount * $unit->getAttributes()->getAttribute('PRODUCTION_COSTS', $resourceName);
                    }
                }
            }

            // Вознаграждение
            foreach(GB_ResourcesList::$allResources as $k => $resourceName)
            {
                if (key_exists($resourceName, $reward)) {
                    
                    $rewardVal = $rewardTax*$reward[$resourceName]/100;
                    $battle->getAttributes()->setAttribute('CURRENT_AMOUNT', 'STEALING_REWARD_'.$resourceName, $rewardVal);
            
                    // Увеличиваем вознаграждние за этот ресурс
                    $rewardClass->deltaReward($resourceName, $rewardVal);

                } else {
                    $battle->getAttributes()->setAttribute('CURRENT_AMOUNT', 'STEALING_REWARD_'.$resourceName, 0);
                }
            }
        }

        return;
    }
}


?>