<?php

class UnitPriceSearch
{
    static public function getUnitRaces($DBID)
    {
        assert(is_int($DBID) && $DBID>=0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
            SELECT
                id_race as id,
                racename as name
            FROM
                ds_race
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        //$res[] = array('id' => '4', 'name' => 'QUEST');

        return $res;
    }

    static public function getUnitClasses($DBID)
    {
        assert(is_int($DBID) && $DBID>=0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
            SELECT
                id_unitclass as id,
                classname as name
            FROM
                ds_unitclass
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function getLotteryUnitsByRaceAndClass($race, $class, $DBID)
    {
        $common = DS_Common::CreateForCurentDB($DBID);

        $cond = "";
        if ($race!='all') $cond .= " and ucbbr.id_race=$race";
        if ($class!='all') $cond .= " and uhc.id_unitclass=$class";
        $sql = "select nl.id, nl.prob, nl.num, nl.active from ds_npc_lottery nl, ds_unit u, ds_unithasclass uhc, ds_unitcanbebuildbyrace ucbbr
                where nl.id=u.id_unit and uhc.id_unit = u.id_unit and ucbbr.id_unit = u.id_unit and nl.type='u' $cond";
        $res = $common->getResultArray("", $sql);
        $out = array();
        foreach ($res as $row) $out[$row['id']] = $row;
        return $out;
    }


    static public function getUnitsByRaceAndClass($race, $class, $DBID, $worldID, $onlyForNano = false)
    {
        assert(!empty($race));
        assert(!empty($class));

        assert(is_int($DBID) && $DBID >= 0);
        assert(is_int($worldID) && $worldID > 0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $whereTrade= '';
        if ($onlyForNano == true) {
            $whereTrade = '
                    AND
                     (tuc.id_tradecourse = 2
                        OR
                        tuc.id_tradecourse = 6
                        OR
                        tuc.id_tradecourse = 7
                        ) ';            
        }

        $sql = "SELECT  u.id_unit as id,  
                        COALESCE(ltl.text, u.unitname) as name,
                        tuc.value,
                        tc.id_tradecategory,
                        tc.id_article,
                        tca.categoryname AS tradecourse_name,
                        CASE
                        WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
                        WHEN tca.categoryname = 'MONEY' THEN m.money_name
                        ELSE NULL
                        END AS trade_artyicle_name,
                        tuc.add_date,
                        CASE
                     WHEN
                      tc.id_article = 5
                      AND
                      tc.id_tradecategory = 1
                        THEN
                          (
                           SELECT
                            1
                            )
                       WHEN
                      tc.id_article = 2
                       AND
                      tc.id_tradecategory = 1
                        THEN
                          (
                           SELECT
                            2
                            )
                              WHEN
                      tc.id_article = 1
                       AND
                      tc.id_tradecategory = 1
                        THEN
                          (
                           SELECT
                            3
                            )
                
                                  WHEN
                      tc.id_article = 4
                       AND
                      tc.id_tradecategory = 1
                        THEN
                          (
                           SELECT
                            4
                            )
                
                                  WHEN
                      tc.id_article = 3
                       AND
                      tc.id_tradecategory = 1
                
                        THEN
                          (
                           SELECT
                            5
                            )
                 WHEN
                      tc.id_article = 1
                       AND
                      tc.id_tradecategory = 4
                        THEN
                          (
                           SELECT
                            6
                            )
                
                                  WHEN
                      tc.id_article = 2
                       AND
                      tc.id_tradecategory = 4
                        THEN
                          (
                           SELECT
                            7
                            )
                    END AS number
                FROM    ds_unit AS u
                INNER JOIN ds_unithasclass AS uhc ON (uhc.id_unit = u.id_unit)
                LEFT JOIN ds_unitcanbebuildbyrace AS ucbbr ON (ucbbr.id_unit = u.id_unit)
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = u.id_localizedname AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
                INNER JOIN ds_trade_units_cours_data AS tuc ON (tuc.id_unit = u.id_unit)
                INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tuc.id_tradecourse)
                INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
                LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
                LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article)
            WHERE";

        if ($race != 'all')
        {
            $sql .= " ucbbr.id_race = $race AND ";
        }

        $sql .= " tuc.id_world = $worldID
                AND
                tuc.is_current IS TRUE
                $whereTrade ";

        if ($class != 'all')
        {
            assert($class >= 0);
            $sql .= " AND
                uhc.id_unitclass = $class";
        }

        $sql .= " ORDER BY ucbbr.id_race, name, number";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function updateUnitCourse($id_unit, $id_tradecategory, $id_article, $course, $DBID, $worldID)
    {
        assert(is_int($id_unit) && $id_unit > 0);
        assert(is_int($id_tradecategory) && $id_tradecategory > 0);
        assert(is_int($id_article) && $id_article > 0);
        assert(is_int($DBID) && $DBID >= 0);
        assert(is_int($worldID) && $worldID > 0);
        assert(is_float($course) && $course > 0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "SELECT insert_unit_trade_course($worldID, $id_unit, $id_tradecategory, $id_article, $course) AS res";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res[0]['res'];
    }

    static public function updateLotteryUnit($unitID, $num, $prob, $active, $DBID) {
        $common = DS_Common::CreateForCurentDB($DBID);
        $artID = (int) $artID;
        $num = (int) $num;
        $prob = (float) $prob;
        $active = $active?'true':'false';
        $sql = "select * from ds_npc_lottery where type='u' and id=$unitID";
        $res = $common->getResultArray("", $sql);
        if (count($res))
            $sql = "update ds_npc_lottery set prob=$prob, num=$num, active=$active where type='u' and id=$unitID";
        else
            $sql = "insert into ds_npc_lottery (type, id, prob, num, active) values ('u', $unitID, $prob, $num, $active)";
        $common->executeQuery($sql);
    }


    static public function getUnitCourseHistoryByID($unitID, $DBID, $worldID)
    {
        assert(is_int($unitID) && $unitID > 0);
        assert(is_int($DBID) && $DBID >= 0);
        assert(is_int($worldID) && $worldID > 0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
            SELECT
                CASE
                    WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
                    WHEN tca.categoryname = 'MONEY' THEN m.money_name
                    ELSE NULL
                END AS trade_name,
                tu.add_date,
                tu.is_current,
                tu.value
            FROM
                ds_trade_units_cours_data as tu
            INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tu.id_tradecourse)
            INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
            LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
            LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article) 
            WHERE
                id_unit = $unitID
                AND
                id_world = $worldID
            ORDER BY add_date, tu.id_tradecourse
            ";
        /*$sql = "
        SELECT
        COALESCE(ltl.text, u.unitname) as unit_name,
        CASE
        WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
        WHEN tca.categoryname = 'MONEY' THEN m.money_name
        ELSE NULL
        END AS trade_name,
        tu.add_date,
        tu.is_current,
        tu.value
        FROM
        ds_trade_units_cours_data as tu
        LEFT JOIN ds_unit AS u ON (u.id_unit = tu.id_unit)
        LEFT JOIN ds_localizedtextlanguage AS ltl ON (
        ltl.id_localizedtext = u.id_localizedname
        AND
        ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
        INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tu.id_tradecourse)
        INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
        LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
        LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article)
        WHERE
        tu.id_unit = $unitID
        AND
        id_world = $worldID
        ORDER BY add_date, tu.id_tradecourse";*/
        //echo $sql;
        //exit;
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function initUnitTradeCourse($DBID, $worldID)
    {
        assert(is_int($DBID) && $DBID >= 0);
        assert(is_int($worldID) && $worldID > 0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
            INSERT INTO ds_trade_units_cours_data
                (id_world, id_unit, value)
            SELECT
                $worldID AS id_world,
                u.id_unit,
                (
                    SELECT
                        SUM(uha.value * tcd.value) AS stazis
                    FROM
                        ds_unithasattribute AS uha
                        INNER JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
                        INNER JOIN ds_attributename AS atn ON (a.id_attributename = atn.id_attributename)
                        INNER JOIN ds_attributetype AS att ON (a.id_attributetype = att.id_attributetype)
                        INNER JOIN ds_trade_course AS tc ON (tc.id_article = a.id_attributename)
                        INNER JOIN ds_trade_course_data AS tcd ON (tcd.id_tradecourse = tc.id_tradecourse AND tcd.is_current = true)
                    WHERE
                        att.typename = 'PRODUCTION_COSTS'
                        AND
                        atn.attributename != 'CONSTRUCTION_TIME'
                        AND                                        
                        uha.id_unit = u.id_unit
                        AND
                        tcd.id_world = $worldID
                        AND
                        tc.id_tradecategory = 1
                ) AS stazis
            FROM
                ds_unit AS u
                INNER JOIN ds_unithasclass AS uhc ON (uhc.id_unit = u.id_unit)
                INNER JOIN ds_unitclass AS uc ON (uc.id_unitclass = uhc.id_unitclass)
            ";

        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    static public function getTitle($DBID, $worldID)
    {
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>=0);

        //--------------- Формирование верхнего заголовка таблицы -------------------------
        $upTitleCategoryes = TypeSearch::GetTableUpRowsCategorys($DBID);

        foreach ($upTitleCategoryes as $k1=>$v1)
        {
            $categoryName = $v1['categoryname'];
            assert(!empty($categoryName));

            $categoryID = (int)$v1['id_tradecategory'];
            assert($categoryID > 0);

            $tmp = array();
            $typeData = self::GetTypesByCategoryName($DBID, $categoryName);
            foreach($typeData as $k2=>$v2)
            {
                $typeName = $v2['name'];
                assert(!empty($typeName));

                $typeID = (int)$v2['id'];
                assert($typeID > 0);

                $tmp[] = array
                (
                'TYPE_NAME' => $typeName,
                'TYPE_ID' => $typeID
                );
            }

            $title[] = array
            (
            'CATEGORY_NAME' => $categoryName,
            'CATEGORY_ID' => $categoryID,
            'TYPES' => $tmp
            );
        }
        return $title;
    }

    static public function getUnitCourseHistoryTitleByID($unitID, $DBID, $worldID)
    {
        assert(is_int($unitID) && $unitID > 0);
        assert(is_int($DBID) && $DBID >= 0);
        assert(is_int($worldID) && $worldID > 0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
        SELECT
            DISTINCT tu.id_tradecourse,
                CASE
                    WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
                    WHEN tca.categoryname = 'MONEY' THEN m.money_name
                    ELSE NULL
                END AS trade_name
            FROM
                ds_trade_units_cours_data as tu
            INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tu.id_tradecourse)
            INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
            LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
            LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article) 
            WHERE
                id_unit = $unitID
                AND
                id_world = $worldID
            ORDER BY tu.id_tradecourse
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetUnitNameByID($dbID, $id_unit)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($id_unit) && $id_unit >= 0);

        $common = DS_Common::CreateForCurentDB($dbID);

        $sql = "SELECT
            COALESCE(ltl.text, u.unitname) as name
             FROM ds_unit AS u
            LEFT JOIN ds_localizedtextlanguage AS ltl ON (
                            ltl.id_localizedtext = u.id_localizedname
                AND
                ltl.id_language =  ".constant("DEFAULT_LANGUAGE").")
            WHERE
                u.id_unit = $id_unit
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(count($res) == 0)
        {
            return 0;
        }

        return $res[0]['name'];
    }

    static public function GetWorldNameByID($dbID, $worldID)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID >= 0);

        $common = DS_Common::CreateForCurentDB($dbID);

        $sql = "
                SELECT worldname
                FROM ds_world
                WHERE id_world = $worldID
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(count($res) == 0)
        {
            return 0;
        }

        return $res[0]['worldname'];
    }
    static public function GetNameByArticleAndTradecategory($dbID, $worldID, $id_tradecategory, $id_article)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID >= 0);
        assert($id_article>0);
        assert($id_tradecategory>0);

        $common = DS_Common::CreateForCurentDB($dbID);
        switch ($id_tradecategory)
        {
            case '4':
                $sql = "SELECT
                       mon.money_name as name
                     FROM
                       ds_money mon
                     WHERE
                        mon.id_money = $id_article";
                break;
            case '1':
                $sql = "SELECT
                        an.attributename as name
                      FROM
                        ds_attributename an
                     WHERE
                        an.id_attributename = $id_article";
                break;
        }
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(count($res) == 0)
        {
            return 0;
        }

        return $res[0]['name'];
    }
    static public function getRithTitle($DBID,$race, $class)
    {
        assert($race > 0 || $race == 'all');
        assert($class || $class == 'all');
        assert(is_int($DBID) && $DBID >= 0);


        $common = DS_Common::CreateForCurentDB($DBID);

        if  ($race  == 'all' AND $class == 'all')
        {
            $where = "";
        }else{
            if ($race != 'all' AND $class != 'all')
            {
                $where = "
                        WHERE r.id_race = $race
                        AND
                         uc.id_unitclass = $class ";
            }
            if ($race == 'all')
            {
                $where = "WHERE uc.id_unitclass = $class ";
            }
            if ($class == 'all')
            {
                $where = "WHERE r.id_race = $race ";
            }
        }

        /*$sql = "
            SELECT
            COALESCE(ltl.text, u.unitname) as name,
            u.id_unit
             FROM ds_unit AS u
            LEFT JOIN ds_localizedtextlanguage AS ltl ON (
                            ltl.id_localizedtext = u.id_localizedname
                AND
                ltl.id_language = ".constant("DEFAULT_LANGUAGE").")
            LEFT JOIN ds_unitcanbebuildbyrace AS ur ON (ur.id_unit = u.id_unit)
            INNER JOIN ds_unithasclass AS uc ON (uc.id_unit = u.id_unit)
            $where
            ORDER BY ur.id_race, name";*/
        $sql = "SELECT
                    COALESCE(ltl.text, u.unitname) as name, r.id_race, ltlr.text as race,
                    u.id_unit
                    FROM ds_unit AS u
                    LEFT JOIN ds_localizedtextlanguage AS ltl ON (
                            ltl.id_localizedtext = u.id_localizedname
                            AND
                            ltl.id_language = ".constant("DEFAULT_LANGUAGE").")
                    LEFT JOIN ds_unitcanbebuildbyrace AS ucbbr ON (
                            ucbbr.id_unit = u.id_unit)
                    LEFT JOIN ds_race AS r ON (
                            r.id_race = ucbbr.id_race)
                    LEFT JOIN ds_localizedtextlanguage AS ltlr ON (
                            ltlr.id_localizedtext = r.id_localizedname
                            AND
                            ltlr.id_language = ".constant("DEFAULT_LANGUAGE").")
                    LEFT JOIN ds_unithasclass AS uc ON (
                            uc.id_unit = u.id_unit)
                    $where
                    ORDER BY r.id_race, name
                    ";
/*echo "<xmp>";
echo $sql;
echo "</xmp>";
exit;*/
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
    
    static public function GetTypesByCategoryName($DBID, $categoryName)
    {
        assert(is_int($DBID) && $DBID>=0);
        assert(!empty($categoryName));

        $common = DS_Common::CreateForCurentDB($DBID);

        switch($categoryName)
        {
            case 'RESOURCE':
                $sql = "SELECT
                            r.id_attributename AS id,
                            an.attributename AS name,
                             CASE
                         WHEN
                          r.id_attributename = 5
                            THEN
                              (
                               SELECT
                                1
                                )
                           WHEN
                          r.id_attributename = 2
                            THEN
                              (
                               SELECT
                                2
                                )
                                  WHEN
                         r.id_attributename = 1
                            THEN
                              (
                               SELECT
                                3
                                )
                    
                                      WHEN
                          r.id_attributename = 4
                            THEN
                              (
                               SELECT
                                4
                                )
                    
                                      WHEN
                          r.id_attributename = 3
                            THEN
                              (
                               SELECT
                                5
                                )
                     WHEN
                          r.id_attributename = 1
                            THEN
                              (
                               SELECT
                                6
                                )
                    
                                      WHEN
                          r.id_attributename = 2
                            THEN
                              (
                               SELECT
                                7
                                )
                        END AS number

                        FROM
                            ds_resource AS r
                            INNER JOIN ds_attributename AS an ON (an.id_attributename = r.id_attributename)
                        WHERE r.trade_resources = true
                        ORDER BY number";
                break;
            case 'UNIT':
                $sql = "SELECT
                            uc.id_unitclass AS id,
                            uc.classname AS name
                        FROM ds_unitclass AS uc
                        ORDER BY uc.id_unitclass";
                break;
            case 'ARTEFACT':
                $sql = "SELECT
                            e.id_artefactprobability AS id,
                            e.prefix AS name 
                        FROM ds_artefactprobability AS e
                        ORDER BY e.id_artefactprobability";
                break;
            case 'MONEY':
                $sql = "SELECT
                            m.id_money  AS id,
                            m.money_name AS name
                        FROM
                            ds_money AS m
                        ORDER BY m.id_money";
                break;
            default:
                ds_error("Unknown category name = ".$categoryName);
        }
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
}

?>