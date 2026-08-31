<?php $CVS_Version = '$Revision$ ; $Date$';


class DS_Religion extends DS_Base
{

    public static function getByID($id, $withCache = true)
    {
        settype ( $id, 'integer' );
                
        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "";
        }
        
        $sql = "SELECT *
                FROM ds_religion
                WHERE id_religion = ".$id;
        
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $id), $sql, "", "SHORT_PREFIX", $cacheType);
        assert(empty($common->errormsg));

        if(!$res){
            return false;
        }else{
            return $res[0];
        }
    }

    public static function GetReligionAtrributes($id, $withCache = true)
    {
        settype ( $id, 'integer' );
        assert ( $id > 0 );

        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "";
        }
        
        $sql = "
                SELECT 
                    rha.value,
                    attt.typename,
                    attn.attributename
                FROM
                    ds_religionhasattribute rha
                    INNER JOIN ds_attribute a ON (rha.id_attribute = a.id_attribute)
                    INNER JOIN ds_attributetype attt ON (a.id_attributetype = attt.id_attributetype)
                    INNER JOIN ds_attributename attn ON (a.id_attributename = attn.id_attributename)
                WHERE
                    rha.id_religion = ".$id;
        
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $id), $sql, "", "SHORT_PREFIX", $cacheType);
        assert(empty($common->errormsg));
        
        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }       
    }//END OF METHOD
    
    public static function GetReligionStatusByUserInstance($id)
    {
        settype ( $id, 'integer' );

        $common = self::getCommon();

        $sql = "SELECT 
                    uhr.id_religion,
                    uhr.id_status,
                    rs.statusname
                FROM
                    ds_userhasreligion uhr
                    INNER JOIN ds_religionstatus rs ON (uhr.id_status = rs.id_status)
                WHERE
                    uhr.id_userinstance = ".$id;

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        if(sizeof($res)>0)
        {
            return $res[0];
        }else{
            return false;
        }       
        
    }

}//END OF CLASS

?>