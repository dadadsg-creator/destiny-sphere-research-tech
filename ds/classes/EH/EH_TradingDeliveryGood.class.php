<?php $CVS_Version = '$Revision$ ; $Date$';

class EH_TradingDeliveryGood extends EH_Event 
{   
    
    /**
     * Экземпляр ивента скоторым работаем
     *
     * @var DE_EventQueue
     */
    private $eventQueue;
    
    /**
     * Enter description here...
     *
     * @var DE_Comb
     */
    private $sellerComb;
    
    /**
     * Enter description here...
     *
     * @var DE_Comb
     */
    private $customerComb;
    
    private $amount;
    
    private $tradeCategoryID;
    
    private $articleID;
    
    /**
     * Объект непосредственно самого товара
     *
     * @var DE_TradeGoodInterface
     */
    private $tradeGood;
    
    private $tradeCategoryName;
    
    function __construct()
    {
        EH_Event::EH_Event ();
    }

    function handleEvent($eqid) 
    {
        
        //Принимаем сведения о ивенте который орббрабатываем
        assert(is_int($eqid) && $eqid > 0);
        
        $this->eqid = $eqid;
        
        $this->eventQueue = DE_EventQueue::CreateFromID($this->eqid);
        
        if(!$this->checktime)
        {
            $this->checktime = 'now()';
        }
        
        assert($this->getEventParams());
        
        //Принимаем параметры
        $this->sellerComb = DE_Comb::CreateFromID((int)$this->eventparams['ID_SellerComb']);
        
        $this->customerComb = DE_Comb::CreateFromID((int)$this->eventparams['ID_CustomerComb']);
        
        $this->amount = (float)$this->eventparams['Amount'];
        assert($this->amount > 0);
        
        $this->tradeCategoryID = (int)$this->eventparams['ID_Category'];
        assert($this->tradeCategoryID > 0);
        
        $this->articleID = (int)$this->eventparams['ID_Article'];
        assert($this->articleID > 0);
        
        $this->tradeCategoryName = DS_Trade::GetTradeCategoryNameByID($this->tradeCategoryID);
        assert(!empty($this->tradeCategoryName));
        
        $this->tradeGood = ME_TradeCreateGoodObjet::Exec($this->tradeCategoryName, $this->articleID);
        
        //Обрабюытваем
        if($this->_isTimeToHandle()) 
        {
            $this->_handle();
        }else{
            $this->_preHandle();
        }
        
        return true;        
    }//END OF METHOD
    
    
    private function _preHandle()
    {
        $messparams[0] = "{b}".GB_Integer::Format($this->amount, ' ')."{/b}";
        $messparams[1] = "{b}".$this->tradeGood->getLocalizedName()."{/b}";
        
        $timeParts = $this->_getTimePeriodToEnd()->getWithParts();  

        $messparams[2] = "{b}".$this->customerComb->getFullName()."{/b}";
        $messparams[3] = $timeParts['DAYS'];
        $messparams[4] = $timeParts['HOURS'];
        $messparams[5] = $timeParts['MINUTES'];
        $messparams[6] = $timeParts['SECONDS']; 
        
        //Купленный товар (% %) будет доставлен на соту % через % д % ч % м % с.
        DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_DELIVERY_MESS2", $messparams, 0, 'YELLOW', 'NOACCUMULATE');
    }
    
    private function _handle()
    {
        $messparams[0] = "{b}".GB_Integer::Format($this->amount, ' ')."{/b}";
        $messparams[1] = "{b}".$this->tradeGood->getLocalizedName()."{/b}";
        $messparams[2] = "{b}".$this->customerComb->getFullName()."{/b}";
        
        DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "FINISH_DELIVERY_MESS2", $messparams, 0, 'YELLOW' );
        
        //Если это арты то немного подругому обрабатываем
        if($this->tradeCategoryID == 3)
        {
            ME_TradeArtefactInstancesOnTradeLotWork::AddArtfactsFromEventToComb($this->eqid, $this->customerComb);
        }else{
            ME_DeltaResourcesOnComb::Exec($this->tradeCategoryName, $this->customerComb, $this->articleID, $this->amount, $this->eventQueue->getEndDate()); 
        }
        
        
        $this->eventQueue->setIsTrash(true);
    }
}//END OF CLASS

?>