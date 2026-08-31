<?php

/**
 * Объект здания на соте
 *
 */
class DE_AttachedBuilding extends DE_Base 
{
	private $buildingID;
	
	private $cellID;
	
	private $combID;
	
	public function __construct($id)
	{
		assert(is_int($id) && $id>0);
		parent::__construct($id);		
		
		$data = DS_Building::GetAttachedBuildingByID($id);
		assert(is_array($data));
		
		assert(key_exists('id_comb', $data));
		$this->buildingID = (int)$data['id_comb'];	
		assert($this->buildingID > 0);	
		
		assert(key_exists('id_cell', $data));
		$this->cellID = (int)$data['id_cell'];	
		assert($this->cellID > 0);
		
		assert(key_exists('id_comb', $data));
		$this->combID = (int)$data['id_comb'];	
		assert($this->combID > 0);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param int $id
	 * @return DE_AttachedBuilding
	 */
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id > 0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);	
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Cell
	 */
	public function getCell()
	{
		return DE_Cell::CreateFromID($this->cellID);
	}
	
	
	
}

?>