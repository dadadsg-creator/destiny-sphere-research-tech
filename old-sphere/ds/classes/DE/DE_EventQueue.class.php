<?php $CVS_Version = '$Revision$ ; $Date$';

/** 
 * Класс сужности ивента
 */

class DE_EventQueue extends DE_Base implements DE_EventQueueInterface
{
    /**
     * Enter description here...
     *
     * @var GB_Date
     */
    private $startTime;
    
    /**
     * Enter description here...
     *
     * @var GB_Date
     */
    private $endTime;
    
    /**
     * Enter description here...
     *
     * @var array
     */
    private $params = array();
    
    /**
     * Enter description here...
     *
     * @var DE_Event
     */
    private $event;     
    
    /**
     * Enter description here...
     *
     * @var EH_Event
     */
    private $handler;
    
    /**
     * Enter description here...
     *
     * @var boolean
     */
    private $isTrash;

    /**
     * Enter description here...
     *
     * @var boolean
     */
    private $lockedForHandl = false;
    
    /**
     * Enter description here...
     *
     * @var De_EventQueueAttributes
     */
    private $attributes;
    
    function __construct($id, $data = '')
    {       
        assert(is_int($id) && $id>0);
        
        parent::__construct($id);
        

        if(!is_array($data))
        {
            $data = DS_Event::GetEventQueueByID($id);
        }       
        assert(count($data)>0 && is_array($data));              
        
        assert ( key_exists ( 'eventstarttime', $data ) );              
        $this->startTime = GB_TypeFactory::CreateMaster('GB_Date', $data['eventstarttime']);;

        assert ( key_exists ( 'eventendtime', $data ) );                
        $this->endTime = GB_TypeFactory::CreateMaster('GB_Date', $data['eventendtime']);        

        assert ( key_exists ( 'id_eventqueue', $data ) );               
        $this->eventQueueID = (int)$data['id_eventqueue'];
                
        assert(key_exists('id_event', $data));              
        $this->event = DE_Event::CreateFromID((int)$data['id_event']);      
        
        assert ( key_exists ( 'istrash', $data ) );             
        $this->isTrash = GB_TypeFactory::CreateBase('GB_Boolean', $data['istrash']);    
        
        $this->_locking();
        
    }
        
    /**
     * Создание объектрна на снование его ID
     *
     * @param integer $id
     * @param array $data
     * @return DE_EventQueue
     */
    static public function CreateFromID($id, $data = '')
    {
        assert(is_int($id) && $id>0);       
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);                                  
    }
    
    /**
     * Enter description here...
     *
     * @param DE_Army $army
     * @return DE_EventQueue
     */
    static public function CreateFromArmy(DE_Army $army)
    {
        $id = $army->getBindEventID();      
        assert(is_int($id) && $id>=0);
        
        if($id == 0)
        {
            return DE_EventQueueNull::CreateFromID(100);
        }else{
            return DE_EventQueue::CreateFromID($id);
        }               
    }
        
    static public function CreateInDB ($eventName, $participants, $options, GB_DatePeriod $times)
    {
        assert(!empty($eventName));     
        assert(is_array($participants) && count($participants)>0);  
        assert(is_array($options) && count($options)>0);
                        
        $eventQueueID = DS_Event::InsertEventQueue($eventName, $times);
        assert($eventQueueID > 0);
        
        DS_Event::InsertEventParticiPants($eventQueueID, $participants);
        
        DE_EventQueueAttributes::CreateFromData($eventQueueID, $eventName, $options);       
        
        $data['eventstarttime'] = $times->getStartDate()->getPgFormat();
        $data['eventendtime'] = $times->getEndDate()->getPgFormat();
        $data['id_event'] = DS_Event::GetEventIDByName($eventName);
        $data['id_eventqueue'] = $eventQueueID;     
        $data['istrash'] = 'f';

        return self::CreateFromID($eventQueueID, $data);
    }
    
    private function _locking()
    {
        //Проверям можно ли полная обработка ивент
        if(!$this->event->getNeedCreationLock())
        {
            return;
        }
        
        //Лочить надо только в конечной фазе
        if($this->event->getLockOnlyInEnd() && (($this->endTime->value - time()) > constant('USERACTION_LOCK_TIME_OUT')))
        {
            return;
        }
        
        //Лочим ивент
        if(!SU_Locker::CerateForCurrentDB('EVENT_QUEUE')->lockWithTest($this->getID()))
        {
            $this->lockedForHandl = true;
            
            //Забираем на всякий случай с собой еще и атрибуты. А то на 
            //момент даже частичной боработки их может уже не оказаться.
            $this->attributes = DE_EventQueueAttributes::CreateFromEventID($this->getID());
        }               
    }
    
    public function isNull()
    {
        return false;
    }
    
    /**
     * Enter description here...
     *
     * @return DC_EventParticipants
     */
    public function getEventParticipants()
    {   
        return DC_EventParticipants::CreateFromEventID($this->getID());
    }
    
    public function getEventName()
    {
        return $this->event->getEventName();
    }
    
    /**
     * Enter description here...
     *
     * @return boolean
     */
    public function isLockedForHandl()
    {
        return $this->lockedForHandl;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_EventQueueAttributes
     */
    public function getAttributes()
    {   
        if(!($this->attributes instanceof DE_EventQueueAttributes))
        {
            $this->attributes = DE_EventQueueAttributes::CreateFromEventID($this->getID());
        }
        return $this->attributes;
    }
    
    public function getStartDate()
    {       
        return $this->startTime;
    }
    
    public function setStartDate(GB_Date $date)
    {   
        $this->startTime = $date;
        $this->isModifyed = true;
    }
    
    public function getEndDate()
    {   
        return $this->endTime;
    }
    
    public function setEndDate(GB_Date $date)
    {   
        $this->endTime = $date;
        $this->isModifyed = true;
    }
    
    public function getHandlerName()
    {       
        return $this->event->getHandlerName();
    }
    
    public function getHandler()
    {
        $this->_invariant();
        
        if(is_null($this->handler))
        {           
            $name = $this->getHandlerName();
            $this->handler = new $name;
        }
        
        return $this->handler;
    }
    
    public function attachToComb(DE_Comb $comb)
    {
        DS_Event::AttachEventToComb($this, $comb);
    }
    
    public function setIsTrash($value)
    {       
        assert(is_bool($value));
        
        //Если ивент залочин на оконеченое исполнение то нельхя его сносить     
        assert(!$this->lockedForHandl);
        
        $this->isTrash->value = $value;
        $this->isModifyed = true;
    }
    
    public function getIsTrash()
    {   
        return $this->isTrash;
    }
    
    public function delete()
    {       
        parent::delete();
        DS_Event::DeleteEvent($this);               
    }
    
    public function commit()
    {   
        parent::commit();
        
        
        if($this->isModifyed == true)
        {
            if(DS_Event::UpdateEvent($this))
            {
                $this->isModifyed = false;
            }           
        }
    }
}//END OF CLASS


?>