<?PHP $CVS_Version = '$Revision$ ; $Date$';
require_once "Main.php";
//require_once "IT.php";

if ( !SU_GameSessionWork::SessionContinue() ) {
    $str = "Location: index_login.php";
    Header ( $str );
    exit;
}

SU_ClientKey::checkKey(false);
SU_ClientKey::setKey(false);

if ( empty ( $_SESSION["uid"] ) ) {
    $str = "Location: index_login.php";
    Header ( $str );
    exit;
}

if ( empty ( $_SESSION["uiid"] ) ) {
    $str = "Location: index_start.php";
    Header ( $str );
    exit;
}

if ( !isset ( $GLOBALS["common"] ) ) {
    $common = ClassFactory::getInstance("DS_Common");
    if ( !$common->setDbId($_SESSION['dbId']) ) {
        echo "DB-connection failed";
        exit;
    }
}

class LoginQueue
{
    var $errormsg;
    var $logged_in;
    var $pos;
    var $max_logged_in;
    var $total_trans;

    function LoginQueue ()
    {
        if(!$this->proceedLogin())
        {
            echo $this->errormsg;
        }
    }

    function proceedLogin()
    {
        global $common;
        if(isset($_SERVER["HTTP_REFERER"]))
        {
            if(!(ereg("index_start.php", $_SERVER["HTTP_REFERER"])||
            ereg("reg_wizard.php", $_SERVER["HTTP_REFERER"])||
            ereg($_SERVER["PHP_SELF"], $_SERVER["HTTP_REFERER"])))
            {
                $str = "Location: index_start.php";
                if(session_id())
                {
                    $str .= "?".session_name()."=".session_id();
                }
                Header($str);
                exit;
            }
        }

        // get number of transactions
        $sql = "
                select 
                    count(*) as total_trans
                from 
                    pg_stat_activity
                where 
                    datname = '".$common->getDBName()."'
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        if(!$res)
        {
            $this->errormsg = "SystemError - Queue::usersLogged1";
            return false;
        }

        $this->total_trans = $res[0]['total_trans'];

        // get game modifier value for max logged in
        if(!@$_SESSION["max_logged_in"])
        {
            $sql = sprintf("
                    select 
                        value
                    from 
                        ds_gamemodifier gm,
                        ds_userinstance ui
                    where 
                        gm.id_world = ui.id_world
                        and ui.id_userinstance = %d
                        and gm.modifiername = 'MAX_USERS_LOGGED_IN'
                    ",
            $_SESSION["uiid"] );
            $res = $common->getResultArray ( "game_modifier_max_users_logged_in_id_userinstance_".$_SESSION["uiid"], $sql, "", "", "GLOBAL_CACHE" );
            if(!$res)
            {
                $this->errormsg = "SystemError - Queue::usersLogged2";
                return false;
            }

            $this->max_logged_in = $res[0]['value'];
            $_SESSION["max_logged_in"] = $res[0]['value'];
        } else {
            $this->max_logged_in = $_SESSION["max_logged_in"];
        }

        //Ставим клеймо того что человек побывал в очереди
        $_SESSION['INDEX_QUEUE_MEMBER'] = true;
        $_SESSION['INDEX_QUEUE_PASSED'] = false;

        if($this->total_trans <= $this->max_logged_in)
        {
            //Ставим печать о том что человек честно прошел очередь
            $_SESSION['INDEX_QUEUE_PASSED'] = true;

            $sql = "delete from ds_loginqueue where lastupdate < now() - interval '5 minutes'";
            $res = $common->executeQuery ( $sql );

            if(constant("DISABLE_DETECT_PLAYER"))
            {
                $script = 'index.php';
            } else {
                $script = 'detect_player.php';
            }

            $str = "Location: ".$script."?ck=".$_SESSION['client_key'];
            if ( session_id() ) $str .= "&".session_name()."=".session_id();
            Header ( $str );
            exit;
        } else { // show login queue window
            $this->showLoginQueue ();
        }
    }

    function showLoginQueue ()
    {
        $waitTime = $this->total_trans + 5;
        $overload = round($this->total_trans / $this->max_logged_in * 100);

        require_once ( "./language/".constant("DEFAULT_LANGUAGE_SHORT")."/index_queue.lang" );

        $tpl = new HTML_Template_IT ("./templates");
        $tpl->loadTemplatefile ( "index_queue.tpl", true, true );

        //var_dump($lang);

        foreach ( $lang as $key=>$val )
        {
            $tpl->setVariable ( $key, $lang[$key] ) ;
        }

        $tpl->setVariable ( 'WAIT_TIME', $waitTime+100 ) ;
        $tpl->setVariable ( 'SIDIX', session_id() ) ;
        $tpl->setVariable ( 'OVERLOAD', $overload ) ;

        $tpl->show ();
    }
}

$queue = new LoginQueue;

?>