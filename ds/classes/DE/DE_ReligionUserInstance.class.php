<?php $CVS_Version = '$Revision$ ; $Date$';


class DE_ReligionUserInstance extends DE_Base
{
	private $religionID;
	private $religionStatusID;
	private $statusName;	
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);

		parent::__construct($id);

		$data = DS_Religion::GetReligionStatusByUserInstance($id);

		assert(is_array($data) && count($data)>0);		
		
		assert(key_exists('id_religion', $data));
		$this->religionID = (int)$data['id_religion'];
		
		assert(key_exists( 'id_status', $data));		
		$this->religionStatusID = (int)$data['id_status'];

		assert(key_exists( 'statusname', $data));		
		$this->statusName = $data['statusname'];

		$this->_invariant('construct');
	}
	
	static public function CreateFromID($id)
	{				
		assert(is_int($id) &&  $id>0);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
	}

	public function getAttributes()
	{
		$this->_invariant();
			
		return DE_ReligionUserInstanceAttributes::CreateFromReligionIDStatusName($this->getReligionID(), $this->getStatusName());
	}

	public function getReligionID()
	{
		return $this->religionID;
	}

	public function getStatusName()
	{
		return $this->statusName;
	}

	public function commit()
	{
		$this->_invariant();
		parent::commit();
	}
	
	public function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
		
		assert(is_int($this->religionID) && $this->religionID>0);
		assert(is_int($this->religionStatusID) && $this->religionStatusID>0);
		assert(!empty($this->statusName));
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();
		//$this->commit();
	}
	
}//END OF CLASS




?>
