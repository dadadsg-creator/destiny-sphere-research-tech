<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2009/03/17 13:39:05 $';

/**
 * Запросы связанные с сотовым комплексом
 * (сейчас используется только для просмотра комплексов)
 */
class DS_CombComplex extends DS_Base
{
    /**
     * Номер комплекса по его ИД
     *
     * @param int $complexID
     * @return int
     */
    static public function GetComplexNumberByID($complexID)
    {
        assert(is_int($complexID) && $complexID > 0);
        $common = self::getCommon();
        $sql = "SELECT complexnumber FROM ds_combcomplex WHERE id_combcomplex = $complexID";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        return (int)$res[0]['complexnumber'];
    }
    
    /**
     * ИД кольца по его названию и ИД мира
     *
     * @param string $ringName
     * @param itn $worldID
     * @return int
     */
    static public function GetRingIDByRingNameAndWorldID($ringName, $worldID)
    {
        assert(!empty($ringName));
        assert(is_int($worldID) && $worldID > 0);
        $common = self::getCommon();
        $sql = "
            SELECT 
                ri.id_ring
            FROM 
                ds_ring AS ri,
                ds_world AS wo
            WHERE 
                ri.id_world = wo.id_world
                AND
                wo.id_world = $worldID
                AND
                ri.ringname = '$ringName'";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        return (int)$res[0]['id_ring'];
    }
    
    /**
     * ИД комплекса по его номеру и ИД кольца
     *
     * @param int $ringID
     * @param int $complexNumber
     * @return int (0 - нету комплекса с таким номером)
     */
    static public function GetComplexIDByRingIDAndComplexNumber($ringID, $complexNumber)
    {
        assert(is_int($ringID) && $ringID > 0);
        assert(is_int($complexNumber) && $complexNumber > 0);
        $common = self::getCommon();
        $sql = "SELECT id_combcomplex FROM ds_combcomplex WHERE complexnumber = $complexNumber AND id_ring = $ringID";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            return (int)$res[0]['id_combcomplex'];
        }
        
        return 0;
    }
    
    /**
     * ИД комплекса с минимальным номером на кольце
     *
     * @param int $ringID
     * @return int
     */
    static public function GetComplexIdWithMinNumberOnRing($ringID)
    {
        assert(is_int($ringID) && $ringID > 0);
        $common = self::getCommon();
        $sql = "
            SELECT
                id_combcomplex,
                complexnumber
            FROM
                ds_combcomplex
            WHERE
                id_ring = $ringID
                AND 
                complexnumber = (
                    SELECT min(complexnumber) FROM ds_combcomplex WHERE id_ring = $ringID                   
                )
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res[0];
    }
    
    /**
     * Название и локализованное название кольца по его ИД
     *
     * @param int $ringID
     * @return array
     */
    static public function GetRingInfo($ringID)
    {
        assert(is_int($ringID) && $ringID > 0);
        $common = self::getCommon();
        $sql = "
            SELECT
                r.ringnumber,
                ltl.text
            FROM
                ds_ring AS r
                INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = r.id_localizedname)
            WHERE
                id_ring = $ringID
                AND 
                ltl.id_language = ".$_SESSION['lang']."
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res[0];
    }
    
    /**
     * Данные по сотам комплекса
     *
     * @param int $complexID
     * @return array
     */
    static public function GetComplexCombs($complexID)
    {
        assert(is_int($complexID) && $complexID > 0);
        
        $common = self::getCommon();
        $sql = "
            SELECT
                co.id_comb,
                co.id_combcomplex,
                co.id_userinstance,
                co.combnumber,
                cc.complexnumber,
                (
                    SELECT  combname
                    FROM ds_combname
                    WHERE id_comb = co.id_comb
                ) AS combname,
                (
                    SELECT fr.thefinal
                    FROM ds_finalranking fr
                    WHERE fr.id_userinstance = co.id_userinstance
                    limit 1
                ) AS combscore
            FROM
                ds_combcomplex cc,
                ds_comb co
            WHERE
                co.id_combcomplex = cc.id_combcomplex
                AND cc.id_combcomplex = $complexID
            ORDER BY co.combnumber";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) > 0);
        
        return $res;
    }
    
    /**
     * Список всех колец по ИД мира
     *
     * @param int $worldID
     * @return array
     */
    static public function GetAllRings($worldID)
    {
        assert(is_int($worldID) && $worldID > 0);
        
        $common = self::getCommon();
        $sql = "
            SELECT 
                r.ringname,
                ltl.text as localizedname
            FROM 
                ds_ring r,
                ds_localizedtextlanguage ltl
            WHERE
                r.id_localizedname = ltl.id_localizedtext
                AND 
                ltl.id_language = ".$_SESSION['lang']."
                AND 
                r.id_world = $worldID
           ORDER BY ringnumber 
            ";
        $res = $common->getResultArray("world_rings_".$worldID, $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        return $res;
    }
}

?>