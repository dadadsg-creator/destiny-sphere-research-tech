<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CountPeriodLiveUpkeep
{
	static private $forLog = '';
	
	static public function Count($upkeepPoints, GB_Date $chekTime)
	{
		assert(is_array($upkeepPoints) && count($upkeepPoints)>0);
		
		foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k=>$resourceName)		
		{		
			$result[$resourceName] = self::_countResource($upkeepPoints, $chekTime, $resourceName);	
		}
		
		return $result;		
	}
	
	static private function _countResource($upkeepPoints, GB_Date $chekTime, $resourceName)
	{
		assert(is_array($upkeepPoints) && count($upkeepPoints)>0);
		assert(!empty($resourceName));
		
		$upkeepValue = 0;		
		
		ksort($upkeepPoints);
		reset($upkeepPoints);
		
		foreach($upkeepPoints as $k=>$pointCurent)
		{
			$pointNext = next($upkeepPoints);
			
			assert(key_exists('DATE', $pointCurent));	
			assert(key_exists('UPKEEP_VALUE', $pointCurent));		
			
			if(empty($pointNext))
			{
				$pointNext['DATE'] = $chekTime;
			}
			
			assert(key_exists('DATE', $pointNext));
					
			$turns = self::_getTurns($pointCurent['DATE'], $pointNext['DATE']);
			$delta = $pointCurent['UPKEEP_VALUE'][$resourceName] * $turns;
			$upkeepValue += $delta;
		}
		
		return $upkeepValue;
	}
	
	static private function _getTurns(GB_Date $fromDate, GB_Date $toDate)
	{
		//Ёто затыка на случай когда мы исккуственно перенесли врем€ окончани€ ивента 
		//вли€ющего на трассу апкипа на врем€ раньше чем был последний рефреш.
		
		if($toDate->value >= $fromDate->value)
		{
			$delta = $toDate->value - $fromDate->value;
		}else{
			$delta = 1;
		}		
						
		$turns = $delta / (DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60);
		

		return $turns;
	}
}


?>