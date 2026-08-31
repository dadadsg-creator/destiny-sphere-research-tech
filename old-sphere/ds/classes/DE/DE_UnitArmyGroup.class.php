<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_UnitArmyGroup extends DE_Base 
{
	
	public $unitID = 0;
	
	public $unit;
	
	public $armyID = 0;
	
	public $ammount = 0;
	
	function DE_UnitArmyGroup($id){				
		$this->DE_Base($id);
		
		$data = DS_Unit::getUnitGroup($id);
		assert(is_array($data));
		assert(count($data)>0);	
				
		assert(key_exists('id_unit', $data));		
		$this->unitID = $data['id_unit'];
		
		assert(key_exists('id_army', $data));		
		$this->armyID = $data['id_army'];
		
		assert(key_exists('amount', $data));		
		$this->ammount = $data['amount'];
	}
	
	function createFromID($id)
	{		
		settype($id, 'integer');
		assert($id>0);
		
		if(GB_SingletonObjectStorage::HaveObject('DE_UnitArmyGroup',$id)){
			return GB_SingletonObjectStorage::GetObject('DE_UnitArmyGroup',$id);	
		}else{
			$unitGroup = &new DE_UnitGroup($id);
			GB_SingletonObjectStorage::SetObject('DE_UnitArmyGroup', $id);
			return $unitGroup;
		}
	}
	
	function getUnit()
	{
		if($this->unit){
			return $this->unit;
		}else{
			return DE_Unit::CreateFromID($this->unitID);
		}		
	}
	
	function getGroupPower()
	{
		$unit = &$this->getUnit();
		return $this->ammount * $unit->getUnitPower();
	}
		
}
?>