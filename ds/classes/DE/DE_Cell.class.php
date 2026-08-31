<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ќб€ет €чейки соты. «десь испоьзуютьс€ “ќЋ№ ќ неизмен€емы атрибуты €чейки.
 * если захочетьс€ добавить измен€емы такие как тип почвы то надо не забыть про ресеты кеша.
 *
 */
class DE_Cell extends DE_Base
{
	/**
	 * ID сотына которой распложенна эта €чейка.
	 *
	 * @var int
	 */
	private $combID;
	
	/**
	 * ID части соты на которой €чейка расположенна
	 *
	 * @var int
	 */
	private $cellTypeID;
	
	/**
	 * “ип почвы на €чейке
	 *
	 * @var int
	 */
	private $groundTypeID;
	
	/**
	 * Ќомер €чейки на соте
	 *
	 * @var int
	 */
	private $cellNumber;
	
	
	public function __construct($id)
	{
		assert(is_int($id) && $id>0);
		parent::__construct($id);		
		
		$data = DS_Comb::GetCellByID($id);
		assert(is_array($data));
		
		assert(key_exists('id_comb', $data));
		$this->combID = (int)$data['id_comb'];	
		assert($this->combID > 0);	
		
		assert(key_exists('id_celltype', $data));
		$this->cellTypeID = (int)$data['id_celltype'];	
		assert($this->cellTypeID > 0);
		
		assert(key_exists('cellnumber', $data));
		$this->cellNumber = (int)$data['cellnumber'];	
		assert($this->cellNumber > 0);
	}
	
	/**
	 * —оздание на основание ID цела
	 *
	 * @param integer $id
	 * @return DE_Cell
	 */
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id > 0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, '', 'WORLD_CACHE');	
	}
	
	public function getCellTypeID()
	{
		return $this->cellTypeID;
	}
}

?>