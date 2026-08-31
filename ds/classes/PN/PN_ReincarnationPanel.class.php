<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2009/08/27 07:44:26 $';

// Меню реинкарнации юнитов(то биш их покупки)

class PN_ReincarnationPanel extends UA_GetBuildMenu {

    function __construct() {

        UA_GetBuildMenu::UA_GetBuildMenu ();
    }

    function getPanel ($newUnit = array(), $showAlert = false, $messageParams = array()) {
        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><reincarnate />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();
        
        // Вывод графиков
        $armyWeight = DE_ArmyWeight::CreateFromUserInstanceID($this->uiid);
        $lostProdium = round($armyWeight->getArmyWeightLost());
        $biuldProdium = round($armyWeight->getArmyWeightBuilt());
        $maxProdium = 0;
        $targetProdium = 0.7*min($lostProdium, $biuldProdium);
        $reincProdium = round(DS_ArmyWeight::GetReincarnateProdiumVal($this->uiid));
        
        $history = DS_ArmyWeight::GetWeightHistoryByUserInstanceIDNew($this->uiid, 30);

        $historyWeight = array();       
        for ($i = 0;  $i < count($history); $i++)
        {
            $historyWeight[$history[$i]['year']."-".$history[$i]['month']."-".$history[$i]['day']]['army_weight_built'] = 
            $history[$i]['army_weight_built'];
            $historyWeight[$history[$i]['year']."-".$history[$i]['month']."-".$history[$i]['day']]['army_weight_lost'] = 
            $history[$i]['army_weight_lost'];
        }

        $dayKey = 0;
        for($i=30; $i >= 0; $i--)
        {
            $curDate = date("Y-n-j", strtotime("-$i day"));
            if (!key_exists($curDate, $historyWeight)) {
                if ($dayKey == 0) {
                    $buildMonth[$dayKey] = 0;
                    $lostMonth[$dayKey] = 0;
                } else {
                    $buildMonth[$dayKey] = $buildMonth[$dayKey-1];
                    $lostMonth[$dayKey] = $lostMonth[$dayKey-1];
                }
            } else {
                $buildMonth[$dayKey] = $historyWeight[$curDate]['army_weight_built'];
                $lostMonth[$dayKey] = $historyWeight[$curDate]['army_weight_lost'];
            }
            
            if ($dayKey > 23) {
                $lostWeek[] = $lostMonth[$dayKey];
                $buildWeek[] = $buildMonth[$dayKey];
            }
            $dayKey ++;
        }
        
        $reincMonth = DS_ArmyWeight::GetReincarnateHistoryByUserInstanceID($this->uiid, 'days', 30);
        $reincWeek = array_slice($reincMonth, 22);      
        
        // Разрешение реинкарнировать юниты за ресы
        $reincarnationForResources = DE_WorldSettings::CreateForCurentWorld()->get('UNIT_REINCARNATION_IN_RESOURCE_ENABLED');
        // Разрешение реинкарнировать юниты за серу
        $reincarnationForSilver = DE_WorldSettings::CreateForCurentWorld()->get('UNIT_REINCARNATION_IN_SILVER_ENABLED');
    
        $root->set_attribute ("maxProdium", $maxProdium);
        $root->set_attribute ("lostProdium", $lostProdium);
        $root->set_attribute ("biuldProdium", $biuldProdium);
        $root->set_attribute ("reincProdium", $reincProdium);
        $root->set_attribute ("targetProdium", $targetProdium);
        $root->set_attribute ("res", $reincarnationForResources);
        $root->set_attribute ("sil", $reincarnationForSilver);
        
        $ch = $root->new_child ( "lost" );
        $ch->set_attribute ("w", implode(',', $lostWeek));
        $ch->set_attribute ("m", implode(',', $lostMonth));
        
        $ch = $root->new_child ( "build" );
        $ch->set_attribute ("w", implode(',', $buildWeek));
        $ch->set_attribute ("m", implode(',', $buildMonth));
        
        $ch = $root->new_child ( "reinc" );
        $ch->set_attribute ("w", implode(',', $reincWeek));
        $ch->set_attribute ("m", implode(',', $reincMonth));

        // Вывод списака юнитов
        //Выбираем всех юнитов, которые игрок может реинкарнировать
        $unitList = DS_ArmyWeight::GetUnitListToReincarnation($this->uiid, $this->comb, (int)$_SESSION["race"]);

        foreach ($unitList as $unitKey => $unitData) {
            // Выбираем стоимость юнита
            $res = DS_ArmyWeight::GetUnitReincarnationPrice((int)$unitData['id_unit'], $this->world, DE_UserInstance::CreateFromID($this->uiid)->getLeagueId());
            
            $courses = array();
            foreach ($res as $k => $course)
            {
                $val = $course['price'];
                if(strpos($val, 'e') !== false)
                {
                    $val = number_format($val, 10, '.', '');
                }
                $courses[] = $course['name'].":".$val;
            }
    
            $price = implode(';', $courses);
            
            // Выбираем вес юнита
            $unitWeight = DE_Unit::CreateFromID((int)$unitData['id_unit'])->getUnitPower()/10;
            
            $ch = $root->new_child ("unit");
            $ch->set_attribute ("id", $unitData['id_unit']);
            $ch->set_attribute ("unitname", $unitData['unitname']);
            $ch->set_attribute ("amount", "");
            $ch->set_attribute ("price", $price);
            $ch->set_attribute ("PW", $unitWeight);
        }
        
        // Добавляем к ним юниты, которые только что были заказаны
        if (count($newUnit) > 0) {
            foreach ($newUnit as $unitId => $unitData) {
                $ch = $root->new_child ("reunit");
                $ch->set_attribute ("id", $unitId); 
                $ch->set_attribute ("unitname", $unitData['unitname']); 
                $ch->set_attribute ("amount", $unitData['amount']); 
                $ch->set_attribute ("time", $unitData['time']); 
            }
        }
            
        // Выбираем юниты, находящиеся на реинкарнации
        $unitOnReincarnation = DS_ArmyWeight::GetUnitOnReincarnation($this->uiid, $this->comb);
        foreach ($unitOnReincarnation as $reincKey => $reincData)
        {
            $starttime = GB_TypeFactory::CreateBase('GB_Date', $reincData['eventstarttime'])->getTimeStamp(DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta());
            $endtime = GB_TypeFactory::CreateBase('GB_Date', $reincData['eventendtime'])->getTimeStamp(DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta());
            $time = $starttime.'000|'.$endtime.'000';

            $ch = $root->new_child ("reunit");
            $ch->set_attribute ("id", $reincData['id_unit']);
            $ch->set_attribute ("unitname", $reincData['unitname']);
            $ch->set_attribute ("amount", $reincData['amount']);
            $ch->set_attribute ("time", $time);
        }

        $this->getBuildingTabs ( $root, 46, $this->params["lang"], 'PN_ReincarnationPanel' );

        SU_OutputHandler::Singleton()->addString($doc->dump_mem() , 'xmldata');

        if ($showAlert) {
            assert(is_array($messageParams) && count($messageParams) > 0);
            // Формируем ифу о реинкарнации
            $messageName = 'REINCARNATE_UNIT_INFO_MESS';
            $rs = DS_Misc::GetLocolizedSystemMessage($messageName, $_SESSION['lang']);
            $message = $rs['message'];

            for ($i=0; $i < 6; $i++)
            {
                $pos = strpos( $message, "%" );

                if (!key_exists($i, $messageParams))
                {
                    $param = "[!LOST_PARAM!]";
                }else{
                    $param = $messageParams[$i];
                }

                if(!empty($param) || $param == 0)
                {
                    $message = substr_replace ($message, $param, $pos, 1);
                }
            }

            $title = ""; //DS_ArmyWeight::GetLocalised;
            SU_OutputHandler::Singleton()->addReadyString('outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf" title="'.$title.'"><txt><![CDATA['.$message.']]></txt></swloader>');   
        }


        return true;
        
    }
}
?>