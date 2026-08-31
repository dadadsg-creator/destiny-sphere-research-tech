<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Проверям возможно ли покупать такой товар за такой по такой цене данному игроку.
 *
 */
class GL_TradeCanByThisLotByThisPrice
{
    /**
     * Enter description here...
     *
     * @var DE_TradeLot
     */
    static $tradeLot;
    
    /**
     * Новая цена
     *
     * @var float
     */
    static $newPrice;
    
    /**
     * Кода ошибок елси они етсь для валидированныхз классов
     *
     * @var string
     */
    static private $errorCode = "";
        
    /**
     * Покупатель лота
     *
     * @var DE_Comb
     */
    static private $customerComb;
        
    static private $tradeFromCategoryName;
    
    static private $tradeToCategoryName;
    
    /**
     * Enter description here...
     *
     * @var DE_TradeArticle
     */
    static private $tradeFromGood;
    
    /**
     * Enter description here...
     *
     * @var DE_TradeArticle
     */
    static private $tradeToGood;
    
    static private $password;
    
    static public function Test(DE_TradeLot $tradeLot, DE_Comb $customerComb, $newPrice, $password)
    {
        assert(is_float($newPrice) && $newPrice >= 0);
        
        self::_init($tradeLot, $customerComb, $newPrice, $password);
                
        try {
            
            //Не лот ли этого человека
            self::_isNotThisPersonLot();
            
            //Првоерка персонального лота
            self::_testPerson();
            
            //Есть это скоуп
            self::_haveThisTradeScope();
            
            //Валидна ли новая ставка на аукционе
            self::_validNewPrice();
            
            //Есть ли ресусрсы что бы заплатить цену
            self::_haveResources();
                        
            //Не просрочен ли
            self::_testEndDate();
            
            //Этого ли мира лот 
            // если лот прибыл к нам из потустороннего мира, то не нужно его покупать, а то пришельцы еще заведуться
            self::_isThisWorld();
            
            //Бан на торговлю
            self::_checkTradeBan();
            
            // премиум бан
            self::_checkPremiumBan();
            
            //Проверка на рандомную защиту
            self::_checkRandomDefend();
            
            //Проверка для мира расса против рассы. Что бы не купить чужой 
            self::_rvrTest();
            
        }catch(Exception $e){
            
            self::$errorCode = $e->getMessage();
            return false;
            
        }
        
        return true;
    }
    
    static private function _rvrTest()
    {
        if($_SESSION["worldtype"] != 'RACE_VS_RACE')
        {
            switch (self::$tradeLot->getTradeType()->getTradeFromCategoryID())
            {
                case 2:
                    if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_NON_RVR_INTERRACE_ALLOW_UNITS'))
                    {
                        return; 
                    }
                    else 
                    {
                        if(self::$tradeLot->getSeller()->getRace()->getID() != self::$customerComb->getUserInstance()->getRace()->getID())
                        {
                             throw new Exception("NON_RVR_RACE_UNITS_TRADE_FORBID");
                        }
                    }
            }
            
            return ;
        }
        
        
        switch (self::$tradeLot->getTradeType()->getTradeFromCategoryID())
        {
            case 1:
                if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_RVR_ALLOW_RESOURCE'))
                {
                    return;
                }
                break;

            case 2:
                if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_RVR_ALLOW_UNITS'))
                {
                    return;
                }
                break;

            case 3:
                if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_RVR_ALLOW_ARTEFACTS'))
                {
                    return;
                }
                break;
        }
        
        if(self::$tradeLot->getSeller()->getRace()->getID() != self::$customerComb->getUserInstance()->getRace()->getID())
        {
             throw new Exception("RVR_RACE_DIFFERENCE");
        }
    }
    
    static private function _testPerson()
    {
        if(!self::$tradeLot->getIsPersonal())
        {
            return;
        }

        if(self::$tradeLot->getPersonName() != self::$customerComb->getUserInstance()->getUser()->getName())
        {
            throw new Exception("UNCORRECT_USER_NAME_FOR_PERSON_TRADE");
        }
        
        if(self::$tradeLot->getPersonPassword() != self::$password)
        {
            throw new Exception("UNCORRECT_PASSWORD_FOR_PERSON_TRADE");
        }
    }
    
    static private function _isNotThisPersonLot()
    {
        if(self::$tradeLot->getSeller()->getID() == self::$customerComb->getUserInstance()->getID())
        {
            throw new Exception("TRY_TO_BUY_HIS_OWN_LOT");
        }
    }
    
    static private function _validNewPrice()
    {       
        if(!self::$tradeLot->getIsAuction())
        {
            return;
        }       
        assert(self::$newPrice > 0);        
        
        $prices = GL_TradeCountMinAndMaxPrices::Count(self::$tradeLot->getTradeType(), self::$tradeLot->getAmount(), self::$tradeLot->getCurrentPrice(), self::$tradeLot->haveCurrentCustomer());

        if(self::$tradeLot->getTradeType()->getMaxPriceDelta() > 0 && self::$newPrice > $prices['MAX_PRICE'])
        {
            throw new Exception("PRICE_IS_BIGGER_THEN_MAX_TRAND");
        }   
        
        if(self::$newPrice <  $prices['MIN_PRICE'])
        {
            throw new Exception("PRICE_IS_SMALLER_THEN_MIN_TRAND");
        }       

        if(self::$newPrice < $prices['NEXT_PRICE'])
            self::$newPrice = $prices['NEXT_PRICE'];
    }
    
    static private function _haveThisTradeScope()
    {
        $allScopes = GL_CombAllowedTradeScopes::GetAllScopes(self::$customerComb);
        
        foreach ($allScopes as $k => $scope)
        {
            if(self::$tradeLot->getTradeScopeID() == $scope['id_tradescope'])
            {
                return;
            }
        }
        
        throw new Exception("DONT_HAVE_NEED_TRADESCOPE");
        //if(!DS_Trade::CheckCombTradeScope(self::$customerComb, self::$tradeLot->getTradeScopeID())){}
    }
    
    static private function _haveResources()
    {
        
        if(self::$tradeLot->getIsAuction())
        {
            $price = self::$newPrice;
        }else{
            $price = self::$tradeLot->getPrice();
        }
        
        if(!ME_TradeCheckNeedAmountOfGood::Exec(self::$customerComb, self::$tradeLot->getTradeType()->getTradeToCategoryID(), self::$tradeLot->getTradeType()->getTradeToTypeID(), $price))
        {
            throw new Exception("DONT_HAVE_NEED_AMOUNT");
        }
    }
    
    static private function _testEndDate()
    {
        $nowDate = GB_TypeFactory::CreateBase('GB_Date', time());
        
        if(self::$tradeLot->getEndDate()->value < $nowDate->value)      
        {
            throw new Exception("TO_OLD_TRADE");
        }
    }
    
    static private function _isThisWorld()
    {
        if(self::$tradeLot->getWorldID() != DE_World::CreateForCurrentWorld()->getID())
        {
            throw new Exception("LOT_FROM_OTHER_WORLD");
        }       
    }
    
    static private function _checkTradeBan()
    {
        if(DS_User::GetTradeBan(self::$customerComb->getUserInstance()->getUser(), self::$customerComb->getUserInstance()->getWorldID()))
        {
            throw new Exception("CANT_BUY_WITH_TRADE_BAN");
        }
    }
    
    static private function _checkPremiumBan()
    {
        $checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
        if(self::$customerComb->getUserInstance()->getUser()->getPremiumBan()->dateInPeriod($checkDate) && self::$customerComb->getUserInstance()->getUser()->getPremiumBanAllowTrade() != 't')
        {
            throw new Exception("CANT_BUY_WITH_TRADE_BAN");
        }
    }
    
    static private function _checkRandomDefend()
    {
        // Если это колония, ниего не проверяем
        if (self::$customerComb->getRingNumber() == 4) {
            return;
        }
        
        // Если игрок находиться под защитой
        $userInstance = self::$customerComb->getUserInstance();

        if ($userInstance->haveRandomDefend()) {
            throw new Exception("CANT_BY_WITH_RANDOM_DEFEND");
        }
    }
    
    static private function _init(DE_TradeLot $tradeLot, DE_Comb $customerComb, $newPrice, $password)
    {
        assert(is_float($newPrice) && $newPrice >= 0);
        
        self::$errorCode = '';
        self::$tradeLot = $tradeLot;
        self::$customerComb = $customerComb;
        self::$newPrice = $newPrice;
        self::$password = $password;                
        
        self::$tradeFromGood = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(
                                    self::$tradeLot->getTradeType()->getTradeFromCategoryID(), 
                                    self::$tradeLot->getArticleID());
        self::$tradeToGood = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(
                                    self::$tradeLot->getTradeType()->getTradeToCategoryID(), 
                                    self::$tradeLot->getTradeType()->getTradeToTypeID());
    }
    
    static public function GetErrorCode()
    {
        return self::$errorCode;
    }
}




?>