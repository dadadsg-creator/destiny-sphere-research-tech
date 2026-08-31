<?php $CVS_Version = '$Revision$ ; $Date$';


class DE_ReligionNull extends DE_Base
{
	
	private $attributes = null;

	function __construct()
	{
		GB_SingletonObjectStorage::TestSingletonCreation();		

		parent::__construct(100);

		$this->attributes = new DE_AttributesNull;
			
		$this->_invariant('construct');
	}	
	
	static public function Create()
	{				
	
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, 100);	
	}			
	
	public function getAttributes()
	{		
				
		$this->_invariant();	
		return $this->attributes;
	}
	
	public function getName() 
	{
		$this->_invariant();
		return "-";
	}
	
	public function getLocolizedName()
	{
		$this->_invariant();				
		return "-";
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
		assert($this->attributes instanceof DE_AttributesNull);
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();
		//$this->commit();
	}

}//END OF CLASS

?>