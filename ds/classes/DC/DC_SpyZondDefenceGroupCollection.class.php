<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция групп зондов, стоящих на защите соты
 */
class DC_SpyZondDefenceGroupCollection extends DC_Base
{
	private $combID;
	
	function __construct($combID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($combID) && $combID>0);
		
		parent::__construct($combID, 'DE_SpyZondGroup');
		
		$this->combID = $combID;
		
		$data = DS_SpyAction::GetSpyGroupsInAction(DE_Comb::CreateFromID($this->combID), DE_Unit::CreateFromName('SPY_ZOND_SCOUT'), DE_SpyAction::CreateFromName('DEFEND'));

		if(count($data) < 1)
		{
			return;
		}
		
		foreach($data as $k => $v)
		{
			assert(array_key_exists('id_spy_group_action', $v));
			$groupID = (int)$v['id_spy_group_action'];
			assert($groupID>0);	
			
			$this->_addNewElement(DE_SpyZondGroup::CreateFromID($groupID));
		}	

		$this->_invariant('construct');	
	}
		
	static public function CreateFromCombID($combID)
	{
		assert(is_int($combID) && $combID > 0);				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);				
	}	
	
	public function externalAddArmy(DE_SpyZondGroup $group)
	{
		$this->_addNewElement($group);
	}

	public function __destruct()
	{		
		parent::__destruct();
	}
}

?>