<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Менеджер продажи лота.
 *
 */
class ME_TradeExecLotSell
{   
    /**
     * Прототип торга который продаем.
     *
     * @var DE_TradeLoTPrototipe
     */
    static private $tradeLotPrototipe;
    
    /**
     * Название категории товара
     *
     * @var string
     */
    static private $fromTradeCategoryName;
    
    static public function Exec(DE_TradeLoTPrototipe $tradeLotPrototipe)
    {
        self::$tradeLotPrototipe = $tradeLotPrototipe;
        
        //Проверяем на корректность лот
        self::$tradeLotPrototipe->validate();
        
        //Тип товара 
        self::$fromTradeCategoryName = DS_Trade::GetTradeCategoryNameByID(self::$tradeLotPrototipe->get_tradeType()->getTradeFromCategoryID()); 

        //Если это юниты то снимаем апкип
        if(self::$fromTradeCategoryName == 'UNIT')
        {
            self::_getUpkeep();
        }
        
        //Создаем ивент
        $event = self::_createEvent();
        
        //Создаем лот
        $tradeLot = DE_TradeLot::CreateInDBFromTradePrototipeAndEventQueue(self::$tradeLotPrototipe, $event, DE_World::CreateForCurrentWorld());
        
        //Снимаем ресурсы с соты которые выставили на торг (арты снимаються при создание объекта торга .. пока так. потом рефакторизую. // гы гы гы, потом...)          
        if(self::$fromTradeCategoryName != 'ARTEFACT')
        {
            ME_DeltaResourcesOnComb::Exec
            (
                self::$fromTradeCategoryName, 
                self::$tradeLotPrototipe->get_fromComb(), 
                self::$tradeLotPrototipe->get_realFromArticleID(), 
                -self::$tradeLotPrototipe->get_amount(), 
                self::$tradeLotPrototipe->get_startDate()
            );  
        }
        
        //Логируем выставление на торг лота
        DS_Trade::LogSell($tradeLot);
        
        // расчитываем цену за единицу товара
        GL_TradeCalcBestPrice::Calc($tradeLot);
        
        return $tradeLot;
    }
    
    static private function _createEvent()
    {               
        $eventDatesPeriod = new GB_DatePeriod(
            self::$tradeLotPrototipe->get_startDate(), 
            self::$tradeLotPrototipe->get_endDate()
        );
                        
        $participants[] = self::$tradeLotPrototipe->get_fromComb()->getUserInstance()->getID();
        
        $eventParams = array(
            'ID_TradeLot' => self::$tradeLotPrototipe->get_tradeLotID()
        );      
        
        return DE_EventQueue::CreateInDB('TRADING', $participants, $eventParams, $eventDatesPeriod);
                
    }
    
    static private function _getUpkeep()
    {   
        assert(self::$fromTradeCategoryName == 'UNIT');     
        
        $liveUpkeep = GL_FullUnitsMovementCost::CountSellUnit
        (
            self::$tradeLotPrototipe->get_fromComb()->getUserInstance(), 
            GB_TypeFactory::CreateConstant
            (
                'GB_TimePeriod', 
                self::$tradeLotPrototipe->get_endDate()->value - self::$tradeLotPrototipe->get_startDate()->value
            ), 
            DE_Unit::CreateFromID(self::$tradeLotPrototipe->get_realFromArticleID()), 
            self::$tradeLotPrototipe->get_amount()
        );
            
        $combAttributes = self::$tradeLotPrototipe->get_fromComb()->getAttributes();
        
        foreach (GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
        {
            $needValue = (float)$liveUpkeep->getPay($resourceName);
            $haveValue = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);
            
            if ($combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName) >= $needValue)
            {               
                $combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -$needValue);
            }else{
                throw new ME_TradeExecLotSellException('NOT_ENOUGHT_RESOURCES_FOR_SELL_THIS_UNITS');
            }
        }
    }
}

?>