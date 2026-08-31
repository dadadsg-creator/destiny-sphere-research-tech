<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Статический Класс контейнер для SQL зпросов свзяанных с кольцаи.
 */
class DS_Ring  extends DS_Base
{
        
    /**
    * @return 
    * @param 
    * @desc Возврящает ID мира из сесии
    */
    static public function GetWorldID()
    {       
        
        assert(array_key_exists('world', $_SESSION));
        settype ( $_SESSION['world'], 'integer' );
        assert ( $_SESSION['world'] > 0 );
        return $_SESSION['world'];
                        
    }//END OF METHOD
    
    
    /**
    * @return Массив срезультатом выборки или false
    * @param $id ШВ кольца
    * @ Выирает сведения кольце по ID
    */
    static public function GetByID($id, $withCache = true){
        
        settype ( $id, 'integer' );
        assert ( $id > 0 );
        
        $common = self::getCommon();
        
        if($withCache)
        {
            $caheType = "GLOBAL_CACHE";
        }else{
            $caheType = "";
        }
        
        $sql = "SELECT
                    r.id_ring,
                    r.ringnumber,
                    r.ringname,
                    (SELECT  max (cc.complexnumber) as max_count
                    FROM ds_combcomplex as cc
                    WHERE cc.id_ring = r.id_ring
                    ) as complexcount
                FROM ds_ring as r
                WHERE
                    r.id_ring = ".$id;
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $id), $sql, "", "", $caheType);
        assert(empty($comon->errormsg));
        
        if ( !$res ) {          
            return false;
        }else{
            return $res[0];
        }
    }//END OF METHOD
    
    /**
    * @return ID кольца
    * @param unknown $number
    * @desc Извекает ID по номеру соты
    */
    static public function GetIDByNumber($number)
    {
        
        settype ( $number, 'integer' );
        assert ( $number > 0 );
        
        $common = self::getCommon();
        $worldId = DS_Ring::getWorldId();
        
        
        $sql = "SELECT
                    r.id_ring                   
                FROM ds_ring as r
                WHERE
                    r.id_world = ".$worldId." and
                    r.ringnumber = ".$number;
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $number."_".$worldId), $sql, "", "", "GLOBAL_CACHE" );
        assert(empty($common->errormsg));
        
        if ( !$res ) {
            return (int)0;
        }else{
            return (int)$res[0]['id_ring'];
        }
    }//END OF METHOD
    
    /**
     * Кол-во игроков на кольце
     *
     * @param int $worldID
     * @param int $ringnumber
     * @return int
     */
    static public function GetCountUsersOnRing($worldID, $ringnumber)
    {
        assert(is_int($ringnumber) && $ringnumber > 0);
        assert(is_int($worldID) && $worldID > 0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                count(*)
            FROM
                ds_combcomplex AS cc
                INNER JOIN ds_comb AS c USING (id_combcomplex)
                INNER JOIN ds_ring AS r USING (id_ring)
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
                LEFT JOIN ds_npc_user AS nu USING (id_user)
            WHERE
                NOT c.id_userinstance IS NULL
                AND
                r.id_world = ".$worldID."
                AND
                r.ringnumber = ".$ringnumber."
                AND
                nu.id_user IS NULL
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($comon->errormsg));
        
        return (int)$res[0]['count'];
    }//END OF METHOD
    
    /**
     * Enter description here...
     *
     * @param unknown_type $worldID
     * @param unknown_type $ringnumber
     * @param unknown_type $countPerSector
     * @param unknown_type $offset
     */
    static public function GetComplexCountWithUsers($worldID, $ringnumber, $countPerSector, $offset)
    {
        assert(is_int($ringnumber) && $ringnumber > 0);
        assert(is_int($worldID) && $worldID > 0);
        assert(is_int($countPerSector) && $countPerSector > 0);
        assert(is_int($offset) && $offset >= 0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                cc.complexnumber
            FROM
                ds_combcomplex AS cc
                INNER JOIN ds_ring AS r USING (id_ring)
            WHERE
                r.ringnumber = $ringnumber
                AND
                r.id_world = $worldID
                AND
                (
                    SELECT
                        count(*)
                    FROM
                        ds_comb AS c
                        INNER JOIN ds_combcomplex AS cc2 USING (id_combcomplex)
                        INNER JOIN ds_userinstance AS ui USING (id_userinstance)
                        LEFT JOIN ds_npc_user AS nu USING (id_user)
                    WHERE
                        cc2.complexnumber <= cc.complexnumber
                        AND
                        cc2.complexnumber > $offset
                        AND
                        NOT c.id_userinstance IS NULL
                        AND
                        cc2.id_ring = r.id_ring
                        AND
                        nu.id_user IS NULL
                ) >= $countPerSector
                AND
                cc.complexnumber > $offset
            ORDER BY cc.complexnumber
            LIMIT 1
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($comon->errormsg));
        
        return (int)$res[0]['complexnumber'];
    }//END OF METHOD
}//END OF CLASS


?>