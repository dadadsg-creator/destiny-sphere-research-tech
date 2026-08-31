<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";


if ( !isset ( $GLOBALS["common"] ) ) {
    $common = ClassFactory::getInstance ( "DS_Common" );
    $common->connect();
}

class GameModifiers {
    
    var $def_world;
    var $def_base;
    var $errormsg;
    var $wid;
    var $dbId;
    var $userManager;
    
    function GameModifiers ($userManager) 
    {
        $this->userManager = $userManager;
        $this->getRequest ( $_POST );
    }

    function getRequest ( $request ) {

        if ( isset ( $request["next"] ) ) {
            
            $dataTmp = explode('_', $request['id_world']);
            
            $request['wid'] = $dataTmp[0];
            $request['dbId'] = $dataTmp[1];
            $this->showFirstMenu ( "<div class=\"FormText\"></div>" );
            $this->showSecondMenu( $request['dbId'], $request['wid'] );
            
        }elseif ( isset( $request["modif"] ) ) {
            
            if ( !$this->updateModifiers( $request['dbId'], $request['wid'], $request['modif'], $request['modifname'], $request['modifid'] ) ) {

                $this->showFirstMenu ( $this->errormsg );
                $this->showSecondMenu( $request['dbId'], $request['wid'] );
                    
            } else {
                $this->showFirstMenu ( $this->errormsg );
                $this->showSecondMenu( $request['dbId'], $request['wid'] );
                exit;
            }
            
        }else{
            
            if ( @ereg ( $_SERVER["PHP_SELF"], $_SERVER["HTTP_REFERER"] ) ) {
                
                $this->showFirstMenu ( "<div class='FormText'>".local( 'DS_ACTION_SUCCEED ' )."</div>" );
                
            } else {
                
                $this->showFirstMenu ( "<div class=\"FormText\"></div>" );
            }
        }
        
        return true;
    }

    function showFirstMenu ( $str = "") 
    {
        global $common;

        empty ($str) ? ShowCMSMessage($str,1) : "";
        
        print "
        <table>
        <form action='".$_SERVER["PHP_SELF"]."' method='POST'>
        
        <table cellSpacing='0' cellPadding='0'>
            <tr>
                <td bgcolor='444E6C'>
        
                    <div class='FormText'>";
        
        echo "<strong>".local( 'DS_USER_WORLD' ).":&nbsp;&nbsp;</strong></td><td><select name='id_world'>";
        
        $sql = sprintf ( "select * from ds_world" );
        $res = $common->getMultySql ($sql);
        
        if ( !$res ) {
            $this->errormsg = "GameModifier::showFirstMenu";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }
        
        $this->def_base  = $res[0]['dbId'];
        $this->def_world = $res[0]['id_world'];
        
        for ( $i=0; $i<sizeof ( $res ); $i++ ) {
            if ($_REQUEST['id_world'] == $res[$i]['id_world']."_".$res[$i]['dbId']) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            echo "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."' $selected>".$res[$i]['worldname']."</option>";
        }
        
        echo "</select></div></td>
            <td>&nbsp;&nbsp;<input type='submit' class='Submit' value='". local( 'DS_ACTION_SUBMIT' )."' name='next'></td>
        </tr>
        </form>
        </table><br>
        ";
        
        return true;
    }
    
    function showSecondMenu( $dbId='', $wid='' )
    {
        
        global $common;
        
        
        $common->setDbId($dbId);

        if ( $wid == "" ) {
            $this->errormsg = "missing argument";
            return false;   
        }
        
        //Вы бираем значение всех модифаеров
        $sql = 
            "
            SELECT
                            id_modifier,
                            modifiername,
                            value
                        FROM
                            ds_gamemodifier 
                        WHERE
                            id_world = $wid
                        ORDER BY
                            modifiername
            ";
            
        $modifiers = $common->getResultArray ( "", $sql, "", "", "" );      
        
        if (!empty($common->errormsg))
        {
            echo "<br><font color='red'>Can't get modifiers: $common->errormsg</font><br>"; 
            return; 
        }
        
        $resArr = array();
        
        $common->setDbId(0);
        
        foreach ($modifiers as $modifierKey => $modifierdata)
        {
            //Вы бираем его описание и тип

            $sql =
            "SELECT
                            mtpl.description,
                            mt.typename,
                            mt.id_modifiertype
                        FROM
                            ds_gamemodifiertemplate mtpl
                        LEFT JOIN ds_gamemodifiertype mt ON (mtpl.id_modifiertype = mt.id_modifiertype)
                        WHERE 
                            mtpl.modifiername = '".$modifierdata['modifiername']."'
                    
                    ";

            $modifierDesc = $common->getResultArray ( "", $sql, "", "", "" );
    
            if (!empty($common->errormsg))
            {
                echo "<br><font color='red'>Can't get desc of modifier '".$data['modifiername']."': $common->errormsg</font><br>";
                return;
            }                   
            
            if (empty($modifierDesc[0]['typename'])) {
                $modifierDesc[0]['typename'] = '_OUT_OF_GROUP';
            }
            
            $resArr[$modifierDesc[0]['typename']][$modifierKey]['id_modifier'] = $modifierdata['id_modifier'];
            $resArr[$modifierDesc[0]['typename']][$modifierKey]['modifiername'] = $modifierdata['modifiername'];
            //$resArr[$modifierDesc[0]['typename']][$modifierKey]['id_modifiertype'] = $modifierDesc[0]['id_modifiertype'];
            $resArr[$modifierDesc[0]['typename']][$modifierKey]['description'] = $modifierDesc[0]['description'];
            $resArr[$modifierDesc[0]['typename']][$modifierKey]['value'] = $modifierdata['value'];
        }       

        echo '<script  type=text/javascript>
                function display(elem) {
                                if (document.getElementById(elem).style.display != "") { 
                                    document.getElementById(elem).style.display = "";
                                }
                                else {
                                    document.getElementById(elem).style.display = "none";
                                }
                                return false;
                            }
                window.onload = function() {';
                
        foreach ($resArr as $typename => $typedata)
        {
            echo ' document.getElementById("'.$typename.'").style.display = "none"; ';
        }
        echo        '}
                </script>';
        
        echo "
                    <form action='".$_SERVER["PHP_SELF"]."' method='POST'>
                    <input type='hidden' name='dbId' value='".$dbId."'>
                    <input type='hidden' name='wid' value='".$wid."'>
                    <input type='hidden' name='id_world' value='".$wid."_".$dbId."'>";
        
        echo "<table cellSpacing='5' cellPadding='0' border='0' width='100%'>";
        
        //Сортируем по группе модифаеров
        ksort($resArr);
    
        foreach ($resArr as $typename => $typedata)
        {       
            //Считаем количество модифаеров в данной группе
            if ($typename == '_OUT_OF_GROUP') {
                $where = " gt.typename IS NULL ";
            } else {
                $where = " gt.typename = '$typename' ";         
            }
            
            $common->setDbId(0);
            $sql =
                    "SELECT
                            count(gtpl.modifiername) as modifier_count
                        FROM
                            ds_gamemodifiertemplate gtpl
                            LEFT JOIN ds_gamemodifiertype gt ON (gtpl.id_modifiertype = gt.id_modifiertype)
                            
                        WHERE
                            $where
                    ";

            $res = $common->getResultArray ( "", $sql, "", "", "" );
            $count = $res[0]['modifier_count'];
            
            if (!empty($common->errormsg))
            {
                echo "<br><font color='red'>Can't get count of modifier group $typename: $common->errormsg</font><br><pre>$sql";
                return;
            }       
            
            echo '<tr>
                    <td bgcolor="444E6C" class="FormText">
                    <a class="city" href="#" onclick="return display(\''.$typename.'\')"><b>'.$typename.' ('.$count.')</b></a></td>
                </tr>
                ';
    
            echo '
                        <tr id="'.$typename.'"><td>';
            
            
            echo '<table border=0 width="100%">';
            
            if (count($typedata) > 0) 
            {
                foreach ($typedata as $modifierKey => $modifierData)
                {
                    $val = $modifierData['value'];
                    if(strpos($val, 'e') !== false)
                    {
                        $val = number_format($val, 10, '.', '');
                    }

                    
                    echo '
                        <tr>';
                    
                    echo "
                        
                            <td bgcolor=\"444E6C\" class=\"FormText\" width='400'>".$modifierData['modifiername']."</td>
                            <td bgcolor=\"444E6C\" width='15'><input type='text' name='modif[]' size=14 maxlength=14 value='".$val."'></td>
                            <td bgcolor=\"444E6C\" class=\"FormText\">".@$modifierData['description']."</td>
                            <input type='hidden' name='modifid[]' value='".$modifierData['id_modifier']."'>
                            <input type='hidden' name='modifname[]' value='".$modifierData['modifiername']."'>
                        </tr>
                        
                        ";
                    
                    //Кладем модифаер в сессию
                    $_SESSION['ADMIN_GAME_MODIFAERS'][$modifierData['modifiername']] = $val;
                }
            }
                
            echo '</table>';
            echo '</td></tr>
            <tr><td>&nbsp;</td></tr>';                  
        }       
        
        echo "
        <tr>
            
            <td><input type='submit' class=Submit value='". local( 'DS_ACTION_SUBMIT' )."' name='submit'></td>
            <td></td>
        </tr>
        </table></form>";

        return true;
    }

    function updateModifiers ( $dbId='', $wid='', $modif='', $modifname='', $modifid=''  ) {
        
        global $common;

        $common->setDbId($dbId);

        if ( $wid == "" || $modif == "" || $modifname == "" || $modifid == "" ) {
            $this->errormsg = "missing argument";
            return false;   
        }

        require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/Validator.php";

        $v = new ValidateGameModif($modif, $modifname);
        if(!$v->isValid()) 
        {
            $error = $v->getError ();
        }
        
        if(isset($error))
        {
            $this->errormsg = $error;
            return false;
        }       

        $common->executeQuery ( 'begin' );
        
        $changetAttributes = array();

        for($i=0; $i<sizeof($modif); $i++) 
        {
            if($_SESSION['ADMIN_GAME_MODIFAERS'][$modifname[$i]] == $modif[$i])
            {
                continue;
            }
            
            $sql = "
                UPDATE
                    ds_gamemodifier
                SET
                    value = '".$modif[$i]."'
                WHERE
                    id_world = ".$wid."
                    AND 
                    modifiername ='".$modifname[$i]."'";                    
            $res2 = $common->executeQuery($sql);
            assert(empty($common->errormsg));
            $changetAttributes[$modifname[$i]] = array
            (
                'OLD_VALUE' => $_SESSION['ADMIN_GAME_MODIFAERS'][$modifname[$i]],
                'NEW_VALUES' => $modif[$i]
            );  
        }
        
        $common->executeQuery('commit');
        assert(empty($common->errormsg));   
        //Сбрасывем кеши
        $cache = DS_Cache::Singleton();     
        $cache->delete($dbId."_GAME_MODIFAERS_".$wid);
        
        if(count($changetAttributes) > 0)
        {           
            $sql = "SELECT * FROM ds_world WHERE id_world = ".$wid;
            $resTmp = $common->getResultArray("", $sql, "", "", "");
            assert(empty($common->errormsg));
            
            $worldName = $resTmp[0]['worldname'];
                        
            $latter  = "Changing GameModifaers in world '$worldName'\n";
            $latter .= "Changing by administrator: ".$this->userManager->GetUserName()."\n";
            $latter .= "IP: ".@$_SERVER['REMOTE_ADDR']."\n";
            $latter .= "Server: ".@$_SERVER['SERVER_NAME']."\n";
            $latter .= "Attributes changes:\n";
            foreach($changetAttributes as $name => $values)
            {
                actionLog('Changing GameModifaer ['.$name.'] FROM '.$values['OLD_VALUE'].' TO '.$values['NEW_VALUES'].' in world ['.$worldName.']');    
                $latter .= "[".$name."] FROM ".$values['OLD_VALUE']." TO ".$values['NEW_VALUES']."\n";  
            }           
            
            $title = "Change game modifaers in world '$worldName'";
            $title = "[DS] Change game modifaers [".str_replace('_', '', constant('CACHING_PREFIX'))."]";
             
            mail('fedchin@destinysphere.com', $title, $latter);                 
            mail('dmitri@destinysphere.com', $title, $latter);                  
            mail('enzo@ru.destinysphere.com', $title, $latter); 

            $this->errormsg = "Changing Modifiers Done!";   
        }
        return true;                
    }
}

$nw = new GameModifiers($UserManager);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
