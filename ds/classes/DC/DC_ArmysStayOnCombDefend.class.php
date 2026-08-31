<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция армий на защите соты
 */
class DC_ArmysStayOnCombDefend extends DC_Base 
{
	
	private $combID;
	
	function __construct($combID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($combID) && $combID>0);
		
		$this->mode = 'LINK';

		$this->combID = $combID;
		
		parent::__construct($combID, 'DE_Army');
		
		$data = DS_Army::GetCombArmysOnDefend($combID);				

		if($data)
		{
			foreach($data as $k=>$v)
			{
				assert(key_exists('id_unitcontainer', $v));
				$unitContainerID = (int)$v['id_unitcontainer'];
				assert($unitContainerID>0);
			
				$this->_addNewElement(DE_Army::CreateFromID($unitContainerID));
			}
		}
			
				
		$this->_invariant('construct');
	}	
			
	static public function CreateFromCombID($combID)
	{
		assert(is_int($combID) && $combID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);				
	}
		
	
	protected function _collectionCondition($element)
	{
		if(!($element instanceof DE_Army))
		{
			return false;
		}

		if($element->getCurrentCombID() != $this->combID())
		{
			return false;
		}
			
		if($element->getInAction() == false)
		{
			return false;
		}
		
		if($element->getArmyAction()->getActionType() != 'IN_DEFEND')
		{
			return false;
		}
		
		return true;		
	}
	
		
	protected function _invariant($mode='public')
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
		//$this->commit();
	}

}

?>