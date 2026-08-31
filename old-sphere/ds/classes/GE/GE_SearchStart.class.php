<?php $CVS_Version = '$Revision$ ; $Date$';

class GE_SearchStart extends GE_SearchBase
{
    static public function CheckUserPasswordChange($userID)
    {
        assert(is_int($userID) && $userID > 0);

        $sql = "
            SELECT
                u.registrationdate > '2007-12-25 00:00:00' as reg,
                cl.id_user
            FROM
                ds_user AS u
                LEFT JOIN ds_userdata_change_log AS cl ON (
                    cl.id_user = u.id_user
                    AND
                    cl.change_date > '2007-12-25 00:00:00'
                    AND
                    (cl.new_password != cl.old_password)
                )
            WHERE
                u.id_user = $userID
            ";
        $res = self::_getCommon()->getResultArray("", $sql, "", "");
        assert(empty(self::_getCommon()->errormsg) && count($res) > 0);

        if($res[0]['reg'] == 't' || $res[0]['id_user'] > 0)
        {
            return true;
        }

        return false;
    }

    static public function GetUserIdByToken($token)
    {
        self::_getCommon()->setDbId(0);   
        $sql = "SELECT game_uid, open_key, secret_key FROM ds_oauthinfo WHERE open_key='".$token->key."'";
        $res = self::_getCommon()->getResultArray("", $sql, "", "");
        //::LogAll('sql='.$sql.', res='.var_export($res, true), "oauth.log");
        assert(empty(self::_getCommon()->errormsg));
        return $res[0]; 
    }

    static public function GetUserIdByMetaUALogin($userinfo)
    {
        self::_getCommon()->setDbId(0);   
        $sql = "SELECT game_uid FROM ds_oauthinfo WHERE token_username='".$userinfo->sUserName."'";
        $res = self::_getCommon()->getResultArray("", $sql, "", "");
        //::LogAll('sql='.$sql.', res='.var_export($res, true), "oauth.log");
        assert(empty(self::_getCommon()->errormsg));
        return $res[0]; 
    }
    
    static public function GetUserByToken($token)
    {
        self::_getCommon()->setDbId(0);   
        $sql = "SELECT * FROM ds_oauthinfo WHERE open_key='".$token."'";
        $res = self::_getCommon()->getResultArray("", $sql, "", "");
        
        assert(empty(self::_getCommon()->errormsg));
        if ($res)
          return $res[0];   
        else 
          return false;  
    }
        
    static public function ChangeUserPassword($userID, $newPass)
    {
        assert(is_int($userID) && $userID > 0);
        assert(!empty($newPass));
        self::_getCommon()->setDbId(0);

        $sql = "
            UPDATE
                ds_user
            SET
                password = '$newPass'
            WHERE
                id_user = $userID
            ";
        self::_getCommon()->executeQuery($sql);
        assert(empty(self::_getCommon()->errormsg));
    }

    static public function LogPasswordChange($userID, $oldPass, $newPass)
    {
        assert(is_int($userID) && $userID > 0);
        assert(!empty($oldPass));
        assert(!empty($newPass));

        $sql = "
            INSERT INTO ds_password_change
                (id_user, old_password, new_password)
            VALUES
                ($userID, '$oldPass', '$newPass')
            ";
//      self::_getCommon()->executeQuery($sql);
//      assert(empty(self::_getCommon()->errormsg));
    }

    static public function GetWorldsWhereUserNotRegistered($userID)
    {
        assert(is_int($userID) && $userID > 0);
        self::_getCommon()->setDbId(0);

        $sql = "
            SELECT
                wc.*,
                uac.id_world AS allowed
            FROM
                ds_worldcollection AS wc
                LEFT JOIN ds_usersallowedcollection AS uac ON (uac.id_worldcollection = wc.id_worldcollection AND uac.id_user = ".$userID.")
            WHERE
                wc.id_worldcollection NOT IN
                (
                    SELECT wc2.id_worldcollection
                    FROM
                        ds_worldcollection AS wc2
                    INNER JOIN ds_userinstancecollection AS uic ON (uic.id_worldcollection = wc2.id_worldcollection)
                    WHERE
                        uic.id_user = ".$userID."
                )
            ORDER BY wc.sort_order DESC, wc.worldname";
        $res = self::_getCommon()->getResultArray("", $sql, "", "");
        assert(empty(self::_getCommon()->errormsg));

        return $res;
    }

    static public function GetWorldsWhereUserIsRegistered($userID)
    {
        assert(is_int($userID));
        self::_getCommon()->setDbId(0);

        $sql = "
                SELECT
                    wc.*,
                    uic.*,
                    uac.id_world AS allowed
                FROM
                    ds_worldcollection AS wc
                    INNER JOIN ds_userinstancecollection AS uic ON (uic.id_worldcollection = wc.id_worldcollection)
                    LEFT JOIN ds_usersallowedcollection AS uac ON (uac.id_worldcollection = wc.id_worldcollection AND uac.id_user = uic.id_user)
                WHERE
                    uic.id_user = ".$userID."
                    ORDER BY wc.sort_order DESC, wc.worldname
                ";
        $res = self::_getCommon()->getResultArray("", $sql, "", "");
        assert(empty(self::_getCommon()->errormsg));

        return $res;
    }

    static public function GetCombTemplateModifier($worldId)
    {
        assert(is_int($worldId) && $worldId > 0);

        $sql = "
            SELECT
                value
            FROM
                ds_gamemodifier
            WHERE
                id_world = ".$worldId."
                AND
                modifiername = 'COMB_CREATE_FROM_TEMPLATES'
            ";

        $res = self::_getCommon()->getResultArray("", $sql, "", "");
        assert(empty(self::_getCommon()->errormsg));

        return (int)$res[0]['value'];
    }

    static public function GetRaceList($lang)
    {
        $sql = "
            SELECT
                l.text, r.id_race
            FROM
                ds_race AS r
                INNER JOIN ds_localizedtextlanguage AS l ON l.id_localizedtext = r.id_localizedname
            WHERE
                l.id_language = ".$lang."
            ";

        $res = self::_getCommon()->getResultArray("GE_RACE_LIST_LANG_".$lang, $sql, "", "", "");
        assert(empty(self::_getCommon()->errormsg));

        return $res;
    }

    static public function GetWorldCollection($lang)
    {
        settype($lang, 'int');
        assert($lang > 0);

        $sql = "
            SELECT
                wc.id_worldcollection,
                wc.id_world,
                wc.worldname,
                wc.description,
                wc.profi,
                wc.max_users,
                wc.id_worldtype,
                wc.dbname,
                wc.world_identifier,
                wc.for_new_players,
                wc.short_desc,
                lt.text AS type,
                ld.text AS worldtype_descr
            FROM
                ds_worldcollection AS wc
                INNER JOIN ds_worldtype AS wt ON (wt.id_worldtype = wc.id_worldtype)
                INNER JOIN ds_localizedtextlanguage AS lt ON (lt.id_localizedtext = wt.id_localizedtype)
                INNER JOIN ds_localizedtextlanguage AS ld ON (ld.id_localizedtext = wt.id_localizeddescription)
            WHERE
                lt.id_language = ".$lang."
                AND
                ld.id_language = ".$lang."
                AND
                wc.world_identifier != 'UC'
                AND 
                wc.worldname != '-'
                AND
                wc.world_identifier != 'TRN'
                AND
                wc.world_identifier != 'TLC'
                AND
                wc.world_identifier != 'DRK'
                
            ORDER BY
                wc.sort_order DESC, wc.profi, wc.id_worldtype, wc.worldname";

        //$res = self::_getCommon()->getResultArray("GE_WORLD_COLLECTION_LANG_".$lang, $sql, "", "", //"GLOBAL_CACHE");
        $res = self::_getCommon()->getResultArray("", $sql, "", "", "NO_CACHE");

    assert(empty(self::_getCommon()->errormsg));
        return $res;
    }

    static public function GetPremiumDaysLeft($uid)
    {
        assert(is_int($uid));
        if (!self::$common) {
            self::setCommon(DS_Common::CreateForCurrentDB());
        }

        $sql = "
            SELECT
                date_part('epoch', enddate) as enddate,
                date_part('day', enddate - now()) AS day,
                date_part('hour', enddate - now()) AS hour
            FROM
                ds_userprofidata
            WHERE
                id_user = $uid ";

        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(!$res)
        {
            return false;
        } else {
            return $res[0];
        }
    }

    static public function GetMultiWorldAccessForUID($UID)
    {
        assert(is_int($UID) && $UID > 0);

        $sql = "
            SELECT
                id_user
            FROM
                ds_multiworldaccess
            WHERE
                id_user = ".$UID."
            ";
        $res = self::_getCommon()->getResultArray("", $sql, "", "", "");
        assert(empty(self::_getCommon()->errormsg));

        if($res)
        {
            return true;
        } else {
            return false;
        }
    }

    static public function GetUserNonProfiInstances(DE_User $user)
    {
        $sql = "
            SELECT
                id_userinstance
            FROM
                ds_userinstancecollection AS uic
                INNER JOIN ds_worldcollection AS wc ON (wc.id_worldcollection = uic.id_worldcollection)
            WHERE
                uic.id_user = ".$user->getID()."
                AND NOT wc.profi
            ";
        $res = self::_getCommon()->getResultArray("", $sql, "", "", "");
        assert(empty(self::_getCommon()->errormsg));

        return $res;
    }

    static public function GetUserAllowed($userID, $worldId, $dbname)
    {
        assert(is_int($userID) && $userID > 0);
        self::_getCommon()->setDbId(0);

        $sql = "
            SELECT
                uac.id_user,
                uac.count
            FROM
                ds_usersallowedcollection AS uac
            WHERE
                uac.id_user = ".$userID."
                AND
                uac.id_world = ".$worldId."
                AND
                uac.dbname = '".$dbname."'
            ";

        $res = self::_getCommon()->getResultArray("", $sql, "", "", "");
        assert(empty(self::_getCommon()->errormsg));

        return $res;
    }

    static public function GetWorldType($worldId)
    {
        assert(is_int($worldId) && $worldId > 0);

        $sql = "
            SELECT
                wt.worldtype
            FROM
                ds_world w
                INNER JOIN ds_worldhastype wht ON (w.id_world = wht.id_world)
                INNER JOIN ds_worldtype wt ON (wht.id_worldtype = wt.id_worldtype)
            WHERE
                w.id_world = ".$worldId;

        $res = self::$common->getResultArray("world__type_by_world_id_".$worldId, $sql, "", "", "GLOBAL_CACHE");

    assert(empty(self::$common->errormsg));

        if(!$res)
        {
            return false;
        }

        return $res[0]['worldtype'];
    }

    static public function GetFreeCombs($worldID, $race)
    {
        assert(is_int($worldID) && $worldID > 0);
        assert(is_int($race) && $race > 0);

        $sql = "
            SELECT
                count(co.id_comb) AS c
            FROM
                ds_comb AS co,
                ds_combcomplex AS cc,
                ds_ring AS ri,
                ds_world AS wo
            WHERE
                co.id_combcomplex = cc.id_combcomplex AND
                cc.id_ring = ri.id_ring AND
                ri.id_world = wo.id_world AND
                co.id_userinstance is NULL AND
                wo.id_world = ".$worldID." AND
                ri.ringnumber = (
                    SELECT
                        ringnumber
                    FROM
                        ds_racehasbasering
                    WHERE
                        id_race = ".$race."
                )
                AND
                NOT EXISTS (
                    SELECT
                        id_comb
                    FROM
                        ds_reservedcomb
                    WHERE
                        id_comb = co.id_comb
                )";

        $res = self::$common->getResultArray("free_comb_on_ring_id_world_id_race_".$worldID."_".$race, $sql, "", "", "");

        assert(empty(self::$common->errormsg));

        if(!$res)
        {
            return 0;
        }

        return $res[0]['c'];
    }

    static public function GetScaling($UID)
    {
        assert(is_int($UID) && $UID > 0);

        $sql = "
            SELECT
                scaling
            FROM
                ds_userprofitechdata
            WHERE
                id_user = ".$UID;

        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(!$res)
        {
            return false;
        }
        return $res[0]['scaling'];
    }

    static public function UpdateWindowKey($UIID, $windowKey)
    {
        assert(is_int($UIID) && $UIID > 0);

        $sql = "
            SELECT
                *
            FROM
                ds_userinstancewindowkey
            WHERE
                id_userinstance = $UIID
            ";

        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(count($res) == 0)
        {
            $sql = "
                INSERT INTO ds_userinstancewindowkey
                    (windowkey, id_userinstance)
                VALUES
                    (".$windowKey.", ".$UIID.")
                ";
        } else {
            $sql = "
                UPDATE
                    ds_userinstancewindowkey
                SET
                    windowkey = ".$windowKey."
                WHERE
                    id_userinstance = ".$UIID."
                ";
        }

        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }

    static public function GetUserIdByEMail($email)
    {
        assert(!empty($email));
        
        $email = trim($email);

        $sql = "
            SELECT
                id_user
            FROM
                ds_user
            WHERE
                upper(email) = upper('".$email."')
            ";
    
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(!$res)
        {
            return false;
        }

        return (int)$res[0]['id_user'];

    }

    static public function GetUserInfo($UIID, $login, $password)
    {
        assert(!empty($login));
        //assert(!empty($password));
        assert(is_int($UIID) && $UIID > 0);

        $sql = "
                SELECT
                    u.ID_Language,
                    u.id_user,
                    wo.ID_World,
                    ra.ID_Race,
                    ri.ID_Ring,
                    cc.ID_CombComplex,
                    co.ID_Comb,
                    co.CombNumber,
                    l.shortname,
--                  co.combscore,
                    wt.worldtype,
                    (
                        SELECT
                            CASE WHEN
                                count(id_attachedbuilding) - 4 < 0
                            THEN
                                0
                            ELSE
                                count(id_attachedbuilding) - 4
                            END
                        FROM
                            ds_attachedbuilding
                        WHERE
                            id_comb = co.id_comb
                    ) as combscore
                FROM
                    DS_UserInstance ui,
                    DS_User u,
                    DS_World wo,
                    DS_Race ra,
                    DS_Ring ri,
                    DS_CombComplex cc,
                    DS_Comb co,
                    ds_language l,
                    ds_worldhastype wht,
                    ds_worldtype wt
                WHERE
                    wht.id_world = wo.id_world
                    AND
                    wht.id_worldtype = wt.id_worldtype
                    AND
                    ui.ID_User = u.ID_User
                    AND
                    ui.ID_World = wo.ID_World
                    AND
                    ui.ID_Race = ra.ID_Race
                    AND
                    co.ID_UserInstance = ui.ID_UserInstance
                    AND
                    co.ID_CombComplex = cc.ID_CombComplex
                    AND
                    cc.ID_Ring = ri.ID_Ring
                    AND
                    ri.ringnumber != 4
                    AND
                    ui.ID_UserInstance = ".$UIID."
                    AND
                    u.id_language = l.id_language
                    AND
                    u.username = '".$login."'
                    --AND
                    --u.password = '".$password."'
                ";
        $res = self::$common->getResultArray("", $sql, "", "", "NO_CACHE");
        
        assert(empty(self::$common->errormsg));

        assert(count($res) == 1);

        return $res[0];
    }

    static public function GetCountSupportRequest($userId)
    {
        assert(is_int($userId));
        assert($userId > 0);

        $sql = "
            SELECT
                count(*)
            FROM
                ds_usersupportrequest
            WHERE
                id_user = ".$userId."
                AND
                date > now() - '24 hour'::interval
            ";

        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res[0]['count'];
    }


    static public function SetSupportRequest($data)
    {
        assert(is_array($data));

        assert(key_exists('id_user', $data));
        assert(key_exists('is_premium', $data));
        assert(key_exists('world_name', $data));
        assert(key_exists('text', $data));

        $sql = "
            INSERT INTO ds_usersupportrequest
                (id_user, date, world_name, is_premium, text)
            VALUES
                (".$data['id_user'].", now(), '".$data['world_name']."', '".$data['is_premium']."', E'".$data['text']."')
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }

    static public function DisableRamblerLogin($ramblerLogin)
    {
        $res = self::GetRamblerIDInfo($ramblerLogin);

        if(count($res) == 0)
        {
            $sql = "
                INSERT INTO ds_ramblerlogin
                    (rambler_login, login_enable, registration_enable)
                VALUES
                    ('".pg_escape_string($ramblerLogin)."', 'f', 'f')
                ";
        } else {
            $sql = "UPDATE ds_ramblerlogin SET login_enable = 'f'";
        }

        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }

    static public function DisableRamblerRegistration($ramblerLogin)
    {
        $res = self::GetRamblerIDInfo($ramblerLogin);

        if(count($res) == 0)
        {
            $sql = "
                INSERT INTO ds_ramblerlogin
                    (rambler_login, login_enable, registration_enable)
                VALUES
                    ('".pg_escape_string($ramblerLogin)."', 't', 'f')
                ";
        } else {
            $sql = "UPDATE ds_ramblerlogin SET registration_enable = 'f'";
        }

        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }

    static public function GetRamblerIDInfo($ramblerLogin)
    {
        $sql = "
            SELECT
                login_enable,
                registration_enable
            FROM
                ds_ramblerlogin
            WHERE
                upper(rambler_login) = upper('".$ramblerLogin."')
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(count($res) > 0)
        {
            return $res[0];
        }

        return array();
    }

    static public function GetWorldStatus($worldId, $dbId)
    {
        assert(is_int($worldId));
        assert($worldId > 0);
//      assert(key_exists($dbId, $GLOBALS['dbWorldAccess']));
        if(!key_exists($dbId, $GLOBALS['dbWorldAccess']))
        {
            return 'CLOSE';
        }

        $dbName = $GLOBALS['dbWorldAccess'][$dbId]['server'];

        self::$common = DS_Common::CreateForCurentDB(0);//->setDbID(0);

        $sql = "
        SELECT
            COALESCE
            (
                (
                    SELECT
                        ws.status_name
                    FROM
                        ds_worldcollection AS w
                        INNER JOIN ds_worldstatus AS ws ON (ws.id_worldstatus = w.world_status)
                    WHERE
                        w.id_world = $worldId
                        AND
                        w.dbname = '$dbName'
                )
                ,
                'CLOSE'
            ) as status_name
            ";

        $res = self::$common->getResultArray("", $sql, "", "", "");

        assert(empty(self::$common->errormsg));

        return $res[0]['status_name'];
    }

    static public function GetWorldStatusDirect($worldId, $dbId)
    {
        assert(is_int($worldId));
        assert($worldId > 0);
        assert(is_int($dbId));
        assert($dbId >= 0);

        self::$common->setDbID($dbId);

        $sql = "
            SELECT
                ws.status_name
            FROM
                ds_world AS w
                INNER JOIN ds_worldstatus AS ws ON (ws.id_worldstatus = w.world_status)
            WHERE
                w.id_world = $worldId
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(count($res) != 1)
        {
            return 'CLOSE';
        }

        return $res[0]['status_name'];
    }

    static public function GetCountEmailChanges($id_user)
    {
        assert(is_int($id_user) && $id_user > 0);

        $sql = "
            SELECT
                count(*)
            FROM
                ds_userdata_change_log
            WHERE
                old_email != new_email
                AND
                id_user = $id_user
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res[0]['count'];
    }

    static public function ChangeEmail(DE_User $user, $email)
    {
        assert(!empty($email));

        self::$common->setDbId(0);

        $sql = "
            UPDATE
                ds_user
            SET
                email = E'".pg_escape_string($email)."'
            WHERE
                id_user = ".$user->getID()."
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));

        return true;
    }

    static public function GetUserHelp(DE_User $user)
    {
        self::$common->setDbId(0);

        $sql = "
            SELECT
                help_show
            FROM
                ds_user_help_show
            WHERE
                id_user = ".$user->getID()."
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(count($res) > 0)
        {
            return (int)$res[0]['help_show'];
        }

        return 0;
    }

    static public function DecHelpShowCounter(DE_User $user)
    {
        self::$common->setDbId(0);

        $sql = "UPDATE ds_user_help_show SET help_show = help_show - 1 WHERE id_user = ".$user->getID();
        self::_getCommon()->executeQuery($sql);
        assert(empty(self::_getCommon()->errormsg));
    }

    static public function _getPollCurrentQuestoins($worldsToEnter)
    {
        //return;
        //если миров для входа нет
        if (empty($worldsToEnter)) {
            return;
        }

        $where = ' AND (';
        $or = '';

        foreach ($worldsToEnter as $worldArr => $worldName)
        {
            $temp = explode('_', $worldArr);

            $bdId = $temp[2];
            $dbName = $GLOBALS['dbWorldAccess'][$temp[1]]['server'];

            $where .= $or." '$dbName' IN (
                   SELECT
                         pw.dbname
                   FROM
                       ds_poll_question_worlds pw
                   WHERE
                       pw.id_question = p.id_question
                       )
                   AND
                  $bdId IN (
                   SELECT
                         pw.id_world
                   FROM
                       ds_poll_question_worlds pw
                   WHERE
                       pw.id_question = p.id_question
                       )
            ";

            $or = ' OR ';
        }

        $where .= ')';

        self::_getCommon()->setDbId(0);

        $sql = "SELECT
                *
            FROM
                ds_poll_question as p
            WHERE
                is_active = 't'
                $where";

        $res = self::_getCommon()->getResultArray("", $sql, "", "", "");
        assert(empty(self::_getCommon()->errormsg));

        return $res;
    }

    static public function _getPollAnswers($id_question)
    {
        assert(is_int($id_question) && $id_question > 0);

        self::_getCommon()->setDbId(0);

        $sql = "
            SELECT
                *
            FROM
                ds_poll_answer
            WHERE
                id_question = ".$id_question."
            ";

        $res = self::_getCommon()->getResultArray("", $sql, "", "", "");
        assert(empty(self::_getCommon()->errormsg));

        if(count($res) > 0)
        {
            return $res;
        }

        return 0;
    }

    static public function IsUserAnswered($userID, $id_question)
    {
        assert(is_int($userID) && $userID > 0);
        assert(is_int($id_question) && $id_question > 0);

        self::_getCommon()->setDbId(0);

        $sql = "
            SELECT
                *
            FROM
                ds_poll_result r
            WHERE
                id_question = ".$id_question."
                AND
                id_user = ".$userID."
            ";

        $res = self::_getCommon()->getResultArray("", $sql, "", "", "");
        assert(empty(self::_getCommon()->errormsg));

        if(count($res) > 0)
        {
            return true;
        }

        return false;
    }

    static public function _getPollResult($userID, $id_question)
    {
        assert(is_int($userID) && $userID > 0);
        assert(is_int($id_question) && $id_question > 0);

        self::_getCommon()->setDbId(0);

        $sql = "
            SELECT
                pq.question,
                pa.answer,
                (
                    SELECT
                        count(*)
                    FROM
                        ds_poll_result pr
                    WHERE
                        pr.id_answer = pa.id_answer
                ) as count,
                (
                    SELECT
                        count(*)
                    FROM
                        ds_poll_result pr
                    WHERE
                        pr.id_question = pa.id_question
                         AND
                        pr.id_answer != 0
                ) as total_count
            FROM
                ds_poll_question pq
            INNER JOIN ds_poll_answer pa ON (pq.id_question = pa.id_question)
                WHERE
                    pq.id_question = $id_question
                    -- AND
                    --pr.id_answer != 0
            ";


        $res = self::_getCommon()->getResultArray("", $sql, "", "", "");
        assert(empty(self::_getCommon()->errormsg));

        return $res;
    }

    static public function SubmitPoll(DE_User $user, $data)
    {
        assert(is_array($data));

        assert(key_exists('id_question', $data));
        assert(key_exists('id_answer', $data));

        self::_getCommon()->setDbId(0);

        $sql = "
            INSERT INTO
                ds_poll_result
                    (id_question, id_answer, id_user)
                VALUES
                    (".$data['id_question'].", ".$data['id_answer'].", ".$user->getID().")
            ";

        self::_getCommon()->executeQuery($sql);
        assert(empty(self::_getCommon()->errormsg));
    }
}
?>