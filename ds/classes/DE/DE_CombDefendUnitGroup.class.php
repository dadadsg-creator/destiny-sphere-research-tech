<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * То же что и DE_CombDefendArmy, только для групп юнитов
 *
 */
class DE_CombDefendUnitGroup extends DE_Base implements DE_UnitsGroupInterface 
{
	private $unitGroups = array();
	
	private $unitID;	
	
	private $combID;	
	
	private $groupWarPower;
	
	private $loses;
	
	/**
	 * Enter description here...
	 *
	 * @var DC_UnitsGroupsInCombDefendGroup
	 */
	private $unitsGroupCollection;
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(!empty($id) && is_string($id));
		$params = explode('_', $id);
		assert(is_array($params) && count($params) == 2);
		
		$combID = (int)$params[0]; 
		$unitID = (int)$params[1];
		
		assert(is_int($combID) && $combID>0);
		assert(is_int($unitID) && $unitID>0);
		
		$this->combID = $combID;
		$this->unitID = $unitID;
		
		parent::__construct(DE_CombDefendUnitGroup::FormID($combID, $unitID));
		
		
		assert(is_array($this->unitGroups) || count($this->unitGroups)>0);
	}
	
	/** Добавление группы юнитов в дефендова*/
	public function addUnitGroup(DE_UnitsGroup $unitGroup)
	{
		
		$this->getUnitsGroupCollection()->add($unitGroup);
	}
	
	public function getAmount()
	{
		$amount = (float)0;
		
		$this->getUnitsGroupCollection()->resetNext();
		while($unitsGroup = $this->getUnitsGroupCollection()->next())
		{
			$amount = $amount + $unitsGroup->getAmount();
		} 
		
		return $amount;
	}	
	
	public function getUnitsGroupCollection()
	{
		if(!($this->unitsGroupCollection instanceof DC_UnitsGroupsInCombDefendGroup))
		{
			$this->unitsGroupCollection = DC_UnitsGroupsInCombDefendGroup::CreateFromDefendGroupID($this->getID());
		}
		
		return $this->unitsGroupCollection;
	}
	

	public function setLoses($loses)
    {
    	assert(is_float($loses) && $loses >= 0);
    	    
    	$this->loses = $loses;
    	
    	if($this->loses > $this->getAmount())
    	{
    		$this->loses = $this->getAmount();
    	}
    }
	
    public function getLoses()
    {
    
    	return $this->loses;
    }
	
	public function getGroupWarPower()
	{
		

		if(is_null($this->groupWarPower))	
		{
			$this->groupWarPower = new DE_UnitsGroupWarPower($this);
		}
		
		return $this->groupWarPower;			
	}
	
	public function resetGroupWarPower()
	{
	
		$this->groupWarPower = null;
	}
	
	public function getGroupPower($calcUnitsWithNullDamage = true)
	{
		assert(is_bool($calcUnitsWithNullDamage));
		
		$summary = (float)0;
		
		$this->getUnitsGroupCollection()->resetNext();
		while($unitsGroup = $this->getUnitsGroupCollection()->next())
		{
			if(!$calcUnitsWithNullDamage && $unitsGroup->getGroupWarPower()->getDemage() == 0)
			{
				return 0;
			}
			
			$summary = $summary + $unitsGroup->getGroupPower();
		}		
				
		return (float)$summary;
	}
	
	
	static public function CreateFromCombIDUnitID ($combID, $unit)
	{
		assert(is_integer($combID) && $combID>0);
		assert($unit instanceof DE_Unit);		
		$unitID = $unit->getID();	
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, self::FormID($combID, $unitID));								
	}
	
	static public function FormID ($combID, $unitID)
	{
		assert(is_integer($combID) && $combID>0);
		assert(is_integer($unitID) && $unitID>0);	
		
		return $combID.'_'.$unitID;
	}
	
	public function getUnit()
	{
		return DE_Unit::CreateFromID($this->unitID);		
	}//END OF METHOD	
		
	public function getArmy()
	{
		
		return DE_CombDefendArmy::CreateFromCombID($this->combID);			
	}
		
	public function commit()
	{
		
		parent::commit();
		
		if($this->isModifyed == true)
		{
			if(DS_Comb::UpdateCombData($this))
			{
				$this->isModifyed = false;
			}			
		}
	}
}//END OF CLASS


?>