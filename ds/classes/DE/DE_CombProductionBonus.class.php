<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Бонус соты на производство
 *
 */
class DE_CombProductionBonus extends DE_Base 
{
	private $comb;
	
	private $combAttributes;
	
	private $bonus = array();

	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);
		parent::__construct($id);				
		
		$this->comb = DE_Comb::CreateFromID($id);
		
		$this->combAttributes = $this->comb->getAttributes();
		
		foreach(GB_ResourcesList::$allCombResources as $k=>$resourceName)
		{
			if($this->combAttributes->haveAttribute('BONUS_RELATIVE', $resourceName))
			{
				$this->bonus[$resourceName]['BONUS_RELATIVE'] = $this->combAttributes->getAttribute('BONUS_RELATIVE', $resourceName);
			}else{
				$this->bonus[$resourceName]['BONUS_RELATIVE'] = 0;
			}
			
			if($this->combAttributes->haveAttribute('BONUS_ABSOLUTE', $resourceName))
			{
				$this->bonus[$resourceName]['BONUS_ABSOLUTE'] = $this->combAttributes->getAttribute('BONUS_ABSOLUTE', $resourceName);
			}else{
				$this->bonus[$resourceName]['BONUS_ABSOLUTE'] = 0;
			}			
		}
		
/*$str = "
	".var_export($this->bonus, 1)."
";
DebugTools::LogUser('SmallGoblin', $str);*/

		$this->_importBonuses();

		$this->_invariant('construct');
	}//END OF METHOD

	private function _importBonuses()
	{
		$artBonuses = DS_Artefact::GetCombAlianceAndComplexAttribute($this->comb);
		
		foreach($artBonuses as $k=>$v)
		{
			assert(key_exists($v['attributename'], $this->bonus));
			assert(key_exists($v['typename'], $this->bonus[$v['attributename']]));
			
			$this->bonus[$v['attributename']][$v['typename']] += $v['value'];
		}
		
		$brb = DE_WorldSettings::CreateForCurentWorld()->get('RESEARCH_BASE_BONUS');
		$scienceBonus = DS_Science::GetScienceBonus($this->comb);		
		foreach($scienceBonus as $k=>$v)
		{
			assert(key_exists($v['attributename'], $this->bonus));		
			
			$researchBonus = ($brb / $v['exchangerate']) * ($v['level'] - 1);	

			$this->bonus[$v['attributename']]['BONUS_RELATIVE'] += $researchBonus;
		}
	}
		
	public function getBonus ($atrributeName, $bonusType)
	{
		$this->_invariant();

		assert(key_exists($atrributeName, $this->bonus));
		assert(key_exists($bonusType, $this->bonus[$atrributeName]));
		
		return $this->bonus[$atrributeName][$bonusType];
	}
	
	static public function CreateFromCombID($id)
	{
		assert(is_int($id) && $id>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);								
	}		
			
	public function delete()
	{
		GB_SingletonObjectStorage::DeleteObject($this);
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
		
		assert($this->comb instanceof DE_Comb);
		assert($this->combAttributes instanceof DE_CombAttributes);
		assert(is_array($this->bonus) && count($this->bonus)>0);
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
}


?>