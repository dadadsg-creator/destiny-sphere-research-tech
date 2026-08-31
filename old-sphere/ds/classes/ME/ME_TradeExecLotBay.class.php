<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Менеджер реализует покупку товара
 *
 */
class ME_TradeExecLotBay
{
	/**
	 * Объект самого лота который покупаем
	 *
	 * @var DE_TradeLot
	 */
	static private $tradeLot;
	
	/**
	 * Дата покупки
	 *
	 * @var GB_Date
	 */
	static private $bayDate;
	
	/**
	 * Объект результата
	 *
	 * @var DE_TradeExecBayResult
	 */
	static private $result;
	
	/**
	 * РАссчитаный время движения между сотами
	 *
	 * @var GB_TimePeriod
	 */
	static private $travelTime;
	
	/**
	 * Рассчитаные даты для ивента
	 *
	 * @var GB_DatePeriod
	 */
	static private $eventDatesPeriod;
	
	/**
	 * Анонимный покупатель
	 *
	 * @var int
	 */
	static private $buyAnonymous;
	
	/**
	 * Собственно само исполнение
	 *
	 * @param DE_TradeLot $tradeLot
	 * @param GB_Date $bayDate
	 * @param int $buyAnonymous
	 * @return DE_TradeExecBayResult
	 */
	static public function Exec(DE_TradeLot $tradeLot, GB_Date $bayDate, $buyAnonymous)
	{
		assert($buyAnonymous == 1 || $buyAnonymous == 0);
		self::$buyAnonymous = $buyAnonymous;
		
		//Проверяем что лот готов к покупке
		assert($tradeLot->haveCurrentCustomer());
				
		//инициализируем класс
		self::_initClass($tradeLot, $bayDate);
		
		//Рассчитываем налог с продажи		
		self::$tradeLot->setTaxSumm(GL_TradeCountLotTaxSumm::Count(self::$tradeLot));
		
		//Расчитываем времена для ивентов
		self::_countEventTimes();
		
		//Если имеем дело с юнитами то надо обработать апкип
		$tradeFromCategoryName = DS_Trade::GetTradeCategoryNameByID(self::$tradeLot->getTradeType()->getTradeFromCategoryID());	
		if($tradeFromCategoryName == 'UNIT')
		{
			//Снимаем ресурсы за жисть свежекупленных юнитов
			self::_getUpkeepResorces();
			
			//Если лот купили раньше чем он закнчился, то 
			if(self::$tradeLot->getEndDate()->value > self::$bayDate->value)
			{
				self::_returnUpkeepResources();
			}
		}
		
		//Форимруем запись в логе торговли
		ME_TradeAddHistory::Exec($tradeLot, $bayDate);
		
		//Создаем ивент доставки товара
		self::_createGoodDeliveryEvent();
				
		//Для оплаты деньгами мы не создаем ивента доставки а доставляем мнгновенно их
		$tradeToCategoryName = DS_Trade::GetTradeCategoryNameByID(self::$tradeLot->getTradeType()->getTradeToCategoryID());	

		//за что купили
		if($tradeToCategoryName == 'MONEY')
		{
			// кредиты
			self::_execMoneyTransfer();
		}else{
			// ресурсы
			self::_createPaymentDeliveryEvent();
		}				
		
		//А теперь удаляем лот и его ивент
		self::$tradeLot->delete();			
		self::$tradeLot->getLotEvent()->setIsTrash(true);				
		
		return self::$result;
	}	
	
	/**
	 * Возврщаем недоеденную тушенку от солдат из ларька
	 *
	 */
	static private function _returnUpkeepResources()
	{
		$unit = DE_Unit::CreateFromID(self::$tradeLot->getArticleID());
		$combToAttributes = self::$tradeLot->getSellerComb()->getAttributes();
		
		$tradePeriod = GB_TypeFactory::CreateConstant('GB_TimePeriod', self::$tradeLot->getEndDate()->value - self::$tradeLot->getStartDate()->value);
		$tradePeriodLeft = GB_TypeFactory::CreateConstant('GB_TimePeriod', self::$bayDate->value - self::$tradeLot->getStartDate()->value);
						
		$returnUpkeep = GL_FullUnitsMovementCost::CountBayUnitReturnPart(self::$tradeLot->getSeller(), $tradePeriod, $tradePeriodLeft, $unit, self::$tradeLot->getAmount());						
		foreach (GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
		{
			$combToAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)($returnUpkeep->getReturn($resourceName)));

			self::$result->setReturnUpkeepResource($resourceName, $returnUpkeep->getReturn($resourceName));		
		}		
	}
	
	/**
	 * Снимаем с покупателя ресусры за апкип юнитов
	 *
	 */
	static private function _getUpkeepResorces()
	{
		// за аукцион апкип на юниты снимается при ставке, здесь уже не нужно его снимать
		if(self::$tradeLot->getIsAuction())
		{
			return;
		}
		
		$unit = DE_Unit::CreateFromID(self::$tradeLot->getArticleID());
		
		$combToAttributes = self::$tradeLot->getCurrentCustomerComb()->getAttributes();
		
		$upkeepValue = GL_FullUnitsMovementCost::CountBuyUnits(self::$tradeLot->getSellerComb(), self::$tradeLot->getCurrentCustomerComb(), $unit, self::$tradeLot->getAmount(), GB_TypeFactory::CreateBase('GB_Date', time()));
		foreach (GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
		{
			if($combToAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true) >= $upkeepValue->getPay($resourceName))
			{					
				$combToAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)(-$upkeepValue->getPay($resourceName)));
				
				self::$result->setUpkeepResource($resourceName, $upkeepValue->getPay($resourceName));			
			}else{
				throw new ME_TradeExecLotBuyException('NOT_ENOUGHT_RESOURCES_FOR_BUY_THIS_UNITS');
			}
		}
	}
	
	static private function _countEventTimes()
	{
		//Рассчитываем время движения				
		$mooveSpeed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);
		self::$travelTime = GL_TravelTime::Count(self::$tradeLot->getSellerComb(), self::$tradeLot->getCurrentCustomerComb(), $mooveSpeed, self::$bayDate);			
				
		//ЗАдали время ивента это фактически врем ядвижения
		$dateTo = GB_TypeFactory::CreateBase('GB_Date', self::$bayDate->value + self::$travelTime->value);
		self::$eventDatesPeriod = new GB_DatePeriod(self::$bayDate, $dateTo);
	}
	
	static private function _createGoodDeliveryEvent()
	{		
		$participants[] = self::$tradeLot->getCurrentCustomer()->getID();
			
		$eventParams['ID_SellerComb'] = self::$tradeLot->getSellerComb()->getID();
		$eventParams['ID_CustomerComb'] = self::$tradeLot->getCurrentCustomerComb()->getID();
		$eventParams['ID_Category'] = self::$tradeLot->getTradeType()->getTradeFromCategoryID();
		$eventParams['ID_Article'] = self::$tradeLot->getArticleID();
		$eventParams['Amount'] = self::$tradeLot->getAmount();
		$eventParams['ID_Trade'] = self::$tradeLot->getID();		
		
		$eventQueue = DE_EventQueue::CreateInDB('TRADING_DELIVERY_GOOD', $participants, $eventParams, self::$eventDatesPeriod);		
		self::$result->setDeliveryGoodEvent($eventQueue);
		
		//Если это артефакт то прилепляем еще и артефакты
		if(self::$tradeLot->getTradeType()->getTradeFromCategoryID() == 3)
		{
			ME_TradeArtefactInstancesOnTradeLotWork::AddArtfactsToEventFromTradeLot(self::$tradeLot->getID(), $eventQueue->getID());
		}
	}
	
	static private function _createPaymentDeliveryEvent()
	{
		$participants[] = self::$tradeLot->getSeller()->getID();
		
		$eventParams['ID_SellerComb'] = self::$tradeLot->getSellerComb()->getID();
		$eventParams['ID_CustomerComb'] = self::$tradeLot->getCurrentCustomerComb()->getID();
		$eventParams['ID_Trade'] = self::$tradeLot->getID();
		// Информация о цене
		$eventParams['ID_Category'] = self::$tradeLot->getTradeType()->getTradeToCategoryID();
		$eventParams['ID_Article'] = self::$tradeLot->getTradeType()->getToArticle()->getArticleID();
		$eventParams['Price'] = self::$tradeLot->getPriceWithTaxForSendToSeller();
		// Информация о товаре
		$eventParams['ID_CategoryFrom'] = self::$tradeLot->getTradeType()->getTradeFromCategoryID();
		$eventParams['ID_ArticleFrom'] = self::$tradeLot->getTradeType()->getFromArticle()->getArticleID();
		$eventParams['Amount'] = self::$tradeLot->getAmount();
		$eventParams['BuyAnonymously'] = self::$buyAnonymous;
		
		self::$result->setDeliveryPaymentEvent(DE_EventQueue::CreateInDB('TRADING_DELIVERY_PAYMENT', $participants, $eventParams, self::$eventDatesPeriod));
				
		// Снимаем ресусры с соты покупателя. Если с торгом, то ресурсы снимаются в момент ставки и ничего снимать сейчас не нужно
		if(!self::$tradeLot->getIsAuction())
		{
			$categoryName = DS_Trade::GetTradeCategoryNameByID(self::$tradeLot->getTradeType()->getTradeToCategoryID());
			
			ME_TradeDeltaResourcesOnComb::Exec
			(
				$categoryName, 
				self::$tradeLot->getCurrentCustomerComb(), 
				self::$tradeLot->getTradeType()->getToArticle()->getArticleID(), 
				-self::$tradeLot->getPrice(), 
				self::$bayDate
			);	
		}	
		
		self::_sendMessageFromBuyForResources();
	}
	
	static private function _execMoneyTransfer()
	{
		// если лот был с торгом, то сначала возвращаем кредиты из резерва
		if(self::$tradeLot->getIsAuction())
		{
			ME_TradeExecReturnUserBid::Exec(self::$tradeLot, false);
		}
		
		$money = DE_Money::CreateFromID(self::$tradeLot->getTradeType()->getToArticle()->getArticleID());
		$moneyPaySumm = new MU_EntityMoneySumm((int)self::$tradeLot->getCurrentPrice(), $money->getShortName());
		
		$worldFromCollection = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());
		
		$memberSeller = new MU_EntityOperationMember(self::$tradeLot->getSeller()->getUser(), $worldFromCollection);
		$memberCustomer = new MU_EntityOperationMember(self::$tradeLot->getCurrentCustomer()->getUser(), $worldFromCollection);
		$memberSystem = MU_EntityOperationMember::CreateSystemAccount($worldFromCollection->getShortName());
			
		//Переводим деньги продовцу
		$actionType = DE_MoneyActionType::CreateFromName('TRADE_PAYMENT');
		$operation = new MU_EntityOperation($actionType, $moneyPaySumm, $memberCustomer, $memberSeller);
		$operation->setAdditionalID(self::$tradeLot->getID());
		MU_ControlExecOperations::ExecOperation($operation);
		
		//Если кредиты золотые, то мы их превращаем в серебренные
		if($money->getShortName() == 'GC')
		{			
			$actionType = DE_MoneyActionType::CreateFromName('EXCHANGE_CREDITS');
			$exchangeSumm = new MU_EntityMoneySumm((int)self::$tradeLot->getCurrentPrice(), $money->getShortName());
			$operation = new MU_EntityOperation($actionType, $exchangeSumm, $memberSeller, $memberSystem);
			MU_ControlExecOperations::ExecOperation($operation);
		}
		
		// Переводим с продовца налог
		// НАЛОГ УЖЕ РАСЧИТАН В СЕРЕБРЕ, см. GL_TradeCountLotTaxSumm
		if(self::$tradeLot->getTaxSumm() > 0)
		{
			$moneyTaxSumm = new MU_EntityMoneySumm((int)self::$tradeLot->getTaxSumm(), 'SC'); // налог всегда в SC
			$actionType = DE_MoneyActionType::CreateFromName('PAY_TRADE_TAX');
			$operation = new MU_EntityOperation($actionType, $moneyTaxSumm, $memberSeller, $memberSystem);
			$operation->setAdditionalID(self::$tradeLot->getID());
			MU_ControlExecOperations::ExecOperation($operation);		
		}
	
		self::_sendMessageFromBuyForCredits();
	}			
	
	static private function _sendMessageFromBuyForResources()
	{
		if(self::$tradeLot->getIsAuction())
		{
			return;
		}
		
		// Вы купили на аукцоне без торга % % за % %
		$messparams = array();
		$tradeGood = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(self::$tradeLot->getTradeType()->getTradeFromCategoryID(), self::$tradeLot->getTradeType()->getFromArticle()->getArticleID());
		$messparams[0] = "{b}".GB_Integer::Format(self::$tradeLot->getAmount(), ' ')."{/b}";
		$messparams[1] = "{b}".$tradeGood->getLocolizedName()."{/b}";	
		
		$tradeGood = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(self::$tradeLot->getTradeType()->getTradeToCategoryID(), self::$tradeLot->getTradeType()->getToArticle()->getArticleID());
		$messparams[2] = "{b}".GB_Integer::Format(self::$tradeLot->getPrice(), ' ')."{/b}";
		$messparams[3] = "{b}".$tradeGood->getLocolizedName()."{/b}";	
		$messparams[4] = "{b}".self::$tradeLot->getCurrentCustomerComb()->getFullName()."{/b}";	
		
		// Вы купили на аукционе без торга % % за % %. Товар будет доставлен на соту %.
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "QUICK_BUY", $messparams, 0, 'YELLOW');
	}
	
	static private function _sendMessageFromBuyForCredits()
	{
		// Кредиты
		$money = DE_Money::CreateFromID(self::$tradeLot->getTradeType()->getToArticle()->getArticleID());
		
		$price = self::$tradeLot->getCurrentPrice();
		// Если продали за золото, то придет серебро, обменяное по курсу
		if($money->getShortName() == 'GC')
		{
			$price *= constant('GC_COURSE');
			$money = DE_Money::CreateFromShortName('SC');
		} 
		
		$amount = $price - self::$tradeLot->getTaxSumm();
		
		$messparams[0] = "{b}".GB_Integer::Format($amount, ' ')."{/b}";
		$messparams[1] = "{b}".$money->getLocolizedName()."{/b}";
		
		// Товар
		$tradeGood = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(self::$tradeLot->getTradeType()->getTradeFromCategoryID(), self::$tradeLot->getTradeType()->getFromArticle()->getArticleID());		
		$messparams[2] = "{b}".GB_Integer::Format(self::$tradeLot->getAmount(), ' ')."{/b}";
		$messparams[3] = "{b}".$tradeGood->getLocolizedName()."{/b}";				
		$messparams[4] = "{b}".self::$tradeLot->getSellerComb()->getFullName()."{/b}";
		
		$date = GB_TypeFactory::CreateBase('GB_Date', time());

		// Вот так методом с необычным названием мы отправляем сообщение продавцу так чтобы оно не потерялось по дороге
		// остальные методы отправки системных сообщений не подходят
		// одни не умеют сохранять, другие не умеют отправлять нужному адресату
		
		// Если покупатель решил остаться неизвестным
		if (self::$buyAnonymous == 1) {
			// Оплата % % за проданный товар (% %) получена на соту %.. Покупатель Аноним.
			DE_SystemMessages::AddMessageFromMessageCompiler($date, self::$tradeLot->getSeller()->getUserInstanceID(), 'FINISH_DELIVERY_MESS_ANONYMOUS1', $messparams, 0, 'YELLOW');
		} else {
			
			// Покупатель
			$messparams[5] = "{b}".self::$tradeLot->getCurrentCustomer()->getUser()->getName()."{/b}";
		
			// Оплата % % за проданный товар (% %) получена на соту %. Покупатель %.
			DE_SystemMessages::AddMessageFromMessageCompiler($date, self::$tradeLot->getSeller()->getUserInstanceID(), 'FINISH_DELIVERY_MESS1', $messparams, 0, 'YELLOW');
		}

		$messparams = array();
		$messparams[0] = "{b}".GB_Integer::Format(self::$tradeLot->getAmount(), ' ')."{/b}";
		$messparams[1] = "{b}".$tradeGood->getLocolizedName()."{/b}";	
		$messparams[2] = "{b}".GB_Integer::Format(self::$tradeLot->getCurrentPrice(), ' ')."{/b}";
		$messparams[3] = "{b}".$money->getLocolizedName()."{/b}";	
		$messparams[4] = "{b}".self::$tradeLot->getCurrentCustomerComb()->getFullName()."{/b}";
			
		if(self::$tradeLot->getIsAuction())
		{
			//Вы купили на аукционе с торгом % % за % %. Товар будет доставлен на соту %.
			DE_SystemMessages::AddMessageFromMessageCompiler($date, self::$tradeLot->getCurrentCustomer()->getUserInstanceID(), 'BET_TRADING_MESS3', $messparams, 0, 'YELLOW');
		} else {
			// Вы купили на аукционе без торга % % за % %. Товар будет доставлен на соту %.
			DE_SystemMessages::AddMessageFromMessageCompiler($date, self::$tradeLot->getCurrentCustomer()->getUserInstanceID(), 'QUICK_BUY', $messparams, 0, 'YELLOW');
		}
	}
	
	static private function _initClass(DE_TradeLot $tradeLot, GB_Date $bayDate)
	{
		self::$tradeLot = $tradeLot;
		
		self::$bayDate = $bayDate;
		
		self::$result = new DE_TradeExecBayResult(self::$tradeLot);
	}
	
}

?>