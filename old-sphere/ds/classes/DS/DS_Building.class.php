<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Building  extends DS_Base
{
    static public function GetByID($id)
    {
        settype($id, 'integer');
        assert($id > 0);

        $common = self::getCommon();

        $sql = "
            SELECT 
                b.id_building,
                b.buildingname,
                b.id_localizedname,
                bt.type
            FROM 
                ds_building AS b
                LEFT JOIN ds_buildingtype AS bt USING (id_buildingtype)
            WHERE id_building = ".$id."";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $id), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        if(!$res)
        {
            return false;
        }else{
            return $res[0];
        }
    }//END OF METHOD

    static public function GetRaceBuildingByRaceAndBuilding($raceID, $buildingID)
    {
        settype($raceID, 'integer');
        assert($raceID > 0);

        settype($buildingID, 'integer');
        assert($buildingID > 0);

        $common = self::getCommon();

        $sql = "SELECT *
                FROM ds_buildingcanbuildbyrace
                WHERE 
                    id_building = ".$buildingID."
                    AND 
                    id_race = ".$raceID;

        $res = $common->getResultArray(self::FormMark(__METHOD__, $raceID."_".$buildingID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        return $res[0];
    }

    static public function GetIDByName($name)
    {
        assert(!empty($name));

        $common = self::getCommon();

        $sql = "SELECT
                    id_building
                FROM ds_building
                WHERE buildingname = '".$name."'";      
        $res = $common->getResultArray(self::FormMark(__METHOD__, $name), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        }else{
            return $res[0]['id_building'];
        }
    }//END OF METHOD

    static public function GetBuildingAttributes($buildingID)
    {
        settype($buildingID, 'integer');
        assert($buildingID > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    att.typename,
                    atn.attributename,
                    ba.value
                FROM
                    ds_buildinghasattribute as ba
                    INNER JOIN ds_attribute as at ON (ba.id_attribute = at.id_attribute)
                    INNER JOIN ds_attributename as atn ON (at.id_attributename = atn.id_attributename)
                    INNER JOIN ds_attributetype as att ON (at.id_attributetype = att.id_attributetype)
                WHERE
                    ba.id_building = ".$buildingID;     
        $res = $common->getResultArray(self::FormMark(__METHOD__, $buildingID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        if(!$res)
        {
            return false;
        }else{
            return $res;
        }
    }//END OF METHOD

    static public function DeleteAttachedBuildingAttribute($attachedBuildingID, $attributeType, $attributeName)
    {
        assert(is_int($attachedBuildingID) && $attachedBuildingID > 0);
        assert(!empty($attributeType));
        assert(!empty($attributeName));

        $common = self::getCommon();

        $sql = "
            DELETE FROM ds_attachedbuildinghasattribute
            WHERE
                id_attachedbuilding = ".$attachedBuildingID."
                AND
                id_attribute =
                (
                    SELECT a.id_attribute                   
                    FROM
                        ds_attribute as a
                        INNER JOIN ds_attributetype as att on (att.id_attributetype = a.id_attributetype)
                        INNER JOIN ds_attributename as atn on (atn.id_attributename = a.id_attributename)
                    WHERE
                        att.typename = '".$attributeType."'
                        AND
                        atn.attributename = '".$attributeName."'
                )";     
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return true;
    }//END OF METHOD

    static public function InsertAttachedBuildingAttribute($attachedBuildingID, $attributeType, $attributeName)
    {
        assert(is_int($attachedBuildingID) && $attachedBuildingID > 0);
        assert(!empty($attributeType));
        assert(!empty($attributeName));

        $common = self::getCommon();

        $sql = "
            INSERT INTO ds_attachedbuildinghasattribute
                            (id_attachedbuilding, id_attribute)
                            VALUES 
                            (".$attachedBuildingID.",
                                                (SELECT a.id_attribute                      
                                                FROM
                                                    ds_attribute as a
                                                    INNER JOIN ds_attributetype as att on (att.id_attributetype = a.id_attributetype)
                                                    INNER JOIN ds_attributename as atn on (atn.id_attributename = a.id_attributename)
                                                WHERE
                                                    att.typename = '".$attributeType."'
                                                    AND
                                                    atn.attributename = '".$attributeName."')
                               )";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return true;
    }//END OF METHOD

    static public function GetAttachedBuildingByCellID($cellID)
    {
        assert(is_int($cellID) && $cellID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT *
                FROM ds_attachedbuilding AS ab
                WHERE ab.id_cell = $cellID";        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }//END OF METHOD

    static public function GetAttachedBuildingByID($attachedBuildingID)
    {
        assert(is_int($attachedBuildingID) && $attachedBuildingID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT 
                    ab.id_cell,
                    ab.id_comb,
                    c.id_celltype
                FROM 
                    ds_attachedbuilding AS ab
                    INNER JOIN ds_cell AS c ON (c.id_cell = ab.id_cell)
                WHERE ab.id_attachedbuilding = $attachedBuildingID";        
        $res = $common->getResultArray("", $sql);

        assert(empty($common->errormsg) && count($res) == 1);

        return $res[0];
    }//END OF METHOD

    static public function GetAttachedBuildingByCombIDAndBuildingID($combID, $buildingID)
    {
        assert(is_int($combID) && $combID > 0);
        assert(is_int($buildingID) && $buildingID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT *
                FROM ds_attachedbuilding AS ab
                WHERE
                    ab.id_comb = $combID
                    AND
                    ab.id_building = $buildingID";      
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
    
    static public function GetAttachedBuilddingNotUnderConstruction($combID, $buildingID)
    {
        assert(is_int($combID) && $combID > 0);
        assert(is_int($buildingID) && $buildingID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT *
                FROM ds_attachedbuilding AS ab
                WHERE
                    ab.id_comb = $combID
                    AND
                    ab.id_building = $buildingID
                    AND
                    ab.underconstruction = 'f'";        
        //echo $sql."\n";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }//END OF METHOD

    static public function GetScienceLevelForUpgradeBuilding($scienceName, $buildingID)
    {
        assert(!empty($scienceName));
        assert(is_int($buildingID) && $buildingID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT 
                               s.level
                        FROM ds_buildingneedsscience as bns
                        LEFT JOIN ds_science as s
                        ON (s.id_science = bns.id_science)
                        WHERE bns.id_building = ".$buildingID."
                        AND s.sciencename LIKE '".$scienceName."%'";        
        $res = $common->getResultArray(self::FormMark(__METHOD__, $scienceName."_".$buildingID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        if (count($res) > 0) {
            return (int)$res[0]['level'];
        } else {
            return 0;
        }
        return $res;
    }//END OF METHOD

    /*
    *
    * Очки за построку зданая
    *
    */
    static public function GetBuildingScore($buildingID)
    {
        return self::GetBuildingScoreNew($buildingID);

        /*
        assert(is_int($buildingID) && $buildingID > 0);

        $common = self::getCommon();

        $sql = "
        SELECT 
            COALESCE(sum(combhas),0) as brank
        FROM (
        SELECT
                bha.value*(SELECT exchangerate
                            FROM ds_resource
                            WHERE id_attributename = atn.id_attributename) 
                as combhas
        FROM 
                ds_buildinghasattribute bha,
                ds_attribute a,
                ds_attributename atn,
                ds_attributetype att
        WHERE 
                bha.id_building = ".$buildingID."
                AND bha.id_attribute = a.id_attribute
                AND a.id_attributename = atn.id_attributename
                AND a.id_attributetype = att.id_attributetype
                AND att.typename = 'PRODUCTION_COSTS'
                AND atn.attributename != 'CONSTRUCTION_TIME') as t";        

        $ehres = $common->getResultArray ( "building_cost_id_building_".$buildingID, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
        assert ( empty ( $common->errormsg ) );

        return $ehres[0]['brank']/10;
        */
    }//END OF METHOD

    /*
    *
    * Очки за построку зданая по новому
    *
    */
    static public function GetBuildingScoreNew($buildingID)
    {
        assert(is_int($buildingID) && $buildingID > 0);

        $common = self::getCommon();

        $sql = "
        SELECT 
            building_score
        FROM 
            ds_building
        WHERE 
            id_building = ".$buildingID;        

        $ehres = $common->getResultArray ( "building_cost_new_id_building_".$buildingID, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
        assert ( empty ( $common->errormsg ) );

        return (int)$ehres[0]['building_score'];
    }//END OF METHOD

    /*
    *
    * Конвертируем военные заводы другой рассы в свою
    *
    */
    static public function ConvertTrainBuildings($combID, $idRace)
    {
        assert(is_int($combID) && $combID > 0);
        if (!is_int($idRace) || $idRace <= 0) return;

        $bild = array(
                    1 => array(
                            70 => 67,
                            73 => 67,
                            71 => 68,
                            74 => 68,
                            72 => 69,
                            75 => 69,
                            44 => 43, // Огромный нано-кристаллизатор(псолао) => Большой нано-кристаллизатор
                            49 => 17, // Ферма клонов => Ферма
                            86 => 40, // Огромная кузница кадериума (линсу) => Огромная кузница кадериума (вор)
                            87 => 43, // Огромный нано-кристаллизатор (линсу) => Большой нано-кристаллизатор
                            90 => 42, // Огромный продиумный коллектор => Большой сборщик продиума
                            15 => 38, // Город => Деревня
                            ),
                    2 => array(
                            67 => 70,
                            73 => 70,
                            68 => 71,
                            74 => 71,
                            69 => 72,
                            75 => 72,
                            40 => 86, // Огромная кузница кадериума (вор) => Огромная кузница кадериума (линсу)
                            44 => 87, // Огромный нано-кристаллизатор(псолао) => Огромный нано-кристаллизатор (линсу)
                            
                            ),
                    3 => array(
                            67 => 73,
                            70 => 73,
                            68 => 74,
                            71 => 74,
                            69 => 75,
                            72 => 75,
                            40 => 41, // Огромная кузница кадериума (вор) => Большая кузница кадериума
                            49 => 17, // Ферма клонов => Ферма
                            86 => 41, // Огромная кузница кадериума (линсу) => Большая кузница кадериума
                            87 => 44, // Огромный нано-кристаллизатор (линсу) => Огромный нано-кристаллизатор(псолао)
                            90 => 42, // Огромный продиумный коллектор => Большой сборщик продиума
                            15 => 9,  // Город => Жилой дом
                            38 => 9,  // Деревня => Жилой дом
                            
                            ),
        );

        $common = self::getCommon();

        foreach ($bild[$idRace] as $bFrom => $bTo)
        {
            $sql = "
                 UPDATE 
                    ds_attachedbuilding
                 SET 
                    id_building = $bTo
                 WHERE
                    id_comb = $combID
                    AND
                    id_building = ".$bFrom;

            $res = $common->executeQuery ($sql);
            assert(empty($common->errormsg));
        }

        return;
    }//END OF METHOD

    static public function DeleteBuilding($attachedBuildingID)
    {
        assert(is_int($attachedBuildingID) && $attachedBuildingID > 0);

        $common = self::getCommon();

        $sql = "DELETE FROM ds_attachedbuildinghasattribute
                WHERE id_attachedbuilding = $attachedBuildingID";       
        $res = $common->executeQuery ($sql);
        assert(empty($common->errormsg));

        $sql = "DELETE FROM DS_attachedbuilding
                WHERE id_attachedbuilding = $attachedBuildingID";       
        $res = $common->executeQuery ($sql);
        assert(empty($common->errormsg));

    }//END OF METHOD
}

?>