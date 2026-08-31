<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Предок классов обработчиков боя
 *
 */
class EH_Army extends EH_Event
{
    /**
     * Время для которого проводим рефреш. Используеться для внешнего рефреша
     *
     * @var string
     */
    public  $checktime;

    /**
     * Класс игровой логики проверяющий могу ли я атаковать эту соту
     *
     * @var GL_CanAttackComb
     */
    protected $canAttack;

    /**
     * Сота с которой летим
     *
     * @var DE_Comb
     */
    protected $combFrom;

    /**
     * Сота на которую летим
     *
     * @var DE_Comb
     */
    protected $combTo;

    /**
     * Армия с которй происходит все действия
     *
     * @var DE_Army
     */
    protected $army;
    
    /**
     * Армия защитника
     *
     * @var DE_CombDefendArmy
     */
    protected $combDefendArmy;
        
    /**
     * Бой который осчитываем
     *
     * @var DE_Battle
     */
    protected $battle;
    
    /**
     * ОБъект с датой окончани яивента
     *
     * @var GB_Date
     */
    protected $eventEndTimeObject;
    
    /**
     * Объект даты начала ивента
     *
     * @var GB_Date
     */
    protected $eventStartTimeObject;
    
    /**
     * ID исполняемого ивента
     *
     * @var integer
     */
    public $eqid;   
    
    /**
     * Enter description here...
     *
     * @var DE_EventQueue
     */
    protected $eventQueue;
    
    /**
     * Контроллер боя
     *
     * @var CR_Battle
     */
    protected $battleController;
    
    public function __construct()
    {
        $this->EH_Event();
        
        $this->canAttack = new GL_CanAttackComb();
    }
    
    public function handleEvent($eqid)
    {
        assert((int)$eqid > 0);
        $this->eqid = (int)$eqid;
        
        $this->eventQueue = DE_EventQueue::CreateFromID($this->eqid);
        
        if($this->_isTimeToHandle() && $this->eventQueue->isLockedForHandl())
        {
            DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $this->eqid, 'EVENT_QUEUE');                       
        }       
        
        // получаем параметры
        $this->getEventParams();
        if (!count($this->eventparams)) {
            $this->setTrash();
            return false;
        }

        $combToID = (int)$this->eventparams["ID_CombTo"];
        $combFromID = (int)$this->eventparams["ID_CombFrom"];
        $armyID = (int)$this->eventparams["ID_Army"];

        assert($combToID > 0);
        assert($combFromID > 0);
        if ($armyID <= 0) {
            $this->setTrash();
            return false;
        }

        $this->combTo = DE_Comb::CreateFromID($combToID);
        assert($this->combTo instanceof  DE_Comb);
        
        $this->combFrom = DE_Comb::CreateFromID($combFromID);
        assert($this->combFrom instanceof  DE_Comb);
        

//А тут ловим ивенты с несуществующими армиями
//Кажись через раздвоение ивентов что-то да проскакивает
$armyData = DS_Army::GetByID($armyID);
if(!$armyData)
{
$event = DE_EventQueue::CreateFromID((int)$this->eqid);
$str = "
Event: FROM ".$this->eventstarttime." TO ".$this->eventendtime." ($eqid)
CombFrom: ".$this->combFrom->getFormatedAddress()." OF ".$this->combFrom->getUserInstance()->getUser()->getName()."
CombTo: ".$this->combTo->getFormatedAddress()." OF ".$this->combTo->getUserInstance()->getUser()->getName()."";
SU_DebugTools::LogAll($str, "EmptyArmyEvent.log");
            $this->setTrash();
            return false;
}

        
        $this->army = DE_Army::CreateFromID($armyID);
        
        $this->combDefendArmy = DE_CombDefendArmy::CreateFromCombID($this->combTo->getID());
        
        $this->eventEndTimeObject = GB_TypeFactory::CreateConstant('GB_Date', $this->eventendtime); 
        
        $this->eventStartTimeObject = GB_TypeFactory::CreateConstant('GB_Date', $this->eventstarttime); 
        
        //Вставляем защиту от раздвоения ивента армии.
        //Раздвоение ивент армии это когда на одну армию висит 
        //сразу два ивента. Причем один из них он лвый и к нему нет 
        //записи ds_bindeventqueue              
        if($this->eqid != $this->army->getBindEventID())
        {
$str = "
Event: FROM ".$this->eventStartTimeObject->getPgFormat()." TO ".$this->eventEndTimeObject->getPgFormat()." ($eqid)
Army: ".$this->army->getName()."(".$this->army->getID().")
CombFrom: ".$this->combFrom->getFormatedAddress()." OF ".$this->combFrom->getUserInstance()->getUser()->getName()."
CombTo: ".$this->combTo->getFormatedAddress()." OF ".$this->combTo->getUserInstance()->getUser()->getName()."";
SU_DebugTools::LogAll($str, "DoubleArmyEvent.log");
            $this->setTrash();
            return false;
        }
        
        return true;
    }
    
    protected function _createComeBackEvent()
    {                   
        DE_BattleSystemLog::Insert("\n======== START CREATE COME BACK EVENT\n");        
        
        //Коцаем текущий ивент
        $this->setTrash ();

        if (!$this->army) return;
        
        if($this->army->getArmyPower() <= 0 )
        {
            return;
        }
        
        $this->army->setCurentComb(new DE_CombNull('Unknown'));

        $armyAttributes = DE_ArmyAttributes::CreateFromArmyID($this->army->getID());

        if($armyAttributes->haveAttribute('CURRENT_AMOUNT', 'MOVE_SPEED'))
        {
            $speedValue = (float)$armyAttributes->getAttribute('CURRENT_AMOUNT', 'MOVE_SPEED');
        }else{
            $speedValue = (float)1;
        }

        $speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', $speedValue);

        $endTime = $this->eventEndTimeObject->value + GL_TravelTime::Count($this->combFrom, $this->combTo, $speed, $this->eventEndTimeObject)->value;

        $times = new GB_DatePeriod($this->eventEndTimeObject, GB_TypeFactory::CreateConstant('GB_Date', $endTime));

        $eventParticipants = new GB_EventParticipants;
        $eventParticipants->setParticipant($this->army->getUserInstance());

        $actionData = new GB_ArmyActionCreationData('MOVE_FROM');

        $actionData->setArmy($this->army);
        $actionData->setDatePeriod($times);
        $actionData->setParticipants($eventParticipants );
        $actionData->setPath(new GB_TravelPath($this->combTo, $this->combFrom));

        $eventQueueID =(int)ME_CreateArmyAction::CreateAction($actionData);
        assert($eventQueueID > 0);
        
        $this->new = 1;
        $this->newevents[0]['id_eventqueue'] = $eventQueueID;

        $event = DE_EventQueue::CreateFromID($eventQueueID);        
            
        $strTmp  = "\n======== END CREATE COME BACK EVENT ID = $eventQueueID\n";
        $strTmp .= "START_DATE = ".$event->getStartDate()->getPgFormat()."\n";
        $strTmp .= "END_DATE = ".$event->getEndDate()->getPgFormat()."\n";      
        
        DE_BattleSystemLog::Insert($strTmp);
    }
    
    protected function _createRoundEvent(GB_TimePeriod $roundDuration, $roundNumber)
    {
        DE_BattleSystemLog::Insert("\n======== START CREATE ROUND  $roundNumber EVENT\n");          
        
        assert($this->battle instanceof DE_Battle);
        assert(is_int($roundNumber) && $roundNumber>=0);        
        
        //Коцаем текущий ивент
        $this->setTrash ();     
        
        $roundStartTime = $this->eventEndTimeObject;
        $roundEndTime = GB_TypeFactory::CreateConstant('GB_Date', $roundStartTime->value + $roundDuration->value);
        $times = new GB_DatePeriod($roundStartTime, $roundEndTime);

        $eventParticipants = new GB_EventParticipants;
        $eventParticipants->setParticipant($this->combTo->getUserInstance());
        $eventParticipants->setParticipant($this->combFrom->getUserInstance());

        $actionData = new GB_ArmyActionCreationData('IN_BATTLE');

        $actionData->setArmy($this->army);
        $actionData->setDatePeriod($times);
        $actionData->setParticipants($eventParticipants );
        $actionData->setPath(new GB_TravelPath($this->combFrom, $this->combTo));
        $actionData->setBattle($this->battle);
        $actionData->setRound(DE_BattleRound::CreateFromBattleAndNumber($this->battle, $roundNumber));

        $eventQueueID = ME_CreateArmyAction::CreateAction($actionData);
        assert((int)$eventQueueID > 0);
        
        $this->new = 1;
        $this->newevents[0]['id_eventqueue'] = $eventQueueID;

        DE_BattleSystemLog::Insert("\n======== END CREATE ROUND  $roundNumber EVENT\n");            
    }

}

?>