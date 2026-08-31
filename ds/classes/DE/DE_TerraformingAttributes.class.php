<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_TerraformingAttributes extends DE_Base
{
	private $attributes = array();
			
	function __construct($terraformingID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert($terraformingID>0);
		parent::__construct($terraformingID);		
		
		$res = DS_Terraforming::GetAttributesByID($terraformingID);
		
		$this->_importAttributes($res);
		
		$this->_invariant('construct');
	}	
	
	static public function CreateFromTerraformingID($terraformingID)
	{				
		assert(is_int($terraformingID) && $terraformingID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $terraformingID);
	}
	
	private function _importAttributes($res)
	{
		assert(is_array($res) && count($res)>0);
		
		foreach($res as $k=>$v)
		{			
			$this->attributes[$v['typename']][$v['attributename']] = $v['value'];
		}
	}
	
		
	public function getAttribute($attributeTypeName, $attributeName)
	{
		$this->_invariant();
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));
			
		assert(array_key_exists($attributeTypeName, $this->attributes));
		assert(array_key_exists($attributeName, $this->attributes[$attributeTypeName]));
				
		return (float)$this->attributes[$attributeTypeName][$attributeName];
	}
	
	public function getAttributeNamesByTypeName($attributeTypeName)
	{
		$this->_invariant();
		
		if(key_exists($attributeTypeName, $this->attributes))
		{
			return array_keys($this->attributes[$attributeTypeName]);
		}else{
			return array();
		}
	}
	
	public function haveAttribute($attributeTypeName, $attributeName)
	{
		$this->_invariant();
		
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));	
			
		if (array_key_exists($attributeTypeName, $this->attributes) && array_key_exists($attributeName, $this->attributes[$attributeTypeName]))
		{
			return true;
		}else{
			return false;
		}
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
			
		assert(is_array($this->attributes) && count($this->attributes)>0);
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

}//END OF CLASS


?>