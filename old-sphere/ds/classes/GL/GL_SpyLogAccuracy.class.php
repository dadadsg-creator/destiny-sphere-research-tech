<?php

/**
 * Отпределение точности лога разведки по результату боя
 *
 */
class GL_SpyLogAccuracy
{
	const EMPTY_LOG = 0;
	
	const LOW = 1;
	
	const MIDDLE = 2;
	
	const FULL = 3;
	
	/**
	 * Точность для атакующего
	 *
	 * @param array $battleResult
	 * @return string
	 */
	static public function GetForAttacker(array $battleResult)
	{
		assert(key_exists('ROUND', $battleResult));
		assert(key_exists('WINNER', $battleResult));
		assert(key_exists('REASON', $battleResult));
		
		// выиграл атакующий - получает полный лог
		if($battleResult['WINNER'] == 'A')
		{
			return self::FULL;
		}
		
		// выиграл защитник - смотрим раунды
		switch ($battleResult['ROUND'])
		{
			// если в первом раунде
			case 1:
				// атакующий отправлялся на один раунд
				if($battleResult['REASON'] == 'ONE_ROUND' || $battleResult['REASON'] == 'NO_ATTACK_1')
				{
					return self::EMPTY_LOG;
				} else {
					return self::LOW;
				}
				break;
			
			// второй раунд
			case 2:
				return self::MIDDLE;
				break;
					
			// третий раунд
			case 3:
				return self::FULL;
				break;
		}
	
		return self::EMPTY_LOG;
	}
	
	/**
	 * Точность для защитника
	 *
	 * @param array $battleResult
	 * @return string
	 */
	static public function GetForDefender(array $battleResult)
	{
		assert(key_exists('ROUND', $battleResult));
		assert(key_exists('WINNER', $battleResult));
		assert(key_exists('REASON', $battleResult));
		
		// смотрим раунды
		switch ($battleResult['ROUND'])
		{
			// защиты не было
			case 0:
				return self::EMPTY_LOG;
				break;
			
			// 1 раунд
			case 1:
				// атакующий уничтожен
				if($battleResult['WINNER'] == 'D') {
					return self::FULL;
				}
				
				// атакующий отправлялся на один раунд
				// И ПРОИГРАЛ????!!
				return self::LOW;
				break;
		
			// 2 раунд
			case 2:
				return self::LOW;
				break;
				
			// 3 раунд
			case 3:
				return self::FULL;
				break;
		}
		
		return self::EMPTY_LOG;
	}
}

?>