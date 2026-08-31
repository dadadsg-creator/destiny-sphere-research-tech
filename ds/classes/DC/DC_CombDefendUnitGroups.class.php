<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция юнитгрупп который стоят на соте в обороне
 */
class DC_CombDefendUnitGroups extends DC_Base
{
	/**ID комба для которйо эта коллекция  */
	private $combID;
	
	/** Группы юнитов уже добавленные к списку */
	
	function __construct($combID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($combID) && $combID>0);
		
		parent::__construct($combID, 'DE_CombDefendUnitGroup');

		$this->combID = $combID;

		$this->_formCollection();
	}
		
	private function _formCollection()
	{
		$combDefendArmisCollection = DC_CombDefendArmys::CreateFromCombID($this->combID);
		
		if($combDefendArmisCollection->countCollection() == 0)
		{
			return;
		}

		$combDefendArmisCollection->resetNext();
		while($army = $combDefendArmisCollection->next())
		{
			$army->getUnitsGroupCollection()->resetNext();			
			while($unitGroup = $army->getUnitsGroupCollection()->next())
			{
				$this->_importGroup($unitGroup);
			}			
		}
	}//END OF METHOD
	
	private function _importGroup(DE_UnitsGroup $unitGroup)
	{
			
		$this->resetNext();
		while($combDefendUnitGroup = $this->next())
		{		
			if($combDefendUnitGroup->getUnit()->getID() == $unitGroup->getUnit()->getID())
			{			
				$combDefendUnitGroup->addUnitGroup($unitGroup);				
				return;
			}					
		}		
		
		$newCombDefendUnitGroup = DE_CombDefendUnitGroup::CreateFromCombIDUnitID($this->combID, $unitGroup->getUnit());	
		$newCombDefendUnitGroup->addUnitGroup($unitGroup);
		$this->_addNewElement($newCombDefendUnitGroup);
	}

	public function externalImportUnitsGroup(DE_UnitsGroup $unitGroup)
	{
		$this->_importGroup($unitGroup);
	}
	
	public function haveSimplUnitsGroup(DE_UnitsGroup $unitGroup)
	{
		$this->resetNext();
		while($combDefendUnitGroup = $this->next())
		{		
			if($combDefendUnitGroup->getUnit()->getID() == $unitGroup->getUnit()->getID())
			{			
				$unitGroupCollection = 	$combDefendUnitGroup->getUnitsGroupCollection();					
				return $unitGroupCollection->isMember($unitGroup);				
			}					
		}		
		
		return false;
	}
	
	static public function CreateFromCombID($combID)
	{
		settype($combID, 'integer');
		assert($combID>0);				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);				
	}	
}//END OF CLASS

?>