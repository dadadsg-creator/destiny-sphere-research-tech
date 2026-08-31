<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2008/01/31 10:45:11 $';


/**
 * статический Класс контейнер для SQL зпросов свзяанных с сотами.
 */
class DS_Comb extends DS_Base
{

    /**
    * @return Массив срезультатом выборки или false
    * @param $id ID соты
    * @ Выирает сведения о соте по ID
    */
    static public function GetByID ( $id )
    {
        settype ( $id, 'integer' );
        assert ( $id > 0 );

        $common = self::getCommon();

        $sql = "SELECT
                    co.id_userinstance,
                    co.combscore,
                    co.id_comb,
                    cn.combname,
                    co.combnumber,
                    co.id_combcomplex,
                    cc.complexnumber,
                    cc.id_ring,
                    ri.ringname,
                    ri.ringnumber,
                    co.is_capture,
                    co.combscore
                FROM
                    ((ds_comb as co
                        LEFT JOIN ds_combname as cn ON (co.id_comb = cn.id_comb))
                            INNER JOIN ds_combcomplex as cc ON (co.id_combcomplex = cc.id_combcomplex))
                                INNER JOIN ds_ring as ri ON (cc.id_ring = ri.id_ring)
                WHERE
                    co.id_comb = ".$id;
        //Кешировать нальзя!!!
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        }else{

            $sql = "SELECT co.id_userinstance FROM ds_comb as co WHERE co.id_comb = ".$id;
            $res2 = $common->getResultArray("", $sql, "", "", "");
            assert(empty($common->errormsg));

            $res[0]['id_userinstance'] = $res2[0]['id_userinstance'];

            return $res[0];
        }
    }//END OF METHOD


    static public function IsCombEmpty($id)
    {

        settype ( $id, 'integer' );
        assert ( $id > 0 );

        $common = self::getCommon();

        $sql = "SELECT
                    *
                FROM
                    ds_combname
                WHERE
                    id_comb = ".$id;
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return true;
        }else{
            return false;
        }
    }//END OF METHOD

    static public function GetIDByCordinates( $worldID, $ring, $complex, $comb )
    {
        settype ( $worldID, 'integer' );
        assert ( $worldID > 0 );

        settype ( $ring, 'integer' );
        assert ( $ring > 0 );

        settype (  $complex, 'integer' );
        assert (  $complex > 0 );

        settype ( $comb, 'integer' );
        assert ( $comb > 0 );

        $common = self::getCommon();

        $sql = "
            SELECT
                c.id_comb
            FROM
                (ds_comb AS c INNER JOIN ds_combcomplex AS cc ON ( c.id_combcomplex = cc.id_combcomplex )
                    INNER JOIN ds_ring AS r ON ( cc.id_ring = r.id_ring ))
            WHERE
                r.id_world = ".$worldID." AND
                r.ringnumber = ".$ring." AND
                cc.complexnumber = ".$complex." AND
                c.combnumber = ".$comb;

        $res = $common->getResultArray ( self::FormMark(__METHOD__, $worldID."_".$ring.'_'.$complex.'_'.$comb),$sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        if ( !$res ) {
            return 0;
        }else{
            return $res[0]['id_comb'];
        }

    }//END OF METHOD

    static public function GetIDByName($combName)
    {
        $common = self::getCommon();

        if(!VD_CombName::Validate($combName))
        {
            return 0;
        }

        $sql = "
            SELECT
                id_comb
            FROM
                ds_combname
            WHERE
                upper(combname) = upper('".pg_escape_string($combName)."')
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return 0;
        } else {
            return $res[0]['id_comb'];
        }
    }

    static public function HaveOpenCentralGate ($combID)
    {
        settype ( $combID, 'integer' );
        assert ( $combID > 0 );

        $common = DS_Base::getCommon();

        $sql = "
                    SELECT
                        ab.id_attachedbuilding
                    FROM
                        ds_attachedbuilding ab                  
                    WHERE
                        ab.id_comb = ".$combID."                        
                        AND 
                        ab.active = true
                        AND
                        ab.id_building = 6 -- 'CENTRAL_GATE'
                    ";

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if(sizeof($res) > 0)
        {
            return true;
        }else{
            return false;
        }
    }//END OF METHOD

    static public function HaveOpenRingGate ($combID)
    {
        settype ( $combID, 'integer' );
        assert ( $combID > 0 );

        $common = self::getCommon();

        $sql = sprintf ( "
                    SELECT
                        id_attachedbuilding
                    FROM
                        ds_attachedbuilding ab,
                        ds_building b
                    WHERE
                            ab.id_building = b.id_building
                        AND ab.id_comb = %d
                        AND ab.active = true
                        AND b.buildingname = 'RING_GATE'
                    ",$combID );

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if(sizeof($res) > 0)
        {
            return true;
        }else{
            return false;
        }

    }//END OF METHOD

    static public function HaveGandolaTransporter ($combID)
    {
        settype ( $combID, 'integer' );
        assert ( $combID > 0 );

        $common = self::getCommon();

        $sql = sprintf ( "
                    SELECT
                        id_attachedbuilding
                    FROM
                        ds_attachedbuilding ab,
                        ds_building b
                    WHERE
                            ab.id_building = b.id_building
                        AND ab.id_comb = %d
                        AND ab.active = true
                        AND b.buildingname = 'GONDOLA_TRANSPORTER'
                    ",$combID );

        $res = $common->getResultArray ( "ccheck_gondola_id_comb_".$combID, $sql, "", "", "" );
        assert(empty($common->errormsg));

        if ( sizeof ( $res ) > 0 )
        {
            return true;
        }
        return false;
    }//END OF METHOD

    static public function GetCombAtrributes ($combID)
    {
        settype ( $combID, 'integer' );
        assert ( $combID > 0 );

        $common = self::getCommon();
        /*
        $sql = "
        SELECT
        attrname.attributename,
        attrtype.typename,
        chres.value,
        chres.id_combhasresource,
        r.exchangerate,
        chres.id_attribute
        FROM
        ds_combhasresource AS chres
        INNER JOIN ds_attribute AS attr ON (chres.id_attribute = attr.id_attribute)
        INNER JOIN ds_attributetype AS attrtype ON (attr.id_attributetype = attrtype.id_attributetype)
        INNER JOIN ds_attributename AS attrname ON (attr.id_attributename = attrname.id_attributename)
        LEFT JOIN ds_resource AS r ON (r.id_attributename = attrname.id_attributename = )
        WHERE
        chres.id_attribute = attr.id_attribute
        AND
        attrname.id_attributename = attr.id_attributename
        AND
        attrtype.id_attributetype = attr.id_attributetype
        AND
        chres.id_comb = ".$combID."";*/

        $sql = "
            SELECT              
                chres.value,
                chres.id_combhasresource,               
                chres.id_attribute
            FROM
                ds_combhasresource AS chres             
            WHERE
                chres.id_comb = ".$combID."
            ORDER BY chres.id_attribute";       
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }
    } //END OF METHOD

    static public function UpdateCombAttribute (&$attributes)
    {
        assert(is_array($attributes));
        assert(count($attributes)>0);

        $common = self::getCommon();

        $sqls = array();
        foreach($attributes as $attributeTypeName=>$attributesSet)
        {
            foreach($attributesSet as $attributeName=>$v)
            {
                if($v['IS_MODIFYED'] == false)
                {
                    continue;
                };

                // assert(is_float($v['VALUE']));
                assert($v['ID']>0);

                $attributes[$attributeTypeName][$attributeName]['IS_MODIFYED'] = false;

                $sqls[] = "
                UPDATE ds_combhasresource
                SET value = '".$v['VALUE']."'
                WHERE
                    id_combhasresource = ".$v['ID'];
            }
        }

        if(count($sqls)>0)
        {
            foreach($sqls as $k=>$sql)
            {
                $res = $common->executeQuery($sql);
                assert(empty($common->errormsg));
            }
        }

        return true;
    }

    static public function GetWarBaseByCombID ($combID)
    {
        settype ( $combID, 'integer' );
        assert ( $combID > 0 );

        $common = self::getCommon();

        $sql = sprintf ( "
                    SELECT
                        id_attachedbuilding
                    FROM
                        ds_attachedbuilding ab,
                        ds_building b
                    WHERE
                            ab.id_building = b.id_building
                        AND ab.id_comb = %d
                        AND ab.active = true
                        AND b.buildingname = 'MILITARY_BASE'
                    ",$combID );

        $res = $common->getResultArray ( self::FormMark(__METHOD__, $combID), $sql, "", "", "GLOBAL_CACHE" );
        assert(empty($common->errormsg));

        if ( sizeof ( $res ) > 0 )
        {
            return $res[0]['id_attachedbuilding'];
        }
        return false;
    }//END OF METHOD

    static public function UpdateComb(DE_Comb $comb)
    {
        $common = self::getCommon();

        $sql = "
            UPDATE 
                ds_comb
            SET 
                id_userinstance = ".$comb->getUserInstanceID().",
                combscore = ".$comb->getCombScore()."           
            WHERE 
                id_comb = ".$comb->getID()."
            ";      

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));

        return true;
    }//END OF METHOD

    static public function UpdateCombName(DE_Comb $comb)
    {
        $common = self::getCommon();

        $sql = "
            UPDATE ds_combname
            SET combname = '".$comb->getName()."'
            WHERE id_comb = ".$comb->getID()."
            ";      

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));

        return true;
    }//END OF METHOD

    static public function GetUserInstanceCombs($userInstanceID)
    {
        settype ( $userInstanceID, 'integer' );

        $common = self::getCommon();

        $sql = "SELECT id_comb
                FROM ds_comb
                WHERE id_userinstance = ".$userInstanceID;
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }//END OF METHOD

    static public function GetCombsByUserInstance($userInstanceID, $notCapture = false)
    {
        settype ( $userInstanceID, 'integer' );

        $common = self::getCommon();

        $where = "";
        if ($notCapture) {
            $where = " AND co.is_capture = 'f'";
        }

        $sql = "SELECT
                    ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber as coords,
                    co.id_comb,
                    (
                    select 
                        combname
                    from 
                        ds_combname
                    where 
                        id_comb = co.id_comb
                    ) AS combname,
                    co.is_capture
                FROM 
                    ds_comb co,
                    ds_combcomplex cc,
                    ds_ring ri
                WHERE 
                    ri.id_ring = cc.id_ring
                     AND 
                    cc.id_combcomplex = co.id_combcomplex
                     AND 
                    co.id_userinstance = ".$userInstanceID."
                    $where
                order by ri.ringnumber, cc.complexnumber, co.combnumber";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }//END OF METHOD

    static public function GetCellDataByCombAndCell($combID, $cellID)
    {
        assert(is_int($combID) && $combID > 0);
        assert(is_int($cellID) && $cellID > 0);

        $common = self::getCommon();

        $sql = "SELECT *
                FROM
                    ds_cell as c                    
                WHERE
                    c.id_comb = ".$combID."
                    AND
                    c.id_cell = ".$cellID;
        $res = $common->getResultArray(self::FormMark(__METHOD__, $combID."_".$cellID), $sql, "", "PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && (count($res) == 1 || count($res) == 0));

        if(count($res) == 0)
        {
            return array();
        }else{
            return $res[0];
        }
    }//END OF METHOD

    static public function GetCombCells($combID, $cellTypeID)
    {
        assert(is_int($combID) && $combID>0);
        assert(is_int($cellTypeID) && $cellTypeID>0);

        $common = self::getCommon();

        $sql = "SELECT
                    c.*
                FROM
                    ds_cell AS c
                WHERE
                    c.id_comb = ".$combID."
                    AND
                    c.id_celltype = ".$cellTypeID."
                ORDER BY c.cellnumber";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $combID."_".$cellTypeID), $sql, "", "", "NO_CACHE");

        assert(empty($common->errormsg));

        return $res;
    }//END OF METHOD

    static public function GetArtsOnCombsByCells($combID, $cellTypeID)
    {
        settype($combID, 'integer');
        assert($combID > 0);

        settype($cellTypeID, 'integer');
        assert($cellTypeID > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    c.id_cell,
                    c.cellnumber,
                    bha.value as slots_count,
                    a.artefactname,
                    ai.id_artefactinstance,
                    att.typename,
                    atn.attributename,
                    ahb.value,
                    ab.id_building,
                    ai.immortal,
                    ai.death_time,
                    (
                       SELECT atn2.attributename
                       FROM
                           ds_buildinghasattribute as bha2
                           LEFT JOIN ds_attribute as at2 on (at2.id_attribute = bha2.id_attribute)
                           LEFT JOIN ds_attributename as atn2 on (atn2.id_attributename = at2.id_attributename)
                       WHERE
                            bha2.id_building = ab.id_building
                            AND
                            at2.id_attributetype = 2 -- TURN_PRODUCTION
                    ) as production_type
                FROM
                    ds_cell as c
                    INNER JOIN ds_attachedbuilding as ab on (ab.id_cell = c.id_cell)
                    LEFT JOIN ds_artefactattachedbuilding as aab on (aab.id_attachedbuilding = ab.id_attachedbuilding)
                    LEFT JOIN ds_artefactinstance as ai on (ai.id_artefactinstance = aab.id_artefact)
                    LEFT JOIN ds_artefact as a on (a.id_artefact = ai.id_artefact)
                    LEFT JOIN ds_artefacthasbonus as ahb on (ahb.id_artefact = a.id_artefact)
                    LEFT JOIN ds_attribute as at on (at.id_attribute = ahb.id_attribute)
                    LEFT JOIN ds_attributetype as att on (att.id_attributetype = at.id_attributetype)
                    LEFT JOIN ds_attributename as atn on (atn.id_attributename = at.id_attributename)

                    LEFT JOIN ds_buildinghasattribute as bha on (bha.id_building = ab.id_building)
                WHERE
                    c.id_comb = $combID
                    AND
                    c.id_celltype = $cellTypeID
                    AND
                    bha.id_attribute = 17 -- MAX_AMOUNT => ARTEFACT_SLOTS
                ORDER BY c.id_cell";

        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));
        return $res;
    }//END OF METHOD

    static public function GetBuildedWarUnitsOnCombByCells($combID, $cellTypeID)
    {
        settype($combID, 'integer');
        assert($combID > 0);

        settype($cellTypeID, 'integer');
        assert($cellTypeID > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                ab.id_building,
                c.cellnumber AS id_cell,
                u.unitname,
                uip.amount,
                uip.endtime
            FROM
                ds_cell as c
                INNER JOIN ds_attachedbuilding as ab on (ab.id_cell = c.id_cell)
                LEFT JOIN ds_unitinproduction as uip on (uip.id_attachedbuilding = ab.id_attachedbuilding)
                LEFT JOIN ds_unit as u on (u.id_unit = uip.id_unit)
            WHERE
                c.id_comb = ".$combID."
                AND c.id_celltype = ".$cellTypeID."
                AND ab.active = 't'
                AND ab.underconstruction = 'f'
                AND ab.inactivation = 'f'
                AND ab.inupgrade = 'f'
                AND
                EXISTS
                (
                    SELECT
                        pu2.id_building
                    FROM
                        ds_produceableunit as pu2
                        INNER JOIN ds_unit AS u USING (id_unit)
                    WHERE
                        pu2.id_building = ab.id_building
                        AND
                        u.id_unitcontainertype = 1
                )";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));
        return $res;
    }//END OF METHOD

    static public function GetMinCombIDForPlacementOnRing($ringNumber, $worldID, $existsComb = array())
    {
        settype($ringNumber, 'integer');
        assert($ringNumber > 0);

        settype($worldID, 'integer');
        assert($worldID > 0);

        assert(is_array($existsComb));

        if(count($existsComb) > 0)
        {
            $existsCombAddon = " AND c.id_comb NOT IN (".implode(', ', $existsComb).")";
        }else{
            $existsCombAddon = "";
        }

        $common = self::getCommon();

        $sql = "
            SELECT
                MIN(c.id_comb) AS id_comb
            FROM
                ds_comb AS c
                INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                LEFT JOIN ds_reservedcomb AS rc ON (rc.id_comb = c.id_comb)
            WHERE
                c.id_userinstance IS NULL
                AND
                r.id_world = $worldID
                AND
                r.ringnumber = $ringNumber
                AND
                c.is_capture = 'f'
                AND
                NOT EXISTS (
                    SELECT id_comb
                    FROM ds_combname
                    WHERE id_comb = c.id_comb
                )
                AND
                rc.id_comb IS NULL ".$existsCombAddon;

        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));
        return (int)$res[0]['id_comb'];
    }//END OF METHOD

    static public function GetCountFreeCombsInComplexRange($id_world, $id_race, $minComplex, $maxComplex)
    {
        settype($id_world, 'integer');
        assert($id_world > 0);

        settype($id_race, 'integer');
        assert($id_race > 0);

        settype($minComplex, 'integer');
        assert($minComplex >= 0);

        settype($maxComplex, 'integer');
        assert($maxComplex > 0);

        assert($maxComplex > $minComplex);

        $common = self::getCommon();

        $sql = "
            SELECT
                count(id_comb)
            FROM
                ds_comb AS co
                INNER JOIN ds_combcomplex AS cc ON (co.id_combcomplex = cc.id_combcomplex)
                INNER JOIN ds_ring AS r ON (cc.id_ring = r.id_ring)
                INNER JOIN ds_world AS w ON (r.id_world = w.id_world)
            WHERE
                co.id_userinstance IS NULL
                AND
                r.ringnumber = $id_race
                AND
                w.id_world = $id_world
                AND
                co.is_capture = 'f'
                AND
                NOT EXISTS
                (
                    SELECT
                        id_comb
                    FROM
                        ds_reservedcomb
                    WHERE
                        id_comb = co.id_comb
                )
                AND
                cc.complexnumber < $maxComplex
                AND
                cc.complexnumber > $minComplex
            ";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");

        assert(empty($common->errormsg));
        return (int)$res[0]['count'];
    }//END OF METHOD

    static public function GetFreeCombIdInComplexsRangeByRaceAndRandomOffset($id_world, $id_race, $minComplex, $maxComplex, $offset)
    {
        settype($id_world, 'integer');
        assert($id_world > 0);

        settype($id_race, 'integer');
        assert($id_race > 0);

        settype($minComplex, 'integer');
        assert($minComplex >= 0);

        settype($maxComplex, 'integer');
        assert($maxComplex > 0);

        assert($maxComplex > $minComplex);

        settype($offset, 'integer');
        assert($offset >= 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                id_comb
            FROM
                ds_comb AS co
                INNER JOIN ds_combcomplex AS cc ON (co.id_combcomplex = cc.id_combcomplex)
                INNER JOIN ds_ring AS r ON (cc.id_ring = r.id_ring)
                INNER JOIN ds_world AS w ON (r.id_world = w.id_world)
            WHERE
                co.id_userinstance IS NULL
                AND
                r.ringnumber = $id_race
                AND
                w.id_world = $id_world
                AND
                co.is_capture = 'f'
                AND
                NOT EXISTS
                (
                    SELECT
                        id_comb
                    FROM
                        ds_reservedcomb
                    WHERE
                        id_comb = co.id_comb
                )
                AND
                cc.complexnumber < $maxComplex
                AND
                cc.complexnumber > $minComplex
            LIMIT 1
            OFFSET $offset
            ";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));
        return (int)$res[0]['id_comb'];
    }//END OF METHOD

    /**
     * Есть в комплексе, в котором находится сота, боты
     *
     * @param DE_Comb $comb
     * @return (int) кол-во ботов в комплексе 
     */
    static public function HasNPCInSameComplex($complexID)
    {
        assert(is_int($complexID) && $complexID > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                count(*)
            FROM
                ds_comb AS c
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
                INNER JOIN ds_user AS u USING (id_user)
                INNER JOIN ds_npc_user AS nu USING (id_user)
            WHERE
                c.id_combcomplex = ".$complexID."               
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }//END OF METHOD

    /**
     * Функция пытается найти соту в соседнем комплексе с заданой сотой
     *
     * @param DE_Comb $comb
     * @return (int) ID соты
     */
    static public function GetFreeCombInComplex($worldID, $ringNumber, $complexNumber, array $existCombs = array())
    {
        assert(is_int($worldID) && $worldID > 0);
        assert(is_int($ringNumber) && $ringNumber > 0);
        assert(is_int($complexNumber) && $complexNumber > 0);

        $common = self::getCommon();

        if(count($existCombs) > 0)
        {
            $addon = "
                AND
                c.id_comb NOT IN (".implode(', ', $existCombs).")";
        } else {
            $addon = '';
        }

        $sql = "
            SELECT
                c.id_comb
            FROM
                ds_combcomplex AS cc
                INNER JOIN ds_comb AS c USING (id_combcomplex)
                INNER JOIN ds_ring AS r USING (id_ring)
                LEFT JOIN ds_reservedcomb AS rc USING (id_comb)
            WHERE
                c.id_userinstance IS NULL
                AND
                cc.complexnumber = ".$complexNumber."
                AND
                r.ringnumber = ".$ringNumber."
                AND
                c.is_capture = 'f'
                AND
                r.id_world = ".$worldID."
                AND
                rc.id_comb IS NULL
                $addon
            LIMIT 1
            ";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));

        if(count($res) == 0)
        {
            return 0;
        }

        return (int)$res[0]['id_comb'];
    }//END OF METHOD

    /**
     * Список зданий на соте в опредленной ее части.
     *
     */
    static public function GetAttachedBuildibgsInCombPart($combID, $cellTypeID)
    {
        assert(is_int($combID) && $combID > 0);
        assert(is_int($cellTypeID) && $cellTypeID > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    ce.cellnumber,
                    ab.id_building,
                    ab.active,
                    ab.underconstruction,   
                    ab.inactivation,
                    ab.inupgrade,
                    ab.busy,
                    ab.starttime,
                    ab.endtime  
                FROM
                    ds_attachedbuilding AS ab   
                    INNER JOIN ds_cell AS ce ON (ab.id_cell = ce.id_cell)
                WHERE
                    ab.id_comb = ".$combID."
                    AND
                    ce.id_celltype = ".$cellTypeID."
                ORDER BY ce.cellnumber";                        
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));

        return $res;
    }//END OF METHOD

    /**
     * Навзание части соты по ее ID 
     *
     */
    static public function GetCellTypeNameByID($cellTypeID)
    {
        assert(is_int($cellTypeID) && $cellTypeID > 0);

        $common = self::getCommon();

        $sql = "SELECT celltypename
                FROM ds_celltype
                WHERE id_celltype = $cellTypeID";                       
        $res = $common->getResultArray(self::FormMark(__METHOD__, $cellTypeID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0]['celltypename'];
    }//END OF METHOD


    static public function GetCellByID($cellID)
    {
        assert(is_int($cellID) && $cellID > 0);

        $common = self::getCommon();

        $sql = "SELECT *
                FROM ds_cell
                WHERE id_cell = $cellID";                       
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0];
    }//END OF METHOD

    static public function GetProtectiveField($combID)
    {
        assert(is_int($combID) && $combID > 0);

        $common = self::getCommon();

        $sql = "SELECT *
                FROM ds_protective_field
                WHERE id_comb = $combID";                       
        $res = $common->getResultArray('', $sql);
        if (!count(res)) return NULL;
        assert(empty($common->errormsg));
        return $res[0];
    }//END OF METHOD

    static public function SetProtectiveField($combID, $fieldVal, $tempoparyDefend)
    {
        assert($combID > 0);
        assert($fieldVal >= 0 && $fieldVal <= 100);

        $common = self::getCommon();

        $sql = "UPDATE
                    ds_protective_field
                SET 
                    protected_field = $fieldVal,
                    end_temporary_defend = '".$tempoparyDefend->getPgFormat()."'
                WHERE id_comb = $combID";                       
        $res = $common->executeQuery($sql);
    
        assert(empty($common->errormsg));
    }//END OF METHOD

    /**
    *   
    * Выбирает стоимость восстановления одного процента защитного поля
    * 
    */
    static public function GetProtectiveFieldRestorePrice($worldId)
    {
        assert(is_int($worldId) && $worldId > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    CASE
                        WHEN an.attributename IS NULL THEN m.money_name
                        WHEN m.money_name IS NULL THEN an.attributename
                    END as full_name,
                    CASE
                        WHEN an.attributename IS NULL THEN m.short_name
                        WHEN m.money_name IS NULL THEN substr(an.attributename, 0, 3)
                    END as name,
                    trcd.value as price
                FROM
                    ds_protective_field_restore_price AS trcd
                    INNER JOIN ds_trade_course AS tc ON (tc.id_tradecourse = trcd.id_tradecourse)
                    LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
                    LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
                    LEFT JOIN ds_gamemodifier gm ON (gm.id_world = trcd.id_world AND gm.modifiername = 'UNIT_REINCARNATION_MULTIPLIER')              
                WHERE
                    trcd.id_world = $worldId
                ORDER BY trcd.id_tradecourse";  
        $res = $common->getResultArray ( "PROTECTIVE_FIELD_RESTORE_PRICE", $sql, "", "", "WORLD_CACHE" );
        assert(empty($common->errormsg));

        return $res;
    }

    /**
    *   
    * Отменяет все действия на соте : Стоительства, апгрейда, активации
    * 
    */
    static public function CancelAllActionsOnCombs($combId, $idCombTo)
    {
        assert(is_int($combId) && $combId > 0);
        assert(is_int($idCombTo) && $idCombTo > 0);

        $common = self::getCommon();

        // Выбираем  все иветны строительства на соте
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 4 --CONSTRUCT_BUILDING
                     AND
                     eo.optionname = 'ID_Comb'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_AttachedBuilding'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $attachedBuildingID = (int)$res[0]['value'];
            assert($attachedBuildingID > 0);

            // сносим здание
            $sql =  "
                DELETE FROM ds_attachedbuilding
                WHERE id_attachedbuilding = $attachedBuildingID";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));

            // прибиваем эвент
            $event = DE_EventQueue::CreateFromID((int)$bindeqid);
            $event->setIsTrash(true);
        }

        // Выбираем  все иветны апгрейда на соте
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 9 --UPGRADE_BUILDING
                     AND
                     eo.optionname = 'ID_Comb'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_AttachedBuilding'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $attachedBuildingID = (int)$res[0]['value'];
            assert($attachedBuildingID > 0);

            // отменяем апгрейд
            $sql =  "
                UPDATE ONLY ds_attachedbuilding
                SET inupgrade = false
                WHERE id_attachedbuilding = $attachedBuildingID";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));

            // прибиваем эвент
            $event = DE_EventQueue::CreateFromID((int)$bindeqid);
            $event->setIsTrash(true);
        }

        // Выбираем  все иветны активации на соте
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 8 --ACTIVATE_BUILDING
                     AND
                     eo.optionname = 'ID_Comb'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_AttachedBuilding'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $attachedBuildingID = (int)$res[0]['value'];
            assert($attachedBuildingID > 0);

            // отменяем активацию
            $sql =  "
                UPDATE ONLY ds_attachedbuilding
                SET inactivation = false
                WHERE id_attachedbuilding = $attachedBuildingID";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));

            // прибиваем эвент
            $event = DE_EventQueue::CreateFromID((int)$bindeqid);
            $event->setIsTrash(true);
        }

        // Ставим количество построек соты в 0
        $sql =  "
            UPDATE ds_combhasresource
            SET value = 0
            WHERE 
                id_comb = $combId
                AND 
                id_attribute = 28";
        $res = $common->executeQuery ( $sql );
        assert(empty($common->errormsg));

        // Выбираем  все иветны строительства военных юнитов на соте
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 20 --TRAIN_WAR_UNIT
                     AND
                     eo.optionname = 'ID_Comb'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_AttachedBuilding'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $attachedBuildingID = (int)$res[0]['value'];
            assert($attachedBuildingID > 0);


            // прибиваем производство
            $sql =  "
                DELETE FROM ds_unitinproduction
                WHERE id_attachedbuilding = $attachedBuildingID";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));

            // прибиваем эвент
            $event = DE_EventQueue::CreateFromID((int)$bindeqid);
            $event->setIsTrash(true);

            // отцепляем евент от здания
            $sql =  "
                DELETE FROM 
                ds_attachedbuildingbindeventqueue
                WHERE 
                id_attachedbuilding = $attachedBuildingID";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));
        }

        // Выбираем  все иветны терраформинга
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 36 --TERRAFORMING
                     AND
                     eo.optionname = 'ID_Comb'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_Terraforming'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $terraforminglD = (int)$res[0]['value'];
            assert($terraforminglD > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_Cell'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $cellID = (int)$res[0]['value'];
            assert($cellID > 0);

            // Получаем атрибуты здания по типу ячейки
            $res = DS_Building::GetAttachedBuildingByCellID($cellID);

            $combID = (int)$res[0]['id_comb'];
            $attachedBuildingID = (int)$res[0]['id_attachedbuilding'];
            assert($attachedBuildingID > 0);

            // Возвращаем селу предидущий тип грунта
            DS_Terraforming::SetOldGroundTypeOnCellByTerraformingID($terraforminglD, $cellID);

            // прибиваем эвент
            $event = DE_EventQueue::CreateFromID((int)$bindeqid);
            $event->setIsTrash(true);

            //андиндем ивент
            DS_Terraforming::UnBindEventQueue($cellID);

            //Разрешаем удаление здания
            DS_Building::InsertAttachedBuildingAttribute($attachedBuildingID, 'CURRENT_FLAG', 'DESTROYABLE');
        }

        // Выбираем  все иветны тренировки ученых а архов
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 14 --TRAIN_UNIT
                     AND
                     eo.optionname = 'ID_Comb'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            // прибиваем эвент
            $event = DE_EventQueue::CreateFromID((int)$bindeqid);
            $event->setIsTrash(true);
        }

        // Выбираем все археологические экспедиции
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 16 --SEND_ARCHEOLOGY_TEAM
                     AND
                     eo.optionname = 'ID_Comb'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_Comb'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $comblD = (int)$res[0]['value'];
            assert($comblD > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'TeamNumber'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $TeamNumber = (int)$res[0]['value'];
            assert($TeamNumber > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'CountOfArcheologist'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $CountOfArcheologist = (float)$res[0]['value'];
            assert($CountOfArcheologist > 0);


            // удаляем экпедицию
            DS_Archeology::deleteArcheologyTeam($comblD, $TeamNumber);

            $combAttributes = DE_CombAttributes::CreateFromCombID($comblD);

            // убираем из бродячих археологов
            $combAttributes->deltaAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', -$CountOfArcheologist);

            // определяем текущее кол-во археологов в наличии
            $current = floor($combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', true));

            // определяем текущее кол-во бродячих археологов
            $awayAmount = floor($combAttributes->getAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', true));

            // всего
            $current += $awayAmount;

            // максимальное кол-во археологов
            $max = floor($combAttributes->getAttribute('MAX_AMOUNT', 'ARCHEOLOGIST', true));

            // если текущее кол-во + кол-во в экспедиции больше максимального - урезаем экпедицию
            if(($current + $CountOfArcheologist) > $max)
            {
                $CountOfArcheologist = $max - $current;
            }

            // добавляем ученых из экспедиции к текущему кол-ву на соте
            $combAttributes->deltaAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', $CountOfArcheologist);

            // прибиваем эвент
            $event = DE_EventQueue::CreateFromID((int)$bindeqid);
            $event->setIsTrash(true);
        }

        // Выбираем изучение науки с этой соты
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 12 --NEW_RESEARCH
                     AND
                     eo.optionname = 'ID_Comb'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            // отцепляем эвент
            $sql =  "
                DELETE FROM 
                ds_userhassciencebindeventqueue
                WHERE 
                id_eventqueue = $bindeqid";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_UserInstance'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $idUserInstance = (int)$res[0]['value'];
            assert($idUserInstance > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_Science'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $idScience = (int)$res[0]['value'];
            assert($idScience > 0);

            // удаляем науку
            $sql =  "
                DELETE FROM 
                ds_userhasscience
                WHERE 
                id_userinstance = $idUserInstance
                AND id_science = $idScience";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));

            // прибиваем эвент
            $event = DE_EventQueue::CreateFromID((int)$bindeqid);
            $event->setIsTrash(true);

        }

        // Выбираем трансфер ресурсов с этой соты
        // Пока оставляем трансфер с этой соты - ничего нам не мешает
        /*$sql = "SELECT
        eq.id_eventqueue
        FROM
        ds_eventqueue eq
        LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
        LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)
        WHERE
        eq.id_event = 26 --TRANSFER_RESOURCE
        AND
        eo.optionname = 'ID_CombFrom'
        AND
        eho.value = $combId";
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
        $bindeqid = (int)$data['id_eventqueue'];
        assert($bindeqid > 0);

        // Перенапрявляем ивент
        $sql =  "
        UPDATE
        ds_eventhasoption
        SET
        value = $idCombTo
        WHERE
        id_eventoption = 184 -- ID_CombFrom
        AND
        id_eventqueue  = $bindeqid";
        $res = $common->executeQuery ( $sql );
        assert(empty($common->errormsg));
        }*/

        // Выбираем трансфер ресурсов на эту соту
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 26 --TRANSFER_RESOURCE
                     AND
                     eo.optionname = 'ID_CombTo'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            // Перенапрявляем ивент
            $sql =  "
                  UPDATE 
                    ds_eventhasoption
                SET 
                    value = $idCombTo
                WHERE 
                    id_eventoption = 185 -- ID_CombTo
                    AND 
                    id_eventqueue  = $bindeqid";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));
        }

        // Выбираем пересылку армий на эту соту
        $sql = "SELECT
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eq.id_event = 37 --MOVE_TO_COMB
                     AND
                     eo.optionname = 'ID_CombTo'
                     AND
                     eho.value = $combId";  
        $events = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        foreach ($events as $key => $data)
        {
            $bindeqid = (int)$data['id_eventqueue'];
            assert($bindeqid > 0);

            $sql = "
                SELECT 
                    eho.value
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)                                                     
                WHERE 
                     eo.optionname = 'ID_Army'
                     AND
                     eho.id_eventqueue  = $bindeqid";
            $res = $common->getResultArray('', $sql);
            assert(empty($common->errormsg) && count($res) == 1);

            $idArm = (int)$res[0]['value'];
            assert($idArm > 0);

            // Перезаписываем в ивенте запись о связанной соте
            $sql = "
                UPDATE 
                    ds_eventhasoption
                SET 
                    value = $idCombTo
                WHERE 
                    id_eventoption = 1000010
                    AND 
                    id_eventqueue  = $bindeqid";

            $res = $common->executeQuery($sql);
            assert(empty($common->errormsg));

            // Перезаписываем стартовую соту движения армии
            $sql = "
               UPDATE 
                    ds_unitcontaineraction
                SET 
                    id_targetcomb = $idCombTo
                WHERE 
                    id_unitcontainer = $idArm";

            $res = $common->executeQuery($sql);
            assert(empty($common->errormsg));
        }

        return $res;
    }

    /**
    *   
    * Логируем захват колонии
    * 
    */
    static public function RewriteHumansToHome($combFrom, $idCombTo)
    {
        assert($combFrom > 0);
        assert($idCombTo > 0);

        $combFromAttributes = DE_CombAttributes::CreateFromCombID($combFrom);
        $combToAttributes = DE_CombAttributes::CreateFromCombID($idCombTo);

        // Перемещаем архов
        
        // определяем текущее кол-во археологов, которое надо списать
        $currentFrom = floor($combFromAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', true));

        // Смотрим сколько сейчас археологов на домашке
        $currentTo = floor($combToAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', true));
        $awayTo = floor($combToAttributes->getAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', true));

        // Смотрим сколько археологов на домашке в экспедициях
        $currentTo += $awayTo;

        // максимальное кол-во археологов
        $max = floor($combToAttributes->getAttribute('MAX_AMOUNT', 'ARCHEOLOGIST', true));
        
        $add = $currentFrom;

        // если текущее кол-во + кол-во в экспедиции больше максимального - урезаем экпедицию
        if(($currentTo + $currentFrom) > $max)
        {
            $add = $max - $currentTo;
        }

        // добавляем архов на домашку
        $combToAttributes->deltaAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', $add);
        // снимаем архов с колонии
        $combFromAttributes->deltaAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', -$currentFrom);

        
        // Перемещаем ученых
        
        // определяем текущее кол-во археологов, которое надо списать
        $currentFrom = floor($combFromAttributes->getAttribute('CURRENT_AMOUNT', 'SCIENTIST', true));

        // Смотрим сколько сейчас археологов на домашке
        $currentTo = floor($combToAttributes->getAttribute('CURRENT_AMOUNT', 'SCIENTIST', true));
        
        // максимальное кол-во ученых
        $max = floor($combToAttributes->getAttribute('MAX_AMOUNT', 'SCIENTIST', true));

        if(($currentTo + $currentFrom) > $max)
        {
            $add = $max - $currentTo;
        }
        
        // добавляем ученых на домашку
        $combToAttributes->deltaAttribute('CURRENT_AMOUNT', 'SCIENTIST', $add);
        // снимаем ученых с колонии
        $combFromAttributes->deltaAttribute('CURRENT_AMOUNT', 'SCIENTIST', -$currentFrom);
    }

    /**
    *   
    * Логируем захват колонии
    * 
    */
    static public function LogCaptureColony($combId, $idOldOwner, $idNewOwner, GB_Date $date)
    {
        assert(is_int($combId) && $combId > 0);
        assert(is_int($idOldOwner) && $idOldOwner >= 0);
        assert(is_int($idNewOwner) && $idNewOwner >= 0);


        $common = self::getCommon();

        $sql = "
                INSERT INTO ds_capture_colony_history
                    (id_comb, id_old_owner, id_new_owner, capture_date, id_world)
                VALUES
                    ($combId, $idOldOwner, $idNewOwner, '".$date->getPgFormat()."', ".(int)$_SESSION['world'].")";

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
}//END OF CLASS


?>