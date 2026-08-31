<?php

/**
 * Менеджер формирует список зданий на соте
 *
 */
class ME_CombViewFormBuildings 
{
	/**
	 * Сота для которой формируем список ячеек
	 *
	 * @var DE_Comb
	 */
	static private $comb;
	
	/**
	 * ID части соты на которой находимся.
	 *
	 * @var integer
	 */
	static private $cellTypeID;
	
	static public function Exec(DE_Comb $comb, $cellTypeID)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);
		
		self::$comb = $comb;
		self::$cellTypeID = $cellTypeID;
		
		$outTmp = array();
		
		$buildingsData = DS_Comb::GetAttachedBuildibgsInCombPart(self::$comb->getID(), $cellTypeID);
		foreach ($buildingsData as $k=>$buildingData)
		{
			
			$outLocalTmp = array(
				'PARAMS_COUNT' => 0,
				'CELL_NUM' => 0,
				'BUILDING_ID' => 0,
				'UNKNOWN' => '',
				'STATUS' => null,
				'PERCENT' => null,
				'END_DATE' => null
			);
			
			//Эта сдвижка на единицу происходит из-за того что, в базе храняться номера ячеек с 1, а в клиенте с 0.
			if($cellTypeID == 2)
			{
				$cellsMask = GB_CombCentralViewMask::GetServerBaseMask();
				$outLocalTmp['CELL_NUM'] = $cellsMask[(int)$buildingData['cellnumber']] - 1;	
			}else{
				$outLocalTmp['CELL_NUM'] = (int)$buildingData['cellnumber'] - 1;
			}
			
			
			$outLocalTmp['BUILDING_ID'] = (int)$buildingData['id_building'];
			
			$isActive = $buildingData['active'];
			$isUnderConstruction = $buildingData['underconstruction'];
			$isInActivation = $buildingData['inactivation'];
			$isInUpgrade = $buildingData['inupgrade'];
			$isBusy = $buildingData['busy'];
			$startTimeStr = $buildingData['starttime'];
			$endTimeStr = $buildingData['endtime'];
					
			if($isUnderConstruction == 't' || $isInActivation == 't' || $isInUpgrade == 't')
			{
				$startDate = GB_TypeFactory::CreateBase('GB_Date', $startTimeStr);
				$endDate = GB_TypeFactory::CreateBase('GB_Date', $endTimeStr);
/*					
				$proc = (time() - $startDate->value) / ($endDate->value - $startDate->value) * 100;
					
				if($proc > 100)
				{
					$proc = 100;
				} elseif ($proc < 0) {
					$proc = 0;
				}
*/					
				//$outLocalTmp['PERCENT'] = ($startDate->value + DE_User::CreateForCurrentUser()->getTimeDelta()*3600).'000|'.($endDate->value + DE_User::CreateForCurrentUser()->getTimeDelta()*3600).'000';//number_format($proc, 2, ".", " ");
				//$outLocalTmp['PERCENT'] = ($startDate->value - DE_User::CreateForCurrentUser()->getTimeDelta()*3600).'000|'.($endDate->value - DE_User::CreateForCurrentUser()->getTimeDelta()*3600).'000';//number_format($proc, 2, ".", " ");
				$outLocalTmp['PERCENT'] = $startDate->value.'000|'.$endDate->value.'000';//number_format($proc, 2, ".", " ");

				//$outLocalTmp['END_DATE'] = $endDate->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta());
				//$outLocalTmp['END_DATE'] = $endDate->getGreenwichFormatedFull();
				$outLocalTmp['END_DATE'] = $endDate->getFormatedFull();
			}

			if($isUnderConstruction == 't') {
				$outLocalTmp['STATUS'] = 1;
			}elseif ($isInActivation == 't') {
				if ($isActive == 't') {
					// Если здание деактивируется
					$outLocalTmp['STATUS'] = 3;
				} else {
					// Если здание активируется
					$outLocalTmp['STATUS'] = 2;
				}
			}elseif ($isActive == 'f') {
				$outLocalTmp['STATUS'] = 4;
			}elseif ($isInUpgrade == 't') {
				$outLocalTmp['STATUS'] = 5;
			}

			foreach($outLocalTmp as $k=>$v)
			{
				if(is_null($v))
				{
					unset($outLocalTmp[$k]);
				}
			} 
			
			$outLocalTmp['PARAMS_COUNT'] = count($outLocalTmp) - 1;
			
			$outTmp[] = implode('^', $outLocalTmp);
		}
		$tmp = implode('^', $outTmp);

		return $tmp;
	}
}

?>