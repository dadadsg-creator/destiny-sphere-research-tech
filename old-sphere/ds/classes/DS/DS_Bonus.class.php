<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Библиотека запросов к БД свзанных с бонусами игрока.
 */
class DS_Bonus extends DS_Base
{
    /**
     * Добавляет игроку бонус
     *
     * @param integer $id - ID армии участника битвы
     * @return array
     */
    static function IsertUserBonus($userInstanse, $days, $resources)
    {
        assert(is_int($userInstanse));
        assert(is_int($days) && $days > 0);
        assert(is_array($resources));
        
        $common = self::getCommon();
/*echo "<pre>";
print_r($resources);
exit;*/     
        foreach ($resources as $resNAme => $persent)
        {
            // Получаем айдишник атрибута
            $sql = "
                SELECT
                    a.id_attribute
                FROM
                    ds_attribute a
                INNER JOIN ds_attributename AS an ON (a.id_attributename = an.id_attributename)
                INNER JOIN ds_attributetype AS at ON (a.id_attributetype = at.id_attributetype)
                WHERE
                    an.attributename = '".$resNAme."'
                     AND 
                    at.typename = 'BONUS_RELATIVE'"; 
            $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
            assert(empty($common->errormsg));   
            assert(count($res) == 1);   
            $idAttribute =  (int)$res[0]['id_attribute'];   
            
            assert($idAttribute > 0);                               
            
            // Смотрим, есть ли уигрока запись об этом бонусе
            $sql = "
                SELECT
                           *
                FROM
                    ds_userhasbonus uhb
                WHERE
                    uhb.id_userinstance = ".$userInstanse."
                     AND 
                    uhb.id_attribute = ".$idAttribute;      
            $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
            assert(empty($common->errormsg));

            // Есди записи об этом бонусе присутствуют
            if (count($res) > 0) {
                $sql = "
                UPDATE
                    ds_userhasbonus
                SET 
                    start_date = now(),
                    end_date = greatest(now(), end_date) + '".$days." days'::interval ,
                    value = ".($persent/100)."
                WHERE
                    id_userinstance = ".$userInstanse."
                     AND 
                    id_attribute = ".$idAttribute;  
                    //echo "<pre>$sql<br>";
                $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
                assert(empty($common->errormsg));
            } else {
                // Вставляем в табличку новую запись
                $sql = "
                INSERT INTO
                    ds_userhasbonus
                (id_userinstance, id_attribute,start_date, end_date, value)
                VALUES
                (".$userInstanse.", ".$idAttribute.", now(), now() + '".$days." days'::interval, ".($persent/100).")";      
                $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
                assert(empty($common->errormsg));

            }
        }
    }
    
    /**
     * Проверяет, есть ли угрока действующие бонусы
     *
     * @param integer $userInstanseId - ID userinstance
     * @return array
     */
    static function IsUserHasBonuser($userInstanseId, $resource = '')
    {
        assert(is_int($userInstanseId));
        $common = self::getCommon();
        
        $where = '';
        if (!empty($resource)) {
            $where = "
            AND an.attributename = '".$resource."'
            AND at.typename = 'BONUS_RELATIVE'
            ";
        }
        
        $sql = "
                SELECT
                           *
                FROM
                    ds_userhasbonus uhb
                INNER JOIN ds_attribute AS a ON (uhb.id_attribute = a.id_attribute)
                INNER JOIN ds_attributename AS an ON (a.id_attributename = an.id_attributename)
                INNER JOIN ds_attributetype AS at ON (a.id_attributetype = at.id_attributetype)
                WHERE
                    uhb.id_userinstance = ".$userInstanseId."
                     AND 
                    uhb.start_date < now()
                     AND 
                    uhb.end_date > now()
                    ".$where;       
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));

        if (count($res) > 0) {
            return true;
        }
        return  false;
    }
    
    /**
     * Смотрим время окончиния бонуса
     *
     * @param integer $userInstanseId - ID userinstance
     * @return array
     */
    static function GetUserBonusEndDate($userInstanseId, $resourse)
    {
        assert(is_int($userInstanseId));
        assert(!empty($resourse));
        
        $common = self::getCommon();
        
        $sql = "
                SELECT
                uhb.end_date
            FROM
                ds_userhasbonus AS uhb
                INNER JOIN ds_attribute AS a ON (uhb.id_attribute = a.id_attribute)
                INNER JOIN ds_attributename AS an ON (a.id_attributename = an.id_attributename)
                INNER JOIN ds_attributetype AS at ON (a.id_attributetype = at.id_attributetype)
            WHERE
                uhb.id_userinstance = ".$userInstanseId."
                AND an.attributename = '".$resourse."'
                AND at.typename = 'BONUS_RELATIVE'";        
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));
        assert(count($res) == 1);
        
        return  GB_TypeFactory::CreateConstant('GB_Date', $res[0]['end_date']);
    }
}

?>