<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * —ущность группы юнитов составл€ющих армию. 
 */
class DE_UnitsGroup extends DE_Base implements DE_UnitsGroupInterface      
{
	
	private $unitID;
	
	private $armyID;
	
	public $amount;	
	
	private $loses;
		
	private $groupWarPower;
		
	function __construct($id, $data = '')
	{	
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);
			
		parent::__construct($id);
		
		$this->needAdditionalDeleteProcess = true;

		if(!is_array($data))
		{
			$data = DS_Army::getUnitGroup($id);
		}

		assert(is_array($data) && count($data)>0);
				
		assert(key_exists('id_unit', $data));		
		$this->unitID = (int)$data['id_unit'];
				
		assert(key_exists('id_army', $data));		
		$this->armyID = (int)$data['id_army'];
		
		assert(key_exists('amount', $data));		
		$this->amount = (float)$data['amount'];		
		
		$this->loses = (float)0;		
		
		$this->recivedDamage = (float)0;		
		
		$this->outputedDamage =(float)0;
				
		if(key_exists('sintetic', $data) && $data['sintetic'] == 1)
		{
			$this->needInsert = true;
		}
			
		$this->_invariant('construct');
	}	
		
	static public function CreateFromID($id, $data = null)
	{				
		assert(is_int($id) && $id>0);
		assert(is_array($data) || is_null($data));
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}
	
	
	static public function CreateInDB(DE_Army $army, DE_Unit $unit, $amount)
	{		
		assert(is_float($amount) && $amount > 0);
		
		$id = DS_Army::GetNewIDForUnitsGroup();		
		
		$data['id_unit'] = $unit->getID();
		$data['id_army'] = $army->getID();
		$data['amount'] = $amount;
		$data['sintetic'] = 1;

		$unitsGroup = GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
		
		$unitsGroup->setNeedInsert(true);
		
		return $unitsGroup;
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);
		assert(is_int($this->unitID) && $this->unitID>0);		
		assert(is_int($this->armyID) && $this->armyID>0);
		assert(is_float($this->amount));
		assert(is_null($this->groupWarPower) || $this->groupWarPower instanceof DE_UnitsGroupWarPower);								
		assert(is_float($this->loses));	
	}
		
	/**
	 * Enter description here...
	 *
	 * @return DE_Unit
	 */
	public function getUnit()
	{
		//$this->_invariant();				
		return DE_Unit::CreateFromID($this->unitID);				
	}
		
	public function getCapacity()
	{
		$this->_invariant();				
		$val = $this->getUnit()->getCapacity() * $this->amount;
		return (float)$val;
	}

	public function getRecivedDamage()
	{
		return 0;
	}
	public function getOutputedDamage()
	{
		return 0;
	}
	
	public function getGroupPower($calcUnitsWithNullDamage = true)
	{		
		assert(is_bool($calcUnitsWithNullDamage));
		
		if(!$calcUnitsWithNullDamage && $this->getGroupWarPower()->getDemage() == 0)
		{
			return 0;
		}
		
		$sum = (double)((double)$this->amount * (double)$this->getUnit()->getUnitPower());		
		assert($sum >= 0);
		
		return $sum;
	}
	
    public function setLoses($loses)
    {
    	assert(is_float($loses) && $loses >= 0);
    	
    	//$this->_invariant(); 
   	
    	$this->loses = $loses;
    	
    	if($this->loses > $this->getAmount())
    	{
    		$this->loses = $this->getAmount();
    	}
    }
	
    public function getLoses()
    {
    	//$this->_invariant();    	    	
    	return $this->loses;
    }
    
    /**
     * 
     * @return DE_UnitsGroupWarPower
     */
	public function getGroupWarPower()
	{
		//$this->_invariant();

		if(is_null($this->groupWarPower))	
		{
			$this->groupWarPower = new DE_UnitsGroupWarPower($this);
		}
		
		return $this->groupWarPower;			
	}
	
	public function resetGroupWarPower()
	{
		//$this->_invariant();
		$this->groupWarPower = null;
	}
	
	public function getUnitID()
	{
		//$this->_invariant();
		return $this->unitID;
	}
	
	public function getArmy()
	{
		//$this->_invariant();			
		return DE_Army::CreateFromID($this->armyID);		
	}
	
	public function haveNullDamage()
	{
		if($this->getGroupWarPower()->getDemage() == 0)
		{
			return true;
		}else{
			return false;
		}
	}
	
	public function getAmount()
	{
		//$this->_invariant();
		return $this->amount;
	}
	
	public function repair()
	{
		//$this->_invariant();
		$roundVal = ceil($this->getAmount());
		
		if($roundVal != $this->getAmount())
		{
			 $this->amount = $roundVal;
			 $this->isModifyed = true;	
		}		
	}
	
	public function deltaAmount($value)
	{
		//$this->_invariant();				
		
		assert(is_float($value));
		
		$out = 0;	
			
		$this->amount += $value;
		
		$this->isModifyed = true;
		
		if($this->amount < 0)
		{	
			$out = -$this->amount;
					
			$this->amount = (float)0;						
		}
		
		return $out;
	}
	
	public function setAmount($value)
	{
		//$this->_invariant();
		assert(is_float($value));		
		
		if($value < 0)
		{
			$this->amount = (float)0;
		}else{
			$this->amount = $value;
		}		
		
		$this->isModifyed = true;
	}
	
	public function delete()
	{		
		parent::delete();									
	}
	
	public function additionDelete()
	{
		if(!$this->needInsert)
		{
			DS_Army::DeleteUnitsGroupByID($this->getID());	
		}		
	}
	
	public function commit()
	{
		//$this->_invariant();
		parent::commit();
		
		assert($this->getAmount() > 0);
		
		if(!DS_Army::GetByID($this->armyID))
		{
			return;
		}
				
		if($this->needInsert)
		{			
			DS_Army::CreateUnitsGroup($this->getArmy(), $this->getUnit(), $this->getAmount(), $this->getID());
			$this->needInsert = false;
			$this->isModifyed = false;							
		}else{
			if($this->isModifyed == true)
			{	
				DS_Army::UpdateUnitsGroup($this);				
				$this->isModifyed = false;				
			}	
		}
		
		
	}
	
	function __destruct()
	{
		//$this->_invariant();
		parent::__destruct();
		//$this->commit();
	}

}//END OF CLASS
?>