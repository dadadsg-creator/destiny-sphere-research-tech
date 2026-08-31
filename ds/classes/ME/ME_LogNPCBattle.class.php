<?php
class ME_LogNPCBattle
{
    static public function Log(DE_Battle $battle, $isDefender) {
        $common = DS_Common::CreateForCurentDB();
        $foundRes = false;
        $lossPart = 0;

        if ($isDefender) {
            // Вывоз ресурсов
            $resources = array('KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'FOOD', 'STASIS_ENERGY');
            foreach($resources as $k=>$resourceName)
            {
                if ($battle->getAttributes()->haveAttribute('CURRENT_AMOUNT', $resourceName))
                {
                    $foundRes = true;
                }
            }

            $lossWeight = 0;
            $weight = 0;
            $rounds = DC_BattleRounds::CreateFromBattleID($battle->getID());
            $rounds->resetNext();
            for ($i=0; $round = $rounds->next(); $i++)
            {
                $results = $round->getBattleResultCollection();
                $results->resetNext();
                while ($result = $results->next()) {
                    $participant = $result->getPattleParticipant();
                    if ($participant->isCombOwner()=='t') {
                        $lossWeight += $result->getUnit()->getUnitPower() * $result->getLoses();
                    }
                    if ($i==0 && $participant->getParticipantFlag() == 'ATTACKER') {
                        $weight += $result->getUnit()->getUnitPower() * $result->getAmount();
                    }
                }
            }
            $remainedWeight = DE_CombDefendArmy::CreateFromCombID($battle->getCombID())->getArmyPower();
            $lossPart = $lossWeight > 0 ? ($lossWeight / ($remainedWeight + $lossWeight)) : 0;

            // Считаем шанс атаки
            $attackChance = 0;
            $npcID = $battle->getComb()->getUserInstanceID();
            $attackerID = $battle->getFromComb()->getUserInstanceID();

            $sql = "select ui.id_league, nt.type, nas.attack_spawn_limit, nas.max_attack_army
                from ds_userinstance ui, ds_npchastype nt, ds_npc_army_settings nas
                where nt.id_userinstance=ui.id_userinstance
                and nas.league=ui.id_league and nas.type=nt.type
                and ui.id_userinstance=".$battle->getComb()->getUserInstance()->getID();
            $res = $common->getResultArray("", $sql);
            $league = $res[0]['id_league'];
            $type = $res[0]['type'];
            if (!$league || !$type) return;
            $attack_spawn_limit = (float) $res[0]['attack_spawn_limit'];
            $max_attack_army = (float) $res[0]['max_attack_army'];

            // Вероятность атаки от количества потерь
            $sql = "select probability from ds_npc_attack_chance
                where league=$league and type=$type
                and condType=1 and condValue>".($lossPart*100)."
                order by condValue limit 1";
            $res = $common->getResultArray("", $sql);
            $attackChance += (float) $res[0]['probability'];

            // Вероятность атаки от количества атак
            $sql = "select probability from ds_npc_attack_chance
                where league=$league and type=$type
                and condType=2 and condValue<=
                (select count(*)+1 from ds_npc_battle_log nb, ds_battle b
                where b.id_battle=nb.id_battle
                and nb.id_npcinstance=$npcID and nb.id_userinstance=$attackerID
                and nb.defender and b.battlestartdate > now() - interval '2 days')
                order by condValue desc limit 1";
            $res = $common->getResultArray("", $sql);
            $attackChance += (float) $res[0]['probability'];

            // Вероятность атаки от количества ответов
            $sql = "select probability from ds_npc_attack_chance
                where league=$league and type=$type
                and condType=3 and condValue<=
                (select count(*) from ds_npc_battle_log nb, ds_battle b
                where b.id_battle=nb.id_battle
                and nb.id_npcinstance=$npcID and nb.id_userinstance=$attackerID
                and not nb.defender and b.battlestartdate > now() - interval '2 days')
                order by condValue desc limit 1";
            $res = $common->getResultArray("", $sql);
            $attackChance += (float) $res[0]['probability'];

            // Вероятность атаки от наличия грабежа
            $sql = "select probability from ds_npc_attack_chance
                where league=$league and type=$type
                and condType=4 and condValue=".($foundRes?1:0);
            $res = $common->getResultArray("", $sql);
            $attackChance += (float) $res[0]['probability'];


            if (mt_rand() / mt_getrandmax() < $attackChance) {
                // Создаём ивент для нападения
                if (mt_rand() / mt_getrandmax() < 0.6) {
                    $targetCombID = $battle->getFromComb()->getID();
                }
                else {
                    $combCollection = $battle->getFromComb()->getUserInstance()->getCombsCollection();
                    $r = mt_rand(1, $combCollection->countCollection());
                    $combCollection->resetNext();
                    for ($i=0; $i<$r; $i++) $comb = $combCollection->next();
                    $targetCombID = $comb->getID();
                }

                // считаем размер армии для ответа
                $sql = "select condType, probability from ds_npc_attack_chance
                    where league=$league and type=$type
                    and condType in (6,7) and condValue=".($battle->getWinnerCode()=='D'?1:0);
                $res = $common->getResultArray("", $sql);
                foreach ($res as $row) {
                    if ($row['condType']==6) $minArmy = $row['probability'];
                    else $maxArmy = $row['probability'];
                }
                $weight *= mt_rand() / mt_getrandmax() * ($maxArmy - $minArmy) + $minArmy;

                if (!$npcID)
                    $max_attack_army *= SU_NPC::getDSArmyMultiplier($battle->getComb()->getComplexNumber());
                if ($weight > $max_attack_army) $weight = $max_attack_army;

                $sql = "select sum(weight) as total from ds_npc_spawn_army where id_npcinstance=$npcID and date > now() - interval '3 days' and not home";
                $res = $common->getResultArray("", $sql);
                $spawned_weight = (float) $res[0]['total'] + $weight;
                if ($spawned_weight <= $attack_spawn_limit) {
                    $options = array(
                        'ID_CombFrom' => $battle->getCombID(),
                        'ID_CombTo' => $targetCombID,
                        'ArmyWeight' => $weight
                    );

                    $dateFrom = GB_TypeFactory::CreateBase('GB_Date', time());
                    $dateTo = GB_TypeFactory::CreateBase('GB_Date', time() + mt_rand(0, 3600));
                    $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);
                    $participants = array($npcID, $attackerID);
                    DE_EventQueue::CreateInDB('NPC_ATTACK', $participants, $options, $datePeriod);
                    $sql = "insert into ds_npc_spawn_army (id_npcinstance, date, home, weight)
                        values ($npcID, now(), false, $weight)";
                    $common->executeQuery($sql);

                    // Вторая атака
                    $sql = "select probability from ds_npc_attack_chance
                        where league=$league and type=$type and condType=5";
                    $res = $common->getResultArray("", $sql);
                    $attackChance = (float) $res[0]['probability'];
                    $spawned_weight += $weight;
                    if ($spawned_weight <= $attack_spawn_limit && $attackChance > 0 && mt_rand() / mt_getrandmax() < $attackChance) {
                        $combCollection = $battle->getFromComb()->getUserInstance()->getCombsCollection();
                        $combCollection->resetNext();
                        if (mt_rand() / mt_getrandmax() < 0.6) {
                            $r = mt_rand(1, $combCollection->countCollection());
                            for ($i=0; $i<$r; $i++) $comb = $combCollection->next();
                            $targetCombID = $comb->getID();
                        }
                        else {
                            $targetComb = NULL;
                            while ($comb = $combCollection->next()) {
                                $newDistance = GL_TravelTime::Count($battle->getComb(), $comb, GB_TypeFactory::CreateBase('GB_MoveSpeed',1.0))->value;
                                if ($targetComb==NULL || $newDistance < $distance) {
                                    $targetComb = $comb;
                                    $distance = $newDistance;
                                }
                            }
                            $targetCombID = $targetComb->getID();
                        }
                        $options = array(
                            'ID_CombFrom' => $battle->getCombID(),
                            'ID_CombTo' => $targetCombID,
                            'ArmyWeight' => $weight
                        );
                        $dateTo = GB_TypeFactory::CreateBase('GB_Date', time() + mt_rand(0, 3600));
                        $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);
                        DE_EventQueue::CreateInDB('NPC_ATTACK', $participants, $options, $datePeriod);
                        $sql = "insert into ds_npc_spawn_army (id_npcinstance, date, home, weight)
                            values ($npcID, now(), false, $weight)";
                        $common->executeQuery($sql);
                    }
                }
            }
        }

        $NpcInstanceID = $isDefender? $battle->getComb()->getUserInstanceID() : $battle->getFromComb()->getUserInstanceID();
        $UserInstanceID = $isDefender? $battle->getFromComb()->getUserInstanceID() : $battle->getComb()->getUserInstanceID();
        $sql = "insert into ds_npc_battle_log (id_battle, id_npcinstance, id_userinstance, defender, plundered, loss_part)
            values (".$battle->getID().", $NpcInstanceID, $UserInstanceID, ".($isDefender?'true':'false').", ".($foundRes?'true':'false').", $lossPart)";
        $common->ExecuteQuery($sql);
    }
}
?>