<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Разделение демеджа внутри группы обороняющегося составной.
 */
class GL_SeparateDemageBetweenDefendUnitGroups
{
	static public function Separate (DE_CombDefendUnitGroup $combDefendUnitsGroup, DE_UnitsGroup $unitsGroup)
	{							
		$res = $unitsGroup->getAmount() / $combDefendUnitsGroup->getSummaryAmmount();
		return (float)$res;
	}
}

?>