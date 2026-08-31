<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2010/03/10 09:52:23 $';

/**
 * Класс игровой логики. Рассчитывает сколько данная армия может украсть с соты и сколько бует сохранено в хранилищах.
 */

class GL_ResourceStealing
{
	/**
	 * Основной метод
	 *
	 * @param DE_Comb $comb
	 * @param DE_Army $army
	 * @return DE_ResourcesForStealing
	 */
	static public function Count(DE_Comb $comb, DE_Army $army)
	{

		//Создали объект результата.
		$resourcesForStealing = new DE_ResourcesForStealing($army, $comb);

		// Если это не колония игрок находиться под защитой
		if($comb->getRingNumber() != 4 && $comb->getUserInstance()->haveRandomDefend())
		{
			return $resourcesForStealing;
		}		
		
		if(!DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_STEAL_RESOURCE_WITH_NULL_DAMAGE') && $army->haveNullDamage())
		{
			return $resourcesForStealing;
		}
		
		//Рассчитали сколько ресурсов сохранено, сколько доступно для грабежа.
		self::_countResourcesForSteeling($resourcesForStealing);

		//Рассиытваем сколько смогли унести.
		self::_countStealResources($resourcesForStealing);

		return $resourcesForStealing;
	}

	/**
	 * Рассчитывем сколько ресусром можно украсть и сколько должно быть сокрыто в хранилищах.
	 *
	 * @param DE_ResourcesForStealing $resourcesForStealing
	 */
	static private function _countResourcesForSteeling(DE_ResourcesForStealing $resourcesForStealing)
	{

		$liveUpkeep = GL_LiveUpkeep::CountComb($resourcesForStealing->getComb());

		$combAttributes = $resourcesForStealing->getComb()->getAttributes();		
		foreach (GB_ResourcesList::$stealingResources as $k => $resourceName)
		{
	
			//Затыка потчащая отрецательный ресы. Ситуация на самом деле штатаная так что лучше не убирать отсюда.
			/*if($combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName) < 0)
			{
				$combAttributes->setAttribute('CURRENT_AMOUNT', $resourceName, (float)0);
			}*/

			//Колличество ресов на соте текущее.
			$curentAmount = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true);

			//Если сейчас обрабатываем ресурс который апкиповый то к нему еще пллючуе мпрожиточнф минимум
			if(in_array($resourceName, GB_ResourcesList::$unitsLiveUpkeepResources))
			{
				$armyUpkeepDelta = $liveUpkeep[$resourceName] * DE_WorldSettings::CreateForCurentWorld()->get('ARMY_LIVE_UPKEEP_RESERV_IN_TURNS');
			}else{
				$armyUpkeepDelta = 0;
			}
			
			$mayStoreResource = 0;
			
			if(DE_WorldSettings::CreateForCurentWorld()->get('RESOURCE_STORING_TURNS') == 1)
			{
				$tp = GL_CountCombProductionOfResource::Count($resourcesForStealing->getComb(), $resourceName);
				$turn = $combAttributes->getAttribute('STORAGE_FACTOR', $resourceName,  true);

				//Количество ресурсa которое можно спрятать
				$mayStoreResource += floor($tp * $turn + $armyUpkeepDelta);	
			}
			
			if(DE_WorldSettings::CreateForCurentWorld()->get('RESOURCE_STORING_ABSOLUT') == 1)
			{
				$mayStoreResource += $combAttributes->getAttribute('STORAGE_FACTOR_ABSOLUT', $resourceName,  true);
			}

			//колличество ресурса которое будет спратанно
			if($curentAmount >= $mayStoreResource)
			{
				$storeResource = $mayStoreResource;
			}else{
				$storeResource = $curentAmount;
			}
			
/*if($resourceName == 'FOOD')
{
$str = "
resourceName = $resourceName
curentAmount = $curentAmount
storeResource = $storeResource
mayStoreResource = $mayStoreResource
";	
SU_DebugTools::LogAll($str, 'Yoooooooo.log');	
}*/
			//Уставновили ресуср на сохранение
			$resourcesForStealing->setResourceForStore($resourceName, $storeResource);

			//Колличетсва ресурса которого мона спиреть.
			$resourcesForStealing->setResourceForSteal($resourceName, $curentAmount - $storeResource);
		}

	}

	/**
	 * Загрузка приоритетного ресурса под заваязку.
	 *
	 * @param DE_ResourcesForStealing $resourcesForStealing
	 * @param unknown_type $priorityResourceName
	 */
	static private function _loadPriorityResource(DE_ResourcesForStealing $resourcesForStealing, $priorityResourceName)
	{
		assert(in_array($priorityResourceName, GB_ResourcesList::$stealingResources));

		//Сколько всего ресурса приоритеного у нас в стазисе
		$currentStazis = $resourcesForStealing->getResourceForStealInStazis($priorityResourceName);

		//Объект данного ресурса.
		$resource = DE_Resource::CreateFromName($priorityResourceName);


		if($currentStazis >= $resourcesForStealing->getCurentCapicity())
		{
			//Если ресурса больше чем места, то грузим пока место не кончиться.
			$delta = $resource->convertFromStazis($resourcesForStealing->getCurentCapicity());
		}else{
			//Если места больше то грузим весь ресурс.
			$delta = $resource->convertFromStazis($currentStazis);
		};

		$resourcesForStealing->deltaStealedResource($priorityResourceName, $delta);
	}

	/**
	 * Загрузка всех ресурсов равномерно.
	 *
	 * @param DE_ResourcesForStealing $resourcesForStealing
	 */
	static private function _loadAllResource(DE_ResourcesForStealing $resourcesForStealing)
	{

		//Продолжаем до тех пор пока есть еще что грабить или же не кончилось место в армии.
		while($resourcesForStealing->countNotEmptyResourcesForSteal()>0 && $resourcesForStealing->getCurentCapicity()>$resourcesForStealing->countNotEmptyResourcesForSteal())
		{
			//Сколько сейчас места можно выделить под отдельный ресусрс
			$storageFactorForResource = floor($resourcesForStealing->getCurentCapicity() / $resourcesForStealing->countNotEmptyResourcesForSteal());

			//Минимальное колличество ресурса в стазисе которое ркасть можно
			$minResourceStazis = $resourcesForStealing->getResourceForStealInStazis($resourcesForStealing->getMinResourceForStealName());

			if($minResourceStazis > $storageFactorForResource)
			{
				//Если места меньше чем реса, то грузим максимально по месту.
				$loadStazisDelta = $storageFactorForResource;
			}else{
				//Если места больше то грузим весь ресурс
				$loadStazisDelta = $minResourceStazis;
			}

			//Загружаем ресусры пропорционально
			foreach(GB_ResourcesList::$stealingResources as $k=>$resourceName)
			{
				//Если ресурс уже кончился то мимо него проходим.
				if($resourcesForStealing->getResourceForSteal($resourceName) == 0)
				{
					continue;
				}

				//Грузим ресурс
				$resourcesForStealing->deltaStealedResource($resourceName, DE_Resource::CreateFromName($resourceName)->convertFromStazis($loadStazisDelta));
			}
		}
	}

	/**
	 * Расчет краденных ресурсов.
	 *
	 * @param DE_ResourcesForStealing $resourcesForStealing
	 */
	static private function _countStealResources(DE_ResourcesForStealing $resourcesForStealing)
	{

		//Временная затыка дле тестов
		if($resourcesForStealing->getArmy()->getAttributes()->haveAttribute('CURRENT_AMOUNT', 'RESOURCE_FOR_STEALING'))
		{
			$loadType = $resourcesForStealing->getArmy()->getAttributes()->getAttribute('CURRENT_AMOUNT', 'RESOURCE_FOR_STEALING');
		}else{
			$loadType = 'ALL_RES';
		}

		//Еще одна временная затыка. Так как попадались случаи пустого параметр 'RESOURCE_FOR_STEALING'. Т.е. параметр есть , занчения нет.
		if(empty($loadType))
		{
			$loadType = 'ALL_RES';
		}

		assert($loadType == 'ALL_RES' || in_array($loadType, GB_ResourcesList::$stealingResources));

		if($loadType == 'ALL_RES')
		{
			self::_loadAllResource($resourcesForStealing);
		}else{
			self::_loadPriorityResource($resourcesForStealing, $loadType);
			self::_loadAllResource($resourcesForStealing);
		}
	}
}
?>