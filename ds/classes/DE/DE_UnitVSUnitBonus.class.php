<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * Сущность с бонусами юнита рпотив юнита
 */

class DE_UnitVSUnitBonus extends DE_Base
{	
	
	private $bonuses;
						
	private $emptyBonus = 1;
	
	function __construct($unitFromID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_integer($unitFromID) && $unitFromID>0);		
		parent::__construct($unitFromID);
		
		$this->bonuses = new GB_Array('string', 'double');		
		
		$data = DS_Unit::GetUnitVSUnitBonusForUnit($unitFromID, false);		
		
		if(is_array($data) && count($data)>0)
		{
			foreach($data as $k=>$v)
			{
				$this->bonuses->addElement($v['id_unitto'], (double)$v['value']);
			}
		}
		
		$this->_invariant('construct');
	}
			
	static public function CreateFromUnitFromID($unitFromID)
	{
		assert(is_int($unitFromID) && $unitFromID>0);			
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $unitFromID, "", "GLOBAL_CACHE");
	}
			
	public function getBonusVsUnit(DE_Unit $unit)
	{
		$this->_invariant();
		if($this->bonuses->haveKey((string)$unit->getID()))
		{
			return (float)$this->bonuses->getElement((string)$unit->getID());
		}else{
			return (float)$this->emptyBonus;
		}
	}	
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);						
		assert($this->bonuses instanceof GB_Array);		
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();			
	}
	
	public function delete()
	{
		parent::delete();
				
	}
	
	function __destruct()
	{
		parent::__destruct();
		$this->_invariant();		
	}

}


?>