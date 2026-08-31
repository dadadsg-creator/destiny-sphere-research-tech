.<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Статтический класс бибилотека запросов связанная с армиеми.
 */
class DS_Army extends DS_Base
{

    /**
    * @return Возвращяет сведения по армии  
    * @param ID армии $armyId
    * @desc Возвращяет сведения по армии по ID
    */
    static function GetByID ( $armyID )
    {
        settype ($armyID, 'integer');
        assert ($armyID > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    uc.*
                FROM 
                    ds_unitcontainer as uc                  
                WHERE 
                    uc.id_unitcontainer = ".$armyID."
                    AND NOT
                    uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        if (count($res)>0)
        {
            return $res[0];
        }else {
            return false;
        }

    }//END OF FUNCTION

    static public function GetBindEventQueueIDByArmyID($armyID)
    {
        assert(is_int($armyID) && $armyID > 0);

        $common = self::getCommon();

        $sql = "SELECT
                    ucbeq.id_eventqueue
                FROM 
                    ds_unitcontainerbindeventqueue as ucbeq
                WHERE 
                    ucbeq.id_unitcontainer = ".$armyID;
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg) && (count($res) == 0 || count($res) == 1));

        if(count($res) == 0)
        {
            return 0;
        }else{
            return (int)$res[0]['id_eventqueue'];
        }
    }

    /////////////////////////////////////////////////////////////////////////////

    static function GetArmyUnitGroups($armyId, $sort = false)
    {
        settype ($armyId, 'integer');
        assert ($armyId > 0);

        // если используем сортировку
        if($sort)
        {
            $join = 'LEFT JOIN ds_unitcanbebuildbyrace AS ucbbr USING (id_unit)';
            $order = 'ORDER BY ucbbr.id_race, uchu.id_unit';
        } else {
            $join = '';
            $order = '';
        }

        $common = self::getCommon();
        $sql = "SELECT
                    uchu.*              
                FROM 
                    ds_unitcontainerhasunit AS uchu
                    ".$join."
                WHERE 
                    uchu.id_army = ".$armyId."
                ".$order."
                ";      
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        if(count($res)<0)
        {
            return false;
        }else{
            return $res;
        }
    }//END OF FUNCTION

    static public function GetUnitGroup($id)
    {
        settype($id, 'integer');
        assert($id>0);

        $common = self::getCommon();

        $sql = "SELECT *
                FROM ds_unitcontainerhasunit
                WHERE id_unitcontainerhasunit = ".$id;
        $res = $common->getResultArray ( "", $sql, "", "", "" );

        if(!(is_array($res) && empty($common->errormsg) && (count($res)>0)))
        {
            assert(empty($common->errormsg));
            assert(is_array($res));
            assert(count($res)>0);
        }else{
        }

        return $res[0];
    }//END OF METHOD

    static function DeleteUnitsGroupByID($id)
    {
        assert(!empty($id));

        $common = self::getCommon();
        $sql = "DELETE FROM ds_unitcontainerhasunit where id_unitcontainerhasunit = ".$id;
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
        return $res;
    }

    static public function GetNewIDForUnitsGroup()
    {
        $common = self::getCommon();

        $id = (int)self::getNextSeqVal($common, 'ds_unitcontainerhasunit_id_unitcontainerhasunit_seq');
        assert($id > 0);

        return $id;
    }

    static function CreateUnitsGroup(DE_Army $army, DE_Unit $unit, $amount, $id = null)
    {
        assert(is_float($amount) && $amount>0);
        assert(is_null($id) || (is_int($id) && $id > 0));

        $common = self::getCommon();

        if(is_null($id))
        {
            $id = self::GetNewIDForUnitsGroup();
        }

        $sql = "INSERT INTO ds_unitcontainerhasunit
                (id_unitcontainerhasunit, id_army, id_unit, amount)
                VALUES
                ($id, ".$army->getID().", ".$unit->getID().", ".$amount.")";
        $res = $common->executeQuery($sql);
        if(!empty($common->errormsg)) $common->errormsg="";

        return $id;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////

    static public function GetHomeArmyIDByCombID($combID)
    {
        settype ($combID, 'integer');
        assert ($combID > 0);

        $common = self::getCommon();
        $sql = "SELECT id_unitcontainer
                FROM ds_unitcontainer
                WHERE 
                        isdefault = true
                    AND id_basecomb = ".$combID;        
        $res = $common->getResultArray (self::FormMark(__METHOD__, $combID), $sql, "", "", "GLOBAL_CACHE" );
        assert(empty($common->errormsg));

        // assert(count($res) == 1);
        return (int)$res[0]['id_unitcontainer'];

    }

    /**
    * @return Список с ID армий на соте
    * @param ID соты $combID
    * @desc Список с ID армий на соте
    */
    static function GetArmysOnComb($combID)
    {
        settype ($combID, 'integer');
        assert ($combID > 0);

        $common = self::getCommon();

        $sql = "SELECT DISTINCT
                    uc.id_unitcontainer, uc.moveable                    
                FROM 
                    ds_unitcontainer AS uc 
                        INNER JOIN ds_unitorcontainertype AS uct ON (uc.id_unitcontainertype = uct.id_unitcontainertype)
                        LEFT JOIN ds_unitcontaineraction AS uca ON (uc.id_unitcontainer = uca.id_unitcontainer)
                        LEFT JOIN ds_unitcontaineractiontype AS ucat ON (uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype)
                WHERE                   
                    (
                        (uc.id_currentcomb = ".$combID." AND uc.inaction = 't' AND ucat.actiontypename = 'IN_DEFEND') 
                        OR 
                        (uc.id_basecomb = ".$combID."  AND uc.inaction = 'f')
                    )                   
                    AND uct.containertypename = 'ARMY'
                    AND NOT
                    uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")
                ORDER BY uc.moveable";          
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        $armysList = GB_SingletonObjectStorage::GetObjectsOfClass('DE_Army', true);


        foreach($armysList as $armyID=>$army)
        {
            $ownerArmyComBack = false;
            $comToDefend = false;

            if($army->getArmyAction()->isNull())
            {
                if($army->getBaseCombID() == $combID)
                {
                    $ownerArmyComBack = true;
                }
            }else{
                if(($army->getArmyAction()->getActionType() == 'IN_DEFEND') && $army->getCurentComb()->getID() == $combID)
                {
                    $comToDefend = true;
                }
            }


            $dontAlradyHave = true;
            foreach($res as $k=>$v)
            {
                if($v['id_unitcontainer'] == $army->getID())
                {
                    $dontAlradyHave = false;
                }
            }

            if(($ownerArmyComBack || $comToDefend) && $dontAlradyHave)
            {
                $res[] = array('id_unitcontainer' => $army->getID());
            }
        }

        return $res;
    }

    static function UpdateUnitsGroup(DE_UnitsGroup $unitsGroup)
    {
        $common = self::getCommon();
        $sql = "UPDATE ds_unitcontainerhasunit
                SET
                    amount = '".$unitsGroup->getAmount()."'
                WHERE id_unitcontainerhasunit = ".$unitsGroup->getID(); 

        $res = $common->executeQuery ( $sql );
        assert(empty($common->errormsg));

        return $res;
    }

    static function UpdateArmyData(DE_Army $army)
    {
        $common = self::getCommon();
        $sql = "UPDATE ds_unitcontainer
                SET
                    inaction = '".$army->getInAction('PG')."',
                    unitcontainername = '".$army->getName()."',
                    id_currentcomb = '".$army->getCurentComb()->getID()."',
                    id_basecomb = '".$army->getBaseCombID()."',
                    id_userinstance = '".$army->getUserInstance()->getID()."'
                WHERE id_unitcontainer = ".$army->getID();  
        $res = $common->executeQuery ( $sql );
        assert(empty($common->errormsg));

        if($army->getBindEventID() == 0)
        {
            $sql = "DELETE FROM ds_unitcontainerbindeventqueue
                    WHERE id_unitcontainer = ".$army->getID()."";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));
        }else{
            $sql = "SELECT *
                    FROM ds_unitcontainerbindeventqueue
                    WHERE id_unitcontainer = ".$army->getID()."";
            $res = $common->getResultArray ( "", $sql, "", "", "" );
            assert(empty($common->errormsg));

            if(count($res)>0)
            {
                $sql = "UPDATE ds_unitcontainerbindeventqueue
                    SET id_eventqueue = ".$army->getBindEventID()."
                    WHERE id_unitcontainer = ".$army->getID()."";
                $res = $common->executeQuery ( $sql );
                assert(empty($common->errormsg));
            }else{
                $sql = "INSERT INTO ds_unitcontainerbindeventqueue
                        (id_unitcontainer, id_eventqueue)
                        VALUES
                        (".$army->getID().", ".$army->getBindEventID().")";
                $res = $common->executeQuery ( $sql );
                if (!empty($common->errormsg)) $common->errormsg = '';
            }
        }

        return $res;
    }

    static function HaveArmyAction ($armyID)
    {
        settype ($armyID, 'integer');
        assert ($armyID > 0);

        $common = self::getCommon();

        $sql = "SELECT id_unitcontainer FROM ds_unitcontaineraction WHERE id_unitcontainer = ".$armyID;
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if (count($res)>0)
        {return true;} else {return false;}
    }

    static function GetArmyAction($armyID)
    {
        settype ($armyID, 'integer');
        assert ($armyID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT 
                    uca.id_unitcontaineractiontype,
                    uca.id_unitcontainer,
                    uca.id_startcomb,
                    uca.id_targetcomb,
                    uca.actionstarttime,
                    uca.actionendtime,
                    ucat.actiontypename
                FROM
                    ds_unitcontaineraction as uca
                    INNER JOIN ds_unitcontaineractiontype as ucat ON (uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype)
                WHERE
                    id_unitcontainer = ".$armyID;

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    static function InsertArmyAction($armyID, $combFromID, $combToID, GB_DatePeriod $datePeriod, $actionType)
    {
        assert(is_int($armyID) && $armyID > 0);
        assert(is_int($combFromID) && $combFromID > 0);
        assert(is_int($combToID) && $combToID > 0);

        assert(is_string($actionType)&& !empty($actionType));

        $common = self::getCommon();

        $actionTypes = ST_ArmyActionTypes::Create();

        $combFrom = DE_Comb::CreateFromID($combFromID);
        $combTo =  DE_Comb::CreateFromID($combToID);

        $sql = "INSERT INTO
                    ds_unitcontaineraction
                        (   id_unitcontainer, 
                            id_unitcontaineractiontype, 
                            id_startcomb, 
                            id_targetcomb, 
                            actionstarttime, 
                            actionendtime,
                            id_userinstance_from,
                            id_userinstance_to
                        )
                    VALUES 
                        ( 
                        ".$armyID.", 
                        ".$actionTypes->getActionTypeIDByName($actionType).", 
                        ".$combFromID.", 
                        ".$combToID.", 
                        '".$datePeriod->getStartDate()->getPgFormat()."',
                        '".$datePeriod->getEndDate()->getPgFormat()."',
                        ".$combFrom->getUserInstanceID().",
                        ".$combTo->getUserInstanceID()."
                        )";
        $res = $common->executeQuery ($sql);
        assert(empty($common->errormsg));

        return $res;
    }

    static function UpdateArmyAction(DE_Army $army, DE_Comb $combFrom, DE_Comb $combTo, GB_DatePeriod $datePeriod, $actionType)
    {
        assert(is_string($actionType)&& !empty($actionType));

        $common = self::getCommon();

        $sql = "UPDATE
                    ds_unitcontaineraction
                SET
                    id_unitcontaineractiontype = (select id_unitcontaineractiontype from ds_unitcontaineractiontype where actiontypename = '".$actionType."'),
                    id_startcomb = ".$combFrom->getID().",
                    id_targetcomb = ".$combTo->getID().", 
                    actionstarttime = '".$datePeriod->getStartDate()->getPgFormat()."',
                    actionendtime = '".$datePeriod->getEndDate()->getPgFormat()."'
                WHERE 
                    id_unitcontainer = ".$army->getID()."
        ";
        $res = $common->executeQuery ( $sql );
        assert(empty($common->errormsg));

        return $res;
    }

    static function DeleteArmyAction (DE_ArmyAction $action)
    {
        $common = self::getCommon();

        $sql = "DELETE FROM ds_unitcontaineraction
                WHERE id_unitcontainer = ".$action->getID()."";
        $res = $common->executeQuery ($sql );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function UpdateArmyAttribute ($armyHasAttributeID, $value)
    {
        //assert(is_float($value));
        assert($armyHasAttributeID>0);

        $common = self::getCommon();

        $sql = "
            UPDATE ds_unitcontainerhasattribute
            SET value = '".$value."'
            WHERE 
                id_unitcontainerhasattribute = ".$armyHasAttributeID;

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetArmyAtrributes ($armyID)
    {
        settype ( $armyID, 'integer' );
        assert ( $armyID > 0 );

        $common = self::getCommon();

        $sql = "
                SELECT 
                    ucha.id_unitcontainerhasattribute,
                    ucha.value,
                    an.attributename,
                    at.typename
                FROM
                    ds_attribute AS a
                    INNER JOIN ds_unitcontainerhasattribute AS ucha ON (a.id_attribute = ucha.id_attribute)
                    INNER JOIN ds_attributename AS an ON (a.id_attributename = an.id_attributename)
                    INNER JOIN ds_attributetype AS at ON (a.id_attributetype = at.id_attributetype)
                WHERE
                    ucha.id_unitcontainer = ".$armyID;
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $armyID), $sql, "", "", "" );
        assert(empty($common->errormsg));

        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }
    }//END OF METHOD

    static public function DeleteArmyAttributes($armyID)
    {
        settype ( $armyID, 'integer' );
        assert ( $armyID > 0 );

        $common = self::getCommon();

        $sql = " DELETE FROM ds_unitcontainerhasattribute WHERE id_unitcontainer = ".$armyID;

        $res = $common->getResultArray ( self::FormMark(__METHOD__, $armyID), $sql, "", "", "" );
        assert(empty($common->errormsg));

        return $res;
    }


    static public function GetNewIDForArmyAttribute()
    {
        $common = self::getCommon();

        $id = (int)self::getNextSeqVal($common, 'ds_unitcontainerhasattribute_id_unitcontainerhasattribute_seq');
        assert($id > 0);

        return $id;
    }

    static public function CreateArmyAttributes($attributeTypeName, $attributeName, $value, $armyID, $id = null)
    {
        assert ( is_string($attributeTypeName) && !empty($attributeTypeName));
        assert ( is_string($attributeName) && !empty($attributeName));
        assert ( $value >= 0);
        assert( is_int($armyID) && count($armyID)>0);
        assert(is_null($id) || (is_int($id) && $id > 0));

        $common = self::getCommon();

        if(is_null($id))
        {
            $id = self::GetNewIDForArmyAttribute();
        }

        $sql = "
                INSERT INTO 
                    ds_unitcontainerhasattribute 
                    (id_unitcontainerhasattribute, id_unitcontainer, value, id_attribute) 
                VALUES 
                    (
                        ".$id.",
                        ".$armyID.",
                        '".$value."',
                        (
                            SELECT 
                                a.id_attribute
                            FROM
                                public.ds_attribute a,
                                public.ds_attributename an,
                                public.ds_attributetype at
                            WHERE
                                (a.id_attributetype = at.id_attributetype) AND 
                                (a.id_attributename = an.id_attributename) AND 
                                (an.attributename = '".$attributeName."') AND 
                                (at.typename = '".$attributeTypeName."')
                        )
                    );
                ";
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));

        return $id;
    }//END OF METHOD



    static public function GetCombArmys ($combID)
    {
        assert (is_int($combID) && $combID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT 
                    uc.id_unitcontainer
                FROM
                    ds_unitcontainer uc,
                    ds_unitorcontainertype uct
                WHERE
                    uc.id_unitcontainertype = uct.id_unitcontainertype AND
                    (uc.id_basecomb = ".$combID.") AND
                    uct.containertypename = 'ARMY'
                    AND NOT
                    uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")
                ORDER BY uc.id_unitcontainer";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $combID), $sql, "", "", "");
        assert(empty($common->errormsg));

        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }
    }

    static public function GetArmysAttackOrDefendComb ($combID)
    {
        assert (is_int($combID) && $combID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT 
                    uc.id_unitcontainer
                FROM
                    ds_unitcontainer uc,
                    ds_unitorcontainertype uct,
                    ds_unitcontaineraction uca
                WHERE
                    uc.id_unitcontainertype = uct.id_unitcontainertype AND
                    uc.id_unitcontainer = uca.id_unitcontainer AND
                    (uca.id_targetcomb = ".$combID.") AND
                    uct.containertypename = 'ARMY'
                    AND NOT
                    uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")
                    ORDER BY uc.id_unitcontainer";

        $res = $common->getResultArray(self::FormMark(__METHOD__, $combID), $sql, "", "", "");
        assert(empty($common->errormsg));

        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }
    }

    static public function GetCombArmysOnDefend ($combID)
    {
        assert (is_int($combID) && $combID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    uc.id_unitcontainer
                FROM
                    ds_unitcontainer uc
                    JOIN ds_unitorcontainertype uct ON (uc.id_unitcontainertype = uct.id_unitcontainertype)
                    JOIN ds_unitcontaineraction uca ON (uc.id_unitcontainer = uca.id_unitcontainer)
                    JOIN ds_unitcontaineractiontype ucat ON (uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype)
                WHERE                   
                    uc.id_currentcomb = ".$combID." 
                    AND
                    uct.containertypename = 'ARMY' 
                    AND
                    ucat.actiontypename = 'IN_DEFEND'
                    AND NOT
                    uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")                  
                ORDER BY uc.id_unitcontainer";
        $res = $common->getResultArray ("", $sql);
        assert(empty($common->errormsg));

        $armysList = GB_SingletonObjectStorage::GetObjectsOfClass('DE_Army', true);

        // Добавляем армии, прилетевшие на защиту во время этого рефреша
        foreach($armysList as $armyID=>$army)
        {
            $ownerArmyComBack = false;
            $comToDefend = false;

            if($army->getArmyAction()->isNull())
            {
                if($army->getBaseCombID() == $combID)
                {
                    $ownerArmyComBack = true;
                }
            }else{
                if(($army->getArmyAction()->getActionType() == 'IN_DEFEND') && $army->getCurentComb()->getID() == $combID)
                {
                    $comToDefend = true;
                }
            }


            $dontAlradyHave = true;
            foreach($res as $k=>$v)
            {
                if($v['id_unitcontainer'] == $army->getID())
                {
                    $dontAlradyHave = false;
                }
            }

            if($comToDefend && $dontAlradyHave)
            {
                $res[] = array('id_unitcontainer' => $army->getID());
            }
        }

        if(sizeof($res)>0)
        {
            return $res;
        }else{
            return false;
        }
    }

    static public function GetAllArmyActionTypes($withCache = true)
    {
        $common = self::getCommon();

        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "";
        }

        $sql = "SELECT
                    id_unitcontaineractiontype,
                    actiontypename
                FROM ds_unitcontaineractiontype";
        $res = $common->getResultArray (self::FormMark(__METHOD__), $sql, "", "SHORT_PREFIX", $cacheType );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function DeleteArmyActionByArmyID($id)
    {
        assert (is_int($id) && $id > 0);
        $common = self::getCommon();

        $sql = "DELETE FROM ds_unitcontaineraction
                WHERE id_unitcontainer = ".$id;
        $res = $common->executeQuery ($sql);
        assert(empty($common->errormsg));

        return $res;
    }

    static public function DeleteArmyByID($id)
    {
        assert (is_int($id) && $id > 0);
        $common = self::getCommon();

        $sql = "DELETE FROM ds_unitcontainerbindeventqueue
                WHERE id_unitcontainer = ".$id;
        $res = $common->executeQuery ($sql);
        assert(empty($common->errormsg));

        $sql = "DELETE FROM ds_unitcontainer
                WHERE id_unitcontainer = ".$id;
        $res = $common->executeQuery ($sql);
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetUserInstanceArmys($UIID)
    {
        $common = self::getCommon();

        $sql = "SELECT
                    uc.*,
                    ucbeq.id_eventqueue
                FROM 
                    ds_unitcontainer as  uc
                    LEFT JOIN ds_unitcontainerbindeventqueue as ucbeq ON (uc.id_unitcontainer = ucbeq.id_unitcontainer)
                WHERE 
                    uc.id_userinstance = $UIID
                    AND
                    uc.id_unitcontainertype = 1
                    AND NOT
                    uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")";        
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetUnitsAmountInHomeArmyByCombAndUnit(DE_Comb $comb, $unitID)
    {
        assert(is_int($unitID) && $unitID > 0);

        $common = self::getCommon();

        $sql = "
            SELECT *
            FROM
                ds_unitcontainer AS uc
                INNER JOIN ds_unitcontainerhasunit AS uchu ON (uchu.id_army = uc.id_unitcontainer)
            WHERE
                uc.isdefault = true
                AND
                uc.id_basecomb = ".$comb->getID()."
                AND
                uchu.id_unit = $unitID";        
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));

        if(count($res) == 0)
        {
            return array();
        }else{
            return $res[0];
        }
    }

    static public function GetCountUserArmiesOnComb(DE_Comb $comb)
    {
        $common = self::getCommon();

        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_unitcontainer
            WHERE
                id_basecomb = ".$comb->getCombID()."
                AND
                id_unitcontainertype = 1
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static public function GetTotalCountUserArmies($uiid)
    {
        assert(is_int($uiid) && $uiid > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                count(*)
            FROM
                ds_unitcontainer
            WHERE
                id_userinstance = $uiid
                AND
                id_unitcontainertype = 1
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static public function GetCountUserArmiesInActionOnComb(DE_Comb $comb, $actions)
    {
        assert(is_array($actions));
        assert(count($actions) > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_unitcontainer AS uc
                INNER JOIN ds_unitcontaineraction AS uca ON (uca.id_unitcontainer = uc.id_unitcontainer)
                INNER JOIN ds_unitcontaineractiontype AS ucat ON (ucat.id_unitcontaineractiontype = uca.id_unitcontaineractiontype)
            WHERE
                uc.id_basecomb = ".$comb->getCombID()."
                AND
                ucat.actiontypename IN ('".implode("','", $actions)."')             
                AND NOT
                uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static public function GetCountArmiesInActionToComb(DE_Comb $comb, $actions, $timeInterval)
    {
        assert(is_array($actions));
        assert(count($actions) > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_unitcontainer AS uc
                INNER JOIN ds_unitcontaineraction AS uca ON (uca.id_unitcontainer = uc.id_unitcontainer)
                INNER JOIN ds_unitcontaineractiontype AS ucat ON (ucat.id_unitcontaineractiontype = uca.id_unitcontaineractiontype)
            WHERE
                uca.id_targetcomb = ".$comb->getCombID()."
                AND
                ucat.actiontypename IN ('".implode("','", $actions)."')             
                AND NOT
                uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")
                AND
                uca.actionstarttime > now() - '$timeInterval SECONDS'::interval
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static public function GetCountUserArmiesInActionToComb($uiid, DE_Comb $comb, $actions, $timeInterval)
    {
        assert(is_array($actions));
        assert(count($actions) > 0);
        assert(is_int($timeInterval) && $timeInterval > 0);
        assert(is_int($uiid) && $uiid > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_unitcontainer AS uc
                INNER JOIN ds_unitcontaineraction AS uca ON (uca.id_unitcontainer = uc.id_unitcontainer)
                INNER JOIN ds_unitcontaineractiontype AS ucat ON (ucat.id_unitcontaineractiontype = uca.id_unitcontaineractiontype)
            WHERE
                uca.id_targetcomb = ".$comb->getCombID()."
                AND
                uc.id_userinstance = ".$uiid."
                AND
                ucat.actiontypename IN ('".implode("','", $actions)."')             
                AND NOT
                uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")
                AND
                uca.actionstarttime > now() - '$timeInterval SECONDS'::interval
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static public function GetTimeNextArmiesInActionToComb($uiid, DE_Comb $comb, $actions, $timeInterval)
    {
        assert(is_array($actions));
        assert(count($actions) > 0);
        assert(is_int($timeInterval) && $timeInterval > 0);
        assert(is_int($uiid) && $uiid > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                actionstarttime
            FROM
                ds_unitcontainer AS uc
                INNER JOIN ds_unitcontaineraction AS uca ON (uca.id_unitcontainer = uc.id_unitcontainer)
                INNER JOIN ds_unitcontaineractiontype AS ucat ON (ucat.id_unitcontaineractiontype = uca.id_unitcontaineractiontype)
            WHERE
                uca.id_targetcomb = ".$comb->getCombID()."
                AND
                uc.id_userinstance = ".$uiid."
                AND
                ucat.actiontypename IN ('".implode("','", $actions)."')             
                AND NOT
                uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")
                AND
                uca.actionstarttime > now() - '$timeInterval SECONDS'::interval
                ORDER BY uca.actionstarttime
                LIMIT 1
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res[0]['actionstarttime'];
    }

    static public function GetCountUserArmiesInAction($uiid, $actions)
    {
        assert(is_array($actions));
        assert(count($actions) > 0);
        assert(is_int($uiid) && $uiid > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_unitcontainer AS uc
                INNER JOIN ds_unitcontaineraction AS uca ON (uca.id_unitcontainer = uc.id_unitcontainer)
                INNER JOIN ds_unitcontaineractiontype AS ucat ON (ucat.id_unitcontaineractiontype = uca.id_unitcontaineractiontype)
            WHERE
                uc.id_userinstance = $uiid
                AND
                ucat.actiontypename IN ('".implode("','", $actions)."')
                AND NOT
                uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    /**
     * Список всех действий армий которые участвую в крсной кнопке
     *
     */
    static public function GetArmyActionsListForRedButton()
    {
        $common = self::getCommon();

        $sql = "
            SELECT * 
            FROM ds_unitcontaineractiontype
            WHERE in_red_button = true  
            ";
        $res = $common->getResultArray (self::FormMark(__METHOD__, 1), $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        return $res;
    }


    static public function GetActionByName($actionName, $langID)
    {
        assert(is_string($actionName) && !empty($actionName));
        assert(is_int($langID) && $langID > 0);

        $common = self::getCommon();

        $sql = "
            select
                ucat.actiontypename,
                ltl.text as localizedtypename
            from
                ds_unitcontaineractiontype ucat,
                ds_localizedtextlanguage ltl
            where
                ucat.id_localizedtypename = ltl.id_localizedtext
                and 
                ltl.id_language = ".$langID."
                and 
                actiontypename = '".$actionName."'
            ";
        $res = $common->getResultArray(self::FormMark(__METHOD__, $actionName.'_'.$langID), $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        assert(is_array($res) && count($res) > 0);

        return $res[0];
    }

    static public function GetUserArmysOnDefend($userInstances)
    {
        assert(is_int($userInstances) && $userInstances > 0);

        $common = self::getCommon();

        $sql = "select
                    uc.id_unitcontainer
                from 
                    ds_unitcontainer uc,
                    ds_unitcontaineraction uca,
                    ds_unitcontaineractiontype ucat
                where 
                    uc.id_userinstance = $userInstances
                     and 
                    uc.id_unitcontainer = uca.id_unitcontainer
                     and 
                    uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype
                     and 
                    (ucat.actiontypename = 'IN_DEFEND' OR ucat.actiontypename = 'MOVE_TO_DEFEND')";

        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetArmyOnUserCombsDefend($userInstances)
    {
        assert(is_int($userInstances) && $userInstances > 0);

        $common = self::getCommon();

        $sql = "select
                    uc.id_unitcontainer
                from 
                    ds_unitcontainer uc,
                    ds_unitcontaineraction uca,
                    ds_unitcontaineractiontype ucat,
                    ds_comb co
                where 
                    uc.id_userinstance != $userInstances
                     and 
                    uc.id_unitcontainer = uca.id_unitcontainer
                     and 
                    uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype
                     and 
                    (ucat.actiontypename = 'IN_DEFEND' OR ucat.actiontypename = 'MOVE_TO_DEFEND')
                     and 
                    uca.id_targetcomb = co.id_comb
                     and 
                    co.id_userinstance = $userInstances";

        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetUserArmysMoveToAttack($userInstances)
    {
        assert(is_int($userInstances) && $userInstances > 0);

        $common = self::getCommon();

        $sql = "select
                    uc.id_unitcontainer
                from 
                    ds_unitcontainer uc,
                    ds_unitcontaineraction uca,
                    ds_unitcontaineractiontype ucat
                where 
                    uc.id_userinstance = $userInstances
                     and 
                    uc.id_unitcontainer = uca.id_unitcontainer
                     and 
                    uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype
                     and
                    ucat.actiontypename = 'MOVE_TO_ATTACK'";


        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetArmysMoveToAttackUser($userInstances)
    {
        assert(is_int($userInstances) && $userInstances > 0);

        $common = self::getCommon();

        $sql = "select
                    uc.id_unitcontainer
                from 
                    ds_unitcontainer uc,
                    ds_unitcontaineraction uca,
                    ds_unitcontaineractiontype ucat,
                    ds_comb co
                where 
                    uc.id_userinstance != $userInstances
                     and 
                    uc.id_unitcontainer = uca.id_unitcontainer
                     and 
                    uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype
                     and 
                    ucat.actiontypename = 'MOVE_TO_ATTACK'
                     and 
                    uca.id_targetcomb = co.id_comb
                     and 
                    co.id_userinstance = $userInstances";


        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        return $res;
    }

    /**
     * Список армий, которые атакуют соту
     *  используется я в логах шпионажа
     *
     * @see ME_SpyFormLog.class.php
     * @param int $combID
     * @param GB_Date $checkDate
     * @return array
     */
    static public function GetArmiesMoveToAttackComb($combID, GB_Date $checkDate)
    {
        assert(is_int($combID) && $combID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    uc.id_unitcontainer
                FROM
                    ds_unitcontainer AS uc
                    INNER JOIN ds_unitcontaineraction AS uca USING (id_unitcontainer)
                    INNER JOIN ds_unitcontaineractiontype AS ucat USING (id_unitcontaineractiontype)
                WHERE
                    ucat.actiontypename = 'MOVE_TO_ATTACK'
                    AND
                    uca.id_targetcomb = $combID
                    AND
                    uca.actionendtime > '".$checkDate->getPgFormat()."'
            ";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        return $res;
    }

    /**
     * Перезапись всех армий, которые находятся в каком-либо действии
     *  на домашнюю соту игрока
     *
     */
    static public function RewriteAllArmiesToHome($idCombFrom, $idCombTo, $userInctanceId, GB_Date $checkDate)
    {
        $common = self::getCommon();

        // Все армии с соты
        $combArmysCollection = DC_CombArmys::CreateFromCombID($idCombFrom);
        $combArmysCollection->resetNext();

        // Придумываем имя армии для возврата юнитов с захваченной колонии на домашку старого владельца
        $newArmyName = 'Colony'.$idCombFrom.$idCombTo;

        // Проверяем имя на уникальность
        $counter = 0;
        while(!self::IsArmyNameUnique($newArmyName))
        {
            $newArmyName = $newArmyName.GB_RandomPassword::GenerateOnlyNumbers(3);
            $counter ++;
            assert($counter < 10);
        }

        // создаем армию
        $sql = "
                INSERT INTO
                    ds_unitcontainer
                (id_basecomb, id_unitcontainertype, id_userinstance, moveable, inaction, unitcontainername, isdefault) 
                VALUES
                ($idCombTo, 1, ".DE_Comb::CreateFromID($idCombFrom)->getUserInstanceID().", true, false, '$newArmyName', false);
        ";
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));

        // получаем ее id
        $sql = "
                SELECT 
                    max(id_unitcontainer) as id_army
                FROM 
                    ds_unitcontainer
                WHERE 
                    id_userinstance = ".DE_Comb::CreateFromID($idCombFrom)->getUserInstanceID()."
                    AND 
                    isDefault = false";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));
        $idNewArmy = $res[0]['id_army'];
        assert($idNewArmy > 0);

        $homeArmy = DE_Army::CreateFromID($idNewArmy);
        $sendArmy = 0;

        while($army = $combArmysCollection->next())
        {
            $armyAction = $army->getArmyAction();

            // Если армии на соте
            if($armyAction->isNull())
            {
                // Расформировываем армии для высылки на домашку
                $unitGroupsCollection = $army->getUnitsGroupCollection();
                $unitGroupsCollection->resetNext();

                while($unitGroup = $unitGroupsCollection->next())
                {
                    // Флаг того, что юниты будет выслана на домашку
                    $sendArmy = 1;

                    $unit = $unitGroup->getUnit();
                    $amount = ceil($unitGroup->getAmount());

                    // Смотрим, есть ли в новой армии уже такие юниты
                    $homeUnitGroup = $homeArmy->getUnitsGroupCollection()->GetUnitGroupByUnit($unit);
                    if($homeUnitGroup === false)
                    {
                        // Создаем
                        $homeUnitGroup = DE_UnitsGroup::CreateInDB($homeArmy, $unit, $amount);
                        $homeArmy->getUnitsGroupCollection()->AddUnitGroup($homeUnitGroup);
                    } else {
                        // Плюсуем
                        $homeUnitGroup->deltaAmount($amount);
                    }
                    // Уничтожаем старый контейнер
                    $unitGroup->delete();

                    $unitGroupsCollection->resetNext();
                }

                //SU_DebugTools::LogAll('Переместили '.$amount.' юнитов '.$idUnit.' из армии '.$idArmy.' в армию '.$idNewArmy, 'AnnitaCapCol.log');

                // Если армия домашняя
                if ($army->getIsDefault()) {
                    // Домашнюю армию записываем на нового владельца
                    $army->setUserInstance($userInctanceId);
                } else {
                    // Уничтожаем старую армию , если это не домашка
                    $army->delete();
                    $combArmysCollection->resetNext();
                }
                continue;
            }

            switch ($armyAction->getActionType())
            {
                // Если армия летит в атаку или защиту
                case 'MOVE_TO_ATTACK':
                case 'MOVE_TO_DEFEND':
                    // Ивент действия
                    $armyEvent = DE_EventQueue::CreateFromArmy($army);
                    // Аттрибуты ивента
                    $eventAttr = $armyEvent->getAttributes();
                    // Меняем соту с которой летит в атрибутах ивента
                    $eventAttr->setAttribute('ID_CombFrom', $idCombTo);

                    // Дествие армии
                    $armyAction = $army->getArmyAction();
                    // Меняем соту с которой летит армия
                    $armyAction->setCombFrom($idCombTo);

                    break;

                case 'IN_DEFEND':
                    // Дествие армии
                    $armyAction = $army->getArmyAction();
                    // Меняем соту с которой летит армия
                    $armyAction->setCombFrom($idCombTo);
                    break;

                case 'MOVE_FROM':
                    // Ивент действия
                    $armyEvent = DE_EventQueue::CreateFromArmy($army);
                    // Аттрибуты ивента
                    $eventAttr = $armyEvent->getAttributes();
                    // Меняем соту на которую возвращается в атрибутах ивента
                    $eventAttr->setAttribute('ID_CombTo', $idCombTo);

                    // Дествие армии
                    $armyAction = $army->getArmyAction();
                    // Меняем соту с которой летит армия
                    $armyAction->setCombTo($idCombTo);
                    break;

                case 'MOVE_TO_COMB':
                    // Ивент действия
                    $armyEvent = DE_EventQueue::CreateFromArmy($army);
                    // Аттрибуты ивента
                    $eventAttr = $armyEvent->getAttributes();
                    // Меняем соту на которую возвращается в атрибутах ивента
                    $eventAttr->setAttribute('ID_CombTo', $idCombTo);
                    
                    // Меняем у армии соту, с которой она начала полет
                    // Дествие армии
                    $armyAction = $army->getArmyAction();
                    // Меняем соту с которой летит армия
                    $armyAction->setCombTo($idCombTo);
                    break;
                case 'IN_BATTLE':
                    // Ивент действия
                    $armyEvent = DE_EventQueue::CreateFromArmy($army);
                    // Аттрибуты ивента
                    $eventAttr = $armyEvent->getAttributes();
                    // Меняем соту на которую возвращается в атрибутах ивента
                    $eventAttr->setAttribute('ID_CombFrom', $idCombTo);

                    // Дествие армии
                    $armyAction = $army->getArmyAction();
                    // Меняем соту с которой летит армия
                    $armyAction->setCombFrom($idCombTo);
                    break;

                case 'HIDE_ARMY':
                    
                    // Смотрим юниты в армии
                    $unitGroupsCollection = $army->getUnitsGroupCollection();
                    $unitGroupsCollection->resetNext();

                    while($unitGroup = $unitGroupsCollection->next())
                    {
                        // Флаг того, что юниты будет выслана на домашку
                        $sendArmyToHome = 0;

                        $unitId = $unitGroup->getUnit()->getId();
                        
                        if ($unitId == 98 || $unitId == 99 || $unitId == 93) {
                            // Уничтожаем контейнер
                            $unitGroup->delete();
    
                            $unitGroupsCollection->resetNext();
                        } else {
                            // В армии есть юниты кроме квестовых - ее будем высылать на домашку
                            $sendArmyToHome = 1;
                        }                       
                    }

                //SU_DebugTools::LogAll('Переместили '.$amount.' юнитов '.$idUnit.' из армии '.$idArmy.' в армию '.$idNewArmy, 'AnnitaCapCol.log');

                    // Если армия надо выслать на домашку
                    if ($sendArmyToHome == 1) {
                        // Ивент действия
                        $armyEvent = DE_EventQueue::CreateFromArmy($army);
                        // Аттрибуты ивента
                        $eventAttr = $armyEvent->getAttributes();
                        // Меняем соту на которую возвращается в атрибутах ивента
                        $eventAttr->setAttribute('ID_CombTo', $idCombTo);
                        // Меняем соту с которой укрыта в атрибутах ивента
                        $eventAttr->setAttribute('ID_CombFrom', $idCombTo);
    
    
                        // Дествие армии
                        $armyAction = $army->getArmyAction();
                        // Меняем соту с которой летит армия
                        $armyAction->setCombFrom($idCombTo);
                        $armyAction->setCombTo($idCombTo);
                    } else {
                        // Убиваем ивент действия
                        $armyEvent = DE_EventQueue::CreateFromArmy($army);
                        $armyEvent->setIsTrash(true);
                        
                        // Уничтожаем армию
                        $army->delete();
                        $combArmysCollection->resetNext();
                    }
                    break;

                default:
                    continue;
            }

            // Меняем у армии базовую соту
            $army->setBaseComb(DE_Comb::CreateFromID($idCombTo));
        }
        
        if ($sendArmy == 1) {
            //Создаем ивент движения новой армии на домашнюю соту игрока
            $traveltime = GL_TravelTime::Count(DE_Comb::CreateFromID($idCombFrom), DE_Comb::CreateFromID($idCombTo), GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)DE_WorldSettings::CreateForCurentWorld()->get('MAX_ACCELERATION')))->value;
            $endtime = time() + $traveltime;
            $dateFrom = $checkDate;
            $dateTo = GB_TypeFactory::CreateBase('GB_Date', $endtime);

            $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);

            $eventParticipants = new GB_EventParticipants;
            $eventParticipants->setParticipant(DE_UserInstance::CreateFromID(DE_Comb::CreateFromID($idCombFrom)->getUserInstanceID()));


            $actionData = new GB_ArmyActionCreationData('MOVE_FROM');

            $actionData->setArmy($homeArmy);
            $actionData->setDatePeriod($datePeriod);
            $actionData->setParticipants($eventParticipants);
            $actionData->setPath(new GB_TravelPath(DE_Comb::CreateFromID($idCombFrom), DE_Comb::CreateFromID($idCombTo)));

            $eqid = ME_CreateArmyAction::CreateAction($actionData);

        } else {
            // Удаляем ранее созданную армию
            $homeArmy->delete();
        }
        
    }
    
    /**
     * Отменяем все армии, летящие на соту или в бою на этой соте
     *
     */
    static public function ReturnAllArmiesToComb($idCombFrom, $userInctanceId, GB_Date $checkDate)
    {
        $common = self::getCommon();
        
        // Все арми, летящие на эту соту (или в бою на этой соте)
        $sql = "
            SELECT
                uc.id_unitcontainer
            FROM
                ds_unitcontainer uc,
                ds_unitcontaineraction uca
            WHERE
                uc.id_unitcontainer = uca.id_unitcontainer
                AND uca.id_targetcomb = $idCombFrom
                AND uca.id_targetcomb != uc.id_basecomb
                AND uc.id_userinstance != $userInctanceId";

        $armiesAttTo = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        foreach ($armiesAttTo as $kTo => $dTo)
        {
            $idArm = (int)$dTo['id_unitcontainer'];
            assert($idArm > 0);

            // Если армия уже убита
            if (in_array($idArm, GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true))) {
                continue;
            }


            $army = DE_Army::CreateFromID($idArm);
            $actionType = $army->getArmyAction()->getActionType();

            // Если это защата или полет в защиту, отменяем действие
            if ($actionType == 'DEFEND' ||$actionType == 'IN_DEFEND' || $actionType == 'MOVE_TO_DEFEND') {
                ME_CancelDefend::Cancel($army, $checkDate);

                $messparams = array();
                $messparams[0] = "{b}".DE_Comb::CreateFromID($idCombFrom)->getFullName()."{/b}";
                $messparams[1] = "{b}".DE_UserInstance::CreateFromID($userInctanceId)->getUser()->getName()."{/b}";
                $messparams[2] = "{b}".$army->getName()."{/b}";

                // Колония % была захвачена игроком %. Защита армией % колонии прекращена.
                DE_SystemMessages::AddMessageFromMessageCompiler($checkDate, $army->getUserInstance()->getID(), 'CANCEL_DEFEND_CAPTURE_COLONY', $messparams, 1, 'RED');

                continue;
            }

            // Если это полет в атаку, отменяем действие
            if ($actionType == 'MOVE_TO_ATTACK') {
                ME_CancelAttack::Cancel($army, $checkDate);

                $messparams = array();
                $messparams[0] = "{b}".DE_Comb::CreateFromID($idCombFrom)->getFullName()."{/b}";
                $messparams[1] = "{b}".DE_UserInstance::CreateFromID($userInctanceId)->getUser()->getName()."{/b}";
                $messparams[2] = "{b}".$army->getName()."{/b}";

                // Колония % была захвачена игроком %. Атака армией % колонии прекращена.
                DE_SystemMessages::AddMessageFromMessageCompiler($checkDate, $army->getUserInstance()->getID(), 'CANCEL_ATTACK_CAPTURE_COLONY', $messparams, 1, 'RED');
            }

        }

        // Колонизационный модуль записываем на нового владельца
        $sql = "
                    UPDATE 
                        ds_unitcontainer
                    SET
                        id_userinstance = $userInctanceId
                    WHERE
                        id_unitcontainertype = 3
                        AND
                        id_basecomb = $idCombFrom";

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));

        return;
    }

    static public function ReturnAllBattles($idCombFrom, $idCombTo)
    {
        assert($idCombFrom > 0);
        assert($idCombTo > 0);
        
        $common = self::getCommon();

        // Перезаписываем все бои, с этой соты на новую
        $sql = "
            UPDATE
                ds_battle
            SET
                id_attackercomb = $idCombTo
            WHERE
                id_attackercomb = $idCombFrom
        ";
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
    }
    static public function IsArmyNameUnique($armyName)
    {
        assert(!empty($armyName));

        $common = self::getCommon();

        $sql = "
                SELECT 
                    id_unitcontainer
                FROM 
                    ds_unitcontainer
                WHERE 
                    upper(unitcontainername) = upper('$armyName')";

        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));
        if(count($res) > 0)
        {
            return false;
        }else{
            return true;
        }
    }

    static public function DeleteAllArmies($idIserInstance, $idComb)
    {
        assert(is_int($idIserInstance) && $idIserInstance >= 0);
        assert(is_int($idComb) && $idComb > 0);

        $common = self::getCommon();

        // Удаляем всех юнитов из армий на этой соте
        $sql = "
                DELETE 
                FROM ds_unitcontainerhasunit
                WHERE id_army
                IN
                (SELECT id_unitcontainer FROM ds_unitcontainer
                WHERE id_basecomb = $idComb)
                ";

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));

        // Удаляем все армии, кроме домашней
        $sql = "
                DELETE 
                FROM ds_unitcontainer
                WHERE id_basecomb = $idComb
                AND isdefault = 'f'

                ";

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));

        // Перезаписываем домашнюю армию на нового владельца
        $sql = "
                    UPDATE 
                        ds_unitcontainer
                    SET
                        id_userinstance = $idIserInstance
                    WHERE
                        id_basecomb = $idComb";

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));

    }

    /**
     * Перезапись всех зондов
     *  на домашнюю соту игрока
     *
     */
    static public function DecomposeZonds($idCombFrom, $idCombTo)
    {
        assert(is_int($idCombFrom) && $idCombFrom > 0);
        assert(is_int($idCombTo) && $idCombTo > 0);

        $common = self::getCommon();

        $comb = DE_Comb::CreateFromID($idCombFrom);
        $combAttributes = $comb->getAttributes();
        $combAttributesTo = DE_Comb::CreateFromID($idCombTo)->getAttributes();

        // Получаем все ивенты полетов зондов
        $sql = "
              SELECT * FROM ds_spy_group_action s
              WHERE s.id_comb_from = $idCombFrom
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        foreach ($res as $key => $data)
        {
            $eqid = (int)$data['id_eventqueue'];
            assert($eqid > 0);

            // Получаем атрибуты ивента
            $event = DE_EventQueue::CreateFromID($eqid);
            $eventAttributes = $event->getAttributes();
            $unit = DE_Unit::CreateFromID((int)$eventAttributes->getAttribute('ID_Unit'));
            $amount = (int)$eventAttributes->getAttribute('Amount');

            // Меняем количество зондов
            $combAttributes->deltaAttribute('AWAY_AMOUNT', $unit->getName(), -$amount);
            $combAttributes->deltaAttribute('CURRENT_AMOUNT', $unit->getName(), $amount);

            $event->setIsTrash(true);
            $event->commit();
        }

        // Разлагаем зонды
        $countScout = $combAttributes->getAttribute('CURRENT_AMOUNT', 'SPY_ZOND_SCOUT');

        // Ставим в 0 значение зондов на захваченной соте
        $combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SPY_ZOND_SCOUT', -$countScout);
        if ($countScout > 0) {
            $priceScout = GL_SpyCountZondPrice::Count($comb, DE_Unit::CreateFromName('SPY_ZOND_SCOUT'), (int)$countScout, 0);

            $unitAttributes = DE_UnitAttributes::CreateFromUnitID(83);
            $attributeNames = $unitAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');
            foreach ($attributeNames as $k=>$attributeName)
            {
                if ( $attributeName == 'CONSTRUCTION_TIME' || $attributeName == 'WORKER')
                {
                    continue;
                }

                assert(key_exists($attributeName, $priceScout));
                $value = round($priceScout[$attributeName]);

                $combAttributesTo->deltaAttribute('CURRENT_AMOUNT', $attributeName, $value);
            }
        }

        $countExplorer = $combAttributes->getAttribute('CURRENT_AMOUNT', 'SPY_ZOND_EXPLORER');
        // Ставим в 0 значение зондов на захваченной соте
        $combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SPY_ZOND_EXPLORER', -$countExplorer);
        if ($countExplorer > 0) {
            $priceExplorer = GL_SpyCountZondPrice::Count($comb, DE_Unit::CreateFromName('SPY_ZOND_EXPLORER'), (int)$countExplorer, 0);

            $unitAttributes = DE_UnitAttributes::CreateFromUnitID(88);
            $attributeNames = $unitAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');
            foreach ($attributeNames as $k=>$attributeName)
            {
                if ( $attributeName == 'CONSTRUCTION_TIME' || $attributeName == 'WORKER')
                {
                    continue;
                }

                assert(key_exists($attributeName, $priceExplorer));
                $value = round($priceExplorer[$attributeName]);

                $combAttributesTo->deltaAttribute('CURRENT_AMOUNT', $attributeName, $value);
            }

            // Добавляем военный опыт
            $battleXPPrice = (float)DE_WorldSettings::CreateForCurentWorld()->get('SPY_ZOND_EXPLORER_BATTLE_XP_COST');
            $battleXP = DE_BattleXP::CreateFromID($comb->getUserInstanceID());
            $battleXP->deltaXP($battleXPPrice * $countExplorer);
        }
    }

    /**
     * Перезапись всех зондов
     *  на домашнюю соту игрока
     *
     */
    static public function IsArmyHasQuetsUnit($idArmy)
    {
        assert(is_int($idArmy) && $idArmy > 0);

        $common = self::getCommon();

        // Получаем все ивенты полетов зондов
        $sql = "
            SELECT * 
            FROM ds_unitcontainerhasunit
            WHERE id_army = $idArmy
            AND id_unit IN (98, 99, 93);
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if (count($res) > 0) {
            return true;
        }
        return false;

    }

    /**
     * Удаляем юнита из всех армий игрока, 
     *  базирующихся на данной соте
     *
     */
    static public function DeleteAllQuestUnitOnComb($idComb)
    {
        assert(is_int($idComb) && $idComb > 0);

        $common = self::getCommon();

        // Выбираем армию, где есть этот юнит
        $sql = "
                SELECT 
                    u.id_unitcontainer, 
                    uc.id_unitcontainerhasunit,
                    u.isdefault
                FROM 
                    ds_unitcontainerhasunit uc,
                    ds_unitcontainer u
                WHERE 
                    uc.id_unit IN (98, 99, 93)
                    AND uc.id_army = u.id_unitcontainer
                    AND u.id_basecomb = $idComb
                ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        // Если такой юнить есть в армии
        if (count($res) > 0) {
            $idArm = (int)$res[0]['id_unitcontainer'];
            assert($idArm > 0);
            $idUchu = (int)$res[0]['id_unitcontainerhasunit'];
            assert($idUchu > 0);
            $idDef = $res[0]['isdefault'];
            assert($idDef == 't' || $idDef == 'f');

            // Удаляем его из армии
            $sql = "
                DELETE 
                FROM 
                    ds_unitcontainerhasunit
                WHERE 
                    id_unitcontainerhasunit = $idUchu
                ";
            $res = $common->executeQuery($sql);
            assert(empty($common->errormsg));

            // Смотрим, остались ли в этой армии еще юниты
            $sql = "
                    SELECT 
                        *
                    FROM 
                        ds_unitcontainerhasunit
                    WHERE
                        id_army = $idArm";

            $res = $common->getResultArray("", $sql, "", "", "");
            assert(empty($common->errormsg));

            // Если в армии нет больше юнитов, и это не домашняяя армия
            if (count($res) == 0 && $idDef == 'f') {
                // Удаляем армию
                $sql = "
                    DELETE 
                    FROM 
                        ds_unitcontainer
                    WHERE
                        id_unitcontainer = $idArm";
                $res = $common->executeQuery($sql);
                assert(empty($common->errormsg));
            }
        }
    }

}//END OF CLASS


?>