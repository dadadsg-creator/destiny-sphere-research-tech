<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Science  extends DS_Base
{

    static public function GetScienceBonus(DE_Comb $comb)
    {

        $common = self::getCommon();

        $sql = "SELECT
                    atn.attributename,
                    s.level,
                    r.exchangerate
                FROM
                    ds_science s,
                    ds_sciencebranch sbr,
                    ds_sciencebranchattributename sban,
                    ds_resource r,
                    ds_attributename atn,
                    (
                        SELECT
                            max(s.id_science) as id_science
                        FROM
                            ds_userhasscience uhs,
                            ds_science s,
                            ds_sciencebranch sbr
                        WHERE
                                uhs.id_science = s.id_science
                            AND s.id_branch = sbr.id_branch
                            AND uhs.id_userinstance = ".$comb->getUserInstanceID()."
                            AND uhs.inresearch = false
                        GROUP BY sbr.id_branch
                    ) AS t
                WHERE
                        s.id_science = t.id_science
                    AND s.id_branch = sbr.id_branch
                    AND sbr.id_branch = sban.id_sciencebranch
                    AND sban.id_attributename = r.id_attributename
                    AND atn.id_attributename = r.id_attributename";
        $res = $common->getResultArray ("", $sql);
        assert(empty($common->errormsg));

        return $res;
    }//END OF METHOD


    static public function UpdateUSerHasScience($UIID, $scienceID, $inResearch = null, $endDate = null)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($scienceID) && $scienceID > 0);

        assert(($inResearch instanceof GB_Boolean) || is_null($inResearch));
        assert(($endDate instanceof GB_Date) || is_null($endDate));
        assert(!is_null($endDate) || is_null($inResearch));

        $sql = "UPDATE ds_userhasscience
        SET $setPart
        WHERE
        id_science = ".$scienceID."
                    AND
                    if_userinstance = ".$scienceID;     
        $res = $common->getResultArray ("", $sql);
        assert(empty($common->errormsg));
    }

    static public function GetSpyScienceBranchLevel($UIID)
    {
        assert(is_int($UIID));

        $common = self::getCommon();

        $sql = "
        SELECT
            max(s.level) as level
        FROM
            ds_userhasscience AS uhs
        INNER JOIN ds_science AS s ON (uhs.id_science = s.id_science)
        INNER JOIN ds_sciencebranch AS sb USING (id_branch)
        WHERE
            uhs.id_userinstance = $UIID
            AND
            sb.branchname = 'SPYING'
            AND
            NOT uhs.inresearch
            ";
        $res = $common->getResultArray ("", $sql);
        assert(empty($common->errormsg));

        if(count($res) == 0)
        {
            return 1;
        }

        return (int)$res[0]['level'];
    }

    static public function GetScienceBranchLevel($scienceName, $UIID)
    {
        assert(is_int($UIID));

        $common = self::getCommon();

        $sql = "
        SELECT
            max(s.level) as level
        FROM
            ds_userhasscience AS uhs
        INNER JOIN ds_science AS s ON (uhs.id_science = s.id_science)
        INNER JOIN ds_sciencebranch AS sb USING (id_branch)
        WHERE
            uhs.id_userinstance = ".$UIID."
            AND
            sb.branchname = '".$scienceName."'
            AND
            NOT uhs.inresearch
            ";
        $res = $common->getResultArray ("", $sql);
        assert(empty($common->errormsg));

        if(count($res) == 0)
        {
            return 0;
        }

        return (int)$res[0]['level'];
    }


    static public function GetTradeAbility($UIID, $tradeCategoryId, $tradeArticleId)
    {
        return true;

        assert(is_int($UIID));
        assert(is_int($tradeCategoryId) && $tradeCategoryId > 0);
        assert(is_int($tradeArticleId)  && $tradeArticleId > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    ab.abilityname
                FROM
                    ds_userhasability uhab,
                    ds_ability ab,
                    ds_trade_ability ta
                WHERE
                    uhab.id_ability = ab.id_ability 
                    AND 
                    ta.id_ability = ab.id_ability
                    AND 
                    uhab.id_userinstance = ".$UIID."
                    and 
                    ta.id_trade_category = ".$tradeCategoryId.
                    ($tradeCategoryId == 1 ? " AND ta.id_trade_article = ".$tradeArticleId : "");
                    $res = $common->getResultArray ("", $sql);
                    assert(empty($common->errormsg));

                    return count($res) == 0 ? false : true;
    }

    static public function GetScienceDataById($langId, $scienceId)
    {
        assert(is_int($langId) && $langId > 0);
        assert(is_int($scienceId)  && $scienceId > 0);

        $common = self::getCommon();

        $sql = "SELECT
                        ltl.text as sciencename,
                        s.level
                    FROM
                        DS_LocalizedTextLanguage ltl,
                        DS_Science s
                    WHERE
                            s.ID_LocalizedName = ltl.ID_LocalizedText
                        AND ltl.ID_Language = $langId
                        AND s.ID_Science = $scienceId";
        $res = $common->getResultArray ("sciencename_id_language_id_science_".$langId."_".$scienceId, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        return $res;
    }


    static public function AddScinceToUser($UIID, $scienceId)
    {
        assert(is_int($UIID));
        assert(is_int($scienceId)  && $scienceId > 0);

        $common = self::getCommon();

        // Помечаем науку как изученную
        $sql = "UPDATE
                    ds_userhasscience
                SET 
                    inresearch = false
                WHERE 
                    id_science = $scienceId
                    AND 
                    id_userinstance = $UIID";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));

        // добавляем абилитис соответствующие этой науке
        $sql = "INSERT INTO
                    ds_userhasability
                (id_userinstance, id_ability )
                    SELECT 
                        $UIID, 
                        id_ability
                    FROM 
                        ds_sciencehasability
                    WHERE 
                        id_science = $scienceId";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));

        return true;
    }

    static public function IsScienceAccelerated($eventId)
    {
        assert(is_int($eventId)  && $eventId > 0);

        $common = self::getCommon();

        // Помечаем науку как изученную
        $sql = "SELECT
                    *
                FROM
                    ds_eventacceleration
                WHERE
                    id_eventqueue = ".$eventId;
        $res = $common->getResultArray( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if (count($res) > 0) {
            return true;
        }

        return false;
    }

    static public function CansecSciense($UIID, $scienceId, $eqid)
    {
        assert(is_int($UIID));
        assert(is_int($scienceId)  && $scienceId > 0);
        assert(is_int($eqid)  && $eqid > 0);

        $common = self::getCommon();

        // Прибиваем исследование
        $sql = "DELETE FROM
                    ds_userhassciencebindeventqueue
                WHERE 
                    id_eventqueue = $eqid";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));

        // Убираем науку у игрока
        $sql = "DELETE FROM
                    ds_userhasscience
                WHERE 
                    id_userinstance = $UIID
                    AND 
                    id_science = $scienceId";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetScienceBonusByRace($raceID)
    {
        assert(is_int($raceID) && $raceID > 0);

        $common = self::getCommon();

        // Прибиваем исследование
        $sql = "SELECT
                            rha.value
                        from
                            ds_racehasattribute rha,
                            ds_attribute a,
                            ds_attributename atn,
                            ds_attributetype att
                        where
                                rha.id_race = $raceID
                            and rha.id_attribute = a.id_attribute
                            and a.id_attributename = atn.id_attributename
                            and a.id_attributetype = att.id_attributetype
                            and att.typename = 'BONUS_RELATIVE'
                            and atn.attributename = 'SCIENCE'";
        $res = $common->getResultArray ( "racehasbonus_science_id_race_".$raceID, $sql, "", "", "" );
        assert(empty($common->errormsg));

        if (sizeof ($res) > 0) {
            return (float)$res[0]['value'];
        }
        return 0;
    }

    static public function GetScienceScore($sienceID)
    {
        assert(is_int($sienceID) && $sienceID > 0);

        $common = self::getCommon();

        // Прибиваем исследование
        $sql = "SELECT
                            science_score
                        FROM
                            ds_science
                        WHERE
                            id_science = ".$sienceID;

        $ehres = $common->getResultArray ( "science_score_id_science_".$sienceID, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
        assert ( empty ( $common->errormsg ) );
        
        return $ehres[0]['science_score'];
    }

}//END OF CLASS


?>