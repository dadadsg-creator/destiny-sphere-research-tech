<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция инстансов, защищающих соту
 */
class DC_CombDefendUserInstances extends DC_Base
{
		
	function __construct($combID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($combID) && $combID>0);
		
		parent::__construct($combID);
		
		$data = DS_Battle::CombDefendUserInstances($combID);
		
		if(count($data)<1)
		{
			return;
		}
		
		foreach($data as $k=>$v)
		{
			assert(array_key_exists('id_userinstance', $v));
			$userInstanceID = (int)$v['id_userinstance'];
			assert(is_int($userInstanceID ) && $userInstanceID >0);
			$this->collection[] = DE_UserInstance::CreateFromID($userInstanceID);
		}	

		$this->_invariant();	
	}
		
	static public function CreateFromCombID($combID)
	{
		assert(is_int($combID) && $combID>0);				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);				
	}	
	
	
	public function refreshDefenders()
	{
		$this->_invariant();
		$data = DS_Battle::CombDefendUserInstances($this->getID());
		if(count($data)<1)
		{
			return;
		}
		foreach($data as $k=>$v)
		{
			assert(array_key_exists('id_userinstance', $v));
			$userInstanceID = (int)$v['id_userinstance'];
			assert(is_int($userInstanceID ) && $userInstanceID >0);
			$newDefender = DE_UserInstance::CreateFromID($userInstanceID);
			if(!$this->isMember($newDefender))
			{
				$this->add($newDefender);
			}
		}			
	}
		
	
	protected function _invariant()
	{
		parent::_invariant();
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
}

?>