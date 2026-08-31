<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Распределение удара по группам армии.
 */
class GL_SeparateDemageBetweenUnitGroups
{
	static public function Separate (DE_UnitsGroupInterface $unitGroupTo)
	{
		/* Определяем режим работы с прослоем или без*/
		$separate = DE_WorldSettings::CreateForCurentWorld()->get('SEPARATED_DAMAGE');
		if ($separate == 1)
		{

			//Формула с прослоем
			$seporator = 1 / $unitGroupTo->getArmy()->getUnitsGroupCollection()->countCollection();

		} else {

			//Формула без прослоя
			$seporator = $unitGroupTo->getGroupPower() / $unitGroupTo->getArmy()->getArmyPower();
		}

		return  (float)$seporator;
	}
}

?>