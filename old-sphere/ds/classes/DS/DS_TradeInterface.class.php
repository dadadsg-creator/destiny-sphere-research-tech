<?php $CVS_Version = '$Revision: 1.14 $ ; $Date: 2009/03/16 14:26:12 $';

class DS_TradeInterface extends DS_Base
{
    static public function GetTradeCoursesForResources($resourceID, DE_World $world)
    {
        assert(is_int($resourceID) && $resourceID > 0);

        $common = self::getCommon();

        if(DE_WorldSettings::CreateForCurentWorld()->get('RECOUNT_COURSES_IN_NANO'))
        {
            $sql = "
                SELECT
                array_to_string
                (
                    array
                    (
                        SELECT

                            CASE
                               WHEN an.attributename IS NULL THEN m.short_name
                                WHEN m.money_name IS NULL THEN substr(an.attributename, 0, 3)
                              END
                              || ':' ||
                           CASE
                               WHEN tc.id_tradecategory = 1 THEN
                               (SELECT
                                    trcd.value
                                FROM
                                    ds_trade_resorce_course_data AS trcd
                                WHERE
                                trcd.id_resource = ".$resourceID."
                                    AND
                                    trcd.is_current = true
                                    AND
                                    trcd.id_world = ".$world->getID()."
                                    AND
                                    trcd.id_tradecourse = 2)
                                /
                                (SELECT
                                    trcd.value
                                FROM
                                    ds_trade_resorce_course_data AS trcd
                                WHERE
                                    trcd.id_resource = tc.id_article
                                    AND
                                    trcd.is_current = true
                                    AND
                                    trcd.id_world = ".$world->getID()."
                                    AND
                                    trcd.id_tradecourse = 2)
                               WHEN tc.id_tradecategory = 4 THEN trcd.value
                              END
                        FROM
                            ds_trade_resorce_course_data AS trcd
                            INNER JOIN ds_trade_course AS tc ON (tc.id_tradecourse = trcd.id_tradecourse)
                            LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
                              LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
                        WHERE
                            trcd.id_world = ".$world->getID()."
                            AND
                            trcd.is_current = true
                            AND
                            trcd.id_resource = ".$resourceID."
                        ORDER BY trcd.id_tradecourse
                    ), ';'
                ) AS courses
                ";
        } else {
            $sql = "
                SELECT
                array_to_string
                (
                    array
                    (
                        SELECT
                            CASE
                              WHEN an.attributename IS NULL THEN m.short_name
                              WHEN m.money_name IS NULL THEN substr(an.attributename, 0, 3)
                            END
                            || ':' || trcd.value
                        FROM
                            ds_trade_resorce_course_data AS trcd
                            INNER JOIN ds_trade_course AS tc ON (tc.id_tradecourse = trcd.id_tradecourse)
                            LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
                            LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
                        WHERE
                            trcd.id_world = ".$world->getID()."
                            AND
                            trcd.is_current = true
                            AND
                            trcd.id_resource = ".$resourceID."
                        ORDER BY trcd.id_tradecourse
                    ), ';'
                ) AS courses
                ";
        }

        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0]['courses'];
    }

    static public function GetTradeCourseForUnits($unitID, DE_World $world)
    {
        assert(is_int($unitID) && $unitID > 0);

        $common = self::getCommon();

        if(DE_WorldSettings::CreateForCurentWorld()->get('RECOUNT_COURSES_IN_NANO'))
        {
            $sql = "
                SELECT

                            CASE
                               WHEN an.attributename IS NULL THEN m.short_name
                                WHEN m.money_name IS NULL THEN substr(an.attributename, 0, 3)
                              END as name,
                           CASE
                               WHEN tc.id_tradecategory = 1 THEN
                               (SELECT
                                    tucd.value
                                FROM
                                    ds_trade_units_cours_data AS tucd
                                WHERE
                                    tucd.id_unit = ".$unitID."
                                    AND
                                    tucd.is_current = true
                                    AND
                                    tucd.id_world = ".$world->getID()."
                                    AND
                                    tucd.id_tradecourse = 2)
                                /
                                (SELECT
                                    trcd.value
                                FROM
                                    ds_trade_resorce_course_data AS trcd
                                WHERE
                                    trcd.id_resource = tc.id_article
                                    AND
                                    trcd.is_current = true
                                    AND
                                    trcd.id_world = ".$world->getID()."
                                    AND
                                    trcd.id_tradecourse = 2)
                               WHEN tc.id_tradecategory = 4 THEN trcd.value
                              END AS value
                        FROM
                            ds_trade_units_cours_data AS trcd
                            INNER JOIN ds_trade_course AS tc ON (tc.id_tradecourse = trcd.id_tradecourse)
                            LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
                              LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
                        WHERE
                            trcd.id_world = ".$world->getID()."
                            AND
                            trcd.is_current = true
                            AND
                            trcd.id_unit = ".$unitID."
                        ORDER BY trcd.id_tradecourse
                ";
        } else {
            $sql = "SELECT
                    CASE
                        WHEN an.attributename IS NULL THEN m.short_name
                        WHEN m.money_name IS NULL THEN substr(an.attributename, 0, 3)
                    END as name,
                    trcd.value
                FROM
                    ds_trade_units_cours_data AS trcd
                    INNER JOIN ds_trade_course AS tc ON (tc.id_tradecourse = trcd.id_tradecourse)
                    LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
                    LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
                WHERE
                    trcd.id_world = ".$world->getID()."
                    AND
                    trcd.is_current = true
                    AND
                    trcd.id_unit = ".$unitID."
                ORDER BY trcd.id_tradecourse
            ";
        }

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        $courses = array();
        foreach ($res as $k => $course)
        {
            $val = $course['value'];
            if(strpos($val, 'e') !== false)
            {
                $val = number_format($val, 10, '.', '');
            }
            $courses[] = $course['name'].":".$val;
        }

        $res = implode(';', $courses);

        return $res;
    }

    static public function GetTradeCourseForArtefacts($artefactID, DE_World $world)
    {
        assert(is_int($artefactID) && $artefactID > 0);

        $common = self::getCommon();

        if(DE_WorldSettings::CreateForCurentWorld()->get('RECOUNT_COURSES_IN_NANO'))
        {
            $sql = "SELECT
                    CASE
                        WHEN an.attributename IS NULL THEN m.short_name
                        WHEN m.money_name IS NULL THEN substr(an.attributename, 0, 3)
                    END as name,
                    CASE
                               WHEN tc.id_tradecategory = 1 THEN
                               (SELECT
                                       tacd.value
                                FROM
                                    ds_trade_artefact_course_data AS tacd
                                WHERE
                                    tacd.id_artefact = ".$artefactID."
                                    AND
                                    tacd.is_current = true
                                    AND
                                    tacd.id_world = ".$world->getID()."
                                    AND
                                    tacd.id_tradecourse = 2)
                                /
                                (SELECT
                                    trcd.value
                                FROM
                                    ds_trade_resorce_course_data AS trcd
                                WHERE
                                    trcd.id_resource = tc.id_article
                                    AND
                                    trcd.is_current = true
                                    AND
                                    trcd.id_world = ".$world->getID()."
                                    AND
                                    trcd.id_tradecourse = 2)
                               WHEN tc.id_tradecategory = 4 THEN trcd.value
                              END AS value
                FROM
                    ds_trade_artefact_course_data AS trcd
                    INNER JOIN ds_trade_course AS tc ON (tc.id_tradecourse = trcd.id_tradecourse)
                    LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
                    LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
                WHERE
                    trcd.id_world = ".$world->getID()."
                    AND
                    trcd.is_current = true
                    AND
                    trcd.id_artefact = ".$artefactID."
                ORDER BY trcd.id_tradecourse
                ";
        } else {
            $sql = "SELECT
                    CASE
                        WHEN an.attributename IS NULL THEN m.short_name
                        WHEN m.money_name IS NULL THEN substr(an.attributename, 0, 3)
                    END as name,
                    trcd.value
                FROM
                    ds_trade_artefact_course_data AS trcd
                    INNER JOIN ds_trade_course AS tc ON (tc.id_tradecourse = trcd.id_tradecourse)
                    LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
                    LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
                WHERE
                    trcd.id_world = ".$world->getID()."
                    AND
                    trcd.is_current = true
                    AND
                    trcd.id_artefact = ".$artefactID."
                ORDER BY trcd.id_tradecourse
                ";
        }
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        $courses = array();
        foreach ($res as $k => $course)
        {
            $val = $course['value'];
            if(strpos($val, 'e') !== false)
            {
                $val = number_format($val, 10, '.', '');
            }
            $courses[] = $course['name'].":".$val;
        }

        return implode(';', $courses);
    }


    static function GetCurrentCourses($worldID, $isActiveTradeBy = true)
    {
        assert(is_int($worldID) && $worldID > 0);

        $common = self::getCommon();

        $sql = "SELECT
                      tmp.*
                FROM
                    (
                        SELECT
                            tc.id_tradecategory,
                            tc.id_article,
                            tc.id_tradecourse,
                            CASE
                                WHEN an.attributename IS NULL THEN m.money_name
                                WHEN m.money_name IS NULL THEN an.attributename
                            END AS name,
                            CASE
                                WHEN an.attributename IS NULL THEN 'MONEY'
                                WHEN m.money_name IS NULL THEN 'RESOURCE'
                            END AS categoryname
                        FROM
                            ds_trade_course AS tc
                            LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
                            LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
                        ORDER BY tc.id_tradecategory, tc.id_article
                    ) AS tmp
                    INNER JOIN ds_trade_category AS tc ON (tc.categoryname = tmp.categoryname)
                WHERE
                    tc.can_trade_by	 = true
                ORDER BY tmp.id_tradecategory, tmp.id_article";
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static function GetMatrixLeftTitle()
    {
        $common = self::getCommon();

        $sql = "SELECT DISTINCT
                    tt.id_trade_on_category,
                    tt.id_trade_on_type
                FROM ds_trade_type AS tt
                ORDER BY tt.id_trade_on_category, tt.id_trade_on_type";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetMatrixRow($tradeCategoryID, $tradeTypeID, $worldID)
    {
        assert((int)$tradeCategoryID && $tradeCategoryID > 0);
        assert((int)$tradeTypeID && $tradeTypeID > 0);
        assert(is_int($worldID) && $worldID > 0);

        $common = self::getCommon();

        $sql = "
            SELECT *
            FROM
                ds_trade_type AS tt
                INNER JOIN ds_trade_type_data AS ttd ON (ttd.id_tradetype = tt.id_tradetype)
                INNER JOIN ds_trade_category AS tbc ON (tbc.id_tradecategory = tt.id_trade_by_category)
            WHERE
                ttd.id_world = $worldID
                AND
                tt.id_trade_on_category = $tradeCategoryID
                AND
                tt.id_trade_on_type = $tradeTypeID
                AND
                ttd.is_current = true
                AND
                tbc.can_trade_by = true
            ORDER BY tt.id_trade_by_category, tt.id_trade_by_type";

        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetArtefactsOnCombStoreWithGrouping(DE_Comb $comb, DE_World $world)
    {
        $common = self::getCommon();

        $sql = "SELECT
                        a.id_artefact,
                        a.id_artefactprobability,
                        a.id_artefactscope,
                        a.id_artefacttype,
                        ai.immortal,
                        to_char(ai.death_time, 'DD.MM.YYYY') AS death_time,
                        to_char(ai.birth_time, 'DD.MM.YYYY') AS birth_time,
                        count(*) as amount

                    FROM
                        ds_combhasartefact AS cha
                        INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
                        INNER JOIN ds_artefact AS a ON (a.id_artefact = ai.id_artefact)
                    WHERE
                        cha.id_comb = ".$comb->getID()."
                    GROUP BY a.id_artefact, ai.immortal, death_time, birth_time, a.id_artefactprobability, a.artefactpower, a.id_artefactscope,a.id_artefacttype
                    ORDER BY a.id_artefactprobability DESC, a.id_artefactscope DESC,a.id_artefacttype DESC";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetCombAllowedTradeScopes(DE_Comb $comb)
    {
        $common = self::getCommon();

        $sql = "SELECT
                    ts.id_tradescope,
                    ts.tradescopename
                FROM
                    ds_trade_scope ts,
                    ds_comballowedtradescope cats
                WHERE
                    ts.id_tradescope = cats.id_tradescope
                    AND
                    cats.id_comb = ".$comb->getID();
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetAuctionFilterLevel($parentID = null)
    {
        assert(is_null($parentID) || (is_int($parentID) && $parentID > 0));

        $common = self::getCommon();

        if(is_null($parentID))
        {
            $parentWhere = " IS NULL ";
        }else{
            $parentWhere = " = $parentID ";
        }

        $sql = "
            SELECT
                aus.id_sort,
                aus.sortname
            FROM
                ds_auctionsort aus
            WHERE
                aus.id_parent $parentWhere
                AND
                aus.isactive = true
            ORDER BY aus.id_sort";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $parentID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetTradeLots(DE_TradeAuctionPanelMainSQL $sqlVersion, $versionName)
    {

        assert(!empty($versionName));

        $common = self::getCommon();

        $sql = $sqlVersion->getVersionSql($versionName);
			
		    if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
		    {
		    	SU_DebugTools::LogAll('sql='.$sql,'trade.log');
		    }
        $res = $common->getResultArray("", $sql, "", "", "");

        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetTradeMenuFilters()
    {
        $common = self::getCommon();

        $lang = (int)$_SESSION['lang'];
        assert($lang > 0);

        $sql = "
            SELECT
                tmf.id_filter,
                tmf.default_value,
                tmf.values,
                tmf.name,
                COALESCE(ltl.text, tmf.name) as text
            FROM
                ds_trade_menu_filters AS tmf
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = tmf.id_localized_name AND ltl.id_language = $lang)
            ORDER BY sort_order ASC
            ";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $lang), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetTradeMenuSorts()
    {
        $common = self::getCommon();

        $lang = (int)$_SESSION['lang'];
        assert($lang > 0);

        $sql = "
            SELECT
                tms.id_filter,
                tms.name,
                COALESCE(ltl.text, tms.name) as text,
                tms.sort_direction,
                tms.sort_order
            FROM
                ds_trade_menu_sorting AS tms
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = tms.id_localizedname AND ltl.id_language = $lang)
            ORDER BY sort_order ASC
            ";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $lang), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetTradeMenuSortNameByID($sortID)
    {
        $common = self::getCommon();
        assert($sortID > 0);

        $lang = (int)$_SESSION['lang'];
        assert($lang > 0);

        $sql = "
            SELECT
                tms.name
            FROM
                ds_trade_menu_sorting AS tms
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = tms.id_localizedname AND ltl.id_language = $lang)
            WHERE
                tms.id_filter = $sortID
            ";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $sortID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0]['name'];
    }

    static public function GetArtefactsIDByTradeFilter($scopeID, array $size = array(), array $bonus = array())
    {
        assert(is_int($scopeID) && $scopeID > 0);

        $common = self::getCommon();

        $where = 'a.id_artefactscope = '.$scopeID;

        if(count($size) > 0)
        {
            $sizes = implode(',', $size);
            assert(preg_match('/^[0-9,]+$/', $sizes));
            $where .= ' AND a.id_artefactprobability IN ('.$sizes.')';
        }

        if(count($bonus) > 0)
        {
            $bonuses = implode(',', $bonus);
            assert(preg_match('/^[0-9,]+$/', $bonuses));
            $where .= ' AND atr.id_attributename IN ('.$bonuses.')';
        }

        $sql = "
            SELECT
                DISTINCT a.id_artefact
            FROM
                ds_artefact AS a
                LEFT JOIN ds_artefacthasbonus AS ahb USING (id_artefact)
                LEFT JOIN ds_attribute AS atr ON (atr.id_attribute = ahb.id_attribute)
            WHERE
        $where
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        $artefacts = array();

        foreach ($res as $k => $a)
        {
            $artefacts[] = (int)$a['id_artefact'];
        }

        return $artefacts;
    }
}

?>