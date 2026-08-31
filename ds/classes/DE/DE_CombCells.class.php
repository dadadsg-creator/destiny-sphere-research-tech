<?php $CVS_Version = '$Revision$ ; $Date$';

/**
	 ласс содержит €чейки опредленной части соты. ÷ентральной, нейральной или домашней.
	ID обекта сставной состоит из IDcomb_IDcelltype
*/
class DE_CombCells extends DE_Base
{
	/**
	 * ID —оты с которой имеем дело.
	 *
	 * @var Integer
	 */
	private $combID;
	
	/**
	 * ID чатси соты. 1 - домашка, 2 - центральна€, 3 - домашка.
	 *
	 * @var integer
	 */
	private $cellTypeID;
	
	/**
	 * ћассив с €чейками соты
	 *  'GROUND_TYPE_ID' => ID типа почвы,
		'CELL_ID' => ID €чейки в Ѕƒ
		
		 лючем в массиве €вл€етьс€ cellNumber
		
	 * @var array
	 */
	public $cells = array();
	
	public function __construct($id)
	{		
		assert(is_string($id) && !empty($id) && strpos($id, '_'));		
		parent::__construct($id);
		
		$this->_create($id);
	}
	
	private function _create($id)
	{
		assert(is_string($id) && !empty($id) && strpos($id, '_'));	
		
		$tmp = explode('_', $id);
		assert(is_array($tmp) && count($tmp) == 2);
		
		$this->combID = (int)$tmp[0];
		assert($this->combID > 0);
		
		$this->cellTypeID = (int)$tmp[1];
		assert($this->cellTypeID > 0);
		
		$data = DS_Comb::GetCombCells($this->combID, $this->cellTypeID);
		assert(is_array($data) && count($data)>0);
		
		foreach($data AS $k=>$v)
		{
			$cellNumber = (int)$v['cellnumber'];
			assert($cellNumber > 0);
			
			$groundTypeID = (int)$v['id_groundtype'];
			assert($groundTypeID > 0);
			
			$cellID = (int)$v['id_cell'];
			assert($cellID > 0);
			
			$this->cells[$cellNumber] = array(
				'GROUND_TYPE_ID' => $groundTypeID,
				'CELL_ID' => $cellID
			);
		}	
	}
	
	private function _reset()
	{
		unset($this->combID);
		unset($this->cellTypeID);
		unset($this->cells);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $combID
	 * @param unknown_type $cellTypeID
	 * @return DE_CombCells
	 */
	static public function CreateFromCombIDAndCellTypeID($combID, $cellTypeID)
	{
		assert(is_int($combID) && $combID > 0);	
		assert(is_int($cellTypeID) && $cellTypeID > 0);	
		
		$id = $combID."_".$cellTypeID;
			
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, '', 'WORLD_CACHE');
	}		
	
	public function reset()
	{
		GB_SingletonObjectStorage::ResetObjectCache(__CLASS__, $this->getID(), 'WORLD_CACHE');
		
		$this->_reset();
		
		$this->_create($this->getID());
	}
}


?>