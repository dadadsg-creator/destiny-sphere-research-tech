<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Проверям возможно ли пробать такой товар за такой по такой цене данному игроку.
 *
 */
class GL_TradeCanSellByThisPriceAndScope
{

	/**
	 * Кода ошибок елси они етсь для валидированныхз классов
	 *
	 * @var array()
	 */
	static private $errorCode = array();


	/**
	 * ID прототипа трейда который проверяем
	 *
	 * @var integer
	 */
	static private $tradeLotID;

	/**
	 * Тип торговли
	 *
	 * @var DE_TradeType
	 */
	static private $tradeType;

	/**
	 * Из какого предмата
	 *
	 * @var DE_TradeArticle
	 */
	static private $fromArticle;

	/**
	 * В какой предмет
	 *
	 * @var DE_TradeArticle
	 */
	static private $toArticle;

	/**
	 * Цена
	 *
	 * @var float
	 */
	static private $price;

	/**
	 * Колличество
	 *
	 * @var float
	 */
	static private $amount;

	/**
	 * ИМя терйд скопа
	 *
	 * @var integer
	 */
	static private $tradeScopeID;

	/**
	 * Продовец барахла
	 *
	 * @var DE_Comb
	 */
	static private $comb;

	/**
	 * Enter description here...
	 *
	 * @var DE_TradeLotPrototipe
	 */
	static private $tradeLotPrototipe;

	static public function Test(DE_TradeLotPrototipe $tradeLotPrototipe)
	{
		self::_resetVars();

		$tradeLotPrototipe->validate();

		self::$tradeLotID = $tradeLotPrototipe->get_tradeLotID();

		self::$fromArticle = $tradeLotPrototipe->get_fromArticle();

		self::$toArticle = $tradeLotPrototipe->get_toArticle();

		self::$price = $tradeLotPrototipe->get_price();

		self::$amount = $tradeLotPrototipe->get_amount();

		self::$tradeScopeID = $tradeLotPrototipe->get_tradeScopeID();

		self::$comb = $tradeLotPrototipe->get_fromComb();

		self::$tradeLotPrototipe = $tradeLotPrototipe;

		try {

			//Существует ли данный вид торговли
			self::_checkExists();

			//Разрешен ли данный вид торговли
			self::_checkAllowed();

			//Цена укладываеться в корридор допустимый
			self::_checkPrice();

			//Есть ли у инстанса соотвествующий скоп
			self::_checkScope();

			//Проверка на наличие необходимого колличества торвара на соте
			self::_checkNeedAmount();

			//Првоерка существования для имени персноальнго трейда
			self::_checkPerson();

			//Во время боя нельзя сливать
			self::_checkBattle();

			//Персональный бан на торговлю
			self::_checkTradeBan();

			//Премиум бан
			self::_checkPremiumBan();

			//Проверка на уровень торговли
			self::_checkLevel();

			//Проверка на рандомную защиту
			self::_checkRandomDefend();

		}catch(Exception $e){

			self::$errorCode[self::$tradeLotID] = $e->getMessage();
			return false;

		}

		self::$errorCode[self::$tradeLotID] = '';
		return true;
	}

	static private function _checkPerson()
	{
		if(!self::$tradeLotPrototipe->get_isPersonal())
		{
			return;
		}

		if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_PERSONAL_TRADES') == 0)
		{
			throw new Exception("INCREDEBLE_PERSONAL_TRADE");
		}

		if(!VD_UserName::Validate(self::$tradeLotPrototipe->get_personName()))
		{
			throw new Exception("UNCORRECT_USERNAME_NAME_FOR_PERSON_TRADE");
		}

		$userID = DS_User::GetUserIDByName(self::$tradeLotPrototipe->get_personName());

		if(!$userID)
		{
			throw new Exception("UNCORRECT_USERNAME_NAME_FOR_PERSON_TRADE");
		}

		if(!DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($userID,DE_World::CreateForCurrentWorld()->getID()))
		{
			throw new Exception("UNCORRECT_USERNAME_NAME_FOR_PERSON_TRADE");
		}
	}

	static private function _checkExists()
	{
		assert(self::$fromArticle instanceof DE_TradeArticle);
		assert(self::$toArticle instanceof DE_TradeArticle);

		self::$tradeType = DE_TradeType::CreateFromTradeArticlesAndWorld(self::$fromArticle, self::$toArticle, DE_World::CreateForCurrentWorld());

		if(!(self::$tradeType instanceof DE_TradeType))
		{
			throw new Exception("DONT_EXISTS_TRADE_TYPE");
		}
	}

	static private function _checkAllowed()
	{
		assert(self::$tradeType instanceof DE_TradeType);

		if(!self::$tradeType->isAllowed())
		{
			throw new Exception("THIS_TRADE_TYPE_IS_NOT_ALLOWED");
		}
	}

	static private function _checkPrice()
	{
		assert(is_float(self::$price) && self::$price > 0);
		assert(is_float(self::$amount) && self::$amount > 0);
		assert(self::$tradeType instanceof DE_TradeType);

		if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_TRANDS') == 0)
		{
			return;
		}

		$prices = GL_TradeCountMinAndMaxPrices::Count(self::$tradeType, self::$amount);

		if(self::$tradeType->getMaxPriceDelta() > 0 && self::$price > $prices['MAX_PRICE'])
		{
			throw new Exception("PRICE_IS_BIGGER_THEN_MAX_TRAND");
		}

		if(self::$price < $prices['MIN_PRICE'])
		{
			throw new Exception("PRICE_IS_SMALLER_THEN_MIN_TRAND");
		}
	}

	static private function _checkLevel()
	{
		if(!DS_Science::GetTradeAbility(self::$comb->getUserInstance()->getID(), self::$fromArticle->getTradeCategoryID(), self::$fromArticle->getArticleID()))
		{
			throw new Exception("USER_HAS_NOT_TRADE_ABILITY");
		}
	}

	static private function _checkScope()
	{
		assert(self::$tradeScopeID > 0);
		assert(self::$comb instanceof DE_Comb);

		$message = '';
		$haveScope = false;
		$allScopes = GL_CombAllowedTradeScopes::GetAllScopes(self::$comb);

		foreach ($allScopes as $k => $scope)
		{
			$message .= '  scope wich Oleg has '.$scope['id_tradescope'].',<br>';
			if(self::$tradeScopeID == $scope['id_tradescope'])
			{
				$haveScope = true;
				break;
			}
		}

		if(!$haveScope)
		{
			throw new Exception("DONT_HAVE_NEED_TRADESCOPE");
		}

		// проверяем не запрещен ли данный trade scope
		$modifier = '';
		switch (self::$tradeScopeID)
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

		if(!empty($modifier) && DE_WorldSettings::CreateForCurentWorld()->get($modifier))
		{
			throw new Exception("TRADE_SCOPE_DISABLED");
		}

		//Дополнительная проверка если продажа идет на алдьфнсовый скоуп
		if(
		self::$tradeScopeID == 4
		&&
		DE_Alliance::CreateFromUserInstanceID(self::$comb->getUserInstanceID())->isEmpty()
		)
		{
			throw new Exception("DONT_HAVE_NEED_TRADESCOPE");
		}

	}

	static private function _checkNeedAmount()
	{
		assert(self::$comb instanceof DE_Comb);
		assert(self::$amount > 0);
		assert(self::$fromArticle instanceof DE_TradeArticle);

		if(!ME_TradeCheckNeedAmountOfGood::Exec(self::$comb, self::$fromArticle->getTradeCategoryID(), self::$tradeLotPrototipe->get_realFromArticleID(), self::$amount))
		{
			throw new Exception("DONT_HAVE_NEED_AMOUNT");
		}
	}

	static private function _checkBattle()
	{
		if(self::$fromArticle->getTradeCategoryID() != 2)
		{
			return;
		}

		$rs = DS_Battle::NotEndedBattlesOnComb(self::$comb);

		if(count($rs) > 0)
		{
			throw new Exception("CANT_SEL_UNITS_WHEN_BATTLE_IN_PROGRESS");
		}
	}

	static private function _checkTradeBan()
	{
		if(DS_User::GetTradeBan(self::$comb->getUserInstance()->getUser(), self::$comb->getUserInstance()->getWorldID()))
		{
			throw new Exception("CANT_SELL_WITH_TRADE_BAN");
		}
	}

	static private function _checkPremiumBan()
	{
		$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
		if(self::$comb->getUserInstance()->getUser()->getPremiumBan()->dateInPeriod($checkDate) && self::$comb->getUserInstance()->getUser()->getPremiumBanAllowTrade() != 't')
		{
			throw new Exception("CANT_SELL_WITH_TRADE_BAN");
		}
	}

	static private function _checkRandomDefend()
	{
		// Если это колония, ниего не проверяем
		if (self::$comb->getRingNumber() == 4) {
			return;
		}
		
		// Если игрок находиться под защитой
		$userInstance = self::$comb->getUserInstance();

		if ($userInstance->haveRandomDefend()) {
			//  Если лот не персональный, и выставляется не на самого себя
			if(!self::$tradeLotPrototipe->get_isPersonal() || self::$tradeLotPrototipe->get_personName() != $userInstance->getUser()->getName())
			{
				throw new Exception("CANT_SELL_WITH_RANDOM_DEFEND");
			}
		}
	}

	static private function _resetVars()
	{
		self::$amount = 0;
		self::$errorCode = '';
		self::$fromArticle = null;
		self::$price = 0;
		self::$toArticle = null;
		self::$tradeScopeID = 0;
		self::$tradeType = null;
		self::$comb = null;
		self::$tradeLotID = 0;
	}

	static public function GetErrorCodeForObject(DE_TradeLotPrototipe $tradeLotPrototipe)
	{
		$id = $tradeLotPrototipe->get_tradeLotID();

		assert(key_exists($id, self::$errorCode));
		return self::$errorCode[$id];
	}
}

?>