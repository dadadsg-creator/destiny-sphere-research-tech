<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2009/01/27 11:00:41 $';

// проверяет различные введенные юзером данные на валидность


class Validator
{
    public $errorMsg;
    public $error_id;
    public $common;

    function Validator ()
    {
        /** Создаем экземпляр common */
        $this->common = DS_Common::Singleton();
        assert($this->common->setDbId(0));

        $this->errorMsg=array();
        $this->validate();
    }

    function setError ($msg)
    {
        $lang = constant('DEFAULT_LANGUAGE');
        $sql = sprintf ( "select ltl.text
                        from ds_systemmessage sm,
                        ds_localizedtextlanguage ltl
                        where sm.id_localizedtext = ltl.id_localizedtext
                        and ltl.id_language = %d
                        and sm.messagename = '%s'",
                        $lang,
                        $msg
                        );
        $res = $this->common->getResultArray ( "systemmessage_id_language_messagename_".$lang."__".$msg, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
        if($res === false)
        {
            $this->setError('SystemError - setError1');
            return false;
        }

        if(sizeof($res) != 0)
        {
            $this->errorMsg[] = $res[0]['text'];
        }
    }

    function isValid()
    {
        if ( sizeof ($this->errorMsg) > 0 )
        {
            return false;
        } else {
            return true;
        }
    }

    function getError ()
    {
        $a = array_pop ( $this->errorMsg );

        if (!empty($this->error_id) || !empty($a) )
        {
            return array($this->error_id, $a );
        }
        else {
            return;
        }
    }
    
    function validate()
    {
        return;
    }
}

class ValidateUser extends Validator {

    public $user;

    function ValidateUser($user)
    {
        $this->user=$user;
        Validator::Validator();
    }

    function validate()
    {

        if ( !preg_match ( '/^[a-zA-Z0-9_]+$/', $this->user ) )
        {
            $this->setError ('LOGIN_HAS_WRONG_SYMBOLS');
            $_SESSION["user"] = "";
            return false;
        }

        if (strlen ( $this->user ) < 3 )
        {
            $this->setError ( 'LOGIN_TOO_SHORT' );
            $_SESSION["user"] = "";
            return false;
        }

        if ( strlen ( $this->user ) > 20 )
        {
            $this->setError ( 'LOGIN_TOO_LONG' );
            $_SESSION["user"] = "";
            return false;
        }

        $sql = sprintf ( "select id_user
                from DS_User
                where upper(UserName) = upper('%s')",
        $this->user);

        $res = $this->common->getResultArray ( "id_user_username", $sql, "", "", "" );

        if ( $res === false )
        {
            $this->setError('SystemError - (select) validate user');
            $_SESSION["user"] = "";
            return false;
        }

        if ( sizeof ( $res ) > 0 )
        {
            $this->setError ( 'LOGIN_NOT_UNIQUE' );
            $_SESSION["user"] = "";
            return false;
        }

        $_SESSION["user"] = $this->user;

        return true;
    }
}

class ValidatePassword extends Validator
{
    public $pass;
    public $conf;
    public $login;

    function ValidatePassword($pass, $conf, $login)
    {
        $this->pass=$pass;
        $this->conf=$conf;
        $this->login = $login;
        Validator::Validator();
    }

    function validate()
    {
        if ( !preg_match ( '/^[a-zA-Z0-9_]+$/', $this->pass ) )
        {
            $this->setError ( 'PASSWORD_HAS_WRONG_SYMBOLS' );
            $_SESSION["pass"] = "";
            return false;
        }

        if ( !preg_match ( '/^[a-zA-Z0-9_]+$/', $this->conf ) )
        {
            $this->setError ( 'CONFIRMATION_HAS_WRONG_SYMBOLS' );
            $_SESSION["conf"] = "";
            return false;
        }

        if (strlen($this->pass) < 6 )
        {
            $this->setError ( 'PASSWORD_TOO_SHORT' );
            $_SESSION["pass"] = "";
            return false;
        }

        if ( strlen ( $this->pass ) > 16 )
        {
            $this->setError ( 'PASSWORD_TOO_LONG' );
            $_SESSION["pass"] = "";
            return false;
        }

        if ( $this->pass!=$this->conf )
        {
            $this->setError ( 'PASSWORD_DONT_MATCH' );
            $_SESSION["conf"] = "";
            return false;
        }

        $file = @file ( "passwordblacklist" );

        for ( $i=0; $i<sizeof ($file ); $i++ )
        {
            if ( $this->pass == trim ( $file[$i] ) )
            {
                $this->setError ( 'PASSWORD_IN_BLACKLIST' );
                $_SESSION["pass"] = "";
                return false;
            }
        }

        if ( $this->pass == $this->login )
        {
            $this->setError ( 'PASSWORD_IN_BLACKLIST' );
            $_SESSION["pass"] = "";
            return false;
        }

        $_SESSION["pass"] = $this->pass;
        $_SESSION["conf"] = $this->conf;

        return true;
    }
}

class ValidateEmail extends Validator
{
    public $email;
    
    public $emailConf;

    function ValidateEmail ( $email, $emailConf )
    {
        $this->email = trim($email);
        $this->emailConf = trim($emailConf);
        Validator::Validator ();
    }

    function validate()
    {

        if ( strlen ( $this->email ) == 0 )
        {
            $this->setError ( 'EMPTY_EMAIL' );
            $_SESSION["email"] = "";
            $_SESSION["email_conf"] = "";
            return false;
        }

        $pattern = "/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/";

        if ( !preg_match ( $pattern, $this->email ) )
        {
            $this->setError ( 'WRONG_EMAIL' );
            $_SESSION["email"] = "";
            $_SESSION["email_conf"] = "";
            return false;
        }
        
        if(function_exists('checkdnsrr'))
        {
            list(, $domain) = explode('@', $this->email);
            if(checkdnsrr($domain, 'A') === false && checkdnsrr($domain, 'MX') === false)
            {
                $this->setError ( 'WRONG_EMAIL' );
                $_SESSION["email"] = "";
                $_SESSION["email_conf"] = "";
                return false;
            }
        }

        if ( strlen ( $this->email ) > 100 )
        {
            $this->setError ( 'EMAIL_TOO_LONG' );
            $_SESSION["email"] = "";
            $_SESSION["email_conf"] = "";
            return false;
        }

        if($this->email != $this->emailConf)
        {
            $this->setError ( 'EMAIL_CONF_WRONG' );
            $_SESSION["email"] = "";
            $_SESSION["email_conf"] = "";
            return false;
        }
        
        $sql = sprintf ( "select ID_User
                from DS_User
                where upper(email) = upper ( '%s' )",
        $this->email );

        $res = $this->common->getResultArray ( "id_user_email", $sql, "", "", "" );
        if ( $res === false )
        {
            $this->setError ( 'SystemError - (select) validate email' );
            $_SESSION["email"] = "";
            $_SESSION["email_conf"] = "";
            return false;
        }

        if ( sizeof ( $res ) > 0 )
        {
            $this->setError ( 'EMAIL_NOT_UNIQUE' );
            $_SESSION["email"] = "";
            $_SESSION["email_conf"] = "";
            return false;
        }

        $_SESSION["email"] = $this->email;
        $_SESSION['email_conf'] = $this->emailConf;

        return true;
    }
}

class ValidateLang extends Validator
{
    public $lang;

    function ValidateLang ( $lang )
    {
        $this->lang=$lang;
        Validator::Validator ();
    }

    function validate()
    {
        $sql = sprintf ( "select id_language,
                shortname
                from ds_language
                where id_language = %d",
        $this->lang );

        $res = $this->common->getResultArray ( "id_user_email", $sql, "", "", "" );
        if ( !$res )
        {
            $this->setError ( 'SystemError - (select) validate lang' );
            $_SESSION["email"] = "";
            return false;
        }

        //Закомментированно за ненадобностью.
/*
        if ( $res[0]['shortname'] == 'E')
        {
            $this->setError ( 'LANGUAGE_NOT_AVAILABLE' );
            $_SESSION["lang"] = "";
            return false;
        }
*/
        $_SESSION["lang"] = $this->lang;

        return true;
    }
}

class ValidateCheckBox1 extends Validator
{
    public $chname;

    function ValidateCheckBox1 ( $chname )
    {
        $this->chname = $chname;
        Validator::Validator();
    }

    function validate ()
    {
        if ( $this->chname == "0" )
        {
            $this->setError ( 'CHECK_BOX1' );
            return false;
        }
        return true;
    }
}


class ValidateComb extends Validator
{
    public $comb;

    function ValidateComb ($comb)
    {
        $this->comb=$comb;
        Validator::Validator();
    }

    function validate()
    {

        if ( !preg_match('/^[a-zA-Z0-9_]+$/', $this->comb ) )
        {
            $this->setError ( 'COMBNAME_HAS_WRONG_SYMBOLS' );
            $_SESSION["comb"] = "";
            return false;
        }

        if ( strlen ( $this->comb ) < 3 )
        {
            $this->setError ( 'COMBNAME_TOO_SHORT' );
            $_SESSION["comb"] = "";
            return false;
        }

        if ( strlen ( $this->comb ) > 20 )
        {
            $this->setError ( 'COMBNAME_TOO_LONG' );
            $_SESSION["comb"] = "";
            return false;
        }

        $sql = sprintf ( "select ID_CombName
                from DS_CombName
                where upper(CombName) = upper('%s')",
        $this->comb );

        $res = $this->common->getResultArray ( "id_user_email", $sql, "", "", "" );
        if ( $res === false ) {
            $this->setError ( 'SystemError - (select) validate combname' );
            $_SESSION["comb"] = "";
            return false;
        }

        if ( sizeof ( $res ) > 0){
            $this->setError ( 'COMBNAME_NOT_UNIQUE' );
            $_SESSION["comb"] = "";
            return false;
        }

        $_SESSION["comb"] = $this->comb;

        return true;
    }
}

class ValidateLogin extends Validator
{
    public $login;
    public $pass;

    function ValidateLogin ( $login, $pass )
    {
        $this->pass=$pass;
        $this->login=$login;
        Validator::Validator ();
    }

    function validate()
    {

        if ( strlen ( $this->login ) == 0 )
        {
            $this->setError ( 'EMPTY_LOGIN' );
            $this->error_id = 1;
            //          $_SESSION["try"]++;
            return false;
        }

        if (strlen ( $this->pass ) == 0 )
        {
            $this->setError ( 'EMPTY_PASSWORD' );
            $this->error_id = 2;
            //          $_SESSION["try"]++;
            return false;
        }

        if ( !preg_match ('/^[a-zA-Z0-9_\!\@\#\$\%\^\&\*\(\)\;\:\.\,\?]+$/',$this->login ) )
        {
            $this->setError ( 'LOGIN_HAS_WRONG_SYMBOLS' );
            $this->error_id = 3;
            return false;
        }

        if ( !preg_match ( '/^[a-zA-Z0-9_\!\@\#\$\%\^\&\*\(\)\;\:\.\,\?]+$/', $this->pass ) )
        {
            $this->setError ( 'PASSWORD_HAS_WRONG_SYMBOLS' );
            $this->error_id = 4;
            return false;
        }

        $sql = sprintf ( "select *
                from DS_User
                where UserName = '%s'
                and password = '%s'",
        $this->login,
        md5($this->pass) );

        $res = $this->common->getResultArray ( "id_user_login_password", $sql, "", "", "" );
        if ( $res === false ) {
            $this->setError ( 'SystemError - (select) validate login password' );
            $this->error_id = 7;
            return false;
        }

        if ( sizeof ( $res ) == 0)
        {
            $this->setError ( 'WRONG_PASSWORD' );
            $this->error_id = 5;
            return false;
        }
        $accountactive = 't';
        if ($res[0]['accountactive']=='f' || $res[0]['activate_mail']==0 && $_COOKIE['partner']==139) $accountactive = 'f';

        if ( $accountactive === 'f')
        {
            $this->setError ( 'INACTIVE_ACOUNT' );
            $this->error_id = 6;
            return false;
        }
        return true;
    }
}

class ValidateUiid extends Validator
{
    public $uiid;

    function ValidateUiid ($uiid)
    {
        $this->uiid=$uiid;
        Validator::Validator();
    }

    function validate()
    {
        $sql = sprintf ( "select id_userinstance
                from ds_userinstance
                where id_userinstance = %d",
                $this->uiid);

        $res = $this->common->getResultArray ( "id_useruserinstance", $sql, "", "", "" );
        if ( $res === false )
        {
            $this->setError ( 'SystemError - (select) validate login uiid' );
            return false;
        }

        if ( sizeof ( $res ) != 1)
        {
            $this->setError ( 'SystemError - uiid not valid' );
            return false;
        }

        return true;
    }
}


class ValidateWorldName extends Validator
{
    public $worldname;

    function ValidateWorldName ($worldname)
    {
        $this->worldname=$worldname;
        Validator::Validator();
    }

    function validate()
    {

        if ( !preg_match ('/^[a-zA-Z0-9_]+$/',$this->worldname ) )
        {
            $this->setError ( 'WORLDNAME_HAS_WRONG_SYMBOLS' );
            $_SESSION["wname"] = "";
            return false;
        }

        if ( strlen ( $this->worldname ) < 3 )
        {
            $this->setError ( 'WORLDNAME_TOO_SHORT' );
            $_SESSION["wname"] = "";
            return false;
        }

        if ( strlen ( $this->worldname ) > 20 )
        {
            $this->setError ( 'WORLDNAME_TOO_LONG' );
            $_SESSION["wname"] = "";
            return false;
        }

        $sql = sprintf ( "select id_world
                from DS_World
                where upper(WorldName) = upper('%s')",
        $this->worldname );

        $res = $this->common->getResultArray ( "id_world", $sql, "", "", "" );
        if ( $res === false ) {
            $this->setError ( 'SystemError - validate worldname' );
            return false;
        }

        if ( sizeof ( $res ) > 0 )
        {
            $this->setError ( 'WORLDNAME_NOT_UNIQUE' );
            $_SESSION["wname"] = "";
            return false;
        }

        $_SESSION["wname"] = $this->worldname;

        return true;
    }
}

class ValidateGameModif extends Validator
{
    public $modif;

    function ValidateGameModif ( & $modif, $modifname)
    {
        $this->modif = & $modif;
        $this->modifname = $modifname;
        Validator::Validator();
    }

    function validate()
    {

        for ( $i=0; $i<sizeof ( $this->modif ); $i++ )
        {

            if ( strlen ( $this->modif[$i] ) == 0 )
            {
                $this->setError ('EMPTY_MODIF_VALUE');
                return false;
            }

            $this->modif[$i] = str_replace ( ",", ".", $this->modif[$i] );

            if ( !is_numeric ( $this->modif[$i] ) )
            {
                $this->setError ( 'NOT_NUMERIC_VALUE' );
                $_SESSION[$this->modifname[$i]] = "";
                return false;
            }

            $_SESSION[$this->modifname[$i]] = $this->modif[$i];
        }

        return true;
    }
}

class ValidateAdminLogin extends Validator
{
    public $login;
    public $pass;

    function ValidateAdminLogin ( $login, $pass )
    {
        $this->pass=$pass;
        $this->login=$login;
        Validator::Validator ();
    }

    function validate()
    {

        if ( strlen ( $this->login ) == 0 )
        {
            $this->setError ( 'EMPTY_LOGIN' );
            return false;
        }

        if ( strlen ( $this->pass ) == 0 )
        {
            $this->setError ( 'EMPTY_PASSWORD' );
            return false;
        }

        if ( !preg_match ( '/^[a-zA-Z0-9_]+$/',$this->login ) )
        {
            $this->setError ( 'LOGIN_HAS_WRONG_SYMBOLS' );
            return false;
        }

        if ( !preg_match ( '/^[a-zA-Z0-9_]+$/',$this->pass ) )
        {
            $this->setError ( 'PASSWORD_HAS_WRONG_SYMBOLS' );
            return false;
        }

        $sql = sprintf("select *
                from DS_User
                where UserName = '%s'
                and password = '%s'
                and admin = true",
        $this->login,
        md5($this->pass));
        $res = $this->common->getResultArray ( "id_user", $sql, "", "", "" );

        if ( $res === false ) {
            $this->setError ( 'SystemError - validate login' );
            return false;
        }

        if ( sizeof ( $res ) == 0 )
        {
            $this->setError ( 'WRONG_PASSWORD' );
            return false;
        }
        return true;
    }
}
?>
