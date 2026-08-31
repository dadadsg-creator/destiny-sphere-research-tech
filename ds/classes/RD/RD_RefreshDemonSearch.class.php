<?php $CVS_Version = '$Revision$ ; $Date$';
class RD_RefreshDemonSearch
{
    static public function GetTransactionsCount(DS_Common $common, $DBID)
    {
        assert(is_int($DBID) && $DBID >= 0);
        
        $dbName = $GLOBALS['dbWorldAccess'][$DBID]['dbname'];
        assert(!empty($dbName));
        
        $sql = "SELECT 
                    count(pid) as activity
                FROM 
                    pg_stat_activity
                WHERE 
                    datname = '".$dbName."'";   
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res[0]['activity'];
    }
    
    static public function GetUIIDWithMaxEndEventsCount(DS_Common $common, $depricatedUIIDs)
    {       
        assert(is_array($depricatedUIIDs));
        
        if(count($depricatedUIIDs) > 0)
        {
            $subString = " AND ep.id_userinstance NOT IN (".implode(', ', $depricatedUIIDs).")";
        }else{
            $subString = "";
        }
        
        // $offset = rand(0, 10)*5;
        
        $sql = "SELECT
                    ep.id_userinstance,
                    count(*) as colvo
                FROM
                    ds_userinstance ui, ds_eventqueue eq, ds_eventparticipant ep
                    LEFT JOIN ds_vocationmode vm on (
                        vm.enddate IS NULL
                        AND
                        vm.id_userinstance = ep.id_userinstance
                    )
                WHERE ui.id_userinstance = ep.id_userinstance
                    AND eq.id_eventqueue = ep.id_eventqueue
                    AND eq.istrash = false
                    AND eq.eventendtime < now()
                    AND ui.lastrefresh < (now() - '10 mins'::interval)
                    AND vm.id_userinstance is null
                    ".$subString."
                GROUP BY ep.id_userinstance
                ORDER BY colvo DESC
                LIMIT 20";
        $res = $common->getResultArray("", $sql, "", "", "");
        if($res === false)
        {
            $common->stopErrorBlocking();
            return false;
        }
        //assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            return $res;
        }else{
            return false;
        }
    }
    
    static public function GetUIIDData(DS_Common $common, $UIID)
    {       
        
        $sql = "SELECT
                    *
                FROM
                    ds_userinstance AS ui
                    INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
                    INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
                WHERE
                    ui.id_userinstance = ".$UIID;   
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) > 0);
        
        return $res[0];
    }
    
    static public function GetStartUserData(DS_Common $common, $worldID, $userName)
    {
        assert(is_int($worldID) && $worldID > 0);
        assert(!empty($userName));
        $sql = "select 
                    u.ID_Language,
                    l.shortname,
                    u.id_user,
                    u.time_delta,
                    ui.ID_World,
                    ra.ID_Race,
                    co.ID_Comb,
                    co.CombNumber,
                    co.id_combcomplex,
                    ri.id_ring,
                    ui.id_userinstance
                from 
                    DS_UserInstance ui,
                    DS_User u,
                    DS_Race ra,
                    DS_Comb co,
                    ds_language l,
                    ds_ring ri,
                    ds_combcomplex cc
                where 
                    ui.ID_User = u.ID_User
                    and 
                    ui.ID_Race = ra.ID_Race
                    and 
                    co.ID_UserInstance = ui.ID_UserInstance
                    and 
                    u.id_language = l.id_language
                    and 
                    co.id_combcomplex = cc.id_combcomplex
                    and 
                    cc.id_ring = ri.id_ring
                    and 
                    ui.id_world = ".$worldID."
                    and 
                    u.username = '".$userName."'
                order by ri.ringnumber limit 1";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg) && count($res) > 0);
        
        return $res[0];
    }
    
    /**
     * Выборка следующей жертвы для решреша
     *      выбирается обладатель самого старого евента
     *      определяется до какого времени его отрефрешить
     *
     * @param DE_WorldFromCollection $world
     * @return array (USER_INSTANCE_ID => int, CHECK_TIME => GB_Date)
     */
    static public function GetUserInstanceIDForRefresh(DE_WorldFromCollection $world)
    {
        $common = DS_Common::CreateForCurentDB();
        //$common->setDbId($common->getDBIDByName($world->getDBName()));
        
        // выбираем ИД инстанса
        $sql = "
            SELECT
                ep.id_userinstance
            FROM
                ds_eventqueue AS eq
                INNER JOIN ds_eventparticipant AS ep USING (id_eventqueue)
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
                LEFT JOIN ds_user_refresh_black_list AS urbl USING (id_userinstance)
                LEFT JOIN ds_user_refresh_process_list AS urpl ON (urpl.id_userinstance = ep.id_userinstance AND NOT urpl.is_complite)
            WHERE
                urbl.id_blacklist IS NULL
                AND
                urpl.id_refresh IS NULL
                AND
                ui.id_world = ".$world->getWorldID()."
            ORDER BY eq.eventendtime DESC
            LIMIT 1
            ";
        $res = $common->getResultArray( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));
        
        // нету евентов
        if(count($res) == 0)
        {
            return array('USER_INSTANCE_ID' => 0, 'CHECK_TIME' => GB_TypeFactory::CreateBase('GB_Date', time()));
        }
        
        $userInstanceID = (int)$res[0]['id_userinstance'];

        // смотрим время
        $sql = "SELECT ds_get_end_date_for_partial_refresh($userInstanceID) AS check_time";
        $res = $common->getResultArray( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg) && count($res) == 1);
        
        $checkTime = $res[0]['check_time'];
        
        return array('USER_INSTANCE_ID' => $userInstanceID, 'CHECK_TIME' => GB_TypeFactory::CreateBase('GB_Date', $checkTime));
    }
    
    /**
     * Занесение в базу информации о новом потоке
     *
     * @param DE_WorldFromCollection $world
     * @param int $pid
     * @param int $userInstanceID
     */
    static public function InsertThreadInfo($threadID, DE_WorldFromCollection $world, $pid, $userInstanceID)
    {
        assert(is_int($pid) && $pid > 0);
        assert(is_int($userInstanceID));
        
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "
            ISNERT INTO ds_user_refresh_process_list
                (id_refresh, pid, exec_server_name, id_userinstance, id_world, start_time, expire_time, is_complite)
            VALUES
                ($threadID, $pid, '".SU_DebugTools::GetServerName()."', $userInstanceID, ".$world->getWorldID().", now(), now() + '".$world->getWorldSettings()->get('RD_MAX_REFRESH_TIME')." SECOND'::interval, false)
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    /**
     * Кол-во активных потоков
     *
     * @param DE_WorldFromCollection $world
     */
    static public function GetActiveThreadCount(DE_WorldFromCollection $world)
    {
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_user_refresh_process_list
            WHERE
                NOT is_complite
                AND
                expire_time > NOW()
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return (int)$res[0]['count'];
    }
    
    
    /**
     * Кол-во трензацкий в базе
     *
     * @return int
     */
    static public function GetDBTransactionsCount()
    {
        $common = DS_Common::CreateForCurentDB();
        
        $dbName = $GLOBALS['dbWorldAccess'][$common->dbId]['dbname'];
        assert(!empty($dbName));
        
        $sql = "SELECT 
                    count(procpid) as activity
                FROM 
                    pg_stat_activity
                WHERE 
                    datname = '".$dbName."'";   
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return (int)$res[0]['activity'];
    }
    
    /**
     * Отмечаем окончание рефреша
     *
     * @param int $threadID
     * @param string $result
     */
    static public function MarkThreadAsComplite($threadID, $result)
    {
        assert(is_int($threadID) && $threadID > 0);
        
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "
            UDPATE 
                ds_user_refresh_process_list
            SET
                is_complite = true,
                result = E'".pg_escape_string($result)."'
            WHERE
                id_refresh = $threadID
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    /**
     * Добавление нейдачников в черный список
     *
     * @param int $UIID
     */
    static public function AddInstanceToBlackList($UIID)
    {
        assert(is_int($UIID));
        
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "
            INSERT INTO ds_user_refresh_black_list
                (id_userinstance, add_date, comment)
            VALUES
                ($UIID, now(), 'REFRESH_DAEMON')
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    /**
     * Айдишник для следующего треда
     *
     * @return int
     */
    static public function GetNewThreadID()
    {
        $common = DS_Common::CreateForCurentDB();
        return (int)DS_Base::getNextSeqVal($common, 'ds_user_refresh_process_list_id_refresh_seq');
    }
    
    
    /**
     * Список неоконченных потоков
     *
     */
    static public function GetNotEndedThreads(DE_WorldFromCollection $world)
    {
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "
            SELECT
                pid,
                exec_server_name
            FROM
                ds_user_refresh_process_list
            WHERE
                NOT is_complite
                AND
                expire_time < now()
                AND
                id_world = ".$world->getWorldID()."
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
}
?>