<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_GetCombWarUnitsBuilds extends UA_Action
{

    function __construct()
    {
        $this->UA_Action();
    }//END OF CONSTRUCTOR

    function checkLocalParams ()
    {
        return true;
    }//END OF FUNCTION

    function checkAction ()
    {
        assert(key_exists('profi_game', $_SESSION));

        if($_SESSION['profi_game'] != 1)
        {
            SU_OutputHandler::Singleton()->addString("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key addr=\"index_war_mode_demo.php\" target_load=\"_blank\"/>", 'loadkey');
            echo SU_OutputHandler::Singleton()->fetchAll();
            exit;
        }

        return true;
    }//END OF FUNCTION

    function performAction ()
    {
        /*$disabledBuildingsId = array (
        69 => 19, 72 => 19, 75 => 19,
        68 => 22, 71 => 22, 74 => 22,
        67 => 36, 70 => 36, 73 => 36
        );*/
        $data = DS_Comb::GetBuildedWarUnitsOnCombByCells($this->comb, $this->celltype);

        $outData = array();
        $outData['cells'] = array();
        $outData['units'] = array();
        $tmpBuildingID = array();

        foreach($data as $k=>$v)
        {
            if((int)$v['amount'] == 0)
            {
                $status = 0;
            }else{
                $status = 1;
            }

            if($this->celltype == 2)
            {
                $mask = GB_CombCentralViewMask::GetServerBaseMask();
                $cellID = $mask[(int)$v['id_cell']];
            }else{
                $cellID = (int)$v['id_cell'];
            }

            if(!is_null($v['endtime']))
            {
                $endTime = GB_TypeFactory::CreateBase('GB_Date', $v['endtime']);
                $endTime = $endTime->getPgFormat(DE_User::CreateForCurrentUser()->getTimeDelta());
            } else {
                $endTime = $v['endtime'];
            }

            $tmp = array(
            'id_cell' => $cellID,
            'type' => (int)$v['id_building'],
            'unit' => $v['unitname'],
            'count' => (int)$v['amount'],
            'timeend' => $endTime,
            'status' => $status
            );

            $outData['cells'][] = $tmp;

            $tmpBuildingID[(int)$v['id_building']] = 1;
        }

        /**
         * ВЫбираем все юниты, которые можно строить на этой соте
         */
        $userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
        $comb = DE_Comb::CreateFromID($this->comb);
        foreach ($tmpBuildingID as $key => $value)
        {
            $buildingID = $key;
            $res = ME_AllowTrainWarUnit::Exec($userInstance, $comb, $buildingID);
            foreach ($res as $k => $v)
            {
                // Если это юнит только для захватываемой колонии
                if ((int)$v['id_unit'] == 98 || (int)$v['id_unit'] == 99 || (int)$v['id_unit'] == 93) {
                    // Если это не захватываемая колония
                    if (!DE_Comb::CreateFromID($this->comb)->getIsCapture()) {
                        continue;
                    }
                    
                    $common = DS_Common::CreateForCurentDB();

                    // Если у нас уже есть такой юнит
                    $sql = sprintf ( "
                                SELECT 
                                    id_unit 
                                FROM 
                                    ds_unitcontainer u,
                                    ds_unitcontainerhasunit uu 
                                WHERE 
                                    id_unit  IN (98, 99 ,93)
                                    AND u.id_unitcontainer = uu.id_army
                                    AND u.id_basecomb = %d", $this->comb);
                    $resC = $common->getResultArray ( "", $sql);
                    assert(empty($common->errormsg));

                    if (count($resC) > 0)
                    {
                        continue;
                    }

                    // Если у нас уже строится такой юнит
                    $sql = sprintf ( "
                                SELECT * FROM 
                                    ds_unitinproduction up,
                                    ds_attachedbuilding ab,
                                    ds_comb c
                                    
                                WHERE id_unit  IN (98, 99 ,93)
                                AND up.id_attachedbuilding = ab.id_attachedbuilding
                                AND c.id_comb = ab.id_comb
                                AND c.id_comb =  %d", $this->comb);
                    $resC = $common->getResultArray ( "", $sql);
                    assert(empty($common->errormsg));

                    if (count($resC) > 0)
                    {
                        continue;
                    }
                }

                $unit = DE_Unit::CreateFromID((int)$v['id_unit']);
                $liveUpkeep = GL_LiveUpkeep::CountUnits($unit, 1, $userInstance);

                $food = (float)round($liveUpkeep['FOOD'], 6);
                $energy = (float)round($liveUpkeep['STASIS_ENERGY'], 6);
                $kad = (float)round($liveUpkeep['KADERIUM'], 6);
                $nano = (float)round($liveUpkeep['NANO_CRYSTAL'], 6);
                $prod = (float)round($liveUpkeep['PRODIUM'], 6);

                $tmp = array(
                'unitname' => $unit->getName(),
                'id' => $unit->getID(),
                'food' => $food,
                'energy' => $energy,
                'kad' => $kad,
                'nano' => $nano,
                'prod' => $prod,
                'type' => $buildingID
                );
                

                $outData['units'][] = $tmp;
            }
        }

        $outData['turn_time'] = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;

        $userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
        if ($userInstance->buildSpeedupEndTime > time()) $outData['speedup'] = 1;
        else $outData['speedup']=0;

        $out = $this->_formOutput($outData);
        SU_OutputHandler::Singleton()->addString($out, 'xmldata');

        return true;
    }

    private function _formOutput($outData)
    {
        assert(is_array($outData));

        $out = '<?xml version="1.0" encoding="utf-8"?>'."\n".'<warcomplex turn="'.$outData['turn_time'].'" speedup="'.$outData['speedup'].'">';


        /**
         * Список юнитов доступных для строительства на этой соте
         */
        foreach($outData['units'] as $key => $unitData)
        {
            $maxallow = "";
            
            if ($unitData['id'] == 98 || $unitData['id'] == 99 || $unitData['id'] == 93) {
                    $maxallow = 'maxallow="1"';
                }
                
            $out .= '<unit unitname="'.$unitData['unitname'].'" id="'.$unitData['id'].'" food="'.$unitData['food'].'"  energy="'.$unitData['energy'].'" kad="'.$unitData['kad'].'" nano="'.$unitData['nano'].'" prod="'.$unitData['prod'].'" type="'.$unitData['type'].'" '.$maxallow.' />';
        }

        foreach($outData['cells'] as $k => $cellData)
        {
            $out .= '<factory id_cell="'.$cellData['id_cell'].'" type="'.$cellData['type'].'" status="'.$cellData['status'].'" unit="'.$cellData['unit'].'" count="'.$cellData['count'].'" timeend="'.$cellData['timeend'].'"/>';
        }
        $out .= '</warcomplex>';

        return  $out;
    }
}//END OF CLASS


?>