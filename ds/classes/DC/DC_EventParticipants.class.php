<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция участников мероприятия
 */

class DC_EventParticipants extends DC_Base
{				
	function __construct($eventID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($eventID) && $eventID>0);
		
		parent::__construct($eventID, 'DE_UserInstance');
		
		$data = DS_Event::GetEventParticipantsByEventID($eventID);
		if (count($data)>0)
		{
			foreach($data as $k=>$v)
			{
				assert(key_exists('id_userinstance', $v));
				$userInstanceID = (int)$v['id_userinstance'];
				// Закомментировано для захватываемых колоний
				// assert($userInstanceID>0);
				
				$this->_addNewElement(DE_UserInstance::CreateFromID($userInstanceID));
			}
		}
		
		$this->_invariant('construct');
	}	
			
	static public function CreateFromEventID($eventID)
	{
		assert(is_int($eventID) && $eventID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $eventID);				
	}
			
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);				
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();			
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();	
	}
	
	public function addNewElement($userInstanceID)
	{
		//assert($userInstanceID > 0);
		$this->_addNewElement(DE_UserInstance::CreateFromID($userInstanceID));	
	}
}

?>