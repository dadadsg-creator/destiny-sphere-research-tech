<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Сущность типа "домик"
 *
 */
class DE_Building extends DE_Base
{		
	private $buildingName;
	
	private $type;
		
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);
		
		parent::__construct($id);		
		
		$data = DS_Building::GetByID($id);
		
		assert(is_array($data));
		assert(count($data)>0);
				
		assert ( key_exists ( 'buildingname', $data ) );		
		$this->buildingName = $data['buildingname'];		
		
		assert ( key_exists ( 'type', $data ) );		
		$this->type = $data['type'];		

		$this->_invariant('construct');
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return DE_Building
	 */
	static public function CreateFromID($id)
	{		
		assert(is_integer($id) && $id>0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $name
	 * @return DE_Building
	 */
	static public function CreateFromName($name)
	{		
		assert(!empty($name));
		
		$id = (int)DS_Building::GetIDByName($name);
		
		assert($id > 0);
		
		return self::CreateFromID($id);
	}
	
	public function getRaceBuilding($raceID)
	{		
		assert(is_int($raceID) && $raceID > 0);
		$this->_invariant();
				
		return DE_BuildingByRace::CreateFromRaceIDAndBuildingID($raceID, $this->getID());
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_BuildingAttributes
	 */
	public function getAttributes()
	{
		$this->_invariant();
		
		return DE_BuildingAttributes::CreateFromBuildingID($this->getID());
	}
	
	public function getName()
	{
		$this->_invariant();
		
		return $this->buildingName;
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
		
		assert(!empty($this->buildingName));
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();			
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();		
	}
	
	
}

?>