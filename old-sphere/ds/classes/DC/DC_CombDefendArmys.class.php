<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция армий стоящих на защите соты
 */
class DC_CombDefendArmys extends DC_Base
{
	
	private $combID;
	
	function __construct($combID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($combID) && $combID>0);
		
		parent::__construct($combID, 'DE_Army');
		
		$this->combID = $combID;
		
		$data = DS_Army::GetArmysOnComb($combID);

		if(count($data)<1)
		{
			return;
		}
		
		foreach($data as $k=>$v)
		{
			assert(array_key_exists('id_unitcontainer', $v));
			$unitContainerID = (int)$v['id_unitcontainer'];
			assert($unitContainerID>0);	
			
			$this->_addNewElement(DE_Army::CreateFromID($unitContainerID));
		}	

		$this->_invariant('construct');	
	}
		
	static public function CreateFromCombID($combID)
	{
		assert(is_int($combID) && $combID>0);				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);				
	}	
	
	public function externalAddArmy(DE_Army $army)
	{
		$this->_addNewElement($army);
	}
	
	
	protected function _collectionCondition($element)
	{
		if(!($element instanceof $this->storedClassName))
		{
			return false;
		}
						
		if($army->getInAction == true)
		{
			$condition = ($army->getCurentCombID() == $this->combID) && ($army->getArmyAction()->getActionType() == 'IN_DEFEND');
		}else{
			$condition = ($army->getBaseCombID() == $this->combID) && ($army->getInAction == false);			
		}
				
		return $condition;
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);
	}
	
	public function __destruct()
	{		
		parent::__destruct();
	}
}

?>