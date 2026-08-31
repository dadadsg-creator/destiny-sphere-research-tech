<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2008/02/14 07:54:49 $';

/**
 * Статический класс-библиотека запросов связанная с премиумом.
 */

class DS_Premium extends DS_Base
{
    /**
     * Изменяем врямя окончания премиума
     *
     * @param integer $uid
     * @param integer $daysDelta
     */
    static public function changePremiumEndDate($uid, $daysDelta)
    {       
        settype ($uid, 'integer');
        assert ($uid > 0);
        settype ($daysDelta, 'integer');
        assert ($daysDelta != 0);
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE 
                ds_userprofidata 
            SET                         
                enddate = enddate + '".$daysDelta." days'::interval 
            WHERE 
                id_user = ".$uid;
        
        $res = $common->executeQuery ($sql);                
        if(!empty($common->errormsg))
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Устанавливаем статус игрока в профи
     *
     * @param integer $uid
     */
    static public function changeUserStatusToProfi($uid)
    {
        settype ($uid, 'integer');
        assert ($uid > 0);
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE 
                ds_userprofidata 
            SET                         
                active = true, 
                profi = true, 
                trial = false
            WHERE 
                id_user = ".$uid;
        
        $res = $common->executeQuery ($sql);                
        if(!empty($common->errormsg))
        {
            return false;
        }
            
        return true;
    }
    
    /**
     * Проверить профи или нет игрок
     *
     * @param integer $uid
     * @return bool
     */
    static public function checkIfUserProfi($uid)
    {
        settype ($uid, 'integer');
        assert ($uid > 0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                count(*)
            FROM
                ds_userprofidata upd
            WHERE
                upd.active = TRUE
                AND upd.enddate > now()
                AND upd.profi = TRUE
                AND upd.id_user = ".$uid;
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );        
        assert(empty($common->errormsg));       

        if($res[0]['count'] == 0)
        {
            return false;
        }
        
        return true;
    }
    
    static public function checkIfUserHavePremiumRegistration($uid)
    {
        settype ($uid, 'integer');
        assert ($uid > 0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                count(*)
            FROM
                ds_userprofidata upd
            WHERE
                upd.id_user = ".$uid;
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );        
        assert(empty($common->errormsg));       

        if($res[0]['count'] == 0) 
        {
            return false;
        }
        
        return true;
    }
    
    static public function insertFakePremiumRegistration($uid)
    {
        settype ($uid, 'integer');
        assert ($uid > 0);
        
        $common = self::getCommon();
        
        $sql = "
            INSERT INTO ds_userprofidata
                (id_user, profi, trial, creationdate, startdate, enddate, active)
            VALUES
                (".$uid.", 'f', 'f', now(), now(), now(), 't')";
        
        $res = $common->executeQuery ($sql);                
        if(!empty($common->errormsg))
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Устанавливает дату начала и окончания премиум-аккаунта в now
     * (для тех, у кого закончились премиум-дни)
     *
     * @param integer $uid
     * @return bool
     */
    static public function setStartEndDateToNow($uid)
    {
        settype ($uid, 'integer');
        assert ($uid > 0);
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE
                ds_userprofidata
            SET
                startdate = now(),
                enddate = now()
            WHERE
                id_user = ".$uid;
            
        $res = $common->executeQuery ($sql);                
        if(!empty($common->errormsg))
        {
            return false;
        }
        
        return true;        
    }
    
    /**
     * Получить дату окончания премиум аккаунта
     *
     * @param integer $uid
     * @return  array
     */
    static public function getUserPremiumDates($uid)
    {
        settype ($uid, 'integer');
        assert ($uid > 0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT 
                to_char (enddate, 'YYYYMMDDHH24MISS') as enddate,
                to_char (startdate, 'YYYYMMDDHH24MISS') as startdate,
                enddate as enddate_pg,
                startdate as startdate_pg
            FROM 
                ds_userprofidata 
            WHERE 
                id_user = ".$uid;
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );        
        assert(empty($common->errormsg));
        return $res;        
    }
    
    
    static public function logPremiumChange($uid, $type, $days_delta, $enddate_old, $enddate_new, $comments)
    {
        settype ($uid, 'integer');
        assert ($uid > 0);
        settype ($days_delta, 'integer');
        assert(strlen($type) > 0);
        
        $common = self::getCommon();
        
        $sql = "
            INSERT INTO ds_premium_change_log
                (
                    id_user,
                    type,
                    date,
                    days_delta,
                    end_date_old,
                    end_date_new,
                    comments
                )
            VALUES
                (
                    $uid,
                    '$type',
                    now(),
                    $days_delta,
                    '$enddate_old',
                    '$enddate_new', 
                    '".pg_escape_string($comments)."'
                )
            ";
                            
        $res = $common->executeQuery($sql);             
        assert(empty($common->errormsg));
        
        // логирование получения триального премиума
        if($type == 'BILLING' && $comments == 'trial_days')
        {
            $sql = "
                INSERT INTO ds_present
                    (id_user, days, type)
                VALUES
                    ($uid, $days_delta, 1)
                ";
            $res = $common->executeQuery($sql);             
            assert(empty($common->errormsg));
        }
    }
    
    static public function CheckPremiumPresent($uid)
    {
        settype ($uid, 'integer');
        assert ($uid > 0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                count(*)
            FROM
                ds_present
            WHERE
                id_user = $uid
                AND
                type = 1
            ";
        $res = $common->getResultArray ( "", $sql, "", "", "" );        
        assert(empty($common->errormsg));
        return (int)$res[0]['count'];
    }
    
    /**
     * Метод логирования включений премиума
     *
     * @param integer $uid
     * @param string $since
     * @param integer $duration
     * @param string $prolonged
     */
    static public function logAddingPremium($uid, $since, $duration, $prolonged, $paymentcode)
    {
        settype ($uid, 'integer');
        assert ($uid > 0);
        settype ($duration, 'integer');
        settype ($since, 'string');
        assert (strlen($since) > 0);
        settype ($prolonged, 'string');
        assert (strlen($prolonged) > 0);
        settype ($paymentcode, 'string');
        assert (strlen($paymentcode) > 0);
        
        $common = self::getCommon();
        
        $sql = "
            INSERT INTO ds_premiumonlog
                (id_user, since, duration,
                 is_prolonged, by_whom, changetime)
            VALUES
                (".$uid.",".$since.",".$duration.
                ",".$prolonged.", 'new billing', now())";
                            
        $res = $common->executeQuery($sql);             
        assert(empty($common->errormsg));
    }
    
    static public function GetAllUserWithPremium($worlds)
    {
        assert(is_array($worlds));
        
        $where = '';
        $or = '';
        
        foreach ($worlds as $key => $world)
        {
            $tmp = explode('_', $world);
            $dbName = $tmp[0];
            $world_id = (int)$tmp[1];
            assert(!empty($dbName));
            assert($world_id > 0);
            $where .= $or."
                (   
                uic.dbname = '".$dbName."'
                 AND
                uic.id_world = ".$world_id."
                )
            ";
            
            $or = ' OR ';
        }
            
        $common = self::getCommon();
        
        $sql = "
            SELECT 
                distinct(upd.id_user) 
            FROM
                ds_userprofidata upd
            LEFT JOIN ds_userinstancecollection uic ON (upd.id_user = uic.id_user)          
            WHERE
                upd.enddate > now()
            AND
            ($where)
            ORDER BY upd.id_user
            ";
        $res = $common->getResultArray ( "", $sql, "", "", "" );        
        assert(empty($common->errormsg));
        
        return $res;
    }
}
?>