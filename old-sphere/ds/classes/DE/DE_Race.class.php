<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/07/01 08:46:26 $';

class DE_Race extends DE_Base 
{
	private $raceID;
	private $raceName;	
	private $localizedNameID;
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);

		parent::__construct($id);
		
		$data = DS_Race::getByID($id);
		
		assert(is_array($data) && count($data)>0);		
		
		assert(key_exists('id_race', $data));
		$this->raceID = (int)$data['id_race'];
		
		assert(key_exists( 'racename', $data));		
		$this->raceName = $data['racename'];
		
		assert(key_exists( 'id_localizedname', $data));
		$this->localizedNameID = (int)$data['id_localizedname'];
		
		$this->_invariant('construct');
	}	
	
	static public function CreateFromID($id)
	{				
		assert(is_int($id) &&  $id>0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, '', 'GLOBAL_CACHE');
	}		

	static public function CreateFromUserInstanceID($userInstanceID)
	{
		// Для колоний DestinySphere
		if ($userInstanceID == 0) {
			return DE_Race::CreateFromID(1);
		}
		
		assert(is_int($userInstanceID) && $userInstanceID>0);

		$id = (int)DE_UserInstance::CreateFromID($userInstanceID)->getRaceID();
		if(!$id)
		{
			return false;
		}else{
			return DE_Race::CreateFromID($id);
		}			
	}
	
	public function getLocalizedName()
	{
		$this->_invariant();		
		return DE_Localization::CreateForCurentLang()->get($this->localizedNameID);
	}
	
	public function getName()
	{
		return $this->raceName;
	}
	
	public function getAttributes()
	{
		$this->_invariant();		
		return DE_RaceAttributes::CreateFromRaceID($this->getID());
	}

	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
		
		assert(!empty($this->raceName));
		assert(is_int($this->raceID) && $this->raceID>0);	
		assert(is_int($this->localizedNameID) && $this->localizedNameID>0);
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
		//$this->commit();
	}
}//END OF CLASS


?>