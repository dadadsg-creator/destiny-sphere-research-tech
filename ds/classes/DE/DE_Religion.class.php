<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/07/01 08:46:26 $';


class DE_Religion extends DE_Base
{
	private $religionID;
	private $religionName;	
	private $religionLocolizedNameID;

	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);

		parent::__construct($id);
		
		$data = DS_Religion::getByID($id, false);
		
		assert(is_array($data) && count($data)>0);		
		
		assert(key_exists('id_religion', $data));
		$this->religionID = (int)$data['id_religion'];
		
		assert(key_exists( 'religionname', $data));		
		$this->religionName = $data['religionname'];
		
		assert(key_exists( 'id_localizedname', $data));		
		$this->religionLocolizedNameID = (int)$data['id_localizedname'];
		

		$this->_invariant('construct');
	}	
	
	static public function CreateFromID($id)
	{				
		assert(is_int($id) &&  $id>0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, '', 'GLOBAL_CACHE');	
	}		

	static public function CreateFromUserInstanceID($id)
	{
		assert(is_int($id) &&  $id>0);
		$religionID = (int)DS_Religion::GetReligionStatusByUserInstance($id);
		if ($religionID>0)
		{
			return DE_Religion::CreateFromID($religionID);			
		}else{
			return false;
		}		
	}
	
	public function getName()
	{
		$this->_invariant();				
		return $this->religionName;
	}
	
	public function getLocolizedName()
	{
		$this->_invariant();				
		return DE_Localization::CreateForCurentLang()->get($this->religionLocolizedNameID);
	}
	
	public function getAttributes()
	{
		$this->_invariant();				
		return DE_ReligionAttributes::CreateFromReligionID($this->getID());
	}

	public function commit()
	{
		$this->_invariant();
		parent::commit();
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
		assert(!empty($this->religionName));
		assert(is_int($this->religionID) && $this->religionID>0);
		assert(is_int($this->religionLocolizedNameID) && $this->religionLocolizedNameID>0);		
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();
		//$this->commit();
	}

}//END OF CLASS

?>