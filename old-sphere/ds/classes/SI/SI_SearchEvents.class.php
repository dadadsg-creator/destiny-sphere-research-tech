<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_SearchEvents extends SI_Base 
{   
    static public function GetUserEvents($UIID)
    {
        assert((int)$UIID > 0);
        
        $sql = "SELECT
                    *,
                    (
                        SELECT id_attachedbuilding
                        FROM DS_AttachedBuildingBindEventQueue
                        WHERE ID_EventQueue = eq.id_eventqueue
                    ) AS attachebuilding,                   
                    (
                        SELECT id_comb
                        FROM DS_CombBindEventQueue
                        WHERE ID_EventQueue = eq.id_eventqueue
                    ) AS attachedcomb                   
                FROM
                    ds_eventqueue as eq
                    INNER JOIN ds_eventparticipant as ep on (ep.id_eventqueue = eq.id_eventqueue)
                    INNER JOIN ds_event as e on (e.id_event = eq.id_event)
                WHERE
                    ep.id_userinstance = ".$UIID."
                        AND
                    eq.istrash = false";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        return $res;
    }
    
    static public function GetIsEventAccelerated($id_eventqueue)
    {
        assert((int)$id_eventqueue > 0);
        
        $sql = "SELECT
                    *                   
                FROM
                    ds_eventacceleration
                WHERE
                    id_eventqueue = ".$id_eventqueue."";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        if (count($res) > 0) {
            return true;
        }
        
        return false;
    }
    
    static public function SetEventAcceleration($id_eventqueue)
    {
        assert((int)$id_eventqueue > 0);
        
        $sql = "INSERT INTO
                    ds_eventacceleration
                (id_eventqueue)
                VALUES                  
                (".$id_eventqueue.")";
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static public function GetEventOptions($eventqueueID)
    {
        assert((int)$eventqueueID > 0);
        
        $sql = "SELECT
                    *
                FROM                    
                    ds_eventhasoption as eho
                    INNER JOIN ds_eventoption as eo on (eo.id_eventoption = eho.id_eventoption)
                    INNER JOIN ds_eventqueue as eq on (eq.id_eventqueue = eho.id_eventqueue AND eq.id_event = eo.id_event)
                WHERE
                    eho.id_eventqueue = $eventqueueID";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        return $res;
    }
    
    static public function InsertEventQueue($eventData)
    {
        assert(is_array($eventData));
        
        $id = self::GetNextValFromSeq('ds_eventqueue_id_eventqueue_seq');       
        
        $sql = "INSERT INTO ds_eventqueue
                (id_eventqueue, id_event, eventstarttime, eventendtime, istrash)
                VALUES
                (".$id.", ".$eventData['id_event'].", '".$eventData['eventstarttime']."', '".$eventData['eventendtime']."' , false)";   
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg)); 

        return $id;
    }
    
    static public function InsertEventOption($eventqueueID, $optionID, $optionValue)
    {
        assert((int)$eventqueueID > 0);
        assert((int)$optionID > 0);     
                
        $sql = "INSERT INTO ds_eventhasoption
                (id_eventqueue, id_eventoption, value)
                VALUES
                (".$eventqueueID.", ".$optionID.",".(int)$optionValue.")";
        $res = self::$common->executeQuery ($sql);          
        assert(empty(self::$common->errormsg)); 
    }
    
    static public function MarkEventAsTrash($eventqueueID)
    {
        assert((int)$eventqueueID > 0);     
                
        $sql = "UPDATE ds_eventqueue
                SET istrash = true
                WHERE id_eventqueue = ".$eventqueueID;      
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg)); 
    }
    
    static public function DeleteArmyAction($armyID)
    {
        assert((int)$armyID > 0);   
        
        $sql = "DELETE FROM ds_unitcontaineraction              
                WHERE id_unitcontainer = ".$armyID;     
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));
    }
    
    static public function InsertEventParticipant($eventQueueID, $userInstanceID)
    {
        assert((int)$eventQueueID > 0); 
        assert((int)$userInstanceID > 0);   
        
        $sql = "INSERT INTO ds_eventparticipant
                (id_eventqueue, id_userinstance)                
                VALUES
                ($eventQueueID, $userInstanceID)";      
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));
    }
    
    static public function ReturnArmyToBaseComb($armyID)
    {
        assert((int)$armyID > 0);   
        
        $sql = "UPDATE ds_unitcontainer
                SET
                    id_currentcomb = null,
                    inaction = false
                WHERE id_unitcontainer = ".$armyID;     
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));
    }
    
    
    
    static public function AttachEventToBuilding($eventQueueID, $atachedBuildingID)
    {
        assert((int)$eventQueueID > 0); 
        assert((int)$atachedBuildingID > 0);    
        
        $sql = "INSERT INTO ds_attachedbuildingbindeventqueue
                (id_eventqueue, id_attachedbuilding)                
                VALUES
                ($eventQueueID, $atachedBuildingID)";       
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));
    }
    
    static public function AttachEventToComb($eventQueueID, $combID)
    {
        assert((int)$eventQueueID > 0); 
        assert((int)$combID > 0);   
        
        $sql = "INSERT INTO ds_combbindeventqueue
                (id_comb, id_eventqueue)                
                VALUES
                ($combID, $eventQueueID)";      
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));
    }
    
    static public function AttachUnitProduction($attachedBuildingID, $unitID, $startTime, $enTime, $amount)
    {
        assert((int)$attachedBuildingID > 0);   
        assert((int)$unitID > 0);   
        assert((int)$amount >= 0);          
        assert(!empty($startTime));
        assert(!empty($enTime));
        
        $sql = "INSERT INTO ds_unitinproduction
                (id_attachedbuilding, id_unit, starttime, endtime, amount)              
                VALUES
                ($attachedBuildingID, $unitID, '".$startTime."', '".$enTime."', $amount)";      
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));
    }
    
    static public function AttachArchiologyTeam($combID, $teamNumber, $cellTypeID, $startTime, $endTime, $amount, $eventQueueID, $size)
    {
        settype($combID, 'integer');
        assert($combID > 0);
        
        settype($teamNumber, 'integer');
        assert($teamNumber > 0);
        
        settype($cellTypeID, 'integer');
        assert($cellTypeID > 0);
        
        settype($amount, 'integer');
        assert($amount > 0);
        
        settype($eventQueueID, 'integer');
        assert($eventQueueID > 0);
        
        settype($size, 'integer');
        assert($size >= 0);
        
        assert(!empty($startTime));
        assert(!empty($endTime));
        
        $sql = "INSERT INTO ds_archeologicalteam
                (id_comb, teamnumber, id_celltype, starttime, endtime, amount, id_eventqueue, size)             
                VALUES
                ($combID, $teamNumber, $cellTypeID, '$startTime', '$endTime', $amount, $eventQueueID, $size)";      
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));
    }
    
    static public function AttachToTerraforming($cellID, $eventQueueID)
    {           
        settype($cellID, 'integer');
        assert($cellID > 0);
        
        settype($eventQueueID, 'integer');
        assert($eventQueueID > 0);
                        
        $sql = "INSERT INTO ds_terraformingbindeventqueue
                (id_cell, id_eventqueue)                
                VALUES
                ($cellID, $eventQueueID)";      
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));
    }
    
    static public function AttachEventToScience($UIID, $scienceID, $eventQueueID, $combID)
    {
        settype($UIID, 'integer');
        assert((int)$UIID > 0);
        
        settype($scienceID, 'integer');
        assert((int)$scienceID > 0);        
        
        settype($eventQueueID, 'integer');
        assert((int)$eventQueueID > 0);
        
        settype($combID, 'integer');
        assert((int)$combID > 0);
        
        $sql = "INSERT INTO ds_userhassciencebindeventqueue
                (id_science, id_userinstance, id_eventqueue, id_comb)
                VALUES
                ($scienceID, $UIID, $eventQueueID, $combID)";
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));     
    }   
    
    static public function GetTradeData($eventqueueID)
    {
        assert((int)$eventqueueID > 0);
        
        $sql = "SELECT
                    tr.*,
                    tc.categoryname
                FROM
                    ds_trade tr,
                    ds_tradecategory tc
                WHERE
                    tr.id_trade = (
                        SELECT id_trade
                        FROM ds_tradebindeventqueue
                        WHERE id_eventqueue = $eventqueueID) AND
                    tr.id_tradecategory = tc.id_tradecategory";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        // assert(empty(self::$common->errormsg) && count($res) > 0);
                
        return $res[0];
    }   
    
    static public function GetUnitAttributeByTapeAndName($unitID, $attributeType, $attributeName)
    {
        assert((int)$unitID > 0);
        assert(!empty($attributeType));
        assert(!empty($attributeName));
        
        $sql = "SELECT
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
                    u.id_unit = $unitID
                    AND
                    att.typename = '$attributeType'
                    AND
                    atn.attributename = '$attributeName'";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg) && count($res) > 0);
                
        return $res[0];
    }   
    
    static public function EndTransferEvents($UIID)
    {
        settype($UIID, 'integer');
        assert((int)$UIID > 0);
        
        $sql = "
            UPDATE 
                ds_eventqueue
            SET
                eventendtime = NOW() - '1 MINUTE'::interval
            WHERE
                id_eventqueue IN
                (
                    SELECT
                        ep.id_eventqueue
                    FROM
                        ds_eventparticipant AS ep
                        INNER JOIN ds_eventqueue AS eq USING (id_eventqueue)
                        INNER JOIN ds_event AS ev ON (ev.id_event = eq.id_event)
                    WHERE
                        ep.id_userinstance = $UIID
                        AND
                        ev.eventname IN ('TRANSFER_RESOURCE')
                )
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static public function EndTradeDeliveryEvents($UIID)
    {
        settype($UIID, 'integer');
        assert((int)$UIID > 0);
        
        $sql = "
            UPDATE 
                ds_eventqueue
            SET
                eventendtime = NOW() - '1 MINUTE'::interval
            WHERE
                id_eventqueue IN
                (
                    SELECT
                        ep.id_eventqueue
                    FROM
                        ds_eventparticipant AS ep
                        INNER JOIN ds_eventqueue AS eq USING (id_eventqueue)
                        INNER JOIN ds_event AS ev ON (ev.id_event = eq.id_event)
                    WHERE
                        ep.id_userinstance = $UIID
                        AND
                        ev.eventname IN ('TRADING_DELIVERY_GOOD', 'TRADING_DELIVERY_PAYMENT')
                )
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }
}

?>