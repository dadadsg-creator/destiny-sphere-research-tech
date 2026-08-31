<?php $CVS_Version = '$Revision$ ; $Date$';

class ST_Attributes extends ST_Base
{
	private $dataSecondary = array();
	private $dataTypeNamesByID = array();
	private $dataNamesByID = array();
	private $dataLocalizationByName = array();

	
	function __construct($id)
	{				
		parent::__construct(parent::_formMark(__CLASS__));
						
	}//END OF METHOD
	
	protected function _getDataFromDB()
	{
		return DS_Misc::GetAllAtrributes(false);
	}
	
	protected function _parse()
	{
		foreach($this->preData as $k=>$v)
		{
			$this->data[$v['typename']][$v['attributename']] = (int)$v['id_attribute'];
			$this->dataSecondary[$v['id_attribute']] = array("attributeTypeName"=>$v['typename'], "attributeName"=>$v['attributename']);
						
			$this->dataTypeNamesByID[$v['id_attributetype']] = $v['typename'];
			$this->dataNamesByID[$v['id_attributename']] = $v['attributename'];
			
			//$this->dataNamesByTypeNames[$v['typename']] = $v['id_attributetype'];
			$this->dataIDByNames[$v['attributename']] = $v['id_attributename'];
			
			$this->dataLocalizationByName[$v['attributename']] = $v['id_localizedname'];
		}
	}
	
	public function getAttributeIDByAttributeName($attributeName)
	{
		assert(key_exists($attributeName, $this->dataIDByNames));
		
		return $this->dataIDByNames[$attributeName];
	}

	public function getAttributeNameByAttributeNameID($attributeNameID)
	{
		assert(key_exists($attributeNameID, $this->dataNamesByID));
		return $this->dataNamesByID[$attributeNameID];
	}
	
	public function getAttributeIDByTypeAndName($attributeTypeName, $attributeName)
	{		
		assert(key_exists($attributeTypeName, $this->data));
		assert(key_exists($attributeName, $this->data[$attributeTypeName]));
		
		return $this->data[$attributeTypeName][$attributeName];
	}
		
	public function getAttributeNameByAttributeID($attributeID)
	{
		assert(key_exists($attributeID, $this->dataSecondary));
		return $this->dataSecondary[$attributeID]['attributeName'];
	}
	
	public function getAttributeTypeNameByAttributeID($attributeID)
	{		
		assert(key_exists($attributeID, $this->dataSecondary));
		
		return $this->dataSecondary[$attributeID]['attributeTypeName'];
	}

	public function getAttributeLocalizedName($attributeName)
	{
		assert(key_exists($attributeName, $this->dataLocalizationByName));
		
		return $this->dataLocalizationByName[$attributeName];
	}
	
	/**
	 * Enter description here...
	 *
	 * @return ST_Attributes
	 */
	public static function Create()
	{		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, 111, '', 'GLOBAL_CACHE');
	}	
	
	public function __destruct()
	{
		parent::__destruct();
	}
}

?>