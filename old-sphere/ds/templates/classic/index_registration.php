<?PHP
//Проверям не закрыта ли игра
require_once('include/GameDown.inc.php');
if (GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}

require_once('Main.php');
//include_once($_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/index_start_errors.lang");
require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";
require_once("IT.php");
require_once('classes/Validator.class.php');

$common = DS_Common::CreateForCurentDB(0);

class Registration
{
    private $errorMsg;

    private $common;

    public function __construct()
    {
        $this->common = DS_Common::CreateForCurentDB(0);

        if(isset($_GET) && key_exists('action', $_GET))
        {
            if($_GET['action'] == 'activate')
            {
                if(!$this->activateUser())
                {
                    echo $this->errorMsg;
                }
            }
        } elseif(!$this->getPostData($_POST)) {
            echo $this->errorMsg;
        }
    }

    private function getPostData($data)
    {
        $common = $this->common;

        $common->setDbId(0);
        $common->executeQuery("begin");

        $language = constant("DEFAULT_LANGUAGE_SHORT");

        if(sizeof($data) == 0)
        {
            if(!$this->showRegisterForm())
            {
                return false;
            }
        } elseif(isset($data['register'])) {
            if(isset($_SERVER["HTTP_REFERER"]))
            {
                if(!ereg($_SERVER["PHP_SELF"], $_SERVER["HTTP_REFERER"]) && !ereg('index_login.php', $_SERVER["HTTP_REFERER"]))
                {
                    $str = "Location: index_registered.php";
                    Header($str);
                    return false;
                }
            }

            $ramblerRegistration = false;
            if(isset($data['rambler']))
            {
                if($data['rambler'])
                {
                    $ramblerRegistration = true;
                }
            }

            $v[0]=new ValidateUser($_POST['user']);
            $v[1]=new ValidatePassword($_POST['pass'],$_POST['conf'], $_POST['user']);
            $v[2]=new ValidateEmail($_POST['email'], $_POST['emailconf']);
            $v[3]=new ValidateLang($_POST['lang']);
            $v[4]=new ValidateCheckBox1($_POST['checkbox']);

            $errorMsg = "";

            foreach($v as $validator)
            {
                if(!$validator->isValid())
                {
                    $error=$validator->getError();
                    if ( !isset ( $error[1] ) ) $errorMsg = "";
                    $errorMsg.="<li>".$error[1]."</li>\n";
                }
            }

            $v = new Validator();

            if(isset($_POST['ref_user']) && !empty($_POST['ref_user']))
            {
                $recomendErr = false;

                if(!VD_UserName::Validate($_POST['ref_user']))
                {
                    $recomendErr = true;
                }

                if(!$recomendErr)
                {
                    $recomendUserId = VD_UserName::CheckIfExists($_POST['ref_user'], $common);

                    if(!$recomendUserId)
                    {
                        $recomendErr = true;
                    }
                }

                if($recomendErr)
                {
                    $v->setError('RECOMEND_USER_ERROR');
                    $error = $v->getError();
                    if(isset($error[1]))
                    {
                        $errorMsg .= "<li>".$error[1]."</li>\n";
                    }
                }
            } else {
                $recomendUserId = 0;
            }

            if(isset($errorMsg) && !empty($errorMsg))
            {
                $this->showRegisterForm($errorMsg, $ramblerRegistration);
            } else {
                //Отправка оповещения по почте
                if(constant("ACTIVATION_EMAIL_ENABLED") == true && constant("REGISTRATION_ENABLED") === "true" && !$ramblerRegistration)
                {
                    require_once("language/".$language."/reg_latter.lang");
                    require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

                    // создаем объект смарти
                    $tpl = new Smarty;
                    $tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
                    $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
                    $tpl->compile_check = true;
                    $tpl->debugging = false;

                    $tpl->assign('lang', $lang);

                    $tpl->assign('LOGIN', $_POST['user']);
                    $tpl->assign('PASSWORD', $_POST['pass']);

                    $tpl->assign('URL', GAME_HOST.'/ds/index_registration?action=activate&key='.md5($_POST['user']));

                    $latter = $tpl->fetch('reg_latter.tpl');

                    $headers = "From: \"".$lang['LATTER_FROM']."\" <noreply@destinysphere.com>\n".
                    "Return-Path: noreply@destinysphere.com \n".
                    "Content-Type: text/plain; charset=\"" . constant("DEFAULT_CHARSET") . "\"\n";

                    mail($_POST['email'], $lang['LATTER_SUBJECT'], $latter, $headers);
                }

                if($ramblerRegistration)
                {
                    $mailActivate = 1;
                } else {
                    $mailActivate = 0;
                }

                $from_partner = $_POST['with_rambler_id'] == 1 ? 1 : 0;

                $sql = "
                    INSERT INTO DS_User
                        (
                            ID_Language,
                            UserName,
                            Password,
                            Email,
                            registrationdate,
                            lastrefresh,
                            vacationmode,
                            accountactive,
                            admin,
                            time_delta,
                            activate_mail,
                            from_partner
                        )
                    VALUES
                        (
                            ".$_POST['lang'].",
                            '".$_POST['user']."',
                            '".md5($_POST['pass'])."',
                            '".$_POST['email']."',
                            'now()',
                            'now()',
                            false,
                            ".constant("REGISTRATION_ENABLED").",
                            false,
                            ".constant("DEFAULT_TIMEDELTA").",
                            ".$mailActivate.",
                            ".$from_partner."
                        )
                    ";

                $res = $common->executeQuery($sql);

                if($res === false)
                {
                    $this->errorMsg = "SYSTEM ERROR - Registration::getPostData1 ";
                    return false;
                }

                $sql = sprintf("select id_user
                        from ds_user
                        where username = '%s'
                        and password = '%s'",
                    $_POST['user'],
                    md5 ( $_POST['pass']));

                $res = $common->getQueryArray($sql);
                if($res == "")
                {
                    $this->errorMsg = "SYSTEM ERROR - Registration::getPostData2";
                    return false;
                }

                $id_user = $res[0]['id_user'];
                $common->executeQuery("commit");

                $sql = sprintf("insert into ds_userprovidedbypartner ( id_user, id_partner )
                        values ( %d, %d )",
                    $id_user, constant("DEFAULT_PARTNER"));

                $res2 = $common->executeQuery($sql);
                if($res2 === false)
                {
                    $this->errorMsg = "SYSTEM ERROR - Registration::getPostData3";
                    return false;
                }

                if($recomendUserId)
                {
                    if(!$this->_addRecomendUser($id_user, $recomendUserId))
                    {
                        return false;
                    }
                }

                /** forum registration part **/

                if(constant("FORUM_REGISTRATION_ENABLED") == true && $common->connect('forum'))
                {
                    $res = $common->executeQuery('begin');

                    $sql = sprintf ( "insert into %s_groups (group_name, group_description, group_single_user, group_moderator)
                            values ('', 'Personal User', 1, 0)", constant('FORUM_PREFIX') );

                    $res = $common->executeQuery ( $sql );

                    if($res === false)
                    {
                        $this->errorMsg = "SYSTEM ERROR - Registration::getPostData2-2".$sql." | ".pg_last_error();
                        return false;
                    }

                    $sql = "select max(group_id) as gid from ".constant('FORUM_PREFIX')."_groups";

                    $res = $common->getResultArray("max_group_id", $sql, "", "", "");

                    if(!$res)
                    {
                        $this->errorsg = "SYSTEM ERROR - Registration::getPostData4";
                        return false;
                    }

                    $gid = $res[0]['gid'];

                    $sql = sprintf("insert into %s_users
                            (user_id, username, user_regdate, user_password, user_email, user_icq, user_website,
                            user_occ, user_from, user_interests, user_sig, user_sig_bbcode_uid, user_avatar,
                            user_avatar_type, user_viewemail, user_aim, user_yim, user_msnm, user_attachsig,
                            user_allowsmile, user_allowhtml, user_allowbbcode, user_allow_viewonline, user_notify,
                            user_notify_pm, user_popup_pm, user_timezone, user_dateformat, user_lang, user_style,
                            user_level, user_allow_pm, user_active, user_actkey)
                            values ( DEFAULT, '%s', '%s', '%s', '%s', '', '', '', '', '', '', '', '', 0, 0, '', '', '',
                            0, 1, 0, 1, 1, 0, 1, 1, %s, 'D M d, Y G:i', '%s', 1, 0, 1, 1, '')",
                        constant('FORUM_PREFIX'), $_POST['user'], time(), md5 ( $_POST['pass'] ), $_POST['email'], constant("FORUM_TIMEZONE"), constant("FORUM_LANGUAGE") );

                    $res = $common->executeQuery($sql);

                    if($res === false)
                    {
                        $this->errorsg = "SYSTEM ERROR - Registration::getPostData5";
                        return false;
                    }

                    $sql = "select max(user_id) as uid from ".constant('FORUM_PREFIX')."_users";

                    $res = $common->getResultArray ( "max_user_id", $sql, "", "", "" );
                    if(!$res)
                    {
                        $this->errorMsg = "SYSTEM ERROR - Registration::getPostData6";
                        return false;
                    }

                    $uid = $res[0]['uid'];

                    $sql = sprintf("insert into %s_user_group
                            (user_id, group_id, user_pending)
                            values (%d, %d, 0)",
                        constant('FORUM_PREFIX'), $uid, $gid);

                    $res = $common->executeQuery($sql);

                    if($res === false)
                    {
                        $this->errorMsg = "SYSTEM ERROR - Registration::getPostData7";
                        return false;
                    }

                    $res = $common->executeQuery('commit');
                }

                //@session_destroy();
                $str = "Location: index_registered.php";

                if($ramblerRegistration)
                {
                    $ramblerLogin = new GE_RamblerLogin();
                    if($ramblerLogin->hasRSID())
                    {
                        if($ramblerLogin->execLogin())
                        {
                            $str = "Location: index_login.php?rambler_id_login=1&".session_name()."=".session_id();
                        }
                    }
                }

                $res = "";
                Header($str);
                return true;
            }

            return true;
        }
    }

    private function _addRecomendUser($id_user, $recomendUserId)
    {
        $sql = "
            INSERT INTO ds_user_registration_recomend
                (id_user, id_user_recomend)
            VALUES
                ($id_user, $recomendUserId)
            ";
        $this->common->executeQuery($sql);
        if(!empty($this->common->errormsg))
        {
            $this->errorMsg = "SYSTEM ERROR - Registration::AddUserRecomend";
            return false;
        }

        return true;
    }

    private function showRegisterForm($str = "", $ramblerID = false)
    {
        $common = $this->common;

        // создаем объект смарти
        $tpl = new Smarty;
        $tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;


        include_once("./language/".constant("DEFAULT_LANGUAGE_SHORT")."/index_registration.lang");

        if(!@$lang)
        {
            $this->errorMsg = "Login::showRegistrationForm1";
            return false;
        }

        $tpl->assign('lang', $lang);

        foreach(@$_REQUEST as $key=>$val)
        {
            $tpl->assign('R_'.strtoupper($key), $val);
        }

        $ramblerData = $this->_checkRamblerID();
        foreach ($ramblerData as $k => $v)
        {
            $tpl->assign($k, $v);
        }
        
        $tpl->assign('RAMBLER_ID', $ramblerID);

        if($str != "")
        {
            $tpl->assign("ANY_ERROR", $lang["ERROR"]);
            $tpl->assign("ERROR_TEXT", $str);
        }

        $sql = "
            SELECT
                ID_Language,
                Language
            FROM
                DS_Language
            ORDER BY id_language DESC
            ";
        $res = $common->getResultArray("all_language_registration", $sql, "", "", "GLOBAL_CACHE");
        if(!$res)
        {
            $this->errorMsg = " SYSTEM ERROR - Registration::showRegisterForm1";
            return false;
        }
        $tpl->assign("LANGUAGE", $res);

        // компилируем темплейт
        $out = $tpl->fetch('index_registration.tpl');

        echo $out;
    }

    private function activateUser()
    {
        $common = $this->common;

        $common->setDbId(0);

        $sql = "UPDATE ds_user SET activate_mail=1 WHERE  md5(username) = '".$_GET['key']."'";

        $res = $common->executeQuery($sql);

        if(!$res)
        {
            $this->errorMsg = " SYSTEM ERROR - Registration::activateUser1";
            return false;
        }

        $str = "Location: index_account_activated.php";
        Header($str);
        return false;
    }

    
    private function _checkRamblerID()
    {
        $assign = array();

        // Рамблер ID
        $ramblerLogin = new GE_RamblerLogin();
        if($ramblerLogin->hasRSID())
        {
            $common = DS_Common::CreateForCurentDB(0);

            GE_SearchStart::SetCommon($common);
            if($ramblerLogin->canLogin())
            {
                $assign['RAMBLER_EXISTS'] = false;
                $user = $ramblerLogin->execLogin();
                if($user === false)
                {
                    if($ramblerLogin->canRegistrate())
                    {
                        $assign['RAMBLER_LOGIN'] = false;
                        $assign['RAMBLER_REGISTRATION'] = true;
                        $assign['EMAIL'] = $ramblerLogin->getRamblerLogin();
                        $assign['USERNAME'] = $ramblerLogin->getUserNameForRegistration($common);
                        $assign['PASSWORD'] = GB_RandomPassword::Generate();
                        $assign['RSID'] = $ramblerLogin->getRSID();
                    }
                } else {
                    $assign['RAMBLER_LOGIN'] = true;
                    $assign['USER_NAME'] = $user->getName();
                }
            }
        }
        /*
        $assign['RAMBLER_LOGIN'] = false;
        $assign['RAMBLER_REGISTRATION'] = true;
        $assign['EMAIL'] = 'stariy@smtp.ru';
        $assign['USERNAME'] = 'TestRec';
        $assign['PASSWORD'] = GB_RandomPassword::Generate();
        $assign['RSID'] = 111222;
        */
        return $assign;
    }

}

$reg = new Registration;
?>