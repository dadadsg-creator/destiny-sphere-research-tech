<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс атрибутов для нулевого объекта.
 */
class DE_AttributesNull extends DE_Base implements DE_AttributesInterface
{	
	
	function __construct()
	{		
		parent::__construct(100);			
		$this->_invariant('construct');
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_AttributesNull
	 */
	static public function Create()
	{						
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, 100);						
	}
	
	public function haveAttribute($attributeTypeName, $attributeName)
	{	
		return true;	
	}
	
	public function getAttribute($attributeTypeName, $attributeName, $noObject = false)
	{
		if($noObject)
		{
			return (float)0;
		}else{
			return (float)0;
		}			
	}

	protected function _invariant($mode = 'public')
	{
		parent::_invariant($mode);
	}
	
	public function commit()
	{
 		
		parent::commit();				
	}
	
}//END OF CLASS

?>