<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_FullUnitsMovementCost
{
	static public function CountHideArmy(DE_Army $army, GB_DatePeriod $datePeriod)
	{
		$turnTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		
		$travelTurns100 = round(($datePeriod->getEndDate()->value - $datePeriod->getStartDate()->value) / $turnTime, 5);
		
		$liveResources = GL_LiveUpkeep::CountArmy($army);

		$result = new DE_UnitsMoveCostFullValue;	

		foreach($liveResources as $resourceName => $needValue)
		{
			$val = ceil($needValue * $travelTurns100);

			assert($val >= 0);

			$result->setPay($resourceName, $val);
		}

		return $result;	
	}
	
	
	static public function CountPath(DE_Army $army, DE_Comb $combFrom, DE_Comb $combTo, GB_MoveSpeed $speed, GB_Date $startDate, $mode)
	{
		assert($mode == 'ATTACK' || $mode == 'TRANSFER' || $mode == 'DEFEND');
		
		$turnTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		
		$speed100 = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);
		
		// считаем время туда
		$travelTime100 = GL_TravelTime::Count($combFrom, $combTo, $speed100, $startDate);
		assert($travelTime100->value > 0);
		
		$travelTurns100 = round($travelTime100->value / ($turnTime), 5);
		assert($travelTurns100 > 0);
		
		$moveEnergy = GL_MoveUpkeep::Count($army->getArmyPower(), $travelTime100, $mode, $speed);
		
		$liveResources = GL_LiveUpkeep::CountArmy($army);

		$result = new DE_UnitsMoveCostFullValue;	

		foreach($liveResources as $resourceName => $needValue)
		{
			if($resourceName == 'STASIS_ENERGY')
			{
				$val = ceil($moveEnergy + $needValue * $travelTurns100);
			} else {
				$val = ceil($needValue * $travelTurns100);
			}

			assert($val >= 0);

			$result->setPay($resourceName, $val);
		}

		return $result;	
	}
	
	
	/**
	 * РАсчт сколько ресурсов надо на атаку. 
	 *
	 * @param DE_Army $army
	 * @param DE_Comb $combFrom
	 * @param DE_Comb $combTo
	 * @param GB_MoveSpeed $speed
	 * @return unknown
	 */
	static public function CountAttack(DE_Army $army, DE_Comb $combFrom, DE_Comb $combTo, GB_MoveSpeed $speed, $startDate = null)
	{
		assert(is_null($startDate) || $startDate instanceof GB_Date);
		if(is_null($startDate))
		{
			$startDate = GB_TypeFactory::CreateBase('GB_Date', time());
		}

		$turnTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;

		$costTo = self::CountPath($army, $combFrom, $combTo, $speed, $startDate, 'ATTACK');
		
		// считаем время туда
		$speed100 = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);
		$travelTime100 = GL_TravelTime::Count($combFrom, $combTo, $speed100, $startDate);
		assert($travelTime100->value > 0);
		
		$baseRoundTime = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_ROUND_TIME_MULTIPLIER') * $turnTime;
		$returnDate = GB_TypeFactory::CreateBase('GB_Date', $startDate->value + $travelTime100->value + $baseRoundTime);
		$costFrom = self::CountPath($army, $combFrom, $combTo, $speed, $returnDate, 'ATTACK');

		$result = new DE_UnitsMoveCostFullValue;	
		
		$liveResources = GL_LiveUpkeep::CountArmy($army);

		foreach($liveResources as $resourceName => $needValue)
		{
			$valTo = $costTo->getPay($resourceName);
			$valFrom = $costFrom->getPay($resourceName);

			assert($valTo >= 0);
			assert($valFrom >= 0);

			$result->setPay($resourceName, $valTo + $valFrom);
		}

		return $result;	
	}	

	/**
	 * РАсчет сколько ресурсов надо вернуть после отмены атаки.
	 * возвращаются все ресурсы для полета обратно + часть ресурсов для полета туда
	 *
	 * @param DE_Army $army
	 * @param DE_Comb $combFrom
	 * @param DE_Comb $combTo
	 * @param GB_TimePeriod $passedTime
	 * @param GB_TimePeriod $travelTime
	 * @param GB_Date $startDate
	 * @return unknown
	 */
	static public function CountCancelAttack(DE_Army $army, DE_Comb $combFrom, DE_Comb $combTo, GB_TimePeriod $passedTime, GB_TimePeriod $travelTime, GB_Date $startDate)
	{
		$turnTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		$passedTurns = round($passedTime->value / ($turnTime), 5);

		if($passedTurns ==0)
		{
			$passedTurns = 0.00001;
		}
		
		$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)$army->getAttributes()->getAttribute('CURRENT_AMOUNT', 'MOVE_SPEED'));
		$moveEnergy = GL_MoveUpkeep::Count($army->getArmyPower(), $passedTime, 'ATTACK', $speed);		
		$liveResources = GL_LiveUpkeep::CountArmy($army);

		$result = self::CountAttack($army, $combFrom, $combTo, $speed, $startDate);	

		foreach($liveResources as $resourceName => $needValue)
		{
			// умнажаем на 2 т.к. тратим на дарогу туда и обратно
			if($resourceName == 'STASIS_ENERGY')
			{
				$val = 2*ceil($moveEnergy + $needValue * $passedTurns);
			} else {
				$val = 2*ceil($needValue * $passedTurns);
			}

			if(($result->getPay($resourceName) - $val) < 0)
			{
				$result->setReturn($resourceName, 0);
			} else {
				$result->setReturn($resourceName, $result->getPay($resourceName) - $val);
			}
		}

		return $result;
	}

	/**
	 * РАсчет затрат ресурсов на постановку арми на защиту.
	 *
	 * @param DE_Army $army
	 * @param DE_Comb $combFrom
	 * @param DE_Comb $combTo
	 * @param GB_MoveSpeed $speed
	 * @return unknown
	 */
	static public function CountDefend(DE_Army $army, DE_Comb $combFrom, DE_Comb $combTo, GB_MoveSpeed $speed, GB_Date $startDate = null)
	{
		return self::CountAttack($army, $combFrom, $combTo, $speed, $startDate);
	}		

	/**
	 * РАсчет затрат ресурсов для снятия армии с защиты.
	 *
	 * @param unknown_type $army
	 * @param unknown_type $combFrom
	 * @param unknown_type $combTo
	 * @param unknown_type $passedTime
	 * @param unknown_type $travelTime
	 * @return unknown
	 */
	static public function CountCancelDefend(DE_Army $army, DE_Comb $combFrom, DE_Comb $combTo, GB_TimePeriod $passedTime, GB_TimePeriod $travelTime, GB_Date $startDate)
	{
		return self::countCancelAttack($army, $combFrom, $combTo, $passedTime, $travelTime, $startDate);	
		/*
		$turnTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		$returnPart = 1 - 2*$passedTime->value / $travelTime->value;

		if($returnPart < 0)
		{
			$returnPart = 0;
		}
		
		$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)$army->getAttributes()->getAttribute('CURRENT_AMOUNT', 'MOVE_SPEED'));

		$result = self::CountPath($army, $combFrom, $combTo, $speed, $startDate, 'ATTACK');

		foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k=>$resourceName)
		{
			$result->setReturn($resourceName, (int)ceil($result->getPay($resourceName) * $returnPart));
		}

		return $result;
		*/
	}

	/**
	 * РАссчет затра ресурсов что бы выстватьб юнитов в торгователь
	 *
	 * @param DE_UserInstance $userInstance
	 * @param GB_TimePeriod $sellPeriod
	 * @param DE_Unit $unit
	 * @param unknown_type $amount
	 * @return unknown
	 */
	static public function CountSellUnit(DE_UserInstance $userInstance, GB_TimePeriod $sellPeriod, DE_Unit $unit, $amount)
	{
		$turns = $sellPeriod->value / DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') / 60;
		assert($turns >= 0);

		$liveResources = GL_LiveUpkeep::CountUnits($unit, $amount, $userInstance);

		$result = new DE_UnitsMoveCostFullValue;	

		foreach($liveResources as $resourceName => $needValue)
		{			
			$val = ceil($needValue * $turns);						
			assert($val >= 0);						
			$result->setPay($resourceName, $val);
		}		

		return $result;	
	}

	/**
	 * РАсчет сколько ресурсов надо вернуть продавцу после покупки юнита выстваленного на продажу.
	 *
	 * @param DE_UserInstance $userInstance
	 * @param GB_TimePeriod $sellPeriod
	 * @param GB_TimePeriod $passedPeriod
	 * @param DE_Unit $unit
	 * @param unknown_type $amount
	 * @return unknown
	 */
	static public function CountBayUnitReturnPart(DE_UserInstance $userInstance, GB_TimePeriod $sellPeriod, GB_TimePeriod $passedPeriod,  DE_Unit $unit, $amount)
	{
		$returnPart = self::_countReturnPart($passedPeriod, $sellPeriod);

		$result = self::CountSellUnit($userInstance, $sellPeriod, $unit, $amount);		

		foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
		{
			$result->setReturn($resourceName, ceil($result->getPay($resourceName) * $returnPart));	
		}

		return $result;	
	}

	/**
	 * РАсчет сколько ресурсов надо потратьи на доставку купленого юнита
	 *
	 * @param DE_Comb $combFrom
	 * @param DE_Comb $combTo
	 * @param DE_Unit $unit
	 * @param unknown_type $amount
	 * @return unknown
	 */
	static public function CountBuyUnits(DE_Comb $combFrom, DE_Comb $combTo, DE_Unit $unit, $amount, GB_Date $date)
	{
		$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);

		$travelTime = GL_TravelTime::Count($combFrom, $combTo, $speed, $date);
		assert($travelTime->value > 0);

		$travelTurns = round($travelTime->value / (DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60), 5);
		assert($travelTurns > 0);

		$moveEnergy = GL_MoveUpkeep::Count($unit->getUnitPower(), $travelTime, 'TRANSFER', $speed);		

		$liveResources = GL_LiveUpkeep::CountUnits($unit, $amount, $combTo->getUserInstance());

		$result = new DE_UnitsMoveCostFullValue;	

		foreach($liveResources as $resourceName => $needValue)
		{
			if($resourceName == 'STASIS_ENERGY')
			{
				$val = ceil($moveEnergy + $needValue * $travelTurns);
			} else {
				$val = ceil($needValue * $travelTurns);	
			}

			assert($val >= 0);

			$result->setPay($resourceName, $val);
		}

		return $result;	
	}

	/**
	 * Рассчет затрат ресурсов на пересулку юнито вна колонию.
	 *
	 * @param DE_Unit $unit
	 * @param unknown_type $amount
	 * @param DE_Comb $combFrom
	 * @param DE_Comb $combTo
	 * @param GB_MoveSpeed $speed
	 * @return unknown
	 */
	static public function CountTransfer(DE_Unit $unit, $amount, DE_Comb $combFrom, DE_Comb $combTo, GB_MoveSpeed $speed)
	{
		settype($amount, 'integer');
		assert($amount > 0);

		$speed100 = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);

		$travelTime100 = GL_TravelTime::Count($combFrom, $combTo, $speed100);
		assert($travelTime100->value > 0);

		$travelTurns100 = round($travelTime100->value / (DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60), 5);
		assert($travelTurns100 > 0);

		$moovePower = $unit->getUnitPower() * $amount;
		$mooveEnergy = GL_MoveUpkeep::Count($moovePower, $travelTime100, 'TRANSFER', $speed);

		$liveResources = GL_LiveUpkeep::CountUnits($unit, $amount, $combFrom->getUserInstance());

		$result = new DE_UnitsMoveCostFullValue;

		foreach($liveResources as $resourceName => $needValue)
		{
			if($resourceName == 'STASIS_ENERGY')
			{
				$val = ceil($mooveEnergy + $needValue * $travelTurns100);
			} else {
				$val = ceil($needValue * $travelTurns100);
			}

			assert($val >= 0);

			$result->setPay($resourceName, $val);
		}

		return $result;	
	}	

	/**
	 * РАсчитываетьяс какую часть ресурсов надо вернуть при отмене 
	 *
	 * @param GB_TimePeriod $passedTime
	 * @param GB_TimePeriod $travelTime
	 * @return unknown
	 */
	static private function _countReturnPart(GB_TimePeriod $passedTime, GB_TimePeriod $travelTime)
	{
		$returnPart =  1 - ( $passedTime->value / $travelTime->value );

		$minReturnPart = (float)DE_WorldSettings::CreateForCurentWorld()->get('MIN_CANCEL_ARMY_RESOURS_RETURN_PART');

		assert($minReturnPart < 1 && $minReturnPart > 0);

		if($returnPart < $minReturnPart)
		{
			$returnPart = $minReturnPart;
		}

		return $returnPart;
	}
}
?>