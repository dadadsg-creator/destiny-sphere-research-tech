<?php

class TypeSearch
{
    static public function GetAllWorldsList()
    {
        $common = DS_Common::CreateForCurentDB(0);

        $out = array();

        foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
        {
            $common->setDbId((int)$dbid);

            $sql = "SELECT id_world, worldname FROM ds_world";
            $res = $common->getResultArray('', $sql, '', '', '');
            assert(empty($common->errormsg));

            foreach($res as $k=>$v)
            {
                $v['dbid'] = $dbid;
                $out[] = $v;
            }
        }
        return $out;
    }
    
    static public function getArtefactScopes($DBID)
    {
        assert(is_int($DBID) && $DBID>=0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
            SELECT
                ars.id_artefactscope as id,
                COALESCE(ltl.text, ars.scopename) as name,
                ars.scopename
            FROM
                ds_artefactscope AS ars
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ars.id_localizedscope AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
            ORDER BY ars.id_artefactscope
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
    
    static public function getArtefactSizes($DBID)
    {
        assert(is_int($DBID) && $DBID>=0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
            SELECT
                ars.id_artefactprobability as id,
                COALESCE(ltl.text, ars.prefix) as name,
                ars.prefix
            FROM
                ds_artefactprobability AS ars
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ars.id_localizedprefix AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
            ORDER BY ars.id_artefactprobability
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
    
    static public function GetAllResources($DBID)
    {
        assert(is_int($DBID) && $DBID >= 0);

        $common = DS_Common::CreateForCurrentDB($DBID);

        $sql = "SELECT
                    r.id_attributename AS id,
                    an.attributename AS name
                FROM
                    ds_resource AS r
                INNER JOIN ds_attributename AS an ON (an.id_attributename = r.id_attributename)
                WHERE 
                    r.trade_resources = true";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetAllManey($DBID)
    {
        assert(is_int($DBID) && $DBID >= 0);

        $common = DS_Common::CreateForCurrentDB($DBID);

        $sql = "        SELECT
                            m.id_money  AS id,
                            m.money_name AS name
                        FROM
                            ds_money AS m
                        ORDER BY m.id_money";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetAllScopeList()
    {
        
        $common = DS_Common::CreateForCurentDB(0);

        $out = array();

            $common->setDbId((int)$dbid);

            $sql = "SELECT
                        ts.id_tradescope,
                        ts.tradescopename,
                        COALESCE(ltl.text, ts.tradescopename) as scopename
                        FROM
                        ds_trade_scope as ts
                    LEFT JOIN ds_localizedtextlanguage as ltl ON 
                            (
                            ltl.id_localizedtext = ts.id_localizedname
                            AND
                            ltl.id_language = ".constant("DEFAULT_LANGUAGE").")";
            
            $res = $common->getResultArray('', $sql, '', '', '');
            assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetTableLeftColumnCategorys($DBID)
    {
        assert(is_int($DBID) && $DBID>=0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "SELECT *
                FROM ds_trade_category AS tc
                WHERE can_trade_on = true
                ORDER BY tc.id_tradecategory";              
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetTableUpRowsCategorys($DBID)
    {
        assert(is_int($DBID) && $DBID>=0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "SELECT *
                FROM ds_trade_category AS tc
                WHERE can_trade_by = true
                ORDER BY tc.id_tradecategory";              
        $res = $common->getResultArray ( "", $sql, "", "", "");
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

    static public function GetTradeTypeDataByAllID($DBID, $worldID, $tradeOnCatID, $tradeOnTypeID, $tradeByCatID, $tradeByTypeID)
    {
        /*echo $tradeOnCatID;
        echo $tradeOnTypeID;
        echo $tradeByCatID;
        echo $tradeByTypeID;*/
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert(is_int($tradeOnCatID) && $tradeOnCatID>0);
        assert(is_int($tradeOnTypeID) && $tradeOnTypeID>0);
        assert(is_int($tradeByCatID) && $tradeByCatID>0);
        assert(is_int($tradeByTypeID) && $tradeByTypeID>0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
            SELECT
                tt.id_tradetype,
                ttd.add_date,
                ttd.is_allowed,
                ttd.max_price_delta,
                ttd.min_price_delta,
                ttd.tax
            FROM
                ds_trade_type_data AS ttd
                INNER JOIN ds_trade_type AS tt ON (tt.id_tradetype = ttd.id_tradetype AND ttd.is_current = true)
            WHERE
                tt.id_trade_on_category = $tradeOnCatID
                AND
                tt.id_trade_on_type = $tradeOnTypeID
                AND
                tt.id_trade_by_category = $tradeByCatID
                AND
                tt.id_trade_by_type = $tradeByTypeID
                AND
                ttd.id_world = $worldID";
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetTradeTypeDataByTYpeIDAndWorldID($DBID, $worldID, $tradeTypeID)
    {
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert(is_int($tradeTypeID) && $tradeTypeID>0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
            SELECT
                tt.*,
                ttd.*,
                tcon.categoryname AS category_on_name,
                tcby.categoryname AS category_by_name
            FROM
                ds_trade_type_data AS ttd
                INNER JOIN ds_trade_type AS tt ON (tt.id_tradetype = ttd.id_tradetype AND ttd.is_current = true)
                INNER JOIN ds_trade_category AS tcon ON (tcon.id_tradecategory = tt.id_trade_on_category)
                INNER JOIN ds_trade_category AS tcby ON (tcby.id_tradecategory = tt.id_trade_by_category)
            WHERE
                tt.id_tradetype = $tradeTypeID
                AND
                ttd.id_world = $worldID
            ";
        $res = $common->getResultArray ( "", $sql, "", "", "");

        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0];
    }

    static public function SetTradeTypeDataAsOldANDInsertTradeTypeData($DBID, $worldID, $tradeTypeID, $isAllowed, $maxDelta, $minDelta, $tax)
    {
//echo "is al = $isAllowed";        
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert(is_int($tradeTypeID) && $tradeTypeID>0);
        assert($isAllowed == 't' || $isAllowed == 'f');

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "BEGIN; 
                UPDATE ds_trade_type_data
                SET is_current = false
                WHERE 
                    id_tradetype = ".$tradeTypeID."
                    AND
                    id_world = $worldID;
                INSERT INTO ds_trade_type_data
                (id_tradetype, is_allowed, max_price_delta, min_price_delta, tax, id_world)
                VALUES
                ($tradeTypeID, '$isAllowed', $maxDelta, $minDelta, $tax, $worldID)";

        $res = $common->getResultArray ( "", $sql, "", "", "");
        if (!empty($common->errormsg))
        {
            $sqlRolback = "ROLLBACK";

            $res = $common->getResultArray ( "", $sqlRolback, "", "", "");
            assert(empty($common->errormsg));
            
            echo "Can't execute query: <xmp>$sql</xmp>";
            exit;
        }
        
        $sql = "COMMIT";

        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));
    }

    static public function InsertTradeTypeData($DBID, $worldID, $tradeTypeID, $isAllowed, $maxDelta, $minDelta, $tax)
    {
//echo "is al = $isAllowed";        
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert(is_int($tradeTypeID) && $tradeTypeID>0);
        assert($isAllowed == 't' || $isAllowed == 'f');

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "INSERT INTO ds_trade_type_data
                (id_tradetype, is_allowed, max_price_delta, min_price_delta, tax, id_world)
                VALUES
                ($tradeTypeID, '$isAllowed', $maxDelta, $minDelta, $tax, $worldID)";

        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));
    }

    static public function GetTradeTypeHistoryData($DBID, $worldID, $tradeTypeID)
    {
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert(is_int($tradeTypeID) && $tradeTypeID>0);

        $common = DS_Common::CreateForCurentDB($DBID);
        $sql = "
            SELECT
                ttd.*
            FROM
                ds_trade_type_data AS ttd
            WHERE
                ttd.id_tradetype = $tradeTypeID
                AND
                ttd.id_world = $worldID
            ORDER BY ttd.add_date DESC
            ";
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function AddTradeTypeData($DBID, $worldID, $tradeTypeID, $isAllowed, $maxDelta, $minDelta, $tax)
    {
        if ($tradeTypeID == 0) {
            return;
        }
        
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert(is_int($tradeTypeID) && $tradeTypeID>0);
        assert($maxDelta>=0);
        assert($minDelta<=0);
        assert($tax>=0);
        assert($isAllowed == 't' || $isAllowed == 'f');

        switch ($isAllowed)
        {
            case 't':
                $isAllowed = 'true';
                break;
            case 'f':
                $isAllowed = 'false';
                break;
        }

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "INSERT INTO
                    ds_trade_type_data (id_tradetype,
                                        is_allowed,
                                        max_price_delta,
                                        min_price_delta,
                                        tax,
                                        is_current,
                                        id_world)
                VALUES ($tradeTypeID, $isAllowed, $maxDelta, $minDelta, $tax,  true, $worldID)";
//echo "<xmp>".$sql."</xmp>";
//exit;
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));
    }

    static public function GetIdTradeTypeByAllID($DBID, $worldID, $tradeOnCatID, $tradeOnTypeID, $tradeByCatID, $tradeByTypeID)
    {
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert(is_int($tradeOnCatID) && $tradeOnCatID>0);
        assert(is_int($tradeOnTypeID) && $tradeOnTypeID>0);
        assert(is_int($tradeByCatID) && $tradeByCatID>0);
        assert(is_int($tradeByTypeID) && $tradeByTypeID>0);

        $common = DS_Common::CreateForCurentDB($DBID);

        $sql = "
            SELECT
                id_tradetype
            FROM
                ds_trade_type
            WHERE
                id_trade_on_category = $tradeOnCatID
                AND
                id_trade_on_type = $tradeOnTypeID
                AND
                id_trade_by_category = $tradeByCatID
                AND
                id_trade_by_type = $tradeByTypeID";

        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
    static public function GetNameByCategory($DBID, $worldID, $tradeCat, $tradeType)
    {
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert(is_int($tradeCat) && $tradeCat>0);
        assert(is_int($tradeType) && $tradeType>0);

        $common = DS_Common::CreateForCurentDB($DBID);

        switch($tradeCat)
        {
            case '1':
                $sql = "SELECT
                            an.attributename AS name
                        FROM
                            ds_resource AS r
                            INNER JOIN ds_attributename AS an ON (an.id_attributename = r.id_attributename)
                        WHERE r.id_attributename = $tradeType";
                break;
            case '2':
                $sql = "SELECT
                              uc.classname AS name
                        FROM  ds_unitclass AS uc
                        WHERE uc.id_unitclass = $tradeType";
                break;
            case '3':
                $sql = "SELECT
                            e.prefix AS name
                        FROM ds_artefactprobability AS e
                        WHERE
                        e.id_artefactprobability = $tradeType";
        }
//echo "<xmp>".$sql."</xmp>";
//exit;
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

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
//echo "<xmp>".$sql."</xmp>";
//exit; 
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
    
    static public function GetCategoryNameByCategoryID($DBID, $categoryID)
    {
        assert(is_int($DBID) && $DBID >= 0);
        assert($categoryID>0);
        
        $common = DS_Common::CreateForCurentDB($dbID);
        
            $sql = "SELECT categoryname
                        FROM ds_trade_category
                        WHERE
                        id_tradecategory = $categoryID";
            
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(count($res) == 0)
        {
            return 0;
        }
        
        return $res[0]['categoryname']; 
    }
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
        
        $res[] = array('id' => '4', 'name' => 'QUEST');
        
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
    
    static public function GetCoursesForNano($DBID, $worldID)
    {
        $common = DS_Common::CreateForCurentDB($DBID);
        
        $sql = "SELECT 


                        
                                       trcd.id_resource,
                                       trcd.value
                                FROM
                                    ds_trade_resorce_course_data AS trcd
                                WHERE
                               
                                    trcd.is_current = true
                                    AND
                                    trcd.id_world = ".$worldID."
                                    AND
                                    trcd.id_tradecourse = 2";   
/*echo "<xmp>";
echo $sql;      
echo "</xmp>";

exit ;  */      
        $res = $common->getResultArray ( "", $sql, "", "", "");     
        assert(empty($common->errormsg) && count($res) > 0);

        return $res;
    }
}

?>