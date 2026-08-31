<?
class GE_ForumRegistration
{
    static public function forumRegistration($id_user)
    {
        //для Литвы особый случай
        if ((constant("DEFAULT_LANGUAGE_SHORT")=='L') && (constant("FORUM_REGISTRATION_ENABLED") == true))
        {
            $mySql = DS_MySQLWork::Singleton();
            $mySql->connect('FORUM');

            $mySql->exec('begin');

            $sql = sprintf ( "insert into %s_groups (group_name, group_description, group_single_user, group_moderator)
                        values ('', 'Personal User', 1, 0)", constant('FORUM_PREFIX') );

            $res = $mySql->exec($sql);

            if($res === false)
            {
                //$this->errorMsg = "SYSTEM ERROR - Registration::getPostData2-2".$sql." | ".mysql_error();
                return false;
            }

            $sql = "select max(group_id) as gid from ".constant('FORUM_PREFIX')."_groups";

            $res = $mySql->get($sql);

            if(!$res)
            {
                //$this->errorMsg = "SYSTEM ERROR - Registration::getPostData4";
                return false;
            }

            $gid = $res[0]['gid'];

            $sql = "select max(user_id) as uid from ".constant('FORUM_PREFIX')."_users";

            $res = $res = $mySql->get($sql);
            if(!$res)
            {
                //$this->errorMsg = "SYSTEM ERROR - Registration::getPostData6";
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
                //$this->errorMsg = "SYSTEM ERROR - Registration::getPostData5";
                return false;
            }

            $sql = sprintf("insert into %s_user_group
                                (user_id, group_id, user_pending)
                                values (%d, %d, 0)",
            constant('FORUM_PREFIX'), $uid, $gid);

            $res = $mySql->exec($sql);

            if($res === false)
            {
                //$this->errorMsg = "SYSTEM ERROR - Registration::getPostData7";
                return false;
            }

            $mySql->exec('commit');
        }
        else if (constant("FORUM_REGISTRATION_THROUGH_GATE") == 1)
        {
            // Вариант для анлима
            $forumPassword = 'a'.GB_RandomPassword::Generate(8);
            $forumUserId = self::UnlimForumRegistration($forumPassword);
            if($forumUserId <= 0) $forumPassword = '';
        }
        return $forumPassword;
    }

    static private function UnlimForumRegistration($forumPassword)
    {
        $registredOnForum = false;
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

            if(!empty($result))
            {
                $arr = explode('|', $result);
                if(count($arr) > 1 && trim($arr[0]) == 'OK')
                {
                    $registredOnForum = true;
                    list($forumUserId, $pass) = explode('=', $arr[1]);
                    $forumUserId = trim($forumUserId);
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
}
?>