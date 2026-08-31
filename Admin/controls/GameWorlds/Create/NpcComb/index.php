<?php
ini_set('memory_limit', '16M');
set_time_limit(0);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
//require_once $_SERVER['DOCUMENT_ROOT']."/ds/classes/VD/VD_UserName.class.php";


class TurnirCreateTemplate
{
    private $action = "showFirstMenu";

    // Id базы, в которой находиться шаблон соты
    private $dbId;

    // Id мира, в котором находиться шаблон соты
    private $worldId;

    // Id базы, в которой создаем колониии
    private $turnirDbId;

    // Id мира, в котором создаем колониии
    private $turnirWorldId;

    // Название мира, в котором создаем колониии
    private $turnirWorldName;

    // Количество сот, котрые надо создать
    private $combAmount;

    // Ник юзверя-образца
    private $username = "";

    // Шаблон названия новых сот
    private $combName = "";
    
    // npc - игрок, на которого будут записаны новые соты 
    private $npcPlayer = "";

    private $common;

    private $UIID;

    // Расса (кольцо)
    private $race = 1;
    
    // Все кольца
    private $rings = array( 
                    1 => "Ворания",
                    2 => "Лиенсорд",
                    3 => "Псолеон",
                        );


    function __construct($data)
    {
        $this->common = DS_Common::CreateForCurentDB(0);
        $this->getRequest ($data);

        switch ($this->action)
        {
            case 'showSecondMenu':
                if (!$this->showSecondMenu()) {
                    $this->showFirstMenu();
                }
                break;

            case 'applayTemplate':
                $this->applayTemplate();
                break;
            default:
                $this->showFirstMenu();
                break;
        }
    }

    function showFirstMenu()
    {
        echo "<h1>Выберите соту образец:</h1>";
        $sql = sprintf ( "select id_world, worldname from ds_world" );
        $res = $this->common->getMultySql ($sql);

        if ( !$res ) {
            $this->errormsg = "DeleteBuildings::showFirstMenu !!! ".$sql;
            return false;
        }

        echo '
        <form action="" method="GET">
        <input type="hidden" name="action" value="showSecondMenu">
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=4>
                        <tr>
                            <td bgcolor="444E6C" class=th_2>
                                Мир</td>
                            <td bgcolor="444E6C" class=th_2>
                                Имя пользователя</td>
                        </tr>
                        <tr>
                            <td bgcolor="444E6C"><select name="world" size="1">';
        foreach ($res as $key => $val) {
            if (!empty($this->worldId) && $val['dbId']."_".$val['id_world'] == $this->dbId."_".$this->worldId) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            echo "
                                <option value=".$val['dbId']."_".$val['id_world']." $selected>".$val['worldname']."</option>";

        }

        echo '
                                </select>
                            </td>
            
                            <td bgcolor="444E6C" valign=top>
                            <input name="username" type="text" value="'.$this->username.'"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        ';


        echo "<h1>Выберите мир для добавления колоний<br>и введите количество колоний, которые хотите создать:</h1>";
        
        

        echo '
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
            <tr>
                <td>
                    <table width=100% border=0 cellspacing=1 cellpadding=4>
                        <tr>
                            <td bgcolor="444E6C" class=th_2>
                                Мир
                            </td>
                            <td bgcolor="444E6C" class=th_2>
                                Шаблон ника Npc игрока
                            </td>
                            <td bgcolor="444E6C" class=th_2>
                                Шаблон названия соты
                            </td>
                            <td bgcolor="444E6C" class=th_2>
                                Кол-во
                            </td>
                            <td bgcolor="444E6C" class=th_2>
                                Кольцо
                            </td>
                        </tr>
                        <tr>
                            <td bgcolor="444E6C">
                                <select name="turnir_world" size="1">';
            foreach ($res as $key => $val) {
            if (!empty($this->worldId) && $val['dbId']."_".$val['id_world'] == $this->turnirDbId."_".$this->turnirWorldId) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            echo "
                                <option value=".$val['dbId']."_".$val['id_world']."_".urlencode($val['worldname'])." $selected>".$val['worldname']."</option>";
        }

        echo '
                                </select>
                            </td>
                            <td bgcolor="444E6C" valign=top>
                                <input name="npc_player" type="text" value="'.$this->npcPlayer.'">
                            </td>
                            <td bgcolor="444E6C" valign=top>
                                <input name="comb_name" type="text" value="'.$this->combName.'">
                            </td>
                            <td bgcolor="444E6C" valign=top>
                                <input name="amount_comb" size="3" type="text" value="'.$this->combAmount.'">
                            </td>
                            <td bgcolor="444E6C" valign=top>
                                <select name="race" size="1">
                                ';
        foreach ($this->rings as $ringId => $ringName) {
            if ($this->race == $ringId) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            echo "
                                <option value=".$ringId." $selected>".$ringName."</option>";
                            }
        
        echo '
                                </select>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table><br>
        <input type="Submit" name="next" value="&nbsp;&nbsp;Искать&nbsp;&nbsp;" class = submit></form>';

    }

    function showSecondMenu () {

        assert($this->dbId >= 0);
        assert($this->worldId > 0);
        assert($this->turnirDbId >= 0);
        assert($this->turnirWorldId > 0);
        
        if (empty($this->npcPlayer)) {
            print ShowCMSMessage('Empty npc player', 2);
            return false;
        }

        // Проверяем валидность имени шаблона
        if (!VD_UserName::Validate($this->username)) {
            print ShowCMSMessage('Невалидный ник: '.VD_UserName::FetchErrorCode(), 2);
            return false;
        }
        
        // Смотрим, есть ли игрок, по которому хотим делать образец
        $sql = "
                    SELECT
                        *
                    FROM
                        ds_user u
                    WHERE
                         u.username = '".$this->username."'";

        $userInfo = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg));

        if ( sizeof($userInfo) == 0 ) {
            //Игрока с таким именем нет
            print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);
            return false;
        }

        if ( $this->combAmount <= 0 || $this->combAmount > 100 ) {
            print ShowCMSMessage('Неверное количество сот', 2);
            return false;
        }

        $this->common->setDbId($this->dbId);
        $_SESSION["dbId"] = $this->dbId;

        // Выбираем иформацию о сотах игрока в этом мире
        $sql = "
                    SELECT
                      
                        cn.combname,
                        ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
                        wo.worldname,
                        ui.id_userinstance,
                        ri.ringnumber
                    FROM
                        ds_user u
                        left outer join ds_userinstance ui ON (u.id_user = ui.id_user)
                        left outer join ds_world wo ON (wo.id_world = ui.id_world)
                        left outer join ds_comb co ON (co.id_userinstance = ui.id_userinstance)
                        LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
                        LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                        left outer join ds_combname cn ON (co.id_comb = cn.id_comb)
                       WHERE
                         u.username = '".$this->username."'
                        AND
                         wo.id_world = ".$this->worldId;
        ;
        $userInstance = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg));

    
        if ( count($userInstance) == 0 ) {
            //Игрока не зареган в этом мире
            print ShowCMSMessage('USER NOT REGISTERED IN THIS WORLD', 2);
            return false;
        }
        if ( empty($this->combName) ) {
            //Не ввели название соты
            print ShowCMSMessage('Empty Comb Name', 2);
            return false;
        }

        if ( count($userInstance) > 1 ) {
            //Игрока не зареган в этом мире
            print ShowCMSMessage('TEMPLATE CAN CONSIST ONLY ONE COMB', 2);
            return false;
        }

        // Проверяем валидность названия соты, пока без совпадения имен,
        //так как не сообразить как бы это сделать, с учетом того, что мы иогли уже создавать соты
        //на основании этого шаблона
        $this->common->setDbId($this->turnirDbId);
        $_SESSION["dbId"] = $this->turnirDbId;
        if(!VD_CombName::Validate($this->combName, false, $this->common))
        {
            print ShowCMSMessage(VD_CombName::FetchErrorCode(), 2);
            return false;
        }
        
        // Проверяем валидность имени
        if (!VD_UserName::Validate($this->npcPlayer)) {
            print ShowCMSMessage('Невалидный ник npc-игрока: '.VD_UserName::FetchErrorCode(), 2);
            return false;
        }
            
        /*// Смотрим, существует ли npc-игрок
        $sql = "
                    SELECT
                        *
                    FROM
                        ds_user u
                    WHERE
                         u.username = '".$this->npcPlayer."'";

        $npcUserInfo = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg));

        if ( !empty($npcUserInfo) && $npcUserInfo[0]['npc'] != 't') {
            print ShowCMSMessage('Введенный вами Npc игрок не является NPC ', 2);
            return false;
        }*/
        
        echo '<h1>Выбранный шаблон соты:</h1>';

        //Выводим инфу о игроке
        echo
        '<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <tr>
                    <td>
                        <table width=100% border=0 cellspacing=1 cellpadding=3>
                            <tr>
                                <th class=th_2>
                                    Мир
                                </th>
                                <th class=th_2>
                                    Соты
                                </th>
                            </tr>
                            <tr>
                                <td align="center" class=maintxt_med bgcolor=#444E6C>
                                    '.$userInstance['0']['worldname'].'
                                </td>
                                        <td align="center" class=maintxt_med bgcolor=#444E6C>';
        //Проходимся по каждой соте
        foreach ($userInstance as $key => $comb)
        {
            echo $comb['combname']." (".$comb['coords'].")<br>";
        }

        echo '
                                    </tr>   
                            </table>
                    </td>
                </tr>
            </table>';

        echo '<h1>Выбранный мир: '.$this->turnirWorldName.'</h1>';
        echo '<h1>Количество сот: '.$this->combAmount.'</h1>';
        echo '<h1>Шаблон названия соты: '.$this->combName.'</h1>';
        echo '<h1>Кольцо: '.$this->rings[$this->race].'</h1>';
        echo '<h1>Шаблон ника Npc игрока: '.$this->npcPlayer.'</h1>';
        /*if ( !empty($npcUserInfo)) {
            echo '<h1> Npc-игрок: '.$this->npcPlayer.'</h1>';
        } else {
            echo '<h1> Будет создан Npc-игрок с ником: '.$this->npcPlayer.' (он будет принадлежать к 1му уровню и 1й лиге)</h1>';
        }*/

        echo '
        <form action="" method="GET">
        <input type="hidden" name="action" value="applayTemplate">
        <input type="hidden" name="world" value="'.$this->dbId.'_'.$this->worldId.'">
        <input type="hidden" name="turnir_world" value="'.$this->turnirDbId.'_'.$this->turnirWorldId.'_'.$this->turnirWorldName.'">
        <input type="hidden" name="username" value="'.$this->username.'">
        <input type="hidden" name="UIID" value="'.$userInstance['0']['id_userinstance'].'">
        <input type="hidden" name="race" value="'.$this->race.'">
        <input type="hidden" name="amount_comb" value="'.$this->combAmount.'">
        <input type="hidden" name="comb_name" value="'.$this->combName.'">
        <input type="hidden" name="npc_player" value="'.$this->npcPlayer.'">
        <input type="Submit" name="next" value="Создать"  class = submit>
        </form>';
        return true;
    }

    function applayTemplate () {
        assert($this->dbId >= 0);
        assert($this->worldId > 0);
        assert($this->turnirDbId >= 0);
        assert($this->turnirWorldId > 0);
        //assert($this->UIID > 0);
        assert($this->race > 0 && $this->race < 4);
        assert($this->combAmount > 0 && $this->combAmount <= 100);
        assert(!empty($this->combName));
        assert(!empty($this->npcPlayer));

        // Еще раз проверка на валидность имени
        assert(VD_UserName::Validate($this->npcPlayer));

        
        /*// Смотрим, есть ли у npc-игрока инстанс в новом мире
        $this->common->setDbId($this->turnirDbId);
        $_SESSION["dbId"] = $this->turnirDbId;
        
        $sql = "
                    SELECT
                          ui.id_userinstance
                    FROM
                        ds_user u
                    LEFT JOIN   
                         ds_userinstance ui USING (id_user)
                    WHERE
                         u.username = '".$this->npcPlayer."'
                         AND 
                        ui.id_world = ".$this->turnirWorldId;

        $npcUserInstance = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg));*/
        
        $this->common->setDbId($this->dbId);
        $_SESSION["dbId"] = $this->dbId;

        // Выбираем Id соты-образца
        $sql = "
                    SELECT
                        co.id_comb
                    FROM
                        ds_user u
                        left outer join ds_userinstance ui ON (u.id_user = ui.id_user)
                        left outer join ds_world wo ON (wo.id_world = ui.id_world)
                        left outer join ds_comb co ON (co.id_userinstance = ui.id_userinstance)
                       WHERE
                         u.username = '".$this->username."'
                        AND
                         wo.id_world = ".$this->worldId;
        $res = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg) && count($res) == 1);
        $oldCombId = (int)$res[0]['id_comb'];
        assert($oldCombId > 0);
        
        echo '<h1>Start create comb...</h1>';

        $this->common->setDbId($this->turnirDbId);
        $_SESSION["dbId"] = $this->turnirDbId;
        $_SESSION['world'] = $this->turnirWorldId;

        // Смотрим шаблон названия соты
        $sql = "SELECT
                    * 
                FROM
                    ds_combname
                WHERE 
                    combname ~ '^".$this->combName."[0-9]+'
                ORDER BY combname DESC";        
        $res = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg));
        
        /*
        $sql = "SELECT
                    combname 
                FROM
                    ds_combname
                WHERE 
                    combname ~ '^".$this->combName."[0-9]+'
                ORDER BY CAST(substring(combname, ".(strlen($this->combName) + 1).") as int) DESC";     */

        // Если мы уже использовали данный шаблон для создания сот
        if (count($res > 0)) {
            // Определяем следующую цифру в названии
            $nameArr = str_replace($this->combName, "", $res[0]['combname']);

            $combNameNumber = (int)$nameArr + 1;
        } else {
            $combNameNumber = 0;
        }
        
        // Смотрим шаблон ника npc юзверя
        $sql = "SELECT
                    username
                FROM
                    ds_user
                WHERE 
                    username ~ '^".$this->npcPlayer."[0-9]+'
                ORDER BY CAST(substring(username, ".(strlen($this->npcPlayer) + 1).") as int) DESC";        
        $res = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg));

        // Если мы уже использовали данный шаблон для создания сот
        if (count($res > 0)) {
            // Определяем следующую цифру в названии
            $nameArr = str_replace($this->npcPlayer, "", $res[0]['username']);

            $playerNameNumber = (int)$nameArr + 1;
        } else {
            $playerNameNumber = 0;
        }

        echo "<pre>";
        $combNames = "";

        for ($i = 0; $i < $this->combAmount; $i ++)
        {
            $userName = $this->npcPlayer.($playerNameNumber + $i);
            
            // Создаем игрока
            echo '<h1>Сreate user '.$this->npcPlayer.'...</h1>';

            $this->common->setDbId(0);
            $_SESSION["dbId"] = 0;
        
            $sql = "select nextval('ds_user_id_user_seq') as id";
            $res = $this->common->getResultArray("", $sql, "", "", "");
            assert(empty($this->common->errormsg) && count($res) > 0);

            $UID = (int)$res[0]['id'];
            assert($UID > 0);
            
   
            $sql = "
                    INSERT INTO ds_user
                        (
                            id_user,    
                            id_language,
                            username,
                            password,
                            email,
                            registrationdate,
                            lastrefresh,
                            vacationmode,
                            accountactive,
                            admin,
                            time_delta,
                            activate_mail,
                            from_partner,
                            reg_referer,
                            ip_addr, 
                            npc
                        )
                    VALUES
                        (
                            $UID,
                            ".constant('DEFAULT_LANGUAGE') .",
                            '".$userName."',
                            '".md5('gjfhdyddFD5')."',
                            'empty".$userName."540',
                            'now()',
                            'now()',
                            true,
                            true,
                            false,
                            ".constant("DEFAULT_TIMEDELTA").",
                            1,
                            null, 
                            null,  
                            '1.1.1.1',
                            true
                        )
                    ";
            $this->common->executeQuery($sql);
            assert(empty($this->common->errormsg)); 

            sleep(5);
            
            $this->common->setDbId($this->turnirDbId);
            $_SESSION["dbId"] = $this->turnirDbId;  
            
            
            // Создаем инстанс
            echo '<h1>Сreate user instance...</h1>';
            
            $sql = "select nextval('ds_userinstance_id_userinstance_seq') as id";
            $res = $this->common->getResultArray("", $sql, "", "", "");
            assert(empty($this->common->errormsg) && count($res) > 0);

            $UIID = (int)$res[0]['id'];
            assert($UIID > 0);
            
            // создаем userinstance
            $sql = "
                    INSERT INTO
                    ds_userinstance
                    (id_userinstance, id_user, id_race, id_world, lastrefresh, window_key, newb_defend_enddate, id_league)
                    VALUES 
                    ($UIID, $UID, ".$this->race.", ".$this->turnirWorldId.", now(), 0, now(), 1)";
            $this->common->executeQuery ( $sql );
            assert(empty($this->common->errormsg));
             
             // создаем реийтинг
            $sql = "INSERT INTO 
                    ds_ranking
                    (id_userinstance, id_level)
                    VALUES
                    ($UIID, 1)";
            $this->common->executeQuery ( $sql );
            assert(empty($this->common->errormsg));
            
            
            $combName = $this->combName.($combNameNumber + $i);

            // Координыты новой соты
            SI_ExecStoreUser::SetCommon($this->common);
            $combId = GL_GetCombForPlacement::Get($this->race, $this->turnirWorldId, array(), 1);   
            if ($combId == 0) {
                print ShowCMSMessage('На кольце "'.$this->rings['$this->race'].'" нет свободных мест!', 2);
                return false;
            }
            $combData = SI_SearchComb::GetCombDataByID($combId);
            $combCoords['RING'] = $combData['ringnumber'];
            $combCoords['COMPLEX'] = $combData['complexnumber'];
            $combCoords['COMB'] = $combData['combnumber'];

            //***** СОЗДАНИЕ СОТЫ *******//

            $this->common->executeQuery('begin');
            
            $this->common->setDbId($this->dbId);
            $_SESSION["dbId"] = $this->dbId;

            $combResult = SI_ExecStoreComb::Exec($oldCombId);
            $combResult->setCombName($combName);    
            
            $this->common->setDbId($this->turnirDbId);
            $_SESSION["dbId"] = $this->turnirDbId;  

            SI_ExecRestoreComb::Exec($this->turnirWorldId, $UIID, $combCoords, $combResult);

            $this->common->executeQuery('COMMIT');
            //$this->common->executeQuery('rollback');

            echo '<h1>User '.$userName.' Comb '.$combName.' create on coords ('.$combCoords['RING'].'.'.$combCoords['COMPLEX'].'.'.$combCoords['COMB'].')</h1></font>';
            $combNames .= '<br>Username '.$userName.', Combname '.$combName.'('.$combCoords['RING'].'.'.$combCoords['COMPLEX'].'.'.$combCoords['COMB'].')';
            //***************************//

        }
        echo '<h1>CREATED '.$i.' COMBS OF '.$this->combAmount.'</h1></font>';

        actionLog( "Create ncp-comb",  "From user '".$this->username."' in world '".$this->turnirWorldName."' on ring '".$this->rings[$this->race]."' count '".$this->combAmount."'.<br> 
        Combs: $combNames");

        return true;
    }

    function getRequest ($request)
    {
        if (key_exists('action', $request)) {
            $this->action = $request['action'];
        }

        if (key_exists('username', $request)) {
            $this->username = $request['username'];
        }

        if (key_exists('UIID', $request)) {
            $this->UIID = (int)$request['UIID'];
        }

        if (key_exists('race', $request)) {
            $this->race = (int)$request['race'];
            assert($this->race == 1 || $this->race == 2 || $this->race == 3);
        }

        if (key_exists('world', $request)) {

            $wid = explode ('_', $request['world']);
            $this->dbId = (int)$wid[0];
            $this->worldId = (int)$wid[1];
            assert($this->dbId >= 0);
            assert($this->worldId > 0);
        }

        if (key_exists('turnir_world', $request)) {

            $wid = explode ('_', urldecode($request['turnir_world']));
            $this->turnirDbId = (int)$wid[0];
            $this->turnirWorldId = (int)$wid[1];
            $this->turnirWorldName = $wid[2];
            assert($this->turnirDbId >= 0);
            assert($this->turnirWorldId > 0);
        }

        if (key_exists('amount_comb', $request)) {

            $this->combAmount = (int)$request['amount_comb'];
        }

        if (key_exists('comb_name', $request)) {

            $this->combName = $request['comb_name'];
        }

        if (key_exists('npc_player', $request)) {

            $this->npcPlayer = $request['npc_player'];
        }

        return true;
    }

}

$v = new TurnirCreateTemplate($_REQUEST);

// turnir stop
//
//      ds_vacationmode.end_date => null
//
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>