<?PHP
    error_reporting ( E_ALL - (E_NOTICE | E_WARNING | E_DEPRECATED) );
    //error_reporting ( E_ALL );
    //session_start();
    
    assert_options(ASSERT_ACTIVE, 1);
    assert_options(ASSERT_WARNING, 1);
    assert_options(ASSERT_QUIET_EVAL, 0);
    assert_options(ASSERT_BAIL, 1);


    if($_SERVER['SERVER_ADDR'] == '94.242.60.31') {
        define("DBName","portal_unlim");
        $default_page = 316;
    }
    else if ($_SERVER['SERVER_NAME']=='ds-game.su' || $_SERVER['SERVER_NAME']=='www.ds-game.su') {
        define("DBName","pulsar");
        $default_page = 228;
    }
    else {
        define("DBName","portal_ru");
        $default_page = 228;
    }
    define("DBHostName","94.242.60.26");
    define("DBUserName","ruportal");
    define("DBPassword","REDACTED_DB_PASSWORD");

    define("DEFAULT_LANG","ru");
    define("DEFAULT_LONG_LANG","Russian");
    define("DEFAULT_LANG_ID","3");
    
    /****** BEGIN from config.php *********/
    //�������� ����� ��� ������ ������� ������.
    $GLOBALS['default_lang'] = 'ru'; //ru
    

    $default_meta_description = "Default meta";
    $default_meta_keywords = "Default keywords";
    /****** END from config.php *********/

    define("GAME_PATH", $_SERVER['DOCUMENT_ROOT'].'/ds/');
    
    define("CACHE_STORE_PATH", $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/');
    define("CACHE_PAGES_INFO_FILE", $_SERVER['DOCUMENT_ROOT'].'/CachePagesInfo/PageCache.txt');
    
    function getMysqlConnection() {
        
        global $DB_CONNECTION_ID;
        
        if (!$DB_CONNECTION_ID) {
            
            $DB_CONNECTION_ID = mysql_connect(DBHostName, DBUserName, DBPassword);
            
            if (! $DB_CONNECTION_ID) {
                echo $_SERVER['SERVER_NAME']." => "."Can't connect to MySQL server\n";
                die(mysql_error());
            }
            
            $db_selected = mysql_select_db(DBName);
            if(!$db_selected)
            {
                die ('Can\'t use foo : ' . mysql_error());
            }

            //$connId = getMysqlConnection();
            $query = "SET CHARACTER SET cp1251;";
            mysql_query($query, $DB_CONNECTION_ID );
            $query = "SET NAMES cp1251;";
            mysql_query($query, $DB_CONNECTION_ID );
        };
        return $DB_CONNECTION_ID;
    }//END OF FUNCTION

    function closeMysqlConnection() {
        global $DB_CONNECTION_ID;
        @mysql_close($DB_CONNECTION_ID);
    }//END OF FUNCTION

    function getLanguages() {

        $ret = array();
        
        $connId = getMysqlConnection();
        
        $query = "SELECT ID, Title FROM Langs ORDER BY Title DESC";
        
        if ($result = @mysql_query($query, $connId )) {
            
            while ($row = mysql_fetch_row($result)) {
                                
                $ret[$row[0]] = $row[1];
                
            }
            
        }
        
        return $ret;
    }//END OF FUNCTION 
    
    function actionLog($msg, $details='')
    {
        global $session;
        global $adminMenu;

        if(!$adminMenu)
        {
            return;
        }

        $connId = getMysqlConnection();

        $menuID = $adminMenu->curentActive();
        $menuName = $adminMenu->getCurentMenuFullName();
        $query = "INSERT INTO ActionsLog
                  (SID,User_id,Action,AdminMenu_ID, AdminMenuName, details)
                  VALUES 
                 ('".$session->sid."','".$session->user_id."','".addslashes($msg)."','".$menuID."', '".$menuName."', '".mysql_real_escape_string( $details )."')";
        
        if (!mysql_query($query, $connId ))
        {
            die('ERROR: Cant insert record in ActionsLog!'."<br><br>SQL::<br>".$query);     
        }
        
    }//END OF FUNCITON 
    
    
    
    /** ������� �������������� ��������� */
    function local ($name, $params = ''){
        
        $debug_mode = 1;
        $debug = debug_backtrace();
        
        $in = "'".$debug[0]['file']."' (".$debug[0]['line'].")";
        
        if ( empty ($name) ) {
            trigger_error("Emty parameter 'name'", E_USER_ERROR);
        }
        
        $connId = getMysqlConnection();
        
        if ( empty ($connId) ) {
            trigger_error("Emty parameter 'connId'", E_USER_ERROR);
        }
        
        $lang = constant('DEFAULT_LANG');       
        
        $query = "
            SELECT Word
            FROM 
                Words 
            WHERE               
                Word = '".$name."'";                
        $result = mysql_query($query, $connId );     
        
        if(!$result){
            if ($debug_mode == 1) {
                return $name;
            } return 'ERROR! <br> Unknown word "'.$name.'" for localization! <br>'.$in;
            
        }
        
        $row = mysql_fetch_row($result);
        $str = $row[0];
        
        if(!$str){
            if ($debug_mode == 1) {
                return $name;
            } return 'ERROR!<br>Unknown word "'.$name.'" for localization!<br>'.$in;
            
        }
        
        $query = "
            SELECT D.Translation
            FROM 
                (Words as W INNER JOIN Dictionary as D ON (W.id = D.Word_id))
                    INNER JOIN Langs as L ON ( L.ID =  D.Lang_id)
            WHERE 
                L.ID = '".$lang."' AND 
                W.Word = '".$name."'";      
        
        $result = mysql_query($query, $connId );
        
        if(!$result){
            if ($debug_mode == 1) {
                echo $name;
            } die('ERROR!<br> Cant detect Localization for WORD = '.$name."<br> ".$in);
        }
        
        $row = mysql_fetch_row($result);
        $str = $row[0];
        if($params){
            
            if( is_array( $params ) ){
                
                for ( $i = 0; $i < count( $params ); $i++ ) {
                    
                    $pos = strpos ( $str, "%" );
                    
                    $str = substr_replace ( $str, $params[$i], $pos, 1 );
                }
                
            }else{
                
                $str = str_replace("%", $params, $str);
            }
        }
        
        if(!$str){
            if ($debug_mode == 1) {
                $str = $name;
            } else {$str = "ERROR!<br>Word '$name' dont have localization!<br> ".$in;}
            
        }
        
        return $str;
    }//END OF FUNCTION
    
    
    function headerLocation ( $url ) {          
        header("Location: ".$url);
    }//END OF FUNCTION 
    
    
    function JSConfirm ($text, $url){
        return "javascript: if (confirm('".$text."')) document.location.href='".$url."'";
    }//END OF FUNCTION
    
    
    function addSID( $url ) {
        return $url;
    }

    function ShowCMSMessage ( $message_CMS, $mess_type ) {
        switch ( $mess_type ) {
            case 1:
                $mess_ico = 'info_ico.gif';
                $mess_color = 'mess_info';
                break;
            case 2:
                $mess_ico = 'error_ico.gif';
                $mess_color = 'mess_error';
                break;
        }
        $msg_cms = $message_CMS;
        require_once $_SERVER['DOCUMENT_ROOT']."/templates/message/message_template.php";
    }//END OF FUNCTION
    

    /**
    * ������� ���������� �������� �����, �������������� ������� ��� ����� ���� 
    * [:DS_ACTION:] �� �������������� �����, � ���� ������ �������� local($str);
    *
    * @param unknown $string
    * @return ���������� $string
    */
    function getLocalize($str)
    {
        echo "";
        if ( !empty ($str) )
        {
            preg_match_all ("/\[:(.*):\]/i",str_replace('][', "]\n[", $str),$arr);
            if ( !empty ($arr[0][0]) )
            {
                if ( count ($arr[0]) > 1)
                {
                    foreach ($arr[0] as $key => $value)
                    {$str = str_replace($arr[0][$key], local($arr[1][$key]), $str);}
                    return $str;
                } else {return str_replace($arr[0][0], local($arr[1][0]), $str);}
            }
            else {return $str;}
        }
        else {return $str;}
    }//END OF FUNCTION
?>
