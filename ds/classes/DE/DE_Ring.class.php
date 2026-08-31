<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс для опсиания сущности
 */
class DE_Ring extends DE_Base 
{	
		
	/** Номер кольца */
	private $number;
	
	/** колличество комдпексов на кольце */
	private $complexCount;

	/**
	* @param $id - ID соты
	* @desc Внести описание здесь...
	*/
	function __construct($id) 
	{		
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);
		
		parent::__construct($id);
						
		$data = DS_Ring::getByID($id, false);		
		assert(is_array($data) && count($data)>0);		
				
		assert(key_exists('id_ring', $data));		
		$this->id = (int)$data['id_ring'];
		
		assert ( key_exists ( 'ringnumber', $data ) );		
		$this->number = (int)$data['ringnumber'];
		
		assert ( key_exists ( 'complexcount', $data ) );		
		$this->complexCount = (int)$data['complexcount'];
		
		$this->_invariant('construct');
		
	}//END OF METHOD
	
	/**
	* @param $id - ID соты
	* @desc Статтический метод для создания экземпляра объекта DE_Ring
	*/
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, "", 'WORLD_CACHE');
	}
	
	static public function CreateFromNumber($ringNumber)
	{
		assert(is_int($ringNumber) && $ringNumber>0);
		$id = DS_Ring::GetIDByNumber($ringNumber);
		assert(is_int($id) && $id>0);
		return DE_Ring::CreateFromID($id);
	}		
	
	/**
	* @return Номер комплекса
	* @desc Возвращает номер комплекса
	*/
	public function getComplexCount ()
	{
		$this->_invariant();
		return $this->complexCount;
	}//END OF METHOD	
 	

	public function getNumber()
	{
		$this->_invariant();
		return $this->number;
	}
	
 	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
				
		assert(is_int($this->number) && $this->number>0);
		assert(is_int($this->complexCount) && $this->complexCount>0);
	}
 	
	public function commit()
	{
		$this->_invariant();
		parent::commit();
		
		if($this->isModifyed == true)
		{
			if(DS_Comb::UpdateCombData($this))
			{
				$this->isModifyed = false;
			}			
		}
	}
	
	function __destruct()
	{
		$this->_invariant();
		//$this->commit();
	}

}//END OF CLASS


?>