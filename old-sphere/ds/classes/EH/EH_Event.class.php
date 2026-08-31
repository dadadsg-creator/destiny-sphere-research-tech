<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2010/07/07 08:46:10 $';


/**
 * Базовый класс для всех обработчиков
 *
 * Использует:
 *  - Являеться потомком GameBase
 *  $GLOBALS['params']
 *  $_SESSION["comb"]
 *  $GLOBALS['common']
 *  $_SESSION["lang"]
 */

class EH_Event extends GameBase {

    /** Хранит параметры ивента */
    public $eventparams = array ();

    public  $eqid;

    public $errormsg;

    public $sysmsg;

    public $eventstarttime;

    public $eventendtime;

    public $combresupdate  = array ();

    public $halfrefresh;

    /** Список параметров  */
    public $params = array ();

    /** UIID пользователя с которым работаем */
    public $uiid = 0;   

    /** ID языка текущего */
    public $langId = 0;

    public $common;

    public $worldSettings;

    public $rehandle;

    public $new;

    public $checktime;

    /**
     * Конструктор класса
     */
    function EH_Event()
    {
        GP_SystemStatistic::AddToArray('USERACTION', 'HAHDLERS_STARTED', get_class($this));

        /** Конструтор родителя */
        $this->GameBase();

        $this->common = DS_Common::CreateForCurentDB();

        $this->worldSettings = DE_WorldSettings::CreateForCurentWorld();

        //Экземпляр класса для работы с системными сообщениями
        //$this->sysmsg = SystemMessages::GetInstance();

        //Устанавливаем params
        $this->setParams ( $GLOBALS['params'] );

        //Устанваливаем $this->comb
        $this->setComb ( $_SESSION["comb"] );

        //Устанавливаем $this->uiid
        $this->setUIID ( $GLOBALS['params'] );

        //Устанавливаем $this->langId
        $this->setLangId ( $_SESSION["lang"] );
        
    }//END OF METHOD EH_Event ()


    /**
     *  Берет параметры эвента
     */
    protected function getEventParams () {

        $event = DE_EventQueue::CreateFromID((int)$this->eqid);

        $this->eventstarttime = $event->getStartDate()->getPgFormat();
        $this->eventendtime = $event->getEndDate()->getPgFormat();

        $this->eventparams = $event->getAttributes()->getAttributesAsArray();

        return true;
    }//END OF METHOD getEventParams ()

    /**
     * Интерфес - Проверяет параметры Ивента
     */
    protected function checkEventParams () {
        return true;
    }//END OF METHOD checkEventParams ()

    /**
     * Интерфес - Обработчик Ивента
     */
    protected function handleEvent ( $eqid ) {
        return true;
    }//END OF METHOD handleEvent ()

    /**
     * Интерфес - Создает потомка Ивента
     */
    protected function createChildEvent () {
        return true;
    }//END OF METHOD createChildEvent ()

    /**
     * Прибивает Ивент. Помечает его как выполненный
     *
     * Принимает:
     * $eqtotrash - ID экзепляра Ивента
     */
    protected function setTrash ( $eqtotrash = "" )
    {
        if($eqtotrash == "")
        {
            $eqtotrash = $this->eqid;
        }

        $event = DE_EventQueue::CreateFromID((int)$eqtotrash);
        $event->setIsTrash(true);
        
        $containerTails = array('EH_ArmyMovementToAttack', 'EH_ArmyMovementToDefend', 'EH_BattleRound');
        
        if(in_array($event->getHandlerName(), $containerTails))
        {
            DS_Event::CleanEventTailsUnitContainer((int)$eqtotrash);    
        }else{
            DS_Event::CleanEventTailsGlobal((int)$eqtotrash);   
        }
        
        return true;
    }//END OF METHOD setTrash ()

    /**
     * Принудительный рефреш другого игрока
     *
     */
    protected function refreshUserinstance ( $refreshedUserInstanceID, $witEventHandling = 0, $eventID)
    {
        // Поправочка для захватываемых колоний
        //assert(is_int($refreshedUserInstanceID) && $refreshedUserInstanceID>=0);
        if ($refreshedUserInstanceID == 0) {
            return true;
        }
        
        assert(is_int($refreshedUserInstanceID) && $refreshedUserInstanceID>0);
        assert($witEventHandling == 0 || $witEventHandling == 1);
        assert(is_int($eventID) && $eventID>0);

        $event = DE_EventQueue::CreateFromID($eventID);

        //Так как рефреш может проихсодить не тольо по окончанию ивента, то надо определить
        //Время на которое идет рефреш.
        $checkDateObject = GB_TypeFactory::CreateBase('GB_Date', $event->getEndDate()->value);
        
        if($checkDateObject->value > time())
        {
            $checkDateObject->value = time();
        }
                
        $userInstanceObject = DE_UserInstance::CreateFromID($refreshedUserInstanceID);
                
        $refreshCommand = new CR_RefreshUserInstanceCommand($userInstanceObject);
        $refreshCommand->setRefreshType((bool)$witEventHandling, true);
        $refreshCommand->setExternalRefreshMode($checkDateObject, $event);                  
        
        $refreshController = new CR_RefreshUserInstance($refreshCommand);
        $refreshController->exec();                 
        
        return true;   
    }//END OF METHOD refreshUserinstance ()

    /**
     * Устанавливаем значение $this->common
     * Параметры:
     * $common - объект класса DS_Common
     */
    protected function setCommon ( &$common ){
        assert ( !empty ( $common ) );
        assert ( is_object ( $common ) );
        assert ( is_a ( $common, "DS_Common" ) );
        $this->common = &$common;
        return true;
    }//END OF METHOD setCommon($common)

    /**
     * Устанавливаем значение $this->params
     * Параметры:
     * $params - массив с параметрами пользователя для которого мы тисполняем
     * ивент
     */
    protected function setParams ( $params ) {
        assert ( !empty ( $params ) );
        assert ( is_array ( $params ) );
        assert ( count ( $params ) > 0 );
        $this->params = $params;
        return true;
    }//END OF METHOD setParams($params)

    /**
     * Устанавливаем значение ID соты
     * Параметры:
     * $id - целочисленное ID соты не равный 0
     */
    protected function setComb ( $id ){
        settype ( $id, 'integer' );
        assert ( $id > 0 );
        $this->comb = $id;
        return true;
    }//END OF METHOD setComb ( $id )

    /**
     * Устанавливаем значение $this->uiid
     * Параметры:
     * $params - массив с параметрами пользователя для которого мы тисполняем
     * ивент
     */
    protected function setUIID ( $params )
    {
        assert(array_key_exists("uiid", $params));
        settype($params['uiid'], 'integer');

        $this->uiid = $params['uiid'];

        return true;
    }//END OF METHOD setUIID($params)

    /**
     * Метод занимаеться установкой языка текущего
     *
     * Для свой работы требует что бы уже был устанволен $this->common
     * Параметры:
     * $langId - ID религии.Ц елое не равное нулю.
     *
     */
    protected function setLangId ( $langId )
    {
        settype($langId, 'integer');
        assert($langId > 0);

        $this->langId = $langId;

        return true;
    }//END OF METHOD setLangId ($langId)
    
    protected function _getChekTime()
    {       
        // если внешний рефреш
        if(!empty($this->checktime))
        {
            if($this->checktime == 'now()')
            {
                $checkDate = GB_TypeFactory::CreateBase('GB_Date', time());                 
            }else{
                $checkDate = GB_TypeFactory::CreateBase('GB_Date', $this->checktime);
            }
        }else{          
            $checkDate = GB_TypeFactory::CreateBase('GB_Date', time());                         
        }       
        
        return $checkDate;
    }
    
    protected function _isTimeToHandle()
    {
        $checkDate = $this->_getChekTime();     

        $event = DE_EventQueue::CreateFromID((int)$this->eqid);

        return $event->getEndDate()->value <= $checkDate->value;
    }//END OF METHOD

    protected function  _getTimePeriodToEnd()
    {
        $timeToEnd = strtotime($this->eventendtime) - time();
        return GB_TypeFactory::CreateConstant('GB_TimePeriod', (int)$timeToEnd);
    }

    protected function _getEndDateWithTimeDelta()
    {
        if(!isset($_SESSION['time_delta']))
        {
            $delta = 0;
        }else{
            $delta = $_SESSION['time_delta'];
        }

        $event = DE_EventQueue::CreateFromID((int)$this->eqid);

        $time = $event->getEndDate()->value + $delta * 60 * 60;

        return GB_TypeFactory::CreateBase('GB_Date', $time);
    }

    protected function _getComplitPart()
    {
        // если внешний рефреш
        if(!empty($this->checktime))
        {
            $checkDate = GB_TypeFactory::CreateBase('GB_Date', $this->checktime);
        }else{
            $checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
        }

        $event = DE_EventQueue::CreateFromID((int)$this->eqid);

        $allTime =  $event->getEndDate()->value - $event->getStartDate()->value;

        if($allTime < 0)
        {
            $allTime = 0;
        }

        $passedTime = $checkDate->value - $event->getStartDate()->value;

        if($passedTime < 0)
        {
            $passedTime = 0;
        }

        if($allTime == 0)
        {
            return 1;
        }

        if($passedTime == 0)
        {
            return 0;
        }

        $return = $passedTime / $allTime;

        if($return > 100)
        {
            $return = 100;
        }
        
        if($return < 0)
        {
            $return = 0;
        }
        
        return $return;
    }

    /** 
     * Рассчет коочлесиват построенных юнитов когда е время окончания не пришло 
     * */
    protected function _countTmpTrainedUnitsCount($timetoTrainOne)
    {
        settype($timetoTrainOne, 'integer');
        assert($timetoTrainOne > 0);
        
        // если внешний рефреш
        if(!empty($this->checktime))
        {
            $checkDate = GB_TypeFactory::CreateBase('GB_Date', $this->checktime);
        }else{
            $checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
        }

        $event = DE_EventQueue::CreateFromID((int)$this->eqid);
        
        $period = $checkDate->value - $event->getStartDate()->value;
        $trained = floor($period / $timetoTrainOne);
                
        
        return $trained;        
    }
    
    public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
    {
        return $event->getEndDate()->value < $chekDate->value;
    }
}
?>