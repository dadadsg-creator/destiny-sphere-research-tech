<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_RecieverWorld extends DS_Base 
{
    static public function GetWorldsForTransferFromRecieverWorld($fromDbId, $fromWorldId)
    {
        assert(is_int($fromDbId) && $fromDbId >= 0);
        assert(is_int($fromWorldId) && $fromWorldId >= 0);
        
        $common = self::getCommon();
        $common->setDbId(0);
        
        $sql = "
            SELECT
                wtwr.*,
                wc2.*
            FROM
                ds_worldtransfer_worlds_relation AS wtwr
                
                INNER JOIN ds_worldtransfer_worlds_list AS wtwl ON (wtwl.id_worldtransfer_world = wtwr.id_world_from)
                INNER JOIN ds_worldcollection AS wc ON (wtwl.world_identifier = wc.world_identifier)
                
                INNER JOIN ds_worldtransfer_worlds_list AS wtwl2 ON (wtwl2.id_worldtransfer_world = wtwr.id_world_to)
                INNER JOIN ds_worldcollection AS wc2 ON (wtwl2.world_identifier = wc2.world_identifier)
            WHERE
                wc.id_world = $fromWorldId
                AND
                wc.dbname = '".$GLOBALS['dbWorldAccess'][$fromDbId]['server']."'
            ORDER BY wtwr.priority DESC
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
    
    static public function GetTopRanking($count, $dbId, $worldId)
    {
        assert(is_int($count) && $count > 0);
        assert(is_int($dbId) && $dbId >= 0);
        assert(is_int($worldId) && $worldId > 0);
        
        $common = self::getCommon();
        $common->setDbId($dbId);
        
        $sql = "
            SELECT
                avg(fr.thefinal),
                fr.id_userinstance
            FROM
                ds_finalrankinghistory AS fr
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
                LEFT JOIN ds_user AS u ON (u.id_user = ui.id_user)
            WHERE
                ui.id_world = ".$worldId."
                AND
                ui.id_userinstance > 0
                AND
                (u.npc is null or u.npc=false)
            GROUP BY fr.id_userinstance
            ORDER BY avg(fr.thefinal) DESC
            LIMIT ".$count."
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetInstancesWithDaysAfterCreation($days, $dbId, $worldId)
    {
        assert(is_int($days) && $days > 0);
        assert(is_int($dbId) && $dbId >= 0);
        assert(is_int($worldId) && $worldId > 0);
        
        $common = self::getCommon();
        $common->setDbId($dbId);
        
        $sql = "
            SELECT
                id_userinstance
            FROM
                ds_userinstance ui
            LEFT JOIN ds_user AS u ON (u.id_user = ui.id_user)
            WHERE
                creation_date < NOW() - '".$days." DAY'::interval
                AND
                id_world = ".$worldId."
                AND
                id_userinstance > 0
                AND
                (u.npc is null or u.npc = false)
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetUsersWithAllowedTransferForDays($days, $worldId, $dbId)
    {
        assert(is_int($days) && $days > 0);
        assert(is_int($dbId) && $dbId >= 0);
        assert(is_int($worldId) && $worldId > 0);
        
        $common = self::getCommon();
        $common->setDbId($dbId);
        
        $sql = "
            SELECT
                ui.id_user,
                ui.id_userinstance,
                u.username
            FROM
                ds_worldtransfer_allowed_user AS wtau
                INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = wtau.id_userinstance)
                LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
            WHERE
                add_date < now() - '".$days." DAY'::interval
                AND
                transfer_required
                AND
                NOT transfer_done
                AND
                ui.id_world = ".$worldId."
                AND
                (u.npc is null or u.npc=false)
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function AllowTransferForUser($UIID, $worldID, $dbId, $required = false)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($dbId) && $dbId >= 0);
        assert(is_int($worldID) && $worldID > 0);
        
        assert(is_bool($required));
        
        if($required)
        {
            $r = 'true';
        } else {
            $r = 'false';
        }
        
        $common = self::getCommon();
        $common->setDbId($dbId);
        
        // Смотрим, нет ли уже этой записи
        $sql = "
            SELECT 
                *
            FROM
                ds_worldtransfer_allowed_user
            WHERE
                id_userinstance = $UIID
                AND 
                transfer_required = $r
                AND
                transfer_done = false
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(count($res) == 0)
        {
            $sql = "
            INSERT INTO ds_worldtransfer_allowed_user
                (id_userinstance, transfer_required, transfer_done)
            VALUES
                ($UIID, $r, false)
            ";
            $common->executeQuery($sql);
            assert(empty($common->errormsg));
        }
    }
    
    static public function RequireTransferForUser($UIID, $dbId)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($dbId) && $dbId >= 0);
        
        $common = self::getCommon();
        $common->setDbId($dbId);
        
        $sql = "
            UPDATE 
                ds_worldtransfer_allowed_user
            SET
                transfer_required = true
            WHERE
                id_userinstance $UIID
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function AddGameMessageToUser($UIID, $dbId, $subject, $text)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($dbId) && $dbId >= 0);
        assert(!empty($subject));
        assert(!empty($text));
        
        $common = self::getCommon();
        $common->setDbId($dbId);
        
        $sql = "select nextval('ds_message_id_message_seq') as id";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) > 0);
        
        $messageID = (int)$res[0]['id'];
        
        $sql = "
            INSERT INTO ds_message 
                (
                    id_message,
                    id_messagescope,
                    id_from_userinstance,
                    subject,
                    messagetext,
                    messagedate
                ) 
            VALUES 
                (
                    ".$messageID.",
                    10,
                    0,
                    E'".pg_escape_string(urldecode($subject))."',
                    E'".pg_escape_string(urldecode($text))."',
                    now() 
                )
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        $sql = "
            INSERT INTO ds_userhasmessage
                (id_userinstance, id_message)
            VALUES
                (".$UIID.", ".$messageID.")
            ";
    
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function CheckTransferForUser($UIID, $dbId)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($dbId) && $dbId >= 0);
        
        $common = self::getCommon();
        $common->setDbId($dbId);
        
        $sql = "
            SELECT
                id_userinstance,
                transfer_required,
                transfer_done
            FROM
                ds_worldtransfer_allowed_user
            WHERE
                id_userinstance = $UIID         
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            return $res[0];
        }
        
        return $res;
    }
    
    static public function AddToTransferList($id_user, $db_from, $id_world_from, $db_to, $id_world_to)
    {
        assert(is_int($id_user) && $id_user > 0);
        assert(is_int($id_world_from) && $id_world_from > 0);
        assert(is_int($id_world_to) && $id_world_to > 0);
        assert(is_int($db_from) && $db_from >= 0);
        assert(is_int($db_to) && $db_from >= 0);

        $common = self::getCommon();
        
        $common->setDbId(0);
        $common->startTrans();
        
        $sql = "
            INSERT INTO ds_worldtransfer
                (id_user, db_from, id_world_from, db_to, id_world_to, transfer_type, status)
            VALUES
                (".$id_user.", '".$GLOBALS['dbWorldAccess'][$db_from]['server']."', ".$id_world_from.", '".$GLOBALS['dbWorldAccess'][$db_to]['server']."', ".$id_world_to.", 5, 1)
            ";  
        $common->executeQuery($sql);
        assert(empty($common->errormsg));   
        
        $sql = "
            UPDATE
                ds_worldtransfer_allowed_user
            SET
                transfer_done = true
            WHERE
                id_userinstance = (
                    SELECT
                        id_userinstance
                    FROM
                        ds_userinstance
                    WHERE
                        id_user = ".$id_user."
                        AND
                        id_world = ".$id_world_from."
                )
            ";  
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        $common->setDbId(0);
        $common->closeTrans('COMMIT');
    }
    
    
    static public function DeleteUser($UIID, $dbId)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($dbId) && $dbId >= 0);
        
        $common = self::getCommon();
        $common->setDbId($dbId);
        
        $sql = "SET STATEMENT_TIMEOUT to 0; SELECT drop_uiid(".$UIID.")";
        
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        SU_DebugTools::LogAll($UIID."_".$dbId, 'AnnitaTesTransfer.log');
    }
    
}

?>