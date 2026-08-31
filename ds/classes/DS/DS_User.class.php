<?php $CVS_Version = '$Revision: 1.17 $ ; $Date: 2008/07/01 08:46:46 $';


/**
 * Сттаитческий клас с библиотекой запросов по классу пользователя.
 */
class DS_User extends DS_Base
{
    static function GetByID($id)
    {
        settype ( $id, 'integer' );
        assert ( $id >= 0 );

        $common = self::getCommon();
        $sql = "SELECT
                    u.*,
                    l.*,
                    COALESCE(uhi.image_url, '') AS image_url,
                    COALESCE(upbp.id_partner, 0) AS id_partner
                FROM
                    ds_user AS u
                    INNER JOIN ds_language AS l ON (u.id_language = l.id_language)
                    LEFT JOIN ds_user_has_image AS uhi USING (id_user)
                    LEFT JOIN ds_userprovidedbypartner AS upbp USING (id_user)
                WHERE id_user = ".$id;

        $res = $common->getResultArray ( "",$sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res){
            return false;
        }else{
            return $res[0];
        }
    }

    static function GetUserIDByName($name)
    {

        assert(!empty($name));
        $common = self::getCommon();

        $sql = "SELECT id_user
                FROM
                    ds_user
                WHERE username = '".pg_escape_string($name)."'";

        $res = $common->getResultArray ( "",$sql, "", "", "");

        assert(empty($common->errormsg));

        if(!$res){
            return false;
        }else{
            return (int)$res[0]['id_user'];
        }
    }

    static function IsPremium($userID)
    {
        assert(is_int($userID) && $userID>0 );

        $common = self::getCommon();

        $sql = "SELECT id_user
                FROM ds_userprofidata
                WHERE
                    id_user = ".$userID."
                    AND
                    now() < enddate
                    AND
                    active = true
                    AND
                    trial = false";
        $res = $common->getResultArray ( "",$sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        }else{
            return true;
        }
    }

    static public function IsTrialPremium($userID)
    {
        assert(is_int($userID));

        $common = self::getCommon();

        $sql = "SELECT id_user
                FROM ds_userprofidata
                WHERE
                    id_user = ".$userID."
                    AND
                    now() < enddate
                    AND
                    active = true
                    AND
                    trial = true";
        $res = $common->getResultArray ( "",$sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        }else{
            return true;
        }
    }

    static private function _updateUserPremiumBan(DE_User $user)
    {
        $common = self::getCommon();

        if(self::GetPremiumBan($user))
        {
            $sql = "
                UPDATE
                    ds_user_premium_ban
                SET
                    start_date = '".$user->getPremiumBan()->getStartDate()->getPGFormat()."',
                    end_date = '".$user->getPremiumBan()->getEndDate()->getPGFormat()."',
                    comments = '".$user->getPremiumBanComment()."',
                    allow_trade = '".$user->getPremiumBanAllowTrade()."'
                WHERE
                    id_user = ".$user->getID()."
                ";
        } else {
            $sql = "
                INSERT INTO ds_user_premium_ban
                    (id_user, start_date, end_date, comments, allow_trade)
                VALUES
                    (".$user->getID().", '".$user->getPremiumBan()->getStartDate()->getPGFormat()."', '".$user->getPremiumBan()->getEndDate()->getPGFormat()."', '".$user->getPremiumBanComment()."', '".$user->getPremiumBanAllowTrade()."')
                ";
        }
        echo $common->errormsg;
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    static private function _updateUserReasonBan(DE_User $user)
    {
        // обновление информации о бане пользователя
        $banReason = $user->getBanReason();

        if(count($banReason) == 0)
        {
            return false;
        }

        $reason = self::getBanReasonInfo($banReason['reasonname']);
        if(count($reason) == 0)
        {
            return false;
        }

        if(!$banReason['time'])
        {
            $banReason['time'] = $reason['bantime'];
        }

        $banReason['time'] = $banReason['time']*60*60;

        $startDate = strtotime($banReason['startdate']);

        $endDate = $startDate + $banReason['time'];
        $endDate = GB_TypeFactory::CreateBase('GB_Date', $endDate);
        $banReason['enddate'] = $endDate->getPgFormat();

        $common = self::getCommon();

        $sql = "
            SELECT
                id_user
            FROM
                ds_userban
            WHERE
                id_user = ".$user->getID()."
            ";
        $res2 = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(count($res2) > 0)
        {
            $sql = "
                UPDATE ds_userban
                SET
                    id_userbanreason = ".$reason['id_userbanreason'].",
                    startdate = '".$banReason['startdate']."',
                    enddate = '".$banReason['enddate']."'
                WHERE
                    id_user = ".$user->getID()."
                ";
        } else {
            $sql = "
                INSERT INTO ds_userban
                    (id_user, id_userbanreason, startdate, enddate)
                VALUES
                    (".$user->getID().", ".$reason['id_userbanreason'].", '".$banReason['startdate']."', '".$banReason['enddate']."')
                ";
        }

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    static public function UpdateUser (DE_User $user)
    {
        $common = self::getCommon();

        $active = $user->isActive()?'t':'f';

        $sql = "
            UPDATE ds_user
            SET
                time_delta = ".$user->getTimeDelta(true).",
                id_language = ".$user->getLanguageID().",
                password = '".$user->getPassword()."',
                accountactive = '".$active."',
                username = '".$user->getName()."'
            WHERE id_user = ".$user->getID()."";

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));

        self::_updateUserReasonBan($user);

        self::_updateUserPremiumBan($user);

        return true;
    }

    static public function GetTradeBan(DE_User $user, $worldID)
    {
        assert(is_int($worldID) && $worldID > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                count(*)
            FROM
                ds_trade_user_ban AS tb
            WHERE
                id_user = ".$user->getID()."
                AND
                (
                    tb.end_date > now()
                    OR
                    tb.end_date IS NULL
                )
                AND
                tb.start_date < now()
                AND
                tb.id_world = $worldID
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res[0]['count'];
    }

    static public function GetTradeBanDetails(DE_User $user)
    {
        $common = self::getCommon();

        $sql = "
            SELECT
                tb.end_date,
                tbr.reason_name,
                COALESCE(ltl.text, tbr.reason_name) AS reason
            FROM
                ds_trade_user_ban AS tb
                INNER JOIN ds_trade_user_ban_reason AS tbr USING (id_ban_user)
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = tbe.id_localizedreason AND ltl.id_language = ".$user->getLanguageID().")
            WHERE
                id_user = ".$user->getID()."
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(count($res) == 0)
        {
            return array();
        }

        return $res[0];
    }

    static public function GetBanReasonInfo($reasonName)
    {
        $common = self::getCommon();
        $sql = "
                SELECT
                    bantime,
                    id_userbanreason
                FROM
                    ds_userbanreason
                WHERE
                    upper(reasonname) = upper('".$reasonName."')
                ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(count($res))
        {
            return $res[0];
        }
        return $res;
    }

    static public function GetBanReason(DE_User $user)
    {
        $common = self::getCommon();

        $sql = "
            SELECT
                ub.startdate,
                ub.enddate,
                ubr.reasonname,
                ltl.text
            FROM
                ds_userban AS ub
                INNER JOIN ds_userbanreason AS ubr ON (ubr.id_userbanreason = ub.id_userbanreason)
                RIGHT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ubr.id_localizedname)
            WHERE
                ub.id_user = ".$user->getID()."
                AND
                ltl.id_language = ".$user->getLanguageID()."
                AND
                startdate < now()
                AND
                enddate > now()
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(count($res))
        {
            return $res[0];
        }

        return $res;
    }

    /**
     * Информация о премиум-бане (ВИП сауне)
     *
     * @param DE_User $user
     * @return unknown
     */
    static public function GetPremiumBan(DE_User $user)
    {
        $common = self::getCommon();

        $sql = "
            SELECT
                *
            FROM
                ds_user_premium_ban
            WHERE
                id_user = ".$user->getID()."
            ";
        $res = $common->getResultArray('', $sql, '', '', '');
        assert(empty($common->errormsg));

        if(count($res))
        {
            return $res[0];
        }

        return array();
    }

    static public function GetOAuthInfoByUserinstanceId($id_userinstance)
    {
        $common = self::getCommon();
        $sql  = "SELECT id_user FROM ds_userinstance WHERE id_userinstance={$id_userinstance}";
        $res = $common->getResultArray('', $sql, '', '', '');
        assert(empty($common->errormsg));

        if (!$res)
            return false;

        $gameuid = $res[0]['id_user'];
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);

        $sql = "SELECT game_uid, open_key, secret_key FROM ds_oauthinfo WHERE game_uid={$gameuid}";
        $res = $common->getResultArray('', $sql, '', '', '');
        assert(empty($common->errormsg));

        $_SESSION["dbId"] = $oldDbId;

        if ($res)
           return $res[0];
         else
           return false;
    }

    static public function GetOAuthInfoByUserId($id_user)
    {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);

        $sql = "SELECT game_uid, open_key, secret_key FROM ds_oauthinfo WHERE game_uid={$id_user}";
        $res = $common->getResultArray('', $sql, '', '', '');

        assert(empty($common->errormsg));

        $_SESSION["dbId"] = $oldDbId;


        if ($res)
            return $res[0];
        else
            return false;
    }
    /**
     * Получить список игроков, которых привел в игру игрок с логином
     *
     * @param string $login
     * @return array
     */
    static public function GetReferalsByLogin($login)
    {
        $common = self::getCommon();

        $sql = "SELECT
                      urr.id_user
                FROM
                    ds_user_registration_recomend urr,
                    ds_user u
                WHERE
                     u.username = '".pg_escape_string($login)."'
                     AND u.id_user = urr.id_user_recomend";

        $res = $common->getResultArray("", $sql, "", "", "");

        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetRecommendedUser($ref_user_id)
    {
        $common = self::getCommon();
        $common->setDbId(0);

        $sql = "SELECT
                      urr.id_user_recomend, u.username, u.id_language, u.email, u.registrationdate,u.from_partner,
              u.from_site_id, u.password
            FROM
                     ds_user_registration_recomend as urr
                     INNER JOIN ds_user as u ON (u.id_user = urr.id_user_recomend)
                WHERE
                     urr.id_user = $ref_user_id";


        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(count($res) == 0){
            return false;
        }

        return $res[0];
  }

  /**
     * Метод получения списка игроков с их рефералами
     *
     * @return array
     */
    static public function GetReferalsAll($dateTime)
    {
        $common = self::getCommon();
        $common->setDbId(0);

        $sql = "SELECT
                      urr.id_user_recomend, urr.id_user
                FROM
                    ds_user_registration_recomend as urr
                    INNER JOIN ds_user as u ON (u.id_user = urr.id_user)
                WHERE
                     u.registrationdate > '". $dateTime ."'";

        $res = $common->getResultArray("", $sql, "", "", "");

        if(!empty($common->errormsg)){
            return false;
        }

        return $res;
    }

    //Получение игроков с рефералами за заданный период
    static public function GetReferalsForPeriod($date1, $date2, $page, $itemscnt = 20)
    {
        $common = self::getCommon();
        $common->setDbId(0);

        if (!$page)
          $page = 1;

        $offset = ($page - 1)*$itemscnt;

        $sql = "SELECT
                    urr.id_user, u.ip_addr, u.username, u.email,
                    u.registrationdate,u.from_partner,
                    u.password, u.reg_referer,
                    urr.id_user_recomend, recu.username as recusername
                FROM
                    ds_user_registration_recomend as urr
                INNER JOIN ds_user as u ON (u.id_user = urr.id_user)
                INNER JOIN ds_user as recu ON (urr.id_user_recomend = recu.id_user)
                and u.registrationdate>='$date1' and
                u.registrationdate<='$date2' ORDER BY u.id_user DESC LIMIT {$itemscnt} OFFSET $offset";
        $res = $common->getResultArray("", $sql, "", "", "");

        if(!empty($common->errormsg)){
            return false;
        }

        if (!$res)
        return array(null,0);


        $sql = "SELECT  count(urr.id_user) as cnt
        FROM   ds_user_registration_recomend as urr
        INNER JOIN ds_user as u ON (u.id_user = urr.id_user)
        WHERE  u.registrationdate>='$date1' and
        u.registrationdate<='$date2'";
        $res2 = $common->getResultArray("", $sql, "", "", "");
//      echo $common->errormsg;
        return array($res, $res2[0]['cnt']);
    }

    /**
     * Метод получения списка игроков, зареганых за заданный период
     *
     * @return array
     */
    static public function getUsersCnt($date1, $date2, $pid)
    {
        $common = self::getCommon();
        $common->setDbId(0);

        $from_partner = '';

        if ($pid)
          $from_partner = 'AND from_partner='.$pid;
        $sql = "SELECT count(*) as cnt from ds_user where registrationdate >= to_timestamp('$date1','YYYY-MM-DD') and registrationdate <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS') {$from_partner}";
        $res = $common->getResultArray("", $sql, "", "", "");

        if(!empty($common->errormsg)){
            return false;
        }

        $sql = "SELECT count(*) as cnt from ds_user where registrationdate >= to_timestamp('$date1','YYYY-MM-DD') and registrationdate <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS') and activate_mail=1 {$from_partner}";
        $res2 = $common->getResultArray("", $sql, "", "", "");

        if(!empty($common->errormsg)){
            return false;
        }

        return array($res[0]['cnt'], $res2[0]['cnt']);
    }

    static function GetByIDVkontakte($id)
    {
        settype ( $id, 'integer' );
        assert ( $id >= 0 );

        $common = self::getCommon();
        $sql = "SELECT
                    id_user
                FROM
                    ds_user
                WHERE id_user_vkontakte = ".$id;

        $res = $common->getResultArray ( "",$sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res){
            return false;
        }else{
            return $res[0]['id_user'];
        }
    }

    static function setImageURL($id_user, $imurl)
    {
        $common = self::getCommon();
        $sql = "SELECT * FROM ds_user_has_image WHERE id_user={$id_user}";
        $res = $common->getResultArray ( "",$sql, "", "", "");
        
        if ($res)
          $sql = "update ds_user_has_image set image_url='{$imurl}' where id_user={$id_user}";      
        else 
          $sql = "INSERT INTO ds_user_has_image(id_user, image_url) VALUES({$id_user}, '{$imurl}')";
                          
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));       
    }
    
    static function GetByPopmogID($id)
    {
        settype ( $id, 'integer' );
        assert ( $id >= 0 );

        $common = self::getCommon();
        $sql = "SELECT
                  u.*,
                  uhi.image_url
                FROM
                    ds_user u
                LEFT JOIN ds_user_has_image AS uhi USING (id_user)                  
                WHERE id_user_popmog = ".$id;

        $res = $common->getResultArray ( "",$sql, "", "", "");
        assert(empty($common->errormsg));
//    SU_DebugTools::LogAll("$sql, res=".var_export($res, true),'popmog.log');
        if(!$res){
            return false;
        }else{
            return $res[0];
        }
    }

    static function GetIDVkontakteByUserId($id)
    {
        settype ( $id, 'integer' );
        assert ( $id >= 0 );

        $common = self::getCommon();
        $sql = "SELECT
                    id_user_vkontakte
                FROM
                    ds_user
                WHERE id_user = ".$id;

        $res = $common->getResultArray ( "",$sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res){
            return false;
        }else{
            return $res[0]['id_user_vkontakte'];
        }
    }

    static function InsertUserVkontakte($idVkontakte, $userName)
    {
        settype ( $idVkontakte, 'integer' );
        assert ( $idVkontakte >= 0 );

        assert(!empty($userName));

        $common = self::getCommon();
        $sql = "INSERT INTO DS_User
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
                            id_user_vkontakte,
                            activate_mail
                        )
                    VALUES
                        (
                            ".config('DEFAULT_LANGUAGE').",
                            '".pg_escape_string($userName)."',
                            NULL,
                            NULL,
                            'now()',
                            'now()',
                            false,
                            ".constant("REGISTRATION_ENABLED").",
                            false,
                            ".constant("DEFAULT_TIMEDELTA").",
                            $idVkontakte,
                            1
                        )
                    ";

        $res = $common->getResultArray ( "",$sql, "", "", "");
        assert(empty($common->errormsg));
    }


    static function InsertUserPopmog($userdata)
    {
        $userIdsocial = intval($userdata['id']);
        $login = $userdata['local_name'];




        if ($userdata['language']=='en')
        {
          $ulang = 2;
        }
        else if ($userdata['language']=='de')
        {
          $ulang = 1;
        }
        else
        {
      $ulang = constant('DEFAULT_LANGUAGE');
        }

        $common = self::getCommon();
        $sql = "INSERT INTO DS_User
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
                            id_user_popmog,
                            activate_mail,
                            from_partner,
                            ip_addr
                        )
                    VALUES
                        (
                            ".$ulang.",
                            '".$userdata['local_name']."',
                            NULL,
                            '".$userdata['email']."',
                            'now()',
                            'now()',
                            false,
                            ".constant("REGISTRATION_ENABLED").",
                            false,
                            ".constant("DEFAULT_TIMEDELTA").",
                            $userIdsocial,
                            1,
                            3,
                            '{$_SERVER['REMOTE_ADDR']}'
                            
                        )
                    ";

        $res = $common->getResultArray ( "",$sql, "", "", "");
        assert(empty($common->errormsg));
    }

}//END OF CLASS


?>