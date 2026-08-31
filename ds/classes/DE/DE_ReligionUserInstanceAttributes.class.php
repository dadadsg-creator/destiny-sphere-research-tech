<?php $CVS_Version = '$Revision$ ; $Date$';


class DE_ReligionUserInstanceAttributes extends DE_Base implements DE_AttributesInterface 
{
	private $attributes = array();
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		parent::__construct($id);

		$a = explode('|',$id);
		$religionID = (int)$a[0];
		$statusName = (string)$a[1];
		
		assert(is_int($religionID) && $religionID>0);
		assert(!empty($statusName));
			
		$res = DS_Religion::GetReligionAtrributes($religionID);
		$this->_importAttributes($res, $statusName);
		$this->_invariant('construct');
	}

	static public function CreateFromReligionIDStatusName($religionID, $statusName)
	{				
		assert(is_int($religionID) && $religionID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $religionID.'|'.$statusName);			
	}

	private function _importAttributes($res,$statusName)
	{

		assert(is_array($res) && count($res)>0);
		
		foreach($res as $k=>$v)
		{
			if ( $statusName != 'RELIGI_STATUS_ACTIVE' && $v['value']>0 ) {continue;}
			$val = array(
				'VALUE' => (float)$v['value']
			); 
			$this->attributes[$v['typename']][$v['attributename']] = $val;
		}
	}

	public function getAttribute($attributeTypeName, $attributeName, $noObject = false)
	{
		$this->_invariant();
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));		
		
		assert(array_key_exists($attributeTypeName, $this->attributes));
		assert(array_key_exists($attributeName, $this->attributes[$attributeTypeName]));
		
		if($noObject)
		{
			return $this->attributes[$attributeTypeName][$attributeName]['VALUE'];
		}else{
			return GB_TypeFactory::CreateConstant('GB_Float', $this->attributes[$attributeTypeName][$attributeName]['VALUE']);
		}
		
	}

	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
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