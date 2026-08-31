<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Сттаитческий клас с библиотекой запросов по классу юнита.
 */
class DS_Unit extends DS_Base
{
    
    static public function GetIDByUnitName($name)
    {       
        assert (!empty($name));
        
        $common = self::getCommon();
        
        $sql = "SELECT id_unit
                FROM ds_unit
                WHERE unitname = '".$name."'";
        
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $name), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
            
        return (int)$res[0]['id_unit'];     
    }
    
    static public function GetByID($id, $withCache = true)
    {
        settype ( $id, 'integer' );
        assert ( $id > 0 );
        
        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "";
        }
        
        $common = self::getCommon();
        
        $sql = "SELECT 
                    u.*,
                    uc.classnumber
                FROM 
                    ds_unit AS u
                    LEFT JOIN ds_unithasclass AS uhc ON (uhc.id_unit = u.id_unit)
                    LEFT JOIN ds_unitclass AS uc ON (uc.id_unitclass = uhc.id_unitclass)
                WHERE u.id_unit = ".$id;
        
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $id),$sql, "", "SHORT_PREFIX", $cacheType);
        assert(empty($common->errormsg));
                
        if(!$res){
            return false;
        }else{
            return $res[0];
        }
    }

    static public function GetUnitVSUnitBonusForUnit($unitFromID, $withCache = true)
    {
        settype($unitFromID, 'integer');
        assert($unitFromID > 0);        
        
        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "";
        }
        
        $sql = "SELECT *
                FROM ds_unitvsunit
                WHERE id_unitfrom = ".$unitFromID;
        $res = $common->getResultArray(self::FormMark(__METHOD__, $unitFromID), $sql, "", "SHORT_PREFIX", $cacheType);
        assert(empty($common->errormsg));

        return $res;
    }//END OF METHOD
        
    static public function GetUnitAtrributes ($unitID, $withCache = true)
    {
        settype ( $unitID, 'integer' );
        assert ( $unitID > 0 );

        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "";
        }
        
        $sql = "
                SELECT
                    u.id_unit,
                    atn.attributename,
                    att.typename,
                    uha.value,
                    res.exchangerate
                FROM
                    ds_unit u                   
                    INNER JOIN ds_unithasattribute uha ON (u.id_unit = uha.id_unit)
                    INNER JOIN ds_attribute atr ON (uha.id_attribute = atr.id_attribute)
                    INNER JOIN ds_attributename atn ON (atr.id_attributename = atn.id_attributename)
                    INNER JOIN ds_attributetype att ON (atr.id_attributetype = att.id_attributetype)
                    LEFT JOIN ds_resource res ON (atn.id_attributename = res.id_attributename)                  
                WHERE
                    u.id_unit = ".$unitID;

        $res = $common->getResultArray(self::FormMark(__METHOD__, $unitID), $sql, "", "SHORT_PREFIX", $cacheType);
        assert(empty($common->errormsg));

        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }       
    }
    
    /**
     * Выборка ИД всех нормальных юнитов (не квестовых)
     * используется при генерации шпионских логов, в добавление подставных юнитов, а так же в армейском калькуляторе(в военной базе)
     *
     * @return unknown
     */
    static public function GetAllUnitsID()
    {
        $common = self::getCommon();
        $sql = "
            SELECT 
                u.id_unit 
            FROM 
                ds_unit u
            LEFT JOIN ds_unithasclass uc ON (uc.id_unit = u.id_unit)
            LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = u.id_localizedname AND l.id_language = 3)
            LEFT JOIN ds_unitcanbebuildbyrace ur ON (ur.id_unit = u.id_unit)                        
            WHERE 
                EXISTS (
                    SELECT 
                        id_race 
                    FROM 
                        ds_unitcanbebuildbyrace 
                    WHERE 
                        id_unit = u.id_unit 
                        AND 
                        id_race < 4
                ) 
                AND 
                id_unitcontainertype = 1
            ORDER BY ur.id_race, uc.id_unitclass, l.text                    
            ";
        $res = $common->getResultArray(self::FormMark(__METHOD__), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetAllRaceUnitsID($race)
    {
        assert(is_int($race) && $race > 0);
        
        $common = self::getCommon();
        $sql = "
            SELECT 
                DISTINCT id_unit 
            FROM 
                ds_unit 
            WHERE 
                EXISTS (
                    SELECT 
                        id_race 
                    FROM 
                        ds_unitcanbebuildbyrace 
                    WHERE 
                        id_unit = ds_unit.id_unit 
                        AND 
                        id_race = ".$race."
                )
                AND
                id_unitcontainertype = 1";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $race), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        return $res;
    }

    static public function GetAllRaceUnits($race)
    {
        assert(is_int($race) && $race > 0);
        
        $common = self::getCommon();
        $sql = "
            SELECT 
                u.* 
            FROM 
                ds_unit u
            LEFT JOIN ds_unithasclass uc ON (uc.id_unit = u.id_unit)
            LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = u.id_localizedname AND l.id_language = 3)
            
            WHERE 
                EXISTS (
                    SELECT 
                        id_race 
                    FROM 
                        ds_unitcanbebuildbyrace 
                    WHERE 
                        id_unit = u.id_unit 
                        AND 
                        id_race = $race
                )
                AND
                id_unitcontainertype = 1
           ORDER BY uc.id_unitclass, l.text
                    ";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $race), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function IsUnitOnComb($combId)
    {
        assert(is_int($combId) && $combId > 0);
        
        $common = self::getCommon();
        $sql = "
            SELECT 
                * 
            FROM 
                ds_unitcontainer u,
                ds_unitcontainerhasunit uu 
            WHERE 
                id_unit  IN (98, 99 ,93)
                AND u.id_unitcontainer = uu.id_army
                AND u.id_basecomb = $combId
                AND u.inaction = 'f'
                    ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        if (count($res) > 0) {
            return true;
        }
        
        return false;
    }   
}//END OF CLASS

?>