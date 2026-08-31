<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_Language extends DE_Base
{
		
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_integer($id) && $id>0);		
		parent::__construct($id);		
		
		$this->_invariant();
	}
			
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id>0);			
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
	} 
	
	static public function CreateForCurentLanguage()
	{		
		assert(key_exists('lang', $_SESSION));		
		assert($_SESSION['lang']>0);
		
		return self::CreateFromID((int)$_SESSION['lang']);	
	}
	
	public function commit()
	{
		parent::commit();
	}
}


?>