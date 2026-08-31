<?php

class TradeLogSearch
{
    static public function getTradesLogsCount()
    {
        $common = DS_Common::CreateForCurrentDB();
        
        $sql = self::_GetQuery();
        $sql = "SELECT count(*)
                    FROM
                    ( $sql ) as tab
                    ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res[0]['count'];
    }

    static public function getTradesLogs($limit, $offset)
    {
        $common = DS_Common::CreateForCurrentDB();
        
        $sql = self::_GetQuery($limit, $offset);
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function getStateOfCourseRecountByIdTrade($DBID, $idTrade)
    {
        assert(is_int($DBID) && $DBID >= 0);
        assert($idTrade > 0);

        $common = DS_Common::CreateForCurrentDB($DBID);

        $sql = "
            SELECT
                hist.course_recount
            FROM
                ds_trade_history AS hist
            WHERE
                hist.id_trade = $idTrade
            ";  

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res[0]['course_recount'];
    }

    static public function changeStateOfCourseRecountByIdTrade($DBID, $idTrade, $state)
    {
        assert(is_int($DBID) && $DBID >= 0);
        assert($idTrade > 0);
        assert($state == 't' || $state == 'f');

        if ($state == 't')
        {
            $s = 'true';
        }

        if ($state == 'f')
        {
            $s = 'false';
        }

        $common = DS_Common::CreateForCurrentDB($DBID);

        $sql = "
                UPDATE
                    ds_trade_history SET course_recount = $s
                WHERE
                    id_trade = $idTrade
                ";  

        $common->executeQuery($sql);
        assert(empty($common->errormsg));

        return;
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
    
    static public function GetBits($idTrade)
    {
        assert(is_int($idTrade) && $idTrade > 0);
        
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "
            SELECT 
                username,
                summ,
                bid_date,
                ip,
                cancel_date,
                cancel_ip       
            FROM 
                ds_trade_history_bid thb
            LEFT JOIN ds_user AS u ON (u.id_user = thb.id_user)
            WHERE 
                thb.id_trade = $idTrade
            ORDER BY 
                thb.bid_date DESC
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
    
    static private function _GetQuery($limit = 0, $offset = 0)
    {
        $userName = $_SESSION['user'];
        $fromDate = $_SESSION['startDate'];
        $toDate = $_SESSION['endDate'];
        $DBID = $_SESSION["dbId"];
        $worldID = $_SESSION['worldID'];
        $scopes = $_SESSION['scopes'];
        $catecories = $_SESSION['catecories'];
        $catecoriesfor = $_SESSION['catecoriesfor'];
        $recources = $_SESSION['recources'];
        $recourcesfor = $_SESSION['recourcesfor'];
        $money = $_SESSION['money'];
        $class = $_SESSION['class'];
        $race = $_SESSION['race'];
        $unitName = $_SESSION['unitName'];
        $artName = $_SESSION['artName'];
        $artscope = $_SESSION['artscope'];
        $size = $_SESSION['size'];
        $showSameIp = $_SESSION['showSameIp'];
        $ip = $_SESSION['ipAddress'];
        $auctionOnly = $_SESSION['AuctionOnly'];
        
        assert(is_int($DBID) && $DBID >= 0);
        assert(is_int($worldID) && $worldID > 0);
        assert($limit >= 0);
        assert($offset >= 0);
        assert(is_array($scopes));
        assert(is_array($catecories));
        assert(is_array($catecoriesfor));
        assert(is_array($recources));
        assert(is_array($money));
        if (!is_array($race)) $race = array();
        assert(is_array($class));
        
        $where = ' TRUE';

        if(!empty($userName))
        {
            $where .= " AND (uf.username LIKE '$userName'
                            OR
                            ut.username LIKE '$userName')
                            ";
        }

        if(!empty($fromDate))
        {
            $where .= " AND hist.start_date > to_timestamp($fromDate)";
        }

        if(!empty($toDate))
        {
            $where .= " AND hist.end_date < to_timestamp($toDate)";
        }

        if($limit > 0)
        {
            $lim = " OFFSET $offset LIMIT $limit";
        }

        //Фильтр по скопам
        $or = '';
        if (count($scopes) != 5) {
            $whereScope = 'AND
                        ( ';

            foreach ($scopes as $k => $scope)
            {
                $whereScope .= "$or
                            hist.id_trade_scope = ".$scope." ";
                $or = " OR ";
            }
            $whereScope .= " )";
        }
        

        $whrereSell = "";
        $or = "";
        
        //Фильтр по тому, что покупали
        foreach ($catecories as $k => $cat)
        {
            //Если это ресурсы
            if ($cat == '1')
            {

                foreach ($recources as $k => $res)
                {
                    $whrereSell .= " $or
                             (t.id_from_category = 1  AND filtr1=$res)";
                    $or = " OR ";
                }
            }

            //Если это юниты
            if ($cat == '2')
            {               
                //Если введено имя юнита
                if ($unitName != "")
                {
                    $whrereSell .= " $or
                                (t.id_from_category = 2  AND t.goods_from_s = '$unitName')";
                    $or = "OR";

                } else {
                    
                    $whrereSell .= "$or
                        (   t.id_from_category = 2
                            AND ";
                    //Где расса юнита
                    $whereRace = "(";
                    $or = "";

                    foreach ($race as $k => $unitrace)
                    {
                        $whereRace .= "
                                    $or
                                    t.filtr1= '$unitrace' ";
                        $or = "OR";
                    }
                    $whereRace .= ")";

                    $whrereSell .= $whereRace;
                    $whrereSell .= "  AND ";

                    //Где класс юнита
                    $whereClass = "(";
                    $or = "";

                    foreach ($class as $k => $unitclass)
                    {
                        $whereClass .= "
                                    $or
                                    t.filtr2= '$unitclass' ";
                        $or = "OR";
                    }
                    $whereClass .= ")";

                    $whrereSell .= $whereClass;
                    
                    $whrereSell .= ")";
                    $or = "OR";
                }
            }

            //Если это артефакты
            if ($cat == '3')
            {
                //Если введено имя арта
                if ($artName != "")
                {
                    $whrereSell .= " $or
                                (t.goods_from_s = '$artName')";
                    $or = "OR";

                } else {
                    $whrereSell .= "$or
                            ( t.id_from_category = 3 AND ";
                
                //Где скоп артефакта
                $whereArtscope = "(";
                $or = "";

                foreach ($artscope as $k => $s)
                {
                    $whereArtscope .= "
                                    $or
                                    t.filtr1= '$s' ";
                    $or = "OR";
                }
                $whereArtscope .= ")";

                $whrereSell .= $whereArtscope;
                $whrereSell .= "  AND ";

                //Где размер артефакта
                $whereSize = "(";
                $or = "";

                foreach ($size as $k => $ss)
                {
                    $whereSize .= "
                                    $or
                                    t.filtr2= '$ss' ";
                    $or = "OR";
                }
                $whereSize .= ")";

                $whrereSell .= $whereSize;

                $whrereSell .= ")";
            }
            }
        }

        if ($whrereSell != "") {
            $whrereSell = "(".$whrereSell.")";
        }
        
        //фильтр по курсу
        $down = "";
        $condition = "";
        if ($_SESSION['lt'] != '')
        {
            $whereCource = " cdf.course_dif ".$_SESSION['firstSign']." ".$_SESSION['lt'];

            $condition = " ".$_SESSION['condition']." ";
            
            $down = "  
                        WHERE $whereCource";
        }

        if ($_SESSION['gt'] != '') {
            $whereCource .= $condition." cdf.course_dif ".$_SESSION['secondSign']." ".$_SESSION['gt'];
            
            $down = "  
                        WHERE $whereCource";
        }

        
        //Фильтр по тому, за что покупали
        $whereCellFor = 'AND
                        ( ';
        $or = "";
        foreach ($catecoriesfor as $k => $cat)
        {
            if ($cat == '1')
            {
                foreach ($recourcesfor as $k => $res)
                {
                    $whereCellFor .= "$or
                             (hist.id_to_category=$cat  AND hist.id_to_article=$res)";
                    $or = " OR ";
                }
            }

            if ($cat == '4')
            {
                foreach ($money as $k => $mon)
                {
                    $whereCellFor .= "$or
                             (hist.id_to_category=$cat  AND hist.id_to_article=$mon)";
                    $or = " OR ";
                }
            }
        }

        $whereCellFor .= " )";
        
        //Если мы хотим посмотреть торги с одинаковым айпишником
        if ($showSameIp == 1) {
            $whereIP = '
            AND 
                hist.from_ip =  hist.to_ip
            '   ;       
        }
        
        //Если мы хотим посмотреть торги с определенным айпишником
        if ($ip != '') {
            $whereIP = "
            AND 
                (hist.from_ip = '$ip'
                OR
                hist.to_ip = '$ip')
            "   ;       
        }
        
        //Если мы хотим посмотреть торги с одинаковым айпишником
        if ($auctionOnly == 1) {
            $whereAuctionOnly = "
            AND 
                hist.is_auction =  't'
            ";      
        } else {
            $whereAuctionOnly = '';
        }

        $sql = "SELECT *
                    FROM
                    (SELECT t.*, ( (100*(t.to_ammount/t.from_ammount-t.cource)/t.cource)
                    ) as course_dif
                                    FROM (
                SELECT

             DISTINCT hist.id_trade,
                hist.id_from_category,          
                hist.start_date,
                hist.end_date,
                uf.username AS seller,
                (
                 SELECT
                    COALESCE(cn.combname, 'empty comb')||'
                    ('||r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber||')'
                FROM
                    ds_comb AS c
                LEFT JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                LEFT JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                LEFT JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
                WHERE
                    c.id_comb = hist.from_comb_id
                ) 
                as comb_from,
                ut.username AS customer,
                (
                 SELECT
                     COALESCE(cn.combname, 'empty comb')||'
                    ('||r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber||')'
                 FROM
                    ds_comb AS c
                 LEFT JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                 LEFT JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                 LEFT JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
                 WHERE
                    c.id_comb = hist.to_comb_id
                ) 
                as comb_to,
               CASE
        -- 1 recource
        WHEN hist.id_from_category = 1 THEN
        (
            SELECT
                an.attributename
            FROM
                ds_attributename an
            WHERE
                hist.id_from_article = an.id_attributename
        )
        
        -- 2 units
        WHEN c_from.id_tradecategory = 2 THEN
        (
            SELECT
                uni.unitname||'<br>'||ltl.text
            FROM
                ds_unit uni
            LEFT JOIN ds_localizedtextlanguage ltl ON (ltl.id_localizedtext = uni.id_localizedname AND ltl.id_language = 3)
            WHERE
                hist.id_from_article = uni.id_unit
        )

        -- 3 arts
        WHEN c_from.id_tradecategory = 3 THEN
        (
            SELECT
                art.artefactname||'<br>'||ltl.text
            FROM
                ds_artefact art
            LEFT JOIN ds_localizedtextlanguage ltl ON (ltl.id_localizedtext = art.id_localizedname AND ltl.id_language = 3)
            WHERE
                hist.id_from_article = art.id_artefact
        )
        
        ELSE
            c_from.categoryname
        END AS goods_from,
               CASE
        -- 1 recource
        WHEN hist.id_from_category = 1 THEN
        (
            SELECT
                an.attributename
            FROM
                ds_attributename an
            WHERE
                hist.id_from_article = an.id_attributename
        )
        
        -- 2 units
        WHEN c_from.id_tradecategory = 2 THEN
        (
            SELECT
                uni.unitname
            FROM
                ds_unit uni
            WHERE
                hist.id_from_article = uni.id_unit
        )

        -- 3 arts
        WHEN c_from.id_tradecategory = 3 THEN
        (
            SELECT
                art.artefactname
            FROM
                ds_artefact art
            WHERE
                hist.id_from_article = art.id_artefact
        )
        
        ELSE
            c_from.categoryname
        END AS goods_from_s,

                hist.from_ammount,
                CASE
                 WHEN c_to.id_tradecategory = 1
                    THEN
                      (
                        SELECT
                            an.attributename
                        FROM
                            ds_attributename an
                        WHERE
                            hist.id_to_article = an.id_attributename
                        )
                 WHEN c_to.id_tradecategory = 4
                    THEN
                     (
                      SELECT
                        mon.money_name
                      FROM
                        ds_money mon
                      WHERE
                        hist.id_to_article = mon.id_money
                       )
                ELSE
                     c_to.categoryname
                END AS goods_to,
                hist.to_ammount,
                tax_summ,
                ts.tradescopename,
                CASE
                    WHEN hist.id_from_category = 1
                        THEN
                        (
                        SELECT a.value FROM ( SELECT *          
                        FROM ds_trade_resorce_course_data as trcd
                        WHERE   trcd.id_resource = hist.id_from_article
                                AND trcd.id_tradecourse = (
                                    SELECT t.id_tradecourse
                                    FROM ds_trade_course as t
                                    WHERE t.id_tradecategory = hist.id_to_category
                                          AND
                                          t.id_article = hist.id_to_article)
                                AND
                                trcd.add_date < hist.start_date
                                AND
                                trcd.id_world = $worldID
                                ORDER BY trcd.add_date DESC
                                 ) as a
                                LIMIT 1
                       )
                            
                    WHEN hist.id_from_category = 2
                        THEN
                        (
                        SELECT a.value FROM ( SELECT *
                        FROM ds_trade_units_cours_data as trucd
                        WHERE   trucd.id_unit = hist.id_from_article
                                AND
                                trucd.id_tradecourse = (
                                    SELECT t.id_tradecourse
                                    FROM ds_trade_course as t
                                    WHERE   t.id_tradecategory = hist.id_to_category
                                            AND
                                            t.id_article = hist.id_to_article)
                                AND
                            trucd.add_date < hist.start_date
                                AND
                                trucd.id_world = $worldID
                                ORDER BY trucd.add_date DESC
                            ) as a
                                LIMIT 1
                    )
                                        
                    WHEN hist.id_from_category = 3
                        THEN
                        (
                            SELECT a.value FROM ( SELECT *
                        FROM ds_trade_artefact_course_data as tracd
                        WHERE tracd.id_artefact = hist.id_from_article
                                AND tracd.id_tradecourse = (
                                    SELECT t.id_tradecourse
                                    FROM ds_trade_course as t
                                    WHERE   t.id_tradecategory = hist.id_to_category
                                            AND
                                            t.id_article = hist.id_to_article)
                                AND
                                 tracd.add_date < hist.start_date
                                AND
                                tracd.id_world = $worldID
                                ORDER BY tracd.add_date DESC
                                ) as a
                                LIMIT 1
                           )                
                END AS cource,
                hist.is_auction,
                hist.from_ip,
                hist.to_ip,
                
                  CASE
                 -- 1 recource
                 WHEN
                  c_from.id_tradecategory = 1
                    THEN
                      (
                       SELECT
                            hist.id_from_article
                        )
                    -- 2 units
                    WHEN
                     c_from.id_tradecategory = 2
                    THEN
                     (
                      SELECT
                       ucbr.id_race
                      FROM
                       ds_unitcanbebuildbyrace ucbr         
                            WHERE
                             hist.id_from_article = ucbr.id_unit
                        LIMIT 1         
                        )
                    -- 3 arts
                    WHEN
                     c_from.id_tradecategory = 3
                    THEN
                     (
                      SELECT
                       art.id_artefactscope
                      FROM
                       ds_artefact art          
                            WHERE
                             hist.id_from_article = art.id_artefact         
                        )
                    ELSE
                     0
                END AS filtr1,
                
                CASE
                 -- 1 recource
                 WHEN
                  c_from.id_tradecategory = 1
                    THEN
                      (
                       SELECT
                        0
                        )
                    -- 2 units
                    WHEN
                     c_from.id_tradecategory = 2
                    THEN
                     (
                      SELECT
                       uhc.id_unitclass
                      FROM
                       ds_unithasclass uhc          
                            WHERE
                             hist.id_from_article = uhc.id_unit         
                        )
                    -- 3 arts
                    WHEN
                     c_from.id_tradecategory = 3
                    THEN
                     (
                      SELECT
                       art.id_artefactprobability
                      FROM
                       ds_artefact art          
                            WHERE
                             hist.id_from_article = art.id_artefact         
                        )
                    ELSE
                     0
                END AS filtr2
            
            FROM
                ds_trade_history AS hist
                LEFT JOIN ds_user AS uf ON (hist.from_user_id = uf.id_user)
                LEFT JOIN ds_user AS ut ON (hist.to_user_id = ut.id_user)
                INNER JOIN ds_trade_category AS c_from ON (c_from.id_tradecategory = hist.id_from_category)
                INNER JOIN ds_trade_category AS c_to ON (c_to.id_tradecategory = hist.id_to_category)
                LEFT JOIN ds_trade_scope AS ts ON (ts.id_tradescope = hist.id_trade_scope)
            WHERE 
                $where
                AND
                hist.world_id = $worldID
                $whereScope
                $whereCellFor
                $whereIP
                $whereAuctionOnly
            ORDER BY 
                hist.start_date DESC) as t
        WHERE
        $whrereSell
        ) as cdf 
        $down
        $lim
                    ";      
//SU_DebugTools::LogAll($sql, 'Annita.log');
/*echo "<pre>";
print_r($sql);
echo "</pre>";
exit;*/
        return $sql;
    }
}

?>