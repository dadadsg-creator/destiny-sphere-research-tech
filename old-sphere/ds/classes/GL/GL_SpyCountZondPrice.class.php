<?php

/**
 * Ќабор функций, дл€ работы с прогрессивной ценой на юниты (следующий стоит дороже)
 * сделано было дл€ шпионских юнитов, но никакой проверки дл€ какого юнита рассчитываем цену нет, 
 * так что, при желании, можно спокойно использовать дл€ любых других юнитов
 *
 */
class GL_SpyCountZondPrice
{
	/**
	 * –асчет стоимости строительства юнитов
	 *
	 * @param DE_Comb $comb
	 * @param DE_Unit $unit
	 * @param int $count
	 * @return array
	 */
	static public function Count(DE_Comb $comb, DE_Unit $unit, $count, $currentAmountDelta = 0)
	{
		assert(is_int($count) && $count > 0);
		
		$currentCount = (int)$comb->getAttributes()->getAttribute('CURRENT_AMOUNT', $unit->getName());
		$currentCount += (int)$comb->getAttributes()->getAttribute('AWAY_AMOUNT', $unit->getName());
		$currentCount += (int)DS_UnitTrain::GetCountUnitInProduction($unit, $comb);
		$currentCount += $currentAmountDelta;
		$priceIncreaseFactor = 1.0 + (float)DE_WorldSettings::CreateForCurentWorld()->get($unit->getName().'_PRODUCTION_COST_INCREASE');
		
		$price = array();
		
		// «атыка
		if ($currentCount < 0) {
			$currentCount = 0;
		}
		
		foreach (GB_ResourcesList::$allBuildResources as $res)
		{			
			$baseCost = (float)$unit->getAttributes()->getAttribute('PRODUCTION_COSTS', $res);		
			if($baseCost == 0)
			{
				$price[$res] = 0;
				continue;
			}
			$costTmp = self::_getProgressionSum($baseCost, $priceIncreaseFactor, $currentCount + $count);
			$costTmp -= self::_getProgressionSum($baseCost, $priceIncreaseFactor, $currentCount);
			$price[$res] = ceil($costTmp);
		}
		
		return $price;
	}
	
	/**
	 * –асчет максимального кол-ва зондов доступного дл€ строительства
	 * от текущего кол-ва ресурсов на сот
	 *
	 * @param DE_Comb $comb
	 * @param DE_Unit $unit
	 */
	static public function GetMaxByResource(DE_Comb $comb, DE_Unit $unit, $currentAmountDelta = 0)
	{
		assert(is_int($currentAmountDelta));
		
		$currentCount = (int)$comb->getAttributes()->getAttribute('CURRENT_AMOUNT', $unit->getName());
		$currentCount += (int)$comb->getAttributes()->getAttribute('AWAY_AMOUNT', $unit->getName());
		$currentCount += (int)DS_UnitTrain::GetCountUnitInProduction($unit, $comb);
		$currentCount += $currentAmountDelta;
		if($currentCount < 0)
		{
			$currentCount = 0;
		}
		
		$priceIncreaseFactor = 1.0 + (float)DE_WorldSettings::CreateForCurentWorld()->get($unit->getName().'_PRODUCTION_COST_INCREASE');
		
		$max = array();
		
		foreach (GB_ResourcesList::$allBuildResources as $res)
		{
			$resource = $comb->getAttributes()->getAttribute('CURRENT_AMOUNT', $res);
			$price = (float)$unit->getAttributes()->getAttribute('PRODUCTION_COSTS', $res);
			if($price == 0)
			{
				continue;
			}
			
			// случай когда следующий зонд стоит меньше предыдущего, не предусмотрен
			if($priceIncreaseFactor > 1)
			{
				$basePrice = self::_getProgressionSum($price, $priceIncreaseFactor, (int)$currentCount);
				$max[] = log((1 + (($resource + $basePrice)/$price)*($priceIncreaseFactor - 1.0)), $priceIncreaseFactor) - $currentCount;
			} else {
				$max[] = floor($resource / $price);
			}
		}
		
		// провер€ем военный опыт дл€ зонда исследовател€
		if($unit->getName() == 'SPY_ZOND_EXPLORER')
		{
			$price = (float)DE_WorldSettings::CreateForCurentWorld()->get('SPY_ZOND_EXPLORER_BATTLE_XP_COST');
			$resource = DE_BattleXP::CreateFromID($comb->getUserInstanceID())->getXP();
			
			if($priceIncreaseFactor > 1)
			{
				$basePrice = self::_getProgressionSum($price, $priceIncreaseFactor, (int)$currentCount);
				$max[] = log((1 + (($resource + $basePrice)/$price)*($priceIncreaseFactor - 1.0)), $priceIncreaseFactor) - $currentCount;
			} else {
				$max[] = floor($resource / $price);
			}
		}
		
		return floor(min($max));
	}
	
	/**
	 * —умма геометрической прогрессии
	 * @link http://ru.wikipedia.org/wiki/√еометрическа€_прогресси€
	 *
	 * @param float $start
	 * @param float $coef
	 * @param int $count
	 * @return float
	 */
	static public function _getProgressionSum($start, $coef, $count)
	{
		assert(is_float($start) && $start > 0);
		assert(is_float($coef) && $coef > 0);
		assert(is_int($count) && $count >= 0);
		
		if($count == 0)
		{
			return 0;
		}
		
		if($coef == 1.0)
		{
			return $start * $count;
		}
		
		return $start * ((1 - pow($coef, $count)) / (1 - $coef));
	}
}

?>