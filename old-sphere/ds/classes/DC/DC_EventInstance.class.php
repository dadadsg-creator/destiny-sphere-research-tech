<?php $CVS_Version = '$Revision$ ; $Date$';
/** 
 * $Author$
 * $Date$
 * $Revision$
 * 
 * 
 * 
ЛЕХА!!!! Этот класс среди колекций стоит особничком, так что пока его не стоит приводить к общему виду. Это временная заитыка 

Да какая блин в жопу временная!!!! Это постоянно живущее порождение восполенного разума.
*/
class DC_EventInstance extends DE_Base
{           
    private $userInstance;
    
    private $endDates;
    
    private $events;
    
    private $counter = 0;   
    
    function __construct($userInstanceID)
    {       
        parent::__construct($userInstanceID);
        
        $this->userInstance = DE_UserInstance::CreateFromID($userInstanceID);

        
        $this->endDates = new GB_Array('integer', 'float');
        $this->events = new GB_Array('integer', 'DE_EventQueue');
        
        $rs = DS_Event::GetInstanceEvents($this->userInstance->getID());

        if(is_array($rs))
        {           
            foreach($rs as $k=>$v)
            {
                $id = (int)$v['id_eventqueue'];
                assert($id>0);
                    
                $eventQueue = DE_EventQueue::CreateFromID($id, $v);             
                
                $index = (int)$this->counter++;
                
                $this->endDates->addElement($index, (float)$eventQueue->getEndDate()->value);
                $this->events->addElement($index, $eventQueue);
            }
        }
    }
    
    static public function CreateFromUserInstanceID($id)
    {       
        //assert($id>0);                    
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }   
    
    public function addEvent($ID)
    {
        settype($ID, 'integer');
        assert($ID>0);  
                        
        $eventQueue = DE_EventQueue::CreateFromID($ID);
        
        // Жесткая затыка для колоний. 
        // Сыпалась ошибка в случае если в начале один игрок захватывал колнию, потом второй, при обновлении второго игрока
        // все из за того, что ивент второго боя надо перезаписывать от игрока у которого изначально отбирают колонию на игока, который отбирает 
        if (!$eventQueue->getEventParticipants()->isMember($this->userInstance)) {
            return ;
        }
        
        //првоеряем что бы в чужую коллекцию не добавляли лишние ивенты
        assert($eventQueue->getEventParticipants()->isMember($this->userInstance));
        
        $index = (int)$this->counter++;
        $this->endDates->addElement($index, (float)$eventQueue->getEndDate()->value);               
        $this->events->addElement($index, $eventQueue);     
        
        $this->endDates->sortByVals();
    }
    
    public function fetchNextEvent()
    {
        $key = $this->endDates->fetchNextKey();
        if(is_null($key))
        {
            $out =  false;
        }else{
            $out =  $this->events->getElement($key);            
        }
        return $out;
    }
}//END OF CLASS

?>