<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция армий летящих на соту и стоящих на соте.
 *
 */
class DC_ArmyAttackOrDefendComb extends DC_Base 
{
	
	private $combID;
	
	function __construct($combID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($combID) && $combID>0);
		
		parent::__construct($combID, 'DE_Army');
		
		$this->combID = $combID;
		
		$data = DS_Army::GetArmysAttackOrDefendComb($combID);
			
		if(empty($data) || count($data)<1)
		{
			return;
		}
				
		foreach($data as $k=>$v)
		{
			assert(key_exists('id_unitcontainer', $v));
			$unitContainerID = (int)$v['id_unitcontainer'];
			assert($unitContainerID>0);
			
			$this->_addNewElement(DE_Army::CreateFromID($unitContainerID));
		}
		
		$this->_invariant('construct');
	}	
			
	/**
	 * Enter description here...
	 *
	 * @param int $combID
	 * @return DC_CombArmys
	 */
	static public function CreateFromCombID($combID)
	{
		assert(is_int($combID) && $combID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);						
	}
	
	/*protected function _collectionCondition($element)
	{
		if(!($element instanceof $this->storedClassName))
		{
			return false;
		}
		
		if($element->getBaseCombID() != $this->armyID)
		{
			return false;
		}
		
		return true;
	}*/
	
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);				
	}
	
	function __destruct()
	{
		parent::__destruct();
	}

}

?>