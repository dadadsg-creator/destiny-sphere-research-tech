<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция с боями данного пользователя
 */
class DC_UserInstanceBattles extends DC_Base 
{
	function __construct($userInstanceID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($userInstanceID) && $userInstanceID>0);
		
		parent::__construct($userInstanceID, 'DE_Battle');
		
		$data = DS_Battle::GetBattlesToUSerInstance($userInstanceID);				
				
		foreach($data as $k=>$v)
		{
			assert(key_exists('id_battle', $v));			
			$battleID = (int)$v['id_battle'];
			assert($battleID > 0);
			$this->_addNewElement(DE_Battle::CreateFromID($battleID));
		}
		
		$this->_invariant('construct');
	}	
			
	static public function CreateFromUserInstanceID($userInstanceID)
	{
		assert(is_int($userInstanceID) && $userInstanceID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $userInstanceID);				
	}
	
	protected function _invariant($mode = 'construct')
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

}
?>