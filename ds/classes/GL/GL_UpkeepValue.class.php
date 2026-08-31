<?php $CVS_Version = '$Revision$ ; $Date$';




/**
 * Класс для расчета стоимости перемещения армии
 */
class GL_UpkeepValue extends GameBase {
	
		
	static private $allowModes = array (
		'DEFEND', //Защита
		'ATTACK', //Атака	
		'TRANSFER', //Пересылка		
	);
		
	static public function Count($power, GB_TimePeriod $trevelTime, $mode)
	{ // 
//		assert();
//		DebugTools::logAll(var_export($power, 1),'ZYAAAAAAAAAAAAAAAAAAAAAA.log');
		assert(is_int($power) || is_double($power) || is_float($power) && $power > 0);

		assert (in_array ($mode, self::$allowModes));
		$worldSettings = DE_WorldSettings::CreateForCurentWorld();		
		
		$turnTime = GB_TypeFactory::CreateBase('GB_Integer', round($worldSettings->get('TURN_TIME')*60));
		
		switch ( $mode ) {
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

		if ( $moveCost <= 0 ){
			return GB_TypeFactory::CreateConstant('GB_Integer', 0);
		}
		
		//Переводим время пути из секунлдв турны		
		$trevelTimeInTurns = $trevelTime->value / $turnTime->value;
		
		$needs_energy = round ( $power * $trevelTimeInTurns*2 * $moveCost );
		
		if($needs_energy < 0)
		{
			$needs_energy = 0;
		}
									
		switch ( $mode ) {
			case 'DEFEND':
				$out = $needs_energy;
				break;
			case 'ATTACK':
				$out = $needs_energy * 2;
				break;
			case 'TRANSFER':
				$out = $needs_energy;
				break;
			default:
		}//END OF SWITCH
		
		return GB_TypeFactory::CreateConstant('GB_Integer', round($out));		
	}//END OF METHOD	
}//END OF CLASS

?>