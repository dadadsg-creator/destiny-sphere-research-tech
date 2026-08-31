<?php $CVS_Version = '$Revision$ ; $Date$';

// команда тренировать военные юниты
class UA_TrainWarUnit extends UA_Unit
{
    /**
     * Флаг того что текущее в итерации зданиенаходиться в апгрейде и там строит ьнельзя
     *
     * @var boolean
     */
    private $buildingInUpgrade = false;


    private $cells;

    private $attachedbids;

    private $old_count;

    private $bids;

    function __construct()
    {
        UA_Unit::UA_Unit ();
    }

    // проверяем параметры
    function checkLocalParams ()
    {
        // id юнита
        $this->id_unit = (int)$this->root->get_attribute ( "id" );
        assert($this->id_unit > 0);

        $this->cells = explode(',', $this->root->get_attribute("c"));
        assert(count($this->cells) > 0);

        foreach($this->cells as $k=>$cellNumber)
        {
            $cellData = ME_CombViewGetCellIDAndBuildigIDByCell::Exec((int)$cellNumber, false);
            $this->cells[$k] = $cellData['CELL_ID'];
            assert(is_int($this->cells[$k]) && $this->cells[$k] > 0);
        }

        // кол-во юнитов
        $count = (int)$this->root->get_attribute ( "count" );
        assert($count > 0);

        $this->getBuildingsInfoByCells();

        /************************************************************
        // id_attachedbuilding
        $this->attachedbids = explode(',', $this->root->get_attribute ( "ab" ));
        assert(count($this->attachedbids)>0);

        $old_count = explode(',', $this->root->get_attribute ( "old_count" ));
        assert(count($old_count)>0);

        $this->bids = explode(',', $this->root->get_attribute ( "b" ));
        assert(count($this->bids)>0);
        /************************************************************/

        $this->counttotrains = GL_ParalelTrainWarUnitsLoad::Count($this->old_count, $count);
        assert(count($this->attachedbids) == count($this->counttotrains));

        $this->factoryResultArray = array();
        return true;
    }

    // проверяем допустимость действия
    function checkAction()
    {

        foreach ($this->attachedbids as $key => $value)
        {
            $this->attachedbid = $this->attachedbids[$key];
            assert($this->attachedbid > 0);

            $this->counttotrain = (int)$this->counttotrains[$key];

            if($this->counttotrain == 0)
            {
                continue;
            }

            $this->buildingInUpgrade = false;

            $this->bid = $this->bids[$key];

            $this->id_cell = $this->cells[$key];

            if (!$this->_checkAction())
            {
                return false;
            }

            if($this->buildingInUpgrade)
            {
                continue;
            }

            if (!$this->_perfomAction())
            {
                return false;
            }

        }

        return true;
    }

    // выполняем действие
    function performAction ()
    {

        //if($_SESSION['profi_game'])
        //{
        $noCellData = true;
        //} else {
        //  $noCellData = false;
        //}

        // рефреш с актуализацией ресурсов
        if(!$this->refresh( 2, "", "", 1, $noCellData))
        {
            return false;
        }

        $this->_formHelper();
        return true;
    }

    function _formHelper()
    {
        if( !(isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == true)))
        {
            return;
        }

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><army_train />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();

        foreach ($this->factoryResultArray as $key => $value)
        {
            if($this->celltype == 2)
            {
                $mask = GB_CombCentralViewMask::GetServerBaseMask();
                $cellID = $mask[(int)$value['id_cell']];
            }else{
                $cellID = (int)$value['id_cell'];
            }

            $ch = $root->new_child ( "factory" );
            $ch->set_attribute ( "type", $value['type'] );
            $ch->set_attribute ( "status", $value['status'] );
            $ch->set_attribute ( "unit", $value['unit'] );
            $ch->set_attribute ( "count", $value['count'] );
            $ch->set_attribute ( "timeend", $value['timeend'] );
            $ch->set_attribute ( "id_cell", $cellID );
        }

        SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'helper' );
    }

    function _checkAction()
    {
        global $params;
        global $common;

        //Здание нельзя начинать апгредйить пока в нем строяться юниты
        $sql = "SELECT inupgrade FROM ds_attachedbuilding WHERE id_attachedbuilding = ".$this->attachedbid;
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);

        if($res[0]['inupgrade'] == 't')
        {
            $this->buildingInUpgrade = true;

            $messparams = array();
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANT_TRAIN_UNITS_IN_UPGRADED_BUILDING", $messparams );
            return true;
        }

        /*
        $this->bid = (int)$res[0]['id_building'];
        assert($this->bid > 0);
        */

        /**** ЗАТЫКА! В евенте TrainWarUnit нельзя тренировать невоенных юнитов ****/
        $sql = "SELECT uct.containertypename FROM ds_unit AS u INNER JOIN ds_unitorcontainertype AS uct USING(id_unitcontainertype) WHERE id_unit = ".$this->id_unit;
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        if(!$res[0]['containertypename'] == 'ARMY')
        {
            ds_error("CAN'T TRAIN NON COMBAT UNITS");
        }
        /***************************** END OF ЗАТЫКА *******************************/

        //Проверяем можно ли стороить эти юниты в этом здание
        $sql = "SELECT * FROM ds_produceableunit WHERE id_building = ".$this->bid." AND id_unit =".$this->id_unit."";
        $res = $common->getResultArray("GET_PRODUCEABLE_WAR_UNITS_IN_BUILDING_".$this->bid."_".$this->id_unit, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(count($res) > 0);

        //Если в здание уже стояться юниты какие-то то добавлять можно только юниты такого же типа
        $sql = "
                SELECT
                    id_attachedbuilding
                FROM
                    ds_unitinproduction
                WHERE
                    id_attachedbuilding = ".$this->attachedbid."
                    AND id_unit != ".$this->id_unit;
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(sizeof($res) != 0)
        {
            $this->errormsg = " SYSTEM ERROR UA_TrainWarUnit::checkAction2";
            return false;
        }

        /*
        $sql = "
        SELECT
        ab.id_cell
        FROM
        ds_attachedbuilding ab
        WHERE
        (id_attachedbuilding = ".$this->attachedbid.")
        ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        $this->id_cell = $res[0]['id_cell'];
        */

        // Если юнит для колонии, смотрим, не пытаемся ли мы построить больше одного
        if ($this->id_unit == 99 || $this->id_unit == 98 || $this->id_unit == 93) {
            if ($this->counttotrain > 1) {
                $this->errormsg = " SYSTEM ERROR UA_TrainWarUnit::checkAction01";
                return false;
            }
            
            if (!DE_Comb::CreateFromID((int)$this->comb)->getIsCapture()) {
                $this->errormsg = " SYSTEM ERROR UA_TrainWarUnit::checkAction02";
                return false;
            }

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
            $res = $common->getResultArray ( "", $sql);
            assert(empty($common->errormsg));

            if (count($res) > 0)
            {
                $this->errormsg = " SYSTEM ERROR UA_TrainWarUnit::checkAction03";
                return false;
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
            $res = $common->getResultArray ( "", $sql);
            assert(empty($common->errormsg));

            if (count($res) > 0)
            {
                $this->errormsg = " SYSTEM ERROR UA_TrainWarUnit::checkAction04";
                return false;
            }
        }

        // хватает ли на юнит науки и зданий
        $sql = sprintf ( "
                SELECT
                    t.id_unit,
                    u.*
                FROM
                    (
                        SELECT
                            pu.id_building,
                            pu.id_unit,
                            ucbbr.id_race,
                            (
                                SELECT
                                    (
                                        SELECT
                                            count(id_unit)
                                        FROM
                                            ds_unitneedsscience
                                        WHERE
                                            id_unit = pu.id_unit
                                    ) = (
                                        SELECT
                                            count(uns.id_unit)
                                        FROM
                                            ds_unitneedsscience uns,
                                            ds_userhasscience uhs
                                        WHERE
                                            uns.id_science = uhs.id_science
                                            and uhs.inresearch = false
                                            and uhs.id_userinstance = %d
                                            and uns.id_unit = pu.id_unit
                                    )
                            ) as senough,
                            (
                                SELECT
                                    (
                                        SELECT
                                            count(id_unit)
                                        FROM
                                            ds_unitneedsbuilding unb
                                        WHERE
                                            id_unit = pu.id_unit
                                    ) = (
                                        SELECT
                                            count(unb.id_unit)
                                        FROM
                                            ds_unitneedsbuilding unb,
                                            ds_attachedbuilding ab
                                        WHERE
                                            unb.id_building = ab.id_building
                                            and ab.active = true
                                            AND ab.underconstruction = 'f'
                                            AND ab.inactivation = 'f'
                                            and ab.id_comb = %d
                                            and id_unit = pu.id_unit
                                        )
                            ) as benough
                        FROM
                            ds_produceableunit pu,
                            ds_attachedbuilding ab2,
                            ds_unitcanbebuildbyrace ucbbr
                        WHERE
                            pu.id_unit = ucbbr.id_unit
                            and pu.id_building = ab2.id_building
                            and ab2.id_attachedbuilding = %d
                            and pu.id_building = %d
                            and ucbbr.id_race = %d
                    ) t,
                    ds_unit u
                WHERE
                    t.id_unit = u.id_unit
                    and t.senough = true
                    and t.benough = true
                    and u.id_unit = %d",
        $params["uiid"],
        $this->comb,
        $this->attachedbid,
        $this->bid,
        $params["race"],
        $this->id_unit );

        $res = $common->getResultArray("science_building_enough", $sql, "", "", "");
        assert(empty($common->errormsg));
        if(!$res)
        {
            $this->errormsg = " SYSTEM ERROR UA_TrainWarUnit::checkAction3";
            return false;
        }

        // хватает ли на юниты ресурсов
        $this->combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
        $this->unitAttributes = DE_UnitAttributes::CreateFromUnitID((int)$this->id_unit);

        $maxCountTemp = array();
        foreach(GB_ResourcesList::$allBuildResources as $k=>$resourceName)
        {
            if(!$this->unitAttributes->haveAttribute('PRODUCTION_COSTS', $resourceName))
            {
                continue;
            }

            $prodValue = $this->unitAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);

            if($prodValue == 0)
            {
                continue;
            }

            $maxCountTemp[] = floor($this->combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true) / $prodValue);
        }
        $allow = min($maxCountTemp);

        if($allow == 0)
        {
            $messparams[0] = $this->counttotrain;
            $messparams[1] = $this->unitAttributes->getUnit()->getLocalizedName();
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANT_TRAIN_MORE_WAR_UNITS", $messparams, 0, 'GREEN');

            $this->haveProcessedErrors = true;
            return true;
        } elseif($this->counttotrain > $allow) {

            $this->counttotrain = $allow;
        }

        // время тренировки одного юнита
        $this->timetotrainone = $this->unitAttributes->getAttribute('PRODUCTION_COSTS', 'CONSTRUCTION_TIME') * $this->worldSettings->get('TURN_TIME') * 60;
        $userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
        if ($userInstance->buildSpeedupEndTime > time()) $this->timetotrainone /= 2;
        return true;
    }

    function _perfomAction()
    {
        global $params;
        global $common;

        if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
        {
            $this->_vocationModeAlert();
            return true;
        }

        if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
        {
            $this->_shildModeAlert();
            return true;
        }

        if($this->haveProcessedErrors)
        {
            return true;
        }

        if($_SESSION['profi_game'])
        {
            $noCellData = true;
        } else {
            $noCellData = false;
        }

        /*if($this->counttotrain == "" || $this->counttotrain <= 0)
        {
        if ( !$this->refresh (2, "", "", $noCellData) )
        {
        return false;
        }
        return true;
        }*/

        // название соты
        $messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

        // название юнита
        $messparams[2] = "{b} ".DE_Unit::CreateFromID((int)$this->id_unit)->getLocalizedName()."{/b}";

        $eid = DS_Event::GetEventIDByName('TRAIN_WAR_UNIT');
        assert($eid > 0);

        $sql = "
            SELECT 
                abbeq.id_eventqueue
            FROM
                ds_attachedbuildingbindeventqueue as abbeq
                INNER JOIN ds_unitinproduction as uip ON (uip.id_attachedbuilding = abbeq.id_attachedbuilding)
                INNER JOIN ds_eventqueue as eq ON (eq.id_eventqueue = abbeq.id_eventqueue)
            WHERE
                eq.istrash = false
                AND
                uip.id_attachedbuilding = ".$this->attachedbid."
                AND
                uip.id_unit = ".$this->id_unit."";
        $res = $common->getResultArray ( "", $sql, "", "", "");
        if($res === false)
        {
            $this->errormsg .= " SYSTEM ERROR - UA_TrainWarUnit::performAction4";
            return false;
        }

        if(sizeof($res) == 0)
        { // новый эвент

            // время окончания
            if(!$this->getEventEndTime())
            {
                return false;
            }

            // создаем эвент
            $this->createEvent ('TRAIN_WAR_UNIT');

            if($this->eqid == "")
            {
                $this->errormsg .= " SYSTEM ERROR - UA_TrainWarUnit::performAction5";
                return false;
            }

            $sql = sprintf("
                    INSERT INTO DS_AttachedBuildingBindEventQueue
                        (ID_EventQueue, ID_AttachedBuilding)
                    VALUES
                        (%d, %d)
                    ",
            $this->eqid,
            $this->attachedbid );
            $res = $common->executeQuery($sql);
            if($res === false)
            {
                $this->errormsg .= " SYSTEM ERROR - UA_TrainWarUnit::performAction6";
                return false;
            }
            $sql = sprintf("
                    INSERT INTO ds_unitinproduction
                        (id_attachedbuilding, id_unit, starttime, endtime, amount)
                    VALUES
                        (%d, %d, '%s', '%s', %d )
                    ",
            $this->attachedbid,
            $this->id_unit,
            $this->starttime,
            $this->endtime,
            $this->counttotrain
            );
            $res = $common->executeQuery($sql);
            if($res === false)
            {
                $this->errormsg .= " SYSTEM ERROR - UA_TrainWarUnit::performAction7";
                return false;
            }

            // вычитаем ресурсы
            foreach(GB_ResourcesList::$allBuildResources as $k=>$resourceName)
            {
                if(!$this->unitAttributes->haveAttribute('PRODUCTION_COSTS', $resourceName))
                {
                    continue;
                }

                $prodValue = (float)($this->unitAttributes->getAttribute('PRODUCTION_COSTS', $resourceName) * $this->counttotrain);

                $this->combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -$prodValue);
            }


            // системное сообщение
            $messparams[1] = "{b} ".$this->counttotrain."{/b}";
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "START_TRAIN_UNIT_MESS", $messparams, 0, 'GREEN');
        } else { // апрейтим эвент

            $existeq = (int)$res[0]['id_eventqueue'];
            assert($existeq > 0);

            // вычитаем ресурсы
            foreach(GB_ResourcesList::$allBuildResources as $k=>$resourceName)
            {
                if(!$this->unitAttributes->haveAttribute('PRODUCTION_COSTS', $resourceName))
                {
                    continue;
                }

                $prodValue = (float)($this->unitAttributes->getAttribute('PRODUCTION_COSTS', $resourceName) * $this->counttotrain);

                $this->combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -$prodValue);
            }

            // изменяем время окончания
            $eventOptions = DE_EventQueueAttributes::CreateFromEventID($existeq);
            $eventOptions->deltaAttribute('CountToTrain', $this->counttotrain);

            //Изменяем время окончания ивента
            $event = DE_EventQueue::CreateFromID((int)$existeq);
            $endDate = $event->getEndDate();
            $incDate = $this->unitAttributes->getAttribute('PRODUCTION_COSTS', 'CONSTRUCTION_TIME')
                 * $this->worldSettings->get('TURN_TIME') * 60 * $this->counttotrain;
            $userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
            if ($userInstance->buildSpeedupEndTime > time()) $incDate /= 2;
            $endDate->value = $endDate->value + $incDate;

            $event->setEndDate($endDate);
            $this->endtime = $endDate->getPgFormat();
            // апрейтим производство
            $sql = sprintf("
                    UPDATE 
                        ds_unitinproduction
                    SET 
                        amount = amount + %d,
                        endtime = endtime + text(%d*%d)::interval
                    WHERE 
                        id_attachedbuilding = %d",
            $this->counttotrain,
            $this->counttotrain,
            $this->timetotrainone,
            $this->attachedbid );

            $res = $common->executeQuery($sql);
            if($res === false)
            {
                $this->errormsg .= " SYSTEM ERROR - UA_TrainWarUnit::performAction13";
                return false;
            }

            $messparams[1] = "{b} ".$this->counttotrain."{/b}";
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "ADD_TRAIN_UNIT_MESS", $messparams, 0, 'GREEN' );
        }

        $res = "";
        $sql = "";

        // Тайм дельту приплюсуем
        $endTimeWithTimeDelta = GB_TypeFactory::CreateBase('GB_Date', $this->endtime);
        $endTimeWithTimeDelta->value += 3600 * DE_UserInstance::CreateFromID((int)$this->uiid)->getUser()->getTimeDelta();

        $this->factoryResultArray[] = array(
        'type' => $this->bid,
        'status' => 1,
        'unit' => DE_Unit::CreateFromID((int)$this->id_unit)->getName(),
        'count' => $this->counttotrain,
        'timeend' => $endTimeWithTimeDelta->getPgFormat(),
        'id_cell' => $this->id_cell
        );

        return true;
    }

    // время окончания
    function getEventEndTime()
    {
        $startDate = GB_TypeFactory::CreateBase('GB_Date', time());
        $endDate = GB_TypeFactory::CreateBase('GB_Date', time() + $this->timetotrainone * $this->counttotrain);

        $this->starttime = $startDate->getPgFormat();
        $this->endtime = $endDate->getPgFormat();

        return true;
    }

    function createEventParams ()
    {
        $this->eventparams["CountToTrain"] = $this->counttotrain;
        $this->eventparams["ID_Unit"] = $this->id_unit;
        $this->eventparams["ID_Comb"] = $this->comb;
        $this->eventparams["ID_AttachedBuilding"] = $this->attachedbid;
        $this->eventparams["TrainedCountAccumulator"] = 0;

        return true;
    }

    function getBuildingsInfoByCells()
    {
        global $common;

        $cells = implode(', ', $this->cells);

        $sql = "
            SELECT
                ab.id_building,
                ab.id_cell,
                c.cellnumber,
                ab.id_attachedbuilding,
                COALESCE(uip.amount, 0) as old_count
            FROM
                ds_attachedbuilding AS ab
                INNER JOIN ds_cell AS c ON (c.id_cell = ab.id_cell)
                LEFT JOIN ds_unitinproduction AS uip ON (uip.id_attachedbuilding = ab.id_attachedbuilding)
            WHERE
                ab.id_cell IN ($cells)
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        foreach($res as $k => $ab)
        {
            $this->cells[$k] = (int)$ab['cellnumber'];
            $this->attachedbids[$k] = (int)$ab['id_attachedbuilding'];
            $this->bids[$k] = (int)$ab['id_building'];
            $this->old_count[$k] = (int)$ab['old_count'];
        }
    }
}

?>