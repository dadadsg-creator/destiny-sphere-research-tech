<?php $CVS_Version = '$Revision$ ; $Date$';
/** DEPRICATED CLASS */

class DC_BattleParticipantHasArmies extends DC_Base
{
		
	function __construct($participantID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($participantID) && $participantID>0);
		
		parent::__construct($participantID);
		
		$data = DS_Battle::getParticipantArmys($participantID);
		
		if(count($data)<1 || $data == false)
		{
			return;
		}
		
		foreach($data as $k=>$v)
		{
			assert(array_key_exists('id_unitcontainer', $v));
			$unitContainerID = (int)$v['id_unitcontainer'];
			assert(is_int($unitContainerID ) && $unitContainerID >0);
			$this->add(DE_Army::CreateFromID($unitContainerID));
		}	

		$this->_invariant();	
	}
		
	static public function CreateFromParticipantID($participantID)
	{
		assert(is_int($participantID) && $$participantID>0);				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $participantID);				
	}	
	
	static public function CreateInDB($participantID)
	{
		assert(is_int($participantID) && $participantID > 0);		
		$armiesOfParticipant = DS_Battle::GetParticipantArmies($participantID);
		foreach($armiesOfParticipant as $key=>$army)
		{
			assert(array_key_exists('id_unitcontainer', $army));
			assert(array_key_exists('unitcontainername', $army));
			
			$unitContainerID =(int)$army['id_unitcontainer'];
			assert($unitContainerID >0);
			
			$unitcontainername = $army['unitcontainername'];
			
			DS_Battle::InsertParticipantArmies($participantID,$unitContainerID,$unitcontainername);
		}
		
	}
	
			
	
	protected function _invariant()
	{
		parent::_invariant();
	}
}

?>