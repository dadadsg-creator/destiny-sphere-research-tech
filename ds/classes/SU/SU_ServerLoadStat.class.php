<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_ServerLoadStat
{

    static public function  MainInterface()
    {
        if (isset($_REQUEST['action'])) 
        {
            $action = $_REQUEST['action'];
        }else{
            $action = '';
        }
        
        if (isset($_GET['dbid']) && is_array($_GET['dbid'])) 
        {
            $dbIds = $_GET['dbid'];
        } else {
            $dbIds = array();
        }
        
        $out = "<h1>ServersLoadStatistic</h1>";
        
        $out .= self::GetHTTPServerLoad()."<br><br>";
        
        $out .= '<b>Select DB server to get statistic</b>' . "\n";
        $out .= '<form action="?" method="GET">' . "\n";
        $out .= '<input type="hidden" name="action" value="getstat" />';
        $out .= '<table width="300" cellspacing="0" cellpadding="3" border="1">' . "\n";

        foreach ($GLOBALS['dbWorldAccess'] as $k => $v) 
        {
            if (empty($action) || in_array($k, $dbIds)) 
            {
                $checked = ' checked="checked"';
            } else {
                $checked = '';
            }
            
            $out .= '<tr>';
            $out .= '<td>'.$v['server'].'</td>';
            $out .= '<td><input type="checkbox" name="dbid[]" value="' . $k . '" '. $checked .' /></td>';
            $out .= '</tr>' . "\n";
        }

        $out .= '<tr><td align="center" colspan="2"><input type="submit" value="Get load!" /></td></tr>';
        $out .= '</table>' . "\n";
        $out .= '</form>' . "\n<br />";
        
        echo $out;
        flush(); 
        
        $out = '';
        
        if ('getstat' === $action) 
        {
            
            echo SU_DebugTools::GetInternalDate()." - START GET STATISTICS FROM SERVERS<br>";
            flush();
            
            $worldStat = array();

            if (!key_exists('dbid', $_GET) || !is_array($_GET['dbid'])) 
            {
                $_GET['dbid'] = array();
            }

            foreach ($_GET['dbid'] as $dbid) 
            {
                $dbid = intval($dbid);
                
                echo SU_DebugTools::GetInternalDate()." - Start get stat for server [".$GLOBALS['dbWorldAccess'][$dbid]['server']."]<br>";
                flush();                
                
                $worldStat[$GLOBALS['dbWorldAccess'][$dbid]['server']] = self::GetDBServerLoad($dbid);
                
                echo SU_DebugTools::GetInternalDate()." - OK!<br>";
                flush();
            }

            echo "<br><br>";
            flush();
            
            if (0 === count($dbIds)) 
            {
                $out .= '<div>No database selected!</div>';
            } else {
                $out .= SU_StatTableOut::getFormattedArray($worldStat);
            }
        }
        
        return $out;
    }
    
    static public function GetHTTPServerLoad()
    {       
        $serverLoad = SU_HttpServerLoad::Get();
        $out = "<b>HTTP SERVER [".$_SERVER['SERVER_NAME']."]:</b> \n".$serverLoad[0]." ".$serverLoad[1]." ".$serverLoad[2]."\n\n";
        
        return $out;
    }

    /**
     * Выборка из БД статистики
     * @param int $DBID идентификатор БД
     * @static
     * */
    static public function getDbServerLoad($DBID)
    {
        $resultArray = array();
        $dbData = $GLOBALS['dbWorldAccess'][$DBID];

        $common = DS_Common::CreateForCurentDB($DBID);
        $common->setDbID($DBID);
        
        $resultArray = array
        (
            'SERVER_TOTAL' => array (
                'COUNT_TRANSACTIONS' => 'N/A',
                'COUNT_USERINSTANCES' => 'N/A',
                'COUNT_REFRESHED_USERS_PER_HOUR' => 'N/A',
                'COUNT_REFRESHED_USERS_PER_DAY' => 'N/A',
            ),
            'BATTLES' => array (
                'TOTAL_BATTLES_COUNT' => 'N/A',
                'BATTLES_START_LAST_WEEK' => 'N/A',
                'BATTLES_START_LAST_DAY' => 'N/A',
                'AVER_BATTLEROUNDS_COUNT' => 'N/A',
                'AVER_UNITGROUP_IN_BATTLE' => 'N/A',
            ),
            'COMBS' => array (
                'COUNT_LIVE_COMBS_TOTAL' => 'N/A',
                'COUNT_COLONYS' => 'N/A',
                'COUNT_BUILDINGS' => 'N/A',
            ),
            'ARCHIOLOGY' => array (
                'COUNT_ARTEFACTS' => 'N/A',
                'COUNT_ARCHIOLOGY_TEEMS_ON_SEARCH' => 'N/A'
            ),
            'EVENTS' => array (
                'COUNT_NOT_READY_EVENTS' => 'N/A',
                'COUNT_READY_EVENTS' => 'N/A',
                'COUNT_DEAD_EVENTS' => 'N/A',
                'COUNT_TRASH_EVENTS' => 'N/A'
            ),
            'TRADE' => array (
                'COUNT_TOTAL_TRADES' => 'N/A',              
                'COUNT_AUCTIONS' => 'N/A',              
                'COUNT_NO_TRADES' => 'N/A',             
                
                'COUNT_TRADES_ON_INTERRING_TRADING' => 'N/A',
                'COUNT_TRADES_ON_ALLIANCE_TRADING' => 'N/A',
                'COUNT_TRADES_ON_RING_TRADING' => 'N/A',                
                'COUNT_TRADES_ON_COMB_COMPLEX_TRADING' => 'N/A',                
                
                'COUNT_RESOURCE' => 'N/A',
                'COUNT_UNIT' => 'N/A',
                'COUNT_ARTEFACT' => 'N/A',              
            ),                  
            'POWER_SHILDS' => array (
                'COUNT_BUILDINGS' => 'N/A',
                'COUNT_NEVER_WORKED_SHILDS' => 'N/A',                                                               
                'COUNT_SHILDS_WORKING' => 'N/A',                
            ),
        );
        
        ////////////////////SERVER_TOTAL//////////////////////////////////////
        $sql = '
            SELECT
                count(pid) as activity
            FROM 
                pg_stat_activity
            WHERE 
                datname = \'' . $dbData['dbname'] . '\'';
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));
        $resultArray['SERVER_TOTAL']['COUNT_TRANSACTIONS'] = number_format($res[0]['activity']);
        
        $sql = "SELECT count(*) as c
                FROM ds_userinstance as e"; 
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['SERVER_TOTAL']['COUNT_USERINSTANCES'] = number_format($res[0]['c']);
        
        $sql = "SELECT count(*) as c
                FROM ds_userinstance as e
                WHERE
                    e.lastrefresh < now()
                    AND
                    e.lastrefresh > (now() - '1 hour'::interval)";  
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['SERVER_TOTAL']['COUNT_REFRESHED_USERS_PER_HOUR'] = number_format($res[0]['c']);
        
        $sql = "SELECT count(*) as c
                FROM ds_userinstance as e
                WHERE
                    e.lastrefresh < now()
                    AND
                    e.lastrefresh > (now() - '24 hour'::interval)"; 
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['SERVER_TOTAL']['COUNT_REFRESHED_USERS_PER_DAY'] = number_format($res[0]['c']);
        
        ////////////////////////////////// BATTLES ///////////////////////////////////
        $sql = "  SELECT count(*) as c
                FROM ds_battle as e";   
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['BATTLES']['TOTAL_BATTLES_COUNT'] = number_format($res[0]['c']);
        
        $sql = "SELECT count(*) as c
                FROM ds_battle as e
                WHERE e.battlestartdate > (now() - '24 hour'::interval)";   
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['BATTLES']['BATTLES_START_LAST_DAY'] = number_format($res[0]['c']);
        
        $sql = "SELECT count(*) as c
                FROM ds_battle as e
                WHERE e.battlestartdate > (now() - '168 hour'::interval)";  
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['BATTLES']['BATTLES_START_LAST_WEEK'] = number_format($res[0]['c']);
        
        $sql = "  SELECT avg(t.m) as c
         FROM
         (
                SELECT max(e.roundnumber) as m
                FROM ds_battleround as e
                WHERE e.roundnumber != 0
                GROUP BY e.id_battle
         ) as t";   
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['BATTLES']['AVER_BATTLEROUNDS_COUNT'] = round($res[0]['c'], 4);
        
        $sql = "  
            SELECT avg(t.colvo) as c
            FROM (  SELECT count(*) as colvo
            FROM ds_battleround as e
            INNER JOIN ds_battleresult as b on (b.id_battleround = e.id_battleround)
            WHERE e.roundnumber != 0
            GROUP BY e.id_battle ) as t";   
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['BATTLES']['AVER_UNITGROUP_IN_BATTLE'] = round($res[0]['c'], 4);
                
        ////////////////////////////////// COMBS //////////////////////////////
        $sql = "  
            SELECT count(*) AS c
            FROM ds_comb AS c
            WHERE NOT c.id_userinstance IS NULL
            ";  
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['COMBS']['COUNT_LIVE_COMBS_TOTAL'] = number_format($res[0]['c']);
        
        $sql = "  
            SELECT count(*) AS c
            FROM
                ds_comb AS c
                INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
            WHERE
                NOT c.id_userinstance IS NULL
                AND
                cc.id_complextype = 2
            ";  
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['COMBS']['COUNT_COLONYS'] = number_format($res[0]['c']);
        
        $sql = "  
            SELECT count(*) AS c
            FROM ds_attachedbuilding AS ab
            ";  
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['COMBS']['COUNT_BUILDINGS'] = number_format($res[0]['c']);
        
        ///////////////////////////// ARCHIOLOGY /////////////////////////////////////
        $sql = "  
            SELECT count(*) AS c
            FROM ds_artefactinstance AS a
            ";  
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['ARCHIOLOGY']['COUNT_ARTEFACTS'] = number_format($res[0]['c']);
        
        $sql = "  
            SELECT count(*) AS c
            FROM ds_archeologicalteam AS e  
            WHERE e.endtime > now()
            ";  
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['ARCHIOLOGY']['COUNT_ARCHIOLOGY_TEEMS_ON_SEARCH'] = number_format($res[0]['c']);
        
        ///////////////////////////// EVENTS /////////////////////////////////////
        $sql = "select  count(*) as c
         FROM ds_eventqueue as e
         where
              e.istrash = 'f'
              and
              e.eventendtime > now()";  
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['EVENTS']['COUNT_NOT_READY_EVENTS'] = number_format($res[0]['c']);
        
        $sql = "select  count(*) as c
                FROM ds_eventqueue as e
                where
                    e.istrash = 'f'
                    and
                    e.eventendtime > (now() - '7 days'::interval)
                    AND
                    e.eventendtime < now()";    
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['EVENTS']['COUNT_READY_EVENTS'] = number_format($res[0]['c']);
        
        $sql = "select  count(*) as c
         FROM ds_eventqueue as e
         where
              e.istrash = 'f'
              and
              e.eventendtime < (now() - '7 days'::interval)";   
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['EVENTS']['COUNT_DEAD_EVENTS'] = number_format($res[0]['c']);
        
        $sql = "select  count(*) as c
                FROM ds_eventqueue as e
                where
                e.istrash = 't'";   
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        $resultArray['EVENTS']['COUNT_TRASH_EVENTS'] = number_format($res[0]['c']);
        
        
        //////////////////////////////// TRADE /////////////////////////////////////
        $sql = "SELECT count(*) AS c
                FROM ds_trade_lots";    
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));   
        $resultArray['TRADE']['COUNT_TOTAL_TRADES'] = number_format($res[0]['c']);
        
        $sql = "SELECT
                    count(*) AS c,
                    t.is_auction
                FROM ds_trade_lots AS t
                GROUP BY t.is_auction"; 
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));           
        foreach($res as $k=>$v)
        {
            if('t' != $v['is_auction'])
            {
                $resultArray['TRADE']['COUNT_NO_TRADES'] = number_format($v['c']);
            }else{
                $resultArray['TRADE']['COUNT_AUCTIONS'] = number_format($v['c']);
            }
        }
                
        $sql = "SELECT
                    count(*) AS c,
                    ts.tradescopename
                FROM
                    ds_trade_lots AS t
                    INNER JOIN ds_trade_scope AS ts ON (ts.id_tradescope = t.id_tradescope)
                GROUP BY ts.tradescopename";    
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));           
        foreach($res as $k=>$v)
        {
            $resultArray['TRADE']['COUNT_TRADES_ON_'.$v['tradescopename']] = number_format($v['c']);            
        }
        
        
        $sql = "SELECT
                    count(*) AS c,
                    tc.categoryname
                FROM
                    ds_trade_lots AS t
                    INNER JOIN ds_trade_type AS tt ON (tt.id_tradetype = t.id_tradetype)
                    INNER JOIN ds_trade_category AS tc ON (tc.id_tradecategory = tt.id_trade_on_category)
                GROUP BY tc.categoryname";  
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));           
        foreach($res as $k=>$v)
        {
            $resultArray['TRADE']['COUNT_'.$v['categoryname']] = number_format($v['c']);            
        }
                
        
        
        //////////////////////////////// SHILD/////////////////////////////////////             
        $sql = "SELECT count(*) as c FROM ds_powershild_on_userinstance AS ui"; 
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));   
        $resultArray['POWER_SHILDS']['COUNT_BUILDINGS'] = number_format($res[0]['c']);
        
        $sql = "SELECT count(*) as c FROM ds_powershild_on_userinstance WHERE last_work_length = 1";    
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));   
        $resultArray['POWER_SHILDS']['COUNT_NEVER_WORKED_SHILDS'] = number_format($res[0]['c']);
        
        $sql = "SELECT count(*) AS c
                FROM
                    ds_powershild_current_status AS cs 
                WHERE
                    cs.id_powershildstatusname = 5";    
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));   
        $resultArray['POWER_SHILDS']['COUNT_SHILDS_WORKING'] = number_format($res[0]['c']);
        

        return $resultArray;
    }   
}

?>