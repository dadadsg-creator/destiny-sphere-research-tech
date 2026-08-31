<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция групп юнитов формирующих оборонную группу юнитов.
 */
class DC_UnitsGroupsInCombDefendGroup extends DC_Base
{
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(!empty($id) && is_string($id));
		
		$this->mode = 'LINK';
		
		parent::__construct($id, 'DE_UnitsGroup');
	}
		
	static public function CreateFromDefendGroupID($id)
	{
		assert(!empty($id) && is_string($id));				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);				
	}	

	public function add(DE_UnitsGroup $unitsGroup)
	{
		$this->_addNewElement($unitsGroup);
	}
}

?>