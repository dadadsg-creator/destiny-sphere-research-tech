<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс для пересчета силы армии в стазисе в звезды
 */
class GL_ArmyPowerToStars
{	
		
	public static function Count($armyPower)
	{	
		assert($armyPower >= 0);		
		
		if($armyPower < 1000)
		{
			$armyPower = 1000;
		}
				
		$stars = log($armyPower/1000, 12);
		assert($stars >= 0);
			
		return ceil($stars);
	}
	
	public static function CountForMenu($armyPower)
	{
		$stars = self::Count($armyPower);
		
		if($stars < 1)
		{
			return (float)1;
		}else{
			return ceil($stars);
		}
	}
}

?>