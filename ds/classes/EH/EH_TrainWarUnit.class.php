<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Обработчик события строительства военных юнитов. 
 * Дико ривая я вещь, я ее просто ненавижу.!!! особенно из-за того что она обрабатываеться не только по окончанию. 
 */
class EH_TrainWarUnit extends EH_Event
{
    /**
     * Объект сущности табличек
     *
     * @var DE_UnitInProduction
     */
    private $unitInProduction;

    /**
     * Сота на которой производим
     *
     * @var DE_Comb
     */
    private $combObject;

    /**
     * Юнит который производим
     *
     * @var DE_Unit
     */
    private $unit;

    /**
     * Колличество юнитов которое поставленно для тернировки сейчас в ивенте
     *
     * @var integer
     */
    private $countToTrain;

    /**
     * Сколько народу было уже натренированно во время промежуточных внешних рефрешей
     *
     * @var integer
     */
    private $trainedCountAccumulator = 0;

    /**
     * колли чество натренированных за данную итерацию юнитов
     *
     * @var integer
     */
    private $trained = 0;

    /**
     * ID здания где работаем
     *
     * @var integer
     */
    private $attachedBuildingID;

    /**
     * Время на строительство одного юнита в секундах
     *
     * @var integer
     */
    private $timetoTrainOne;

    /**
     * Дата 
     *
     * @var GB_Date
     */
    private $chekDate;

    /**
     * Ивент который обрабатываем 
     *
     * @var DE_EventQueue
     */
    private $event;

    /**
     * Стату текуший ивента ('IN_PROGRES', 'ENDED')
     *
     * @var string
     */
    private $eventStatus;

    function __construct()
    {
        EH_Event::EH_Event ();
    }

    function handleEvent($eqid)
    {
        $this->_init($eqid);

        //Если усе уже построили то прибыиваем ивент. Тупо затыка от глюки, когда етсь ивент но нет для него записи в ds_unitinproduction
        if($this->unitInProduction->isEmpty())
        {
            $this->setTrash();
            return true;
        }

        // Если все уже произвели то точно удаляемся
        if($this->countToTrain <= 0)
        {
            $this->unitInProduction->delete();
            $this->setTrash();

            return true;
        }

        if ($this->_isTimeToHandle())
        {
            $this->_handle();
        } else {
            $this->_preHandle();
        }

        $this->_sendMessage();

        return true;
    }

    private function _init($eqid)
    {
        $this->eqid = (int)$eqid;
        assert($this->eqid > 0);

        $this->event = DE_EventQueue::CreateFromID($this->eqid);

        $this->chekDate = $this->_getChekTime();

        assert($this->getEventParams());

        //Сколько всего юнитов стоит на постройку в ивенте
        $this->countToTrain = (int)$this->eventparams["CountToTrain"];
        assert($this->countToTrain >= 0);

        //Сколько народу было натренированно в о время внешних рефрешей
        $this->trainedCountAccumulator = (int)$this->eventparams["TrainedCountAccumulator"];
        assert($this->trainedCountAccumulator >= 0);

        //Здание в котором производим
        $this->attachedBuildingID = (int)$this->eventparams["ID_AttachedBuilding"];
        assert($this->attachedBuildingID > 0);

        //Сота на котором сторим
        $this->combObject = DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"]);

        //Юнит который строим
        $this->unit = DE_Unit::CreateFromID((int)$this->eventparams["ID_Unit"]);

        //ОБъект сущности в БД ds_unitinproduction
        $this->unitInProduction = DE_UnitInProduction::CreateFromID($this->attachedBuildingID);

        //Время на строительсов одного юнита
        $this->timetoTrainOne = (int)ceil($this->unit->getAttributes()->getAttribute('PRODUCTION_COSTS', 'CONSTRUCTION_TIME') * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60);
        if ($this->combObject->getUserInstance()->buildSpeedupEndTime > time()) $this->timetoTrainOne /= 2;
        assert($this->timetoTrainOne > 0);
    }

    private function _handle()
    {
        //Сколько всего было поставленно на тренировку, столько и натренировалось
        $this->trained = $this->countToTrain;

        //ДОбавляем в домашку что наделали оглаеды.
        ME_AddUnitsToHomeArmy::AddUnit($this->combObject, $this->unit, (float)$this->countToTrain, $this->chekDate, true);

        // прекращаем производство
        $this->unitInProduction->delete();

        //  прибиваем эвент
        $this->setTrash();

        ME_EventMonitor::$needChecks = TRUE;

        //Отметка в трассе апкипа
        DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combObject, $this->chekDate);

        //Ставим флаг состояния объекта
        $this->eventStatus = 'ENDED';
    }

    private function _preHandle()
    {
        //Время в секундах которое прошло с момента начала ивента до момента обработки
        $timeDeltaAfterStart = $this->chekDate->value - $this->event->getStartDate()->value;

        //Корректируем сообщения на случай внешнего рефреша
        $unitTrainAcccumulator = $this->event->getAttributes()->getAttribute('TrainedCountAccumulator');

        //Если не пришло время тсроительство даже одного юнита то хрен ли тту делать вобще?
        if($timeDeltaAfterStart < $this->timetoTrainOne)
        {
            // если было что-то в акумуляторе, то будет сообщение
            $this->event->getAttributes()->setAttribute('TrainedCountAccumulator', 0);
            return;
        }

        //Рассчитываем сколько юнитов успели уже построить
        $this->trained = floor($timeDeltaAfterStart / $this->timetoTrainOne);

        //Затыка очредная. Не Мало ли что. по хорошому бы сюда assert надо и половить тех кто с кривыми ивентами
        if($this->trained > $this->countToTrain)
        {
            $this->trained = $this->countToTrain;
        }

        //Добавили что понатсроили в домашку
        ME_AddUnitsToHomeArmy::AddUnit($this->combObject, $this->unit, (float)$this->trained, $this->chekDate, true);

        //Уменьшили колличество на сколько построили
        $this->event->getAttributes()->deltaAttribute('CountToTrain', -$this->trained);
        $this->unitInProduction->deltaAmount((int)-$this->trained);

        //Сдвигаем время старта иента
        $newEventStartTime = GB_TypeFactory::CreateBase('GB_Date', $this->event->getStartDate()->value + $this->trained * $this->timetoTrainOne);
        $this->event->setStartDate($newEventStartTime);

        //Если внешний рефреш или же работа рефреш демона то корректируем значения акумултора колличества построенных уже.
        if($this->halfrefresh || RD_AdeptUserActionToConsole::$commandLineFlag)
        {
            $this->event->getAttributes()->deltaAttribute('TrainedCountAccumulator', $this->trained);
        }else{
            $this->event->getAttributes()->setAttribute('TrainedCountAccumulator', 0);
        }

        // ME_EventMonitor::$needChecks = TRUE;

        //Отметка в трассе апкипа
        DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combObject, $this->chekDate);

        //Если натренировали все что надо то выходим
        if($this->trained == $this->countToTrain)
        {
            $this->setTrash();
            $this->unitInProduction->delete();
            $this->eventStatus = 'ENDED';
        }else{
            $this->eventStatus = 'IN_PROGRES';
        }
    }

    private function _sendMessage()
    {
        //Если внешний рефреш или же рефреш демон и ивент ещше не закончен то вобще не занимаемся формированием сообщения
        if(($this->halfrefresh || RD_AdeptUserActionToConsole::$commandLineFlag) && $this->eventStatus == 'IN_PROGRES')
        {
            return;
        }

        $trained = (int)($this->trained + $this->trainedCountAccumulator);

        //если ничего не натринировались то ничегои не пишем
        if($trained == 0)
        {
            return;
        }

        $leftToTrain = (int)($this->countToTrain + $this->trainedCountAccumulator);

        MC_CompilerTrainWarUnits::GetSingl()->addData($this->combObject, $this->unit, $trained, $leftToTrain);
    }

    public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
    {

        return true;
    }
}

?>