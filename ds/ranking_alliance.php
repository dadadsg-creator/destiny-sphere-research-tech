<?PHP $CVS_Version = '$Revision: 1.4 $ ; $Date: 2008/02/19 14:14:56 $';
// 10.02.2006 version 1.3

ini_set('memory_limit', '16M');
require_once ( 'Main.php' );

SU_GameSessionWork::SessionContinue();

import('DS_Common');

require_once 'include/Smarty/Smarty.class.php';
require_once('include/GameDown.inc.php');

include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/ranking_alliance.lang");
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
     * Object DS_Cache
     *
     * @var DS_Cache
     */
    var $cache;
    var $common;


    function Ranking ()
    {
        $this->cache = DS_Cache::Singleton();
        $this->common = new DS_Common();

        if(!$this->showRanking($_GET))
        {
            die($this->errormsg);
        }
    }

    function showRanking ($data)
    {
        //global $common;

        if (!isset($_SESSION["lang"]))
        {
            $lang = constant('DEFAULT_LANGUAGE');
        } else {
            $lang = $_SESSION["lang"];
        }

        $uiid = 0;
        if((isset($_SESSION["uiid"])) && (!@$data["player"]))
        {
            $uiid = $_SESSION["uiid"];
        }

        if(!isset($_SESSION["world"]) && !isset($data["world"]))
        {
            $world = constant('DEFAULT_WORLD');
        } elseif (isset($data["world"])) {
            $world = (int)$data["world"];
        } elseif (isset($_SESSION["world"])) {
            $world = (int)$_SESSION["world"];
        }

        if(isset($data["dbid"]))
        {
            $dbId = (int)$data["dbid"];
        } elseif(isset($_SESSION["dbId"] )) {
            $dbId = (int)$_SESSION["dbId"];
        } else {
            $dbId = 0;
        }

        if (!$this->getWorldList($dbId))
        {
            $wList = $this->getWorldList();
            foreach ($wList as $key => $value)
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
            $sortOrder2 = " ORDER BY totalpoints DESC ";
        }
    
        $sql = sprintf("
                select 
                    count(members) as overall_records
                from 
                    ds_allianceranking
                WHERE 
                    id_world = %d", $world);
        $res = $this->common->getResultArray ( "overall_records_id_world_".$world, $sql, "", "", "GLOBAL_CACHE" );
        if($res === false) 
        {
            $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking3";
            return false;
        }

        $count = $res[0]['overall_records'];
        if(isset($data["offset"]))
        {
            if(empty($data["offset"])) 
            {
                $offset = 0;
            } else {
                $offset = (int)$data["offset"];
            }
        } else {
            $offset = 0;
        }


        /* ТУТ Собственно место вызова темплейта */
        $tpl = new Smarty;
        $tpl->template_dir = "templates/";
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;

        $worldList = $this->getWorldList();
        $current_world = $worldList[$dbId][$world];

        $tpl->assign("CURRENT_LTL_WORLDNAME",$current_world['worldname']);
        
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
                $option_value = $v['worldname'];
                $world_select_options[$option_key] = $option_value;

                if ($dbId == $key && $world == $k)
                {
                    $world_select_options_current = $option_key;
                }
            }
        }
        $tpl->assign("WORLD_SELECT_OPTIONS",$world_select_options);
        $tpl->assign('WORLD_SELECT_OPTIONS_CURRENT', $world_select_options_current);

        if(!$this->common->setDbId($this->dbid))
        {
            $this->errormsg = "SYSTEM ERROR Ranking1::SetDB: Bad Set db Id $k".$this->common->errormsg;
            return false;
        }

        $sql = sprintf("
                select 
                    * 
                from 
                    ds_allianceranking
                WHERE 
                    id_world = %d
                    %s
                limit $this->limit 
                offset $offset",
                $world, $sortOrder2 
            );
        $identyfier = "limit_".$this->limit."_offset_".$offset."_".$world."_".$sortOrder2;
        $res = $this->common->getResultArray ( "alliance_ranking_2_".$identyfier, $sql, "", "", "GLOBAL_CACHE" );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR Ranking::ShowRanking5";
            return false;
        }

        $ofs = $offset;
        $rows = array();


        for($i=0; $i<sizeof($res); $i++)
        {
            $ofs++;
            $res[$i]['row'] = $ofs;
        }


        if(key_exists('player', $data) && !empty($data["player"]) && VD_UserName::Validate($data["player"]))
        {
            $playerSearch = $data["player"];
        } else {
            $playerSearch = "";
        }
    
        $tpl->assign('ROW_ELEMENT', $res);
        $tpl->assign('CURRENT_WORLDID', $world);
        $tpl->assign('CURRENT_DBID', $dbId);
        $tpl->assign('CURRENT_OFFSET', $offset);
        $tpl->assign('CURRENT_PLAYER', $playerSearch);
        $tpl->assign('CURRENT_UIID', $uiid);
        $tpl->assign("lang", $GLOBALS['lang']);

        /**
         * ButtonLeft
         */
        if($offset - $this->limit >= 0)
        {
            $button_left = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?world=".$world."&dbid=".$dbId."&offset=".( $offset - $this->limit >= 0 ? $offset - $this->limit : 0 )."&player=".$playerSearch."&sort=".$sortOrder."&dir=".$sortDirection."\">
            <img src='../img/buttons/btn_prev.gif' border=0></a>";
        } else {
            $button_left = "<img src='../img/buttons/btn_prev.gif' border=0>";
        }

        /**
         * FirstPage
         */
        if ( $offset - $this->limit >= 0 )
        {
            $fist_page = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?world=".$world."&dbid=".$dbId."&offset=0&sort=".$sortOrder."&dir=".$sortDirection."\" class=wnd_gen_txt_large><img src=\"../img/elements/icon_minipost.gif\" width=12 height=9 border=0><b>1</b></a>";
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
            $last_page = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?world=".$world."&dbid=".$dbId."&offset=".((ceil($count/$this->limit)-1)*$this->limit)."&sort=".$sortOrder."&dir=".$sortDirection."\" class=wnd_gen_txt_large><img src=\"../img/elements/icon_minipost.gif\" width=12 height=9 border=0><b>".ceil($count/$this->limit)."</b></a>";
        } else {
            $last_page = "&nbsp;";
        }


        /**
         * ButtonRight
         */
        if ( $offset + $this->limit < $count )
        {
            $button_right = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?world=".$world."&dbid=".$dbId."&offset=".( $offset + $this->limit >= $count ? $offset : $offset + $this->limit )."&sort=".$sortOrder."&dir=".$sortDirection."\"><img src='../img/buttons/btn_next.gif' border=\"0\"></a>";
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


        $tpl->display('ranking_alliance.tpl');

        $res = "";

        return true;
    }

    function _formWorldList()
    {
        //global $common;

        $identifier = 'FormWorldListByAlRank';
        $lifetime = 10800;

        $rs = $this->cache->get($identifier);
        if($rs)
        {
            $res_assoc=$rs;
        }else{

            $res_assoc = array();
            $res_tmp = array();

            foreach ($GLOBALS['dbWorldAccess'] as $k=>$v)
            {
                $res_tmp = array();
                $dbId = $k;

                if(!$this->common->setDbId($dbId))
                {
                    $this->errormsg = "SYSTEM ERROR: Ranking::SetDB4 Bad Set db Id $dbId";
                    return false;
                }

                $sql = "
                    SELECT * FROM ds_world WHERE world_identifier != 'UC' AND world_status != 5
                    ";
                $res = $this->common->getResultArray ( "", $sql, "", "", "" );

                if ($res === false)
                {
                    return false;
                }
                
                foreach ($res as $key => $v)
                {
                    $res_tmp[$v['id_world']] = $v;
                }

                $res['dbId'] = $dbId;
                if (sizeof($res_tmp) > 0)
                {
                    $res_assoc[$dbId] = $res_tmp;
                }

                $res = array();
            }
            $this->cache->set($identifier, $lifetime, $res_assoc);
        }
        return $res_assoc;
    }

    function getWorldList($dbId = null, $worldId = null)
    {
        //var_dump($dbId);
        //var_dump($worldId);

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