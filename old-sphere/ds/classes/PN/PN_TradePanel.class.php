<?php $CVS_Version = '$Revision: 1.14 $ ; $Date: 2009/04/01 10:15:19 $';

// закладка торговли (выставление товара на торги)

class PN_TradePanel extends UA_GetBuildMenu
{
	/**
	 * Данные для вывода менюшки
	 *
	 * @var TI_TradePanel
	 */
	private $transportInput;

	/**
	 * ОБъект текущего мира
	 *
	 * @var DE_World
	 */
	private $worldObject;

	public function __construct()
	{
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	public function getPanel()
	{

		//Проверяем разрешен ли торговля в мире
		$noTradePeriods = DS_World::GetNoTradePeriod(DE_World::CreateForCurrentWorld()->getID());
		if($noTradePeriods)
		{
			$tmp = $noTradePeriods['END_DATE']->value - time();
			$timeToEnd = GB_TypeFactory::CreateBase('GB_TimePeriod', $tmp);
			$messparams[0] = "{b} ".$timeToEnd->getDSFormat()."{/b}";

			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'NO_TRADE_ALERT', $messparams);

			return true;
		}

		$this->transportInput = new TI_TradePanel($this->bid, $this->tab);
		$this->worldObject = DE_World::CreateForCurrentWorld();

		$turnTime = (float)(DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60);
		$this->transportInput->setTurnTime($turnTime);

		// Если в мире установлены торговые коридоры
		if (DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_TRANDS') == 1) {
			// устанавливаем минимальное количество ресов на продажу
			$this->transportInput->setMinquntity(100);
		} else {
			$this->transportInput->setMinquntity(1);
		}
		
		// Устанавливаем максимально количество турнов, на которые можно выставлять лоты
		$this->transportInput->setTime2trade((int)DE_WorldSettings::CreateForCurentWorld()->get('TRADE_MAX_TURN'));

		$this->transportInput->setTradeFlag('TRADE_USE_PERSONAL_TRADES', (int)DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_PERSONAL_TRADES'));
		$this->transportInput->setTradeFlag('TRADE_USE_TAX', (int)DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_TAX'));
		$this->transportInput->setTradeFlag('TRADE_USE_TRANDS', (int)DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_TRANDS'));
		$this->transportInput->setTradeFlag('TRADE_ANONYMOUS_SELL', (int)DE_WorldSettings::CreateForCurentWorld()->get('TRADE_ANONYMOUS_SELL'));

		$this->setCurrentCoursesAndUpperMatrixTitle();
		$this->setMatrixLeftTitleAndRows();
		$this->setTradeFrom();
		$this->setCombAllowedTradeScopes();

		SU_OutputHandler::Singleton()->addString(TL_TradePanel::FormMenu($this->transportInput), 'xmldata');

		return true;
	}


	private function setCombAllowedTradeScopes()
	{
		//$tradeScopesData = DS_TradeInterface::GetCombAllowedTradeScopes(DE_Comb::CreateFromID((int)$this->comb));
		$tradeScopesData = GL_CombAllowedTradeScopes::GetAllScopes(DE_Comb::CreateFromID((int)$this->comb));
		assert(is_array($tradeScopesData) && count($tradeScopesData) > 0);

		foreach($tradeScopesData as $k => $tradeScopeData)
		{
			$modifier = '';
			switch((int)$tradeScopeData['id_tradescope'])
			{
				case 1:
					$modifier = 'DISABLE_COMPLEX_TRADING';
					break;
				case 2:
					$modifier = 'DISABLE_RING_TRADING';
					break;
				case 3:
					$modifier = 'DISABLE_INTERRING_TRADING';
					break;
				case 4:
					$modifier = 'DISABLE_ALLIANCE_TRADING';
					break;
			}

			// trade scope запрещен в настройках мира
			if(!empty($modifier) && DE_WorldSettings::CreateForCurentWorld()->get($modifier))
			{
				continue;
			}

			$this->transportInput->setAllowedTradeScope((int)$tradeScopeData['id_tradescope'], $tradeScopeData['tradescopename']);
		}
	}

	private function setCurrentCoursesAndUpperMatrixTitle()
	{
		$currentCourses = DS_TradeInterface::GetCurrentCourses($this->worldObject->getID());

		foreach($currentCourses as $k=>$courseData)
		{
			$this->transportInput->setCourse($courseData['name'], 0);

			$this->transportInput->setMatrixUpperTitle
			(
			(int)$courseData['id_tradecategory'],
			(int)$courseData['id_article'],
			(int)$courseData['id_article'],
			$courseData['name']
			);
		}
	}

	private function setMatrixLeftTitleAndRows()
	{
		$data = DS_TradeInterface::GetMatrixLeftTitle();


		foreach($data as $k=>$data)
		{

			$tradeCategoryID = (int)$data['id_trade_on_category'];
			assert($tradeCategoryID > 0);

			$tradeTypeID = (int)$data['id_trade_on_type'];
			assert($tradeTypeID > 0);
			$this->transportInput->setMatrixLeftTitle($tradeCategoryID, $tradeTypeID);

			$rowData = DS_TradeInterface::GetMatrixRow($tradeCategoryID, $tradeTypeID, $this->worldObject->getID());

			foreach ($rowData as $k=>$v)
			{
				if($v['is_allowed'] == 't')
				{
					$isAllowed = 1;
				}else{
					$isAllowed = 0;
				}

				if($v['id_trade_by_category'] != 4) { //Если это не зотоло и серебро
					if($isAllowed) $isAllowed = DS_Science::GetTradeAbility($_SESSION['uiid'], (int)$v['id_trade_on_category'], (int)$v['id_trade_on_type']);
					if($isAllowed) $isAllowed = DS_Science::GetTradeAbility($_SESSION['uiid'], (int)$v['id_trade_by_category'], (int)$v['id_trade_by_type']);
				}

				$minPriceDelta = (float)$v['min_price_delta'];
				$maxPriceDelta = (float)$v['max_price_delta'];

				$taxBase = (float)$v['tax'];
				$premiumTaxDelta = DE_WorldSettings::CreateForCurentWorld()->get('TRADE_PREMIUM_TAX_DELTA');

				if(DE_User::CreateFromID((int)$_SESSION['uid'])->isProfy())
				{
					$tax = $taxBase + $premiumTaxDelta;
				}else{
					$tax = $taxBase;
				}

				if($tax < 0)
				{
					$tax = 0;
				}

				if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_TAX') == 0)
				{
					$tax = 'X';
				}else{
					settype($tax, 'float');
				}

				if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_TRANDS') == 0)
				{
					$minPriceDelta = 'X';
					$maxPriceDelta = 'X';
				}

				$this->transportInput->setMatrixDataRow($isAllowed, $minPriceDelta, $maxPriceDelta, $tax);
			}


			$this->transportInput->attachMatrixRow();
		}
	}

	private function setTradeFrom()
	{
		$comb = DE_Comb::CreateFromID((int)$this->comb);
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

		//Русурсы
		foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
		{
			$amount = floor($combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName));
			$resourceID = DS_Misc::GetResourceIDByName($resourceName);

			$courses = DS_TradeInterface::GetTradeCoursesForResources($resourceID, DE_World::CreateForCurrentWorld());


			$this->transportInput->setTradeFrom(1, $resourceID, $resourceID, $amount, $courses, $resourceName);
		}

		//Юниты
		$unitGroupsCollection = DE_Army::CreateHomeArmyByCombID($comb->getID())->getUnitsGroupCollection(true);
		$unitGroupsCollection->resetNext();

		while($unitGroup = $unitGroupsCollection->next())
		{
			$unit = $unitGroup->getUnit();
			$amount = floor($unitGroup->getAmount());

			$liveUpkeep = GL_LiveUpkeep::CountUnit($unit, 1);

			$upkeepData = array();
			foreach($liveUpkeep as $resourceName => $upkeep)
			{
				$upkeepData[$resourceName] = $upkeep;
			}

			//			$tradeArticle = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(2, $unit->getID());

			$courses = DS_TradeInterface::GetTradeCourseForUnits($unit->getID(), DE_World::CreateForCurrentWorld());

			if(!empty($courses))
			{
				$this->transportInput->setTradeFrom(2, $unit->getClassNumber(), $unit->getID(), $amount, $courses, $unit->getName(), $upkeepData);
			}
		}

		//Артефакты
		$artefactsData = DS_TradeInterface::GetArtefactsOnCombStoreWithGrouping($comb, DE_World::CreateForCurrentWorld());
		foreach($artefactsData as $k=>$artefactData)
		{
			$artefactID = (int)$artefactData['id_artefact'];
			assert($artefactID > 0);

			$probabilityID = (int)$artefactData['id_artefactprobability'];
			assert($probabilityID > 0);

			$amount = (float)$artefactData['amount'];
			assert($amount > 0);

			$datum = "";
			if($artefactData['immortal'] == 'f') {
				$sql = sprintf ( "select ltl.text
						from ds_localizedtextlanguage ltl,
						ds_systemmessage sm
						where ltl.id_localizedtext = sm.id_localizedtext
						and ltl.id_language = %d
						and sm.messagename = 'ARTEFACT_DEATH_TIME'", $this->langId );

				$loc_death_text_res = $this->common->getResultArray ( "system_message_finished_id_language_".$this->langId, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				$loc_death_text = $loc_death_text_res[0]['text'];

				list($d,$m,$y) = explode('.', $artefactData['birth_time']);
				$datum .= mktime(0, 0, 0, $m, $d, $y);
				list($d,$m,$y) = explode('.', $artefactData['death_time']);
				$datum .= '|'.mktime(0, 0, 0, $m, $d, $y);
				
				$death_message = sprintf(str_replace('%', '%s', $loc_death_text), $artefactData['death_time']);
				
				$datum = ' datum="'.$datum.'" death_message="'.$death_message.'" ';
			}
				
			$courses = DS_TradeInterface::GetTradeCourseForArtefacts($artefactID, DE_World::CreateForCurrentWorld());

			if(!empty($courses))
			{
				$this->transportInput->setTradeFrom(3, $probabilityID, $artefactID, $amount, $courses, '', null, $datum);
			}
		}
	}
}

?>