<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс для расчета стоимости перемещения армии
 */
class GL_MoveUpkeep
{
			
	static private $allowModes = array (
		'DEFEND', //Защита
		'ATTACK', //Атака	
		'TRANSFER', //Пересылка		
	);
		
	/**
	 * Вычисление энергии необхадимой на перемещение юнитов.
	 *
	 * @param int $power - Юнит ыпересчитанный в стазис
	 * @param GB_TimePeriod $trevelTime - Время движение при скорости 100%
	 * @param string $mode - Режим 'DEFEND', 'ATTACK', 'TRANSFER' 
	 * @param GB_MoveSpeed $speed - Скорость дивжения.
	 * @return int
	 */
	static public function Count($power, GB_TimePeriod $trevelTime, $mode, GB_MoveSpeed $speed)
	{  
		assert(is_int($power) || is_double($power) || is_float($power) && $power > 0);		

		assert (in_array ($mode, self::$allowModes));
		
		$worldSettings = DE_WorldSettings::CreateForCurentWorld();		
		
		$turnTime = $worldSettings->get('TURN_TIME') * 60;
		
		switch($mode) 
		{
			case 'DEFEND':
				$moveCost = $worldSettings->get('UNIT_MOVEMENT_COSTS');
				break;
			case 'ATTACK':
				$moveCost = $worldSettings->get('UNIT_MOVEMENT_COSTS');
				break;
			case 'TRANSFER':
				$moveCost = $worldSettings->get('UNIT_TRANSFER_COSTS');
				break;
			default:
		}//END OF SWITCH			

		if($moveCost <= 0)
		{
			return (int)0;
		}
		
		//Переводим время пути из секунлдв турны		
		$trevelTimeInTurns = $trevelTime->value / $turnTime;
		if($trevelTimeInTurns <= 0)
		{
$msg = '
	travelTime <= 0
	trevelTime->value = '.$trevelTime->value.'
';
SU_DebugTools::LogAll($msg, __CLASS__, 1, 0);
			$trevelTimeInTurns = 0.01;
		}
		
		$needs_energy = $power * $trevelTimeInTurns * $moveCost;		

		if($needs_energy <= 0)
		{
			ds_error("Uncorrect moove energy value! Test1 ($needs_energy, $power, $trevelTimeInTurns, $moveCost)");
		}		
			
		if($needs_energy < 0)
		{
			$needs_energy = 0;
		}				
		
		if($speed->value <= 1)
		{
			$needs_energy = $needs_energy * ($speed->value + 0.8) * (1/1.8);
		}else{
			$needs_energy = $needs_energy * (((($speed->value - 1) * ($worldSettings->get('MIN_SPEED_ACCELERATION_TIME_TURNS') - 1)) / 0.5) + 1);					
		}
		
		if($needs_energy <= 0)
		{
			ds_error("Uncorrect moove energy value! Test2 ($needs_energy, ".$speed->value.", ".$worldSettings->get('MIN_SPEED_ACCELERATION_TIME_TURNS').")");
		}		
		
		$out = ceil($needs_energy);
		
		assert($out >= 0);
		
		return 	$out;
	}//END OF METHOD	
}//END OF CLASS

?>