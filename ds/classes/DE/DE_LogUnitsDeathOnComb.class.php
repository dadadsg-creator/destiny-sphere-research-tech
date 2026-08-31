<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс для логирования помирания юнитов в игре в результате чего-либо кроме боев.
 *
 */
class DE_LogUnitsDeathOnComb extends DE_Base
{
	
	/**
	 * Сота на которой мрет
	 *
	 * @var DE_Comb
	 */
	private $comb;	
	
	/**
	 * Чекущая дата проверки
	 *
	 * @var GB_Date
	 */
	private $currentCheckDate;
	
	/**
	 * Массив с данными логировнаия
	 * array(
	 * 		ID_UNIT => array(CHECK_DATE => array(REASON_NAME => array(START_AMOUNT, DELTA)));	 
	 * )
	 * 
	 * @var array
	 */
	private $data = array();
	
	function __construct($combID)
	{
		assert(is_int($combID) && $combID>0);
		
		parent::__construct($combID);		

		$this->comb = DE_Comb::CreateFromID($combID);		
	}	
	
	/**
	 * создание объекта по ID соты
	 *
	 * @param integer $combID
	 * @return DE_LogUnitsDeathOnComb
	 */
	static public function CreateFromID($combID)
	{		
		assert(is_integer($combID) && $combID>0);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID, "", "WORLD_CACHE");
	}
	
	public function refreshData(GB_Date $checkDate)
	{
		$this->currentCheckDate = $checkDate;
	}
	
	public function log($reasonName, DE_Unit $unit, $startAmount, $delta)
	{
		assert(is_string($reasonName) && !empty($reasonName));
		assert(is_numeric($startAmount));
		assert(is_numeric($delta));
				
		assert($this->currentCheckDate instanceof  GB_Date);
		
		//Если ничего не померло то фигли логировать
		if($delta <= 0 || $startAmount <= 0)
		{
			return;
		}
		
		if(!key_exists($unit->getID(), $this->data))
		{
			$this->data[$unit->getID()] = array();
		}
		
		if(!key_exists($this->currentCheckDate->value, $this->data[$unit->getID()]))
		{
			$this->data[$unit->getID()][$this->currentCheckDate->value] = array();
		}
		
		if(!key_exists($reasonName, $this->data[$unit->getID()][$this->currentCheckDate->value]))
		{
			$this->data[$unit->getID()][$this->currentCheckDate->value][$reasonName] = array();
		}
		
		if(count($this->data[$unit->getID()][$this->currentCheckDate->value][$reasonName]) == 0)
		{
			$this->data[$unit->getID()][$this->currentCheckDate->value][$reasonName] = array($startAmount, $delta);
		}else{
			$this->data[$unit->getID()][$this->currentCheckDate->value][$reasonName][0] += $startAmount;
			$this->data[$unit->getID()][$this->currentCheckDate->value][$reasonName][1] += $delta;			
		}
		
		$this->data[$unit->getID()][$this->currentCheckDate->value][$reasonName] = array($startAmount, $delta);
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();	

		if(count($this->data))
		{
			foreach($this->data AS $unitID => $unitData)
			{
				foreach($unitData AS $checkTimeStamp => $reasonData)
				{
					foreach($reasonData AS $reasonName => $amountData)
					{
						$reasonNameID = DS_LogUnitsDeath::GetDeathReasonByID($reasonName);
						assert(is_int($reasonNameID) && $reasonNameID > 0);
						
						DS_LogUnitsDeath::InsertRecord(
							$reasonNameID, 
							$this->comb, 
							$this->comb->getUserInstance(),
							DE_Unit::CreateFromID((int)$unitID),
							GB_TypeFactory::CreateBase('GB_Date', (int)$checkTimeStamp),
							$amountData[0],
							$amountData[1]
						);	
					}					
				}
			}
		}//END OF IF
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();		
	}
}

?>