<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_ResourceStealing
{

	static public function LoadResource(DE_Battle $battle)
	{
		$combFrom = $battle->getComb();

		$armyCollection = $battle->getAttacker()->getArmiesColletion();
		$armyCollection->resetNext();
		$armyParcipiant = $armyCollection->next();

		//Проверям существует ли еще данная армия.
		if(GB_SingletonObjectStorage::HaveObjectInGraveYard('DE_Army', $armyParcipiant->getArmyID()))
		{
			return;
		}else{
			$army = $armyParcipiant->getArmy();
		}
		
		// Процент вывоза
		$newbStealing = 100;

		// Процент потерь вывоза
		$newbStealingLose = 0;

		// Id атакера и защитника
		$ataker = $battle->getFromComb()->getUserInstanceID();
		$defender = $combFrom->getUserInstanceID();
		
		// Если мы не воюем с игрокрм Destiny Sphere
		if ($ataker != 0 && $defender != 0) {
			// Смотрим разницу в уровнях
			$atakerLevel = DE_Ranking::CreateFromID($ataker)->getLevel();
			$defenderLevel = DE_Ranking::CreateFromID($defender)->getLevel();	
			
			// Если уровень атакера больше уровня защитника
			if ($atakerLevel > $defenderLevel) {
				// Если включена зависимость вывоза ресурсов от разницы в уровнях
				if (DE_WorldSettings::CreateForCurentWorld()->get('STEALING_NEWB_ENABLED') == 1) {
					// Максимальная рассматриваемая разница в уровнях
					$Xa = DE_WorldSettings::CreateForCurentWorld()->get('STEALING_NEWB_MAX_LEVEL_DIFFERENCE');
					// Сколько процентов минимально вывозится при разнице больше максимальной
					$Ya = DE_WorldSettings::CreateForCurentWorld()->get('STEALING_NEWB_MAX');
					// "крутость" графика;
					$A = DE_WorldSettings::CreateForCurentWorld()->get('STEALING_NEWB_RATE');
	
					// Если Уровень Атакера - Уровень Защитника > Xa, то процент вывоза равен константе Ya.
					if (($atakerLevel - $defenderLevel) > $Xa) {
						$newbStealing = $Ya;
					} else {
						// Если 0 < Уровень Атакера - Уровень Защитника <= Xa, то считаем процент вывоза по формуле:
						// Вывоз = А * (Разница в Уровнях - Xa)^2 + Ya;
						$newbStealing = ($A * pow(($atakerLevel - $defenderLevel - $Xa), 2) + $Ya);
					}
				}
	
				// Если включена зависимость потери вывоза ресурсов от разницы в уровнях
				if (DE_WorldSettings::CreateForCurentWorld()->get('STEALING_LOSE_NEWB_ENABLED') == 1) {
					// Потери вывоза. Максимальная рассматриваемая разница в уровнях
					$Xa = DE_WorldSettings::CreateForCurentWorld()->get('STEALING_LOSE_NEWB_MAX_LEVEL_DIFFERENCE');
					// Потери вывоза. Сколько процентов минимально теряется при разнице больше максимальной
					$Ya = DE_WorldSettings::CreateForCurentWorld()->get('STEALING_LOSE_NEWB_MAX');
					// Потери вывоза. "крутость" графика;
					$A = DE_WorldSettings::CreateForCurentWorld()->get('STEALING_LOSE_NEWB_RATE');
	
					// Если Уровень Атакера - Уровень Защитника > Xa, то процент вывоза равен константе Ya.
					if (($atakerLevel - $defenderLevel) > $Xa) {
						$newbStealingLose = $Ya;
					} else {
						// Если 0 < Уровень Атакера - Уровень Защитника <= Xa, то считаем процент вывоза по формуле:
						// Вывоз = А * (Разница в Уровнях - Xa)^2 + Ya;
						$newbStealingLose = ($A * pow(($atakerLevel - $defenderLevel - $Xa), 2) + $Ya);
					}
				}
			}
		}
		
		$battle->getAttributes()->setAttribute('CURRENT_AMOUNT', 'STEALING_NEWB_TAX', (float)$newbStealing);
		$battle->getAttributes()->setAttribute('CURRENT_AMOUNT', 'STEALING_LOSE_NEWB_TAX', (float)$newbStealingLose);

		$stealedResources = GL_ResourceStealing::Count($combFrom, $army);
		
		foreach(GB_ResourcesList::$stealingResources as $k => $resourceName)
		{
			// Количество ресурсов, доступных для грабежа
			$value = $stealedResources->getStealedResources($resourceName);
		
			if($value == 0)
			{
				continue;
			}

			// Количество ресурсов, списываемых с соты (с поравкой на разницу в уровнях)
			$deltaValue = $newbStealing*$value/100;
			
			// Количество ресурсов, которые будут даны аткару, с учетом налога на младший уровень защитника
			$stealValue = $deltaValue - $newbStealingLose*$deltaValue/100;
			
			// Фактический вывоз армией
			$army->getAttributes()->setAttribute('CURRENT_AMOUNT', $resourceName, (float)$stealValue);

			// Отписывам ресурсы от соты с корой грабим
			$combFrom->getAttributes()->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)-$deltaValue);
			// Захватчик разграбил
			$battle->getAttributes()->setAttribute('CURRENT_AMOUNT', $resourceName, (float)$stealValue);
		}
	}

	//Разгружаем что есть в армии от грабежа и от апкипа
	static public function UnloadResource(DE_Comb $combTo, DE_Comb $combFrom, DE_army $army)
	{
		$armyAttributes = $army->getAttributes();

		$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());

		foreach (GB_ResourcesList::$stealingResources as $attributeName)
		{
			if(!$armyAttributes->haveAttribute('CURRENT_AMOUNT', $attributeName))
			{
				continue;
			}

			//Получаем налог на грабеж
			$stealingTax = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_STEALING_TAX_'.$attributeName);
			assert(0 <= $stealingTax && $stealingTax <= 1);

			$value = $armyAttributes->getAttribute('CURRENT_AMOUNT', $attributeName);

			if ($stealingTax != 0) {
				//Логируем потерю ресов
				DE_LogRerourceStealingTax::CreateFromID($combTo->getID())->log($combFrom, $attributeName, $stealingTax, $value, $stealingTax*$value, $checkDate);

				$value = $value - $stealingTax*$value;
			}

			$combTo->getAttributes()->deltaAttribute('CURRENT_AMOUNT', $attributeName, (float)$value);

			$armyAttributes->setAttribute('CURRENT_AMOUNT', $attributeName, (float)0);
		}

	}

	//Количество награбленных ресурсов
	static public function GetLocolizedStealingResourcesList(DE_Army $army)
	{
		$armyAttributes = $army->getAttributes();

		$out['stealing'] = array();
		$out['loosed'] = array();
		$out['loaded'] = array();

		foreach (GB_ResourcesList::$stealingResources as $attributeName)
		{
			if(!$armyAttributes->haveAttribute('CURRENT_AMOUNT', $attributeName))
			{
				continue;
			}
			$resourseName = DE_Resource::CreateFromName($attributeName)->getLocolizedShortName();
			$stealing = $armyAttributes->getAttribute('CURRENT_AMOUNT', $attributeName);
			$out['stealing'][] = $resourseName.": ".GB_Integer::Format($stealing, ' ');

			//Получаем налог на грабеж
			$stealingTax = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_STEALING_TAX_'.$attributeName);
			assert(0 <= $stealingTax && $stealingTax <= 1);

			if ($stealingTax != 0) {

				$loosed = $stealingTax*$stealing;
				$loaded = $stealing - $loosed;

				$out['loaded'][] = $resourseName.": ".GB_Integer::Format($loaded, ' ');
				$out['loosed'][] = $resourseName.": ".GB_Integer::Format($loosed, ' ');
			} else {
				$out['loaded'][] = $resourseName.": ".GB_Integer::Format($stealing, ' ');
			}
		}

		if(count($out['stealing']) == 0)
		{
			$out['stealing'] = " - ";
		}else{
			$out['stealing'] = implode (' ', $out['stealing']);
		}

		if(count($out['loosed']) == 0)
		{
			$out['loosed'] = " - ";
		}else{
			$out['loosed'] = implode (' ', $out['loosed']);
		}

		if(count($out['loaded']) == 0)
		{
			$out['loaded'] = " - ";
		}else{
			$out['loaded'] = implode (' ', $out['loaded']);
		}

		return $out;
	}

}



?>