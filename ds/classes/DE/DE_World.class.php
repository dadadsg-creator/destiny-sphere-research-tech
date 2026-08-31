<?php $CVS_Version = '$Revision$ ; $Date$';


class DE_World extends DE_Base
{
	private $name;

	private $shortName;
	
	private $description;
	
	public function __construct($id)
	{
		//GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_integer($id) && $id>0);		
		parent::__construct($id);
		
		$data = DS_World::GetByID($id);		
		assert(is_array($data) && count($data)>0);

		assert(key_exists('worldname', $data));		
		$this->name = $data['worldname'];
		assert(!empty($this->name));			

		assert(key_exists('world_identifier', $data));
		$this->shortName = $data['world_identifier'];
		assert(!empty($this->shortName));
			
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return DE_World
	 */
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id>0);					
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
	}	
	
	/**
	 * Enter description here...
	 *
	 * @return DE_World
	 */
	static public function CreateForCurrentWorld()
	{		
		assert(key_exists('world', $_SESSION));
		$worldID = (int)$_SESSION['world'];
		assert($worldID > 0);
				
		return self::CreateFromID($worldID);
	}	
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getShortName()
	{
		return $this->shortName;
	}
}


?>