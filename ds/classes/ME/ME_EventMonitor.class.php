<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2008/12/22 12:32:21 $';

/**
 Небольшое замечание.
 Сейчас впозможна ситуация слеждущего вида:
 Человек атаковал меня.
 Я атаковал его.
 Я рефрешу соту ....
 Сперва долж на приехать на меня его аормия.
 Она дереться.
 Формируеться ивент движения армии домой .. по времени одна должна успеть к прилету моей армии туда ....
 начинаеться далее обработка ататки моей армии ... оба эти случаи в одном рефеше орбрабатываються ...
 И его армии там не оказыватьеся .. так как не попадает в коолецию его ивентов ивент созданый мною ... и не обработанный мною.
*/


class ME_EventMonitor extends BS_Base
{
    private $userInstance;
    
    private $eventsCollection;
            
    private $eventLocker;
    
    private $checkDate;
    
    private $externalRefreshMode = false;
    
    /**
     * Частичный рефреш
     *
     * @var bool
     */
    private $partialRefreshMode = false;
    
    private $errormsg = ''; 
    
    public  $external_refresh = 0;
    
    private $refreshedEventID = array();
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   
    static private $eventsOnWorkID = array();

    static public $needChecks = FALSE;
    
    /**
     * Колличество ивентов уже обработанных монитором
     *
     * @var integer
     */
    static public $eventNumber = 0; 
    
    /** 
     * Во ремя внешнего рефреша часть ивенто вотказываеться в иполнение по причинам разным. 
     * Что бы они не пропали из очереди их после этого возвращают на место. Так как 
     * это часто ивенты которые рано было еще исоплнять.
     * */
    private $eventsIDToReturn = array();
    
    public function __construct($userInstanceID)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        
        parent::__construct();
        
        settype($userInstanceID, 'integer');
        
        $this->userInstance = DE_UserInstance::CreateFromID($userInstanceID);
    
        $this->checkDate = GB_TypeFactory::CreateBase('GB_Date', (int)time());
        
        $this->eventsCollection = DC_EventInstance::CreateFromUserInstanceID($userInstanceID);

        $this->_invariant('construct');
    }
    
    static public function CreateFromUserInstance(DE_UserInstance $userInstance)
    {                   
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $userInstance->getID());
    }   
    
    public function getID()
    {       
        return $this->userInstance->getID();
    }
    
    /** Устанавливаем режим внешнего рефреша */
    public function setExternalRefreshMode(GB_Date $checkDate, DE_EventQueue $refreshedEvent)
    {       
        $this->checkDate = $checkDate;      
        
        $this->externalRefreshMode = true;
        
        $this->refreshedEventID[] = $refreshedEvent->getID();

        $this->eventsIDToReturn = array();
    }   
    
    public function setPartialRefreshMode(GB_Date $checkDate)
    {
        $this->checkDate = $checkDate;
        
        $this->partialRefreshMode = true;
    }
    
    function Scan()
    {               
        $listEH = '';       
        
        //Проверям если пользователь в режиме отпуска то не обрабатываем ивенты
        if(GL_IsUserInVocationMode::Test($this->userInstance))
        {
            return true;
        }
        
        //Если человек находиться под щитом.
        if(DE_PowerShild::CreateFromUserInstance($this->userInstance->getID())->isWorks())
        {
            return true;
        }
        
        //ЛОичм проецц обработки ивентов для данного инстанаса
        if(!SU_Locker::CerateForCurrentDB('USERACTION')->lockWithTest($this->userInstance->getID()))
        {
            DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $this->userInstance->getID(), 'USERACTION');           
        }
        
        //Помечаем стартовое значение апкипа при расчете трассы изменения апкипа
        ME_MarkStartUpkeepForRefresh::Count($this->userInstance);
        
        //Защита от зацикливания
        $deadCirlceDetector = 0;
        
        // Засекаем время
        $timeStart = time();
        
        //Перебираем все ивенты из коллекции
        while($event = $this->eventsCollection->fetchNextEvent())
        {
            //ЗАщита от зацикливания
            /*
            $deadCirlceDetector++;
            
            if($deadCirlceDetector > 20000)
            {
                $messparams[0] = "";
                DE_SystemMessages::AddMessageFromAction(__CLASS__, "PROCCESSED_NOT_ALL_EVENTS", $messparams, 0, 'RED');
                return true;

                //ds_error('Illegal events count on user '.$this->userInstance->getUser()->getName().' '.$deadCirlceDetector.'. Please write to support.');   
            }
            */
            // Проверяем, не вышло ли время для обработки ивентов
            // Засекаем время
            $time = time();
            
            // Если мы слишком долго обраватываем иветны, то пора сворачивать удочки (4 минуты)
            if ($time - $timeStart > 240) {
                // Кидаем игроку сообщение 
                $messparams[0] = "";
                DE_SystemMessages::AddMessageFromAction(__CLASS__, "PROCCESSED_NOT_ALL_EVENTS", $messparams, 0, 'RED');
                return true;
            }
            
            //Елси связь с клиентом прервана то прекращаем обработку и выходим.
            if(connection_aborted())
            {
                GB_SingletonObjectStorage::CommitAllCommitable();
                ME_EndDBTransactions::Exec();
                exit;
            }
            
            //Если уде трешевый ивент то пропускаем его
            if($event->getIsTrash()->value) 
            {
                continue;
            }
            
            //Если иент этот уже  вработе сейчас то его необрабатываем
            if(in_array($event->getID(), self::$eventsOnWorkID))
            {
                //Если не стали обрабатывтаь то возвращаем на место ивент               
                $this->eventsIDToReturn[] = $event->getID();                
                continue;
            }               
            //Проверки необходимости обработки ивента по внешнему рефрешу
            if($this->externalRefreshMode)          
            {

                //Если это ивент ненадо обрабатывать по внешнему рефрешу то пропускаем его
                if(!$event->getHandler()->handleInExternalRefresh($event, $this->userInstance, $this->checkDate))
                {
                    //Если не стали обрабатывтаь то возвращаем на место ивент
                    $this->eventsIDToReturn[] = $event->getID();
                    continue;
                }
                                
                //Если это то ивент которыкей вызвал внешний ревреш то его тоже пропускаем 
                if(in_array($event->getID(), $this->refreshedEventID))
                {
                    $this->eventsIDToReturn[] = $event->getID();
                    continue;
                }                                   
            }

            //заносим ивент который пошел на обработку в список обработанных уже за этот рефреш ивентов
            self::$eventsOnWorkID[] = $event->getID();
            
            //Если рефреш внешний то задаем нужные параметры
            if($this->externalRefreshMode)
            {
                $event->getHandler()->halfrefresh = 1;
                $event->getHandler()->checktime = $this->checkDate->getPgFormat();
            }
            
            if($this->partialRefreshMode)
            {
                $event->getHandler()->checktime = $this->checkDate->getPgFormat();
            }
            
            // даем команду обработчику его обработать
            if(!$event->getHandler()->handleEvent((int)$event->getID())) 
            {                   
                // DS_Error($event->getHandler()->errormsg);
            }
            
            //Увеличли счетчик обработанных ивентов
            if($event->getEndDate()->value < $this->checkDate->value)
            {
                self::$eventNumber++;
            }

            if($event->getHandler()->rehandle == 1) 
            { // если стоит флаг повторной обработки, заносим в список для повторной обработки
                $this->eventsCollection->addEvent($event->getID());
            }
            
            if($event->getHandler()->new == 1) 
            { // если порождены новые эвенты, добавляем их в список обработки
                for($e=0; $e<sizeof ($event->getHandler()->newevents ); $e++) 
                {   
                    $eventQueue = DE_EventQueue::CreateFromID((int)$event->getHandler()->newevents[$e]['id_eventqueue']);
                    $eventParticipantCollection = $eventQueue->getEventParticipants();
                                        
                    $eventParticipantCollection->resetNext();
                    while($participant = $eventParticipantCollection->next())
                    {
                        
                        /*if ($participant->getID() == 0) {
                            continue;
                        }*/
                        DC_EventInstance::CreateFromUserInstanceID($participant->getID())->addEvent($eventQueue->getID());
                    }                                                                                   
                }
            }                                                               
        }//END OF while()           

        
        //Если имеем дело с внешним рефрешем и есть иент ыдля возврата то возвращаем
        if($this->externalRefreshMode && count($this->eventsIDToReturn) > 0)            
        {
            foreach($this->eventsIDToReturn as $k=>$eventID)
            {
                $this->eventsCollection->addEvent($eventID);
            }
            
            $this->eventsIDToReturn = array();
        }

        if (ME_EventMonitor::$needChecks) {
            // Проверяем защиту новичков
            GL_NewbDefendTestArmyPower::SetNeedCheckCurrentUserInstanceFlag();

            // Проверяем смену лиги
            $this->_checkLevelLeague();
        }
            
        return true;
    }


    private function _checkLevelLeague()
    {
        // Если лиги выключены, ничего не делаем
        if (DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_ENABLED') != 1) {
            return;
        }

        // Текущая лига
        $CurLeague = $this->userInstance->getLeagueId();
        if ($CurLeague == 3) return;

        $userInstanceArmys = DC_UserInstanceArmys::CreateFromUserInstanceID($this->userInstance->getID());

        $totalArmysPower = 0.0; 

        $userInstanceArmys->resetNext();
        while(false !== ($army = $userInstanceArmys->next()))
        {
            $totalArmysPower += $army->getArmyPower();
        }

        // Лига, соответствующая по суммарному весу армий
        $league = DS_League::GetLeagueArmyes($totalArmysPower/10);
        
        // Если есть соответствующая времени жизни лига и она больше от текущей
        if ($league && ($league['id_league'] > $CurLeague)) {
                // Меняем лигу
                $this->userInstance->setLeagueId((int)$league['id_league']);

                // Сообщение
                $messparams = array($culLevel, $league['league_name']);
                $date = GB_TypeFactory::CreateConstant('GB_Date', time());
                // Командор, стало известно, что Вы обладаете слишком большим количеством мощных армии, дислоцирующихся на Ваших сотах. Принято решение перевести Вас в Лигу %, которой соответствуют такие масштабы! Поздравляем!
                DE_SystemMessages::AddMessageFromMessageCompiler($date, $this->userInstance->getID(), 'NEXT_LEAGUE_ARMIES', $messparams, 0, 'BLUE');

                // Сообщение в зеленку
                $messageScopeID = DS_Message::GetMessageScopeIDByName('PRIVATE');
                assert($messageScopeID > 0);
                $senderName = 'DestinySphere';
                $subject = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE', array(), null, null, false);
                $recieverName = DE_User::CreateFromID($this->userInstance->getUserID())->getName();
                $fromUIID = 0;

                $message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE_ARMIES', $messparams, null, null, false);
                $messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);

                DS_Message::AddMessageToUser($messageID, $this->userInstance->getID());
        }
    }

        
    private function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;
        
        assert($this->userInstance instanceof DE_UserInstance);
        assert($this->eventsCollection instanceof DC_EventInstance);        
        assert($this->eventLocker instanceof LockUIID);
        assert(is_bool($this->externalRefreshMode));
        assert($this->external_refresh == 0 || $this->external_refresh == 1);
        assert($this->checkDate instanceof GB_Date);
        assert($this->errormsg == '');
    }
    
    public function __destruct()
    {
        parent::__destruct();
    }
}
?>