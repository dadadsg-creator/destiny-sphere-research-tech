<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Действие армии
 */
class DE_ArmyAction extends DE_Base
{
	/**
	 * Время начала действия
	 *
	 * @var GB_Date
	 */
	private $startTime;
	
	/**
	 * Время окончания действия
	 *
	 * @var GB_Date
	 */
	private $endTime;
	
	private $typeID = 0;
	
	private $typeName = '';
	
	private $combFromID = 0;	
	
	private $combToID = 0;
			
	private $armyID = 0;		
	
	private $isNull = true;
	
	private $createAsNull = true;
	
	function __construct($armyID)
	{		
		assert(is_int($armyID) && $armyID>0);
		parent::__construct($armyID);
		
		$data = DS_Army::GetArmyAction($armyID);
		assert(is_array($data));
		
		$dataCount = count($data);
		
		if($dataCount == 1)
		{
			$this->_createFull($data[0]);
		}elseif($dataCount == 0){
			$this->_createNull();
		}else{
			ds_error("Invalid ArmyAction data array count = ".$dataCount);
		}
	}
	
	private function _createFull($data)
	{		
		assert(is_array($data));
		
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
		
		$this->isNull = false;
		$this->createAsNull = false;
	}
	
	private function _createNull()
	{		
		$this->createAsNull = true;		
		$this->setActionAsNull();
		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param int $armyID
	 * @return DE_ArmyAction
	 */
	static public function CreateFromArmyID($armyID)
	{
		assert(is_int($armyID) && $armyID>0);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $armyID);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DE_Comb $combFrom
	 * @param DE_Comb $combTo
	 * @param GB_DatePeriod $datePeriod
	 * @param string $actionType
	 */
	public function setNewActionData(DE_Comb $combFrom, DE_Comb $combTo, GB_DatePeriod $datePeriod, $actionType)
	{
		assert(!empty($actionType));		
		
		$this->combFromID = $combFrom->getID();		
		$this->combToID = $combTo->getID();
		$this->startTime = $datePeriod->getStartDate();
		$this->endTime = $datePeriod->getEndDate();
		$this->typeName = $actionType;
		
		$this->isNull = false;	
		$this->isModifyed = true;
	}
	
	public function setActionAsNull()
	{		
		$this->typeID = 0;
		$this->typeName = '';
		$this->combFromID = 0;
		$this->combToID = 0;	
		
		$this->isNull = true;				
		$this->isModifyed = true;	
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getCombFrom()
	{		
		return DE_Comb::CreateFromID($this->combFromID);
	}
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function setCombFrom($combFromID)
	{		
		assert($combFromID > 0);
		$this->combFromID = $combFromID;

		$this->isNull = false;
		$this->isModifyed = true;
	}	
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function setCombTo($combToID)
	{		
		assert($combToID > 0);
		$this->combToID = $combToID;

		$this->isNull = false;
		$this->isModifyed = true;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return GB_Date
	 */
	public function getEndDate()
	{	
		return $this->endTime;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return GB_Date
	 */
	public function setEndDate(GB_Date $endDate)
	{	
		$this->endTime = $endDate;
		
		$this->isNull = false;
		$this->isModifyed = true;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return GB_Date
	 */
	public function getStartDate()
	{	
		return $this->startTime;
	}

	
	/**
	 * Enter description here...
	 *
	 * @return GB_Date
	 */
	public function setStartDate(GB_Date $endDate)
	{	
		$this->startTime = $endDate;
		
		$this->isNull = false;
		$this->isModifyed = true;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getCombTo()
	{	
		return DE_Comb::CreateFromID($this->combToID);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return int
	 */
	public function getCombToID()
	{	
		return $this->combToID;
	}
	
	public function isNull()
	{	
		return $this->isNull;	
	}
		
	public function getActionType()
	{	
		return $this->typeName;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_EventQueue
	 */
	public function getEvent()
	{	
		return DE_EventQueue::CreateFromArmy($this->getArmy());
	}
		
	/**
	 * Enter description here...
	 *
	 * @return DE_Army
	 */
	public function getArmy()
	{
		$this->_invariant();
		return DE_Army::CreateFromID((int)$this->id);
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();
		
		if(!$this->isModifyed)
		{
			return;
		}
		
		if($this->createAsNull)
		{
			if($this->isNull)
			{
				return;
			}else{
				$datePeriod = new GB_DatePeriod($this->startTime, $this->endTime);
				DS_Army::InsertArmyAction((int)$this->id, $this->combFromID, $this->combToID, $datePeriod, $this->getActionType());
			}
		}else{
			if($this->isNull)
			{
				DS_Army::DeleteArmyAction($this);
			}else{
				$datePeriod = new GB_DatePeriod($this->startTime, $this->endTime);
				DS_Army::UpdateArmyAction($this->getArmy(), $this->getCombFrom(), $this->getCombTo(), $datePeriod, $this->getActionType());
			}
		}
	}
	
	public function __destruct()
	{
		parent::__destruct();
		$this->_invariant();	
	}
}

?>