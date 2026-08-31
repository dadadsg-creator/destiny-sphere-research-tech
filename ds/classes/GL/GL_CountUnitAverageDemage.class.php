<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс игровой логики для рассчета значения демеджа юнита на мин и макс его значениях.
 */
class GL_CountUnitAverageDemage
{
	static public function Count($demageMin, $demageMax)
	{
		$random = DE_WorldSettings::CreateForCurentWorld()->get("UNIT_RANDOM_DAMAGE");
		
		assert($demageMax >= $demageMin);
		
		if($random)
		{
			$rand1 = mt_rand($demageMin, $demageMax);
			$rand2 = mt_rand($demageMin, $demageMax);
			return (float)(($rand1 + $rand2)/2);
		} else {
			return (float)(($demageMax - $demageMin) / 2) + $demageMin;
		}
	}
	
	/**static public function Count($demageMin, $demageMax)
	{
		assert($demageMax >= $demageMin);						
		return (float)(($demageMax - $demageMin) / 2) + $demageMin;
	}*/
}


?>