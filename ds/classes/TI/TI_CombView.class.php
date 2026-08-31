<?php

/**
 * Метод с входными для формирование описания соты 
 *
 */
class TI_CombView extends TI_BaseMenu 
{
	/**
	 * Строка с ячейками соты
	 *
	 * @var string
	 */
	public $cells = '';
	
	/**
	 * Строка со зданиями
	 *
	 * @var string
	 */
	public $buildings = '';
	
	/**
	 * имя типа части соты
	 *
	 * @var string
	 */
	public $cellTypeName = '';
	
	/**
	 * Открыты или закрыты ворота
	 *
	 * @var integer
	 */
	public $gateStatus = 0;
	
	/**
	 * Время турна в секундах
	 *
	 * @var integer
	 */
	public $turnTime = 0;
	
	/**
	 * ID соты для которой передаем.
	 *
	 * @var integer
	 */
	public $combID;
	
	/**
	 * Метка для клиентского кеша
	 *
	 * @var string
	 */
	public $combMark = '';
	
	public function __construct($combID, $cellTypeName)
	{
		assert(is_int($combID) && $combID > 0);
		assert(is_string($cellTypeName) && !empty($cellTypeName));
		
		parent::__construct($combID, $cellTypeName);								
		
		$this->combID = $combID;
		$this->cellTypeName = $cellTypeName;
	}	
	
	public function validate()
	{
		assert(is_string($this->cells));
		assert(is_string($this->buildings));
		assert(is_string($this->cellTypeName) && !empty($this->cellTypeName));
		assert($this->gateStatus == 0 || $this->gateStatus == 1);
		assert(is_numeric($this->turnTime) && $this->turnTime > 0);
		assert(is_int($this->combID) && $this->combID > 0);
		assert(is_string($this->combMark) && !empty($this->combMark));
	}
}

?>