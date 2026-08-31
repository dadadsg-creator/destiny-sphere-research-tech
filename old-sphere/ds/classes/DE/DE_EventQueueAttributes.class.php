<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_EventQueueAttributes extends DE_Base
{
    /**
     * Enter description here...
     *
     * @var array
     */
    public $attributes = array();
        
    /**
     * Enter description here...
     *
     * @var array
     */
    private $attributesAsArray = array();       
    
    function __construct($eventID, $data = '')
    {       
        assert(is_int($eventID) && $eventID>0);
        parent::__construct($eventID);
        
        if(!is_array($data))
        {
            $data = DS_Event::GetEventOptionsByEventQueueID($eventID);      
        }
        /*
        if (!is_array($data) || count($data) == 0) {
            SU_DebugTools::LogAll(DE_EventQueue::CreateFromID($eventID), 'EventQueueErrors.log');   
        }
        */        
        $this->_importAttributes($data);
    }
     
    /**
     * Enter description here...
     *
     * @param integer $eventID
     * @param array $data
     * @return DE_EventQueueAttributes
     */
    static public function CreateFromEventID($eventID, $data = '')
    {               
        assert(is_int($eventID) && $eventID>0);     
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $eventID, $data);                     
    }
    
    /**
     * Enter description here...
     *
     * @param integer $eventQueueID
     * @param string $eventName
     * @param array $options
     * @return DE_EventQueueAttributes
     */
    static public function CreateFromData($eventQueueID, $eventName, $options)
    {               
        assert(!empty($eventName));
        assert($eventQueueID>0);
        assert(is_array($options) && count($options)>=1);
        
        $eventOptions = DE_Event::CreateFromEventName($eventName)->getOptions();
        
        $data = array();
        $tmp = array();
        
        foreach($options as $optionName=>$optionValue)
        {
            $tmp['id_eventoption'] = $eventOptions->getIDByName($optionName);
            $tmp['value'] = $optionValue;
            $tmp['optionname'] = $optionName;
            $tmp['NOT_FROM_DB'] = true;
            $data[] = $tmp;
        }   
        
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $eventQueueID, $data);                        
    }
    
    private function _importAttributes($res)
    {
        foreach($res as $k=>$v)
        {           
            $this->attributes[$v['optionname']]['VALUE'] = $v['value'];
            $this->attributes[$v['optionname']]['IS_MODIFAED'] = false;
            $this->attributes[$v['optionname']]['ID'] = $v['id_eventoption'];               
            $this->attributesAsArray[$v['optionname']] = &$this->attributes[$v['optionname']]['VALUE']; 
            
            if(key_exists('NOT_FROM_DB', $v))
            {
                $this->needInsert = $v['NOT_FROM_DB'];
            }
        }
    }   
    
    public function getAttributesAsInternalArray()
    {                   
        return $this->attributes;
    }
    
    public function getAttributesAsArray()
    {               
        return $this->attributesAsArray;
    }   
    
    public function getAttribute($attributeName)
    {   
        // assert(key_exists($attributeName, $this->attributes));
        
        return $this->attributes[$attributeName]['VALUE'];
    }
    
    public function setAttribute($attributeName, $value)
    {                   
        if(!key_exists($attributeName, $this->attributes)) return;
        
        $this->attributes[$attributeName]['VALUE'] = $value;        
        $this->attributes[$attributeName]['IS_MODIFAED'] = true;

        $this->isModifyed = true;
    }   
    
    public function deltaAttribute($attributeName, $value)
    {                                       
        assert(key_exists($attributeName, $this->attributes));
        
        $this->attributes[$attributeName]['VALUE'] += $value;       
        $this->attributes[$attributeName]['IS_MODIFAED'] = true;

        $this->isModifyed = true;
    }   
            
    public function commit()
    {
        $this->_invariant();
        
        parent::commit();
        if($this->needInsert)
        {           
            if(!DE_EventQueue::CreateFromID($this->getID())->getIsTrash()->value)
            {
                DS_Event::InsertEventParams($this);
            }
        }elseif ($this->isModifyed){
            DS_Event::UpdateEventAttributes($this);
            $this->isModifyed = false;
        }       
    }
}//END OF CLASS

?>