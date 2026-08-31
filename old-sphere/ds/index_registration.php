<?PHP $CVS_Version = '$Revision$ ; $Date$';
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
if ( stripos($_SERVER['HTTP_HOST'],'ds.klase.lt') !==false )
{
    require_once("../sys/OAuth.php");
    require_once('config/klasecfg.php' );
}
else if ( stripos($_SERVER['HTTP_HOST'],'popmog') !==false )
{
  include "lib/PopmogRestClient.php";
    require_once('config/popmogcfg.php' );  
}

if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
{
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

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

        $errorMsg = "";

        if(sizeof($data) == 0)
        {
            if (!isset($_COOKIE['ref']))
            {
                $referer = @$_SERVER['HTTP_REFERER'];
                $refenc = urlencode($referer);
                setcookie("ref", $refenc, time()+3600*24*14, "/");
            }

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
            if (isset($data['rambler']))
            {
                if($data['rambler'])
                {
                    $ramblerRegistration = true;
                }
            }

            $v[0] = new ValidateUser($_POST['user']);
            $v[1] = new ValidatePassword($_POST['pass'],$_POST['conf'], $_POST['user']);
            $v[2] = new ValidateEmail($_POST['email'], $_POST['email']);
            $v[3] = new ValidateLang($_POST['lang']);
            $v[4] = new ValidateCheckBox1($_POST['checkbox']);

            $errorMsg = "";

            foreach($v as $validator)
            {
                if($validator && !$validator->isValid())
                {
                    $error = $validator->getError();
                    if(isset($error[1]))
                    {
                        $errorMsg .= "<li>".$error[1]."</li>\n";
                    }
                }
            }

            $v = new Validator();
            $klasereg = 0;
            if ( stripos($_SERVER['HTTP_HOST'],'ds.klase.lt') !==false )
            $klasereg = 1;

            if (isset($_POST['ur']) && !empty($_POST['ur']))
            {
                $user = DS_User::GetByID($_POST['ur']);

                if (!$user)
                $recomendUserId = 0;
                else
                $recomendUserId = $_POST['ur'];
            }
            else
            {
                $recomendUserId = 0;
            }

            if(!empty($errorMsg))
            {
                $this->showRegisterForm($errorMsg, $ramblerRegistration);
            } else {
                if($ramblerRegistration)
                {
                    $mailActivate = 1;
                } else {
                    $mailActivate = 0;
                }

                if (isset($_COOKIE["partner"]))
                $partner = intval($_COOKIE["partner"]);
                else
                $partner = 2;

                if (stripos($_SERVER['HTTP_HOST'],'ds.klase.lt'))
                {
                    $partner = 21;
                }
                else if (stripos($_SERVER['HTTP_HOST'], 'ds.games.tut.by') !== false)
                {
                    $partner = 41;
                }

                if (isset($_COOKIE["ref"]))
                $reg_referer = pg_escape_string(substr(urldecode($_COOKIE["ref"]), 0, 255));
                else
                $reg_referer = '';

                if ($language  == 'L')
                $mailActivate = 1;

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
                            from_partner,
                      reg_referer,
                      ip_addr
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
                            ".$partner.",
                          '".$reg_referer."',
                          '".$_SERVER['REMOTE_ADDR']."'
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

                $id_user = (int)$res[0]['id_user'];

                if ($klasereg)
                {
                    $open_key    = @$_COOKIE['open_key'];
                    $secret_key  = @$_COOKIE['secret_key'];
                    if (!$open_key || !$secret_key)
                    {
                        header("Location: /");
                        exit;
                    }
                    $token_username = @$_COOKIE['klaselt_name'];

                    $sql = "INSERT INTO ds_oauthinfo(open_key, secret_key, token_username, game_uid)
                  VALUES('{$open_key}', '{$secret_key}', '{$token_username}', {$id_user})";
                    $res = $common->executeQuery($sql);
                    if($res === false)
                    {
                        //echo "$sql\n";
                        //echo $common->errormsg;
                        return array(0,"SYSTEM ERROR - Registration::ds_oauthinfo ");
                    }


                    $odata = MakeOAuthNotification($open_key, $secret_key, array('type' => 'start'));
                    //SU_DebugTools::LogAll(var_export($odata, true),'oauth.log');
                }
                //////////////////////////// ПОДАРОЧНЫЙ ПРЕМИУМ ПРИ ПЕРЕХОДЕ ПО ССЫЛКЕ ОТ ПАРТНЕРА /////////////////////////////////////
                $this->_addPremiumPresentFromPartner($id_user);
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                //////////////////////////// Рекомендованный игрок //////////////////////////////////////////////////////////////////////
                if($recomendUserId)
                {
                    if(!$this->_addRecomendUser($id_user, $recomendUserId))
                    {
                        return false;
                    }
                }

                $common->executeQuery("commit");

                //////////////////////////// РЕГИСТРАЦИЯ НА ФОРУМЕ //////////////////////////////////////////////////////////////////////
                $forumUserId = 0;

                // Если мы регаемся через гейт


                //для Литвы особый случай
                if (($language=='L') && (constant("FORUM_REGISTRATION_ENABLED") == true))
                {
                    $mySql = DS_MySQLWork::Singleton();
                    $mySql->connect('FORUM');

                    $mySql->exec('begin');

                    $sql = sprintf ( "insert into %s_groups (group_name, group_description, group_single_user, group_moderator)
                            values ('', 'Personal User', 1, 0)", constant('FORUM_PREFIX') );

                    $res = $mySql->exec($sql);

                    if($res === false)
                    {
                        $this->errorMsg = "SYSTEM ERROR - Registration::getPostData2-2".$sql." | ".mysql_error();
                        return false;
                    }

                    $sql = "select max(group_id) as gid from ".constant('FORUM_PREFIX')."_groups";

                    $res = $mySql->get($sql);

                    if(!$res)
                    {
                        $this->errorsg = "SYSTEM ERROR - Registration::getPostData4";
                        return false;
                    }

                    $gid = $res[0]['gid'];

                    $sql = "select max(user_id) as uid from ".constant('FORUM_PREFIX')."_users";

                    $res = $res = $mySql->get($sql);
                    if(!$res)
                    {
                        $this->errorMsg = "SYSTEM ERROR - Registration::getPostData6";
                        return false;
                    }

                    $uid = $res[0]['uid'] + 1;

                    $sql = sprintf("insert into %s_users
                            (user_id, username, user_regdate, user_password, user_email, user_icq, user_website,
                            user_occ, user_from, user_interests, user_sig, user_sig_bbcode_uid, user_avatar,
                            user_avatar_type, user_viewemail, user_aim, user_yim, user_msnm, user_attachsig,
                            user_allowsmile, user_allowhtml, user_allowbbcode, user_allow_viewonline, user_notify,
                            user_notify_pm, user_popup_pm, user_timezone, user_dateformat, user_lang, user_style,
                            user_level, user_allow_pm, user_active, user_actkey)
                            values ( $uid, '%s', '%s', '%s', '%s', '', '', '', '', '', '', '', '', 0, 0, '', '', '',
                            0, 1, 0, 1, 1, 0, 1, 1, %s, 'D M d, Y G:i', '%s', 1, 0, 1, 1, '')",
                    constant('FORUM_PREFIX'), $_POST['user'], time(), md5 ( $_POST['pass'] ), $_POST['email'], constant("FORUM_TIMEZONE"), constant("FORUM_LANGUAGE") );

                    $res = $mySql->exec($sql);

                    if($res === false)
                    {
                        $this->errorsg = "SYSTEM ERROR - Registration::getPostData5";
                        return false;
                    }

                    $sql = sprintf("insert into %s_user_group
                            (user_id, group_id, user_pending)
                            values (%d, %d, 0)",
                    constant('FORUM_PREFIX'), $uid, $gid);

                    $res = $mySql->exec($sql);

                    if($res === false)
                    {
                        $this->errorMsg = "SYSTEM ERROR - Registration::getPostData7";
                        return false;
                    }

                    $mySql->exec('commit');
                }
                else
                {
                    if (constant("FORUM_REGISTRATION_THROUGH_GATE") == 1) {
                        // Вариант для анлима
                        $forumPassword = GB_RandomPassword::Generate(8);

                        $forumUserId = $this->_forumRegistration($forumPassword);

                        if($forumUserId > 0)
                        {
                            $this->_addForumIdToUser($forumUserId, $id_user);
                        }

                    } else {
                        // РЕГИСТРАЦИЯ НА ФОРУМЕ ПЕРЕНЕСЕНА ПОСЛЕ СОЗДАНИЯ СОТЫ ДЛЯ ЗАЩИТЫ ДЛЯ СПАМА
                        // Вариант для классики
                        /*if(constant("FORUM_REGISTRATION_ENABLED") == true && $common->connect('forum'))
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

                            $forumUserId = 1;
                            $forumPassword = $_POST['pass'];
                        }*/
                    }
                }

                //////////////////////////// Отправка оповещения по почте //////////////////////////////////////////////////////////////////////
                if(constant("ACTIVATION_EMAIL_ENABLED") == true && constant("REGISTRATION_ENABLED") === "true")
                {
                    require_once("language/".$language."/reg_latter.lang");
                    require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

                    // создаем объект смарти
                    $tpl = new Smarty;
                    $tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
                    $tpl->compile_dir = constant('SMARTY_CACHE_PATH');
                    $tpl->compile_check = true;
                    $tpl->debugging = false;

                    $tpl->assign('lang', $lang);

                    // типа по тихому сливаем инфу о регистрации, если возникла ошибка
                    if(constant("FORUM_REGISTRATION_ENABLED") == true && $forumUserId > 0)
                    {
                        $tpl->assign('FORUM_PASSWORD', $forumPassword);
                    } else {
                        $tpl->assign('FORUM_PASSWORD', '');
                    }

                    $tpl->assign('LOGIN', $_POST['user']);

                    if (!$klasereg)
                    $tpl->assign('PASSWORD', $_POST['pass']);
                    else
                    $tpl->assign('PASSWORD', 'Use your klase.lt authorization');

                    if(!$ramblerRegistration && !$klasereg)
                    {
                        $tpl->assign('URL', GAME_HOST.'/ds/index_registration.php?action=activate&key='.md5($_POST['user'].$id_user));
                    } else {
                        $tpl->assign('URL', '');
                    }

                    if (constant("DEFAULT_LANGUAGE_SHORT")=='L')
                    {
                        if (!$klasereg)
                        {
                            $tpl->assign('domain', 'www.ds-game.lt');
                            $tpl->assign('domain2', '( www.destinysphere.lt )');
                        }
                        else
                        $tpl->assign('domain', 'ds.klase.lt');

                        if ($klasereg)
                        $latter = $tpl->fetch('reg_letter_litva_klaselt.tpl');
                        else
                        $tpl->assign('GAMEURL', constant('GAME_HOST').'ds/index_login.php');

                        $latter = $tpl->fetch('reg_letter_litva.tpl');
                        //SU_DebugTools::LogAll($latter,'reg.log');
                    }
                    else
                    {
                        $latter = $tpl->fetch('reg_latter.tpl');
                    }

                    $charset = array ('R' => 'windows-1251', 'G' => 'windows-1252', 'E' => 'windows-1251','L' => 'windows-1257');

                    $eol = "\r\n";
                    $headers = 'From: noreply <noreply@destinysphere.com>'.$eol;
                    $headers .= "Message-ID: <".time()." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
                    $headers .= "X-Mailer: PHP v".phpversion().$eol;
                    $headers .= "Content-Type: text/plain; charset=".$charset[constant("DEFAULT_LANGUAGE_SHORT")].$eol;

                    mail($_POST['email'], $lang['LATTER_SUBJECT'], $latter, $headers);
                }

                $_POST['login'] = $_POST['user'];
                $_POST['enter'] = 'Login';

                // Логинимся в игру
                $log = new GE_Login;
            }
            return true;
        }
    }

    /**
     * Подарочный премиум
     *      дается при регистрации через партнеров (см. ds_partner)
     *      или
     *      можно давать всем игрокам (см. DEFAULT_PARTNER в settings.php)
     *
     * @param unknown_type $id_user
     * @return unknown
     */
    private function _addPremiumPresentFromPartner($id_user)
    {
        if(!is_int($id_user) || $id_user <= 0)
        {
            $this->errorMsg = "SYSTEM ERROR - Registration::addPremiumPresent1";
            return false;
        }

        //if(!empty($partnerID))
        //{
        $parnterData = $this->_getPartnerPremiumPresent($this->_getPartnerID());
        if(!is_array($parnterData) || $parnterData['premium_present'] == 0)
        {
            return;
        }

        $user = DE_User::CreateFromID($id_user);
        PU_ExecChangePremiumDays::Exec($user, (int)$parnterData['premium_present'], 'REGISTRATION', 'From partner: '.$parnterData['name']);

        $sql = "
            INSERT INTO ds_userprovidedbypartner
                (id_user, id_partner)
            VALUES
                (".$id_user.", ".$parnterData['id_partner'].")
            ";
        $res = $this->common->executeQuery($sql);
        if($res === false)
        {
            $this->errorMsg = "SYSTEM ERROR - Registration::addPremiumPresent3";
            return false;
        }
        //}
    }

    private function _getPartnerID()
    {
        if(!key_exists('pid', $_REQUEST) || strlen($_REQUEST['pid']) !== 32)
        {
            return constant('DEFAULT_PARTNER');
        }

        return $_REQUEST['pid'];
    }

    private function _getPartnerPremiumPresent($partnerID)
    {
        $common = DS_Common::CreateForCurentDB(0);

        // дефолтный партер, задается в settings.php
        if($partnerID == constant('DEFAULT_PARTNER'))
        {
            $sql = "
                select
                    p.name,
                    p.id_partner,
                    p.premium_present
                from
                    ds_partner as p
                WHERE
                    p.id_partner = ".$partnerID;
            $res = $common->getResultArray("", $sql);
            assert(empty($common->errormsg));

            if(count($res) < 1)
            {
                return 0;
            }

            return $res[0];
        }

        if(!key_exists('REMOTE_ADDR', $_SERVER))
        {
            return 0;
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        if(!preg_match('/^[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}$/', $ip))
        {
            return 0;
        }

        $sql = "
            select
                p.name,
                p.id_partner,
                p.premium_present
            from
                ds_partner_has_ip_range as p_ip
                inner join ds_partner as p using (id_partner)
            where
                '$ip' >= p_ip.from_ip
                and
                '$ip' <= p_ip.to_ip
                and
                (
                    p.public_id = '".pg_escape_string($partnerID)."'
                    OR
                    p.public_id IS NULL
                )
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));

        if(count($res) < 1)
        {
            return 0;
        }

        return $res[0];
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
        if (!empty($this->common->errormsg))
        {
            $this->errorMsg = "SYSTEM ERROR - Registration::AddUserRecomend";
            return false;
        }

        return true;
    }

    /**
     * Enter description here...
     *
     * @param int $forumId
     */
    private function _addForumIdToUser($forumId, $id_user)
    {
        /*
        $common = $this->common;

        $common->setDbId(0);
        $common->executeQuery("begin");

        $sql = "
        INSERT INTO ds_forum_user
        (id_user, id_forum_user)
        VALUES
        (".$id_user.", ".$forumUserId.")
        ";
        $common->executeQuery($sql);
        if(!empty($common->errormsg))
        {
        $this->errorMsg = "SYSTEM ERROR - Registration::AddForumUser";
        return false;
        }

        $common->executeQuery("commit");
        */
    }

    /**
     * Регистрация на форуме
     *
     * @param unknown_type $forumPassword
     * @return bool
     */
    private function _forumRegistration($forumPassword)
    {
        $forumUserId = 0;

        if(constant("FORUM_REGISTRATION_ENABLED") == true)
        {
            // готовим данные
            $data = array(
            'username'          => $_POST['user'],
            'new_password'      => $forumPassword,
            'password_confirm'  => $forumPassword,
            'email'             => $_POST['email'],
            'email_confirm'     => $_POST['email'],
            'confirm_code'      => 'zzzzzzzz',
            'lang'              => constant('FORUM_LANGUAGE'),
            'tz'                => constant('FORUM_TIMEZONE'),
            'check'             => md5('FORUM'.date('Y-m')),
            );

            // готовим запрос
            $content = http_build_query($data);

            $options = array(
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'user_agent' => '',
            'content' => $content,
            'proxy' => '',
            'request_fulluri' => '',
            'max_redirects' => '',
            //'timeout' => 10
            );

            $opts = array('http' => $options);

            $context  = stream_context_create($opts);

            // отсылаем запрос форуму
            $result = file_get_contents(constant('URL_FORUM_REGISTRATION_GATE'), false, $context, 0, 100000);

            $forumUserId = 0;

            if(!empty($result))
            {
                $arr = explode('|', $result);
                if(count($arr) > 1 && $arr[0] == 'OK')
                {
                    list($forumUserId, $pass) = explode('=', $arr[1]);
                } else {
                    SU_DebugTools::LogAll($result.' || '.print_r($data, true), 'FORUM_REGISTRATION.log');
                }
            } else {
                SU_DebugTools::LogAll('Empty result : '.$result.' || '.print_r($data, true), 'FORUM_REGISTRATION.log');
                $forumUserId = 99999999; // затыка, на случай когда тормозит гейт регистрации на форуме
            }
        }

        return $forumUserId;
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

    private function showRegisterForm($str = "", $ramblerID = false)
    {
        global $popmogcfg;
        $common = $this->common;

        // создаем объект смарти
        $tpl = new Smarty;
        $tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
        $tpl->compile_dir = constant('SMARTY_CACHE_PATH');
        $tpl->compile_check = true;
        $tpl->debugging = false;

        $tpl->assign('HOST', constant('GAME_HOST'));

        $recommend_user = 0;
        if (key_exists('ur', $_REQUEST))
        {
            $user = DS_User::GetByID($_REQUEST['ur']);
            if (!$user) {
                $recommend_user = 0;
            } else {
                $recommend_user = $_REQUEST['ur'];
            }
        }

        include_once("./language/".constant("DEFAULT_LANGUAGE_SHORT")."/index_registration.lang");

        if (!@$lang)
        {
            $this->errorMsg = "Login::showRegistrationForm1";
            return false;
        }

        $tpl->assign('lang', $lang);
        $tpl->assign('R_LANG', constant("DEFAULT_LANGUAGE"));
        $tpl->assign('R_REF_USER', $recommend_user);

        foreach(@$_REQUEST as $key=>$val)
        {
            $tpl->assign('R_'.strtoupper($key), $val);
        }

        //$tpl->assign('RAMBLER_ID', $ramblerID);

        if (stripos($_SERVER['HTTP_HOST'], 'ds.klase.lt')!==false)
        {
            if (isset($_COOKIE['klaselt_name']))
            {
                $email = $_COOKIE['klaselt_email'];
                if (!@$_REQUEST['user'])
                  $user = $_COOKIE['klaselt_name'];
                else
                 $user = $_REQUEST['user'];
                //$tok = addslashes($_GET['oauth_token']);
                //$user = GE_SearchStart::GetUserByToken($tok);
                //die(var_export());
                $klase = 1;

                $tpl->assign('PASSWORD',GB_RandomPassword::Generate());
                $tpl->assign('KLASE_REGISTRATION', true);
                //echo "klaselt_email=".$_COOKIE['klaselt_email'];
                $tpl->assign('USERNAME', $user);
                $tpl->assign('EMAIL',    $email);
            }
            else
            {
                header("Location: /");
                die;
            }
        }
        /*
    else if ( stripos($_SERVER['HTTP_HOST'],'popmog') !==false )
    {
        //$sso_token = $_GET['sso_token'];
      if ( !empty($sso_token) ) 
      {
        $client   = new PopmogRestClient ( $popmogcfg['key'], $popmogcfg['secret'] ); 
        $userdata = $client->create_session_from_token( $sso_token );

        if ( !empty($session_token) ) 
        {
          // verify that user is logged in, get userdata (optional)
          $another_client = new PopmogRestClient ( $popmogcfg['key'], $popmogcfg['secret'], $session_token ); 
          $userdata       = $another_client->get_userdata();
        }       
        
                $tpl->assign('PASSWORD',GB_RandomPassword::Generate());
                $tpl->assign('POPMOG_REGISTRATION', true);
                //echo "klaselt_email=".$_COOKIE['klaselt_email'];
                $tpl->assign('USERNAME', $userdata['local_name']);
                $tpl->assign('EMAIL',    $userdata['email']);        
      } 
    }       
    */
        else
        {
            $ramblerData = $this->_checkRamblerID();
            foreach ($ramblerData as $k => $v)
            {
                $tpl->assign($k, $v);
            }
        }


        $partnerID = $this->_getPartnerID();
        if(!empty($partnerID))
        {
            $tpl->assign('PID', $partnerID);
        }

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


        $sql = "SELECT * FROM ds_user WHERE  md5(username||id_user) = '".$_GET['key']."'";
        $user_res = $common->getResultArray("", $sql, "", "", "");
        if(!$user_res)
        {
        $sql = "SELECT * FROM ds_user WHERE  md5(username) = '".$_GET['key']."'";
          $user_res = $common->getResultArray("", $sql, "", "", "");
      if(!$user_res)
      {
              $this->errorMsg = " SYSTEM ERROR - Registration::activateUser2";
              return false;
      }  
        }

        $sql = "UPDATE ds_user SET activate_mail=1 WHERE  id_user = ".$user_res[0]['id_user'];
        $res = $common->executeQuery($sql);

        if(!$res)
        {
            $this->errorMsg = " SYSTEM ERROR - Registration::activateUser1";
            return false;
        }
        
        $idp = (int)$user_res[0]['from_partner'];
        $str = "Location: index_account_activated.php";

        $partner_arr = array(38, 49, 59, 109, 112, 113, 139, 154);
        
        //if ($_SERVER['REMOTE_ADDR'] == '195.182.156.98')  SU_DebugTools::LogAll("ipd=$ipd, partner_arr=".var_export($partner_arr, true), 'activate.log');
        // Если партнер http://www.troywell.ru
        if (in_array($idp, $partner_arr) && VERSION_NAME=="RU_CLASSIC") 
        {
            $userId = (int)$user_res[0]['id_user'];
            $str .= "?idp={$idp}&order_id=$userId";
            
          //if ($_SERVER['REMOTE_ADDR'] == '195.182.156.98')   SU_DebugTools::LogAll("str=$str", 'activate.log');
        }

        Header($str);
        exit;
    }
}

$reg = new Registration;
?>