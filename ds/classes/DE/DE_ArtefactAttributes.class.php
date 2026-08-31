<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Атрибуты артефакта
 */
class DE_ArtefactAttributes extends DE_Base implements DE_AttributesInterface
{
	private $attributes = array();
	
	function __construct($artefactID)
	{
		assert(is_int($artefactID) && $artefactID>0);
		parent::__construct($artefactID);
		
		$res = DS_Artefact::GetArtefactAtrributes($artefactID, false);
		$this->_importAttributes($res);
		$this->_invariant('construct');
	}

	/**
	 * Enter description here...
	 *
	 * @param int $artefactID
	 * @return DE_ArtefactAttributes
	 */
	static public function CreateFromArtefactID($artefactID)
	{			
		assert(is_int($artefactID) && $artefactID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $artefactID, '', 'GLOBAL_CACHE');						
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
	
	public function getAttributeTypesNames()
	{
		$this->_invariant();
				
		return array_keys($this->attributes);		
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
	
	public function getAttribute($attributeTypeName, $attributeName, $noObject = false)
	{
 
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));		
		
		assert(array_key_exists($attributeTypeName, $this->attributes));
		assert(array_key_exists($attributeName, $this->attributes[$attributeTypeName]));
				
		if($noObject)
		{
			return (float)$this->attributes[$attributeTypeName][$attributeName]['VALUE'];
		}else{
			return $this->attributes[$attributeTypeName][$attributeName]['VALUE'];
		}
	}
	
	public function haveAttribute($attributeTypeName, $attributeName)
	{
 
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
	}
	
	public function commit()
	{
 		
		parent::commit();				
	}
	
	 
}//END OF CLASS



?>