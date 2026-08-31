<?PHP

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

if ( !isset ( $GLOBALS["common"] ) ) {
    $common = ClassFactory::getInstance ( "DS_Common" );
    $common->connect();
}

class NewWorld {

    function NewWorld () {

        $this->getRequest ( $_POST );
    }

    function getRequest ( $request ) {

        if ( !isset ( $request["create"] ) ) {
            if ( @ereg ( $_SERVER["PHP_SELF"], $_SERVER["HTTP_REFERER"] ) ) {
                $this->showFirstMenu ( "<div class='FormText'>".local( 'DS_ACTION_SUCCEED ' )."</div>" );
            } else {
                $this->showFirstMenu ( "<div class=\"FormText\">".local( 'DS_NEWWORLD_START_NOTICE' )."</div>" );
            }
        } else {
            if ( !$this->create ( $request["wname"], $request["modif"], $request["modifname"], $request["lang"], $request["type"], $request["DbServerId"], $request['w_id'] ) )
            {
                $this->showFirstMenu ( $this->errormsg );
            } else {
                actionLog( 'Create New World - '.$request["wname"]);
                echo 'Create New World - '.$request["wname"]." complete!";
                //headerlocation ( "index.php" ) ;
                //exit;
            }
        }
    }


    function showFirstMenu ( $str = "" )
    {
        global $common;

        print "<table>";
        print "$str";
        print "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>";
        print "<table border=0 cellSpacing=\"4\" cellPadding=\"0\">";

        print "<tr ><td colspan =3 bgcolor=\"444E6C\">";

        echo "
         <div class=\"FormText\">".local( 'DS_ACTION_SELECT_DB' ).":<br>
            <select name='DbServerId'>";
        foreach ($GLOBALS['dbWorldAccess'] as $k=>$v){
            //              if($k == 0){ continue; }
            echo '<option value="'.$k.'" >'.$v['server'].'</option>';
        }
        echo '</select> <br><br>';

        $sql = "select ID_Language, Language, shortname from DS_Language";
        $res = $common->getResultArray ( "all_languages", $sql, "", "", "" );

        print local( 'DS_ACTION_SELECT_WORLD_LANGUAGE' )." : <br> </div>";
        print "<select name='lang'>";


        if ( !$res ) {
            $this->errormsg = " SYSTEM ERROR - NewWorld::showFirstMenu1";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }
        for ( $i=0; $i<sizeof ( $res ); $i++ ) {

            $opt = "<option value='".$res[$i]['id_language']."'";
            if ( isset ( $_SESSION["lang"] ) ) {
                if ( $res[$i]['id_language'] == $_SESSION["lang"]) $opt .= " selected";
            }
            $opt .= "><span class='gensmall'>".$res[$i]['language']."</span></option>";
            print $opt;
        }

        print "</select></td></tr><tr><td colspan=3 height=10></td></tr>";

        print "<tr><td colspan =3 bgcolor=\"444E6C\"><div class=\"FormText\">".local( 'DS_ACTION_SELECT_WORLD_TYPE' ).": <br> </div>";
        print "<select name='type'>";

        $sql = "select * from ds_worldtype";

        $res = $common->getResultArray ( "all_world_types", $sql, "", "", "" );
        if ( !$res ) {
            $this->errormsg = " SYSTEM ERROR - NewWorld::showFirstMenu10";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }

        for ( $i=0; $i<sizeof ( $res ); $i++ ) {
            $opt = "<option value='".$res[$i]['id_worldtype']."'><span class='gensmall'>".$res[$i]['worldtype']."</span></option>";
            print $opt;
        }

        print "</select></td></tr><tr><td colspan=3 height=10></td></tr>";

        print "<tr><td colspan=3 bgcolor=\"444E6C\"><div class=\"FormText\">".local( 'DS_ACTION_ENTER_WORLD_NAME' ).": <br> <input type='text' name='wname' maxlength='40' value='";

        if ( isset ( $_SESSION["wname"] ) ) {
            print $_SESSION["wname"];
        }
        print "'></div><br><br></td></tr>";
        
        echo "<tr><td>World identifier: <input type='text' name='w_id' size=4></td></tr>";

        $sql = sprintf ( "select modifiername, value, description
                from ds_gamemodifiertemplate" );

        $res = $common->getResultArray ( "modifiers", $sql, "", "", "" );
        if ( !$res ) {
            $this->errormsg = " SYSTEM ERROR - Registration::showRegisterForm2";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }

        for ( $i=0; $i<sizeof ( $res ); $i++ ){
            print "<tr><td bgcolor=\"444E6C\" class=\"FormText\">".$res[$i]['modifiername']."</td>
            <td bgcolor=\"444E6C\"><input type='text' name='modif[".$i."]' size=10 maxlength=20 value='".$res[$i]['value']."'></td><td>".$res[$i]['description']."</td></tr>";
            print "<input type='hidden' name='modifname[".$i."]' value='".$res[$i]['modifiername']."'>";
        }

        print "<tr><td width=70 bgcolor=\"#444E6C\"></td><td><input type='submit' class=Submit value='". local( 'DS_ACTION_CREATE' )."' name='create'></td></tr>";
        print "</table>";
    }

    function create ( $wname, $modif, $modifname, $lang, $type, $dbId, $w_id ) {

        global $common;

        $common->setDbId($dbId);

        if ( $wname == "" || $modif == "" || $modifname == "" || $lang == "" || $type == "" || $w_id == "") {
            $this->errormsg = "missing argument";
            return false;
        }

        require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/Validator.php";

        $v=new ValidateGameModif ( $modif, $modifname );
        if ( !$v->isValid () ) {
            $error = $v->getError ();
        }
        if( isset ( $error ) ){
            $this->errormsg = $error;
            return false;
        }

        $common->executeQuery ( 'begin' );
        $sql = "select * from ds_world";
        $res = $common->getResultArray ( "id_world", $sql, "", "", "" );
        var_dump($res);



        $sql = sprintf ("
                    insert into ds_world
                        (worldname, profi, world_identifier, for_new_players, world_status)
                    values
                        ('%s', 'f', '%s', 't', 4)",
                    $wname, $w_id );

        $res = $common->executeQuery ( $sql );
        if ( $res === false ) {
            $this->errormsg = " SYSTEM ERROR - NewWorld::create1";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }

        $sql = "select max(id_world) as id_world from ds_world";

        $res = $common->getResultArray ( "id_world", $sql, "", "", "" );
        if ( !$res ) {
            $this->errormsg = " SYSTEM ERROR - NewWorld::create2";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }

        $id_world = $res[0]['id_world'];
        
        $sql = "
            INSERT INTO ds_worldcollection 
                (id_world, dbname, max_users, worldname, profi, description, id_language, id_worldtype, multy_access, world_identifier, for_new_players, world_status)
            VALUES
                ($id_world, '".$GLOBALS['dbWorldAccess'][$dbId]['server']."', 0, '$wname', 'f', '', $lang, $type, 0, '$w_id', 'f', 4)
            ";
        $res = $common->executeQuery ( $sql );
        if ( $res === false ) {
            $this->errormsg = " SYSTEM ERROR - NewWorld::createCollection";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }
        
        $sql = sprintf ( "insert into ds_worldhaslanguage ( id_world, id_language )
                values ( %d, %d )",
        $id_world, $lang );

        $res = $common->executeQuery ( $sql );
        if ( $res === false ) {
            $this->errormsg = " SYSTEM ERROR - NewWorld::create3";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }

        $sql = sprintf ( "insert into ds_worldhastype ( id_world, id_worldtype )
                values ( %d, %d )",
        $id_world, $type );

        $res = $common->executeQuery ( $sql );
        if ( $res === false ) {
            $this->errormsg = " SYSTEM ERROR - NewWorld::create3";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }

        $sql = "SELECT * FROM ds_userinstance WHERE id_userinstance = 0";
        $res = $common->getResultArray ( "id_userinstance_zero_is_present", $sql, "", "", "" );
        if ( $res === false )
        {
                $this->errormsg = " SYSTEM ERROR - Unable select userinstance for DestinySphere";
                $this->errormsg .= " !!! ".$sql;
                return false;

        }
        if (sizeof($res) <= 0)
        {
            $sql = sprintf ( "insert into ds_userinstance ( id_user, id_race, id_world, id_userinstance, lastrefresh, window_key ) values (0, 1, %d, 0, NULL, NULL)",
            $id_world);

            $res = $common->executeQuery ( $sql );
            if ( $res === false ) {
                $this->errormsg = " SYSTEM ERROR - Unable create userinstance for DestinySphere";
                $this->errormsg .= " !!! ".$sql;
                return false;
            }

        }

        $sql = "select * from ds_ringtemplate order by ringnumber";

        $res = $common->getResultArray ( "rings", $sql, "", "", "" );
        if ( !$res ) {
            $this->errormsg = " SYSTEM ERROR - NewWorld::create4";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }


        for ( $i=0; $i<sizeof ( $res ); $i++ ) {

            $sql = sprintf ( "insert into ds_ring ( id_world, id_localizedname, ringname, ringnumber ) values ( %d, %d, '%s', %d )",
            $id_world,
            $res[$i]['id_localizedname'],
            $res[$i]['ringname'],
            $res[$i]['ringnumber'] );

            $res2 = $common->executeQuery ( $sql );
            if ( $res2 === false ) {
                $this->errormsg = " SYSTEM ERROR - NewWorld::create5";
                $this->errormsg .= " !!! ".$sql;
                return false;
            }
        }

        for ( $i=0; $i<sizeof ( $modif ); $i++ ) {

            $sql = sprintf ( "insert into ds_gamemodifier (id_world, modifiername, value)
                    values ( %d, '%s', %f )",
            $id_world, $modifname[$i], $modif[$i] );

            $res2 = $common->executeQuery ( $sql );
            if ( $res2 === false ) {
                $this->errormsg = " SYSTEM ERROR - NewWorld::create6";
                $this->errormsg .= " !!! ".$sql;
                return false;
            }

            $_SESSION[$modifname[$i]] = "";
        }

        $_SESSION["wname"] = "";
        $common->executeQuery ( 'commit' );

        return true;
    }

}

$nw = new NewWorld;

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
