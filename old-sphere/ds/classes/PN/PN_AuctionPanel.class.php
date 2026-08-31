<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка аукционов (торговля)
class PN_AuctionPanel extends UA_GetBuildMenu
{
	/**
	 * Данные для вывода менюшки
	 *
	 * @var TI_AuctionPanel
	 */
	private $transportInput;

	/**
	 * Enter description here...
	 *
	 * @var DE_Comb
	 */
	private $combObject;

	/**
	 * Enter description here...
	 *
	 * @var DE_TradeAuctionPanelMainSQL
	 */
	private $mainSQL;

	/**
	 * Колличество лотов на странице
	 *
	 * @var integer
	 */
	private $tradesPerPage = 10;

	/**
	 * Enter description here...
	 *
	 * @var DE_Localization
	 */
	private $localization;

	public function __construct()
	{

		//SU_DebugTools::LogAll('1111111', 'oleg123oleg.log');

		UA_GetBuildMenu::UA_GetBuildMenu();

		$this->combObject = DE_Comb::CreateFromID((int)$this->comb);

		$this->mainSQL = new DE_TradeAuctionPanelMainSQL(DE_World::CreateForCurrentWorld());

		$this->localization = DE_Localization::CreateForCurentLang();
	}

	public function getPanel()
	{
		//Проверяем разрешен ли торговля в мире
		$noTradePeriods = DS_World::GetNoTradePeriod(DE_World::CreateForCurrentWorld()->getID());

		if($noTradePeriods)
		{
			$tmp = $peacePeriod['END_DATE']->value - time();
			$timeToEnd = GB_TypeFactory::CreateBase('GB_TimePeriod', $tmp);
			$messparams[0] = "{b} ".$timeToEnd->getDSFormat()."{/b}";

			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'NO_TRADE_ALERT', $messparams);

			return true;
		}

		$this->transportInput = new TI_AuctionPanel($this->bid, $this->tab);

		$this->_setFilterData();

		//$this->_getTrades();
		SU_OutputHandler::Singleton()->addString(TL_AuctionPanel::FormMenu($this->transportInput), 'xmldata');

		return true;
	}

	private function _getTrades()
	{
		// Это перенесено в класс UA_TradeAuctionFilter
		if(empty($this->sort_place))
		{
			return;
		}

		if($_SESSION["worldtype"] == 'RACE_VS_RACE')
		{
			$this->mainSQL->setRvrFlag(true);
		}
		elseif(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_NON_RVR_INTERRACE_ALLOW_UNITS'))
		{
			$this->mainSQL->setNonRvrUnitsInterRaceFlag(true);
		}

		$this->mainSQL->setTradeDelay((int)DE_WorldSettings::CreateForCurentWorld()->get('TRADE_DELAY'));
		$this->mainSQL->setOrder($this->order);
		$this->mainSQL->setOwnBets($this->own_bets);
		$this->mainSQL->setImidiate((int)$this->immediate);
		$this->mainSQL->setOwnTrades($this->own_trades);
		$this->mainSQL->setSortPlace($this->sort_place);
		$this->mainSQL->setSortType($this->sort_type);

		$this->mainSQL->setSortName($this->sort_name);
		$this->mainSQL->setBuyBy($this->buy_by);

		$this->mainSQL->setComb($this->combObject);
		$this->mainSQL->setPages((int)$this->page, (int)$this->tradesPerPage);
		$this->mainSQL->setTrader($this->traderSearch);
		$this->mainSQL->setPersonal($this->personal, DE_User::CreateFromID((int)$_SESSION['uid'])->getName());

		ME_TradeFormAuctionMainSQL::Exec($this->mainSQL);
 
		$countData = DS_TradeInterface::GetTradeLots($this->mainSQL, 'COUNT_ROWS');

		
		assert(is_array($countData) && count($countData) == 1);
		$rowsCount = (int)$countData[0]['colvo'];

		//Расчет страниц для вывода
		$totalPages = (int)ceil($rowsCount / $this->tradesPerPage);
		$currentPage = (int)$this->page;

		$this->transportInput->setPages($currentPage, $totalPages, $this->tradesPerPage);

		//Значения допольнительных фильтров
		$this->transportInput->setIsOwnBets((int)$this->own_bets);
		$this->transportInput->setIsOwnTrades((int)$this->own_trades);
		$this->transportInput->setNoTrade((int)$this->immediate);
		$this->transportInput->setOrder($this->order);
		$this->transportInput->setPersonal($this->personal);
		$this->transportInput->setTrader($this->traderSearch);

		if($rowsCount == 0)
		{
			return;
		}

		$tradeLots = DS_TradeInterface::GetTradeLots($this->mainSQL, 'MAIN_SELECT');
		foreach($tradeLots as $k=>$tradeData)
		{
			$endDate = GB_TypeFactory::CreateBase('GB_Date', $tradeData['end_date']);

			if($tradeData['is_auction'] == 't')
			{
				$imidiate = 0;
			}else{
				$imidiate = 1;
			}

			$tradeType = DE_TradeType::CreateFromTypeIDForCurrentWorld((int)$tradeData['id_tradetype']);
			$tradeFromArticle = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld($tradeType->getTradeFromCategoryID(), (int)$tradeData['id_article']);
			$tradeToArticle = $tradeType->getToArticle();
			$tradeType->setFromTradeArticle($tradeFromArticle);

			$combTo = DE_Comb::CreateFromID($this->comb);
			$combFrom = DE_Comb::CreateFromID((int)$tradeData['id_seller_comb']);
			$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);
			$amount = (int)$tradeData['amount'];

			$getPrice = '';
			if($combTo->getID() != $combFrom->getID())
			{
				$travelTime = GL_TravelTime::Count($combFrom, $combTo, $speed);

				if($tradeFromArticle->getTradeCategoryID() == 2) //UNITS
				{
					if($this->comb == $tradeData['id_seller_comb'])
					{
						$getPrice = '';
					} else {
						$unit = DE_Unit::CreateFromID((int)$tradeData['id_article']);
						$upkeepValue = GL_FullUnitsMovementCost::CountBuyUnits($combFrom, $combTo, $unit, $amount, GB_TypeFactory::CreateBase('GB_Date', time()));

						$getPrice = "T:".$travelTime->getDSFormat().";".$upkeepValue->getShortPayNotLocalizedList();
					}
				}else{
					if($this->comb == $tradeData['id_seller_comb'])
					{
						$getPrice = '';
					} else {
						$getPrice = "T:".$travelTime->getDSFormat();
					}
				}
			}

			if($tradeData['id_seller'] == $this->uiid)
			{
				$disabled = 1;
			}else{
				$disabled = 0;
			}

			if((int)$tradeData['id_userinstance_bid'] > 0)
			{
				$currentCustomerName = DE_UserInstance::CreateFromID((int)$tradeData['id_userinstance_bid'])->getUser()->getName();
			}else{
				if((int)$tradeData['is_personal'] == 't')
				{
					$currentCustomerName = $tradeData['person_name'];
				}else{
					$currentCustomerName = '';
				}
			}

			if((float)$tradeData['price_bid'] > 0)
			{
				$price = (float)$tradeData['price_bid'];
			}else{
				$price = (float)$tradeData['startprice'];
			}

			if(!$imidiate)
			{
				$prices = GL_TradeCountMinAndMaxPrices::Count($tradeType, (float)$tradeData['amount'], $price);

				if(!empty($currentCustomerName))
				{
					$nextPrice = $prices['NEXT_PRICE'];
				}
				else
				{
					$nextPrice = $price;
				}

				$maxPrice = $prices['MAX_PRICE'];
			}else{
				$nextPrice = 0;
				$maxPrice = 0;
			}

			//Закрыто отображение текущего покупателя если эо не текущий пользователь
			if($currentCustomerName != DE_User::CreateForCurrentUser()->getName())
			{
				if(!empty($currentCustomerName))
				{
					$currentCustomerName = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('LOT_HAVE_CUSTOMER');
				}else{
					$currentCustomerName = ' ';
				}
			}

			$data = array(
				'TRADE_ID' => (int)$tradeData['id'],
			 	'END_DATE' => $endDate->getPgFormat(),
			 	'AMOUNT' => $tradeData['amount'],
				'PRICE' => $price,
				'SELLER_NAME' => DE_UserInstance::CreateFromID((int)$tradeData['id_seller'])->getUser()->getName(),
				'TRADE_BY' => $tradeToArticle->getInternalName(),
				'TRADE_SCOPE' => $tradeData['tradescopename'],
				'IMIDIATE' => $imidiate,
				'LIBNAME' => $tradeFromArticle->getInternalName(),
				'GET_PRICE' => $getPrice,
				'ID_CAT_ON' => (int)$tradeData['id_trade_on_category'],
				'ID_CAT_BY' => (int)$tradeData['id_trade_by_category'],
				'DISABLED' => $disabled,
				'CUSTOMER' => $currentCustomerName,
				'NEXT_PRICE' => $nextPrice,
				'MAX_PRICE' => $maxPrice
			);

			$this->transportInput->setTrade($data);
		}
	}

	private function _setFilterData()
	{
		// Фильтр по скоупам
		$this->_setSortPlace();

		// Фильтр что покупаем
		$this->_setSortType();

		// Фильтр за что покупаем
		$this->_setSortBuy();

		// Фильтры-чекбоксы типа свои лоты, персональные и т.д.
		$this->_setSortFiltersList();

		// Параметры сортировки
		$this->_setSortOrders();
	}

	private function _setSortPlace()
	{
		$allowTradeScopesData = GL_CombAllowedTradeScopes::GetAllScopes($this->combObject);

		foreach($allowTradeScopesData as $k=>$tradeScopeData)
		{
			if($this->sort_place == $tradeScopeData['tradescopename'])
			{
				$isCurrent = 1;
			}else{
				$isCurrent = 0;
			}
			$this->transportInput->setSortPlace($tradeScopeData['tradescopename'], $isCurrent);
		}
	}

	private function _setSortType()
	{
		if(empty($this->sort_type)) //ДЕлаем значение пол умолчанию
		{
		 	$this->sort_type = "RESOURCES";
		}

		$filterLavelData = DS_TradeInterface::GetAuctionFilterLevel();
		foreach($filterLavelData as $k=>$level1Data)
		{
			$sortLevel1ID = (int)$level1Data['id_sort'];
			assert($sortLevel1ID > 0);

			$sortLEvel1Name = (string)$level1Data['sortname'];
			assert(!empty($sortLEvel1Name));

			if($this->sort_type == $sortLEvel1Name)
			{
				$isCurrentSortType = 1;
			}else{
				$isCurrentSortType = 0;
			}

			$filterLevel2Data = DS_TradeInterface::GetAuctionFilterLevel($sortLevel1ID);
			foreach ($filterLevel2Data as $k=>$level2Data)
			{
				$sortLevel2Name = (string)$level2Data['sortname'];
				assert(!empty($sortLevel2Name));

				if(in_array($sortLevel2Name, $this->sort_name) || ($isCurrentSortType == 1 && count($this->sort_name) == 0) || ($isCurrentSortType == 0))
				{
					$isCurrentSortName = 1;
				}else{
					$isCurrentSortName = 0;
				}

				$this->transportInput->setSortTypeNameTmp($sortLevel2Name, $isCurrentSortName);
			}

			$this->transportInput->amountSortTypeNameTo($sortLEvel1Name, $isCurrentSortType);
		}
	}

	private function _setSortBuy()
	{
		$tradesByData = DS_TradeInterface::GetCurrentCourses(DE_World::CreateForCurrentWorld()->getID());
		foreach($tradesByData as $k=>$tardeByData)
		{

			if(in_array($tardeByData['name'], $this->buy_by) || count($this->buy_by) == 0)
			{
				$isCurrent = 1;
			}else{
				$isCurrent = 0;
			}

			$this->transportInput->setTradeBy($tardeByData['name'], $isCurrent);
		}
	}

	private function _setSortFiltersList()
	{
		$filters = DS_TradeInterface::GetTradeMenuFilters();

		foreach ($filters as $k => $filter)
		{
			$this->transportInput->setFilter($filter['text'], (int)$filter['id_filter'], (int)$filter['default_value']);
		}
	}

	private function _setSortOrders()
	{
		$sorting = DS_TradeInterface::GetTradeMenuSorts();

		foreach ($sorting as $k => $s)
		{
			$this->transportInput->setSorting($s['text'], (int)$s['id_filter'], (int)$s['sort_order'], $s['sort_direction']);
		}
	}
}

?>