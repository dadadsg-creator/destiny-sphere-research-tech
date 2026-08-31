<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

if ( !isset ( $GLOBALS["common"] ) ) {
    $common = & ClassFactory::getInstance ( "DS_Common" );
    $common->connect();
}

class ChangeLevel {

    private $dbId;

    private $wid;

    private $username = "";

    private $errormsg = "";
    
    private $league = "";
    
    private $level = "";
    
    private $bo = "";
    
    private $absolute_bo = "";
    
    private $weight_built = "";
    
    private $weight_lost = "";

    function ChangeLevel( ) {
        $this->getRequest ( $_POST );
    }

    function getRequest ($request)
    {
        if (key_exists('username', $request)) {
            $this->username = $request['username'];
        }

        if (key_exists('id_world', $request)) {
            $wid = explode ('_', $request['id_world']);
            $this->wid = (int)$wid[0];
            $this->dbId = (int)$wid[1];

            assert($this->wid > 0);
            assert($this->dbId >= 0);
        }
        
        if (key_exists('league', $request)) {
            $this->league = (int)$request['league'];
        }
        
        if (key_exists('level', $request)) {
            $this->level = (int)$request['level'];
        }
        
        if (key_exists('bo', $request)) {
            $this->bo = (float)$request['bo'];
        }
        
        if (key_exists('absolute_bo', $request)) {
            $this->absolute_bo = (float)$request['absolute_bo'];
        }

        if (key_exists('npc_xp', $request)) {
            $this->npc_xp = (float)$request['npc_xp'];
        }
        
        if (key_exists('weight_built', $request)) {
            $this->weight_built = (float)$request['weight_built'];
        }
        
        if (key_exists('weight_lost', $request)) {
            $this->weight_lost = (float)$request['weight_lost'];
        }

        $this->showFirstMenu($this->errormsg);

        if (isset($request['showSecondMenu']))
        {
            $this->showSecondMenu();
        } else {
            if (isset($request['changelevel']))
            {
                $this->change();
                $this->showSecondMenu();
            }
        }
        return true;
    }

    function showFirstMenu ( $str = "" )
    {
        global $common;

        if ( $this->errormsg  ) {
            // Выводим ошибку
            print ShowCMSMessage($this->errormsg, 2);
        }

        $sql = sprintf ( "select id_world, worldname from ds_world" );
        $res = $common->getMultySql ($sql);

        if ( !$res ) {
            $this->errormsg = "DeleteBuildings::showFirstMenu !!! ".$sql;
            return false;
        }

        for ( $i=0; $i<sizeof ( $res ); $i++ ) {
            $this->worlds_list[$res[$i]['id_world']."_".$res[$i]['dbId']] = $res[$i]['worldname'];
        }

        echo Forms::openForm('',$_SERVER['PHP_SELF'], 'post');
        echo Forms::hidden('showSecondMenu',1);
        print $str."
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=4>
            <tr>
                <td bgcolor='444E6C' class=th_2>
                    ".local( 'DS_USER_WORLD' )."</td>
                <td bgcolor='444E6C' class=th_2>
                    ".local( 'DS_USER_USERNAME' )."</td>
            </tr>
            <tr>
                <td bgcolor='444E6C'>";

        echo Forms::selectBox($this->worlds_list, $selected=$this->wid."_".$this->dbId, $name='id_world', $size=1, $multiple=false, $additional='');
        echo "  </td>
            
                <td bgcolor='444E6C' valign=top>";
        echo Forms::textField($value = $this->username, $name = 'username', $hidden = false );
        echo "</td>
            </tr>
            <tr>
                <td colspan=2 bgcolor='444E6C' class=maintxt>";
        echo Forms::hidden('unique',1);
        //      echo "&nbsp;";
        /*      echo Forms::checkBox(1,'unique',true, 'class=check').local('DS_SEARCH_ONLY_UNIQUE');*/
        /*Чек-бокс не включать, пока не будет добавлен в скрипт пересчет бонусов при удалении не */
        /*уникальных зданий*/
        echo "</td>
            </tr>
        </table></td></tr></table>";
        echo Forms::submitButton(local( 'DS_ACTION_SEARCH' ),$name = 'next', '','',' class = submit');
        echo Forms::closeForm();
        return true;
    }   //Это запчасть от первой фрмы




    function showSecondMenu () {
        global $common;

        assert($this->dbId >= 0);
        assert($this->wid > 0);

        $_SESSION['dbId'] = $this->dbId;
        $common->setDbId( $this->dbId );

        // Проверяем ввденые данные о игроке
        if (!VD_UserName::Validate($this->username)) {
            ShowCMSMessage("Wrong UserName!", 2);
            return false;
        }

        // Смотрим, есть ли такой игрок
        $sql = "
                    SELECT
                        *
                    FROM
                        ds_user u
                    WHERE
                         u.username = '".$this->username."'";

        $userInfo = $common->getResultArray ("", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if ( sizeof($userInfo) == 0 ) {
            //Игрока с таким именем нет
            print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);
            return false;
        }

        // Смотрим, зареган ли игрок в мире
        assert(sizeof($userInfo) == 1);

        //Смотрим, в каких мирах зареган игрок
        $sql = "
                    SELECT
                        ui.id_userinstance
                    FROM
                        ds_user u
                        LEFT JOIN ds_userinstance ui ON (u.id_user = ui.id_user)                        
                    WHERE
                         u.username = '".$this->username."'
                        AND
                         ui.id_world = ".$this->wid;
        ;
        $userInstance = $common->getMultySql ( $sql );
        assert(empty($common->errormsg));

        if ( sizeof($userInstance) == 0 ) {
            //Игрока не зареган в этом мире
            print ShowCMSMessage('USER NOT REGISTERED IN WORLD', 2);
            return false;
        }

        // Выбираем данные игрока
        $sql = "
            SELECT
               ui.id_userinstance,
               ui.id_league,
               r.id_level,
               be.value,
               be.absolute_value,
               be.npc_xp,
               aw.army_weight_built,
               aw.army_weight_lost
            FROM
                ds_user u
            LEFT JOIN ds_userinstance ui USING (id_user)
            LEFT JOIN ds_ranking r ON (r.id_userinstance = ui.id_userinstance)
            LEFT JOIN ds_battlexp be ON (be.id_userinstance = ui.id_userinstance)
            LEFT JOIN ds_army_weight aw ON (aw.id_userinstance = ui.id_userinstance)
            WHERE
                 u.username = '".$this->username."'
                 AND 
                 ui.id_world = ".$this->wid;

        $res = $common->getQueryArray ( $sql );
        assert(empty($common->errormsg));
        
        
        echo Forms::openForm('',$_SERVER['PHP_SELF'], 'post');
        echo Forms::hidden('changelevel', 1);

        echo "
        
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
        <tr>
        <td>
        <table width=100% border=0 cellspacing=1 cellpadding=4>
            <tr>
                <td bgcolor='444E6C' class=th_2>
                    Лига
                </td>
                <td bgcolor='444E6C' class=th_2>
                    Уровень
                </td>
                <td bgcolor='444E6C' class=th_2>
                    Военные очки
                </td>
                <td bgcolor='444E6C' class=th_2>
                    Боевой опыт
                </td>
                <td bgcolor='444E6C' class=th_2>
                    NPC опыт
                </td>
                <td bgcolor='444E6C' class=th_2>
                    Построенный вес
                </td>
                <td bgcolor='444E6C' class=th_2>
                    Потерянный вес
                </td>
            </tr>
            <tr>
                <td bgcolor='444E6C'>
                    <input type='text' name='league' value='".$res[0]['id_league']."' size='4'>
                </td>
                <td bgcolor='444E6C' valign=top>
                    <input type='text' name='level' value='".$res[0]['id_level']."' size='4'>
                </td>
                <td bgcolor='444E6C' valign=top>
                    <input type='text' name='bo' value='".$res[0]['value']."'>
                </td>
                <td bgcolor='444E6C' valign=top>
                    <input type='text' name='absolute_bo' value='".$res[0]['absolute_value']."'>
                </td>
                <td bgcolor='444E6C' valign=top>
                    <input type='text' name='npc_xp' value='".$res[0]['npc_xp']."'>
                </td>
                <td bgcolor='444E6C' valign=top>
                    <input type='text' name='weight_built' value='".$res[0]['army_weight_built']."'>
                </td>
                <td bgcolor='444E6C' valign=top>
                    <input type='text' name='weight_lost' value='".$res[0]['army_weight_lost']."'>
                </td>
            </tr>
            <tr>
                <td colspan=7 bgcolor='444E6C' class=maintxt>
                    <input type='hidden' name='unique' value='1'>
                </td>
            </tr>
        </table>
        </td>
        </tr>
        </table>";

        echo Forms::hidden('id_world', $this->wid."_".$this->dbId);
        echo Forms::hidden('username', $this->username);
        echo Forms::submitButton(' Change ', $name = 'next', '','',' class = submit');
        echo Forms::closeForm();

        return true;
    }

    function change () {
        global $common;

        assert($this->dbId >= 0);
        assert($this->wid > 0);

        $_SESSION['dbId'] = $this->dbId;
        $common->setDbId($this->dbId);

        // Проверяем ввденые данные о игроке
        if (!VD_UserName::Validate($this->username)) {
            ShowCMSMessage("Wrong UserName!", 2);
            return false;
        }

        // Выбираем данные игрока
        $sql = "
            SELECT
               ui.id_userinstance,
               ui.id_league,
               r.id_level,
               be.value,
               be.absolute_value,
               aw.army_weight_built,
               aw.army_weight_lost
            FROM
                ds_user u
            LEFT JOIN ds_userinstance ui USING (id_user)
            LEFT JOIN ds_ranking r ON (r.id_userinstance = ui.id_userinstance)
            LEFT JOIN ds_battlexp be ON (be.id_userinstance = ui.id_userinstance)
            LEFT JOIN ds_army_weight aw ON (aw.id_userinstance = ui.id_userinstance)
            WHERE
                 u.username = '".$this->username."'
                 AND 
                 ui.id_world = ".$this->wid;

        $userInfo = $common->getQueryArray ( $sql );
        assert(empty($common->errormsg));
        
        if ($userInfo[0]['id_level'] != $this->level) {
            // Смотрим, существует ли в игре такой уровень 
            $sql = "
            SELECT
                id_level
            FROM
                ds_ranking_level
            WHERE
                 id_level = ".$this->level;

            $level = $common->getQueryArray ( $sql );
            assert(empty($common->errormsg));
            
            if (count($level) == 0) {
                echo  ShowCMSMessage('Wrong Level '.$this->level.'!', 2);
                return false;
            }
            
            // Меняем уровень
            $sql = "
            UPDATE 
                ds_ranking 
            SET 
                id_level = ".$this->level."
            WHERE
                id_userinstance = 
                    (SELECT 
                        ui.id_userinstance
                    FROM
                        ds_userinstance ui
                    LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
                    WHERE 
                        u.username = '".$this->username."'
                        AND
                        ui.id_world = ".$this->wid.")
            ";

            $res = $common->getQueryArray ( $sql );
            assert(empty($common->errormsg));
            
                    
            actionLog('Change level for user '.$this->username.' from '.$userInfo[0]['id_level'].' to '.$this->level);
        }
        
        if ($userInfo[0]['id_league'] != $this->league) {
            // Смотрим, существует ли в игре такая лига 
            $sql = "
            SELECT
                id_league
            FROM
                ds_league
            WHERE
                 id_league = ".$this->league;

            $league = $common->getQueryArray ( $sql );
            assert(empty($common->errormsg));
            
            if (count($league) == 0) {
                echo  ShowCMSMessage('Wrong League '.$this->league.'!', 2);
                return false;
            }
            
            // Меняем уровень
            $sql = "
            UPDATE 
                ds_userinstance 
            SET 
                id_league = ".$this->league."
            WHERE
                id_userinstance = 
                    (SELECT 
                        ui.id_userinstance
                    FROM
                        ds_userinstance ui
                    LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
                    WHERE 
                        u.username = '".$this->username."'
                        AND
                        ui.id_world = ".$this->wid.")
            ";

            $res = $common->getQueryArray ( $sql );
            assert(empty($common->errormsg));
            
                    
            actionLog('Change league for user '.$this->username.' from '.$userInfo[0]['id_league'].' to '.$this->league);
        }
        
        if ($userInfo[0]['value'] != $this->bo ||
            $userInfo[0]['absolute_value'] != $this->absolute_bo ||
            $userInfo[0]['npc_xp'] != $this->npc_xp) {
            
            if ($this->bo < 0) {
                echo 'Военные очки меньше нуля!';
                exit;
            }

            if ($this->absolute_bo < 0) {
                echo 'Боевой опыт меньше нуля!';
                exit;
            }

            if ($this->npc_xp < 0) {
                echo 'NPC XP меньше нуля!';
                exit;
            }

            $sql = "SELECT 
                        ui.id_userinstance
                    FROM
                        ds_userinstance ui
                    LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
                    WHERE 
                        u.username = '".$this->username."'
                        AND
                        ui.id_world = ".$this->wid;
            $res = $common->getQueryArray($sql);
            assert(empty($common->errormsg));
            $uiid = $res[0]['id_userinstance'];

            $sql = "select * from ds_battlexp where id_userinstance=$uiid";
            $res = $common->getQueryArray($sql);
            assert(empty($common->errormsg));
            if (count($res)) {
                $sql = "UPDATE ds_battlexp 
                    SET value = ".$this->bo.", absolute_value = ".$this->absolute_bo.", npc_xp= ".$this->npc_xp."
                WHERE id_userinstance = $uiid";
            } else {
                $sql = "insert into ds_battlexp (id_userinstance, value, absolute_value, npc_xp)
                    values ($uiid, ".$this->bo.", ".$this->absolute_bo.", ".$this->npc_xp.")";
            }
            $res = $common->getQueryArray ( $sql);
            assert(empty($common->errormsg));
                    
            actionLog('Change battle score for user '.$this->username.' from '.$userInfo[0]['value'].' to '.$this->bo);
            actionLog('Change battle exp for user '.$this->username.' from '.$userInfo[0]['absolute_value'].' to '.$this->absolute_bo);
        }
        
        if ($userInfo[0]['army_weight_built'] != $this->weight_built) {
            
            if ($this->weight_built < 0) {
                echo 'Построенный вес меньше нуля!';
                exit;
            }
            
            // Апдейтим построенный вес
            $sql = "
            UPDATE 
                ds_army_weight 
            SET 
                army_weight_built = ".$this->weight_built."
            WHERE
                id_userinstance = 
                    (SELECT 
                        ui.id_userinstance
                    FROM
                        ds_userinstance ui
                    LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
                    WHERE 
                        u.username = '".$this->username."'
                        AND
                        ui.id_world = ".$this->wid.")
            ";

            $res = $common->getQueryArray ( $sql );
            assert(empty($common->errormsg));
            
                    
            actionLog('Change army weight built for user '.$this->username.' from '.$userInfo[0]['army_weight_built'].' to '.$this->weight_built);
        }
        
        if ($userInfo[0]['army_weight_lost'] != $this->weight_lost) {
            
            if ($this->weight_lost < 0) {
                echo 'Потерянный вест меньше нуля!';
                exit;
            }
            
            // Апдейтим потерянный вес
            $sql = "
            UPDATE 
                ds_army_weight 
            SET 
                army_weight_lost = ".$this->weight_lost."
            WHERE
                id_userinstance = 
                    (SELECT 
                        ui.id_userinstance
                    FROM
                        ds_userinstance ui
                    LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
                    WHERE 
                        u.username = '".$this->username."'
                        AND
                        ui.id_world = ".$this->wid.")
            ";

            $res = $common->getQueryArray ( $sql );
            assert(empty($common->errormsg));
            
                    
            actionLog('Change army weight lost for user '.$this->username.' from '.$userInfo[0]['army_weight_lost'].' to '.$this->weight_lost);
        }

        echo  ShowCMSMessage('OK', 1);

        return true;
    } //END BuildDelete
}

$ChangeLevel = new ChangeLevel();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>