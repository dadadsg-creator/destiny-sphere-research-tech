<?php $CVS_Version = '$Revision: 1.10 $ ; $Date: 2009/01/27 11:01:48 $';

/**
 * Создание нового инстанса при регистрации
 *
 */
class GE_ExecStartCreateInstance
{
    /**
     * ID мира куда собраемся региться
     *
     * @var integer
     */
    static private $worldID;

    /**
     * ID базы вкаторой мир игрока куда он коннектиться
     *
     * @var integer
     */
    static private $DBID;

    /**
     * ID пользователя
     *
     * @var integer
     */
    static private $UID;

    /**
     * Флаг профи режима
     *
     * @var boolean
     */
    static private $isProfi = false;

    /**
     * ОБъект доля работы с БД
     *
     * @var DS_Common 
     */
    static private $common;

    /**
     * Выбранная раса
     *
     * @var int
     */
    static private $race;

    /**
     * Имя соты
     *
     * @var string
     */
    static private $combName;

    /**
     * Объект с данными пользователя
     *
     * @var DE_User
     */
    static private $user;

    /**
     * Сообщение об ошибке
     *
     * @var string
     */
    static private $errorMsg;

    static public function Exec($data, $redirect = true, $turnirReg = false)
    {
        assert(is_array($data) && count($data) > 0);

        //Получем все необходимые входные данные
        assert(key_exists('uid', $_SESSION));
        self::$UID = (int)$_SESSION['uid'];
        assert(self::$UID > 0);

        assert(key_exists('profi_game', $_SESSION));
        self::$isProfi = (bool)$_SESSION['profi_game'];

        assert(key_exists('world', $data));
        if($data['world'] == '-')
        {
            self::$errorMsg = 'NO_WORLD_SELECTED';
            return false;
        }

        $arrayTmp = explode('_', $data['world']);

        assert(key_exists(0, $arrayTmp));
        self::$worldID = (int)$arrayTmp[0];
        assert(self::$worldID > 0);

        assert(key_exists(1, $arrayTmp));
        self::$DBID = (int)$arrayTmp[1];
        assert(self::$DBID >= 0);
        $_SESSION['reg_world'] = $data['world'];

        if(!key_exists('race', $data) || $data['race'] == 0)
        {
            self::$errorMsg = 'NO_RACE_SELECTED';
            return false;
        }
        self::$race = (int)$data['race'];
        assert(self::$race >= 0 && self::$race < 4);
        $_SESSION['reg_race'] = self::$race;

        assert(key_exists('comb', $data));
        self::$combName = $data['comb'];
        $_SESSION['reg_combname'] = $data['comb'];

        /*// проверяем защитный код
        assert(key_exists('protection', $data));
        if(!$data['protection'] || !GE_ImageProtection::Validate($data['protection']))
        {
        self::$errorMsg = 'PROTECTION_CODE_ERROR';
        return false;
        }*/
        if(GameDown(self::$DBID, self::$worldID))
        {
            self::$errorMsg = 'WORLD_CLOSED';
            return false;
        }

        $_SESSION['dbId'] = self::$DBID;
        $_SESSION['world'] = self::$worldID;
        self::$common = DS_Common::CreateForCurentDB(self::$DBID);

        self::$user = DE_User::CreateFromID(self::$UID);

        GE_SearchStart::SetCommon(self::$common);

        self::$common->setDbId(0);

        // Если это не турнирный мир
        if ($turnirReg == false) {

            // проверяем может ли пользователь зарегистрироваться в мире
            if(!GE_LogicCanRegInWorld::Test(self::$user, self::$isProfi, self::$DBID, self::$worldID))
            {
                self::$errorMsg = "CANT_REGISTRATE_IN_WORLD";
                return false;
            }
        }

        self::$common->setDbId(self::$DBID);

        // проверяем имя соты
        if(!VD_CombName::Validate(self::$combName, true, self::$common))
        {
            self::$errorMsg = VD_CombName::FetchErrorCode();
            return false;
        }

        $world_type = GE_SearchStart::GetWorldType(self::$worldID);

        $_SESSION['world_type'] = $world_type;

        // проверяем есть ли свободное место
        if(GE_SearchStart::GetFreeCombs(self::$worldID, self::$race) == 0)
        {
            self::$errorMsg = 'NO_FREE_COMBS';
            return false;
        }

        // если в настройках включено использование шаблонов
        if(GE_SearchStart::GetCombTemplateModifier(self::$worldID) == 1)
        {
            // чтобы ничего не выводилось перехватываем все
            ob_start();
            $template = TN_Search::GetCombTemplate(self::$worldID, self::$race);
            $templateName = $template[0]['template_name'];
            assert(!empty($templateName));
            $newUIID = SI_ControlApplyTemplate::Apply($_SESSION['s_login'], $templateName, self::$DBID, self::$UID, self::$combName, self::$worldID, self::$race);
            ob_clean();


            GE_ExecTurnirRegistration::SetCommon(self::$common);
            GE_ExecTurnirRegistration::Add(self::$UID, $newUIID, self::$worldID, $GLOBALS['dbWorldAccess'][self::$DBID]['server']);
        } else {
            // иначе просто создаем новый интанс
            $wu = ClassFactory::getInstance("WorldUtils");
            if(!$wu)
            {
                self::$errorMsg = "UNABLE_TO_CREATE_INSTANCE";
                return false;
            }
            global $common;
            $common = self::$common;
            $newUIID = $wu->addNewInstance(self::$UID, self::$worldID, self::$race, self::$combName, $world_type);
            
            if(!$newUIID)
            {
                self::$errorMsg = "UNABLE_TO_CREATE_INSTANCE"; //$wu->errormsg
                SU_DebugTools::WriteToLog($wu->errormsg, "wu.log");
                self::$common->errormsg = '';
                return false;
            }
        }

        if(DE_WorldSettings::CreateForCurentWorld()->get('TURNIR_WORLD') == 1)
        {
            $worldStatus = GE_SearchStart::GetWorldStatusDirect(self::$worldID, self::$DBID);
            self::$common->setDbID(self::$DBID);

            // Если мир заморожн
            if($worldStatus == 'FROZEN')
            {
                // выставляем режим отпуска
                $sql = "
                        INSERT INTO ds_vocationmode
                            (startdate, enddate, id_userinstance)
                        VALUES
                            (NOW()-'2 HOUR'::interval, NULL, $newUIID)
                ";

                self::$common->getResultArray("", $sql, "", "", "");
                assert(empty(self::$common->errormsg));
            }

            // Вставляем в мастер инфу о том, что мы можем заходить в этот мир
            if ($DBID != 0) {
                self::$common->setDbId(0);
                $turnirDbName = $GLOBALS['dbWorldAccess'][$DBID]['dbname'];
                assert(!empty($turnirDbName));

                $sql = "INSERT INTO ds_userinstancecollection
                        (id_user, id_userinstance, id_world, dbname, admin, id_worldcollection)
                        SELECT 
                            ".$userId.",
                            ".$newUIID.",
                            ".$worldID.",
                            '".$turnirDbName."',
                            'f',
                            id_worldcollection
                        FROM 
                            ds_worldcollection                          
                        WHERE 
                            dbname = '".$turnirDbName."'
                            AND
                            id_world = ".$worldId;
                $res = self::$common->getResultArray ("", $sql, "", "", "" );

                assert(empty(self::$common->errormsg));
            }
        }

        $_SESSION["dbId"] = 0;
        self::$common->setDbId(0);
        $sql = "select * from ds_userinstancecollection where id_user=".self::$UID;
        $res = self::$common->getResultArray("", $sql);
        assert(empty(self::$common->errormsg));
        if (count($res) < 2) {
            if (VERSION_NAME=="LIMITED") $interval = '365 days';
            else if (VERSION_NAME=='RU_UNLIM') $interval = '365 days';
            else $interval = '365 days';
            $sql = "select * from ds_userprofidata where id_user=".self::$UID;
            $res = self::$common->getResultArray("", $sql);
            if (!count($res)) {
                $sql = "insert into ds_userprofidata (id_user, profi, creationdate, startdate, enddate, active)
                  values (".self::$UID.", true, now(), now(), now() + interval '$interval', true)";
                self::$common->executeQuery($sql);
            }
            else {
                $sql = "update ds_userprofidata set enddate=greatest(enddate, now() + interval '$interval') where id_user=".self::$UID;
                self::$common->executeQuery($sql);
            }

            if (VERSION_NAME!="LIMITED") {
                /* Стартовая выдача 2000 серебра на мирах классики (убрана)
                $_SESSION["dbId"] = self::$DBID;
                self::$common->setDbId(self::$DBID);
                $sql = "select world_identifier from ds_world where id_world=".self::$worldID;
                $res = self::$common->getResultArray("", $sql);
                $w = $res[0]['world_identifier'];
                $sql = "select * from ds_money_account where world_identifier='$w' and id_user=".self::$UID;
                $res = self::$common->getResultArray("", $sql);
                if (count($res)) {
                    $sql = "update ds_money_account set sc_amount=sc_amount+2000 where world_identifier='$w' and id_user=".self::$UID;
                }
                else {
                    $sql = "insert into ds_money_account (id_user, gc_amount, sc_amount, is_active, world_identifier, add_date)
                        values (".self::$UID.", 0, 2000, true, '$w', now())";
                }
                self::$common->executeQuery($sql);
                assert(empty(self::$common->errormsg));
                $sql = "update ds_money_account set sc_amount=sc_amount-2000 where world_identifier='$w' and id_user=0";
                self::$common->executeQuery($sql);
                */

                $forumPassword = GE_ForumRegistration::forumRegistration(self::$UID);
                if ($forumPassword!='') {
                    $header = "Content-type: text/plain; charset=windows-1251\nFrom: Destiny Sphere <noreply@destinysphere.ru>";
                    $subject = 'Регистрация на форуме Destiny Sphere';
                    $message = "Вы автоматически зарегистрированы на форуме Destiny Sphere.\nВаш логин: ".self::$user->getName()."\nВаш пароль: $forumPassword";
                    mail(self::$user->getEmail(), '=?windows-1251?B?'.base64_encode($subject).'?=', $message, $header);
                }
            }
        }
        else $_SESSION["dbId"] = self::$DBID;

        // Выбираем данные игрока из базы для регистрации на форуме
        $sql = "
                        SELECT * FROM ds_user
                        WHERE id_user = ".self::$UID."
                ";

        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
                
        /****РЕГИСТРАЦИЯ НА ФОРУМЕ***/
        // Вариант для классики
        if(constant("FORUM_REGISTRATION_ENABLED") == true && self::$common->connect('forum'))
        {
            if(count($res) != 1)
            {
                self::$errorMsg = "UNABLE_TO_CREATE_INSTANCE";//$wu->errormsg;
                self::$common->errormsg = '';
                return false;
            }
                
            $username = $res[0]['username'];    
            assert(!empty($username));
            
            $md5pass = $res[0]['password']; 
            assert(!empty($md5pass));
            
            $email = $res[0]['email'];  
            assert(!empty($email));
            
            $email = $res[0]['email'];  
            assert(!empty($email));
            
            
            // Смотрим, если ли на форуме такой инрок
            $sql = sprintf("select user_id from %s_users where username = '%s'",
            constant('FORUM_PREFIX'), $username);
            $res = self::$common->getResultArray("", $sql, "", "", "");
            
            
            if(count($res) > 0)
            {
                // Такой игрок на форуме уже есть, выходим
                // Возвращаем коммону  игровую базу

                self::$common->setDbId($_SESSION['dbId']);
                
                $res = "";

                if ($redirect)
                {
                    // $str = "Location: index_start.php?ck=".$_SESSION['client_key']."&start=t&uiid=".$newUIID."_".$_SESSION['dbId']."_".$_SESSION['world'];
                    $str = "Location: index_start.php?ck=".$_SESSION['client_key']."&uiid=".$newUIID."_".$_SESSION['dbId']."_".$_SESSION['world'];
                    if(session_id())
                    {
                        $str .= "&".session_name()."=".session_id();
                    }
                    Header($str);
                }
        
                return true;
            }
            
            $res = self::$common->executeQuery('begin');

            $sql = sprintf ( "insert into %s_groups (group_name, group_description, group_single_user, group_moderator)
                            values ('', 'Personal User', 1, 0)", constant('FORUM_PREFIX') );

            $res = self::$common->executeQuery ( $sql );

            if($res === false)
            {
                // Возвращаем коммону  игровую базу
                self::$common->setDbId($_SESSION['dbId']);
                return true;
            }

            $sql = "select max(group_id) as gid from ".constant('FORUM_PREFIX')."_groups";

            $res = self::$common->getResultArray("max_group_id", $sql, "", "", "");

            if(!$res)
            {
                // Возвращаем коммону  игровую базу
                self::$common->setDbId($_SESSION['dbId']);
                return true;
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
            constant('FORUM_PREFIX'), $username, time(), $md5pass, $email, constant("FORUM_TIMEZONE"), constant("FORUM_LANGUAGE") );
            $res = self::$common->executeQuery($sql);

            if($res === false)
            {
                // Возвращаем коммону  игровую базу
                self::$common->setDbId($_SESSION['dbId']);
                return true;
            }

            $sql = "select max(user_id) as uid from ".constant('FORUM_PREFIX')."_users";

            $res = self::$common->getResultArray ( "max_user_id", $sql, "", "", "" );
            if(!$res)
            {
                // Возвращаем коммону  игровую базу
                self::$common->setDbId($_SESSION['dbId']);
                return true;
            }

            $uid = $res[0]['uid'];

            $sql = sprintf("insert into %s_user_group
                            (user_id, group_id, user_pending)
                            values (%d, %d, 0)",
            constant('FORUM_PREFIX'), $uid, $gid);

            $res = self::$common->executeQuery($sql);

            if($res === false)
            {
                // Возвращаем коммону  игровую базу
                self::$common->setDbId($_SESSION['dbId']);
                return true;
            }

            $res = self::$common->executeQuery('commit');
        }

        // Возвращаем коммону  игровую базу
        self::$common->setDbId($_SESSION['dbId']);
        $_SESSION['uiid'] = (int)$newUIID;
        
        //$_SESSION["comb"] = "";
        //$_SESSION["race"] = "";
        
        /*    
        $mesParams = array(
            self::$user->getName()//,
            //DE_World::CreateForCurrentWorld()->getName(),
            //DE_UserInstance::CreateFromID((int)$newUIID)->getCombsCollection()->next()->getFormatedAddress()
            );
            
            switch (constant('VERSION_NAME'))
            {
                case 'RU_CLASSIC':
                    $message = 'HELLO_USER';
                    break;
                case 'LIMITED':
                    $message = 'HELLO_USER1';
                    break;
                case 'RU_UNLIM':
                    $message = 'HELLO_USER2';
                    break;
                default:
                    $message = 'HELLO_USER1';
                    break;
            }
        $message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage($message, $mesParams, null, null, false);
        $subject = DE_Localization::CreateForCurentLang()->get(251738);
        $senderName = 'DestinySphere';
        $recieverName = self::$user->getName();
        $messageScopeID = DS_Message::GetMessageScopeIDByName('SYSTEM');
        $fromUIID = 0;
        $messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);
        DS_Message::AddMessageToUser($messageID, (int)$newUIID);
        */

        $res = "";

        if ($redirect)
        {
          // $str = "Location: index_start.php?ck=".$_SESSION['client_key']."&start=t&uiid=".$newUIID."_".$_SESSION['dbId']."_".$_SESSION['world'];
          $str = "Location: index_start.php?ck=".$_SESSION['client_key']."&uiid=".$newUIID."_".$_SESSION['dbId']."_".$_SESSION['world'];
          if(session_id())
            {
                $str .= "&".session_name()."=".session_id();
            }
            Header($str);
        }
        
        return true;
    }

    static public function FetchErrorCode()
    {
        return self::$errorMsg;
    }
}

?>