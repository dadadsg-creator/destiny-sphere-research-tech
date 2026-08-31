<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Event extends DS_Base
{
    /**
    * @return Массив срезультатом выборки или false
    * @param $uiid - инстанс пользователя
    * @ Возвращает список ивентов в которох участвует пользователь
    */
    static public function GetInstanceEvents($uiid)
    {
        
        settype($uiid, 'integer');
        $common = self::getCommon();
        
        $sql = "SELECT DISTINCT
                    eq.eventstarttime,
                    eq.eventendtime,
                    eq.id_eventqueue,                   
                    eq.id_event,
                    eq.istrash                  
                FROM
                    ds_eventqueue AS eq
                    INNER JOIN ds_eventparticipant AS ep ON (eq.id_eventqueue = ep.id_eventqueue)
                WHERE
                    ep.id_userinstance = ".$uiid." AND
                    eq.istrash = false
                ORDER BY eq.eventendtime";
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        if(!$res)
        {           
            return false;
        }else{
            return $res;
        }
    }//END OF METHOD
    
    static public function GetEventQueueByID($ID)
    {
        settype($ID, 'integer');
        assert($ID>0);  
        
        $common = self::getCommon();
        
        $sql = "SELECT
                    eq.eventstarttime,
                    eq.eventendtime,
                    eq.id_eventqueue,   
                    eq.id_event,                
                    eq.istrash                  
                FROM
                    ds_eventqueue eq                    
                WHERE               
                    eq.id_eventqueue = ".$ID."                  
                ORDER BY eq.eventendtime";
        
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(!$res)
        {           
            return false;
        }else{
            return $res[0];
        }
    }
    
    static public function InsertEventQueue($eventName, GB_DatePeriod $times)
    {
        assert(!empty($eventName));     
        
        $common = self::getCommon();
        
        $sql = "SELECT nextval('public.ds_eventqueue_id_eventqueue_seq'::text) AS ID";
        $ehres = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        $ID = (int)$ehres[0]['id'];

        $eventID = (int)self::GetEventIDByName($eventName);
        assert($eventID > 0);
        
        $sql = "INSERT INTO ds_eventqueue 
                (
                    id_eventqueue, 
                    id_event, 
                    eventstarttime, 
                    eventendtime, 
                    istrash
                ) 
                VALUES
                (
                    ".$ID.", 
                    ".$eventID.", 
                    '".$times->getStartDate()->getPgFormat()."', 
                    '".$times->getEndDate()->getPgFormat()."', 
                    false
                );";        
        $common->executeQuery($sql);
        if (!empty($common->errormsg)) echo $common->errormsg;
        assert(empty($common->errormsg));
        
        return $ID;     
    }
    
    static public function InsertEventParticiPants($eventQueueID, $userInstances)
    {
        assert(is_array($userInstances) && count($userInstances)>=1);
        assert(is_int($eventQueueID) && $eventQueueID>0);
                        
        $common = self::getCommon();
        
        $sqls = array();
        
        foreach($userInstances as $k=>$userInstanceID)
        {
            settype($userInstanceID, 'integer');
            // Закомментировано для захватываемых колоний
            // assert($userInstanceID > 0);
            $sqls[] = " INSERT INTO 
                        ds_eventparticipant 
                        (id_eventqueue, id_userinstance ) 
                            VALUES 
                        (".$eventQueueID.", ".$userInstanceID.")";
        }
                
        $common->executeQuery($sqls);
        assert(empty($common->errormsg));       
    }
    
    static public function InsertEventParams (DE_EventQueueAttributes $eventAttributes)
    {                   
        $common = self::getCommon();
        
        $sqls = array();
        
        $options = $eventAttributes->getAttributesAsInternalArray();
        
        foreach($options as $paramName => $v)
        {
            assert(!empty($paramName));
            $eventQueue = $eventAttributes->getID();
            $eventOption = $v['ID'];
            $value = number_format($v['VALUE'], 0, '.', '');
            $sqls[] = "insert into DS_EventHasOption 
                (ID_EventQueue, ID_EventOption, Value)
                select $eventQueue, $eventOption, $value
                where not exists
                (select 1 from DS_EventHasOption where ID_EventQueue=$eventQueue and ID_EventOption=$eventOption)";
        }
        $common->executeQuery($sqls);
        if(!empty($common->errormsg)) $common->errormsg='';
    }
    
    static public function GetEventParticipantsByEventID($eventID)
    {
        assert(is_int($eventID) && $eventID>0);
        $common = self::getCommon();
        
        $sql = "SELECT id_userinstance 
                FROM ds_eventparticipant
                WHERE id_eventqueue = ".$eventID;
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));       
        
        return $res;
    }
    
    static public function GetEventOptionsByEventQueueID($eventID)
    {
        assert(is_int($eventID) && $eventID>0);
        $common = self::getCommon();
        
        $sql = "
                SELECT * FROM ds_eventoption eo, ds_eventhasoption eho, ds_eventqueue eq
                    where eo.id_event = eq.id_event
                    AND eo.id_eventoption = eho.id_eventoption
                    AND eho.id_eventqueue = eq.id_eventqueue
                    AND eq.id_eventqueue = ".$eventID;
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
                
        return $res;
    }
    
    static public function GetEventIDByAttributeAndValue($attributeName, $value)
    {
        assert(!empty($attributeName));     
        $common = self::getCommon();
        
        $sql = "
        SELECT 
            public.ds_eventqueue.id_eventqueue
        FROM
            public.ds_eventoption
                INNER JOIN public.ds_event ON (public.ds_eventoption.id_event = public.ds_event.id_event)
                INNER JOIN public.ds_eventqueue ON (public.ds_event.id_event = public.ds_eventqueue.id_event)
                INNER JOIN public.ds_eventhasoption ON (public.ds_eventqueue.id_eventqueue = public.ds_eventhasoption.id_eventqueue)
        WHERE
            (public.ds_eventoption.optionname = '".$attributeName."') AND 
            (public.ds_eventhasoption.value = ".$value.")
            AND istrash = 'f'";
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));       
        
        return $res;
    }
    
    static public function UpdateEvent(DE_EventQueue $event)
    {           
        $common = self::getCommon();
        
        $sql = "UPDATE ds_eventqueue
                SET 
                    istrash = '".$event->getIsTrash()->getPgFormat()."', 
                    eventstarttime = '".$event->getStartDate()->getPgFormat()."', 
                    eventendtime = '".$event->getEndDate()->getPgFormat()."'
                WHERE id_eventqueue = ".$event->getID()."";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));   

        return true;
    }
    
    static public function DeleteEvent(DE_EventQueue $event)
    {           
        $common = self::getCommon();
        
        $sql = "UPDATE ds_eventqueue
                SET istrash = 't'
                WHERE id_eventqueue = ".$event->getID()."";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));   

        return true;
    }

    static public function UpdateEventAttributes(DE_EventQueueAttributes $eventAttributes)
    {                   
        $common = self::getCommon();

        $sqls = array();
                
        foreach($eventAttributes->attributes as $k=>$v)
        {

            if($v['IS_MODIFAED'] == false)
            {
                continue;
            };
            
            // assert($v['ID']>0);

            $eventAttributes->attributes[$k]['IS_MODIFAED'] = false;

            $sqls[] = "
                UPDATE ds_eventhasoption
                SET value = '".$v['VALUE']."'
                WHERE 
                    id_eventoption = ".$v['ID']."
                    AND id_eventqueue = ".$eventAttributes->getID();                                    
        }

        if(count($sqls)>0)
        {
            $res = $common->executeQuery($sqls);
            assert(empty($common->errormsg));
        }
        
        return true;
    }   
        
    static public function CleanEventTailsGlobal($eventID)
    {
        return true;
        
        assert(is_int($eventID) && $eventID>0);
             
        $common = self::getCommon();

        //ВСе это перенесено на каскады + когда ставишь у ивента треш триггером удаляеться из БД
        $sql = "delete from DS_AttachedBuildingBindEventQueue where ID_EventQueue = ".$eventID;     
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
            
        $sql = "delete from DS_UserHasScienceBindEventQueue where ID_EventQueue = ".$eventID;
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        $sql = "delete from DS_CombBindEventQueue where ID_EventQueue = ".$eventID;
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
                    
        return true; 
    }
    
    static public function CleanEventTailsUnitContainer($eventID)
    {
        return true;
        
        assert(is_int($eventID) && $eventID>0);
             
        $common = self::getCommon();    
        
        $sql = "delete from DS_UnitContainerBindEventQueue where ID_EventQueue = ".$eventID;
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
                        
        return true; 
    }
    
    
    static public function GetEventDataByID($id)
    {
        assert(is_int($id) && $id > 0);
        
        $common = self::getCommon();        
        
        $sql = "
            SELECT 
                *
            FROM
                ds_event as e               
            WHERE
                e.id_event = $id";      
        $res = $common->getResultArray(self::FormMark(__METHOD__, $id), $sql, "", "SHORT_PREFIX", 'GLOBAL_CACHE');
        assert(empty($common->errormsg) || count($res) == 1);       
                
        return $res[0];
    }   
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////
    
    static public function GetEventIDByName($eventName)
    {
        assert(is_string($eventName) && !empty($eventName));
        
        $common = self::getCommon();        
        
        $sql = "
            SELECT 
                id_event
            FROM
                ds_event
            WHERE
                eventname = '$eventName'";      
        $res = $common->getResultArray(self::FormMark(__METHOD__, $eventName), $sql, "", "SHORT_PREFIX", 'GLOBAL_CACHE');
        assert(empty($common->errormsg) || count($res) == 1);       
        
        return (int)$res[0]['id_event'];
    }
    
    static public function GetEventOptionsByEventID($eventID)
    {
        assert(is_int($eventID) && $eventID > 0);
        
        $common = self::getCommon();        
        
        $sql = "SELECT * FROM ds_eventoption WHERE id_event = $eventID";        
        $res = $common->getResultArray(self::FormMark(__METHOD__, $eventID), $sql, "", "SHORT_PREFIX", 'GLOBAL_CACHE');
        assert(empty($common->errormsg) || count($res) > 0);        
        
        return $res;
    }
    
    static public function GetAllEventsAndOptions($withCache = true)
    {
        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "";
        }
        
        $sql = "
            SELECT 
                e.id_event,
                e.eventname,
                eo.id_eventoption,
                eo.optionname,
                eh.eventhandlerclass
            FROM
                ds_event as e
                INNER JOIN ds_eventoption AS eo ON (e.id_event = eo.id_event)
                INNER JOIN ds_eventhandler AS eh ON (eh.id_eventhandler = e.id_eventhandler)";
        
        $res = $common->getResultArray ( self::FormMark(__METHOD__), $sql, "", "SHORT_PREFIX", $cacheType );
        assert(empty($common->errormsg));       
        
        assert(count($res)>0);
        
        return $res;
    }   
    
    /**
     * Присодеинить евент к соте
     *
     * @param DE_Event $event
     * @param DE_Comb $comb
     */
    static public function AttachEventToComb(DE_EventQueue $event, DE_Comb $comb)
    {
        $sql = "insert into DS_CombBindEventQueue (ID_EventQueue, ID_Comb) values (".$event->getID().", ".$comb->getCombID().")";
        $common = self::getCommon();
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    /**
     * Присодеинить евент к соте
     *
     * @param DE_Event $event
     * @param DE_Comb $comb
     */
    static public function UpdateEventParticiPants($eventQueueID, $oldParticipant, $newParticipant)
    {
        $common = self::getCommon();
        $sql = "
                    UPDATE ds_eventparticipant
                    SET id_userinstance = $newParticipant 
                    WHERE 
                    id_userinstance = ".$oldParticipant."
                    AND id_eventqueue = $eventQueueID";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
}

?>