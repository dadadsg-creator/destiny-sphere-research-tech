<?php $CVS_Version = '$Revision$ ; $Date$';
class GL_ParalelTrainWarUnitsLoad
{
	static public function Count($arrayOld, $count)
	{
		$outputArray = array();
		foreach ($arrayOld as $key => $value)
		{
			$outputArray[$key] = 0;
		}

		$strLog = '';
		while ($count > 0)
		{
			reset($arrayOld);
			asort($arrayOld);

			$strLog .= "arrayOld\n";
			$strLog .=print_r($arrayOld, 1);
			$strLog .="\n";

			$current = (int)current($arrayOld);
			$strLog .= "current = ".$current."\n";

			foreach ($arrayOld as $key => $value)
			{
				if (sizeof($arrayOld) == 1 && (int)$value == 0)
				{
					$arrayOld[$key] = (int)$value;
					$newArray[$key] = $count;
					$nextValue = $count;
					break;
				}

				$value = (int)$value;
				if ($value > $current)
				{
					$nextValue = $value - $current;
					$strLog .= "value = ".$value."\n";
					break;
				}
				$newArray[$key] = $value;
			}


			$strLog .= "newArray\n";
			$strLog .=print_r($newArray, 1);
			$strLog .="\n";


			!isset($nextValue) ? $nextValue = 0 : '';
			$need = (int)$nextValue * count($newArray);

			if ($count > $need && $count > 1*count($newArray))
			{
				if ($need == 0)
				{
					$nextValue = floor($count/count($newArray));
				}

				foreach ($newArray as $key => $value)
				{
					if (!key_exists($key, $outputArray))
					{
						$outputArray[$key] = $nextValue;
					} else {
						$outputArray[$key] += $nextValue;
					}
					$arrayOld[$key] = $arrayOld[$key] + $nextValue;
					$count = $count - $nextValue;

					$strLog = "
".print_r($arrayOld, 1)."
".print_r($newArray, 1)."
".print_r($outputArray, 1)."
nextValue = $nextValue
count = $count
need = $need
";
				}
//SU_DebugTools::logAll($strLog, 'GL_ParalelTrainWarUnitsLoad.log');
//assert(false);
			} else {
//assert(false);
				for ($i=1;$count > 0;)
				{
					foreach ($newArray as $key => $value)
					{
						if ($count < $i)
						{
							break;
						}
						if (!key_exists($key, $outputArray))
						{
							$outputArray[$key] = $i;
						} else {
							$outputArray[$key] += $i;
						}
						$count -=$i;
					}
				}
			}

			$newArray = array();
		}

		return $outputArray;
	}

	/**
	 * Формируем ответный массив, слепок, из недостающего количества
	 * юнитов для полной равномерной загрузки юнитов, во все выделенные здания
	 *
	 * @param Array $array
	 */
	static private function _checkNeedForLoad($array)
	{
		arsort($array);
		reset($array);
		$maxValue = current($array);
		foreach ($array as $key => $currentValue)
		{
			$newValue = $maxValue - $currentValue;
			$newArray[$key] = $newValue;
		}
		return $newArray;
	}

	static private function _fillArray($array, $count)
	{

	}
}

//$arrayOld = array(321,60,54,72,258,642);




//print_r($arrayOld);
//print_r($outputArray);
//echo "array_sum: ".array_sum($outputArray);



?>