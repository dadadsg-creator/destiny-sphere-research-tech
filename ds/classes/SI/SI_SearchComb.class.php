<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_SearchComb extends SI_Base
{
    static public function GetCombCellsAndBuildings($combID)
    {
        assert((int)$combID > 0);

        $sql = "
            SELECT
                *
            FROM
                ds_cell as ce
                INNER JOIN ds_celltype as ct on (ct.id_celltype = ce.id_celltype)
                LEFT JOIN ds_attachedbuilding as ab on (ab.id_cell = ce.id_cell)
                LEFT JOIN ds_building as b on (b.id_building = ab.id_building)              
            WHERE
                ce.id_comb = ".(int)$combID."
            ORDER BY ce.id_celltype, ce.cellnumber";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static public function GetCombTradeScopes($combID)
    {
        assert((int)$combID > 0);

        $sql = "
            SELECT
                *
            FROM
                ds_comballowedtradescope                
            WHERE
                id_comb = ".(int)$combID;
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static public function InsertTradeScope($combID, $scopeID)
    {
        assert((int)$combID > 0);
        assert((int)$scopeID > 0);

        $sql = "INSERT INTO ds_comballowedtradescope
                (id_comb, id_tradescope)
                VALUES
                ($combID, $scopeID)";               
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));
    }

    static public function GetBuildingArtefacts($attachedBuildingID)
    {
        assert((int)$attachedBuildingID >= 0);

        if((int)$attachedBuildingID == 0)
        {
            return array();
        }

        $sql = "
            SELECT
                ai.id_artefact,
                ai.immortal, 
                ai.birth_time, 
                ai.death_time
            FROM
                ds_artefactattachedbuilding as aab
                INNER JOIN ds_artefactinstance as ai on (ai.id_artefactinstance = aab.id_artefact)
            WHERE
                aab.id_attachedbuilding = ".(int)$attachedBuildingID."";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static public function GetAttachedBuildingAttributes($attachedBuildingID)
    {
        assert((int)$attachedBuildingID >= 0);

        if((int)$attachedBuildingID == 0)
        {
            return array();
        }

        $sql = "
            SELECT
                id_attribute
            FROM
                ds_attachedbuildinghasattribute
            WHERE
                id_attachedbuilding = ".(int)$attachedBuildingID."";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static public function InsertAttachedBuildingAttributes($attachedBuildingID, $attributeID)
    {
        assert((int)$attachedBuildingID >= 0);
        assert((int)$attributeID >= 0);

        if((int)$attachedBuildingID == 0 || (int)$attributeID == 0)
        {
            return;
        }

        $sql = "select * from ds_attachedbuildinghasattribute
            where id_attachedbuilding=$attachedBuildingID
            and id_attribute=$attributeID";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        if (!count($res)) {
            $sql = "
                INSERT INTO ds_attachedbuildinghasattribute
                (id_attribute, id_attachedbuilding)                 
                VALUES
                ($attributeID, $attachedBuildingID)";
            $res = self::$common->getResultArray("", $sql, "", "", "");
            assert(empty(self::$common->errormsg));
        }
    }

    static public function GetCombAttributes($combID)
    {
        assert((int)$combID > 0);

        $sql = "
            SELECT
                attrname.attributename,
                attrtype.typename,
                chres.value
            FROM
                ds_combhasresource chres
                INNER JOIN ds_attribute attr ON (chres.id_attribute = attr.id_attribute)
                INNER JOIN ds_attributetype attrtype ON (attr.id_attributetype = attrtype.id_attributetype)
                INNER JOIN ds_attributename attrname ON (attr.id_attributename = attrname.id_attributename)
                LEFT JOIN public.ds_resource r ON (attrname.id_attributename = r.id_attributename)
            WHERE
                (chres.id_attribute = attr.id_attribute) AND
                (attrname.id_attributename = attr.id_attributename) AND
                (attrtype.id_attributetype = attr.id_attributetype) AND
                (chres.id_comb = ".$combID.")";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static public function GetArchiologyArtefacts($combID)
    {
        assert((int)$combID > 0);

        $sql = "SELECT
                    ai.id_artefact,
                    ai.immortal, 
                    ai.birth_time, 
                    ai.death_time
                FROM 
                    ds_combhasartefact as cha
                    INNER JOIN ds_artefactinstance as ai on (ai.id_artefactinstance = cha.id_artefact)
                WHERE cha.id_comb = ".$combID;
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static public function GetCombArmys($combID)
    {
        assert((int)$combID > 0);

        $sql = "SELECT *
                FROM ds_unitcontainer
                WHERE id_basecomb = ".$combID;
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static public function GetArmyUnitGroups($armyID)
    {
        assert((int)$armyID > 0);

        $sql = "SELECT *
                FROM ds_unitcontainerhasunit
                WHERE id_army = ".$armyID;
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static public function GetCombUnits($combID)
    {
        assert((int)$combID > 0);

        $sql = "SELECT
                    uchu.id_unit,
                    sum(uchu.amount) as amount
                FROM
                    ds_unitcontainer as uc
                    INNER JOIN ds_unitcontainerhasunit as uchu on (uchu.id_army = uc.id_unitcontainer)
                WHERE
                    uc.id_basecomb = ".(int)$combID."
                    GROUP BY uchu.id_unit";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static public function GetCombIDByCoordinates($worldID, $ringNumber, $complexNumber, $combNumber)
    {
        assert((int)$worldID > 0);
        assert((int)$ringNumber > 0);
        assert((int)$complexNumber > 0);
        assert((int)$combNumber > 0);

        $sql = "
            SELECT
                c.id_comb
            FROM
                ds_comb AS c
                INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                INNER JOIN ds_ring AS r ON (cc.id_ring = r.id_ring)
                LEFT JOIN ds_combname AS cn ON (c.id_comb = cn.id_comb )
            WHERE
                r.ringnumber = $ringNumber
                    AND
                cc.complexnumber = $complexNumber
                    AND
                c.combnumber = $combNumber
                    AND
                r.id_world = $worldID";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(count($res) == 0)
        {
            return (int)0;
        }else{
            return (int)$res[0]['id_comb'];
        }


    }

    static public function GetCombDataByID($combID)
    {
        assert((int)$combID > 0);

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
                    co.is_capture
                FROM
                    ((ds_comb as co
                        LEFT JOIN ds_combname as cn ON (co.id_comb = cn.id_comb))
                            INNER JOIN ds_combcomplex as cc ON (co.id_combcomplex = cc.id_combcomplex))
                                INNER JOIN ds_ring as ri ON (cc.id_ring = ri.id_ring)
                WHERE
                    co.id_comb = ".$combID;
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res[0];
    }
    
    static public function GetProtectColonyByID($combID)
    {
        assert((int)$combID > 0);

        $sql = "SELECT
                    *
                FROM
                    ds_protective_field
                WHERE
                    id_comb = ".$combID;
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);

        return $res;
    }
    
    static public function InsertProtectField($combID, $protected_field, $end_temporary_defend)
    {
        assert((int)$combID > 0);

        $sql = "INSERT INTO 
                    ds_protective_field
                (id_comb, protected_field, end_temporary_defend)
                VALUES
                ($combID, $protected_field, '$end_temporary_defend')";
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));
    }

    static public function AssignComb($UIID, $combName, $combID)
    {
        assert((int)$UIID >= 0);
        assert((int)$combID > 0);
        assert(!empty($combName));

        $sql = "UPDATE ds_comb
                SET  id_userinstance = $UIID
                WHERE id_comb = $combID";       
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));

        // —мотрим, есть ли у соты им€

        $sql = "select id_comb from ds_combname where id_comb=$combID";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        if (count($res)) {
            $sql = "update ds_combname set combname='$combName' where id_comb=$combID";
        }
        else {
            $sql = "INSERT INTO ds_combname
                    (id_comb, combname)
                    VALUES
                    ($combID, '$combName')";
        }
        self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static public function SetCombCapture($combID)
    {
        assert((int)$combID > 0);

        $sql = "UPDATE ds_comb
                SET  is_capture = 't'
                WHERE id_comb = $combID";       
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));
    }

    static public function SetCellAndBuilding($combID, $cellData)
    {
        assert(is_array($cellData));
        assert((int)$combID > 0);

        $out = array
        (
        'CELL_ID' => 0,
        'ATTACHED_BUILDING_ID' => 0
        );

        $sql = "SELECT *
                FROM ds_cell
                WHERE 
                    id_comb = ".$combID."
                    AND
                    id_celltype = ".$cellData['id_celltype']."
                    AND
                    cellnumber = ".$cellData['cellnumber']."";
        $resCell = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));


        $sql = "UPDATE ds_cell
                SET  
                    id_groundtype = ".$cellData['id_groundtype']."
                WHERE id_cell = ".$resCell[0]['id_cell'];       
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));

        $out['CELL_ID'] = (int)$resCell[0]['id_cell'];

        if(empty($cellData['id_building']))
        {
            return  $out;
        }

        $sql = "SELECT *
                FROM ds_attachedbuilding
                WHERE 
                    id_cell = ".$resCell[0]['id_cell']."";
        $resBuilding = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(is_null($cellData['starttime']) || empty($cellData['starttime']))
        {
            $cellData['starttime'] = 'now()';
        } else {
            $cellData['starttime'] = "'".$cellData['starttime']."'";
        }

        if(is_null($cellData['endtime']) || empty($cellData['endtime']))
        {
            $cellData['endtime'] = 'now()';
        } else {
            $cellData['endtime'] = "'".$cellData['endtime']."'";
        }

        if(count($resBuilding) > 0)
        {
            $out['ATTACHED_BUILDING_ID'] = (int)$resBuilding[0]['id_attachedbuilding'];
            $sql = "UPDATE ds_attachedbuilding
                    SET  
                        id_building = ".$cellData['id_building'].",
                        active = '".$cellData['active']."',
                        underconstruction = '".$cellData['underconstruction']."',                       
                        inactivation = '".$cellData['inactivation']."',
                        busy = '".$cellData['busy']."',
                        inupgrade = '".($cellData['inupgrade']=='t'?'t':'f')."',
                        starttime = ".$cellData['starttime'].",
                        endtime = ".$cellData['endtime']."
                    WHERE id_cell = ".$resCell[0]['id_cell'];       
            $res = self::$common->executeQuery ($sql);
            assert(empty(self::$common->errormsg));
        }else{

            $sql = "select nextval('public.ds_attachedbuilding_id_attachedbuilding_seq') as id";
            $res = self::$common->getResultArray ( "", $sql, "", "", "" );
            assert(empty(self::$common->errormsg) && count($res) == 1);
            $id = (int)$res[0]['id'];
            assert($id > 0);

            $out['ATTACHED_BUILDING_ID'] = $id;

            $sql = "INSERT INTO ds_attachedbuilding
                    (
                        id_attachedbuilding, 
                        id_comb, 
                        id_cell, 
                        id_building, 
                        underconstruction,
                        active,
                        inactivation,
                        busy,
                        inupgrade,
                        starttime,
                        endtime
                    )VALUES(
                        $id, 
                        $combID, 
                        ".$resCell[0]['id_cell'].", 
                        ".$cellData['id_building'].", 
                        '".$cellData['underconstruction']."',
                        '".$cellData['active']."',
                        '".$cellData['inactivation']."',
                        '".$cellData['busy']."',
                        '".$cellData['inupgrade']."',
                        ".$cellData['starttime'].",
                        ".$cellData['endtime']."
                    )"; 
            $res = self::$common->executeQuery ($sql);
            assert(empty(self::$common->errormsg));
        }

        return  $out;
    }


    static public function InsertNewArmy($combID, $UIID, $armyData)
    {
        assert((int)$combID > 0);
        assert((int)$UIID >= 0);
        assert(is_array($armyData));

        $id = self::GetNextValFromSeq('ds_unitcontainer_id_unitcontainer_seq');

        $sql = "INSERT INTO ds_unitcontainer
                (id_unitcontainer, id_basecomb, id_unitcontainertype, id_userinstance, moveable, inaction, unitcontainername, isdefault, id_currentcomb)
                VALUES
                (".$id.", ".$combID.", ".$armyData['id_unitcontainertype'].", ".$UIID.", '".$armyData['moveable']."', '".$armyData['inaction']."', '".$armyData['unitcontainername']."', '".$armyData['isdefault']."', null)";      
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg)); 

        return $id;
    }

    static public function InsertNewUnitsGroup($armyID, $groupData)
    {
        assert((int)$armyID > 0);
        assert(is_array($groupData));

        $id = self::GetNextValFromSeq('ds_unitcontainerhasunit_id_unitcontainerhasunit_seq');

        $sql = "INSERT INTO ds_unitcontainerhasunit
                (id_army, id_unit, amount, id_unitcontainerhasunit)
                VALUES
                ($armyID, ".$groupData['id_unit'].", ".$groupData['amount'].", $id)";       
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));

        return $id;
    }

    static public function GetCombAttributeIDBuTypeAndName($combID, $attributeTypeName, $attributeName)
    {
        assert((int)$combID > 0);
        assert(!empty($attributeTypeName));
        assert(!empty($attributeName));

        $sql = "
            SELECT 
                chs.id_combhasresource as id,
                chs.value
            FROM ds_combhasresource as chs
                INNER JOIN ds_attribute as dat ON (dat.id_attribute = chs.id_attribute)
                INNER JOIN ds_attributename as atn ON (atn.id_attributename = dat.id_attributename)
                INNER JOIN ds_attributetype as att ON (att.id_attributetype = dat.id_attributetype)
            WHERE
                chs.id_comb = $combID
                AND
                atn.attributename = '$attributeName'
                AND
                att.typename = '$attributeTypeName'";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(key_exists(0, $res))
        {
            return (int)$res[0]['id'];
        }else{
            return 0;
        }
    }

    static public function UpdateCombAttribute($attributeID, $value)
    {
        assert((int)$attributeID > 0);

        $sql = "update ds_combhasresource
                SET value = '".$value."'
                WHERE id_combhasresource = $attributeID";               
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));
    }

    static public function IsCombNameUnic($combName)
    {
        assert(!empty($combName));

        $sql = "SELECT * FROM ds_combname WHERE combname = '".$combName."'";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(count($res) > 0)
        {
            return false;
        }else{
            return true;
        }
    }

    static public function IsCombFree($combID)
    {
        assert((int)$combID > 0);

        $sql = "SELECT id_userinstance FROM ds_comb WHERE id_comb = '".$combID."'";

        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if((int)$res[0]['id_userinstance'] == 0)
        {
            return true;
        }else{
            return false;
        }
    }

    static public function GetArtIDByInstance($artefactInstanceID)
    {
        assert((int)$artefactInstanceID > 0);

        $sql = "SELECT * FROM ds_artefactinstance WHERE id_artefactinstance = '".$artefactInstanceID."'";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res[0]['id_artefact'];
    }

    static public function CreateArtInstance($id_artefact, $immortal, $birth_time, $death_time)
    {
        assert(is_int($id_artefact) && $id_artefact > 0);
        assert($immortal == 't' || $immortal == 'f');

        if(is_null($birth_time) || empty($birth_time))
        {
            $birth_time = "NULL";
        } else {
            $birth_time = "'$birth_time'";
        }

        if(is_null($death_time) || empty($death_time))
        {
            $death_time = "NULL";
        } else {
            $death_time = "'$death_time'";
        }

        $id = self::GetNextValFromSeq('ds_artefactinstance_id_artefactinstance_seq');
        assert($id > 0);

        $sql = "INSERT INTO ds_artefactinstance
                    (id_artefactinstance, id_artefact, immortal, birth_time, death_time)
                   VALUES 
                    ($id, $id_artefact, '$immortal', $birth_time, $death_time)";

        $res = self::$common->executeQuery ($sql);
        if (!empty(self::$common->errormsg)) self::$common->errormsg = '';

        return $id;
    }

    static public function InsertArtInToArchStore($combID, $artefactInstanceID)
    {
        assert((int)$artefactInstanceID > 0);
        assert((int)$combID > 0);

        $sql = "INSERT INTO ds_combhasartefact
                (id_comb, id_artefact)
                VALUES
                ($combID, $artefactInstanceID)";        
        $res = self::$common->executeQuery ($sql);
        if (!empty(self::$common->errormsg)) self::$common->errormsg = '';
    }

    static public function GetArtefactScope($artefactID)
    {
        assert((int)$artefactID > 0);

        $sql = "SELECT
                    ats.scopename 
                FROM 
                    ds_artefact as a                    
                    INNER JOIN ds_artefactscope as ats on (ats.id_artefactscope = a.id_artefactscope)
                WHERE 
                    a.id_artefact = '".$artefactID."'";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res[0]['scopename'];
    }

    static public function AttachArtefactToBuilding($artefactInstanceID, $attachedBuildingID)
    {
        assert((int)$artefactInstanceID > 0);
        assert((int)$attachedBuildingID > 0);

        $sql = "INSERT INTO ds_artefactattachedbuilding
                (id_attachedbuilding, id_artefact)
                VALUES
                ($attachedBuildingID, $artefactInstanceID)";        
        $res = self::$common->executeQuery ($sql);
        if (!empty(self::$common->errormsg)) self::$common->errormsg = '';
    }

    static public function AttachArtefactToComb($artefactInstanceID, $attachedBuildingID, $combID)
    {
        assert((int)$artefactInstanceID > 0);
        assert((int)$attachedBuildingID > 0);
        assert((int)$combID > 0);

        $sql = "INSERT INTO ds_artefactattachedcomb
                (id_attachedbuilding, id_artefact, id_comb)
                VALUES
                ($attachedBuildingID, $artefactInstanceID, $combID)";       
        $res = self::$common->executeQuery ($sql);
        if (!empty(self::$common->errormsg)) self::$common->errormsg = '';
    }

    static public function AttachArtefactToCombComplex($artefactInstanceID, $attachedBuildingID, $combComplexID)
    {
        assert((int)$artefactInstanceID > 0);
        assert((int)$attachedBuildingID > 0);
        assert((int)$combComplexID > 0);

        $sql = "insert into ds_artefactattachedcomplex
                ( id_combcomplex, id_artefact, id_attachedbuilding )
                values 
                ( $combComplexID, $artefactInstanceID, $attachedBuildingID )";      
        $res = self::$common->executeQuery ($sql);
        if (!empty(self::$common->errormsg)) self::$common->errormsg = '';
    }

    static public function TestSameRaceOfUserAndUnit($UIID, $unitID)
    {
        assert((int)$UIID > 0);
        assert((int)$unitID > 0);

        $sql = "
            SELECT
                ucbbr.id_unit
            FROM
                ds_userinstance AS ui
                INNER JOIN ds_unitcanbebuildbyrace AS ucbbr ON (ucbbr.id_race = ui.id_race OR ucbbr.id_race = 4)
            WHERE
                ui.id_userinstance = $UIID
                AND
                ucbbr.id_unit = $unitID
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        if(count($res) == 0)
        {
            return false;
        }

        return true;
    }


    static public function LogArtefactData(array $cellData, array $artefactData)
    {
        assert(key_exists('id_celltype', $cellData));
        assert(key_exists('cellnumber', $cellData));
        assert(key_exists('id_building', $cellData));
        assert(key_exists('id_comb', $cellData));

        foreach ($artefactData as $k => $art)
        {
            assert(is_array($art));
            assert(key_exists('id_artefact', $art));
            assert(key_exists('immortal', $art));
            assert(key_exists('birth_time', $art));
            assert(key_exists('death_time', $art));

            assert($art['immortal'] == 't' || $art['immortal'] == 'f');

            if(is_null($art['birth_time']) || empty($art['birth_time']))
            {
                $art['birth_time'] = "NULL";
            } else {
                $art['birth_time'] = "'".$art['birth_time']."'";
            }

            if(is_null($art['death_time']) || empty($art['death_time']))
            {
                $art['death_time'] = "NULL";
            } else {
                $art['death_time'] = "'".$art['death_time']."'";
            }
            
            if (empty(self::$logRecordId)) {
                return ;
            }

            $sql = "
                INSERT INTO ds_worldtransfer_artefact_log
                    (id_worldtransfer_log, id_artefact, id_celltype, cellnumber, id_building, id_comb, immortal, birth_time, death_time)
                VALUES
                    (".self::$logRecordId.", ".$art['id_artefact'].", ".$cellData['id_celltype'].", ".$cellData['cellnumber'].", ".$cellData['id_building'].", ".$cellData['id_comb'].", '".$art['immortal']."', ".$art['birth_time'].", ".$art['death_time'].")
                ";
            self::$common->executeQuery($sql);
            assert(empty(self::$common->errormsg));
        }
    }

    static public function AddLogRecord($userID, $worldID)
    {
        assert(is_int($userID) && $userID >= 0);
        assert(is_int($worldID) && $worldID >= 0);

        $id = (int)DS_Misc::getNextSeqVal(self::$common, 'ds_worldtransfer_log_id_worldtransfer_log_seq');

        $sql = "
            INSERT INTO ds_worldtransfer_log
                (id_worldtransfer_log, id_user, id_world)
            VALUES
                ($id, $userID, $worldID)
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));

        return $id;
    }

    static public function GetSpyEvents($userInstance)
    {
        assert(is_int($userInstance) && $userInstance >= 0);

        $sql = "
            SELECT 
                    eq.id_eventqueue 
                FROM 
                    ds_eventqueue eq
                LEFT JOIN ds_eventparticipant ep ON (eq.id_eventqueue = ep.id_eventqueue)
                WHERE 
                    ep.id_userinstance = ".$userInstance."
                    AND
                    (eq.id_event = 38 -- SPY_MOVE_TO_RESEARCH
                    OR eq.id_event = 39 --SPY_MOVE_FROM_RESEARCH
                    )
                    AND
                    NOT eq.istrash
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }
}


?>