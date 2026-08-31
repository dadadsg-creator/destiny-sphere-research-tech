<?php

class SI_SearchFinalizeTransfer extends SI_Base
{
    static public function GetWorldIdentifier($worldID, $dbID)
    {
        assert(is_int($worldID) && $worldID > 0);
        assert(is_int($dbID) && $dbID >= 0);
        
        $common = self::GetCommon();
        
        $sql = "
            SELECT
                world_identifier
            FROM
                ds_worldcollection
            WHERE
                id_world = ".$worldID."
                AND
                dbname = '".$GLOBALS['dbWorldAccess'][$dbID]['server']."'
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res[0]['world_identifier'];
    }
    
    static public function AddVocation($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID > 0);
        
        $common = self::GetCommon();
        
        $sql = "
            INSERT INTO ds_vocationmode
                (startdate, enddate, id_userinstance, password)
            VALUES
                (now(), null, ".$userInstanceID.", 'pereezd_".rand()."')
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function CheckVocation($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID > 0);
        
        $common = self::GetCommon();
        $sql = "
            SELECT
                *
            FROM
                ds_vocationmode
            WHERE
                id_userinstance = ".$userInstanceID."
                AND
                password like 'pereezd%'
                AND
                enddate IS NULL
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        if(count($res) > 0)
        {
            return true;
        }
        
        return false;
    }
    
    static public function DisableManualTransfer($userID, $dbID, $worldID)
    {
        assert(is_int($worldID) && $worldID > 0);
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($userID) && $userID > 0);
            
        $common = self::GetCommon();
        
        $sql = "
            INSERT INTO ds_worldtransfer
                (id_user, db_from, id_world_from, transfer_type, status)
            VALUES
                (".$userID.", '".$GLOBALS['dbWorldAccess'][$dbID]['server']."', $worldID, 5, 3)
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function DisableAutoTransfer($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID > 0);

        $common = self::GetCommon();
            
        $sql = "
            INSERT INTO ds_worldtransfer_allowed_user
                (id_userinstance, transfer_required, transfer_done)
            VALUES
                ($userInstanceID, true,true)
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function GetUserArmySum($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID > 0);
        
        $common = self::GetCommon();
        
        $sql = "
            SELECT
                md5(ARRAY_TO_STRING
                (
                    ARRAY
                    (
                        SELECT
                            uchu.id_unit||'-'||sum(ceil(uchu.amount)) as sum
                        FROM
                            ds_userinstance AS ui
                            INNER JOIN ds_unitcontainer AS uc USING (id_userinstance)
                            INNER JOIN ds_unitcontainerhasunit AS uchu ON (uchu.id_army = uc.id_unitcontainer)
                        WHERE
                            ui.id_userinstance = $userInstanceID
                        GROUP BY uchu.id_unit
                        ORDER BY uchu.id_unit
                    ),
                    ','
                )) as hash
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res[0]['hash'];
    }
    
    static public function GetUserResourceSum($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID > 0);
        
        $common = self::GetCommon();
        
        $sql = "
            SELECT md5(
                ARRAY_TO_STRING
                (
                    ARRAY
                    (
                        SELECT
                            an.attributename||'='||round(cr.value::real)
                        FROM
                            ds_userinstance AS ui
                            INNER JOIN ds_comb AS c USING (id_userinstance)
                            INNER JOIN ds_combname AS cn USING (id_comb)
                            INNER JOIN ds_combhasresource AS cr USING (id_comb)
                            INNER JOIN ds_attribute AS a USING (id_attribute)
                            INNER JOIN ds_attributename AS an USING (id_attributename)
                        WHERE
                            ui.id_userinstance = $userInstanceID
                        ORDER BY cn.combname, an.attributename
                    ),
                    ','
                )
            ) as hash
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res[0]['hash'];
    }
    
    static public function GetUserArtefactSum($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID > 0);
        
        $common = self::GetCommon();
        
        $sql = "
            SELECT
                md5(ARRAY_TO_STRING
                (
                    ARRAY
                    (
                        SELECT
                            ai.id_artefact
                        FROM
                            ds_userinstance AS ui
                            INNER JOIN ds_comb AS c USING (id_userinstance)
                            INNER JOIN ds_combname AS cn USING (id_comb)
                            INNER JOIN ds_combhasartefact AS cha USING (id_comb)
                            INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
                        WHERE
                            ui.id_userinstance = $userInstanceID
                        ORDER BY cn.combname, ai.id_artefact
                    ),
                    ','
                )) as hash
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res[0]['hash'];
    }
    
    
    static public function GetUserBuildingSum($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID > 0);
        
        $common = self::GetCommon();
        
        $sql = "
            SELECT
                md5(ARRAY_TO_STRING
                (
                    ARRAY
                    (
                        SELECT
                            ab.id_building
                        FROM
                            ds_userinstance AS ui
                            INNER JOIN ds_comb AS c USING (id_userinstance)
                            INNER JOIN ds_combname AS cn USING (id_comb)
                            INNER JOIN ds_attachedbuilding AS ab USING (id_comb)
                        WHERE
                            ui.id_userinstance = $userInstanceID
                        ORDER BY cn.combname, ab.id_building
                    ),
                    ','
                )) as hash
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res[0]['hash'];
    }
    
    static public function UpdateTransferStatus($userID, $fromWorldID, $fromDBID, $toWorldID, $toDBID)
    {
        assert(is_int($userID) && $userID > 0);
        assert(is_int($fromWorldID) && $fromWorldID > 0);
        assert(is_int($fromDBID) && $fromDBID >= 0);
        assert(is_int($toWorldID) && $toWorldID > 0);
        assert(is_int($toDBID) && $toDBID >= 0);
        
        $common = self::GetCommon();
        
        $sql = "
            UPDATE 
                ds_worldtransfer 
            SET 
                status = 2
            WHERE
                id_user = ".$userID."
                AND
                db_from = '".$GLOBALS['dbWorldAccess'][$fromDBID]['server']."'
                AND
                id_world_from = ".$fromWorldID."
                AND
                db_to = '".$GLOBALS['dbWorldAccess'][$toDBID]['server']."'
                AND
                id_world_to = ".$toWorldID."
                AND
                status = 1
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function AddUserToCollection($userID, $userInstanceID, $dbID, $worldID)
    {
        assert(is_int($userID) && $userID > 0);
        assert(is_int($userInstanceID) && $userInstanceID > 0);
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID > 0);
        
        $common = self::GetCommon();
        
        $sql = "
            INSERT INTO ds_userinstancecollection
                (id_user, id_userinstance, id_world, dbname, admin, id_worldcollection)
            SELECT 
                $userID,
                $userInstanceID,
                $worldID,
                '".$GLOBALS['dbWorldAccess'][$dbID]['server']."',
                false,
                id_worldcollection
            FROM 
                ds_worldcollection                          
            WHERE 
                dbname = '".$GLOBALS['dbWorldAccess'][$dbID]['server']."'
                AND
                id_world = $worldID
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function MarkSuccessFinalizeStage($name, $logID)
    {
        $fields = array(
            'account_freezed', 'transfer_disabled', 
            'checksum_validated', 'added_to_delete_list', 
            'added_to_collection', 'transfer_status_updated'
        );
        
        assert(is_int($logID) && $logID > 0);
        assert(!empty($name));
        assert(in_array($name, $fields));
        
        $common = self::GetCommon();
        
        $sql = "
            UPDATE
                ds_worldtransfer_log
            SET
                $name = 't'
            WHERE
                id_worldtransfer_log = $logID
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
}

?>