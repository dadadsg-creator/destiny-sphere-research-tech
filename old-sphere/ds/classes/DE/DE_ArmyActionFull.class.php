<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * По состоянию на 30.07.2008 нигде не используется
 */
class DE_ArmyActionFull extends DE_ArmyAction
{
	private $startTime;
	
	private $endTime;
	
	private $typeID;
	
	private $typeName;
	
	private $combFromID;	
	
	private $combToID;
			
	private $armyID;		
		
	function __construct($armyID)
	{
		assert(is_int($armyID) && $armyID>0);
		parent::__construct($armyID);
		
		$data = DS_Army::GetArmyAction($armyID);
		assert(is_array($data) && count($data)>0);

		assert(key_exists('id_unitcontainer', $data));		
		$this->armyID = (int)$data['id_unitcontainer'];
		
		assert(key_exists('id_unitcontaineractiontype', $data));		
		$this->typeID = (int)$data['id_unitcontaineractiontype'];
		
		assert(key_exists('id_startcomb', $data));		
		$this->combFromID = (int)$data['id_startcomb'];

		assert(key_exists('id_targetcomb', $data));		
		$this->combToID = (int)$data['id_targetcomb'];

		assert(key_exists('actionstarttime', $data));		
		$this->startTime = GB_TypeFactory::CreateMaster('GB_Date', $data['actionstarttime']);

		assert(key_exists('actionendtime', $data));		
		$this->endTime = GB_TypeFactory::CreateMaster('GB_Date', $data['actionendtime']);
		
		assert(key_exists('actiontypename', $data));		
		$this->typeName = $data['actiontypename'];

		$this->_invariant('construct');
	}
	
	public function getCombFrom()
	{
		$this->_invariant();
		return DE_Comb::CreateFromID($this->combFromID);
	}
	
	public function getEndDate()
	{
 
		return $this->endTime;
	}
	
	public function getStartDate()
	{
 
		return $this->startTime;
	}

	public function getCombTo()
	{
 
		return DE_Comb::CreateFromID($this->combToID);
	}
	
	public function getCombToID()
	{
 
		return $this->combToID;
	}
	
	public function isNull()
	{
 
		return false;	
	}
		
	public function getActionType()
	{
 
		return $this->typeName;
	}
	
	public function getEvent()
	{
 
		return DE_EventQueue::CreateFromArmy($this->getArmy());
	}
	
	public function setActionType($actionTypeName)
	{
 
		$this->typeName = $actionTypeName;
		$this->isModifyed = true;
	}
	
	public function getArmy()
	{
 
		return DE_Army::CreateFromID($this->armyID);
	}

	
	protected function _invariant($mode = 'public')
	{
 
		
		parent::_invariant($mode);

		assert($this->startTime instanceof GB_Date);
		assert($this->endTime instanceof GB_Date);
		assert(!empty($this->typeName));		
		assert(is_int($this->typeID) && $this->typeID>0);
		assert(is_int($this->combFromID) && $this->combFromID>0);
		assert(is_int($this->combToID) && $this->combToID>0);
		assert(is_int($this->armyID) && $this->armyID>0);		
	}

	public function delete()
	{
 
		parent::delete();
		
		DS_Army::DeleteArmyActionByArmyID($this->getID());
	}
	
	public function commit()
	{
 
		parent::commit();		
	}
	
	function __destruct()
	{
 
		parent::__destruct();
		//$this->commit();
	}
}//END OF CLASS 

?>