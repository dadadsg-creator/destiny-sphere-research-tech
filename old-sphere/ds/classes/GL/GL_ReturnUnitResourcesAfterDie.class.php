<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * РАссчет скольбко надо вернуть ресурсов после уничтожения армии по апкипу или отмены строкитльства
 *
 */
class GL_ReturnUnitResourcesAfterDie
{	
	static public $returnResources = array('KADERIUM', 'NANO_CRYSTAL', 'FOOD', 'PRODIUM', 'STASIS_ENERGY');	
	
	static public function CountUnitGroup(DE_UnitsGroup $unitsGroup, $killDelta)
	{						
		$amount = $unitsGroup->getAmount() * $killDelta;	
		return self::CountUnit($unitsGroup->getUnit(), $amount);
	}
	
	static public function CountUnit(DE_Unit $unit, $amount)
	{		
		assert(is_numeric($amount) && $amount >= 0);

		$KILL_UNITS_RETURN_PART = (float)DE_WorldSettings::CreateForCurentWorld()->get('KILL_UNITS_RETURN_PART');
		
		$productionCost = $unit->getAttributes()->getProductionCost();

		$return = array();
		foreach(self::$returnResources as $k=>$resource)
		{
			if(key_exists($resource, $productionCost))
			{
				$return[$resource] = $productionCost[$resource] * $amount * $KILL_UNITS_RETURN_PART;
			}					
		}				

		return $return;
	}
}
?>