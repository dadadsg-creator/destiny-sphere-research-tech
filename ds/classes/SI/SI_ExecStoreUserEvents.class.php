<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2010/03/04 08:55:10 $';

/**
 * БЫсрое завершение всех ивентов пользователя в мире
 *
 */
class SI_ExecStoreUserEvents extends SI_Base
{
    /**
     * Имен ивентов которые мы не сохраняем
     *
     * @var unknown_type
     */
    static private $eventsDisabledToStore = array
    (                   
        'ARMY_MOVEMENT_TO',
        'ARMY_MOVEMENT_FROM',
        'BATTLE_ROUND',
        'ATTACK',
        'MOVE_TO_DEFEND',
        'DEFEND',
        'MOVE_TO_COMB',
        'HIDE_ARMY'
            
    );
    
    static private $eventsForProcess = array
    (                               
        'TRADING_DELIVERY_GOOD' => '_processGoodDelivery',
        'TRADING' => '_processTrading',
        'TRADING_DELIVERY_PAYMENT' => '_processPaymentDelivery',
        'TRANSFER_RESOURCE' => '_helpTransefArtefact',  
        'TRAIN_WAR_UNIT' => '_precessTrainWarUnits'     
    );
    
    
    static private $userInstanceResult;
    
    static public function Exec(SI_ResultUserInstance $userInstanceResult)
    {               
        
        self::$userInstanceResult = $userInstanceResult;
        
        self::_out(' - START STORE USER EVENTS');
                
        $userEvents = SI_SearchEvents::GetUserEvents(self::$userInstanceResult->getUIID());
        self::_out(' - Selected '.count($userEvents).' events for process');                    
        
        foreach($userEvents as $k=>$eventData)
        {                       
            assert(key_exists('eventname', $eventData));
            $eventName = $eventData['eventname'];
            assert(!empty($eventName));                     
            
            self::_out(" - Start process event ".$eventName." (".$eventData['id_eventqueue'].")", true);                                            
            
            if(in_array($eventName, self::$eventsDisabledToStore))
            {           
                self::_out(" - Disabled to store", true);                                                       
                continue;
            }
            
            self::_out(" - Events dates FROM: ".$eventData['eventstarttime']." TO: ".$eventData['eventendtime'], true);                                                 
            
            $startTimeObject = GB_TypeFactory::CreateBase('GB_Date', $eventData['eventstarttime']);
            $endTimeObject = GB_TypeFactory::CreateBase('GB_Date', $eventData['eventendtime']);         
                        
            $turnTime = self::$userInstanceResult->getTurnTime();
            $eventData['startdelta'] = ($startTimeObject->value - time()) / $turnTime;
            $eventData['enddelta'] = ($endTimeObject->value - time()) / $turnTime;
            
            self::_out(" - Events date deltas FROM: ".$eventData['startdelta']." TO: ".$eventData['enddelta'], true);                                                   
            
            $eventOptions = array();
            $eventOptions = self::_convertOptionsToAssocArray(SI_SearchEvents::GetEventOptions($eventData['id_eventqueue']));               

            foreach($eventOptions as $optionName=>$optionData)
            {
                self::_out(" - - ".$optionName." = ".$optionData['VALUE'], true);
            }
                    
            if(key_exists($eventName, self::$eventsForProcess))
            {
                self::_out(" - Event need preprocessing", true);    
                                
                $processMethodName = self::$eventsForProcess[$eventName];
                
                self::$processMethodName($eventData, $eventOptions);      
                
                /*foreach($eventOptions as $optionName=>$optionData)
                {
                    self::_out(" - - ".$optionName." = ".$optionData['VALUE'], true);
                }*/     
            }
            
            // Если этот ивент ускорен
            if (SI_SearchEvents::GetIsEventAccelerated($eventData['id_eventqueue'])) {
                $eventData['accelerated'] = 1;
            }
            
            self::$userInstanceResult->setEvent($eventData, $eventOptions); 
            //self::_out(" - Event stored");        
        }

    
            
    }   
    
    static private function _processGoodDelivery(&$eventData, &$options)
    {
        assert(is_array($eventData));
        assert(is_array($options));
        
        //$options['ID_Seller']['VALUE'] = $options['ID_Customer']['VALUE'];
        $options['ID_SellerComb']['VALUE'] = $options['ID_CustomerComb']['VALUE'];
        
        self::_helpTransefArtefact($eventData, $options);
    }
    
    static private function _processPaymentDelivery(&$eventData, &$options)
    {
        assert(is_array($eventData));
        assert(is_array($options));
        
        //$options['ID_Customer']['VALUE'] = $options['ID_Seller']['VALUE'];
        $options['ID_CustomerComb']['VALUE'] = $options['ID_SellerComb']['VALUE'];
    }
    
    static private function _processTrading(&$eventData, &$options)
    {
        assert(is_array($eventData));
        assert(is_array($options));

        $tradeData = SI_SearchEvents::GetTradeData($eventData['id_eventqueue']);
        if (!is_array($tradeData)) return;
                
        if($tradeData['id_seller'] != self::$userInstanceResult->getUIID())
        {
            $eventData['ForReturn'] = 'BIND';
            $eventData['ID_Customer'] = $tradeData['id_currentcustomer'];
            $eventData['ID_CustomerComb'] = $tradeData['id_customercomb'];
            $eventData['CurrentPrice'] = $tradeData['currentprice'];
            $eventData['ID_TadeBy'] = $tradeData['id_tradeby']; 
        }else{
            $eventData['ForReturn'] = 'TRADE';
            $eventData['CurrentPrice'] = $tradeData['currentprice'];
            $eventData['ID_TadeBy'] = $tradeData['id_tradeby'];         
            $eventData['ID_Seller'] = $tradeData['id_seller'];
            $eventData['ID_SellerComb'] = $tradeData['id_sellercomb'];
        }
                
        self::_helpTransefArtefact($eventData, $options);
    }
    
    static private function _precessTrainWarUnits(&$eventData, &$options)
    {
        assert(is_array($eventData));
        assert(is_array($options));

        $res = SI_SearchEvents::GetUnitAttributeByTapeAndName($options['ID_Unit']['VALUE'], 'PRODUCTION_COSTS', 'CONSTRUCTION_TIME');       
                        
        $eventData['enddelta'] = $eventData['startdelta'] + $options['CountToTrain']['VALUE'] * $res['value'];
        
        self::_out(" - - Process result timedelta FROM: ".$eventData['startdelta']." TO: ".$eventData['enddelta'] , true);
    }
    
    
    
    static private function _helpTransefArtefact(&$eventData, &$options)
    {
        assert(is_array($eventData));
        assert(is_array($options));

        assert(key_exists('ID_Category', $options));
        if((int)$options['ID_Category']['VALUE'] != 3)
        {
            return;
        }
        
        assert(key_exists('ID_Article', $options));
        $artefactID = (int)$options['ID_Article']['VALUE'];
        
        //$artefactID = (int)SI_SearchComb::GetArtIDByInstance($options['ID_Article']['VALUE']);
        assert($artefactID > 0);
        
        $eventData['ID_Artefact'] = $artefactID;
    }

    static private function _convertOptionsToAssocArray($options)
    {
        assert(is_array($options));
        
        $res = array();
        
        foreach($options as $k=>$optionData)
        {
            $res[$optionData['optionname']]['VALUE'] = $optionData['value'];
            $res[$optionData['optionname']]['ID'] = $optionData['id_eventoption'];
        }
        
        return $res;
    }   
}

 

?>