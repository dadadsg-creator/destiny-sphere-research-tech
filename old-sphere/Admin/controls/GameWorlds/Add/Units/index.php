<?PHP
session_start();
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";


ini_set('memory_limit', '128M');

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Units><font size=3><b>HELP</b></font></a></p>';

$common = DS_Common::CreateForCurentDB();

class addUnits {
    
    var $dbId;
    var $id_world;
    
    var $username;
    
    var $unitsParams;
    
    var $id_unitcontainer;
    var $addUnitsId;
    var $addUnitsAmount;

    var $allUnits;
    
    var $selectUnitContainer;
    var $selectUnits;

    var $errormsg;
    var $comment = "";
    
    function addUnits( ) {

        $this->getRequest ( $_POST );
    }

    function getRequest ( $request ) 
    {
        if ( isset($_SESSION["username"])) 
        {
            $this->username = $_SESSION["username"];
        }

        if ( isset($_SESSION["id_world"])) 
        {
            $this->id_world = $_SESSION["id_world"];
        }

        if ( isset($_SESSION["dbId"])) 
        {
            $this->dbId = $_SESSION["dbId"];
        }

        if(key_exists('comment', $request))
        {
            $this->comment = pg_escape_string(trim($request['comment']));
        }

        if ( isset ( $request["showSecondMenu"] ) ) {
            
            list( $_SESSION['id_world'], $_SESSION['dbId'] ) = explode('_', $request['id_world']);
            $_SESSION['username'] = $request["username"];           
            list( $this->id_world, $this->dbId ) = explode('_', $request['id_world']);
            
            $this->username = $request['username'];     
            
            $this->getAllUnits();

            if( !$this->showSecondMenu() ){
                
                $this->showFirstMenu ( $this->errormsg );
            }
            
        } elseif ( isset( $request["saveChanges"] ) ) {
                                                    
            $this->dbId = $request['dbid'];
            $this->unitsParams = $request['delta'];
            $this->unitsSPR = $request['unitname'];
            
            
            if ( !$this->updateUnitsAmount() ) 
            {
                $this->showFirstMenu( $this->errormsg );
            } else {
                if(!$this->DeleteNullAmountUnits())
                {
                    $this->showFirstMenu ( $this->errormsg );
                } else {
                    ShowCMSMessage(local( 'DS_ACTION_SUCCEED ' ), 1);
                    $this->showFirstMenu ( );               
                }
            } 
            
        } elseif(isset($request['addUnits'])) {
            if (key_exists('clear', $request))
            {
                unset($_SESSION['addedUnits']);
                
                list( $_SESSION['id_world'], $_SESSION['dbId'] ) = explode('_', $request['id_world']);
                $_SESSION['username'] = $request["username"];           
                list( $this->id_world, $this->dbId ) = explode('_', $request['id_world']);
            
                $this->username = $request['username'];     
            
                $this->getAllUnits();
                    
                if( !$this->showSecondMenu() )
                {
                    $this->showFirstMenu ( $this->errormsg );
                }
            } else {
                $this->dbId = $request['dbid'];
                $this->id_unitcontainer = $request['id_unitcontainer'];
                $this->addUnitsId = $request['addunitsid'];
                $this->addUnitsAmount = $request['addunitsamount'];
    
                $i = 0;
                
                foreach ($this->addUnitsAmount AS $key => $unitAmount)
                {
                    if ($unitAmount != 0 || $unitAmount != '')
                    {
                        $_SESSION['addedUnits'][$i]['unit_id'] =  $this->addUnitsId[$key];
                        $_SESSION['addedUnits'][$i]['amount'] =  $unitAmount;
                        $i ++;
                    }
                }
    
                if ( !$this->updateUnitContainer() ) 
                {
                    $this->showFirstMenu ( $this->errormsg );
                } else {
                    ShowCMSMessage(local( 'DS_ACTION_SUCCEED ' ), 1);
                    $this->showFirstMenu ( );               
                }
            }
        }else{
            $this->showFirstMenu ( );
        }
                        
        return true;
    }

    
    function showFirstMenu ( $str = "" ) {
            
        if (!empty($str)) {
            ShowCMSMessage($str, 2);
        }
        
        
        global $common;
        
        print "
        <form action='".$_SERVER["PHP_SELF"]."' method='POST'>
        <input type='hidden' name='showSecondMenu' value='1'>
        
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
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
                    <select name='id_world' style=\"width:250px;\">";
        
        $sql = sprintf ( "select * from ds_world" );
        $res = $common->getMultySql ($sql);
        
        if ( !$res ) {
            $this->errormsg = "addUnits::showFirstMenu !!! ".$sql;
            return false;
        }
        
        
        for ( $i=0; $i<sizeof ( $res ); $i++ ) {
            
            echo "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."'".($this->id_world."_".$this->dbId==$res[$i]['id_world']."_".$res[$i]['dbId'] ? " selected" : "").">".$res[$i]['worldname']."</option>";
        }
        
        echo "</select><br>
                </td>
                <td bgcolor='444E6C'>
                    <input type='text' name='username' size=30 value='".$this->username."'>
                </td>
            </tr>
            <tr>
                <td colspan =2 bgcolor='444E6C'><input type='submit' class=submit name='next' value='".local( 'DS_ACTION_SEARCH' )."'></td>
            </tr>
        </table></td></tr></table>
        </form>
        ";
        
        return true;
    }
    
    
    function showSecondMenu(){
        
        global $common;
        
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

        $common->setDbId( $this->dbId );

        if ( $this->id_world == "" ) {
            $this->errormsg = "Select world";
            return false;   
        }
        
        if ( $this->username == "" ) {
            $this->errormsg = "Enter Username";
            return false;   
        }
        
        $sql = sprintf (
            "
            SELECT
                u.username,
                ui.id_userinstance,
                ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
                co.id_comb,
                cn.combname,
                uc.id_unitcontainer,
                uc.unitcontainername,
                ltl.text as unitname,
                uu.amount::real,
                uu.id_army,
                uu.id_unitcontainerhasunit
            
            FROM
                ds_user u
                LEFT JOIN ds_userinstance ui ON ( u.id_user = ui.id_user )
                LEFT JOIN ds_comb co ON ( ui.id_userinstance = co.id_userinstance )
                LEFT JOIN ds_combname cn ON ( co.id_comb = cn.id_comb )
                LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
                LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                LEFT JOIN ds_unitcontainer uc ON ( co.id_comb = uc.id_basecomb )
                LEFT JOIN ds_unitorcontainertype uct ON ( uc.id_unitcontainertype = uct.id_unitcontainertype )
                LEFT JOIN ds_unitcontainerhasunit uu ON ( uc.id_unitcontainer = uu.id_army )
                LEFT JOIN ds_unit un ON ( uu.id_unit = un.id_unit )
                LEFT JOIN ds_localizedtext lot ON ( un.id_localizedname = lot.id_localizedtext )
                LEFT JOIN ds_localizedtextlanguage ltl ON ( lot.id_localizedtext = ltl.id_localizedtext AND ltl.id_language = '%d')
            
            WHERE
                uct.containertypename = 'ARMY'
                AND u.username = '%s'
                AND ui.id_world = '%d'
            
            ORDER BY
                ri.ringnumber,
                cc.complexnumber,
                co.combnumber,
                uc.unitcontainername,
                unitname
            ",
            constant( 'DEFAULT_LANG_ID'),
            pg_escape_string( $this->username ),
            $this->id_world
        );
        $res = $common->getResultArray ( "", $sql, "", "", "" );

        if ( !$res ) {

            $sql  = sprintf ("
                SELECT 
                    u.username,
                    ui.id_userinstance,
                    (ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber) AS coords,
                    co.id_comb,
                    cn.combname,
                    uc.id_unitcontainer,
                    uc.unitcontainername
                FROM
                        ds_user u
                    LEFT OUTER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
                    LEFT OUTER JOIN ds_comb co ON (ui.id_userinstance = co.id_userinstance)
                    LEFT OUTER JOIN ds_combname cn ON (co.id_comb = cn.id_comb)
                    LEFT OUTER JOIN ds_combcomplex cc ON (co.id_combcomplex = cc.id_combcomplex)
                    LEFT OUTER JOIN ds_ring ri ON (cc.id_ring = ri.id_ring)
                    LEFT OUTER JOIN ds_unitcontainer uc ON (co.id_comb = uc.id_basecomb)
                    LEFT OUTER JOIN ds_unitorcontainertype uct ON (uc.id_unitcontainertype = uct.id_unitcontainertype)
                WHERE
                    (uct.containertypename = 'ARMY') AND 
                    (u.username = E'%s') AND 
                    (ui.id_world = '%d')
                ORDER BY
                    ri.ringnumber,
                    cc.complexnumber,
                    co.combnumber,
                    uc.unitcontainername
            ",
            pg_escape_string( $this->username ),
            $this->id_world
            );
        $res = $common->getResultArray ( "get_all_ARMY_container", $sql, "", "", "" );      

        }
        if ( !$res ) {
            $this->errormsg = "<pre> SYSTEM ERROR - addUnits::showSecondMenu !!! Not find user or Units container</pre>";
            return false;
        } $this->showFirstMenu();


        
        echo "<table width=100% border=0 cellpadding=0 cellspacing=0><tr><td width=50% align=center valign=top>";
        /*      /* ЗАКОМЕНЧЕНО ДЛЯ АЛЬФЫ
        <form name='change_unit' action='".$_SERVER["PHP_SELF"]."' method='POST' onsubmit=\"javascript: return check(document.change_unit.comment.value,'Empty comment!');\">*/
        echo "
        <form name='change_unit' action='".$_SERVER["PHP_SELF"]."' method='POST'>
        <form name='change_unit' action='".$_SERVER["PHP_SELF"]."' method='POST' onsubmit=\"javascript: return check(document.change_unit.comment.value,'!');\">
        <input type='hidden' name='saveChanges' value='1'>
        <input type='hidden' name='dbid' value='".$this->dbId."'>
        <input name='id_world' type='hidden' value='".$_REQUEST['id_world']."'>
        <input name='username' type='hidden' value='".$_REQUEST['username']."'>             
        
        <table width=500 border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
        ";
        
        $temp_id_comb = '';
        $temp_id_unitcontainer = '';
        
        $this->selectUnitContainer = "<select name='id_unitcontainer'>";


        foreach ( $res as $row ){
            

            if( $temp_id_comb != $row['id_comb'] ){
                print "
            <tr>
                <th colspan=3 bgcolor='444E6C' class='th_3'><span class=select_txt>CombName (Coords)</span></th>
            </tr>
            <tr>
                <td colspan=3 bgcolor='444E6C' class='mess_info' align=center><b>".$row['combname']." ( ".$row['coords']." )</b></td>
            </tr>
            ";
                $temp_id_comb = $row['id_comb'];
            }

            if( $temp_id_unitcontainer != $row['id_unitcontainer'] ){

                print "
            <tr>
                <td heidth=25 colspan=3 bgcolor='444E6C'></td>
            </tr>
            <tr>
                <th colspan=3 bgcolor='444E6C' class='th_2'>Unit Container Name</th>
            </tr>
            <tr>
                <td bgcolor='444E6C' class='maintxt' colspan='3' align=center><span class=g_green><b>".$row['unitcontainername']."</b></span></td>
            </tr>
            <tr>
<!--                <th bgcolor='444E6C' class='th_2'>Unit Container Name</th> -->
                <th bgcolor='444E6C' class='th_2'>UnitName</th>
                <th bgcolor='444E6C' class='th_2'>UnitsAmount</th>
                <th bgcolor='444E6C' class='th_2'>DeltaValue</th>
            </tr>
            ";
                $temp_id_unitcontainer = $row['id_unitcontainer'];
                $this->selectUnitContainer .= "<option value='".$row['id_unitcontainer']."'>".$row['unitcontainername']."</option>";
            }
            
            if ($row['unitname']) {
            print "
            <tr>
                <td bgcolor='444E6C' class='maintxt_med' nowrap>".$row['unitname']."</td>
                <td bgcolor='444E6C' class='maintxt_med' align=right>".(!$row['unitname']? "" : sprintf( '%.0f', $row['amount']))."&nbsp;&nbsp;</td>
                <td bgcolor='444E6C' class='maintxt_med' align=center><input type='text' size=25 name='delta[".$row['id_unitcontainerhasunit']."]' value=''></td>
            </tr>";
            }
        }
        
        $this->selectUnitContainer .= "</select>";
        
        echo "
        <tr>
            <td colspan=3 bgcolor='#444E6C' class='maintxt_med' align=center>
                <b>Comment:</b> <input type='text' value='' name='comment' size='45'>
            </td>
        </tr>
        <tr>
            <td colspan=3 bgcolor='#444E6C'>
                <input type='submit'class=Submit value='". local( 'DS_ACTION_APPLY' )."' name='submit'>
            </td>
        </tr>
        </table></td></tr></table></form></td>
        ";

        
        echo "<td width=50% align=center valign=top>
        <form name='add_unit' action='".$_SERVER["PHP_SELF"]."' method='POST'>

        <input type='hidden' name='addUnits' value='1'>
        <input type='hidden' name='dbid' value='".$this->dbId."'>
        <input name='id_world' type='hidden' value='".$_REQUEST['id_world']."'>
        <input name='username' type='hidden' value='".$_REQUEST['username']."'>
        
        <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
            <tr>
                <th bgcolor='#444E6C' colspan=2 class='th_2'>Select Unit Container Name</th> 
            </tr>
            <tr>
                <td bgcolor='#444E6C' colspan=2 class='maintxt' align=center>".$this->selectUnitContainer."</td>
            </tr>
            <tr>
                <th bgcolor='#444E6C' class=th_2>Select Units</th>
                <th bgcolor='#444E6C' class=th_2>UnitsAmount</th>
            </tr>
        ";

            for( $i = 0; $i < 25; $i++ ){
                if (@key_exists($i, $_SESSION['addedUnits']))
                {
                    echo "
                    <tr>
                        <td bgcolor='#444E6C' class='maintxt'>".$this->selectUnitWithSelected($_SESSION['addedUnits'][$i]['unit_id'])."</td>
                        <td bgcolor='#444E6C' class='maintxt'><input type='text' name='addunitsamount[]' value='".$_SESSION['addedUnits'][$i]['amount']."'></td>
                    </tr>";
                }
                else {
                echo "
                <tr>
                    <td bgcolor='#444E6C' class='maintxt'>".$this->selectUnits."</td>
                    <td bgcolor='#444E6C' class='maintxt'><input type='text' name='addunitsamount[]' value=''></td>
                </tr>";
                }
            }
        
        echo "
            <tr>
                <td colspan=2 bgcolor='#444E6C' class='FormText' align='center'>
                    <b>Comment:</b> <input type='text' value='' name='comment' size='45'>
                </td>
            </tr>
            <tr>
                <td colspan=2 bgcolor='#444E6C' class='FormText'>
                    <input type='submit'class=Submit value='  ". local( 'DS_ADD' )."  ' name='submit'>
                    <input type='submit'class=Submit value='  ". local( 'ADMIN_CLEAR' )."  ' name='clear'>
                </td>
            </tr>
        </table></td></tr></table></form>
        </td></tr></table>";
        
        //Получаем имя мира по его id
        $sql = "SELECT
                        worldname
                        FROM ds_world 
                        WHERE id_world = $this->id_world";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        $worldName = $res[0]['worldname'];
        
        actionLog( "Show utits amount for User '".$this->username."' in World '$worldName'");

        return true;
    }

    function updateUnitsAmount ( ) {
        
        global $common;
        
        $common->setDbId( $this->dbId );
                
        if ( $this->unitsParams == "" || !is_array( $this->unitsParams ) ){
            $this->errormsg = "Wrong Params";
            return false;
        }

        $added = '';
        $addedUnits = '';
        // Письмо насяльнику
        $addedQuestUnits = '';
        
        $common->executeQuery ( 'begin' );
        
        foreach ( $this->unitsParams as $id_unitcontainerhasunit => $amount )
        {           

            $amount = (int)$amount;
            $amount = (int)$amount;
            
            if($amount == '' || $amount == 0)
            {               
                continue;
            }
            
            $unitname = $this->getLocalizedUnitName($id_unitcontainerhasunit,0);
            if(!$unitname)
            {
                $common->executeQuery ( 'rollback' );
                $this->errormsg = " SYSTEM ERROR - addUnits::updateUnitsAmount !!! ".$sql;
                return false;
            }
            
            $quest = false;
            // Смотрим, квестовый ли юнит
            $sql = 
                "SELECT 
                    id_unitcontainerhasunit
                FROM ds_unitcontainerhasunit uchu 
                LEFT JOIN ds_unit u ON (u.id_unit = uchu.id_unit)
                LEFT JOIN ds_unitcanbebuildbyrace AS ucbbr ON (ucbbr.id_unit = u.id_unit)
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = u.id_localizedname AND ltl.id_language = 3)
                WHERE 
                    ucbbr.id_race = 4
                    AND 
                    uchu.id_unitcontainerhasunit = $id_unitcontainerhasunit";
            $res = $common->getQueryArray( $sql );          
            if ( !empty( $res ) ) 
            {
                $quest = true;
            }
            
            $sql = sprintf("select 
                                    amount
                                from
                                    ds_unitcontainerhasunit
                                where
                                    id_unitcontainerhasunit = %d",
                            $id_unitcontainerhasunit);
                            
            $res = $common->getResultArray ( "", $sql, "", "", "" );
            if($res === false) 
            {
                $common->executeQuery ( 'rollback' );
                $this->errormsg = " SYSTEM ERROR - addUnits::getUnitsAmount !!! ".$sql;
                return false;
            }
            
            $curAmount = (int)(float)$res[0]['amount'];
            
            if($amount > 0)
            {
                $added .= ' '. $amount.' to '.$curAmount.' of '.$unitname.'<br>';
            } else {
                $added .= ' '. $amount.' from '.$curAmount.' of '.$unitname.'<br>';
            }
            $addedUnits .= $unitname. ': '.$amount.'<br>';
            
            if ($quest === true) {
                    $addedQuestUnits .= ' '. $amount.' '.$unitname."\n";
                }
            
            if(($curAmount + $amount) <= 0)
            {
                $sql = sprintf("delete from
                                            ds_unitcontainerhasunit
                                        where
                                            id_unitcontainerhasunit = %d",
                                $id_unitcontainerhasunit);
            }
            else 
            {           
                $sql = sprintf("UPDATE
                                        ds_unitcontainerhasunit
                                    SET
                                        amount = amount + %d
                                    WHERE
                                        id_unitcontainerhasunit = %d",
                                $amount,
                                $id_unitcontainerhasunit
                                );
            }
            $common->executeQuery ( $sql );
            if ($common->errormsg) 
            {   
                echo $common->errormsg;
                $common->executeQuery ( 'rollback' );
                //$this->errormsg = " SYSTEM ERROR - addUnits::updateUnitsAmount !!! ".$sql;
                return false;
            }           
        }
        
        //Получаем имя мира по его id
        $sql = "SELECT
                        worldname
                        FROM ds_world 
                        WHERE id_world =". $this->id_world;

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        $worldName = $res[0]['worldname'];
        
        actionLog( "Changing Units Amount for Username:'".$_REQUEST['username']."' in World '$worldName.'<br>\nComment:.'".$this->comment."'",'Aded:<br>'.$added);
        
        // Если добавляли квестовые юниты
        if (!empty($addedQuestUnits)) {
            $title = "[DS] Add Quest Units [".str_replace('_', '', constant('CACHING_PREFIX'))."]";

            $latter  = "Add quest Units for user ".$_REQUEST['username']." in world ".$worldName."\n";
            $latter .= "IP: ".@$_SERVER['REMOTE_ADDR']."\n";
            $latter .= "Server: ".@$_SERVER['SERVER_NAME']."\n";
            $latter .= "Comment: ".$this->comment."\n\n";
            $latter .= "Aded:\n".$addedQuestUnits;

            //mail('annita@destinysphere.com', $title, $latter);
            //mail('dmitri@destinysphere.com', $title, $latter);
            //mail('maxim@destinysphere.com', $title, $latter);
        }

        $message = 
'На вашей соте было изменено количество военных юнитов:
'.$addedUnits.'
По всем вопросам вы можете обратиться в службу технической поддержки sup@destinysphere.com 
                
-- 
Вам желает удачи
команда поддержки  DestinySphere';
        
        $this->sendMessage($message);


        $common->executeQuery ( 'commit' );
        return true;
    }
    
    
    function getAllUnits( ){

        global $common;
        
        $common->setDbId( $this->dbId );
        
        $sql = sprintf("
                        SELECT
                                un.id_unit,
                                ltl.text as unitname,
                                ucbbbr.id_race,
                                (
                                SELECT
                                        ltl.text
                                    FROM
                                        ds_race r,
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        r.id_race = ucbbbr.id_race
                                        AND r.id_localizedname = lt.id_localizedtext
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as racename
                            FROM    
                                ds_unit un  
                                LEFT JOIN ds_unitcanbebuildbyrace ucbbbr USING(id_unit)
                                LEFT JOIN ds_unitorcontainertype uct USING(id_unitcontainertype)
                                LEFT JOIN ds_unithasclass uhc USING(id_unit)
                                LEFT JOIN ds_unitclass uc ON (uhc.id_unitclass = uc.id_unitclass)
                                LEFT JOIN ds_localizedtext lot ON (un.id_localizedname = lot.id_localizedtext)
                                LEFT JOIN ds_localizedtextlanguage ltl ON ( lot.id_localizedtext = ltl.id_localizedtext)   
                            WHERE
                                uct.containertypename = 'ARMY'
                                AND ltl.id_language = %d                                                                                                                
                        ORDER BY
                                ucbbbr.id_race, uc.classnumber",
                        constant( 'DEFAULT_LANG_ID' ),
                        constant( 'DEFAULT_LANG_ID' ));

        $res = $common->getResultArray ( "allunits", $sql, "", "", "" );
        if ( !$res ) {
            $this->errormsg = " SYSTEM ERROR - addUnits::getAllUnits !!! ".$sql;
            return false;
        }
        
        foreach ( $res as $row )
        {
            
            $this->allUnits[ $row['id_unit'] ] = $row['unitname'];
        }


        $current_race = 0;
        $this->selectUnits = "<select name='addunitsid[]'>";
        
        foreach($res as $k => $v) 
        {
            if($current_race != (int)$v['id_race'])
            {
                $this->selectUnits .= "<option disabled>-------------".
                                        $v['racename'].
                                        "----------------</option>";
                $current_race = (int)$v['id_race'];
            }
            
            $this->selectUnits .= "<option value='".$v['id_unit']."'>".$v['unitname']."</option>";
            $tmp .= "<input name='unitname[".$v['id_unit']."]' type='hidden' value='".base64_encode($v['unitname'])."'>";
        }
        
        $this->selectUnits .= "</select>";
        $this->selectUnits .=$tmp;
        return true;
    }
    
    
    /**
    * @return true if resultat OK
    * @desc Добавление новых и/или увеличение кол-ва юнитов в выбранной армии игрока
    */
    function updateUnitContainer( ){

        /* ЗАКОМЕНЧЕНО ДЛЯ АЛЬФЫ

        if (empty($this->comment))
        {
            ShowCMSMessage('<b>ENTER COMMENT!!!</b>', 2);
            return false;
        }*/
        
        global $common;     
        $common->setDbId( $this->dbId );
        
        if (
            $this->addUnitsAmount == "" || !is_array( $this->addUnitsAmount ) ||
            $this->addUnitsId == "" || !is_array( $this->addUnitsId ) ||
            $this->id_unitcontainer == ""
        ){
            $this->errormsg = "Wrong Params";
            return false;
        }

        $common->executeQuery ( 'begin' );
        $added = '';
        
        // Письмо насяльнику
        $addedUnits = '';
        $addedQuestUnits = '';
        
        foreach($this->addUnitsAmount as $id => $amount)
        {
            $amount = (int)$amount;
            
            if($amount == '' || $amount <= 0)
            {
                continue;
            } elseif ( $this->addUnitsId[$id] == '' || $this->addUnitsId[$id] <= 0 ) {
                continue;
            }
            
            $quest = false;
            // Смотрим, квестовый ли юнит
            $sql = 
                "SELECT ltl.text 
                FROM ds_unit u 
                LEFT JOIN ds_unitcanbebuildbyrace AS ucbbr ON (ucbbr.id_unit = u.id_unit)
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = u.id_localizedname AND ltl.id_language = 3)
                WHERE ucbbr.id_race = 4
                AND u.id_unit = ".$this->addUnitsId[$id];
            $res = $common->getQueryArray( $sql );          
            if ( !empty( $res ) ) 
            {
                $quest = true;
            }
            
            $sql = '';
            $res = array();
            
            $sql = sprintf(
                    "
                        SELECT
                            uchu.id_unitcontainerhasunit,
                            uchu.amount
                        FROM
                            ds_unitcontainerhasunit uchu
                        WHERE
                            uchu.id_army = '%d'
                            AND uchu.id_unit = '%d'
                    ",
                    $this->id_unitcontainer,
                    $this->addUnitsId[$id]
                );
            
            $res = $common->getQueryArray( $sql );          
            if ( !empty( $res ) ) 
            {
                $unitname = $this->getLocalizedUnitName($res[0]['id_unitcontainerhasunit'], 0);
                if(!$unitname)
                {
                    $common->executeQuery('rollback');
                    $this->errormsg = "SYSTEM ERROR - addUnits::updateUnitsAmount !!! ".$sql;
                    return false;
                }
                
                $added .= ' '. $amount.' to '.$res[0]['amount'].' of '.$unitname.'<br>';

                $addedUnits .= $unitname. ': '.$amount.'<br>';
                if ($quest === true) {
                    $addedQuestUnits .= ' '. $amount.'  '.$unitname.' 
';
                }
                                
                if(((int)$res[0]['amount'] + $amount) <= 0)
                {
                    $sql = sprintf("delete from
                                                ds_unitcontainerhasunit
                                            where
                                                id_unitcontainerhasunit = %d",
                                    $res[0]['id_unitcontainerhasunit']);
                } else {            
                    $sql = sprintf("UPDATE
                                        ds_unitcontainerhasunit
                                    SET
                                        amount = amount + %d
                                    WHERE
                                        id_unitcontainerhasunit = %d",
                                $amount,
                                $res[0]['id_unitcontainerhasunit']
                                );
                }
            } else {
                $sql = sprintf(
                "
                INSERT INTO ds_unitcontainerhasunit ( id_army, id_unit, amount )
                VALUES ( '%d', '%d', '%d' );
                ",
                $this->id_unitcontainer,
                $this->addUnitsId[$id],
                $amount
                );
                
                $unitname = $this->getLocalizedUnitName(0,$this->addUnitsId[$id]);
                if(!$unitname)
                {
                    $common->executeQuery ( 'rollback' );
                    $this->errormsg = " SYSTEM ERROR - addUnits::insertUnitsAmount !!! ".$sql;
                    return false;
                }
                $added .= ' '. $amount.' of '.$unitname.' <br>';
                $addedUnits .= $unitname. ': '.$amount.'<br>';
                if ($quest === true) {
                    $addedQuestUnits .= ' '. $amount.' '.$unitname.'
';

                }
                
            }
            
            $res = $common->executeQuery ( $sql );
            if ( $res === false ) 
            {
                $common->executeQuery( 'rollback' );
                $this->errormsg = " SYSTEM ERROR - addUnits::updateUnitContainer !!! ".$sql;
                return false;
            }           

        }
        
        if($common->executeQuery('commit')) 
        {
                //Получаем имя мира по его id
                $sql = "SELECT
                        worldname
                        FROM ds_world 
                        WHERE id_world =". $this->id_world;

                $res = $common->getResultArray("", $sql, "", "", "");
                assert(empty($common->errormsg));
                $worldName = $res[0]['worldname'];
            
            actionLog( 'Add new Units for user "'.$_REQUEST['username'].'" in world "'.$worldName."'<br>\nComment:.'".$this->comment."'", $added);
            
            // Если добавляли квестовые юниты
            if (!empty($addedQuestUnits)) {
                $title = "[DS] Add Quest Units [".str_replace('_', '', constant('CACHING_PREFIX'))."]";
                
                $latter  = "Add quest Units for user ".$_REQUEST['username']." in world ".$worldName."\n";
                $latter .= "IP: ".@$_SERVER['REMOTE_ADDR']."\n";
                $latter .= "Server: ".@$_SERVER['SERVER_NAME']."\n";
                $latter .= "Comment: ".$this->comment."\n\n";
                $latter .= "Aded:\n".$addedQuestUnits;
            
                //mail('annita@destinysphere.com', $title, $latter);                  
                //mail('dmitri@destinysphere.com', $title, $latter);                    
                //mail('enzo@ru.destinysphere.com', $title, $latter);   
            }

            $message = 
'На ваш аккаунт были добавлены следующие юниты:
'.$addedUnits.'
По всем вопросам вы можете обратиться в службу технической поддержки sup@destinysphere.com 
               
-- 
Вам желает удачи
команда поддержки  DestinySphere';
            
            $this->sendMessage($message);


        }
        
        
        return true;
        
    }

    function DeleteNullAmountUnits ( ) {
        
        global $common;
        
        $common->setDbId( $this->dbId );
        
        if ( $this->unitsParams == "" || !is_array( $this->unitsParams ) ){
            $this->errormsg = "Wrong Params";
            return false;
        }

        $common->executeQuery ( 'begin' );

        foreach ( $this->unitsParams as $id_unitcontainerhasunit => $amount ){
            
//          unlink ($amount);
            $sql = sprintf(
            "
            DELETE
            FROM
                ds_unitcontainerhasunit
            WHERE
                amount <= '0' AND
                id_unitcontainerhasunit = '%d'
            ",
            $id_unitcontainerhasunit
            );
            
            $res = $common->executeQuery ( $sql );
            if ( $res === false ) {
                $this->errormsg = " SYSTEM ERROR - addUnits::DeleteNullAmountUnits !!! ".$sql;              
                return false;
            } 
        }
        
        $common->executeQuery ( 'commit' );

        return true;
    }
    
    function getLocalizedUnitName($id_unitcontainerhasunit,$id_unit)
    {
        global $common;
        if($id_unitcontainerhasunit > 0)
        {
            $sql = sprintf("SELECT
                                ltl.text
                            FROM
                                ds_unitcontainerhasunit uchu,
                                ds_unit u,
                                ds_localizedtext lt,
                                ds_localizedtextlanguage ltl
                            WHERE
                                uchu.id_unitcontainerhasunit = %d
                                AND uchu.id_unit = u.id_unit
                                AND u.id_localizedname = lt.id_localizedtext
                                AND lt.id_localizedtext = ltl.id_localizedtext
                                AND ltl.id_language = %d",
                            $id_unitcontainerhasunit,
                            constant( 'DEFAULT_LANG_ID' ));
        }
        else 
        {
            assert($id_unit > 0);           
            $sql = sprintf("SELECT
                                ltl.text
                            FROM                                
                                ds_unit u,
                                ds_localizedtext lt,
                                ds_localizedtextlanguage ltl
                            WHERE                               
                                u.id_unit = %d
                                AND u.id_localizedname = lt.id_localizedtext
                                AND lt.id_localizedtext = ltl.id_localizedtext
                                AND ltl.id_language = %d",
                            $id_unit,
                            constant( 'DEFAULT_LANG_ID' ));
        }
                            
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        if (!$res || sizeof($res) === 0)
        {
            echo $sql;
            return false;
        }
        else 
        {
            return $res[0]['text'];
        }
    }


function selectUnitWithSelected($id_unit)
    {
        global $common;
        
        $common->setDbId( $this->dbId );
        
        $sql = sprintf("
                        SELECT
                                un.id_unit,
                                ltl.text as unitname,
                                ucbbbr.id_race,
                                (
                                SELECT
                                        ltl.text
                                    FROM
                                        ds_race r,
                                        ds_localizedtext lt,
                                        ds_localizedtextlanguage ltl
                                    WHERE
                                        r.id_race = ucbbbr.id_race
                                        AND r.id_localizedname = lt.id_localizedtext
                                        AND lt.id_localizedtext = ltl.id_localizedtext
                                        AND ltl.id_language = %d
                                ) as racename
                            FROM    
                                ds_unit un  
                                LEFT JOIN ds_unitcanbebuildbyrace ucbbbr USING(id_unit)
                                LEFT JOIN ds_unitorcontainertype uct USING(id_unitcontainertype)
                                LEFT JOIN ds_unithasclass uhc USING(id_unit)
                                LEFT JOIN ds_unitclass uc ON (uhc.id_unitclass = uc.id_unitclass)
                                LEFT JOIN ds_localizedtext lot ON (un.id_localizedname = lot.id_localizedtext)
                                LEFT JOIN ds_localizedtextlanguage ltl ON ( lot.id_localizedtext = ltl.id_localizedtext)   
                            WHERE
                                uct.containertypename = 'ARMY'
                                AND ltl.id_language = %d                                                                                                                
                        ORDER BY
                                ucbbbr.id_race, uc.classnumber",
                        constant( 'DEFAULT_LANG_ID' ),
                        constant( 'DEFAULT_LANG_ID' ));

        $res = $common->getResultArray ( "allunits", $sql, "", "", "" );
        if ( !$res ) {
            $this->errormsg = " SYSTEM ERROR - addUnits::getAllUnits !!! ".$sql;
            return false;
        }
        
        foreach ( $res as $row )
        {
            
            $this->allUnits[ $row['id_unit'] ] = $row['unitname'];
        }


        $current_race = 0;
        $selectUnits = "<select name='addunitsid[]'>";
        
        foreach($res as $k => $v) 
        {
            if($current_race != (int)$v['id_race'])
            {
                $selectUnits .= "<option disabled>-------------".
                                        $v['racename'].
                                        "----------------</option>";
                $current_race = (int)$v['id_race'];
            }
            if ($id_unit == $v['id_unit'])
            {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            
            $selectUnits .= "<option value='".$v['id_unit']."' $selected>".$v['unitname']."</option>";
            $tmp .= "<input name='unitname[".$v['id_unit']."]' type='hidden' value='".base64_encode($v['unitname'])."'>";
        }
        
        $selectUnits .= "</select>";
        $selectUnits .=$tmp;
    
        return $selectUnits;
    }

    private function sendMessage($message)
    {
        global $common;
        $user = DE_User::CreateFromName($this->username);
        $fromUIID = 0;

        $subject ="Добавление юнитов";

        $subject = pg_escape_string(urldecode($subject));
        assert(!empty($subject));
        
        $shortMassage = substr($message, 0, 100);
        $message = pg_escape_string(GL_EncodeGameMessage::Encode(urldecode($message)));
        assert(!empty($message));

        $senderName = 'DestinySphere';
        $recieverName = $user->getName();
        $common->setDBID($this->dbId);
        $_SESSION["dbId"] = $this->dbId;
        $uiid = DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($user->getID(), (int)$this->id_world);

        $messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, 10, $fromUIID);
        DS_Message::AddMessageToUser($messageID, $uiid);
    }

}

$addU = new addUnits();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>