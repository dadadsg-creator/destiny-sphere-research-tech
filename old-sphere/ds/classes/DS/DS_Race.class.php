<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Race extends DS_Base
{

    public static function getByID($id, $withCache = true)
    {
        settype ( $id, 'integer' );
        assert ( $id > 0 );
        
        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "GLOBAL_CACHE";
        }
        
        $sql = "SELECT *
                FROM ds_race
                WHERE id_race = ".$id;
        
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $id), $sql, "", "SHORT_PREFIX", $cacheType);
        assert(empty($common->errormsg));

        if(!$res){
            return false;
        }else{
            return $res[0];
        }
    }

    public static function GetRaceAtrributes($raceID, $withCache = true)
    {
        settype ( $raceID, 'integer' );
        assert ( $raceID > 0 );

        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "GLOBAL_CACHE";
        }
        
        $sql = "
                SELECT 
                    rhattr.value,
                    attt.typename,
                    attn.attributename
                FROM
                    ds_racehasattribute AS rhattr
                    INNER JOIN ds_race AS r ON (rhattr.id_race = r.id_race)
                    INNER JOIN ds_attribute AS att ON (rhattr.id_attribute = att.id_attribute)
                    INNER JOIN ds_attributename AS attn ON (att.id_attributename = attn.id_attributename)
                    INNER JOIN ds_attributetype AS attt ON (att.id_attributetype = attt.id_attributetype)
                WHERE
                    r.id_race = ".$raceID;
        
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $raceID), $sql, "", "SHORT_PREFIX", $cacheType );
        assert(empty($common->errormsg));
        
        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }       

    }

}

?>