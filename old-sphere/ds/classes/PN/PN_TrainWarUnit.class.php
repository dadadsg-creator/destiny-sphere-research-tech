<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка тренировки военных юнитов
class PN_TrainWarUnit extends UA_GetBuildMenu {



    function __construct()
    {
        UA_GetBuildMenu::UA_GetBuildMenu ();
    }

    function getPanel ()
    {

        global $params;
        global $common;

        // корректировка времени
        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><warunit />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();
        //$root->set_attribute ( "c", $this->cellnum );

        $root->set_attribute ( "turn", $this->worldSettings->get('TURN_TIME')*60 );

        $userInstance = DE_UserInstance::CreateFromID((int)$params["uiid"]);
        if ($userInstance->buildSpeedupEndTime > time()) $speedup=1;
        else $speedup=0;
        $root->set_attribute ("speedup", $speedup);

        // текущее кол-во рабочих
        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
        $work = $combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER');

        // Текущие уровни науки
        $sql = "select sb.outorder, max(s.level) as curlevel from ds_science s, ds_sciencebranch sb, ds_userhasscience uhs
            where s.id_branch=sb.id_branch and s.id_science=uhs.id_science
            and not uhs.inresearch and id_userinstance=".$params["uiid"]."
            group by sb.outorder order by sb.outorder";
        $res = $common->getResultArray ("", $sql);
        $curTech = array(0, 0, 0, 0, 0, 0, 0);
        foreach ($res as $row) {
            $curTech[$row['outorder']-1] = $row['curlevel'];
        }
        $sccur = implode(",", $curTech);

        // производятся юниты в этом здании или нет
        $sql = sprintf ( "select u.id_unit,
                u.unitname,
                up.amount,
                to_char ( up.endtime  + '%f hour'::interval, 'DD.MM HH24:MI' ) as endtime
                from ds_unitinproduction up,
                ds_unit u
                where u.id_unit = up.id_unit
                and up.id_attachedbuilding = %d",
        $delta,
        $this->attachedbid );

        $res = $common->getResultArray ( "unit_in_production_id_attachedbuilding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if ( $res === false )
        {
            $this->errormsg .= " SYSTEM ERROR TrainWarUnit::getPanel3";
            return false;
        }

        if ( sizeof ( $res ) == 0 )
        {
            // не производятся
            // есть ли этот список в сессии
            if(key_exists("allow_units", $_SESSION) && false)
            {
                $au = $_SESSION["allow_units"];
            }else{
                $au = array();
            }


            if ( isset ( $au[$this->comb][$this->bid]))
            {
                // если есть, берем из сессии
                //$this->logResponse ( "From session\n".$au[$this->comb][$this->bid] );
                //print $au[$this->comb][$this->bid];
                SU_OutputHandler::Singleton()->addReadyString( $au[$this->comb][$this->bid] );
            }
            else
            {
                // если нет, берем из базы
                $sql = sprintf ( "select t.id_unit,
                        u.*,
                    (select sum(uha.value*(select exchangerate 
                        from ds_resource 
                        where id_attributename = atn.id_attributename))
                        from ds_unithasattribute uha,
                        ds_attribute a,
                        ds_attributename atn,
                        ds_attributetype att
                        where uha.id_attribute = a.id_attribute
                        and a.id_attributename = atn.id_attributename
                        and a.id_attributetype = att.id_attributetype
                        and att.typename = 'PRODUCTION_COSTS'
                        and atn.attributename != 'CONSTRUCTION_TIME'
                        and uha.id_unit = u.id_unit) as ttt
                        from 
                        (select pu.id_building,
                        pu.id_unit,
                        ucbbr.id_race,
                        (select
                        (select count(id_unit)
                        from ds_unitneedsscience
                        where id_unit = pu.id_unit) =
                        (select count(uns.id_unit)
                        from ds_unitneedsscience uns,
                        ds_userhasscience uhs
                        where uns.id_science = uhs.id_science
                        and uhs.inresearch = false
                        and uhs.id_userinstance = %d
                        and uns.id_unit = pu.id_unit)) as senough,
                        (select
                        (select count(id_unit)
                        from ds_unitneedsbuilding unb
                        where id_unit = pu.id_unit) =
                        (select count(unb.id_unit)
                        from ds_unitneedsbuilding unb,
                        ds_attachedbuilding ab
                        where unb.id_building = ab.id_building
                        and ab.active = true
                        and ab.id_comb = %d
                        and id_unit = pu.id_unit)) as benough
                        from ds_produceableunit pu,
                        ds_unitcanbebuildbyrace ucbbr
                        where pu.id_unit = ucbbr.id_unit
                        and pu.id_building = %d
                        and ucbbr.id_race = %d) t,
                        ds_unit u
                        where t.id_unit = u.id_unit
                        -- and t.senough = true
                        and t.benough = true
                        order by ttt",
                $params["uiid"],
                $this->comb,
                $this->bid,
                $params["race"] );

                $res = $common->getResultArray ( "all_allowed_units_id_language_id_userinstace_id_comb_id_building_id_race_".$params["lang"]."_".$params["uiid"]."_".$this->comb."_".$this->bid."_".$params["race"], $sql, "", $params["uiid"], "" );
                if ( $res === false )
                {
                    $this->errormsg .= " SYSTEM ERROR TrainWarUnit::getPanel4";
                    return false;
                }

                $userInstance = DE_UserInstance::CreateFromID((int)$params["uiid"]);

                for ( $i=0; $i<sizeof ( $res ); $i++ )
                {
                    // Если это юнит только для захватываемой колонии
                    if ((int)$res[$i]['id_unit'] == 98 || (int)$res[$i]['id_unit'] == 99 || (int)$res[$i]['id_unit'] == 93) {
                        // Если это не захватываемая колония
                        if (!DE_Comb::CreateFromID($this->comb)->getIsCapture()) {
                            continue;
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

                    $unit = DE_Unit::CreateFromID((int)$res[$i]['id_unit']);
                    $liveUpkeep = GL_LiveUpkeep::CountUnits($unit, 1, $userInstance);

                    $food = (float)round($liveUpkeep['FOOD'], 6);
                    $energy = (float)round($liveUpkeep['STASIS_ENERGY'], 6);
                    $kad = (float)round($liveUpkeep['KADERIUM'], 6);
                    $nano = (float)round($liveUpkeep['NANO_CRYSTAL'], 6);
                    $prod = (float)round($liveUpkeep['PRODIUM'], 6);

                    $ch = $root->new_child ( "unit" );
                    $ch->set_attribute ( "unitname", $res[$i]['unitname'] );
                    $ch->set_attribute ( "id", $res[$i]['id_unit'] );
                    /***** Специальная затыка для апкипа ********/
                    $ch->set_attribute ( "food",  number_format($food, 6, '.', ''));
                    $ch->set_attribute ( "energy", number_format($energy, 6, '.', ''));
                    $ch->set_attribute ( "kad", number_format($kad, 6, '.', ''));
                    $ch->set_attribute ( "nano", number_format($nano, 6, '.', ''));
                    $ch->set_attribute ( "prod", number_format($prod, 6, '.', ''));
                    /********************************************/
                    $ch->set_attribute ( "sccur", $sccur);

                    $avail = 1;
                    $sql = "select sb.outorder, max(s.level) as needlevel from ds_science s, ds_sciencebranch sb, ds_unitneedsscience uns
                        where s.id_branch=sb.id_branch and s.id_science=uns.id_science
                        and id_unit=".$res[$i]['id_unit']."
                        group by sb.outorder order by sb.outorder";
                    $resSc = $common->getResultArray ("unit_need_science_".$res[$i]['id_unit'], $sql);
                    assert(empty($common->errormsg));
                    $needTech = array(0, 0, 0, 0, 0, 0, 0);
                    foreach ($resSc as $row) {
                        $needTech[$row['outorder']-1] = $row['needlevel'];
                        if ($row['needlevel'] > $curTech[$row['outorder']-1]) $avail = 0;
                    }
                    $sctarg = implode(",", $needTech);
                    $ch->set_attribute ( "sctarg", $sctarg);
                    $ch->set_attribute ( "avail", $avail);

                    // Если это юнит только для захватываемой колонии
                    if ((int)$res[$i]['id_unit'] == 98 || (int)$res[$i]['id_unit'] == 99 || (int)$res[$i]['id_unit'] == 93) {
                        $ch->set_attribute ( "maxallow", '1');
                    }

                }

                // заклады
                $this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );
                //$panel = "xmldata=";
                //$panel .= $doc->dump_mem();
                $au[$this->comb][$this->bid] = SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );
                $_SESSION["allow_units"] = $au; // список в сессию

                //$this->logResponse ( $panel );
                //print $panel;
            }

        }
        else
        {
            $userInstance = DE_UserInstance::CreateFromID((int)$params["uiid"]);
            $unit = DE_Unit::CreateFromID((int)$res[0]['id_unit']);

            $liveUpkeep = GL_LiveUpkeep::CountUnits($unit, 1, $userInstance);

            $food = (float)round($liveUpkeep['FOOD'], 6);
            $energy = (float)round($liveUpkeep['STASIS_ENERGY'], 6);
            $kad = (float)round($liveUpkeep['KADERIUM'], 6);
            $nano = (float)round($liveUpkeep['NANO_CRYSTAL'], 6);
            $prod = (float)round($liveUpkeep['PRODIUM'], 6);

            // в здании уже идет производство
            $ch = $root->new_child ( "unit" );
            $ch->set_attribute ( "unitname", $res[0]['unitname'] );
            $ch->set_attribute ( "id", $res[0]['id_unit'] );
            $ch->set_attribute ( "count", $res[0]['amount'] );
            $ch->set_attribute ( "enddate", $res[0]['endtime']);

            /***** Специальная затыка для апкипа ********/
            $ch->set_attribute ( "food",  number_format($food, 6, '.', ''));
            $ch->set_attribute ( "energy", number_format($energy, 6, '.', ''));
            $ch->set_attribute ( "kad", number_format($kad, 6, '.', ''));
            $ch->set_attribute ( "nano", number_format($nano, 6, '.', ''));
            $ch->set_attribute ( "prod", number_format($prod, 6, '.', ''));

            /********************************************/
            $ch->set_attribute ( "sccur", $sccur);

            $sql = "select sb.outorder, max(s.level) as needlevel from ds_science s, ds_sciencebranch sb, ds_unitneedsscience uns
                where s.id_branch=sb.id_branch and s.id_science=uns.id_science
                and id_unit=".$res[0]['id_unit']."
                group by sb.outorder order by sb.outorder";
            $res = $common->getResultArray ("unit_need_science_".$res[$i]['id_unit'], $sql);
            $needTech = array(0, 0, 0, 0, 0, 0, 0);
            $avail = 1;
            foreach ($res as $row) {
                $needTech[$row['outorder']-1] = $row['needlevel'];
                if ($row['needlevel'] > $curTech[$row['outorder']-1]) $avail = 0;
            }
            $sctarg = implode(",", $needTech);
            $ch->set_attribute ( "sctarg", $sctarg);
            $ch->set_attribute ( "avail", $avail);

            // Если это юнит только для захватываемой колонии
            if ((int)$res[0]['id_unit'] == 98 || (int)$res[0]['id_unit'] == 99 || (int)$res[0]['id_unit'] == 93) {
                $ch->set_attribute ( "maxallow", '0');
            }

            $this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );
            //$panel = "xmldata=";
            //$panel .= $doc->dump_mem();
            //$this->logResponse ( $panel );
            // print $panel;
            SU_OutputHandler::Singleton()->addString( $doc->dump_mem(), 'xmldata' );
        }

        $res = "";
        $res2 = "";
        $res3 = "";
        $doc = "";
        $root = "";
        return true;
    }
}



?>