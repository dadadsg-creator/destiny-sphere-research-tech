<?php $CVS_Version = '$Revision$ ; $Date$';


class DE_RaceAttributes extends DE_Base implements DE_AttributesInterface
{
	private $attributes = array();
	
	function __construct($raceID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($raceID) && $raceID>0);
		parent::__construct($raceID);
		
		$res = DS_Race::GetRaceAtrributes($raceID, false);
		$this->_importAttributes($res);
		$this->_invariant('construct');
	}

	static public function CreateFromRaceID($raceID)
	{				
		assert(is_int($raceID) && $raceID>0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $raceID, '', 'GLOBAL_CACHE');		
	}

	private function _importAttributes($res)
	{

		assert(is_array($res) && count($res)>0);
		
		foreach($res as $k=>$v)
		{
			$val = array(
				'VALUE' => (float)$v['value']
			); 
			$this->attributes[$v['typename']][$v['attributename']] = $val;
		}
	}
	
	public function getAttributeNamesForAttributeType($attributeTypeName)
	{
		$this->_invariant();
		
		if(key_exists($attributeTypeName, $this->attributes))
		{
			return array_keys($this->attributes[$attributeTypeName]);
		}else{
			return array();
		}
		
	}
	
	public function getAttribute($attributeTypeName, $attributeName, $noObject = false)
	{
		$this->_invariant();
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));		
		
		assert(array_key_exists($attributeTypeName, $this->attributes));
		assert(array_key_exists($attributeName, $this->attributes[$attributeTypeName]));
		
		
		return $this->attributes[$attributeTypeName][$attributeName]['VALUE'];		
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
		
		parent::_invariant();
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