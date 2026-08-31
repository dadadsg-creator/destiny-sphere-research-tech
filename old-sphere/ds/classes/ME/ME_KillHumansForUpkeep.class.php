<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Убиваем людей на соте если еды не хватает
 *
 */
class ME_KillHumansForUpkeep
{
	static public function Exec(DE_Comb $comb, $needFood, $haveFood, $unitFoodUpkeep, $combUnitsCount)
	{
		assert(is_float($needFood) && $needFood > 0);
		assert(is_float($haveFood));		
		assert(is_float($unitFoodUpkeep) && $unitFoodUpkeep > 0);		
		assert(is_int($combUnitsCount) && $combUnitsCount > 0);		
		assert($haveFood < $needFood);
		
		// Для системного сообщения 
		$kill['WORKER'] = 0;
		$kill['SCIENTIST'] = 0;
		$kill['ARCHEOLOGIST'] = 0;
				
		$common = DS_Common::CreateForCurentDB();
		
		$combAttributes = $comb->getAttributes();

		//Часть которая должна умереть. Почти правильный вариант. Убивает слишком много народу, если долго был минус на соте и игрок не обновлялся
		$countToDie1 = ceil(($needFood - $haveFood) / $unitFoodUpkeep);
		if($countToDie1 <= 0)
		{
			return array();
		}
		
		//Часть которая должна умереть. Почти неправильный вариант. Убивает слишком много народу, если на соте был минус, а игрок быстро обновился
		$countToDie2 = ceil($combUnitsCount * $haveFood / $needFood);

		if($countToDie2 <= 0)
		{
			return array();
		}
		
		$countToDie = min($countToDie1, $countToDie2);
		
		// убиваем в начале рабочих
		$workerAmount = $combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER');
		$workerDelta = min($countToDie, $workerAmount);
		
		// Запысываем для сообщения сколько убили рабочих 
		$kill['WORKER'] = (int)$workerDelta;
		
		$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'WORKER', -(float)$workerDelta);
		//Логируем помирание юнитов.
		DE_LogUnitsDeathOnComb::CreateFromID($comb->getID())->log('UPKEEP', DE_Unit::CreateFromName('WORKER'), $workerAmount, $workerDelta);

		if($countToDie > $workerAmount)
		{
			$countToDie -= $workerAmount;
		} else {
			return $kill;
		}
		
		$partToDie = min(1.0, $countToDie / ($combUnitsCount - $workerAmount));
		
		// убиваем то что на соте
		$personTypes = array('SCIENTIST', 'ARCHEOLOGIST');				
		foreach($personTypes as $k=>$attributeName)
		{
			$amount = $combAttributes->getAttribute('CURRENT_AMOUNT', $attributeName);
			
			$delta = ceil($amount * $partToDie);
			
			// Запысываем для сообщения сколько убили ученых и архов 
			$kill[$attributeName] = (int)$delta;
		
			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, -(float)$delta);
			
			//Логируем помирание юнитов.
			DE_LogUnitsDeathOnComb::CreateFromID($comb->getID())->log('UPKEEP', DE_Unit::CreateFromName($attributeName), $amount, $delta);
		}
	
		//Убиваем в экспедициях
		$sql = "SELECT * FROM ds_archeologicalteam WHERE id_comb = ".$comb->getID();				
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));				
				
		$newArchCount = 0;
		for($i=0; $i<sizeof($res); $i++) 
		{			
			if(empty($res[$i]['id_eventqueue'])) 
			{
				continue;
			}
				
			$amount = (int)$res[$i]['amount'];
			$newAmount = $amount - ceil($amount * $partToDie);
					
			if($newAmount <= 0 )
			{
				$newAmount = 1;
			}
			
			// Добавляем для сообщения убитых архов
			$kill['ARCHEOLOGIST'] += (int)($amount - $newAmount);
			
			//Логируем помирание юнитов.
			DE_LogUnitsDeathOnComb::CreateFromID($comb->getID())->log('UPKEEP', DE_Unit::CreateFromName('ARCHEOLOGIST'), $amount, $amount - $newAmount);
					
			$newArchCount += $newAmount;
					
			$sql = "UPDATE ds_archeologicalteam SET amount = $newAmount WHERE id_comb = ".$comb->getID()." AND teamnumber = ".$res[$i]['teamnumber'];														
			$res2 = $common->executeQuery($sql);
			assert(empty($common->errormsg));
					
			$eventOptions = DE_EventQueueAttributes::CreateFromEventID((int)$res[$i]['id_eventqueue']);
			$eventOptions->setAttribute('CountOfArcheologist', $newAmount);					
		}

		if($newArchCount > 0) 
		{
			$combAttributes->setAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', (float)$newArchCount);										
		}
		return $kill;
	}
}
?>