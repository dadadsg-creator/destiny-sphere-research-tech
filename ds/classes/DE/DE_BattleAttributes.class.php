<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Атрибуты боя, другими словами сколько натырили ресурсов за этот бой
 */
class DE_BattleAttributes extends DE_Base
{
	private $attributes = array();
		
	function __construct($combID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($combID) && $combID>0);
		parent::__construct($combID);
		
		$res = DS_Battle::GetBattleAtrributes($combID);		
		assert(is_array($res)); // && count($res)>0
		
		$this->_importAttributes($res);
		
		$this->_invariant('construct');
	}
	 
	/**
	 * Enter description here...
	 *
	 * @param int $combID
	 * @return DE_BattleAttributes
	 */
	static public function CreateFromBattleID($combID)
	{				
		assert(is_int($combID) && $combID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);						
	}

	public function getAttribute($attributeTypeName, $attributeName)
	{
		$this->_invariant();
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));				
		
		assert(key_exists($attributeTypeName, $this->attributes) && key_exists($attributeName, $this->attributes[$attributeTypeName]));
		
		return $this->attributes[$attributeTypeName][$attributeName]['VALUE'];
	}

	public function createAttribute($attributeTypeName, $attributeName, $value)
	{
		$this->_invariant();
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));				
		
		$ID = DS_Battle::CreateBattleAttributes( $attributeTypeName, $attributeName, $value, $this->getID());
		$val = array(
				'VALUE' => $value,
				'ID' => $ID, 
				'IS_MODIFYED' => false
		); 
		
		$this->attributes[$attributeTypeName][$attributeName] = $val;		
	}
	
	public function setAttribute($attributeTypeName, $attributeName, $value)
	{
		$this->_invariant();
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));		
		
		if (!$this->haveAttribute($attributeTypeName, $attributeName))
		{
			$this->createAttribute($attributeTypeName, $attributeName, (float)$value);
		}
		else 
		{
			$this->attributes[$attributeTypeName][$attributeName]['VALUE'] = (float)$value;
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

	public function deltaAttribute($attributeTypeName, $attributeName, $value)
	{		
		$this->_invariant();
		
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));
		assert(is_float($value));
						
		$this->attributes[$attributeTypeName][$attributeName]['VALUE'] = $this->attributes[$attributeTypeName][$attributeName]['VALUE'] + (float)$value;
		$this->attributes[$attributeTypeName][$attributeName]['IS_MODIFYED'] = true;
	}	
	
	private function _importAttributes($res)
	{
		assert(is_array($res)); // && count($res)>0
		
		foreach($res as $k=>$v)
		{
			$val = array(
				'VALUE' => (float)$v['value'],
				'ID' => $v['id_battlehasattribute'], 
				'IS_MODIFYED' => false
			); 
			$this->attributes[$v['typename']][$v['attributename']] = $val;
		}
	}

	private function _formKey($attributeName, $attributeTypeName)
	{
		assert(!empty($attributeName));
		assert(!empty($attributeTypeName));
		
		return $attributeName.'-'.$attributeTypeName;
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

		foreach($this->attributes as $k=>$v)
		{
			foreach ($v as $key => $value)
			{
				if($value['IS_MODIFYED'] == true)
				{
					if(DS_Battle::UpdateBattleAttribute($this->attributes[$k][$key]['ID'], $this->attributes[$k][$key]['VALUE']))
					{
						$this->attributes[$k]['IS_MODIFYED'] = false;
					}
				}
			}
		}//END FOREACH
	}
		
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();
		//$this->commit();
	}

}//END OF CLASS

?>