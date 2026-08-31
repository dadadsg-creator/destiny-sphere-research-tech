<?php $CVS_Version = '$Revision$ ; $Date$';

/* DEPRECATED */

class DC_EventInstanceCollection
{		
	private $uiid = 0;
	
	/** Массиы с набором ивентов */
	private $events = array();
	
	function __construct($uiid)
	{
		assert($uiid>0);
		$this->uiid = $uiid;
		$this->_init();
	}
	
	public function addEvent($ID)
	{
		settype($ID, 'integer');
		assert($ID>0);	
		
		$v = DS_Event::GetEventQueueByID($ID);

		if(!$v)
		{
			ds_error('DE_EventInstanceCollection::addEvent - Unknown eventquque with ID = '.$ID);
		}
			
		$event['ID'] = $v['id_eventqueue'];
		$event['END_TIME'] = strtotime($v['eventendtime']);
		$event['HANDLER'] = $v['eventhandlerclass'];		
		$this->events[] = $event;
	}
	
	public function getNextEvent()
	{
		if(count($this->events) == 0)
		{
			return false;
		}
		
		$start = true;
		$minTime = 0;
		$eventID = 0;
		$index = 0;
		foreach($this->events as $k=>$v)
		{
			if($start)
			{
				$start = false;
				$minTime = $v['END_TIME'];
				$eventID = $v['ID'];
				$index = $k;
			}else{
				if($v['END_TIME']<$minTime)
				{
					$minTime = $v['END_TIME'];
					$eventID = $v['ID'];
					$index = $k;
				}
			}
		}
		
		$out = $this->events[$index];
		$this->_removeEvent($eventID);
		return $out;
	}
	
	private function _removeEvent($ID)
	{
		settype($ID, 'integer');
		assert($ID>0);	
		
		if(count($this->events) == 0)
		{
			return;
		}
		
		foreach($this->events as $k=>$v)
		{
			if($ID==$v['ID'])
			{
				array_splice($this->events, $k, 1);		
				return true;
			}
		}
		
		return false;
	}
	
	private function _init()
	{
		$rs = DS_Event::GetInstanceEvents($this->uiid);
		
		if(!$rs)
		{
			return;
		}
		
		foreach($rs as $k=>$v)
		{						
			$event['ID'] = $v['id_eventqueue'];
			$event['END_TIME'] = strtotime($v['eventendtime']);
			$event['HANDLER'] = $v['eventhandlerclass'];			
			$this->events[] = $event;
		}				
	}	
			
}//END OF CLASS

?>