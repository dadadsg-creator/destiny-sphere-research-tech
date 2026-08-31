<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * —таитческий клас с библиотекой запросов по классу пользовател€.
 */
class DS_UserInstance extends DS_Base
{
    static public function GetConstantPartByID($id)
    {

        settype ( $id, 'integer' );

        $common = self::getCommon();

        $sql = "SELECT
                    *
                FROM ds_userinstance
                WHERE id_userinstance = ".$id;
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        }else{
            return $res[0];
        }
    }

    /*static public function GetNewbDefendByID($id)
    {
        settype ( $id, 'integer' );
        assert ( $id >= 0 );

        $common = self::getCommon();

        $sql = "SELECT
                    newb_defend_enddate
                FROM
                    ds_userinstance
                WHERE
                    id_userinstance = ".$id;

        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        }else{
            return $res[0]['newb_defend_enddate'];
        }

    }*/

    /*static public function GetLastRefreshByID($id)
    {
        settype ( $id, 'integer' );
        assert ( $id > 0 );

        $common = self::getCommon();

        $sql = "SELECT
                    lastrefresh
                FROM ds_userinstance
                WHERE id_userinstance = ".$id;
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0]['lastrefresh'];
    }*/

    static public function UpdateUserInstance(DE_UserInstance $userInstance)
    {
        $common = self::getCommon();

        $sql = "UPDATE ds_userinstance
                SET lastrefresh = '".$userInstance->getLastRefresh()->getPgFormat()."',
                    newb_defend_enddate = '".$userInstance->getNewbDefendEndDate()->getPgFormat()."',
                    nubhelplevel = ".$userInstance->getNubhelplevel().",
                    help_var='".$userInstance->getHelp_var()."',
                    random_defend_enddate='".$userInstance->getRandomDefendEndDate()->getPgFormat()."',
                    random_defend_enddate_max='".$userInstance->getRandomDefendEndDateMax()->getPgFormat()."',
                    id_league = '".$userInstance->getLeagueId()."'
                WHERE id_userinstance = ".$userInstance->getID();

        $res = $common->executeQuery($sql);

        assert(empty($common->errormsg) && count($res) == 1);

        return true;
    }

    static public function GetBattleXPByUIID($uiid)
    {
        settype($uiid, 'integer');

        $common = self::getCommon();

        $sql = "SELECT
                    *
                FROM ds_battlexp
                WHERE id_userinstance = ".$uiid;
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function UpdateBattleXP(DE_BattleXP $battleXP)
    {
        if($battleXP->getXP() == 0)
        {
            return true;
        }

        $common = self::getCommon();

        if($battleXP->getStartWithEmptyFlag())
        {
            $sql = "INSERT INTO
                        ds_battlexp
                    (id_userinstance, value, absolute_value, npc_xp)
                    VALUES
                    (".$battleXP->getID().", ".$battleXP->getXP().", ".$battleXP->getXPAbsolute().", ".$battleXP->getNpcXP().")";
            $res = $common->executeQuery($sql);
            assert(empty($common->errormsg));
        }else{
            $sql = "UPDATE
                        ds_battlexp
                    SET value = ".$battleXP->getXP().", absolute_value = ".$battleXP->getXPAbsolute().", npc_xp = ".$battleXP->getNpcXP().
                    "WHERE id_userinstance = ".$battleXP->getID();
            $res = $common->executeQuery($sql);
            assert(empty($common->errormsg));
        }

        return true;
    }

    static public function GetUserInstanceIDByUserIDAndWorldID($userID, $worldID)
    {
        assert(is_int($userID) && $userID >= 0);

        assert(is_int($worldID) && $worldID > 0);
        $common = self::getCommon();
        $sql = "
            SELECT
                id_userinstance
            FROM
                ds_userinstance
            WHERE
                id_user = $userID
                AND
                id_world = $worldID
            ";

        $res = $common->getResultArray ( "", $sql, "", "", "");
        
        assert(empty($common->errormsg));

        if(count($res) > 0)
        {
            return (int)$res[0]['id_userinstance'];
        }

        return 0;
    }

    static public function isAllowRefresh($userInstanceID)
    {
        return true;
        assert(is_int($userInstanceID));
        $common = self::getCommon();

        // достаточно ли прошло времени
        $sql = "select isAllowRefresh($userInstanceID) as ar";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);

        if($res[0]['ar'] != 1)
        {
            return false;
        }

        return true;
    }

    static public function StartTrainSpeedup($uiid, $days)
    {
        $common = self::getCommon();
        $sql = "select build_speedup_enddate>now() as check from ds_userinstance where id_userinstance=$uiid";
        $res = $common->getResultArray("", $sql);
        if ($res[0]['check']=='t') {
            $sql = "update ds_userinstance set build_speedup_enddate = build_speedup_enddate + interval '$days days',
                 build_speedup_active=true
                 where id_userinstance=$uiid";
            $common->executeQuery($sql);
        } else {
            $sql = "update ds_userinstance set build_speedup_enddate = now() + interval '$days days',
                 build_speedup_active=true
                 where id_userinstance=$uiid";
            $common->executeQuery($sql);
            $sql = "update ds_unitinproduction set endtime = endtime - (endtime-now()) / 2
                where id_attachedbuilding in
                    (select id_attachedbuilding from ds_attachedbuilding where id_comb in
                        (select id_comb from ds_comb where id_userinstance=$uiid))";
            $common->executeQuery($sql);
            $sql = "update ds_eventqueue set eventendtime=eventendtime - (eventendtime-now()) / 2
                where id_event=20 and id_eventqueue in
                    (select id_eventqueue from ds_eventparticipant where id_userinstance=$uiid)";
            $common->executeQuery($sql);
        }
    }

    static public function CheckEndTrainSpeedup($uiid)
    {
        $common = self::getCommon();
        $sql = "select build_speedup_active and build_speedup_enddate<now() as check from ds_userinstance where id_userinstance=$uiid";
        $res = $common->getResultArray("", $sql);
        if ($res[0]['check']=='t') {
            $sql = "update ds_userinstance set build_speedup_active=false where id_userinstance=$uiid";
            $common->executeQuery($sql);
            $sql = "update ds_unitinproduction set endtime = endtime + (endtime-now())
                where id_attachedbuilding in
                    (select id_attachedbuilding from ds_attachedbuilding where id_comb in
                        (select id_comb from ds_comb where id_userinstance=$uiid))";
            $common->executeQuery($sql);
            $sql = "update ds_eventqueue set eventendtime=eventendtime + (eventendtime-now())
                where id_event=20 and id_eventqueue in
                    (select id_eventqueue from ds_eventparticipant where id_userinstance=$uiid)";
            $common->executeQuery($sql);
        }
    }

}//END OF CLASS

?>