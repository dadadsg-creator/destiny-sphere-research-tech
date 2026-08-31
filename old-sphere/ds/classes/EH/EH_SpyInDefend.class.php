<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Обработка евента стояния зондов на защите соты
 *
 */
class EH_SpyInDefend extends EH_Event 
{
    /**
     * На какой соте стоят зонды
     *
     * @var DE_Comb
     */
    private $combFrom;
        
    /**
     * Евент
     *
     * @var DE_EventQueue
     */
    private $event;
    
    /**
     * Юнит
     *
     * @var DE_Unit
     */
    private $unit;
    
    /**
     * Кол-во юнитов
     *
     * @var unknown_type
     */
    private $amount;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Обработка
     * вся обработка - это написание сообщений 
     * а по окончанию - возвращение зондов на соту
     *
     * @param unknown_type $eqid
     * @return unknown
     */
    public function handleEvent($eqid)
    {
        assert($eqid > 0);
        parent::handleEvent($eqid);
        $this->eqid = $eqid;
        
        $this->event = DE_EventQueue::CreateFromID($eqid);
        $eventAttributes = $this->event->getAttributes();
        
        $this->combFrom = DE_Comb::CreateFromID((int)$eventAttributes->getAttribute('ID_CombFrom'));
        $id_unit = (int)$eventAttributes->getAttribute('ID_Unit');
        if (!$id_unit) {
            $this->event->setIsTrash(true);     
            $this->event->commit();
            return true;
        }
        $this->unit = DE_Unit::CreateFromID($id_unit);
        $this->amount = (int)$eventAttributes->getAttribute('Amount');
        
        if($this->_isTimeToHandle())
        {
            $this->_handle();   
            $this->event->setIsTrash(true);     
            $this->event->commit();     
        }else{
            $this->_preHandle();
        }
        
        return true;
    }
    
    private function _handle()
    {
        // свободные зонды хранятся в атрибутах соты
        $combAttributes = $this->combFrom->getAttributes();
        $combAttributes->deltaAttribute('AWAY_AMOUNT', $this->unit->getName(), -$this->amount);
        $combAttributes->deltaAttribute('CURRENT_AMOUNT', $this->unit->getName(), $this->amount);
        
        $messparams = array();
        $messparams[0] = $this->amount;
        $messparams[1] = $this->combFrom->getFullName();
        
        // Группа из % зондов вернулась с защиты соты %.
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "SPY_RETURNED_FROM_DEFEND", $messparams, 2, 'GREEN', $this->combFrom->getUserInstanceID());
    }
    
    private function _preHandle()
    {
        $messparams = array();
        $messparams[0] = $this->amount;
        $messparams[1] = $this->combFrom->getFullName();
        $messparams[2] = $this->event->getEndDate()->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta());
        
        // Группа из % зондов вернется с защиты соты % %.
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "SPY_IN_DEFEND", $messparams, 2, 'GREEN', $this->combFrom->getUserInstanceID());
    }
}

?>