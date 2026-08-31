<?php $CVS_Version = '$Revision$ ; $Date$';

class EH_EventCreate 
{	
	static public function Create($eventName, $participants, $options, GB_DatePeriod $times)
	{
		assert(!empty($eventName));			
		assert(is_array($participants) && count($participants)>0);		
		assert(is_array($options));			
		
		$eventID = (int)DS_Event::GetEventIDByName($eventName);
		assert($eventID>0);
				
		$eventQueueID = (int)DS_Event::InsertEventQueue($eventID, $times);
		assert(!empty($eventQueueID) && $eventQueueID!=-1);
		
		foreach($participants as $k=>$v)
		{
			DS_Event::InsertEventParticiPant($eventQueueID, $v);
		}
		
		foreach($options as $k=>$v)
		{
			DS_Event::InsertEventParam($eventQueueID, $eventID, $k, $v);
		}	
		
		return $eventQueueID;
	}			
}
?>