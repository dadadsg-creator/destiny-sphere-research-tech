<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

if ( !isset ( $GLOBALS["common"] ) ) {
    $common = & ClassFactory::getInstance ( "DS_Common" );
    $common->connect();
}


class Science {

    private $action = 'index';
    private $dbId;
    private $worldId;
    private $username;
    private $comment;
    private $id_branch;
    private $level;

    function Science()
    {   
        $this->_getParams($_POST);  
        
        switch ($this->action)
        {
            case 'add':
                $this->_add();
                $this->_showIndex();
                $this->_searsh();
                break;
                
            case 'search':
                $this->_showIndex();
                $this->_searsh();
                break;
                
            default:
                $this->_showIndex();
        }
    }
    
    private function _showIndex()
    {
        global $common;
        
        // Список миров
        $sql = sprintf ( "select * from ds_world" );
        $worlds = $common->getMultySql ($sql);
        
        echo "
        <form action='".$_SERVER["PHP_SELF"]."' method='POST'>
        <input type='hidden' name='action' value='search'>
        
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=3>
                        <tr>
                            <td bgcolor='444E6C' class=th_2>
                                ".local( 'DS_USER_WORLD' )."
                            </td>
                            <td bgcolor='444E6C' class=th_2>
                                ".local( 'DS_USER_USERNAME' )."
                            </td>
                        </tr>
                        <tr>
                            <td bgcolor='444E6C'>
                                <select name='world' style=\"width:250px;\">";

        foreach ($worlds as $key => $data)
        {
            echo "
                <option value='".$data['dbId']."_".$data['id_world']."'".($this->worldId."_".$this->dbId==$data['id_world']."_".$data['dbId'] ? " selected" : "").">".$data['worldname']."</option>";
        }

        echo "
                                </select><br>
                            </td>
                            <td bgcolor='444E6C'>
                                <input type='text' name='username' size=30 value='".$this->username."'>
                            </td>
                        </tr>
                    <tr>
                        <td colspan =2 bgcolor='444E6C'><input type='submit' class=submit name='search2' value='".local( 'DS_ACTION_SEARCH' )."'></td>
                    </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        </form>
        ";
    }

    private function _searsh()
    {
        global $common;
    
        assert($this->dbId >= 0);
        assert($this->worldId > 0);
        
        // Смотрим ник игрока
        if(!VD_UserName::Validate($this->username) || (!$userID = (int)VD_UserName::CheckIfExists($this->username, $common)))
        {
            ShowCMSMessage('WRONG USER NAME', 2);
            return false;
        }
        
        $common->setDbId($this->dbId);
        
        // Смотрим, зареган ли такой игрок
        $sql = "
                SELECT
                      ui.id_userinstance
                FROM
                    ds_user u
                LEFT JOIN     
                     ds_userinstance ui USING (id_user)
                WHERE
                     u.username = '".$this->username."'
                     AND
                     ui.id_world = ".$this->worldId;
     
        $res = $common->getResultArray ( "", $sql, "", "", "" );
    
        if (count($res) == 0) {
            ShowCMSMessage('NOT SUCH USER IN THIS WORLD', 2);
            return false;
        }
        
    
        // Выводим скрипт для проверки ввода комментария
        echo '
         <script type="text/javascript" language="JavaScript"> 
         function check(buttons,message){ 
            if ( buttons == "" ){
            alert(message); 
            return false; 
            }
            return true;
            } 
            </script> ';
        
        
        
        // Выбираем список наук и их максимальный уровень и текущий уровень у игрока
        $sql = 'SELECT
                        max(level),id_branch
                    FROM
                        ds_science
                GROUP BY id_branch';

        $branches_max = $common->getResultArray ( "", $sql, "", "", "" );

        if ( $branches_max === false || sizeof($branches_max) == 0 )
        {
            die("can't select brances");
        }

        // Выбираем список изученых наук у игрока
        $sql = "
                    SELECT
                        id_science,
                        id_branch,
                        level
                    FROM
                        ds_science                              
                    WHERE                           
                        id_science not in
                            (
                            SELECT
                                uhs.id_science
                            FROM
                                ds_userhasscience uhs
                            LEFT JOIN ds_userinstance ui ON (uhs.id_userinstance = ui.id_userinstance)
                            LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
                            WHERE
                                uhs.inresearch = 'f'
                                AND 
                                u.username = '".$this->username."'
                                AND 
                                ui.id_world = ".$this->worldId."
                            )";
   
        $reserchedScience = $common->getResultArray ( "", $sql, "", "", "" );
        
        if ($reserchedScience === false)
        {
            die("error1");
        }

        // Выбираем науки, находящиеся на изучении
        $sql = " SELECT
                                ep.id_eventqueue,
                                eho.value
                            FROM
                                ds_eventparticipant ep,
                                ds_eventqueue eq,
                                ds_event ev,
                                ds_eventhasoption eho,
                                ds_eventoption eo,
                                ds_userinstance ui,
                                ds_user u
                            WHERE
                                ep.id_eventqueue = eq.id_eventqueue and
                                eq.istrash = 'f' AND
                                eq.id_event = ev.id_event AND
                                ev.eventname = 'NEW_RESEARCH' AND
                                eho.id_eventqueue = eq.id_eventqueue and
                                eho.id_eventoption = eo.id_eventoption AND
                                eo.optionname = 'ID_Science' AND
                                ep.id_userinstance = ui.id_userinstance AND
                                ui.id_user = u.id_user
                                AND u.username = '".$this->username."'
                                AND ui.id_world = ".$this->worldId;
        $scienceInResearch = $common->getResultArray ( "", $sql, "", "", "" );
        if($common->errormsg)
        {
            die("can't get researched science");
        }
        

        $science = array();
        $branches = array();

        // Выбираем список всех наук
        $sql = 'SELECT
                        id_branch, branchname
                    from
                        ds_sciencebranch';

        $br = $common->getResultArray ( "", $sql, "", "", "" );

        if ( $br === false || sizeof($br) === 0)
        {
            die("Can't select all science branches");
        }

        foreach ($br as $k=>$v) $branches[$v['id_branch']] = $v['branchname'];
        for ($i = 0; $i < sizeof($branches_max); $i++)
        {
            $temp = array();
            for($j = 0; $j < sizeof($reserchedScience); $j++)
            {
                if($reserchedScience[$j]['id_branch'] == $branches_max[$i]['id_branch'])
                {
                    $temp[$reserchedScience[$j]['level']] = $reserchedScience[$j]['id_science'];
                }
            }
            $science[$branches_max[$i]['id_branch']] = $temp;
        }

        $colors = array(
                        1=>'#D3CABA',2=>'#FDE0AD',3=>'#9FBAEC',
                        4=>'#BABA8A',5=>'#72AC68',6=>'#F7F9B7',7=>'#763DBC');
        
        echo "
        <table border=0 cellspacing=0 cellpadding=0>
            <tr>
                <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=3>";

        

        for ($i = 0; $i < sizeof($branches_max); $i++)
        {
            echo "
                        <tr bgcolor=".$colors[$branches_max[$i]['id_branch']].">
                            <td>
                                ".local('ADMIN_'.$branches[$branches_max[$i]['id_branch']])."
                            </td>";
            for($j = 1; $j <= $branches_max[$i]['max']; $j++)
            {
                if(isset($science[$branches_max[$i]['id_branch']][$j]))
                {
                    if((sizeof($scienceInResearch) > 0 ) && ($scienceInResearch[0]['value'] === $scienceInResearch[$branches_max[$i]['id_branch']][$j]))
                    {
                        echo "
                            <td bgcolor='red' align='center'>
                                R
                            </td>";
                    }
                    else
                    {
                        echo "
                            <td bgcolor='white'>
                                $j
                            </td>";
                    }
                }
                else
                {
                    echo "
                            <td>
                                $j
                            </td>";
                }
            }
            echo "
                        </tr>";
        }

        echo "      
                    </table>
                </td>
            </tr>
        </table>
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>                            
                <table  border=0 cellspacing=1 cellpadding=3>
                    <tr>
                        <th colspan=4 bgcolor='444E6C' class='th_2'>
                            ".local('DS_ADD_SCIENCE')."
                        </th>
                    </tr>";
        for ($i = 0; $i < sizeof($branches_max); $i++)
        {
            echo "
                    <tr valign=\"center\">
                        <FORM name='add_science$i' action=\"index.php\" method=\"POST\" onsubmit=\"javascript: return check(document.add_science$i.comment.value,'Empty comment!');\">
                        <INPUT type=\"hidden\"  name=\"action\" value=\"add\">
                        <INPUT type=\"hidden\"  name=\"username\" value=\"$this->username\">
                        <INPUT type=\"hidden\"  name=\"world\" value=\"".$this->dbId."_".$this->worldId."\">                        
                        <INPUT type=\"hidden\" value=\"".$branches_max[$i]['id_branch']."\" name=\"id_branch\">
                        <td class=th_2 bgcolor='444E6C'>
                            ".local('ADMIN_'.$branches[$branches_max[$i]['id_branch']])."
                        </td>
                        <td class=th_2 bgcolor='444E6C'>
                            <SELECT name=level>";
            $checked = false;
            for($j = 1; $j <= $branches_max[$i]['max']; $j++)
            {
                if(isset($science[$branches_max[$i]['id_branch']][$j]) && !$checked)
                {
                    $checked = true;
                    echo "
                            <OPTION  value=$j selected> $j </OPTION>";
                } else {
                    echo "
                            <OPTION  value=$j> $j </OPTION>";
                }
            }
            echo "
                            </SELECT>
                        </td>
                        <td class=th_2 bgcolor='444E6C'>
                            <INPUT type='submit' class=submit name='add' value='".local('DS_ACTION_EDIT')."'>
                        <td class=th_2 bgcolor='444E6C'>
                            Comment: <INPUT type='text' name='comment' value='' size=20>
                        </td>
                        </FORM>
                    </tr>";
        }
        echo '
                </table>
                </td>
            </tr>
        </table>';
    }
    
    private function _add()
    {
        global $common;
        
        assert(is_int($this->dbId) && $this->dbId >= 0);
        assert(is_int($this->worldId) && $this->worldId > 0);
        
        if(!VD_UserName::Validate($this->username) || (!$userID = (int)VD_UserName::CheckIfExists($this->username, $common)))
        {
            ShowCMSMessage('WRONG USER NAME', 2);
            return false;
        }
        
        assert(is_int($this->id_branch) && $this->id_branch > 0);
        
        global $common;
        $common->setDbId($this->dbId);
        
        if (empty($this->comment))
        {
            ShowCMSMessage('<b>ENTER COMMENT!!</b>', 2);
            return false;
        }
        
        $sql = "
                SELECT
                      ui.id_userinstance
                FROM
                    ds_user u
                LEFT JOIN     
                     ds_userinstance ui USING (id_user)
                WHERE
                     u.username = '".$this->username."'
                     AND
                     ui.id_world = ".$this->worldId;
     
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        
        if (count($res) == 0) {
            ShowCMSMessage('NOT SUCH USER IN THIS WORLD', 2);
            return false;
        }
        
        
        $id_userinstance = (int)$res[0]['id_userinstance'];
        assert($id_userinstance > 0);

        if(!$this->endScienceEvent($id_userinstance))
        {
            echo "can't end scince event <br> science was not added";
            return false;
        }

        if($this->isUserHasScience($id_userinstance, $this->level, $this->id_branch))
        {
            $this->delScience($id_userinstance, $this->id_branch, $this->level);
        }
        else
        {
            $this->addScience($id_userinstance, $this->level, $this->id_branch);
        }

        return true;
    }
    
    
    
    private function _getParams($data)
    {
        if (isset($data["username"]))
        {
            $this->username = $data["username"];
        }

        if (isset($data["world"]))
        {
            $tmp = explode('_', $data['world']);
            $this->dbId = (int)$tmp[0];
            $this->worldId = (int)$tmp[1];
            assert($this->dbId >= 0);
            assert($this->worldId > 0);
            
        }

        if (isset($data["comment"]))
        {
            $this->comment = pg_escape_string(trim($data["comment"]));
        }

        if (isset($data["id_branch"]))
        {
            $this->id_branch = (int)$data["id_branch"];
        }

        if (isset($data["level"]))
        {
            $this->level = (int)$data["level"];
        }

        if (isset($data["action"]))
        {
            $this->action = $data["action"];
        }
    }
    
    function endScienceEvent($id_userinstance)
    {
        global $common;

        $sql = sprintf ("SELECT
                                ep.id_eventqueue,
                                eho.value
                            FROM
                                ds_eventparticipant ep,
                                ds_eventqueue eq,
                                ds_event ev,
                                ds_eventhasoption eho,
                                ds_eventoption eo
                            WHERE
                                ep.id_userinstance = %d AND
                                ep.id_eventqueue = eq.id_eventqueue and
                                eq.istrash = 'f' AND
                                eq.id_event = ev.id_event AND
                                ev.eventname = 'NEW_RESEARCH' AND
                                eho.id_eventqueue = eq.id_eventqueue and
                                eho.id_eventoption = eo.id_eventoption AND
                                eo.optionname = 'ID_Science'",
        $id_userinstance);

        $res = $common->getResultArray("", $sql, "", "", "");

        if($common->errormsg)
        {
            die("can't get researched science");
        }

        if(!isset($res[0]['id_eventqueue']) || !isset($res[0]['value']))
        {
            return true;
        }

        $common->executeQuery ('begin');

        $sql1 = sprintf("DELETE FROM
                            ds_eventhasoption 
                        WHERE 
                            id_eventqueue = %d",
        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql1);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event1" ;
            return false;
        }

        $sql2 = sprintf("DELETE FROM
                            ds_eventparticipant 
                        WHERE 
                            id_eventqueue = %d",
        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql2);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event2";
            return false;
        }

        $sql3 = sprintf("DELETE FROM
                            ds_combbindeventqueue 
                        WHERE 
                            id_eventqueue = %d",
        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql3);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event3";
            return false;
        }

        $sql4 = sprintf("DELETE FROM
                            ds_userhassciencebindeventqueue 
                        WHERE 
                            id_eventqueue = %d",
        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql4);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event4";
            return false;
        }

        $sql5 = sprintf("DELETE FROM
                            ds_eventqueue 
                        WHERE 
                            id_eventqueue = %d",
        $res[0]['id_eventqueue']);
        $common->executeQuery ($sql5);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event5  ";
            return false;
        }

        $sql6 = sprintf("DELETE FROM
                                ds_userhasscience
                            WHERE
                                id_userinstance = %d
                                and inresearch = 't'",
        $id_userinstance);
        $common->executeQuery ($sql6);
        if($common->errormsg)
        {
            $common->executeQuery ( 'rollback' );
            echo "can't end science event6  ";
            return false;
        }

        $common->executeQuery ('commit');
        return true;
    }

    
    function getCurLevel($id_userinstance, $id_branch)
    {
        global $common;
        $sql = sprintf("SELECT
                            max(s.level) as cur_level
                        FROM
                            ds_userhasscience uhs,
                            ds_science s
                        WHERE
                            uhs.id_userinstance = %d
                            and uhs.id_science = s.id_science
                            and s.id_branch = %d",                                      
                        $id_userinstance,
                        $id_branch);
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        if($common->errormsg)
        {
            echo $sql;
            die("<br>can't get current science level<br>");
        }

        if(count($res) == 0)
        {
            return 0;
        }

        return $res[0]['cur_level'];
    }

    function isUserHasScience($id_userinstance, $level, $id_branch)
    {
        global $common;
        $sql = sprintf("SELECT *
                            FROM
                                ds_userhasscience uhs,
                                ds_science s
                            WHERE
                                uhs.id_userinstance = %d
                                and uhs.id_science = s.id_science
                                and s.level = %d
                                and s.id_branch = %d",                                      
        $id_userinstance,
        $level,
        $id_branch);

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        if($common->errormsg)
        {
            echo $sql;
            die("<br>can't check have user science or no<br>");
        }

        if(sizeof($res)>0)
        {
            return true;
        }

        return false;
    }

    function delScience($id_userinstance,$id_branch,$level)
    {
        global $common;

        $curLevel = $this->getCurLevel($id_userinstance, $id_branch);
        
        $common->executeQuery('begin');

        $sql = sprintf("DELETE FROM
                                    ds_userhasability
                                WHERE
                                    id_userinstance = %d
                                AND id_ability IN
                                (
                                    SELECT
                                        sha.id_ability
                                    FROM
                                        ds_sciencehasability sha,
                                        ds_science s
                                    WHERE
                                        s.id_branch = %d
                                        AND s.level > %d
                                        AND s.id_science = sha.id_science
                                )",                                     
        $id_userinstance,
        $id_branch,
        $level);

        $res = $common->executeQuery($sql);
        if($common->errormsg)
        {
            $common->executeQuery('rollback');
            echo $sql;
            die("<br>can't delete science from user 1<br>");
        }

        $sql = sprintf("DELETE FROM
                                ds_userhasscience
                            WHERE
                                id_userinstance = %d
                                and id_science IN
                                (
                                    SELECT
                                            s.id_science
                                        FROM
                                            ds_science s
                                        WHERE
                                            s.level > %d
                                            AND s.id_branch = %d
                                )",                                     
        $id_userinstance,
        $level,
        $id_branch);

        $res = $common->executeQuery($sql);
        if($common->errormsg)
        {
            $common->executeQuery('rollback');
            echo $sql;
            die("<br>can't delete science from user 2<br>");
        }

        $common->executeQuery('commit');

        //Получаем имя науки
        $sql = "SELECT
                        branchname
                    from
                        ds_sciencebranch
                    where
                        id_branch ='$id_branch'
                    ";
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );

        if ($common->errormsg)
        {
            echo "<h2><font color=red>Can't normal log thise action</font></h2>";
        }
        $scienceName = $res[0]['branchname'];
        
        $detail = "Set level from '$curLevel' to '$level' of science '$scienceName'<br>";

        //Получаем имя мира по его id
        $sql = "SELECT
                        worldname
                        FROM ds_world 
                        WHERE id_world = $this->worldId";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        $worldName = $res[0]['worldname'];
        
        $str = "Changing science level to User:'".$this->username."' in World:'".$worldName."'<br>\nComment:.'".$this->comment."'";

        actionLog($str, $detail);
        
        return true;
    }

    function addScience($id_userinstance,$level,$id_branch)
    {
        global $common;
        
        $curLevel = $this->getCurLevel($id_userinstance, $id_branch);
        
        $sql = sprintf("SELECT
                                id_science                                
                        FROM
                             ds_science                             
                        WHERE
                            id_branch = %d and
                            level <= %d and
                            id_science not in
                            (
                                SELECT
                                    id_science
                                FROM
                                    ds_userhasscience
                                WHERE
                                    id_userinstance = %d
                            )
                            ",
        $id_branch,
        $level,
        $id_userinstance);

        $sciences = $common->getResultArray ( "", $sql, "", "", "" );

        if ( $sciences === false || sizeof($sciences) === 0)
        {
            die("can't get sciences to add");
        }

        foreach ($sciences as $k=>$v)
        {
            $common->executeQuery('begin');
            $sql = sprintf("INSERT INTO
                                        ds_userhasscience
                                        (id_science,id_userinstance,inresearch,starttime,endtime)
                                VALUES
                                        (%d,%d,'f',now(),now())",
            $v['id_science'],
            $id_userinstance);
            $res = $common->executeQuery ( $sql );
            if(!$res)
            {
                $common->executeQuery('rollback');
                echo $sql;
                die("can't add science with id =  ".$v['id_science']);
            }

            $sql = sprintf("insert into
                                        ds_userhasability 
                                        (id_userinstance, id_ability )
                                        select 
                                            %d, id_ability 
                                        from 
                                            ds_sciencehasability
                                        where 
                                            id_science = %d",                                       
            $id_userinstance,
            $v['id_science']);

            $res = $common->executeQuery($sql);
            if(!$res)
            {
                $common->executeQuery('rollback');
                echo $sql;
                die("can't add science2 with id =  ".$v['id_science']);
            }

            $common->executeQuery('commit');

            //Получаем имя науки
            $sql = "SELECT
                        branchname
                    from
                        ds_sciencebranch
                    where
                        id_branch ='$id_branch'
                    ";
            
            $res = $common->getResultArray ( "", $sql, "", "", "" );

            if ($common->errormsg)
            {
                echo "<h2><font color=red>Can't normal log thise action</font></h2>";
            }
            $scienceName = $res[0]['branchname'];

            $detail = "Set level from '$curLevel' to '$level' of science '$scienceName'<br>";

        }

        //Получаем имя мира по его id
        $sql = "SELECT
                        worldname
                        FROM ds_world 
                        WHERE id_world = $this->worldId";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        $worldName = $res[0]['worldname'];
        
        $str = "Changing science level to User:'".$this->username."' in World:'".$worldName."'<br>\nComment:.'".$this->comment."'";

        actionLog($str, $detail);

        return true;
    }

}

$science = new Science();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
