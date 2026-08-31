<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Пустое действия армии, т.е. армия отдыхает
 */

class DE_ArmyActionNull extends DE_ArmyAction
{
	function __construct($armyID)
	{
		assert(is_int($armyID) && $armyID>0);
		parent::__construct($armyID);
		
		$this->_invariant('construct');
	}
		
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);
	}	
	
	public function commit()
	{
 
		parent::commit();
	}
	
	public function getActionType()
	{
		return '';
	}
	
	public function isNull()
	{
		return true;	
	}
	
	function __destruct()
	{
 
		parent::__destruct();
		//parent::commit();
	}		
}//END OF CLASS 

?>