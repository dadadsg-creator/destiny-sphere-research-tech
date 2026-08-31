<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс для логирования потери ресурсов по дороге (налог на грабеж)
 *
 */
class DE_LogRerourceStealingTax extends DE_Base
{
	
	/**
	 * Сота на которой теряем
	 *
	 * @var DE_Comb
	 */
	private $comb;	
	
	/**
	 * Текущая дата проверки
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
	
	public function log(DE_Comb $combFrom, $resource, $stealingTax, $beforAmount, $loosedAmount, $checkDate)
	{
		assert(is_string($resource) && !empty($resource));
		assert(is_numeric($stealingTax));
		assert(is_numeric($beforAmount));
		assert(is_numeric($loosedAmount));
				
		$key = count($this->data);
		
		$this->data[$key][$resource]['stealing_tax'] = $stealingTax;
		$this->data[$key][$resource]['before_amount'] = $beforAmount;
		$this->data[$key][$resource]['loosed_amount'] = $loosedAmount;
		$this->data[$key][$resource]['comb_from'] = $combFrom->getCombID();
		$this->data[$key][$resource]['ui_from'] = $combFrom->getUserInstanceID();
		$this->data[$key][$resource]['check_date'] = $checkDate->value;
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();	
		if(count($this->data))
		{
			foreach($this->data AS $key => $data)
			{
				foreach ($data as $resource => $resourceData)
				{		
					DS_LogRerourceStealingTax::InsertRecord(
								$this->comb, 
								$this->comb->getUserInstance(),	
								$resource,						
								$resourceData,
								GB_TypeFactory::CreateBase('GB_Date', (int)$resourceData['check_date'])
							);	
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