<?php
class SU_NPC
{
    static private $common = NULL;
    static private $npcSettings = NULL;
    static private $attackSelectionTypes = array('top_rating', 'rating_change', 'attackers', 'neighbours');

    public function __construct($dbId)
    {
        $_SESSION['dbId'] = $dbId;

        $sql = "delete from ds_npc_spawn_army where date < now() - interval '3 days'";
        self::getCommon()->executeQuery($sql);

        $sql = "select c.id_comb, ui.id_league, ui.id_race, nt.type, count(b.id_battle) as battles, ui.id_world
            from ds_userinstance ui, ds_user u, ds_npchastype nt, ds_comb c
            left join ds_battle b on b.id_battlecomb=c.id_comb and b.battleenddate is null
            where c.id_userinstance=ui.id_userinstance
            and nt.id_userinstance=ui.id_userinstance
            and ui.id_user=u.id_user and u.npc
            group by c.id_comb, ui.id_league, ui.id_race, nt.type, ui.id_world";
        $res = self::getCommon()->getResultArray("", $sql);
        foreach ($res as $row) {
            $_SESSION['world'] = $row['id_world'];
            if (DE_WorldSettings::CreateForCurentWorld()->get('ACTIVE_NPC')) {
                $this->deleteIdleArmies($row);
                if (!$this->restoreHomeArmy($row))
                    $this->makeArmyLayer($row);
                if(DE_WorldSettings::CreateForCurentWorld()->get('SPY_ENABLED'))
                    $this->addSpyBots($row);
                $this->makeInitiatedAttacks($row);
                $this->restoreProtectiveField($row);
            }
        }
        GB_SingletonObjectStorage::CommitAllCommitable();
    }

    static private function getNPCSettings() {
        if (self::$npcSettings == NULL) {
            self::$npcSettings = array();
            $sql = "select * from ds_npc_army_settings";
            $res = self::getCommon()->getResultArray("", $sql);
            foreach ($res as $row) {
                $league = $row['league'];
                $type = $row['type'];
                unset($row['league']);
                unset($row['type']);
                foreach (array('strike', 'transport') as $unitType) {
                    for ($race=1; $race<=3; $race++) {
                        $row[$unitType][$race] = explode(',', $row[$unitType.'_units_'.$race]);
                        unset($row[$unitType.'_units_'.$race]);
                    }
                }
                self::$npcSettings[$league][$type] = $row;
            }
        }
        return self::$npcSettings;
    }

    static private function getCommon() {
        if (self::$common == NULL) {
            self::$common = DS_Common::CreateForCurentDB($dbId);
        }
        return self::$common;
    }

    private function deleteIdleArmies(&$row) {
        if ($row['battles'] > 0) return;
        $armies = array();
        $comb = DE_Comb::CreateFromID((int)$row['id_comb']);
        $armiesCollection = $comb->GetCombArmysCollection();
        $armiesCollection->resetNext();
        while ($army = $armiesCollection->next()) {
            if (!$army->getIsDefault() && (!$army->getInAction() || !$army->getArmyPower())) {
                $sql = "insert into ds_npc_spawn_army (id_npcinstance, date, home, weight)
                    values (".$comb->getUserInstance()->getID().", now(), false, -".$army->getArmyPower().")";
                self::getCommon()->executeQuery($sql);
                $armies[] = $army;
            }
        }
        foreach ($armies as $army) $army->delete();
    }

    private function makeArmyLayer(&$row)
    {
        $settings = self::getNPCSettings()[$row['id_league']][$row['type']];
        if (mt_rand() / mt_getrandmax() > (float) $settings['layer_prob']) return;

        $comb = DE_Comb::CreateFromID((int)$row['id_comb']);
        GE_SearchStart::setCommon(self::getCommon());
        $worldtype = GE_SearchStart::GetWorldType((int)$row['id_world']);
        $date = GB_TypeFactory::CreateBase('GB_Date', time());

        $unitExist = array(93,98,99);
        if ($comb->getIsCapture()) unset($unitExist[(int)$row['id_race']]);
        
        $groups = $comb->GetHomeArmy()->getUnitsGroupCollection();
        $groups->resetNext();
        while ($group = $groups->next())
            $unitExist[] = $group->getUnitID();
        $sql = "select u.id_unit from ds_unit u, ds_unitcanbebuildbyrace ur, ds_unithasclass uc
            where u.id_unit=ur.id_unit and u.id_unitcontainertype=1
            and u.id_unit=uc.id_unit and uc.id_unitclass <= {$settings['max_layer_class']}
            and u.id_unit not in (".implode(',',$unitExist).")";
        if ($worldtype=='FREE_FOR_ALL') $sql .= ' and ur.id_race<4';
        else $sql .= " and ur.id_race=".$row['id_race'];
        $allUnits = self::getCommon()->getResultArray("", $sql);
        foreach ($allUnits as $unit) {
            $unitID = (int)$unit['id_unit'];
            $unit = DE_Unit::CreateFromID($unitID);
            ME_AddUnitsToHomeArmy::AddUnit($comb, $unit, 1.0, $date);
        }
    }

    static public function getDSArmyMultiplier($complex) {
        $ws = DE_WorldSettings::CreateForCurentWorld();
        $hyper = $ws->get('PROGRESSIVE_STRENGTH_HYPER_MOD');
        $para = $ws->get('PROGRESSIVE_STRENGTH_PARA_MOD');
        $hvp = $ws->get('PROGRESSIVE_STRENGTH_HYPER_VS_PARA');
        return pow($hyper, $complex-1) * $hvp + pow($para+$complex, 2) / pow($para+1, 2) * (1-$hvp);
    }

    private function restoreHomeArmy(&$row)
    {
        $settings = self::getNPCSettings()[$row['id_league']][$row['type']];
        if (mt_rand() / mt_getrandmax() > (float) ($row['battles'] > 0 ? $settings['fight_army_prob'] : $settings['army_prob'])) return FALSE;

        $comb = DE_Comb::CreateFromID((int)$row['id_comb']);
        GE_SearchStart::setCommon(self::getCommon());
        $worldtype = GE_SearchStart::GetWorldType((int)$row['id_world']);
        $power = $comb->GetHomeArmy()->getArmyPower();
        $min_army = (float) $settings['min_army'];
        $max_army = (float) $settings['max_army'];
        $spawn_limit = (float) $settings['defense_spawn_limit'];
        if (!$comb->getUserInstanceID()) {
            $mult = self::getDSArmyMultiplier($comb->getComplexNumber());
            $min_army *= $mult;
            $max_army *= $mult;
        }
        if ($power >= $min_army) return FALSE;

        $addedPower = mt_rand() / mt_getrandmax() * ($max_army - $min_army) + $min_army - $power;
        $npcID = $comb->getUserInstance()->getID();
        $sql = "select sum(weight) as total from ds_npc_spawn_army where id_npcinstance=$npcID and date > now() - interval '3 days' and home";
        $res = self::getCommon()->getResultArray("", $sql);
        if ((float) $res[0]['total'] + $addedPower > $spawn_limit)
            return FALSE;

        $sql = "insert into ds_npc_spawn_army (id_npcinstance, date, home, weight)
            values ($npcID, now(), true, $addedPower)";
        self::getCommon()->executeQuery($sql);

        $date = GB_TypeFactory::CreateBase('GB_Date', time());
        $units = self::makeArmyUnits($row, $addedPower);
        foreach ($units as $u)
            ME_AddUnitsToHomeArmy::AddUnit($comb, $u['unit'], $u['num'], $date);
        return TRUE;
    }

    private function addSpyBots(&$row) {
        // Добавляем зонды
        $settings = self::getNPCSettings()[$row['id_league']][$row['type']];
        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$row['id_comb']);
        $current = (int) $combAttributes->getAttribute('CURRENT_AMOUNT', 'SPY_ZOND_SCOUT');
        $away = (int) $combAttributes->getAttribute('AWAY_AMOUNT', 'SPY_ZOND_SCOUT');
        $min = (int)$settings['min_zonds'];
        $max = (int)$settings['max_zonds'];
        if ($current+$away < $min) {
            $ctt = mt_rand($min-$current-$away, $max-$current-$away);
            $combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SPY_ZOND_SCOUT', (float)$ctt);
        }
        $current += $ctt;
        if ($current) {
            // Отправляем зонды на защиту
            $dateFrom = GB_TypeFactory::CreateBase('GB_Date', time());      
            $dateTo = GB_TypeFactory::CreateBase('GB_Date', time() + (int) DE_WorldSettings::CreateForCurentWorld()->get('SPY_MAX_DEFENCE_TIME') * (int) DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60);
            $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);
            $creationData = new GB_SpyActionCreationData();
            $creationData->setActionType(DE_SpyAction::CreateFromName('DEFEND'));
            $creationData->setCombFrom(DE_Comb::CreateFromID((int)$row['id_comb']));
            $creationData->setPeriod($datePeriod);
            $creationData->setUnit(DE_Unit::CreateFromID(83));
            $creationData->setZondsAmount($current);
            $creationData->setNeedChangeAttributes(true);
            ME_SpyGroupAction::CreateGroupAction($creationData);
        }
    }


    private function makeInitiatedAttacks(&$row) {
        $settings = self::getNPCSettings()[$row['id_league']][$row['type']];
        if (mt_rand() / mt_getrandmax() > (float) $settings['init_attack_prob']) return;

        $settings = self::getNPCSettings()[$row['id_league']][$row['type']];
        $target = $this->chooseTarget($row);
        if (!$target['combID']) return;

        $combFrom = DE_Comb::CreateFromID((int)$row['id_comb']);
        $combTo = DE_Comb::CreateFromID($target['combID']);

        $armies = array();
        if ($target['armyType'] == 'Pillager') {
            $armyWeight = mt_rand() / mt_getrandmax() * ((float)$settings['max_pillage_army'] - (float)$settings['min_pillage_army']) + (float)$settings['min_pillage_army'];
            if (!$combFrom->getUserInstanceID())
                $armyWeight *= (1 + $combFrom->getComplexNumber() / 100);
            $armies[] = self::makeArmy($row, $armyWeight, 'Pillager');
        }
        else {
            $armyWeight = mt_rand() / mt_getrandmax() * ((float)$settings['max_init_army'] - (float)$settings['min_init_army']) + (float)$settings['min_init_army'];
            if (!$combFrom->getUserInstanceID())
                $armyWeight *= (1 + $combFrom->getComplexNumber() / 100);
            $armies[] = self::makeArmy($row, $armyWeight * 0.05);
            $armies[] = self::makeArmy($row, $armyWeight * 0.95);
        }

        $starttime = time();
        $traveltime = GL_TravelTime::Count($combFrom, $combTo, GB_TypeFactory::CreateBase('GB_MoveSpeed',1.0))->value;
        foreach ($armies as $army) {
            $endtime = $starttime + $traveltime;
            //Создаем ивент
            $dateFrom = GB_TypeFactory::CreateBase('GB_Date', $starttime);
            $dateTo = GB_TypeFactory::CreateBase('GB_Date', $endtime);
            $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);
            $eventParticipants = new GB_EventParticipants;
            $eventParticipants->setParticipant($combTo->getUserInstance());
            $eventParticipants->setParticipant($combFrom->getUserInstance());
            $actionData = new GB_ArmyActionCreationData('MOVE_TO_ATTACK');
            $actionData->setArmy($army);
            $actionData->setDatePeriod($datePeriod);
            $actionData->setParticipants($eventParticipants);
            $actionData->setPath(new GB_TravelPath($combFrom, $combTo));
            ME_CreateArmyAction::CreateAction($actionData);
            $starttime += 3;
        }
    }

    private function chooseTarget(&$row) {
        $settings = self::getNPCSettings()[$row['id_league']][$row['type']];
        $comb = DE_Comb::CreateFromID((int)$row['id_comb']);

        $probs = array(0);
        reset(self::$attackSelectionTypes);
        for ($i=0; list($key, $type) = each(self::$attackSelectionTypes); $i++) {
            $probs[$i+1] = $probs[$i] + (float) $settings['attack_by_'.$type];
        }
        if ($probs[$i] <= 0) return;
        $r = mt_rand() / mt_getrandmax() * $probs[$i];
        reset(self::$attackSelectionTypes);
        for ($i=1; list($key, $type) = each(self::$attackSelectionTypes); $i++) {
            if ($r < $probs[$i]) break;
        }

        switch ($type) {
            case 'top_rating':
                $percent = DE_WorldSettings::CreateForCurentWorld()->get('NPC_TOP_RATING_PERCENT_FOR_ATTACK');
                $sql = "select count(*) as cnt from ds_userinstance";
                $res = self::getCommon()->getResultArray("userinstance_count", $sql, 300, "FULL_PREFIX", "GLOBAL_CACHE");
                $limit = ceil ($res[0]['cnt'] * $percent / 100);
                $more_tables = "ds_battlexp bx,";
                $sql_add = " and bx.id_userinstance=ui.id_userinstance
                    order by r.science_ranking + r.artefact_ranking + r.building_ranking + bx.absolute_value * 100 desc limit $limit";
                break;
            case 'rating_change':
                $percent = DE_WorldSettings::CreateForCurentWorld()->get('NPC_TOP_RATING_PERCENT_FOR_ATTACK');
                $sql = "select count(*) as cnt from ds_userinstance";
                $res = self::getCommon()->getResultArray("userinstance_count", $sql, 300, "FULL_PREFIX", "GLOBAL_CACHE");
                $limit = ceil ($res[0]['cnt'] * $percent / 100);
                $more_tables = "ds_ranking_archive ra1, ds_ranking_archive ra2,";
                $sql_add = " and u.id_user = ra1.id_user and ra1.date = current_date - interval '1 day'
                    and u.id_user = ra2.id_user and ra2.date = current_date
                    order by ra2.total - ra1.total desc limit $limit";
                break;
            case 'attackers':
                $more_tables = "ds_npc_battle_log nbl, ds_battle b,";
                $sql_add = " and nbl.id_userinstance=ui.id_userinstance and nbl.defender
                    and nbl.id_battle=b.id_battle and b.battlestartdate > now() - interval '2 days'";
                break;
            case 'neighbours':
                $more_tables = '';
                $minCCN = $comb->getComplexNumber() - 50;
                $maxCCN = $comb->getComplexNumber() + 50;
                $sql_add = " and cc.complexnumber between $minCCN and $maxCCN";
                break;
            default:
                $more_tables = '';
                $sql_add = '';
        }

        $sql = "select c.id_comb from ds_comb c, ds_combcomplex cc, ds_attachedbuilding ab, ds_ranking r, $more_tables ds_userinstance ui
        left join ds_powershild_current_status pcs on ui.id_userinstance=pcs.id_userinstance and pcs.id_powershildstatusname=5 and now() between pcs.start_date and pcs.end_date
        inner join ds_user u on ui.id_user=u.id_user
        left join ds_user_premium_ban upb on u.id_user = upb.id_user
        where (upb.id_user is null or upb.end_date<now())
        and pcs.id_userinstance is null and u.accountactive and u.id_user!=0 and not u.npc
        and c.id_userinstance=ui.id_userinstance
        and ab.id_comb=c.id_comb and ab.active and ab.id_building=6
        and cc.id_combcomplex=c.id_combcomplex
        and r.id_userinstance=ui.id_userinstance and r.id_level between {$settings['min_level_for_init_attack']} and {$settings['max_level_for_init_attack']}
        and ui.id_world=".$row['id_world'];

        if(!DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_COMBCOMPLEX_ATTACK'))
            $sql .= " and c.id_combcomplex!=".$comb->getComplexID();

        GE_SearchStart::setCommon(self::getCommon());
        $worldtype = GE_SearchStart::GetWorldType((int)$row['id_world']);
        if ($worldtype == 'RACE_VS_RACE')
            $sql .= " and cc.id_ring != ".$comb->getCombRing()->getID();

        $sql .= $sql_add;

        $res = self::getCommon()->getResultArray("", $sql);
        if (!count($res)) return NULL;
        $r = mt_rand(0, count($res)-1);
        /*
        $comb = DE_Comb::CreateFromID((int) $res[$r]['id_comb']);
        echo "Type: $type, Target: ".$comb->getUserInstance()->getUser()->getName()."\n";
        */
        return array("armyType"=> ($type=='attackers'?'Raider':'Pillager'), "combID"=>(int) $res[$r]['id_comb']);
    }

    public static function makeArmy(&$row, $size, $armyType='Raider') {
        if ($size)
            $units = self::makeArmyUnits($row, $size, $armyType);
        else {
            $settings = self::getNPCSettings()[$row['id_league']][$row['type']];
            $unitID = (int) $settings['strike'][$row['id_race']][0];
            $units = array(array('unit'=>DE_Unit::CreateFromID($unitID), 'num'=>1));
        }

        while (true) {
            $randname = $armyType.mt_rand();
            $sql = "select * from ds_unitcontainer where unitcontainername='$randname'";
            $res = self::getCommon()->getResultArray("", $sql);
            if (!count($res)) break;
        }
        $comb = DE_Comb::CreateFromID((int)$row['id_comb']);
        $sql = sprintf ( "insert into ds_unitcontainer
                (id_unitcontainer, id_basecomb, id_unitcontainertype, id_userinstance, moveable,
                inaction, unitcontainername, isdefault ) values
                (default, %d, 1, %d, true, false, '%s', false)
                returning id_unitcontainer",
            $comb->getID(), $comb->getUserInstance()->getID(), $randname);
        $res = self::getCommon()->getResultArray("", $sql);
        $armyID = (int)$res[0]['id_unitcontainer'];
        $army = DE_Army::CreateFromID($armyID);
        foreach ($units as $u) {
            DE_UnitsGroup::CreateInDB($army, $u['unit'], (float)$u['num']);
        }
        return $army;

    }

    private static function makeArmyUnits(&$row, $size, $armyType='') {
        $settings = self::getNPCSettings()[$row['id_league']][$row['type']];
        $units = array();
        $totalParts = $settings['strike_part'] + $settings['transport_part'] + $settings['layer_part'];
        $usedIds = array(93,98,99);

        // Ударные юниты
        $power = $size * ($armyType=='Pillager' ? 0.02 : $settings['strike_part'] / $totalParts);
        if ($armyType=='' and $row['battles']>0) {
            $unitID = 0;
            $cnt = 0;
            $groups = $comb->GetHomeArmy()->getUnitsGroupCollection();
            $groups->resetNext();
            while ($group = $groups->next()) {
                if ($group->getAmount() > $cnt) {
                    $cnt = $group->getAmount();
                    $unitID = $group->getUnitID();
                }
            }
        }
        else {
            $allUnits = $settings['strike'][$row['id_race']];
            $unitID = (int) $allUnits[mt_rand(0, count($allUnits)-1)];
        }
        if (!$unitID) return;
        $unit = DE_Unit::CreateFromID($unitID);
        $num = ceil($power / $unit->getUnitPower());
        if ($num > 0) $units[] = array('unit'=>$unit, 'num'=>$num);
        $usedIds[] = $unitID;

        // Транспортные юниты
        $allUnits = $settings['transport'][$row['id_race']];
        if (count($allUnits)) {
            $power = $size * ($armyType=='Pillager'? 0.98 : $settings['transport_part'] / $totalParts) / count($allUnits);
            foreach ($allUnits as $unitID) {
                $unitID = (int) $unitID;
                $unit = DE_Unit::CreateFromID($unitID);
                $num = round($power / $unit->getUnitPower());
                if ($num > 0) $units[] = array('unit'=>$unit, 'num'=>$num);
                $usedIds[] = $unitID;
            }
        }

        // Прослой
        $maxClass = ($armyType=='Pillager'? 1 : $settings['max_layer_class']);
        $layerPower = $size * $settings['layer_part'] / $totalParts;
        $sql = "select u.id_unit from ds_unit u, ds_unitcanbebuildbyrace ur, ds_unithasclass uc
            where u.id_unit=ur.id_unit and u.id_unitcontainertype=1
            and u.id_unit=uc.id_unit and uc.id_unitclass <= $maxClass
            and u.id_unit not in (".implode(',', $usedIds).")";
        if ($worldtype=='FREE_FOR_ALL') $sql .= ' and ur.id_race<4';
        else $sql .= " and ur.id_race=".$row['id_race'];
        $allUnits = self::getCommon()->getResultArray("", $sql);
        $layerPower /= count($allUnits);
        foreach ($allUnits as $unit) {
            $unitID = (int)$unit['id_unit'];
            $unit = DE_Unit::CreateFromID($unitID);
            $num = ($armyType=='Pillager'? 1 : round($layerPower / $unit->getUnitPower()));
            if ($num > 0) $units[] = array('unit'=>$unit, 'num'=>$num);
        }
        return $units;
    }

    private function restoreProtectiveField(&$row) {
        if ($row['battles']> 0) return;
        $comb = DE_Comb::CreateFromID((int)$row['id_comb']);
        if (!$comb->getIsCapture()) return;
        $field = DE_CombProtectiveField::CreateFromCombID((int)$row['id_comb']);
        $curr = $field->getProtectiveField();
        if ($curr<100) $field->deltaProtectiveField(100 - $curr);
    }
}
?>