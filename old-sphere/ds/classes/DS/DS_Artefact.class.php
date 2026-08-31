<?php $CVS_Version = '$Revision: 1.22 $ ; $Date: 2010/07/07 08:43:35 $';

class DS_Artefact extends DS_Base
{
    static public function GetByArtefactInstanceID($artefactinstaneID)
    {
        settype ($artefactinstaneID, 'integer');
        assert ($artefactinstaneID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    a.id_artefact,
                    a.id_artefactscope,
                    a.id_artefacttype,
                    a.id_artefactprobability,
                    a.artefactname,
                    a.id_localizedname,
                    a.artefactpower,
                    a.destroyable,
                    a.quest,
                    artefact_score
                FROM
                    ds_artefact AS a
                    INNER JOIN ds_artefactinstance AS ai ON (a.id_artefact = ai.id_artefact)
                WHERE
                    ai.id_artefactinstance = ".$artefactinstaneID;

        $res = $common->getResultArray ( self::FormMark(__METHOD__, $artefactinstaneID), $sql, "", "FULL_PREFIX", "GLOBAL_CACHE" );
        assert(empty($common->errormsg));

        if(sizeof($res) > 0)
        {
            return $res[0];
        }else{
            return false;
        }
    }

    static public function GetByArtefactID($artefactID)
    {
        assert(is_int($artefactID) && $artefactID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    a.id_artefact,
                    a.id_artefactscope,
                    a.id_artefacttype,
                    a.id_artefactprobability,
                    a.artefactname,
                    a.id_localizedname,
                    a.artefactpower,
                    a.destroyable,
                    a.quest,
                    artefact_score
                FROM
                    ds_artefact AS a
                WHERE
                    a.id_artefact = ".$artefactID;

        $res = $common->getResultArray(self::FormMark(__METHOD__, $artefactID), $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        if(sizeof($res) > 0)
        {
            return $res[0];
        }else{
            return false;
        }
    }

    static public function InsertArtefactInstance(DE_Artefact $artefact)
    {
        $common = self::getCommon();
        $lifetime = 0;
        $immortal = 'TRUE';
        $birth_time = 'NULL';
        $death_time = 'NULL';

        //Артефакты в мире временные
        $allArtTemporary = DE_WorldSettings::CreateForCurentWorld()->get('ALL_ARTEFACTS_ARE_TEMPORARY');

        if($allArtTemporary == 1) {
            // Получаем время жизни арта
            $lifetime = DE_WorldSettings::CreateForCurentWorld()->get('ARTEFACT_LIFETIME_'.DS_Artefact::GetArtefacrtProbability($artefact->getArtefactID()));
            $immortal = 'FALSE';
            $birth_time = 'NOW()';
            $death_time = "NOW() + cast (".$lifetime." || ' months' as interval)";
        }

        $sql = "insert into DS_ArtefactInstance
                (
                    id_artefactinstance,
                    ID_Artefact,
                    immortal,
                    birth_time, 
                    death_time
                ) values (
                    ".$artefact->getID().",
                    ".$artefact->getArtefactID().",
                    ".$immortal.", 
                    $birth_time,
                    $death_time
                )";
        $res = $common->executeQuery ($sql);
        assert(empty($common->errormsg));
    }

    static public function GetArtefactData($artefactID)
    {
        assert (is_int($artefactID) && $artefactID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    *
                FROM
                    ds_artefact AS a
                WHERE
                    a.id_artefact = ".$artefactID;

        $res = $common->getResultArray ( self::FormMark(__METHOD__, $artefactID), $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        if(sizeof($res) == 1)
        {
            return $res[0];
        }else{
            return false;
        }
    }

    static public function GetNewArtefatcInstanceID()
    {
        $common = self::getCommon();

        $sql = "select nextval('ds_artefactinstance_id_artefactinstance_seq') as id";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg) && count($res) == 1);

        return (int)$res[0]['id'];
    }

    static public function GetArtefactAtrributes ($artefactID, $withCache = true)
    {
        settype ( $artefactID, 'integer' );
        assert ( $artefactID > 0 );

        $common = self::getCommon();

        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "";
        }

        $sql = "
                SELECT
                    attt.typename,
                    attn.attributename,
                    ahb.value
                FROM
                    ds_artefacthasbonus AS ahb
                    INNER JOIN ds_attribute AS att ON (ahb.id_attribute = att.id_attribute)
                    INNER JOIN ds_attributetype AS attt ON (att.id_attributetype = attt.id_attributetype)
                    INNER JOIN ds_attributename AS attn ON (att.id_attributename = attn.id_attributename)
                    INNER JOIN ds_artefact AS a ON (ahb.id_artefact = a.id_artefact)
                WHERE
                    a.id_artefact = ".$artefactID;

        $res = $common->getResultArray ( self::FormMark(__METHOD__, $artefactID), $sql, "", "SHORT_PREFIX", $cacheType );
        assert(empty($common->errormsg));

        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }
    }//END OF METHOD

    static public function GetArtefactsByBuildAttachedID($buildID, $collection=1)
    {
        settype ( $buildID, 'integer' );
        assert ( $buildID > 0 );

        $common = self::getCommon();

        $sql = "SELECT
                    aab.id_artefact
                FROM
                    ds_artefactattachedbuilding AS aab
                WHERE
                    aab.id_attachedbuilding = $buildID
                and aab.collection = $collection";

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }
    }

    static public function GetSummaryBonnusByAttachedBuildingID($attachedBuildingID)
    {
        settype($attachedBuildingID, 'integer');
        assert($attachedBuildingID > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    att.typename,
                    atn.attributename,
                    sum(ahb.value) as val
                FROM
                    ds_artefactattachedbuilding AS aab
                    INNER JOIN ds_artefactinstance AS ai ON (aab.id_artefact = ai.id_artefactinstance)
                    INNER JOIN ds_artefact AS a ON (ai.id_artefact = a.id_artefact)
                    INNER JOIN ds_artefacthasbonus AS ahb ON (ai.id_artefact = ahb.id_artefact)
                    INNER JOIN ds_attribute AS at ON (ahb.id_attribute = at.id_attribute)
                    INNER JOIN ds_attributetype AS att ON (att.id_attributetype = at.id_attributetype)
                    INNER JOIN ds_attributename AS atn ON (atn.id_attributename = at.id_attributename)
                WHERE
                    aab.id_attachedbuilding = ".$attachedBuildingID."
                GROUP BY att.typename, atn.attributename";

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetCombAlianceAndComplexAttribute(DE_Comb $comb)
    {
        $common = self::getCommon();

        $sql = "
            SELECT
                out.typename,
                out.attributename,
                sum(out.value) as value
            FROM
                (
                    SELECT
                        att.typename,
                        atn.attributename,
                        ahb.value
                    FROM
                        ds_artefactattachedcomplex AS aacc
                        INNER JOIN ds_artefactinstance AS ai ON (aacc.id_artefact = ai.id_artefactinstance)
                        INNER JOIN ds_artefacthasbonus AS ahb ON (ai.id_artefact = ahb.id_artefact)
                        INNER JOIN ds_attribute AS at ON (ahb.id_attribute =at.id_attribute)
                        INNER JOIN ds_attributetype AS att ON (att.id_attributetype = at.id_attributetype)
                        INNER JOIN ds_attributename AS atn ON (atn.id_attributename = at.id_attributename)
                    WHERE
                        aacc.id_combcomplex = ".$comb->getComplexID()."
                    UNION ALL
                    (
                        SELECT
                            att.typename,
                            atn.attributename,
                            ahb.value
                        FROM
                            ds_userhasalliance uha
                            INNER JOIN ds_artefactattachedalliance AS aaa ON (aaa.id_alliance = uha.id_alliance)
                            INNER JOIN ds_artefactinstance AS ai ON (aaa.id_artefact = ai.id_artefactinstance)
                            INNER JOIN ds_artefacthasbonus AS ahb ON (ai.id_artefact = ahb.id_artefact)
                            INNER JOIN ds_attribute AS at ON (ahb.id_attribute =at.id_attribute)
                            INNER JOIN ds_attributetype AS att ON (att.id_attributetype = at.id_attributetype)
                            INNER JOIN ds_attributename AS atn ON (atn.id_attributename = at.id_attributename)
                        WHERE
                            uha.id_userinstance = ".$comb->getUserInstanceID()."
                    )
                ) AS out
            GROUP BY out.typename, out.attributename";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        return $res;
    }

    static public function GetArtefactsAmountOnCombStore(DE_Comb $comb, $artefactID)
    {
        assert(is_int($artefactID) && $artefactID > 0);

        $common = self::getCommon();

        $sql = "SELECT count(*) AS colvo
                FROM
                    ds_combhasartefact AS cha
                    INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
                WHERE
                    cha.id_comb = ".$comb->getID()."
                    AND
                    ai.id_artefact = ".$artefactID."
                GROUP BY ai.id_artefact";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if(count($res) == 0)
        {
            return (int)0;
        }else{
            return $res[0]['colvo'];
        }
    }

    static public function GetArtefactsFromCombStore(DE_Comb $comb, $artefactID, $artefactBirthTime = '', $artefactDeathTime= '')
    {
        assert(is_int($artefactID) && $artefactID > 0);

        $common = self::getCommon();

        $sql = "SELECT *
                FROM
                    ds_combhasartefact AS cha
                    INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
                WHERE
                    cha.id_comb = ".$comb->getID()."
                    AND
                    ai.id_artefact = ".$artefactID."";

        // Если у нас не бессмернтный артефакт
        if ($artefactBirthTime != '') {
            $sql .= "
                    AND 
                     to_char(ai.birth_time, 'YYYY-MM-DD') = '".$artefactBirthTime."'
                    AND 
                     to_char(ai.death_time, 'YYYY-MM-DD') = '".$artefactDeathTime."'";
        } else {
            $sql .= "
                    AND immortal = 't' ";
        }

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function AddArtefactToCombStore(DE_Comb $comb, $artefactInstanceID)
    {
        assert(is_int($artefactInstanceID) && $artefactInstanceID > 0);

        $common = self::getCommon();

        $sql = "SELECT *
                FROM ds_combhasartefact
                WHERE
                    id_comb = ".$comb->getID()."
                    AND
                    id_artefact = $artefactInstanceID";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if(count($res) > 0)
        {
            return;
        }

        $sql = "INSERT INTO ds_combhasartefact
                (id_comb, id_artefact)
                VALUES
                (".$comb->getID().", ".$artefactInstanceID.")";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
    }

    /*static public function SetScoreForArtefact($score, $userInstanceId)
    {
    assert($score > 0);
    assert(is_int($userInstanceId) && $userInstanceId > 0);

    $common = self::getCommon();

    $sql = "SELECT *
    FROM ds_userartefactscore
    WHERE
    id_userinstance = $userInstanceId";
    $res = $common->getResultArray ( "", $sql, "", "", "" );
    assert(empty($common->errormsg));

    if(count($res) > 0)
    {
    $sql = "
    UPDATE
    ds_userartefactscore
    SET
    score = ".$score."
    WHERE
    id_userinstance = ".$userInstanceId;
    } else {
    $sql = "
    INSERT INTO ds_userartefactscore
    (score, id_userinstance)
    VALUES
    (".$score.", ".$userInstanceId.")";
    }

    $common->executeQuery($sql);
    assert(empty($common->errormsg));

    }*/

    static public function DeleteArtefactFromCombStore(DE_Comb $comb, $artefactInstanceID)
    {
        assert(is_int($artefactInstanceID) && $artefactInstanceID > 0);

        $common = self::getCommon();

        $sql = "DELETE FROM ds_combhasartefact WHERE id_comb = ".$comb->getID()." AND id_artefact = ".$artefactInstanceID;
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
    }


    static public function GetArtefactsOnCombStore(DE_Comb $comb, $group) {
        $common = self::getCommon();

        $groupsql = $group ? " GROUP BY a.id_artefact, ai.immortal, birth_time, death_time, a.id_artefactprobability, a.artefactpower, a.id_artefactscope, a.id_artefacttype " : "";

        $count = $group ? "count(*)" : "1";

        $sql = "SELECT
                    a.id_artefact,
                    ai.immortal,
                    to_char(ai.death_time, 'DD.MM.YYYY') AS death_time,
                    to_char(ai.birth_time, 'DD.MM.YYYY') AS birth_time,
                    (
                        SELECT
                            ap.prefix||':'||ars.scopename||':'||at.typename as libname
                        FROM
                            ds_artefactprobability ap,
                            ds_artefactscope ars,
                            ds_artefacttype at
                        WHERE
                            a.id_artefactprobability = ap.id_artefactprobability
                            AND
                            a.id_artefactscope = ars.id_artefactscope
                            AND
                            a.id_artefacttype = at.id_artefacttype
                    ) as name,
                    ".$count." as amount
                FROM
                    ds_combhasartefact AS cha
                    INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
                    INNER JOIN ds_artefact AS a ON (a.id_artefact = ai.id_artefact)
                WHERE
                    cha.id_comb = ".$comb->getID().$groupsql."
                ORDER BY a.id_artefactprobability DESC,a.id_artefactscope DESC,a.id_artefacttype DESC";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        return $res;
    }


    static public function GetArtefactsOnCombStoreWithGrouping(DE_Comb $comb)
    {
        return self::GetArtefactsOnCombStore($comb, true);
    }

    static public function GetArtefactsOnCombStoreWithoutGrouping(DE_Comb $comb)
    {
        return self::GetArtefactsOnCombStore($comb, false);
    }



    static public function AttachArtefactInstanceToEvent(DE_EventQueue $event, $artefactInstanceID)
    {
        assert(is_int($artefactInstanceID) && $artefactInstanceID > 0);

        $common = self::getCommon();

        $sql = "
            INSERT INTO ds_transfer_artefactinstance
                (id_eventqueue, id_artefactinstance)
            VALUES
                (".$event->getID().", ".$artefactInstanceID.")
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    static public function GetAllArtefactsFromEvent($eventQueueID)
    {
        assert(is_int($eventQueueID) && $eventQueueID > 0);

        $common = self::getCommon();

        $sql = "SELECT * FROM ds_transfer_artefactinstance
                WHERE  id_eventqueue = $eventQueueID";
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }


    static public function GetTemprorarySignature($artefactInstanceId) {
        $signature = "";
        $common = self::getCommon();
        $sql = "SELECT  ai.immortal,
                        a.artefactname,
                        to_char(ai.death_time, 'DD.MM.YYYY') AS death_time,
                        to_char(ai.birth_time, 'DD.MM.YYYY') AS birth_time
                FROM ds_artefact AS a 
                LEFT JOIN ds_artefactinstance AS ai ON ai.id_artefact=a.id_artefact
                WHERE ai.id_artefactinstance=".$artefactInstanceId;
        $result = $common->getResultArray('', $sql);
        if(!empty($result)) {
            $signature = $result[0]['artefactname'];
            if($result[0]['immortal'] == 'f') $signature .= "_".$result[0]['birth_time']."|".$result[0]['death_time'];
        }
        return $signature;
    }


    static public function GetDropCompensation($worldId, $artefactInstanceID) {
        $artefact = DE_Artefact::CreateFromArtefactInstanceID((int)$artefactInstanceID);
        $compensation_hash = array();
        $common = self::getCommon();
        $sql = "SELECT an.attributename FROM ds_artefacthasbonus AS ab
                LEFT JOIN ds_attribute AS a ON a.id_attribute = ab.id_attribute
                LEFT JOIN ds_attributename AS an ON an.id_attributename = a.id_attributename
                WHERE ab.id_artefact = ".$artefact->getArtefactID();
        $res = $common->getResultArray('', $sql);
        $type = $res[0]['attributename'];
        if(in_array($type, array('PRODIUM', 'KADERIUM', 'FOOD', 'STASIS_ENERGY', 'NANO_CRYSTAL', 'ATTACK', 'DEFENCE', 'HITPOINTS', 'DAMAGE'))) {
            //что будем компенсировать
            if(in_array($type, array('ATTACK', 'DEFENCE', 'HITPOINTS', 'DAMAGE'))) {
                //боевые артефакты будут компенсировать продиум
                $type = 'PRODIUM';
            }

            $resource_mult = array('PRODIUM' => 1, 'KADERIUM' => 5, 'FOOD' => 10, 'STASIS_ENERGY' => 10, 'NANO_CRYSTAL' => 2.5);

            $sql = "SELECT rangefrom, rangeto FROM ds_artefactprobability WHERE id_artefactprobability = ".$artefact->getProbabilityID();
            $range = $common->getResultArray("", $sql);
            $probability_percent = $range[0]['rangeto'] - $range[0]['rangefrom'];

            $sql = "SELECT s.baseprobability FROM ds_artefact AS a
                    LEFT JOIN ds_artefactinstance AS ai ON ai.id_artefact=a.id_artefact
                    LEFT JOIN ds_artefactscope AS s ON s.id_artefactscope = a.id_artefactscope
                    WHERE ai.id_artefactinstance =".(int)$artefactInstanceID;
            $res = $common->getResultArray('', $sql);
            $base_probability = $res[0]['baseprobability'];

            if($probability_percent > 0 && $base_probability > 0) {
                //вероятность нахождения ненулевая, значит вычисляем компенсацию
                //находим модифаер множитель для компенсаций
                $sql = "SELECT value FROM ds_gamemodifier WHERE modifiername='ARTEFACT_DROP_COMPENSATION' AND id_world=".(int)$worldId;
                $res = $common->getResultArray('', $sql);
                if(!empty($res)) {
                    $modifier = $res[0]['value'];
                    if($modifier > 0) {
                        $compensation =  round($resource_mult[$type] / ($probability_percent * $base_probability * $modifier));
                        $compensation_hash['value'] = $compensation;
                        $compensation_hash['resource'] = $type;
                    }
                }
            }
        }
        return $compensation_hash;
    }

    static public function GetArtefacrtProbability($artefactID)
    {
        assert(is_int($artefactID) && $artefactID > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    ab.prefix 
                FROM 
                    ds_artefact a
                INNER JOIN ds_artefactprobability ab ON (a.id_artefactprobability = ab.id_artefactprobability) 
                WHERE
                    a.id_artefact = $artefactID";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        assert(count($res) == 1);

        return $res[0]['prefix'];
    }

    static public function DropAllArtFromComb($combId)
    {
        assert(is_int($combId) && $combId > 0);

        $common = self::getCommon();

        // Выбираем все арты с сот
        $sql = "
        SELECT 
            ai.id_artefactinstance,
            ai.id_artefact,
            ai.immortal,
            ai.death_time
        FROM 
            ds_artefactinstance ai
        LEFT JOIN ds_combhasartefact cha ON (cha.id_artefact = ai.id_artefactinstance)
        WHERE 
            cha.id_comb = $combId";     
        $arts = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        // Проходимся по всем артам
        foreach ($arts as $artKey => $artData)
        {
            $artefact_instance_id = (int)$artData['id_artefactinstance'];
            assert($artefact_instance_id > 0);

            $idArt = (int)$artData['id_artefact'];
            assert($idArt > 0);

            if ($artData['immortal'] == 'f') {
                $art_death_time = $artData['death_time'];
            } else {
                $art_death_time = '';
            }

            //Получаем компенсацию за арт
            $compensation = DS_Artefact::GetDropCompensation($_SESSION['world'], $artefact_instance_id);
            if(!empty($compensation) && $compensation['value'] > 0) {
                $comb = DE_Comb::CreateFromID((int)$combId);
                $comb->getAttributes()->deltaAttribute('CURRENT_AMOUNT', $compensation['resource'], (float)$compensation['value']);

            }

            //Артефакт находится в хранилище, удаляем его оттуда
            $sql = "DELETE FROM ds_combhasartefact WHERE id_artefact = ".$artefact_instance_id;
            $common->executeQuery($sql);
            assert(empty($common->errormsg));

            $sql = "delete from ds_trade_lot_artefactinstances where id_artefactinstance = ".$artefact_instance_id;
            $common->executeQuery($sql);
            assert(empty($common->errormsg));

            //Удаляем артефакт
            $sql = "DELETE FROM ds_artefactinstance WHERE id_artefactinstance = ".$artefact_instance_id;
            $common->executeQuery($sql);
            assert(empty($common->errormsg));

            // Логируем удаление
            SU_LogDeleteCombObjects::LogForCurrentUserAndComb('ARTEFACT', $idArt, 0, $art_death_time);

        }

        // Выбираем все арты со зданий
        $sql = "
        SELECT 
            id_artefactinstance,
            ab.inupgrade,
            c.id_combcomplex,
            ai.id_artefact,
            ai.immortal,
            ai.death_time
        FROM 
            ds_artefactinstance ai 
        LEFT JOIN ds_artefactattachedbuilding aab ON ( ai.id_artefactinstance = aab.id_artefact)
        LEFT JOIN ds_attachedbuilding ab ON (ab.id_attachedbuilding = aab.id_attachedbuilding)
        LEFT JOIN ds_comb c ON (c.id_comb =  ab.id_comb)
        WHERE 
            ab.id_comb = ".$combId;     
        $arts = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        // Проходимся по всем артам
        foreach ($arts as $artKey => $artData)
        {
            // Id инстанса арта
            $artefact_instance_id = (int)$artData['id_artefactinstance'];
            assert($artefact_instance_id > 0);

            // Id артефакта
            $idArt = (int)$artData['id_artefact'];
            assert($idArt > 0);

            // Время жизни арта
            if ($artData['immortal'] == 'f') {
                $art_death_time = $artData['death_time'];
            } else {
                $art_death_time = '';
            }

            // ID комплекса
            $combcomplex_id = (int)$artData['id_combcomplex'];
            assert($combcomplex_id > 0);

            // Находится ли здание в апгрейде
            $buildingInUpgrade = false;
            if($artData['inupgrade'] == 't')
            {
                $buildingInUpgrade = true;
            }

            // Удаляем артефакт из здания
            $sql = "DELETE FROM ds_artefactattachedbuilding WHERE id_artefact = $artefact_instance_id";
            $common->executeQuery($sql);
            assert(empty($common->errormsg));

            // снимаем бонусы
            $sql = "SELECT
                    ars.scopename
                FROM 
                    ds_artefactscope ars,
                    ds_artefact ar,
                    ds_artefactinstance ai
                WHERE 
                    ars.id_artefactscope = ar.id_artefactscope
                    AND 
                    ar.id_artefact = ai.id_artefact
                    AND 
                    ai.id_artefactinstance = $artefact_instance_id";

            $res = $common->getResultArray("", $sql, "", "", "");
            assert(empty($common->errormsg));

            $artefactAttributes = DE_Artefact::CreateFromArtefactInstanceID($artefact_instance_id)->getAttributes();
            $combAttributes = DE_CombAttributes::CreateFromCombID($combId);

            switch ( $res[0]['scopename'] )
            {
                case 'SPHERE':
                    // если здание апгрейдится, то не изменяем атрибуты соты
                    if($buildingInUpgrade)
                    {
                        break;
                    }

                    $attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
                    foreach($attributeTypeNames as $k1=>$attributeTypeName)
                    {
                        $attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
                        foreach($attributeNames as $k2=>$attributeName)
                        {
                            if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
                            {
                                $delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
                            }
                        }
                    }
                    break;

                case 'BUILDING':

                    // если здание апгрейдится, то не изменяем атрибуты соты
                    if($buildingInUpgrade)
                    {
                        break;
                    }

                    $attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
                    foreach($attributeTypeNames as $k1=>$attributeTypeName)
                    {
                        $attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
                        foreach($attributeNames as $k2=>$attributeName)
                        {
                            if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
                            {
                                $delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
                            }
                        }
                    }
                    break;

                case 'COMB':

                    $sql = "DELETE FROM ds_artefactattachedcomb
                        WHERE id_comb = $combId AND id_artefact = $artefact_instance_id";

                    $res = $common->executeQuery ( $sql );
                    assert(empty($common->errormsg));

                    $attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
                    foreach($attributeTypeNames as $k1=>$attributeTypeName)
                    {
                        $attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
                        foreach($attributeNames as $k2=>$attributeName)
                        {
                            if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
                            {
                                $delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
                            }
                        }
                    }
                    break;

                case 'COMBCOMPLEX':

                    $sql = "DELETE FROM ds_artefactattachedcomplex
                        WHERE id_combcomplex = $combcomplex_id AND  id_artefact = $artefact_instance_id";

                    $res = $common->executeQuery ($sql);
                    assert(empty($common->errormsg));

                    break;

                case 'ALLIANCE':

                    $sql = "DELETE FROM ds_artefactattachedalliance
                        WHERE id_artefact = $artefact_instance_id";

                    $res = $common->executeQuery ( $sql );
                    assert(empty($common->errormsg));

                    break;

                case 'WAR':

                    $sql = "DELETE FROM ds_artefactattachedcomb
                        WHERE id_comb = $combId AND id_artefact = $artefact_instance_id";
                    $res = $common->executeQuery ( $sql );
                    assert(empty($common->errormsg));

                    break;
            }


            // Логируем удаление
            SU_LogDeleteCombObjects::LogForCurrentUserAndComb('ARTEFACT', $idArt, 0, $art_death_time);

            //Получаем компенсацию за арт
            $compensation = DS_Artefact::GetDropCompensation($_SESSION['world'], $artefact_instance_id);
            if(!empty($compensation) && $compensation['value'] > 0) {
                $comb = DE_Comb::CreateFromID((int)$combId);
                $comb->getAttributes()->deltaAttribute('CURRENT_AMOUNT', $compensation['resource'], (float)$compensation['value']);

            }

            $sql = "delete from ds_trade_lot_artefactinstances where id_artefactinstance = ".$artefact_instance_id;
            $common->executeQuery($sql);
            assert(empty($common->errormsg));

            // Удаляем артефакт
            $sql = "DELETE FROM ds_artefactinstance WHERE id_artefactinstance = ".$artefact_instance_id;
            $common->executeQuery($sql);
            assert(empty($common->errormsg));
        }
    }

    // Функция для контакта
    static public function getArtInfo($id, $world )
    {
        $common = self::getCommon();

        $sql = "SELECT ar.id_artefact, artefactname, ap.price6gc, ap.price6sc, ap.price12gc, ap.price12sc
FROM    ds_artefact ar LEFT join ds_artefactprice ap ON
ap.id_artefact=ar.id_artefact and  ap.id_world={$world} WHERE ap.id_artefact={$id}";


        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        //  echo $sql."<br>";
        //  echo $common->errormsg;
        assert(empty($common->errormsg));

        if(sizeof($res) > 0)
        {
            return $res[0];
        }else{
            return false;
        }

    }

    // Получить артефакт по ID
    static public function GetArtefactName($id, $lang_id)
    {
        $common = self::getCommon();

        $sql = "SELECT ar.id_artefact, l.text FROM  ds_artefact ar 
inner join ds_localizedtextlanguage l ON
l.id_localizedtext = ar.id_localizedname
where  (l.id_language={$lang_id} AND ar.id_artefact={$id})";

        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        //die($sql.', res='.var_export($res, true).', err='.$common->errmsg);
        if ($res)
          return $res[0]['text'];
        else 
          return '';  
    }
    
    // Функция для контакта
    static public function GetArtefactsForShop($world=0)
    {
        $common = self::getCommon();
        
    $id_lang = !empty($_SESSION['lang'])?$_SESSION['lang']:3;
        $sql = "SELECT ar.id_artefact, artefactname, artefactpower, l.text as title,  t.typename,  s.scopename,
b.value, atrname.attributename, an.text as attrloc, l3.text as scopetxt,
ap.price6gc, ap.price6sc, ap.price12gc, ap.price12sc
FROM    ds_artefact ar 
inner join ds_localizedtextlanguage l ON
l.id_localizedtext = ar.id_localizedname
inner join ds_artefacttype t
ON ar.id_artefacttype = t.id_artefacttype
inner join ds_artefactscope s
ON ar.id_artefactscope = s.id_artefactscope
inner join ds_localizedtextlanguage l3 ON
l3.id_localizedtext = s.id_localizedscope
inner join ds_artefacthasbonus b ON
b.id_artefact = ar.id_artefact 
inner join ds_attribute atr ON
 atr.id_attribute = b.id_attribute
inner join ds_attributename atrname ON
atrname.id_attributename = atr.id_attributename
inner join ds_localizedtextlanguage an ON
atrname.id_localizedname = an.id_localizedtext
LEFT join ds_artefactprice ap ON
ap.id_artefact=ar.id_artefact and  ap.id_world={$world}

where  (l.id_language={$id_lang} and an.id_language={$id_lang} and l3.id_language={$id_lang}) and
(

(((s.scopename like '%SPHERE%' ) or (ar.artefactname like '%REDS_LOVE%'))
or 
(ar.artefactname like '%IGROMIR%'))

and (
(ar.artefactname NOT LIKE '%GOLD_COMB%') 
AND 
(ar.artefactname not in ('GOLD_SPHERE_MOUSE','GOLD_SPHERE_MOBILEPHONE', 'MAJOR_COMBCOMPLEX_IGROMIR_TYPE', 
'IGROMIR_KADERIUM',  'IGROMIR_FOOD', 'IGROMIR_STATIC', 'IGROMIR_PRODIUM' ))
)
)";


        //$res = $common->getResultArray(self::FormMark(__METHOD__, $artefactID), $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        //  echo $sql."<br>";
        //  echo $common->errormsg;
        assert(empty($common->errormsg));

        if(sizeof($res) > 0)
        {
            return $res;
        }else{
            return false;
        }
    }

    static public function setPrice($info, $world)
    {
        $common = self::getCommon();
        $sql = "SELECT * from ds_artefactprice where  id_artefact={$info['id_artefact']} and id_world=$world";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if ($res)
        {
            $sql = "UPDATE ds_artefactprice SET price6gc={$info['price6gc']}, price6sc={$info['price6sc']},
           price12gc={$info['price12gc']}, price12sc={$info['price12sc']}  where id_artefact={$info['id_artefact']} and id_world=$world";
        }
        else
        {
            $sql = "INSERT INTO ds_artefactprice(id_artefact, id_world, price6gc, price6sc, price12gc, price12sc)
           VALUES({$info['id_artefact']}, $world, {$info['price6gc']}, {$info['price6sc']}, {$info['price12gc']},  {$info['price12sc']})";
        }
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    static public function AddArtefactToComb($id_artefact, $lifetme, $combID, $quantity)
    {
        $common = self::getCommon();
        for($i=0; $i < $quantity;$i++)
        {
            $sql = "SELECT
                        add_artefact_to_comb({$combID}, {$id_artefact}, {$lifetme})";
            $common->executeQuery($sql);
            assert(empty($common->errormsg));
        }
    }

}//END OF CLASS

?>