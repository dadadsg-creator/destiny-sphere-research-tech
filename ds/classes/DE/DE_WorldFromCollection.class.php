<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_WorldFromCollection extends DE_Base
{	
	private $nameDB;	
	
	private $shortName;
	
	private $worldID;
	
	public function __construct($id)
	{	
		assert(is_string($id) && !empty($id));	
		parent::__construct($id);
		
		$data = DS_World::GetWorldFromCollectionByShortName($id);	
		//echo "id=$id, data=".var_export($data, true)."<br>";	
		assert(is_array($data) && count($data)>0);

		assert(key_exists('dbname', $data));		
		$this->nameDB = $data['dbname'];
		assert(!empty($this->nameDB));		
		
		assert(key_exists('world_identifier', $data));		
		$this->shortName = $data['world_identifier'];
		assert(!empty($this->shortName));	

		assert(key_exists('id_world', $data));		
		$this->worldID = (int)$data['id_world'];
		assert($this->worldID > 0);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $id
	 * @return DE_WorldFromCollection
	 */
	static public function CreateFromID($id)
	{
		assert(is_string($id) && !empty($id));						
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
	}	
		
	public function getDBName()
	{
		return $this->nameDB;
	}

	public function getShortName()
	{
		return $this->shortName;
	}
	
	public function getWorldID()
	{
		return $this->worldID;
	}
	
	public function getWorldSettings()
	{
		return DE_WorldSettings::CreateForCurentWorld();
	}
}



?>