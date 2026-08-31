<?php

class SU_ValidateDoubleCombsSearch
{
    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;
    
    /**
     * Enter description here...
     *
     * @return DS_Common
     */
    static public function GetCommon()
    {
        if(!self::$common instanceof DS_Common)
        {
            self::$common = DS_Common::CreateForCurentDB(0);
        }
        
        return self::$common;
    }
    
    static public function IsUserInDeleteList($userID, $worldIdentifier)
    {
        assert(is_int($userID) && $userID > 0);
        assert(preg_match('/^[A-Z0-9]{1,3}$/', $worldIdentifier));
        
        $common = self::GetCommon();
        $common->setDbID(0);
        
        $sql = "
            SELECT
                *
            FROM
                ds_user_delete_list
            WHERE
                id_user = $userID
                AND
                world_identifier = '$worldIdentifier'
            ";
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            return true;
        }
        
        return false;
    }
    
    static public function GetInstance($userID, $dbID, $worldID)
    {
        assert(is_int($userID) && $userID > 0);
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID > 0);
        
        $common = self::GetCommon();
        $common->setDbId($dbID);
        
        $sql = "
            SELECT
                ui.id_userinstance,
                ui.creation_date,
                vm.enddate, 
                vm.password,
                cn.combname,
                r.ringnumber
            FROM
                ds_userinstance AS ui
                LEFT JOIN ds_vocationmode AS vm USING (id_userinstance)
                INNER JOIN ds_comb AS c USING (id_userinstance)
                INNER JOIN ds_combname AS cn USING (id_comb)
                INNER JOIN ds_combcomplex AS cc USING (id_combcomplex)
                INNER JOIN ds_ring AS r USING (id_ring)
            WHERE
                ui.id_user = $userID
                AND
                ui.id_world = $worldID
                AND
                r.ringnumber != 4
            ";
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            return $res[0];
        }
        
        return array();
    }
    
    static public function GetUserinstanceInfo($userID, $dbID, $worldID)
    {
        assert(is_int($userID) && $userID > 0);
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID > 0);
        
        $common = self::GetCommon();
        $common->setDbId($dbID);
        
        $sql = "
            SELECT
                ui.*,
                vm.*,
                (
                    select
                        max(lastvisit)
                    from
                        ds_loginhistory
                    where
                        id_user = ui.id_user
                ) as last_login,

                (
                    SELECT
                        count(*)
                    FROM
                        ds_trade_history
                    WHERE
                        from_user_id = $userID
                        and
                        id_to_category = 4 -- MONEY
                ) as count_trades_money,
                
                (
                    SELECT
                        count(*)
                    FROM
                        ds_trade_history
                    WHERE
                        from_user_id = $userID
                ) as count_trades,
                
                (
                    ARRAY_TO_STRING
                    (
                        ARRAY(
                            SELECT
                                cn.combname||'('||r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber||')'
                            FROM
                                ds_comb AS c
                                INNER JOIN ds_combname AS cn USING (id_comb)
                                INNER JOIN ds_combcomplex AS cc USING (id_combcomplex)
                                INNER JOIN ds_ring AS r USING (id_ring)
                            WHERE
                                c.id_userinstance = ui.id_userinstance
                            ORDER BY r.ringnumber, cc.complexnumber, c.combnumber
                        ),
                        ', '
                    )
                ) as comb_names
            FROM
                ds_userinstance AS ui
                LEFT JOIN ds_vocationmode AS vm ON (vm.id_userinstance = ui.id_userinstance AND vm.password ILIKE 'pereezd%')
            WHERE
                ui.id_user = $userID
                AND
                ui.id_world = $worldID
            ";
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            return $res[0];
        }
        
        return array();
    }
    
    static public function GetUsersWithDoubleCombs()
    {
        $common = self::GetCommon();
        $common->setDbId(0);
        
        $sql = "
            SELECT
                id_user,
                count(*)
            FROM
                ds_userinstancecollection AS uic
                INNER JOIN ds_user AS u USING (id_user)
            WHERE
                not exists (
                    select
                        id_user
                    from
                        ds_npc_user
                    where
                        id_user = uic.id_user
                )
                AND
                NOT u.admin
                AND
                u.accountactive
                AND
                u.id_user > 0
                AND
                uic.dbname != 'premium'
                AND
                uic.dbname != 'hell'
                AND
                NOT EXISTS (
                    SELECT
                        id_user
                    FROM
                        ds_multiworldaccess
                    WHERE
                        id_user = uic.id_user
                )
                AND
                NOT EXISTS (
                    SELECT
                        id_user
                    FROM
                        ds_usersallowedcollection
                    WHERE
                        id_user = uic.id_user
                        AND
                        id_worldcollection = uic.id_worldcollection
                )
            GROUP BY id_user
            HAVING count(*) > COALESCE((
                    SELECT
                        count
                    FROM
                        ds_user_allow_instances
                    WHERE
                        id_user = uic.id_user
                ), 1)
            ORDER BY id_user
        ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function AddVocationMode(DE_User $user, DE_WorldFromCollection $world)
    {
        $common = self::GetCommon();
        $common->setDbId($common->getDbIdByName($world->getDBName()));
        
        $sql = "
            INSERT INTO ds_vocationmode
                (id_userinstance, startdate, enddate, password)
            SELECT
                ui.id_userinstance,
                now(),
                null,
                'pereezd_".rand()."'
            FROM
                ds_userinstance AS ui
            WHERE
                ui.id_user = ".$user->getID()."
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function AddPermisionForInstances(DE_User $user, $count)
    {
        assert(is_int($count) && $count >= 0);
        
        $common = self::GetCommon();
        $common->setDbId(0);
        
        $sql = "
            SELECT
                count
            FROM
                ds_user_allow_instances
            WHERE
                id_user = ".$user->getID()."
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            $sql = "UPDATE ds_user_allow_instances SET count = $count, last_change_date = now() WHERE id_user = ".$user->getID()."";
        } else {
            $sql = "
                INSERT INTO ds_user_allow_instances
                    (id_user, last_change_date, count)
                VALUES
                    (".$user->getID().", now(), $count)";
        }
        
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
}

?>