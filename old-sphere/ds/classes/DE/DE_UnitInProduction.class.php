<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_UnitInProduction extends DE_Base
{
	private $attachedBuildingID;
	
	private $unitID;
	
	/**
	 * Enter description here...
	 *
	 * @var GB_Date
	 */
	private $startDate;
	
	/**
	 * Enter description here...
	 *
	 * @var GB_Date
	 */
	private $endDate;
	
	private $amount;
	
	private $isEmpty = false;
	
	public function __construct($id, $data = null)
	{
		assert(is_int($id) && $id > 0);
		parent::__construct($id);
		
		//Это для того что бы засветитьс яна кладбище объектов и др объекты могли пнять что рмия удалена была
		$this->needAdditionalDeleteProcess = true;
		
		if(is_null($data))
		{
			$data = DS_UnitProduction::GetUnitInProductionByAttachedBuildingID($id);		
		}
				
		if(is_array($data) && count($data)>0)
		{
			assert(key_exists('id_attachedbuilding', $data));
			$this->attachedBuildingID = (int)$data['id_attachedbuilding'];
			assert($this->attachedBuildingID > 0);
		
			assert(key_exists('id_unit', $data));
			$this->unitID = (int)$data['id_unit'];
			assert($this->unitID > 0);
			
			assert(key_exists('amount', $data));
			$this->amount = (int)$data['amount'];
			assert($this->amount >= 0);
		
			assert(key_exists('starttime', $data));		
			$this->startDate = GB_TypeFactory::CreateBase('GB_Date', $data['starttime']);
		
			assert(key_exists('endtime', $data));		
			$this->endDate = GB_TypeFactory::CreateBase('GB_Date', $data['endtime']);
		
			if(key_exists('sintetic', $data) && $data['sintetic'] == 1)
			{
				$this->needInsert = true;
			}					
		}else{
			$this->isEmpty = true;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @param unknown_type $data
	 * @return DE_UnitInProduction
	 */
	static public function CreateFromID($id, $data = '')
	{
		assert(is_int($id) && $id>0);					
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}
	
	static public function CreateInDB()
	{
		$id = DS_Battle::CreateInDBWithoutCreationObject($round, $participant, $unitsGroup);
		
		$data = array
		(
			'id_attachedbuilding' => '',
			'id_unit' => '',
			'starttime' => '',
			'endtime' => '',
			'amount' => '',			
			'sintetic' => 1
		);	
		
		return self::CreateFromID($id, $data);
	}
	
	public function isEmpty()
	{
		return $this->isEmpty;
	}
	
	
	public function getAttachedBuildingID()
	{
		assert(!$this->isEmpty);
		
		return $this->attachedBuildingID;
	}
	
	public function getUnitID()
	{
		assert(!$this->isEmpty);
		return $this->unitID;
	}
	
	public function getUnit()
	{
		assert(!$this->isEmpty);
		return DE_Unit::CreateFromID($this->unitID);
	}
	
	public function getAmount()
	{
		assert(!$this->isEmpty);
		return $this->amount;
	}
	
	public function deltaAmount($delta)
	{
		assert(!$this->isEmpty);		
		assert(is_int($delta));
		
		$this->amount += $delta;
		assert($this->amount >= 0);
		
		$this->isModifyed = true;	
	}
	
	public function getStartDate()
	{
		assert(!$this->isEmpty);
		return $this->startDate;
	}
	
	public function setStartTime($timeStamp)
	{
		assert(!$this->isEmpty);
		assert((is_int($timeStamp) || is_double($timeStamp)) && $timeStamp > 0);
		
		$this->startDate->value = $timeStamp;
		
		$this->isModifyed = true;	
	}
	
	public function getEndDate()
	{
		assert(!$this->isEmpty);
		return $this->endDate;
	}
			
	
	public function delete()
	{		
		parent::delete();									
	}	
	
	
	public function additionDelete()
	{
		if(!$this->needInsert)
		{
			DS_UnitProduction::DeleteUnitInProductionByAttachedBuildingID($this->getID());	
		}		
	}
	
	public function commit()
	{
		parent::commit();
		
		if($this->isEmpty)
		{
			return;
		}
		
		if($this->needInsert)
		{
			DS_UnitProduction::UpdateUnitInProduction($this);
			$this->needInsert = false;
			$this->isModifyed = false;	
		}else{
			if($this->isModifyed == true)
			{
				if(DS_UnitProduction::UpdateUnitInProduction($this))
				{
					$this->isModifyed = false;
				}			
			}	
		}
		
	}
	
	function __destruct()
	{
		parent::__destruct();	
	}
}

?>