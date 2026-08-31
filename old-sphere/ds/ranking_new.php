<?PHP
// 10.02.2006 version 1.3

ini_set('memory_limit', '256M');
require_once ( 'Main.php' );
SU_GameSessionWork::SessionContinue();

require_once 'include/Smarty/Smarty.class.php';
require_once('include/GameDown.inc.php');

include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/ranking.lang");
//echo "language/".constant('DEFAULT_LANGUAGE_SHORT')."/ranking.lang<br>";

if (GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}

class Ranking
{
    var $errormsg;
    var $db;
    var $limit = 25;
    var $trans;
    var $dbid;

    /**
     * Вызов DB интресфейса
     *
     * @var DS_Common
     */
    var $common;

    /**
     * Object DS_Cache
     *
     * @var DS_Cache
     */
    var $cache;


    function Ranking()
    {
        $this->cache = DS_Cache::Singleton();
        $this->common = new DS_Common();

        if(!$this->showRanking($_GET))
        {
            die($this->errormsg);
        }
    }

    function showRanking($data)
    {
        $usersNotInRanking = "('DestinySphere', 'HerrDerRinge', 'Curunir', 'Hexenkoenig', 'Morgoth', 'Support', 'Support2', 'Support3')";
        
        if(!isset($_SESSION["lang"]))
        {
            $lang = constant('DEFAULT_LANGUAGE');
        } else {
            $lang = (int)$_SESSION["lang"];
        }

        $uiid = 0;

        if((isset($_SESSION["uiid"])) && (!isset($data["player"])))
        {
            $uiid = (int)$_SESSION["uiid"];
        }

        if(isset($data["dbid"]))
        {
            $dbId = (int)$data["dbid"];
        } elseif (isset($_SESSION["dbId"])) {
            $dbId = (int)$_SESSION["dbId"];
        } else {
            $dbId = $this->getDefaultDbId();
        }


        if(!isset($_SESSION["world"]) && !isset($data["world"]))
        {
            $world = constant('DEFAULT_WORLD');
        } elseif(isset( $data["world"])) {
            $world = (int)$data["world"];
        } elseif(isset($_SESSION["world"])) {
            $world = (int)$_SESSION["world"];
        }

        if(!$this->getWorldList($dbId))
        {
            $wList = $this->getWorldList();
            foreach($wList as $key => $value)
            {
                if (sizeof($value) > 0)
                {
                    $dbId = $key;
                    asort($value);
                    reset($value);
                    $world = key($value);
                    break;
                } else {
                    continue;
                }
            }
        }
        if(!$this->getWorldList($dbId,$world))
        {
            $wList = $this->getWorldList($dbId);
            asort($wList);
            reset($wList);
            $world = key($wList);
        }

        if(GameDown($dbId, $world))
        {
            Header("Status: 302 Moved Temporaly");
            Header("Location: index_down.html");
            exit();
        }

        $this->dbid = $dbId;
        if(!$this->common->setDbId($dbId))
        {
            $this->errormsg = "SYSTEM ERROR Ranking::SetDB: Bad Set db Id $k".$this->common->errormsg;
            return false;
        }


        $showBattlexp = true;
        
        if(key_exists('player', $data) && !empty($data["player"]))
        {
            $playerSearch = htmlspecialchars($data["player"]);
        } else {
            $playerSearch = "";
        }

        if(!empty($playerSearch) && VD_UserName::Validate($playerSearch))
        {
            $playerSearch2 = " AND username ILIKE E'%".pg_escape_string($playerSearch)."%' ";
        } else {
            $playerSearch2 = "";
        }

        if(key_exists('alliance', $data) && !empty($data["alliance"]))
        {
            $allianceInfo = (int)$data["alliance"];
        } else {
            $allianceInfo = 0;
        }
        
        if($allianceInfo > 0)
        {
            $allianceInfo2 = " and a.id_alliance = ".$allianceInfo;
        } else {
            $allianceInfo2 = "";
        }

        if(key_exists('race', $data) && !empty($data['race']))
        {
            $raceInfo = (int)$data["race"];
        } else {
            $raceInfo = '';
        }

        if($raceInfo)
        {
            $raceInfo2 = " and rac.id_race = ".$raceInfo;
        } else {
            $raceInfo2 = "";
        }

        if(key_exists('complex', $data) && !empty($data['complex']))
        {
            $complex = $data["complex"];
        } else {
            $complex = '';
        }

        if(isset($complex) && preg_match('/^[1-4]{1}\.[0-9]{1,4}\.[1-6]{1}$/', $complex))
        {
            $coordinates = explode(".", $complex);
            if($coordinates[0] == 4)
            {
                $sql ="
                SELECT
                    username
                FROM
                    ds_user u,
                    ds_userinstance ui,
                    ds_comb co,
                    ds_combcomplex cc,
                    ds_ring ri
                WHERE
                    ui.id_world = ".$world."  AND
                    co.id_combcomplex = cc.id_combcomplex AND
                    cc.id_ring = ri.id_ring AND
                    co.id_userinstance = ui.id_userinstance AND
                    ui.id_user = u.id_user AND
                    ri.ringnumber = ".(int)$coordinates[0]." AND
                    cc.complexnumber = ".(int)$coordinates[1]."
                ORDER BY 
                    co.combnumber";
                $res = $this->common->getResultArray ( "complex_userlist_".$world."_".$coordinates[0].".".$coordinates[1], $sql, "", "", "GLOBAL_CACHE" );
                if($res === false)
                {
                    $this->errormsg = "SYSTEM ERROR Ranking::Compex is bad";
                    return false;
                }

                if(!$res)
                {
                    $this->errormsg = "Compex is empty";
                    return false;
                }

                $complexInfo = " AND username IN (";

                foreach ($res as $k=>$v)
                {
                    $complexInfo .= "'".$v['username']."',";
                }
                $complexInfo = preg_replace('/,$/','',$complexInfo);
                $complexInfo .= ")";
            } else {
                $complexInfo = " AND ui.id_userinstance IN 
                          (
                            SELECT c.id_userinstance
                            FROM ds_comb c
                            LEFT JOIN ds_combcomplex cc ON (cc.id_combcomplex = c.id_combcomplex)
                            LEFT JOIN ds_ring r ON (r.id_ring = cc.id_ring)
                            WHERE c.id_userinstance = ui.id_userinstance 
                            AND  
                            r.ringnumber != 4
                            AND 
                            r.ringnumber||'.'||cc.complexnumber LIKE '".(int)$coordinates[0].".".(int)@$coordinates[1] ."'
                            )";
            }
        } else {
            $complexInfo = "";
        }

        if(key_exists('sort', $data) && !empty($data['sort']))
        {
            $sortOrder = $data["sort"];
        } else {
            $sortOrder = '';
        }
        
        if(key_exists('dir', $data) && !empty($data['dir']))
        {
            $sortDirection = $data["dir"];
        } else {
            $sortDirection = '';
        }

        if(isset($sortOrder) && preg_match('/^[a-zA-Z0-9_]+$/', $sortOrder) && (strtolower($sortDirection) === 'desc' || strtolower($sortDirection) === 'asc'))
        {
            $sortOrder2 = " ORDER BY ".$sortOrder." ".$sortDirection;
        } else {
            $sortOrder2 = " ORDER BY thefinal desc ";
        }

        $sql = sprintf(
            "
            SELECT 
                            COALESCE(
                            (
                            COALESCE(r.science_ranking, 0)
                            + 
                            COALESCE(r.artefact_ranking, 0)
                            + 
                            COALESCE(r.building_ranking, 0)
                            + 
                            COALESCE(b.absolute_value, 0)*100
                            ), 0)::BIGINT as thefinal,
                            r.science_ranking as scienceranking,
                            r.artefact_ranking as artefactranking,
                            r.building_ranking as buildingranking,
                            COALESCE(b.absolute_value, 0) as bo,
                            a.alliancename,
                            ui.id_userinstance,
                            lg.league_name,
                         --   r.id_level,
                            COALESCE(value, 0) as battlexp,
                            COALESCE(absolute_value, 0) as battlexpabs,
                            l.text as racename,
                            a.id_alliance,
                            rac.id_race
                    FROM 
                        ds_userinstance ui
                    LEFT JOIN ds_user u ON (ui.id_user = u.id_user)
                    LEFT JOIN ds_ranking r ON (r.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_battlexp b ON (b.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_userhasalliance uha ON (uha.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_alliance a ON (a.id_alliance = uha.id_alliance)
                    LEFT JOIN ds_race rac ON (rac.id_race = ui.id_race)
                    LEFT JOIN ds_league lg ON (lg.id_league = ui.id_league)
                    LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = rac.id_localizedname AND l.id_language = ".constant('DEFAULT_LANGUAGE').")
                    WHERE
                        ui.id_world = %d
                        %s %s %s %s 
                        AND 
                        u.username NOT IN ".$usersNotInRanking."
                        %s ",
                    $world, $playerSearch2, $allianceInfo2, $raceInfo2, $complexInfo, $sortOrder2
                    
                /*  SELECT
                ra.*,
                COALESCE(value, 0) as battlexp,
                COALESCE(absolute_value, 0) as battlexpabs
            FROM
                ds_finalranking ra
                LEFT JOIN ds_battlexp ubx ON ( ra.id_userinstance = ubx.id_userinstance )
            WHERE
                id_world = %d
                %s %s %s %s 
                AND 
                ra.username NOT IN ".$usersNotInRanking."
                %s ",
                $world, $playerSearch2, $allianceInfo2, $raceInfo2, $complexInfo, $sortOrder2*/
            );
        $identyfier = "w_".$world."_pS2_".$playerSearch2."_aI2_".$allianceInfo2."_rI2_".$raceInfo2."_cI_".$complexInfo."_sO2_".$sortOrder2;     
        $res = $this->common->getResultArray ( "rank_new_1_".$identyfier, $sql, 1800, "", "GLOBAL_CACHE" ); 
        
        assert(empty($this->common->errormsg));     

        $count = sizeof($res);      

        if(isset($data["offset"]))
        {
            if(empty($data["offset"]))
            {
                $offset = 0;
            } else {
                $offset = (int)$data["offset"];
            }
        } else {
            if ($uiid == 0)
            {
                $offset = 0;
            } else {
                $e = 0;
                $a = 0;
                for($i=0; $i<sizeof($res);$i++)
                {
                    $e++;
                    $c = 0;
                    if($e == $this->limit)
                    {
                        $a++;
                        $e = 0;
                        $c = 1;
                    }

                    if($res[$i]['id_userinstance'] == $uiid)
                    {
                        if($c == 1)
                        {
                            $a--;
                        }

                        $offset = ($a)*$this->limit;

                        if($offset < 0)
                        {
                            $offset = 0;
                        }
                    }
                }
            }
        }

        if(!isset($offset))
        {
            $offset = 0;
        }


        /* ТУТ Собственно место вызова темплейта */
        $tpl = new Smarty;
        $tpl->template_dir = "templates/";
        $tpl->compile_dir = constant('SMARTY_CACHE_PATH');
        $tpl->compile_check = true;
        $tpl->debugging = false;

        $worldList = $this->getWorldList();
        $current_world = $worldList[$dbId][$world];

        $tpl->assign("CURRENT_LTL_WORLDNAME", $current_world['worldname']);

        /**
         * Формируем массив для выпадающего меню выбора миров с авто-переходом.
         */
        foreach ($worldList as $key => $value)
        {
            if (is_null($value))
            {
                continue;
            }

            foreach ($value as $k => $v)
            {
                $option_key = $_SERVER['PHP_SELF']."?world=".$k."&dbid=".$key;
                $option_value['name'] = $v['worldname'];
                $option_value['status'] = $v['world_status'];
                /*
                if($v['world_status'] == 5)
                {
                    $option_value = '<span color="red">'.$option_value.'</span>';
                }
                */
                $world_select_options[$option_key] = $option_value;

                if ($dbId == $key && $world == $k)
                {
                    $world_select_options_current = $option_key;
                }
            }
        }
        
        $tpl->assign('CURRENT_WORLDID', $world);
        $tpl->assign('CURRENT_DBID', $dbId);
        $tpl->assign('CURRENT_OFFSET', $offset);
        $tpl->assign('CURRENT_PLAYER', $playerSearch);
        $tpl->assign('CURRENT_ALLIANCEINFO', $allianceInfo);
        $tpl->assign('CURRENT_RACEINFO', $raceInfo);
        $tpl->assign('CURRENT_UIID', $uiid);
        $tpl->assign("lang",$GLOBALS['lang']);

        $tpl->assign("WORLD_SELECT_OPTIONS",$world_select_options);
        $tpl->assign('WORLD_SELECT_OPTIONS_CURRENT', $world_select_options_current);

        if(!$this->common->setDbId($this->dbid))
        {
            $this->errormsg = "SYSTEM ERROR Ranking1::SetDB: Bad Set db Id $k".$this->common->errormsg;
            return false;
        }

        $sql = sprintf ("
        SELECT 
                        COALESCE(
                            (
                            COALESCE(r.science_ranking, 0)
                            + 
                            COALESCE(r.artefact_ranking, 0)
                            + 
                            COALESCE(r.building_ranking, 0)
                            + 
                            COALESCE(b.absolute_value, 0)*100
                            ), 0)::BIGINT as thefinal,
                            COALESCE(r.artefact_ranking, 0)::BIGINT as artefactranking,
                            COALESCE(r.building_ranking, 0)::BIGINT as buildingranking,
                            COALESCE(r.science_ranking, 0)::BIGINT as scienceranking,
                            u.username,
                            COALESCE(b.absolute_value, 0) as bo,
                            a.alliancename,
                            ui.id_userinstance,
                            lg.league_name,
                            r.id_level,
                            COALESCE(value, 0) as battlexp,
                            COALESCE(absolute_value, 0) as battlexpabs,
                            COALESCE(npc_xp, 0) as npc_xp,
                            a.alliancetag,
                            l.text as racename,
                            a.id_alliance,
                            rac.id_race
                    FROM 
                        ds_userinstance ui
                    LEFT JOIN ds_user u ON (ui.id_user = u.id_user)
                    LEFT JOIN ds_ranking r ON (r.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_battlexp b ON (b.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_userhasalliance uha ON (uha.id_userinstance = ui.id_userinstance)
                    LEFT JOIN ds_alliance a ON (a.id_alliance = uha.id_alliance)
                    LEFT JOIN ds_race rac ON (rac.id_race = ui.id_race)
                    LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = rac.id_localizedname AND l.id_language = ".constant('DEFAULT_LANGUAGE').")
                    LEFT JOIN ds_league lg ON (lg.id_league = ui.id_league)
                    WHERE
                        ui.id_world = %d
                        %s %s %s %s 
                        AND 
                        u.username NOT IN ".$usersNotInRanking."
                        %s                  
                    LIMIT $this->limit OFFSET $offset"
        /*SELECT
                    ra.*,
                    COALESCE(value, 0) as battlexp,
                    COALESCE(absolute_value, 0) as battlexpabs
                FROM
                    ds_finalranking ra
                    LEFT JOIN ds_battlexp ubx ON ( ra.id_userinstance = ubx.id_userinstance )
                WHERE
                    ra.id_world = %d
                    %s %s %s %s 
                    AND 
                    ra.username NOT IN ".$usersNotInRanking."
                    %s                  
                LIMIT $this->limit OFFSET $offset"*/ 
        ,
        $world, $playerSearch2, $allianceInfo2, $raceInfo2, $complexInfo, $sortOrder2);

        $identifier = "w_".$world."_pS2_".$playerSearch2."_aI2_".$allianceInfo2."_rI2_".$raceInfo2."_cI_".$complexInfo."_sO2_".$sortOrder2."_l_".$this->limit."_o_".$offset;
        
        $res = $this->common->getResultArray("rank_new_2_".$identifier, $sql, 1800, "", "GLOBAL_CACHE");
        if($res === false)
        {
            $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking5";
            return false;
        }

        $ofs = $offset;

        $rows = array();
        for($i=0; $i<sizeof($res); $i++)
        {
            $ofs++;

            $sql = "
                SELECT
                    ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber as coords,
                    ri.ringnumber,
                    cn.combname
                FROM
                    ds_comb co,
                    ds_combcomplex cc,
                    ds_ring ri,
                    ds_combname cn
                WHERE
                    co.id_combcomplex = cc.id_combcomplex AND
                    cc.id_ring = ri.id_ring AND
                    co.id_comb = cn.id_comb AND
                    co.id_userinstance = ".$res[$i]['id_userinstance']."
                ORDER BY coords";

            $rs = $this->common->getResultArray("COMB_LIST_new_".$res[$i]['id_userinstance'], $sql, "", "", "GLOBAL_CACHE");

            if ($rs === false)
            {
                $this->errormsg = "ERROR: Ranking:: Unable to get comb list for ".$res[$i]['id_userinstance'];
                return false;
            }

            //var_dump($rs);
            $coords = array();
            foreach ($rs as $k => $v)
            {
                $str = "<a href='".basename($_SERVER['SCRIPT_NAME'])."?world=".$world."&dbid=".$dbId."&complex=".$v['coords']."' class='wnd_gen_ranking_large'>" .$v['coords']. "</a>";
                $coords[] = $str;
                if ($v['ringnumber'] != 4) {
                    $combname = $v['combname'];
                }
            }
            $res[$i]['coordinates'] = implode(', ',$coords);
            $res[$i]['combname'] = $combname;
            $res[$i]['row'] = $ofs;
        }
        $tpl->assign('ROW_ELEMENT', $res);

        /**
         * ButtonLeft
         */
        if($offset - $this->limit >= 0)
        {
            $button_left = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?world=".$world."&dbid=".$dbId."&offset=".( $offset - $this->limit >= 0 ? $offset - $this->limit : 0 )."&player=".$playerSearch."&alliance=".$allianceInfo."&race=".$raceInfo."&sort=".$sortOrder."&dir=".$sortDirection."\">
            <img src='../img/buttons/btn_prev.gif' border=0></a>";
        } else {
            $button_left = "<img src='../img/buttons/btn_prev.gif' border=0>";
        }

        /**
         * FirstPage
         */
        if ( $offset - $this->limit >= 0 )
        {
            $fist_page = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?world=".$world."&dbid=".$dbId."&offset=0&player=".$playerSearch."&alliance=".$allianceInfo."&race=".$raceInfo."&sort=".$sortOrder."&dir=".$sortDirection."\" class=wnd_gen_txt_large><img src=\"../img/elements/icon_minipost.gif\" width=12 height=9 border=0><b>1</b></a>";
        } else {
            $fist_page = "&nbsp;";
        }

        /**
         * CurrentPage
         */
        $current_page = "&nbsp;";
        $a = 0;
        for ($i=0; $i<=$count; $i=$i+$this->limit)
        {
            $a++;
            if ( $i == $offset )
            {
                $current_page = "<img src=\"../img/elements/icon_newest_reply.gif\" width=12 height=9 border=0>".$a;
            }
        }

        /**
         * LastPage
         */
        if ( $offset + $this->limit < $count )
        {
            $last_page = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?world=".$world."&dbid=".$dbId."&offset=".((ceil($count/$this->limit)-1)*$this->limit)."&player=".$playerSearch."&alliance=".$allianceInfo."&race=".$raceInfo."&sort=".$sortOrder."&dir=".$sortDirection."\" class=wnd_gen_txt_large><img src=\"../img/elements/icon_minipost.gif\" width=12 height=9 border=0><b>".ceil($count/$this->limit)."</b></a>";
        } else {
            $last_page = "&nbsp;";
        }

        /**
         * ButtonRight
         */
        if ( $offset + $this->limit < $count )
        {
            $button_right = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?world=".$world."&dbid=".$dbId."&offset=".( $offset + $this->limit >= $count ? $offset : $offset + $this->limit )."&player=".$playerSearch."&alliance=".$allianceInfo."&race=".$raceInfo."&sort=".$sortOrder."&dir=".$sortDirection."\"><img src='../img/buttons/btn_next.gif' border=\"0\"></a>";
        } else {
            $button_right = "<img src='../img/buttons/btn_next.gif' border=\"0\">";
        }

        $navigation = array(
            'button_left' => $button_left,
            'fist_page' => $fist_page,
            'current_page' => $current_page,
            'last_page' => $last_page,
            'button_right' => $button_right
        );

        $tpl->assign('NAVIGATION_ARRAY', $navigation);

        $tpl->display('ranking_new.tpl');

        $res = "";
        return true;
    }

    function getDefaultDbId()
    {
        if( $_SERVER['SERVER_NAME'] == 'alpha.destinysphere.ru' || 
            $_SERVER['SERVER_NAME'] == '192.168.0.11' || 
            $_SERVER['SERVER_NAME'] == 'www2.destinysphere.net' || 
            $_SERVER['SERVER_NAME'] == 'portal' )
        {
            $dbId = 0;
        } else {
            $dbId = 1;
        }
        return $dbId;
    }

    /**
     * Формируем список миров
     * @return array
     */
    function _formWorldList()
    {       
        $this->common->setDbId(0);
                        
        $sql = "SELECT 
                    w.* 
                FROM 
                    ds_world w
                LEFT JOIN ds_gamemodifier g ON (g.id_world = w.id_world)
                WHERE 
                    g.modifiername = 'SHOW_RANKING'
                    AND 
                    g.value = 1";
        $res = $this->common->getMultySql($sql);
        assert(empty($this->common->errormsg));     
        
        $out = array();
        
        foreach($res as $k=>$v)
        {
            $out[$v['dbId']][$v['id_world']] = $v;
        }
        
        return $out;
    }

    function getWorldList($dbId = null, $worldId = null)
    {       

        $worldList = $this->_formWorldList();
            
        if (is_null($dbId) && is_null($worldId))
        {
            return $worldList;
        }

        if (!is_null($dbId) && is_null($worldId))
        {
            if (key_exists($dbId, $worldList))
            {
                return $worldList[$dbId];
            } else {
                return false;
            }
        }

        if (!is_null($dbId) && !is_null($worldId))
        {
            if (key_exists($dbId, $worldList) && key_exists($worldId, $worldList[$dbId]))
            {
                return $worldList[$dbId][$worldId];
            } else {
                return false;
            }
        }

        return false;
    }
}

$r = new Ranking;

?>