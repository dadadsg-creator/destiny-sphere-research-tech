<?php
ini_set('memory_limit', '256M');
set_time_limit(0);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";


class TurnirCreateTemplate
{
    private $action = "showFirstMenu";
    
    private $dbId;
    
    private $worldId;
    
    private $turnirDbId;
    
    private $turnirWorldId;
    
    private $turnirWorldName;
    
    private $username = "";
    
    private $common;
    
    private $UIID;
    
    private $race;

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
        echo "<h1>Choose template account:</h1>";
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

        // Выбираем список всех турнирных миров
        foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
        {
            $this->common->setDbId($dbId);
            $sql = "
                SELECT
                    w.id_world,
                    w.worldname
                FROM
                    ds_world AS w
                    INNER JOIN ds_gamemodifier AS gm ON (gm.id_world = w.id_world)
                WHERE
                    gm.modifiername = 'TURNIR_WORLD'
                    AND
                    gm.value = '1'
                ";
            
            $res = $this->common->getResultArray("", $sql , "", "", "");
            assert(empty($this->common->errormsg));
            
            foreach($res as $k => $world)
            {
                $worlds[$world['worldname']] = $dbId."_".$world['id_world'];
            }
        }

        if(count($worlds) == 0)
        {
            echo "<h1>No turnir worlds</h1>";
            return;
        }
        
        echo "<h1>Create temlate for turnir world:</h1>";
        echo '<select name="turnir_world">';
        foreach($worlds as $worldname => $world)
        {       
            if (!empty($this->turnirWorldId) && $world == $this->turnirDbId."_".$this->turnirWorldId) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            
            echo '<option value="'.$world.'_'.$worldname.'" '.$selected.'>'.$worldname."\n";
        }
        echo '</select><br><br><input type="Submit" name="next" value="&nbsp;&nbsp;Искать&nbsp;&nbsp;"  class = submit></form>';
        
    }

    function showSecondMenu () {

        assert($this->dbId >= 0);
        assert($this->worldId > 0); 
        assert($this->turnirDbId >= 0);
        assert($this->turnirWorldId > 0);   

        // Смотрим, есть ли такой игрок
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
        
        if ( count($userInstance) > 1 ) {
            //Игрока не зареган в этом мире
            print ShowCMSMessage('TEMPLATE CAN CONSIST ONLY ONE COMB', 2);
            return false;
        }
        
        $this->common->setDbId($this->turnirDbId);
        $_SESSION["dbId"] = $this->turnirDbId;
        
        // Смотрим, есть ли для этого мира и рассы уже темплейты
        $sql = "SELECT 
                    * 
                FROM 
                    ds_world_template
                WHERE 
                    id_world =  ".$this->turnirWorldId."
                    AND
                    id_race = ".$userInstance['0']['ringnumber'];
        ;
        $worldTemplates = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg));
        
        if ( count($worldTemplates) > 0 ) {
            print ShowCMSMessage('Для этого мира и рассы уже сздан шаблон. Он будет заменен новым', 2);
        }

        echo '<h1>CHOOSING TEMPLATE:</h1>';
        
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
                        
        echo '<h1>Turnir world: '.$this->turnirWorldName.'</h1>';       
        
        echo '
        <form action="" method="GET">
        <input type="hidden" name="action" value="applayTemplate">
        <input type="hidden" name="world" value="'.$this->dbId.'_'.$this->worldId.'">
        <input type="hidden" name="turnir_world" value="'.$this->turnirDbId.'_'.$this->turnirWorldId.'_'.$this->turnirWorldName.'">
        <input type="hidden" name="username" value="'.$this->username.'">
        <input type="hidden" name="UIID" value="'.$userInstance['0']['id_userinstance'].'">
        <input type="hidden" name="race" value="'.$userInstance['0']['ringnumber'].'">
        <input type="Submit" name="next" value="Applay Template"  class = submit>
        </form>';
        return true;
    }
    
    function applayTemplate () {
        assert($this->dbId >= 0);
        assert($this->worldId > 0); 
        assert($this->turnirDbId >= 0);
        assert($this->turnirWorldId > 0);   
        assert($this->UIID > 0);
        assert($this->race > 0 && $this->race < 4);

        $this->common->setDbId($this->dbId);
        $_SESSION["dbId"] = $this->dbId;
        
        // Морозим соту, чтобы она не обновилась(и не обновилось количество ресов на соте)
        $sql = "
        UPDATE ds_userinstance 
        SET lastrefresh = now()
        where id_userinstance = ".$this->UIID;
        $res = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg));
        
        if (count($vocationData) > 0) {
            $sql = "
            UPDATE 
                ds_vocationmode 
            SET 
                startdate = now(),
                enddate = now() + interval '10 days'
            WHERE
                id_userinstance = ".$this->UIID;
        } else {
            $sql = "
            INSERT INTO 
                ds_vocationmode 
                (startdate, enddate, id_userinstance)
                VALUES
                (now(), now() + interval '10 days', ".$this->UIID.")";
        }       
        $res = $this->common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($this->common->errormsg));
        
        // Сохраняем игрока
        echo '<font color="white"><h1>Start saving user...</h1>';
        flush();
        
        SI_ExecStoreUser::SetCommon($this->common);     
        $storeUserInstance = SI_ExecStoreUser::Exec($this->dbId, $this->UIID, $this->worldId, 1, 'admin', 'comb template', '0000-00-00 00:00:00', 0, $this->username);
        $id_storeuserdata = SI_ExecStoreUser::GetUserDataId();
        
        echo '<h1>User stored.</h1>';
        
        echo '<h1>Start create template...</h1>';
        SI_ExecStoreCombTemplate::GetFromStoreUserData($id_storeuserdata);
        echo  'Template <b>"'.$this->username.'_race'.$this->race.'"</b> create saccessful!';
        
        // Возвращаем отпуск на место
        if (count($vocationData) > 0) {
            $sql = "
            UPDATE 
                ds_vocationmode 
            SET 
                startdate = '".$vocationData[0]['startdate']."',
                enddate = '".$vocationData[0]['enddate']."'
            WHERE
                id_userinstance = ".$this->UIID;
        } else {
            $sql = "
            DELETE FROM
                ds_vocationmode 
            WHERE 
                id_userinstance = ".$this->UIID;
        }   
        $res = $this->common->getResultArray ( "", $sql, "", "", "" );  
        assert(empty($this->common->errormsg));
        
        echo '<h1>Start insert template...</h1>';
        
        $this->common->setDbId($this->turnirDbId);
        $_SESSION["dbId"] = $this->turnirDbId;
        
        // Смотрим, есть ли уже шиблон для этого мира
        $sql = "SELECT 
                    * 
                FROM 
                    ds_world_template
                WHERE 
                    id_world =  ".$this->turnirWorldId."
                    AND
                    id_race = ".$this->race;
        ;
        $worldTemplates = $this->common->getResultArray("", $sql, "", "", "" );
        assert(empty($this->common->errormsg));
        
        if (count($worldTemplates) > 0) {
            $sql = "
                UPDATE 
                    ds_world_template
                SET
                    template_name = '".$this->username.'_race'.$this->race."'
                WHERE 
                    id_world =  ".$this->turnirWorldId."
                    AND
                    id_race = ".$this->race;
            $res = $this->common->getResultArray("", $sql, "", "", "" );
            assert(empty($this->common->errormsg));
        } else {
            $sql = "
                INSERT INTO 
                    ds_world_template
                (id_world, id_race, template_name)
                VALUES
                (".$this->turnirWorldId.", ".$this->race.", '".$this->username.'_race'.$this->race."')";
            $res = $this->common->getResultArray("", $sql, "", "", "" );
            assert(empty($this->common->errormsg));
        }
        echo '<h1>Template insert saccessful!</h1></font>';
        
        actionLog( "Create turnir templete",  "To race: ".$this->race." from user ".$this->username);

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
        }
        
        if (key_exists('world', $request)) {
            
            $wid = explode ('_', $request['world']);
            $this->dbId = (int)$wid[0];
            $this->worldId = (int)$wid[1];
            assert($this->dbId >= 0);
            assert($this->worldId > 0); 
        }

        if (key_exists('turnir_world', $request)) {
            
            $wid = explode ('_', $request['turnir_world']);
            $this->turnirDbId = (int)$wid[0];
            $this->turnirWorldId = (int)$wid[1];
            $this->turnirWorldName = $wid[2];
            assert($this->turnirDbId >= 0);
            assert($this->turnirWorldId > 0);   
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