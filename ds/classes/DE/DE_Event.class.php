<?php $CVS_Version = '$Revision$ ; $Date$';
/** 
 * Класс описания типа ивента
 * 
 */
class DE_Event extends DE_Base
{
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $eventName;
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $handlerName;
	
	/**
	 * Enter description here...
	 *
	 * @var boolean
	 */
	private $needCreationLock; 			
	
	/**
	 * Enter description here...
	 *
	 * @var boolean
	 */
	private $lockOnlyInEnd;
	
	function __construct($id, $data = '')
	{		
		assert(is_int($id) && $id>0);		
		
		parent::__construct($id);
		
		if($data == '')
		{
			$data = DS_Event::GetEventDataByID($id);			
		}		
		assert(is_array($data) && count($data)>0);
		
		assert(key_exists('eventname', $data));
		$this->eventName = $data['eventname'];
		assert(!empty($this->eventName));
		
		assert(key_exists('eventhandlerclass', $data));				
		$this->handlerName = $data['eventhandlerclass'];
		assert(!empty($this->handlerName));
		
		assert(key_exists('need_create_lock', $data));				
		if($data['need_create_lock'] == 't')
		{
			$this->needCreationLock = true;
		}else{
			$this->needCreationLock = false;
		}	

		assert(key_exists('lock_only_in_end', $data));				
		if($data['lock_only_in_end'] == 't')
		{
			$this->lockOnlyInEnd = true;
		}else{
			$this->lockOnlyInEnd = false;
		}		
	}
		
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return DE_Event
	 */
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);									
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return DE_Event
	 */
	static public function CreateFromEventName($eventName)
	{
		assert(is_string($eventName) && !empty($eventName));		
		
		$eventID = DS_Event::GetEventIDByName($eventName);
		assert(is_int($eventID) && $eventID > 0);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $eventID);									
	}	
	
	public function getEventName()
	{
		return $this->eventName;
	}
	
	public function getHandlerName()
	{		
		return $this->handlerName;
	}
	
	public function getNeedCreationLock()
	{
		return $this->needCreationLock;	
	}
	
	public function getLockOnlyInEnd()
	{
		return $this->lockOnlyInEnd;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_EventOptions
	 */
	public function getOptions()
	{
		return DE_EventOptions::CreateFromID($this->getID());	
	}
	
}//END OF CLASS


?>