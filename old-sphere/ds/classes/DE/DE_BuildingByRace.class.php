<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Здание конкретной расы
 * типа один DE_Building может иметь разные названия и описания у каждой расы
 *
 */
class DE_BuildingByRace extends DE_Base
{				
	private $buildingID;
	
	private $raceID;
	
	private $locolizedNameID;		
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(!empty($id));
		
		parent::__construct($id);		
		
		$IDParts = explode('_', $id);
		
		$this->raceID = (int)$IDParts[0];
		$this->buildingID = (int)$IDParts[1];

		assert($this->raceID > 0);
		assert($this->buildingID > 0);
			
		$data = DS_Building::GetRaceBuildingByRaceAndBuilding($this->raceID, $this->buildingID);
		
		assert(is_array($data));
		assert(count($data)>0);
				
		assert ( key_exists ( 'id_localizedname', $data ) );		
		$this->locolizedNameID = (int)$data['id_localizedname'];		

		$this->_invariant('construct');
	}
	
	static public function CreateFromID($id)
	{		
		assert(!empty($id));
				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
	}
	
	static public function CreateFromRaceIDAndBuildingID($raceID, $buildingID)
	{
		assert(is_integer($raceID) && $raceID > 0);
		assert(is_integer($buildingID) && $buildingID > 0);
		
		$id = $raceID."_".$buildingID;
		
		return self::CreateFromID($id);
	}
					
	
	public function getLocalizedName()
	{
		$this->_invariant();
		
		return DE_Localization::CreateForCurentLang()->get($this->locolizedNameID);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Building
	 */
	public function GetBuilding()
	{
		$this->_invariant();
		
		return DE_Building::CreateFromID($this->buildingID);
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
		
		assert($this->buildingID > 0);
		assert($this->raceID > 0);
		assert($this->buildingID > 0);
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