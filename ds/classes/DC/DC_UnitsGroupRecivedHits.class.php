<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция ударов принятая групой
 */
class DC_UnitsGroupRecivedHits  extends DC_Base
{	
	function __construct($id)
	{
		assert(!empty($id));				
		
		$this->mode = 'LINK';
		
		parent::__construct($id, 'array');		
								
		$this->_invariant();
	}

	static public function CreateFromUnitsGroupID($unitsGroupID)
	{
		assert(is_int($unitsGroupID) && $unitsGroupID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $unitsGroupID);						
	}
	
	public function addHit($recivedHit) 
	{
		$this->_addNewElement($recivedHit);			
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

