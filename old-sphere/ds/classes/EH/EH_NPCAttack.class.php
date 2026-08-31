<?php

class EH_NPCAttack extends EH_Event 
{
    public function handleEvent ($eqid)
    {
        $this->eqid = $eqid;
        if(!$this->_isTimeToHandle()) return true;

        $this->getEventParams();
        $combToID = (int)$this->eventparams["ID_CombTo"];
        $combFromID = (int)$this->eventparams["ID_CombFrom"];
        $armyWeight = (float) $this->eventparams["ArmyWeight"];

        $army = $this->GenerateArmy($combFromID, $armyWeight);
        if ($army) {
            $combFrom = DE_Comb::CreateFromID($combFromID);
            $combTo = DE_Comb::CreateFromID($combToID);
            $traveltime = GL_TravelTime::Count($combFrom, $combTo, GB_TypeFactory::CreateBase('GB_MoveSpeed',1.0))->value;
            $starttime = time();
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
        }
        $this->setTrash();
        return true;
    }

    private function GenerateArmy($combID, $armyWeight) {
        if (!$armyWeight) return null;
        $sql = "select nas.*, ui.id_userinstance, ui.id_race, wht.id_worldtype
            from ds_npc_army_settings nas, ds_comb c, ds_userinstance ui, ds_npchastype nt, ds_worldhastype wht
            where c.id_userinstance=ui.id_userinstance
            and nt.id_userinstance=ui.id_userinstance
            and nas.league = ui.id_league
            and nas.type = nt.type
            and ui.id_world=wht.id_world
            and c.id_comb=".$combID;
        $res = $this->common->getResultArray("", $sql);
        if (!count($res)) return null;
        $settings = $res[0];

        while (true) {
            $randname = "Collector".mt_rand();
            $sql = "select * from ds_unitcontainer where unitcontainername='$randname'";
            $res = $this->common->getResultArray("", $sql);
            if (!count($res)) break;
        }
        $sql = sprintf ( "insert into ds_unitcontainer
                (id_unitcontainer, id_basecomb, id_unitcontainertype, id_userinstance, moveable,
                inaction, unitcontainername, isdefault ) values
                (default, %d, 1, %d, true, false, '%s', false)
                returning id_unitcontainer",
            $combID, $settings["id_userinstance"], $randname);
        $res = $this->common->getResultArray("", $sql);
        $armyID = (int)$res[0]['id_unitcontainer'];
        $army = DE_Army::CreateFromID($armyID);

        $totalParts = $settings['strike_part'] + $settings['transport_part'] + $settings['layer_part'];
        $usedIds = array(93,98,99);

        // Ударные юниты
        $power = $armyWeight * $settings['strike_part'] / $totalParts;
        $allUnits = explode(',',$settings['strike_units_'.$settings['id_race']]);
        $unitID = (int)$allUnits[mt_rand(0, count($allUnits)-1)];
        $unit = DE_Unit::CreateFromID($unitID);
        $num = ceil($power / $unit->getUnitPower());
        if ($num > 0) DE_UnitsGroup::CreateInDB($army, $unit, $num);
        $usedIds[] = $unitID;

        // Транспортные юниты
        $allUnits = explode(',',$settings['transport_units_'.$settings['id_race']]);
        if (count($allUnits)) {
            $power = $armyWeight * $settings['transport_part'] / $totalParts / count($allUnits);
            foreach ($allUnits as $unitID) {
                $unitID = (int) $unitID;
                $unit = DE_Unit::CreateFromID($unitID);
                $num = round($power / $unit->getUnitPower());
                if ($num > 0) DE_UnitsGroup::CreateInDB($army, $unit, $num);
                $usedIds[] = $unitID;
            }
        }

        // Прослой
        $layerPower = $armyWeight * $settings['layer_part'] / $totalParts;
        $sql = "select u.id_unit from ds_unit u, ds_unitcanbebuildbyrace ur, ds_unithasclass uc
            where u.id_unit=ur.id_unit and u.id_unitcontainertype=1
            and u.id_unit=uc.id_unit and uc.id_unitclass <= {$settings['max_layer_class']}
            and u.id_unit not in (".implode(',', $usedIds).")";
        if ($settings['id_worldtype']==1) $sql .= ' and ur.id_race<4';
        else $sql .= " and ur.id_race=".$settings['id_race'];
        $allUnits = $this->common->getResultArray("", $sql);
        $layerPower /= count($allUnits);
        foreach ($allUnits as $unit) {
            $unitID = (int) $unit['id_unit'];
            if (in_array($unitID, $usedIds)) continue;
            $unit = DE_Unit::CreateFromID($unitID);
            $num = round($layerPower / $unit->getUnitPower());
            if ($num > 0) DE_UnitsGroup::CreateInDB($army, $unit, $num);
        }
        return $army;
    }
}

?>