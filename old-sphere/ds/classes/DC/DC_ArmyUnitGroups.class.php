<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция юнитгрупп из которых остояит армия
 */
class DC_ArmyUnitGroups extends DC_Base
{	

	private $armyID;
		
	function __construct($armyID, $data = null)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($armyID) && $armyID>0);
		assert(is_null($data) || is_array($data));
		
		parent::__construct($armyID, 'DE_UnitsGroup');
		
		$this->armyID = $armyID;
		
		$sort = false;
		
		if(is_array($data))
		{
			if(key_exists('sort_by', $data))
			{
				$sort = true;
			}
		}
		
		$data = DS_Army::GetArmyUnitGroups($armyID, $sort);
		if (count($data)>0)
		{
			foreach($data as $k=>$v)
			{
				array_key_exists('id_unitcontainerhasunit', $v);
				$unitGroupID = (int)$v['id_unitcontainerhasunit'];
				assert($unitGroupID>0); 
				
				$this->_addNewElement(DE_UnitsGroup::CreateFromID($unitGroupID, $v));
			}
		}
		
		$this->_invariant('construct');
	}	
			
	static public function CreateFromArmyID($armyID, $data = null)
	{
		assert(is_int($armyID) && $armyID>0);
		assert(is_null($data) || is_array($data));
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $armyID, $data);				
	}

	/**
	 * Enter description here...
	 *
	 * @param DE_Unit $unit
	 * @return DE_UnitsGroup
	 */
	public function GetUnitGroupByUnit(DE_Unit $unit)
	{
		$this->resetNext();		
		while($unitsGroup = $this->next())
		{
			if($unitsGroup->getUnitID() == $unit->getID())
			{
				return  $unitsGroup;
			}
		}
		
		return false;
	}
	
	public function AddUnitGroup(DE_UnitsGroup $group)
	{
		if(!$this->isMember($group))
		{
			$this->_addNewElement($group);
		}
	}
	
	protected function _invariant($mode='public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);				
	}
	
	protected function _collectionCondition($element)
	{		
		if(!($element instanceof $this->storedClassName))
		{
			return false;
		}
		
		if($element->getArmy()->getID() != $this->armyID)
		{
			return false;
		}
		
		return true;
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