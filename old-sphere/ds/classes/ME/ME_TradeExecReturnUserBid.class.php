<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_TradeExecReturnUserBid
{
	/**
	 * Enter description here...
	 *
	 * @var DE_TradeLot
	 */
	static private $tradeLot;
	
	static private $fromCategoryName;
	
	static private $toCategoryName;
	
	static public function Exec(DE_TradeLot $tradeLot, $withMessage = true)
	{
		assert(is_bool($withMessage));
		
		self::$tradeLot = $tradeLot;		
						
		self::$toCategoryName = DS_Trade::GetTradeCategoryNameByID(self::$tradeLot->getTradeType()->getTradeToCategoryID());
		
		self::$fromCategoryName = DS_Trade::GetTradeCategoryNameByID(self::$tradeLot->getTradeType()->getTradeFromCategoryID());
		
		if(self::$tradeLot->getCurrentCustomerComb() instanceof DE_CombNull)
		{
			return;
		}
		
		//Возвращение ставки
		if(self::$toCategoryName == 'MONEY')
		{
			$worldFromCollection = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());			
	
			$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $worldFromCollection);
			$memberTo = new MU_EntityOperationMember(self::$tradeLot->getCurrentCustomer()->getUser(), $worldFromCollection);

			$money = DE_Money::CreateFromID(self::$tradeLot->getTradeType()->getTradeToTypeID());
			$summ = new MU_EntityMoneySumm((int)self::$tradeLot->getCurrentPrice(), $money->getShortName());
			$actionType = DE_MoneyActionType::CreateFromName('MOVE_FROM_RESERV');

			$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
			$operation->setAdditionalID(self::$tradeLot->getID());						

			MU_ControlExecOperations::ExecOperation($operation);
		}else{
			ME_DeltaResourcesOnComb::Exec(	self::$toCategoryName, self::$tradeLot->getCurrentCustomerComb(), 
											self::$tradeLot->getTradeType()->getTradeToTypeID(), 
											self::$tradeLot->getCurrentPrice(), 
											GB_TypeFactory::CreateBase('GB_Date', time())
										);	
		}		
		
		//Возворащение апкипных ресов
		if(self::$fromCategoryName == 'UNIT')		
		{			
			self::_returnUnitsUpkeep();
		}		
		
		DS_Trade::DeleteBid(self::$tradeLot);
		DS_Trade::DeleteUserFromTradeEvent(self::$tradeLot, self::$tradeLot->getCurrentCustomer());		

		if($withMessage)
		{
			self::_sendMessage();
		}
	}		
	
	static private function _sendMessage()
	{
		$messparams = array();
		$tradeGood = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(self::$tradeLot->getTradeType()->getTradeFromCategoryID(), self::$tradeLot->getTradeType()->getFromArticle()->getArticleID());
		$messparams[0] = "{b}".self::$tradeLot->getAmount()."{/b}";
		$messparams[1] = "{b}".$tradeGood->getLocolizedName()."{/b}";
		
		$tradeGood = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(self::$tradeLot->getTradeType()->getTradeToCategoryID(), self::$tradeLot->getTradeType()->getToArticle()->getArticleID());
		$messparams[2] = "{b}".self::$tradeLot->getCurrentPrice()."{/b}";
		$messparams[3] = "{b}".$tradeGood->getLocolizedName()."{/b}";	
		
		DE_SystemMessages::AddMessageFromMessageCompiler(GB_TypeFactory::CreateBase('GB_Date', time()), self::$tradeLot->getCurrentCustomer()->getID(), 'TRADE_BET_RETURNED', $messparams, 0, 'YELLOW');
	}
	
	static private function _returnUnitsUpkeep()
	{
		$unit = DE_Unit::CreateFromID(self::$tradeLot->getArticleID());
		
		$combToAttributes = self::$tradeLot->getCurrentCustomerComb()->getAttributes();
		
		$upkeepValue = GL_FullUnitsMovementCost::CountBuyUnits(self::$tradeLot->getSellerComb(), self::$tradeLot->getCurrentCustomerComb(), $unit, self::$tradeLot->getAmount(), GB_TypeFactory::CreateBase('GB_Date', time()));
		foreach (GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
		{
			$combToAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)$upkeepValue->getPay($resourceName));
		}			
	}
}


?>