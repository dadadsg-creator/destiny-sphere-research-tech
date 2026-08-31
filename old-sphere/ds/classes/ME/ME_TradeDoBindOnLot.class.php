<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Делаем ставку на аукциолне
 *
 */
class ME_TradeDoBindOnLot
{
	/**
	 * Enter description here...
	 *
	 * @var DE_TradeLot
	 */
	static private $tradeLot;
	
	/**
	 * Время ставки
	 *
	 * @var GB_Date
	 */
	static private $bidDate;
	
	static private $fromCategoryName;
	
	static private $toCategoryName;
	
	/**
	 * Сота с которой делаем ставку
	 *
	 * @var DE_Comb
	 */
	static private $comb;
	
	static private $newPrice;
	
	/**
	 * Анонимность ставки
	 *
	 * @var int
	 */
	static private $buyAnonymous;
	
		
	static public function Exec(DE_TradeLot $tradeLot, DE_Comb $comb, GB_Date $bidDate, $newPrice, $buyAnonymous)
	{	
		assert($buyAnonymous == 1 || $buyAnonymous == 0);
		self::$buyAnonymous = $buyAnonymous;
		
		self::_init($tradeLot, $comb, $bidDate, $newPrice);
		
		//Снять ресурсы за апкип если это юниты
		self::_getUpkeepResorces();
			
		//Возвращаем старую ставку 
		ME_TradeExecReturnUserBid::Exec(self::$tradeLot);
		
		//Снять с нового покупателя стоимость ставки
		self::_getNewPriceReources();
		
		//Вставить запись о ставке новой
		self::_insertNewBid();	
		
		//продляем торг
		self::_prolongTrade();
		
		// расчитываем цену за единицу товара
		GL_TradeCalcBestPrice::Calc($tradeLot);
		
		self::_logBid();
		
		//Отправляем сообщение
		self::_sendMessage();
	}	

	static private function _prolongTrade()
	{
		$prolongTime = (int)DE_WorldSettings::CreateForCurentWorld()->get('TRADE_AUCTION_BID_PROLONG_TIME');
		
		if($prolongTime <= 0)
		{
			return;
		}
		
		$period = GB_TypeFactory::CreateBase('GB_TimePeriod', $prolongTime);
		self::$tradeLot->increaseEndDate($period);
		
		$event = self::$tradeLot->getLotEvent();
		$endDate = GB_TypeFactory::CreateBase('GB_Date', $event->getEndDate()->value + $period->value);
		$event->setEndDate($endDate);
	}
	
	static private function _getUpkeepResorces()
	{
		if(self::$fromCategoryName != 'UNIT')		
		{
			return;
		}
				
		$unit = DE_Unit::CreateFromID(self::$tradeLot->getArticleID());
		
		// снимаем с нового покупателя
		$combToAttributes = self::$comb->getAttributes();
			
		$upkeepValue = GL_FullUnitsMovementCost::CountBuyUnits(self::$tradeLot->getSellerComb(), self::$comb, $unit, self::$tradeLot->getAmount(), self::$bidDate);
		foreach (GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
		{
			if($combToAttributes->getAttribute('CURRENT_AMOUNT', $resourceName) < $upkeepValue->getPay($resourceName))
			{
				throw new ME_TradeExecLotBuyException('NOT_ENOUGHT_RESOURCES_FOR_BUY_THIS_UNITS');
			}
			
			$combToAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -(float)$upkeepValue->getPay($resourceName));
		}	
	}
	
	
	/**
	 * Снимаем с соты новго покупателя ресусры
	 *
	 */
	static private function _getNewPriceReources()
	{
		if(self::$toCategoryName == 'MONEY')
		{
			$worldFromCollection = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());			
				
			$memberFrom = new MU_EntityOperationMember(self::$comb->getUserInstance()->getUser(), $worldFromCollection);
			$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $worldFromCollection);
			
			$money = DE_Money::CreateFromID(self::$tradeLot->getTradeType()->getTradeToTypeID());
			$summ = new MU_EntityMoneySumm((int)self::$newPrice, $money->getShortName());
			$actionType = DE_MoneyActionType::CreateFromName('MOVE_TO_RESERV');
			
			$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
			$operation->setAdditionalID(self::$tradeLot->getID());
			$operation->setExpireDateAndReason(self::$tradeLot->getEndDate(), 'AUCTION_TRADING');			
			
			MU_ControlExecOperations::ExecOperation($operation);
		}else{
			ME_DeltaResourcesOnComb::Exec(self::$toCategoryName, self::$comb, self::$tradeLot->getTradeType()->getTradeToTypeID(), -self::$newPrice, self::$bidDate);
		}
	}
	
	static private function _insertNewBid()
	{
		DS_Trade::InsertNewBid(self::$tradeLot, self::$comb, self::$bidDate, self::$newPrice, self::$buyAnonymous);
		DS_Trade::InsertNewEventParticipant(self::$tradeLot, self::$comb->getUserInstance());
		
		self::$tradeLot->setCurrentCustomer(self::$comb, self::$newPrice);
	}
	
	static private function _sendMessage()
	{
		// Сообщение покупателю
		// Вы сделали ставку % % на аукцоне. Товар % %
		$messparams = array();
		$tradeGoodTo = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(self::$tradeLot->getTradeType()->getTradeToCategoryID(), self::$tradeLot->getTradeType()->getToArticle()->getArticleID());
		$messparams[0] = "{b}".GB_Integer::Format(self::$tradeLot->getCurrentPrice(), ' ')."{/b}";
		$messparams[1] = "{b}".$tradeGoodTo->getLocolizedName()."{/b}";	
		
		$tradeGoodFrom = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(self::$tradeLot->getTradeType()->getTradeFromCategoryID(), self::$tradeLot->getTradeType()->getFromArticle()->getArticleID());
		$messparams[2] = "{b}".GB_Integer::Format(self::$tradeLot->getAmount(), ' ')."{/b}";
		$messparams[3] = "{b}".$tradeGoodFrom->getLocolizedName()."{/b}";		
		
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "BET_TRADING_MESS", $messparams, 0, 'YELLOW');
		
		// Сообщение продавцу
		// На ваш лот % % сделана ставка % %
		$messparams[0] = "{b}".GB_Integer::Format(self::$tradeLot->getAmount(), ' ')."{/b}";
		$messparams[1] = "{b}".$tradeGoodFrom->getLocolizedName()."{/b}";		
		$messparams[2] = "{b}".GB_Integer::Format(self::$tradeLot->getCurrentPrice(), ' ')."{/b}";
		$messparams[3] = "{b}".$tradeGoodTo->getLocolizedName()."{/b}";		
		$date = GB_TypeFactory::CreateBase('GB_Date', time());
		DE_SystemMessages::AddMessageFromMessageCompiler($date, self::$tradeLot->getSeller()->getUserInstanceID(), 'BET_TRADING_MESS_SELLER', $messparams, 0, 'YELLOW');
	}
	
	static private function _logBid()
	{
		$ip = SU_DebugTools::GetIP(0);
		DS_Trade::MarkBidLogAsCanceled(self::$tradeLot, $ip['IP']);
		DS_Trade::InsertBidLog(self::$tradeLot, $ip['IP']);
	}
	
	static private function _init(DE_TradeLot $tradeLot, DE_Comb $comb, GB_Date $bidDate, $newPrice)
	{
		self::$tradeLot = $tradeLot;
		self::$bidDate = $bidDate;
		self::$comb = $comb;
		self::$newPrice = $newPrice;
		
		self::$toCategoryName = DS_Trade::GetTradeCategoryNameByID(self::$tradeLot->getTradeType()->getTradeToCategoryID());
		self::$fromCategoryName = DS_Trade::GetTradeCategoryNameByID(self::$tradeLot->getTradeType()->getTradeFromCategoryID());
	}

}

?>