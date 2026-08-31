<?php $CVS_Version = '$Revision$ ; $Date$';

class ST_EventAndOptions extends ST_Base
{	
	private $eventNameByID = array();
					
	function __construct($id)
	{								
		ds_error('Depricated class. Use DE_Event and DE-EventOptions');
		
		parent::__construct(parent::_formMark(__CLASS__));
								
	}
	
	protected function _getDataFromDB()
	{		
		return DS_Event::GetAllEventsAndOptions(false);
	}
	
	protected function _parse()
	{
		assert(is_array($this->preData));
		assert(count($this->preData)>0);
		
		foreach($this->preData as $k=>$v)
		{
			$this->data[$v['eventname']]['ID']= $v['id_event'];
			$this->data[$v['eventname']]['HANDLER_NAME']= $v['eventhandlerclass'];
			$this->data[$v['eventname']]['OPTIONS'][$v['optionname']]= $v['id_eventoption'];
			
			$this->eventNameByID[$v['id_event']] = $v['eventname'];
		}
	}
	
	public function getEventNameByID($eventID)
	{
		assert($eventID>0);
		
		return $this->eventNameByID[$eventID];
	}
	
	public function getHandlerNameByEventName($eventName)
	{
		assert(key_exists($eventName, $this->data));
		
		return $this->data[$eventName]['HANDLER_NAME'];
	}
	
	public function getEventIDByName($eventName)
	{
		assert(key_exists($eventName, $this->data));
		
		return (int)$this->data[$eventName]['ID'];
	}
	
	public function getOptionIDByEventNameAndOptionName($eventName, $optionName)
	{
		assert(key_exists($eventName, $this->data));
		assert(key_exists($optionName, $this->data[$eventName]['OPTIONS']));
		
		return $this->data[$eventName]['OPTIONS'][$optionName];
	}
	
	public static function Create()
	{				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, 1111, '', 'GLOBAL_CACHE');
	}	
	
	public function __destruct()
	{
		parent::__destruct();
	}
}

?>