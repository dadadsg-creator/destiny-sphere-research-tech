<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Запросы связанные с боями на зонтах
 *
 */
class DS_SpyBattle extends DS_Base 
{
    static public function GetLogDataByID($logID)
    {
        assert(is_int($logID) && $logID > 0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                *
            FROM
                ds_spy_log
            WHERE
                id_spy_log = ".$logID."
            ";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $logID), $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        assert(count($res) == 1);
        
        return $res[0];
    }
    
    static public function GetUserLogs(DE_UserInstance $userInstance, $actions = array(), $combs = array(), $userID = null, $startDate = null, $endDate = null)
    {
        $common = self::getCommon();
        
        assert(is_null($userID) || (is_int($userID) && $userID > 0));
        
        $where = ' AND sl.id_owner = '.$userInstance->getID();      
        
        if(count($actions) > 0)
        {
            $where .= ' AND sa.action_name IN (\''.implode('\',\'', $actions).'\')';
        }
        
        if(!is_null($userID))
        {
            $where .= ' AND (sl.id_user_from = '.$userID.' OR sl.id_user_to = '.$userID.')';
        }
        
        if($startDate instanceof GB_Date)
        {
            $where .= ' AND sl.log_date > \''.$startDate->getPGFormat()."'";
        }
        
        if($endDate instanceof GB_Date)
        {
            $where .= ' AND sl.log_date < \''.$endDate->getPGFormat()."'";
        }
        
        if(count($combs) > 0)
        {
            $combsWhere = implode(',', $combs);
        } else {
            $combsWhere = "SELECT id_comb FROM ds_comb WHERE id_userinstance = ".$userInstance->getID();
        }
        
        
        $sql = "
            SELECT
                sl.id_spy_log
            FROM
                ds_spy_log AS sl
                INNER JOIN ds_spy_action_type AS sa USING (id_spy_action)
            WHERE
                (
                    sl.id_comb_from IN ( $combsWhere )
                    OR
                    sl.id_comb_to IN ( $combsWhere  )
                )
                $where
            ORDER BY sl.log_date DESC
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
//SU_DebugTools::LogAll($sql, __CLASS__);
        
        return $res;
    }
    
    
    static public function GetNextLogID()
    {
        return (int)self::getNextSeqVal(self::getCommon(), 'ds_spy_log_id_spy_log_seq');
    }
    
    static public function InsertLog(DE_SpyLog $log)
    {
        $common = self::getCommon();
        
        if($log->getCombTo() instanceof DE_Comb)
        {
            $combTo = $log->getCombTo()->getID();
        } else {
            $combTo = 'null';
        }
        
        if($log->getCombFrom() instanceof DE_Comb)
        {
            $combFrom = $log->getCombFrom()->getID();
        } else {
            $combFrom = 'null';
        }
        
        if($log->getUserFrom() instanceof DE_User)
        {
            $userFrom = $log->getUserFrom()->getID();
        } else {
            $userFrom = 'null';
        }
        
        if($log->getUserTo() instanceof DE_User)
        {
            $userTo = $log->getUserTo()->getID();
        } else {
            $userTo = 'null';
        }
        
        if($log->getUnit() instanceof DE_Unit)
        {
            $unit = $log->getUnit()->getID();
        } else {
            $unit = 'null';
        }

        $sql = "
            INSERT INTO ds_spy_log
                (id_spy_log, log_date, id_comb_from, id_comb_to, log_text, id_spy_action, id_user_from, id_user_to, id_owner, accuracy, id_unit)
            VALUES
                (
                    ".$log->getID().",
                    '".$log->getDate()->getPgFormat()."',
                    $combFrom,
                    $combTo,
                    '".pg_escape_string($log->getLogText())."',
                    ".$log->getAction()->getID().",
                    $userFrom,
                    $userTo,
                    ".$log->getOwner()->getID().",
                    ".$log->getAccuracy().",
                    ".$unit."
                )
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
}

?>