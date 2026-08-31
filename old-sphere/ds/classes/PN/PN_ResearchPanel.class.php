<?php $CVS_Version = '$Revision: 1.9 $ ; $Date: 2009/10/21 12:59:44 $';

//================================================
// закладка тренировки ученых и исследования науки

class PN_ResearchPanel extends UA_GetBuildMenu
{
    function __construct()
    {
        UA_GetBuildMenu::UA_GetBuildMenu ();
    }

    // доступна ли наука расе
    function isScienceAllowRace($nextlevid, $race)
    {
        global $common;

        $sql = sprintf ( "select id_science
                from ds_sciencecanberesearchedbyrace
                where id_science = %d
                and id_race = %d",
        $nextlevid, $race );

        $res = $common->getResultArray ( "id_science_nextlevid_id_race_".$nextlevid."_".$race, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
        if (!$res)
        {
            $this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::isScienceAllowRacel1";
            return false;
        }
        $res = "";
        return true;
    }

    // доступна ли наука расе
    function isBranchDisabled($id_world, $id_branch)
    {
        global $common;

        $sql = sprintf ( "
                SELECT
                    id_branch
                FROM
                    ds_disabledsciencebranch
                WHERE
                    id_world = %d
                    AND id_branch = %d
                ",
        $id_world,
        $id_branch
        );
        $res = $common->getResultArray ( "id_world_disabled_id_branch_".$id_world."_".$id_branch, $sql, "", "", "GLOBAL_CACHE" );
        assert( empty($common->errormsg) );
        if (count($res) == 0)
        {
            return false;
        }
        return true;
    }

    // может ли юзер исследовать науку
    function isScienceAllow($science, $comb, $uiid)
    {
        global $common;

        // уже есть
        $sql = sprintf ( "select id_science
            from ds_userhasscience
            where id_userinstance = %d
            and id_science = %d",
        $uiid, $science );

        $res = $common->getResultArray ( "id_science_id_userinstance_id_science_".$uiid."_".$science, $sql, "", $uiid, "LOCAL_CACHE" );
        if ($res === false)
        {
            $this->errormsg .= " SYSTEM ERROR ResearchPanel::isScienceAllow2";
            return false;
        }

        if (sizeof($res) != 0 )
        {
            return false;
        }

        // есть необходимые здания
        $sql = sprintf ( "select
            (select count(snb.id_building)
            from ds_scienceneedsbuilding snb
            where snb.id_science = %d) =
            (select count(snb.id_building)
            from ds_scienceneedsbuilding snb,
            ds_attachedbuilding ab
            where ab.id_building = snb.id_building
            and ab.id_comb = co.id_comb
            and snb.id_science = %d) as allow
            from ds_comb co
            where co.id_comb = %d",
        $science, $science, $comb );

        $res = $common->getResultArray ( "science_enough_building_id_science_id_comb_".$science."_".$comb, $sql, "", $uiid, "LOCAL_CACHE" );
        if ($res === false)
        {
            $this->errormsg .= " SYSTEM ERROR ResearchPanel::isScienceAllow3";
            return false;
        }

        if ($res[0]['allow'] == 'f')
        {
            return false;
        }

        // есть необходимые науки
        $sql = sprintf ( "select
            (select count(id_science_2)
            from ds_scienceneedsscience
            where id_science_1 = s.id_science) =
            (select count(sns.id_science_2)
            from ds_scienceneedsscience sns,
            ds_userhasscience uhs
            where sns.id_science_2 = uhs.id_science
            and sns.id_science_1 = s.id_science
            and uhs.id_userinstance = %d) as allow
            from ds_science s
            where s.id_science = %d",
        $uiid, $science );

        $res = $common->getResultArray ( "science_enough_science_is_userinstace_id_science_".$uiid."_".$science, $sql, "", $uiid, "LOCAL_CACHE" );
        if ($res === false)
        {
            $this->errormsg .= " SYSTEM ERROR ResearchPanel::isScienceAllow4";
            return false;
        }

        if ($res[0]['allow'] == 'f')
        {
            return false;
        }
        $res = "";
        return true;
    }

    function getPanel()
    {
        // сама закладка
        global $params;
        global $common;

        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();

        $turntime = $this->worldSettings->get('TURN_TIME');

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><research />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();

        $root->set_attribute ( "turn", $turntime * 60 );

        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

        // текущее кол-во рабочих
        $work = $combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER');

        // максимальное кол-во ученых
        $scientistmax = $combAttributes->getAttribute('MAX_AMOUNT', 'SCIENTIST');

        // текущее кол-во ученых
        $scientistcurrent = $combAttributes->getAttribute('CURRENT_AMOUNT', 'SCIENTIST');


        // тренируется ученых
        $sql = "
                SELECT 
                    value as scientistcurrent
                FROM
                    ds_eventhasoption eho1,
                    ds_eventoption eo1
                WHERE
                        eho1.id_eventoption = eo1.id_eventoption
                    AND eo1.optionname = 'CountToTrain'
                    AND eho1.id_eventqueue =
                    (
                        SELECT eq.id_eventqueue
                        FROM
                            ds_combbindeventqueue cbeq,
                            ds_eventqueue eq,
                            ds_event e,
                            ds_eventhasoption eho,
                            ds_eventoption eo
                        WHERE
                                cbeq.id_eventqueue = eq.id_eventqueue
                            AND cbeq.id_eventqueue = eho.id_eventqueue
                            AND eq.id_event = e.id_event
                            AND eq.istrash = false
                            AND cbeq.id_comb = ".$this->comb."
                            AND e.eventname = 'TRAIN_UNIT'
                            AND eho.id_eventoption = eo.id_eventoption
                            AND eo.optionname = 'ID_Unit'
                            AND value =
                            (
                                SELECT id_unit
                                FROM ds_unit
                                WHERE unitname = 'SCIENTIST'
                            )
                    )";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(sizeof($res) > 0)
        {
            $scientistcurrent += $res[0]['scientistcurrent'];
            $inprogress = $res[0]['scientistcurrent'];
        }

        $allowbyroom = $scientistmax - $scientistcurrent;

        $ch = $root->new_child ( "train" );
        $ch->set_attribute ( "work", $work );
        $ch->set_attribute ( "scientistmax", $scientistmax );

        // на сколько хватает ресурсов
        $unitAttributes = DE_Unit::CreateFromName('SCIENTIST')->getAttributes();

        $showResorces = array('CONSTRUCTION_TIME', 'WORKER', 'FOOD', 'KADERIUM', 'NANO_CRYSTAL');
        foreach($showResorces as $k=>$resourceName)
        {
            if(!$unitAttributes->haveAttribute('PRODUCTION_COSTS', $resourceName))
            {
                continue;
            }

            $prodValue = $unitAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);

            $ch->set_attribute(substr($resourceName, 0, 1), $prodValue);

            if($resourceName !=  'CONSTRUCTION_TIME')
            {
                $maxCountTemp[] = floor($combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName) / $prodValue);
            }
        }

        $scientistallow = min($maxCountTemp);

        if($scientistallow > $scientistmax)
        {
            $scientistallow = $scientistmax;
        }

        if($scientistallow > $allowbyroom)
        {
            $scientistallow = $allowbyroom;
        }

        if($scientistallow > $work)
        {
            $scientistallow = $work;
        }

        $ch->set_attribute ( "scientistallow", $scientistallow );

        if(isset($inprogress))
        {
            $ch->set_attribute ( "inprogress", $inprogress );
        }


        // идет ли в настоящий момент исследование
        $sql = sprintf ( "
                select cbeq.id_eventqueue
                from ds_combbindeventqueue cbeq,
                ds_comb co,
                ds_eventqueue eq,
                ds_event e
                where cbeq.id_comb = co.id_comb
                and co.id_userinstance = %d
                and cbeq.id_eventqueue = eq.id_eventqueue
                and eq.id_event = e.id_event
                and eq.isTrash = false
                and e.eventname = 'NEW_RESEARCH'",
        $params['uiid'] );
        $res = $common->getResultArray ( "comb_has_researche_id_comb_".$this->comb, $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if($res === false)
        {
            $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane8";
            return false;
        }

        if(sizeof($res) != 0)
        {
            $combresearch = 1;
        }else{
            $combresearch = 0;
        }



        // сколько ученых на всех сотах
        $userCombsCollection = DC_UserInstanceCombs::CreateFromUserInstanceID((int)$params['uiid']);

        $allscientists = 0;

        $userCombsCollection->resetNext();
        while($comb = $userCombsCollection->next())
        {
            $allscientists += $comb->getAttributes()->getAttribute('CURRENT_AMOUNT', 'SCIENTIST');
        }

        // базовый бонус науки
        $brb = (float)$this->worldSettings->get('RESEARCH_BASE_BONUS');

        // Просчет бонуса по открываемым наукам
        $sql = sprintf ( "
                SELECT
                    sban.id_attributename,
                    r.exchangerate
                FROM
                    ds_sciencebranch sbr,
                    ds_sciencebranchattributename sban,
                    ds_resource r
                WHERE
                    sbr.id_branch = sban.id_sciencebranch
                    and sban.id_attributename = r.id_attributename
                ");

        $res = $common->getResultArray ( "resource_exchangerate", $sql, "", "", "" );

        if ($res === false)
        {
            $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane10-2";
            return false;
        }

        $rb = array ();

        for ($i=0;$i<sizeof($res); $i++)
        {
            $rb[ $res[$i]['id_attributename'] ]['exchangerate'] = $res[$i]['exchangerate'];
        }

        // какие уровни науки доступны
        $sql = sprintf ( "
            SELECT
                s.id_science,
                uhs.starttime,
                uhs.endtime,
                sb.branchname,
                sb.id_branch,
                sban.id_attributename,
                ltl.text as localizedname,
                int4(substring(s.sciencename from '[[:digit:]]+')) as curlev,
                uhs.inresearch,
                (
                    SELECT
                        s2.id_science
                    FROM
                        ds_science as s2
                        LEFT JOIN ds_disabledscience AS ds ON (ds.id_science = s2.id_science)
                    WHERE
                        s2.sciencename = replace(
                                s.sciencename, 
                                substring(s.sciencename from '[[:digit:]]+'),
                                int4(substring(s.sciencename from '[[:digit:]]+'))+1
                            )
                        AND ds.id_science IS NULL
                ) as nextlevid
            FROM 
                ds_science s,
                ds_userhasscience uhs,
                ds_sciencebranch sb 
                LEFT JOIN ds_sciencebranchattributename sban ON sban.id_sciencebranch = sb.id_branch,
                ds_localizedtextlanguage ltl,
                (
                    SELECT 
                        max(uhs1.id_science) as id_s
                    FROM 
                        ds_userhasscience uhs1,
                        ds_science s1
                    WHERE 
                        uhs1.id_science = s1.id_science
                        AND uhs1.id_userinstance = %d
                    GROUP BY s1.id_branch
                ) t
            WHERE 
                s.id_science = uhs.id_science
                AND uhs.id_userinstance = %d
                AND s.id_branch = sb.id_branch
                AND t.id_s = s.id_science
                AND sb.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = %d
            ORDER BY sb.outorder
                            ",
        $params["uiid"],
        $params["uiid"],
        $params["lang"] );
        $res = $common->getResultArray ( "allowed_science_id_userinstance_".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if ($res === false)
        {
            $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane10-3";
            return false;
        }

        // Для покупки ускорения науки
        $scienceAcceleration = array();

        for ($i=0;$i<sizeof($res);$i++)
        {
            $ch = $root->new_child ( "science" );
            $ch->set_attribute ( "branchid", $res[$i]['branchname'] );
            $ch->set_attribute ( "ln", urlencode($res[$i]['localizedname']) );

            // Аттрибут по бонусу левела науки
            //if (isset($rb[$res[$i]['id_attributename']] ) && $res[$i]['nextlevid'] != "")
            if (isset($rb[$res[$i]['id_attributename']] ))
            {
                $rbCurLevel = ( $brb / $rb[ $res[$i]['id_attributename'] ]['exchangerate'] )*( $res[$i]['curlev']-1 );
                $ch->set_attribute ( "bonusall", round($rbCurLevel*100, 3) );

                if ($res[$i]['nextlevid'] != "")
                {
                    $rbNewLevel = ( $brb / $rb[ $res[$i]['id_attributename'] ]['exchangerate'] )*( $res[$i]['curlev'] );
                    $ch->set_attribute ( "bonus", round(($rbNewLevel - $rbCurLevel)*100, 3) );
                }

                //$ch->set_attribute ( "id_res", $res[$i]['id_attributename'] );
            }

            if ($res[$i]['inresearch'] == 't')
            {
                // если в процессе исследования
                $sql = sprintf ( "select uhsbeq.id_eventqueue,
                        (eq.eventendtime <= now() ) as finished,
                        to_char ( eq.eventendtime + '%f hour'::interval, 'DD.MM HH24:MI') as enddate
                        from ds_userhasscience uhs,
                        ds_eventqueue eq,
                        ds_userhassciencebindeventqueue uhsbeq
                        where uhs.id_science = uhsbeq.id_science
                        and uhs.id_userinstance = uhsbeq.id_userinstance
                        and uhsbeq.id_eventqueue = eq.id_eventqueue
                        and uhs.id_science = %d
                        and uhs.id_userinstance = %d",
                $delta,
                $res[$i]['id_science'],
                $params["uiid"] );

                $res2 = $common->getResultArray ( "id_eventqueue_id_science_id_userinstance_".$res[$i]['id_science']."_".$params["uiid"], $sql, "", "", "" );
                if ($res2 === false)
                {
                    $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane11";
                    return false;
                }

                assert(key_exists('id_eventqueue',$res2[0]));
                $id_eventqueue = $res2[0]['id_eventqueue'];

                if ($res2[0]['finished'] == 't')
                {
                    // закончено
                    $sql = sprintf ( "select ltl.text
                            from ds_localizedtextlanguage ltl,
                            ds_systemmessage sm
                            where ltl.id_localizedtext = sm.id_localizedtext
                            and ltl.id_language = %d
                            and sm.messagename = 'FINISHED'",
                    $params['lang'] );

                    $res3 = $common->getResultArray ( "system_message_finished_id_language_".$params["lang"], $sql, "", "", "GLOBAL_CACHE" );
                    if ($res3 === false)
                    {
                        $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane12";
                        return false;
                    }

                    $ch->set_attribute ( "enddate", urlencode($res3[0]['text']) );
                } else {
                    $event = DE_EventQueue::CreateFromID((int)$res2[0]['id_eventqueue']);
                    $messparams[3] = $event->getEndDate()->getSystemMessageFormat();
                    $ch->set_attribute ( "enddate", $event->getEndDate()->getFormatedFull($delta));
                    //$ch->set_attribute ( "enddate", $res2[0]['enddate'] );


                    // Для кнопочки ускорения
                    $scienceAcceleration[$i]['avail'] = 1;
                    $scienceAcceleration[$i]['science_name'] = $res[$i]['localizedname'];
                    $scienceAcceleration[$i]['level'] = $res[$i]['curlev'];
                    $scienceAcceleration[$i]['branchname'] = $res[$i]['branchname'];
                    $scienceAcceleration[$i]['id_eventqueue'] = (int)$res2[0]['id_eventqueue'];
                }

                // на сколко процентов
                $sql = "select getprogress( $id_eventqueue ) as prog";
                $res2 = $common->getResultArray ( "progress_id_eventqueue_".$id_eventqueue, $sql, "", "", "" );
                if($res2 === false)
                {
                    $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane13";
                    return false;
                }

                $ch->set_attribute ( "lev", $res[$i]['curlev'] );
                $ch->set_attribute ( "ir", number_format ($res2[0]['prog'], 0, ".", " " ) );
            } else {
                // не исследуется

                if ($res[$i]['nextlevid'] != "")
                {
                    // если не достигнуи максимальный уровень
                    if( $this->isBranchDisabled($_SESSION['world'], $res[$i]['id_branch']))
                    {
                        $ch->set_attribute ( "allow", 0 ); // Branch Disabled
                        continue;
                    }

                    $ch->set_attribute ( "nextlev", $res[$i]['curlev'] );

                    if ($this->isScienceAllow ( $res[$i]['nextlevid'], $this->comb, $params["uiid"] ) && $combresearch != 1)
                    {
                        $scienceallow = 1;
                    } else {
                        $scienceallow = 0;
                    }

                    if ( $this->isScienceAllowRace($res[$i]['nextlevid'], $params["race"]))
                    {
                        $raceallow = 1;
                    } else {
                        $raceallow = 0;
                    }

                    if ($scienceallow == 1 && $raceallow == 1 && $allscientists > 0)
                    {

                        $ch->set_attribute ( "allow", "1" );

                        // бонус расы к археологии
                        $raceID = DE_UserInstance::CreateFromID((int)$params['uiid'])->getRaceID();
                        $sql = "select rha.value
                                from
                                    ds_racehasattribute rha,
                                    ds_attribute a,
                                    ds_attributename atn,
                                    ds_attributetype att
                                where
                                        rha.id_race = ".$raceID."
                                    and rha.id_attribute = a.id_attribute
                                    and a.id_attributename = atn.id_attributename
                                    and a.id_attributetype = att.id_attributetype
                                    and att.typename = 'BONUS_RELATIVE'
                                    and atn.attributename = 'SCIENCE'";
                        $res2 = $common->getResultArray ( "racehasbonus_science_ID_RACE_".$raceID, $sql, "", "", "" );
                        if ($res2 === false)
                        {
                            $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane14";
                            return false;
                        }

                        if (sizeof($res2 ) > 0)
                        {
                            $science_bonus = (float)$res2[0]['value'];
                        } else {
                            $science_bonus = 0;
                        }

                        // время исследования
                        $sql = sprintf ( "select researchcosts/%f as turns
                                from ds_science
                                where id_science = %d",
                                ( $allscientists + $allscientists * $science_bonus ),
                                $res[$i]['nextlevid'] );


                                $res2 = $common->getResultArray ( "researche_time_id_comb_nextlevid_".$this->comb."_".$res[$i]['nextlevid'], $sql, "", $params["uiid"], "LOCAL_CACHE" );

                                if ($res2 === false)
                                {
                                    $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane15";
                                    return false;
                                }

                                //$ch->set_attribute ( "C", (float)number_format ( $res2[0]['turns'], 0, ".", " " ) );

                                $sec = ceil($res2[0]['turns'] * $turntime * 60);
                                $sql = sprintf ( "select ltl.text
                                from ds_localizedtextlanguage ltl,
                                ds_systemmessage sm
                                where ltl.id_localizedtext = sm.id_localizedtext
                                and ltl.id_language = %d
                                and sm.messagename = 'RESEARCH_TIME_MASK'",
                                $params['lang'] );

                                $res2 = $common->getResultArray ( "system_message_research_time_mask_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
                                if ( $res2 === false )
                                {
                                    $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane16";
                                    return false;
                                }

                                $timemask = trim ( $res2[0]['text'] );
                                list ( $d, $h, $m ) = split ( " ", $timemask );

                                /*$sql = sprintf ( "select date_part ( 'day', text (%d )::interval ) as days,
                                date_part ( 'hour', text ( %d )::interval ) as hours,
                                date_part ( 'minute', text ( %d )::interval ) as minutes",
                                $sec, $sec, $sec );

                                $res2 = $common->getResultArray ( "get_days_minutes_seconds_sec_".$sec, $sql, "", "", "GLOBAL_CACHE" );
                                if ( $res2 === false ) {
                                $this->errormsg .= " SYSTEM ERROR ResearchPanel::getPane17";

                                return false;
                                }*/

                                $periodParts = GB_TypeFactory::CreateBase('GB_TimePeriod', $sec)->getWithParts();
                                $res2[0]['days'] = $periodParts['DAYS'];
                                $res2[0]['hours'] = $periodParts['HOURS'];
                                $res2[0]['minutes'] = $periodParts['MINUTES'];

                                $time = "";
                                if ($res2[0]['days'] != 0 )
                                {
                                    $d = str_replace ( "%", $res2[0]['days'], $d );
                                    $time .= $d." ";
                                }

                                if ($res2[0]['hours'] != 0)
                                {
                                    $h = str_replace ( "%", $res2[0]['hours'], $h );
                                    $time .= $h." ";
                                }
                                if ($res2[0]['minutes'] != 0)
                                {
                                    $m = str_replace ( "%", $res2[0]['minutes'], $m );
                                    $time .= $m;
                                }


                                if ($res2[0]['minutes'] == 0 && $res2[0]['hours'] == 0 && $res2[0]['days'] == 0)
                                {
                                    $m = str_replace ( "%", 1, $m );
                                    $time .= " ".$m;
                                }

                                $ch->set_attribute ( "C", $time );
                                //$ch->set_attribute ( "C", '-');//$time );
                    }

                } else {
                    $ch->set_attribute ( "lev", $res[$i]['curlev'] );
                }
            }
        }

        // Получаем количество денег у игрока
        $world = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());
        $member = new MU_EntityOperationMember(DE_User::CreateFromID($_SESSION['uid']), $world);
        $account = MU_SearchOperations::GetAccountData($member);
        assert(!empty($account));

        // Кнопочки ускорения науки
        if (DS_PaymentService::IsServiceActive('PS_BY_SCIENCE_ACCELERATION')) {
            foreach ($scienceAcceleration as $key => $data)
            {
                $ch = $root->new_child("MSG");
                $ch->set_attribute("id", $key);
                $ch->set_attribute("titl", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('SCIENCE_ACCELERATION', array(), null, null, false));
                $ch->set_attribute("head", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('SCIENCE_ACCELERATION', array(), null, null, false));

                // Данная услуга позволяет ускорить изучение текущей науки. Величина ускорения зависит от настроек мира, поэтому в разных мирах ускорение может ... часами.
                $txt = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('SCIENCE_ACCELERATION_DESC', array(), null, null, false);
                $ch->set_attribute("txt",  $txt);

                // Выбираем минимальный уровень науки, который можно ускорить
                $minScienceLevel = DE_WorldSettings::CreateForCurentWorld()->get('SCIENCE_ACCELERATION_MIN_LEVEL');
                assert($minScienceLevel >= 1 && $minScienceLevel <= 149);

                if ($data['level'] < $minScienceLevel) {
                    // Мы не можем ускорять этот уровень науки
                    $ch->set_attribute("avail", 0);
                    //Нельзя ускорить науку менее % уровня
                    $status = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('SCIENCE_ACCELERATION_ERROR2', array($minScienceLevel), null, null, false);
                    $ch->set_attribute("status", $status);
                } elseif (DS_PaymentService::IsScienceAcceleraded($data['id_eventqueue'])) {
                    // У нас наука уже является ускоренной
                    $ch->set_attribute("avail", 0);
                    // Вы не можете воспользоваться данной услугой, так как Вы уже ускорили изучение текущей науки.
                    $status = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('SCIENCE_ACCELERATION_ERROR1', array(), null, null, false);
                    $ch->set_attribute("status", $status);
                } else {

                    // Ивент
                    $event = DE_EventQueue::CreateFromID($data['id_eventqueue']);

                    // Множитель ускорения науки
                    $acceletarorMultiplier = DE_WorldSettings::CreateForCurentWorld()->get('SCIENCE_ACCELERATION_MULTIPLIER');
                    assert($acceletarorMultiplier >= 0 && $acceletarorMultiplier <= 1);

                    // Время, оставшееся у игрока до окончания изучения науки
                    $now = GB_TypeFactory::CreateBase('GB_Date', time());
                    $oldEndDate = GB_TypeFactory::CreateBase('GB_Date', $event->getEndDate()->value);
                    // Если время изучения уже прошло
                    if ($oldEndDate->value - $now->value <= 0) {
                        continue;
                    }

                    $oldPeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $oldEndDate->value - $now->value);

                    //Новое время окончания
                    $newEndDTime = $oldEndDate->value - $acceletarorMultiplier*($oldEndDate->value - $now->value);
                    $newEndDate = GB_TypeFactory::CreateBase('GB_Date', $newEndDTime);
                    $newPeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $newEndDTime - $now->value);

                    // Множитель цены (на сколько часов ускоряем науку)
                    $priceMultiplier = (int)ceil(($oldEndDate->value - $newEndDate->value)/3600);
                    assert($priceMultiplier > 0);

                    //получаем цену
                    $price = DS_PaymentService::GetSciencePrice($data['branchname'].'_'.$data['level'])*$priceMultiplier;
                    // assert($price > 0);

                    // Формируем время
                    $localization = DE_Localization::CreateForCurentLang();
                    $daysSimvol = $localization->get(30003);
                    $hoursSimvol = $localization->get(30004);
                    $minsSimvol = $localization->get(30005);

                    // Старое время окончания науки
                    $oldDateArr = $oldPeriod->getWithParts();
                    $oldDate = $oldDateArr['DAYS']." ".$daysSimvol." ".$oldDateArr['HOURS']." ".$hoursSimvol." ".$oldDateArr['MINUTES']." ".$minsSimvol;

                    // Новое время окончания науки
                    $newDateArr = $newPeriod->getWithParts();
                    $newDate = $newDateArr['DAYS']." ".$daysSimvol." ".$newDateArr['HOURS']." ".$hoursSimvol." ".$newDateArr['MINUTES']." ".$minsSimvol;

                    // ...До окончания изучения % уровня науки % осталось %. С ускорением - % Стоимость услуги % серебряных кредитов. Серебро снимается со счета в мире.
                    $messageParams[0] = $data['level']; // Уровень науки
                    $messageParams[1] = $data['science_name']; // Название науки
                    $messageParams[2] = $oldDate; // Старое время изучения
                    $messageParams[3] = $newDate; // Новое время изучения
                    $messageParams[4] = $price; // Стоимость
                    $txt = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('SCIENCE_ACCELERATION_DESC1', $messageParams, null, null, false);

                    //Смотрим достаточно ли денег у игрока
                    if($account['SC'] < $price)
                    {
                        $ch->set_attribute("avail", 0);
                        $txt .= '%0A'.DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NOT_ENOUGH_CREDIT', array(), null, null, false);
                    } else {
                        $ch->set_attribute("avail", 1);
                    }
                    $ch->set_attribute("status",  $txt);
                }
            }
        }
            
        // Кнопка "Купить ученых"
        if (DS_PaymentService::IsServiceActive('PS_BY_UNIT_SCIENTIIFIC')) {
            // Получаем цену
            $price = UC_PaymentServiceMoneyOperation::GetServicePrice('PS_BY_UNIT_SCIENTIIFIC');

            // Получаем максимальное количество ученых, которое мы можем купить
            $maxAmount = DS_PaymentService::GetMaxUnitAmount($this->comb, (int)$_SESSION['uiid'], 'SCIENTIST');

            // Максимальное количество пакетов, на которое у игрока хватает денег
            $avail = min($maxAmount, (int)floor($account['SC']/$price));

            // Высчитываем количество юнитов, на которое нам хватает места
            $ch = $root->new_child("MSG");
            $ch->set_attribute("id", 32);
            $ch->set_attribute("titl", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BY_UNIT_SCIENTIIFIC', array(), null, null, false));
            $ch->set_attribute("head", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BY_UNIT_SCIENTIIFIC', array(), null, null, false));
            $ch->set_attribute("txt", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BY_UNIT_SCIENTIIFIC_DESC', array($price), null, null, false));
            $ch->set_attribute("rem", str_replace('0A', '%0A', DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BY_UNIT_SCIENTIIFIC_MAX', array(DE_WorldSettings::CreateForCurentWorld()->get('SCIENTIST_BUY_LIMIT'), $avail), null, null, false)));
            $ch->set_attribute("avail", $avail);
            $ch->set_attribute("price", $price);
            $ch->set_attribute("res", 0);

            // Если мы не можем купить ни одного ученого, потому что нет денег
            if ($avail == 0  && $maxAmount != 0) {
                // Кончились деньги
                $ch->set_attribute("status", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NOT_ENOUGH_CREDIT', array(), null, null, false));
            } else {
                $ch->set_attribute("status", "");
            }

            $ch->set_attribute("desc", "BUY_SCIENTISTS");
        }
        // закладки
        $this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );
        SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );

        return true;
    }


}

?>