<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Обработка евента движения зондов в разведку
 *
 */
class EH_SpyMoveToResearch extends EH_Event 
{
    /**
     * ИД евента
     *
     * @var unknown_type
     */
    public $eqid;
    
    /**
     * С какой соты
     *
     * @var DE_Comb
     */
    private $combFrom;
    
    /**
     * На какую соту
     *
     * @var DE_Comb
     */
    private $combTo;
    
    /**
     * евент
     *
     * @var DE_EventQueue
     */
    private $event;
    
    /**
     * юнит зонд
     *
     * @var DE_Unit
     */
    private $unit;
    

    /**
     * Группа зондов, прилетевшая на разведку
     *
     * @var DE_SpyZondGroup
     */
    private $researchGroup;
    
    
    /**
     * Enter description here...
     *
     * @var DE_SpyLog
     */
    private $researchLog;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function handleEvent($eqid)
    {
        assert($eqid > 0);
        parent::handleEvent($eqid);
        $this->eqid = $eqid;
        
        $this->event = DE_EventQueue::CreateFromID($eqid);
        
        $eventAttributes = $this->event->getAttributes();
        $this->combFrom = DE_Comb::CreateFromID((int)$eventAttributes->getAttribute('ID_CombFrom'));
        $this->combTo = DE_Comb::CreateFromID((int)$eventAttributes->getAttribute('ID_CombTo'));
        $this->unit = DE_Unit::CreateFromID((int)$eventAttributes->getAttribute('ID_Unit'));
        $this->startAmount = (int)$eventAttributes->getAttribute('Amount'); 
        
        $this->researchGroup = DE_SpyZondGroup::CreateFromEventQueue($this->event);
        
        if($this->_isTimeToHandle())
        {
            $this->_handle();               
        }else{
            $this->_preHandle();
        }
        
        return true;
    }
    
    /**
     * Оработка евента
     *
     */
    private function _handle()
    {
        //Проверям можно ли атаковать соту
        if (!$this->_canAttackComb()) {
            //  Возвращаем зонды
            ME_CancelSpy::Cancel($this->researchGroup); 
            return;
        }
        // бой между зондами
        ME_SpyZondsBattle::Fight($this->researchGroup, $this->combTo);

        // генерация логов
        $this->_generateLogs();

        // если есть кому возвращаться, создаем евент движения назад
        $this->_createComeBackEvent();      
        
        // если у обороняющегося были потери, то создаем евент починки
        $this->_createRepairEvent();
        
        // если у атакующего были потери, то создаем евент починки
        $this->_createResearchRepairEvent();
        
        // не очень хороший кусочек, чистка потерь зондов
        ME_SpyZondsBattle::CleanUpLoses();
        
        $messparams = array();
        $messparams[0] = $this->startAmount;
        $messparams[1] = "&#34;".$this->unit->getUnitNameLocolized()."&#34;";
        $messparams[2] = $this->combTo->getFullName();
        $messparams[3] = $this->combTo->getUserInstance()->getUser()->getName();
        $messparams[4] = $this->combFrom->getFullName();
    
        // Группа из {b}%{/b} зондов {b}%{/b} прибыла для разведки соты {b}%{/b} игрока {b}%{/b} с соты {b}%{/b}
        //DE_SystemMessages::AddMessageFromAction(__CLASS__.'ACCUMULATE', "SPY_RESEARCHED", $messparams, 2, 'GREEN', $this->combFrom->getUserInstanceID());
        DE_SystemMessages::AddMessageFromMessageCompiler($this->event->getEndDate(), $this->combFrom->getUserInstanceID(), 'SPY_RESEARCHED', $messparams, 2, 'GREEN');

        $this->event->setIsTrash(true);     
        $this->event->commit(); 
    }

    private function _createComeBackEvent()
    {   
        // закоментирован кусок отвечающий за возврат 1 зонда, если летело в разведку более 5 штук
/*      if($this->researchGroup->getAmount() <= 0 && $this->researchGroup->getStartAmount() < DE_WorldSettings::CreateForCurentWorld()->get('SPY_MIN_ZONDS_COUNT_FOR_GET_LOG'))
        {
            return;
        }
*/      
        if($this->researchGroup->getAmount() <= 0)
        {
            return;
//          $return = 1;
//          $this->combFrom->getAttributes()->deltaAttribute('AWAY_AMOUNT', $this->unit->getName(), 1);
        } else {
            $return = $this->researchGroup->getAmount();
        }
        
        $dateFrom = $this->event->getEndDate();
        $moveSpeed = floatval(DE_WorldSettings::CreateForCurentWorld()->get('SPY_RESEARCH_GROUP_SPEED_ACLERATION_FROM') / 100);
        $time = GL_TravelTime::Count($this->combFrom, $this->combTo, GB_TypeFactory::CreateBase('GB_SpyMoveSpeed', (float)$moveSpeed));
        //$time = ceil($time / $moveSpeed);
        
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', $dateFrom->value + $time->value);
                
        $creationData = new GB_SpyActionCreationData();
        $creationData->setActionType(DE_SpyAction::CreateFromName('MOVE_BACK'));
        $creationData->setCombFrom($this->combFrom);
        $creationData->setCombTo($this->combTo);
        $creationData->setPeriod(new GB_DatePeriod($dateFrom, $dateTo));
        $creationData->setUnit($this->unit);
        $creationData->setZondsAmount($return);
        $creationData->setNeedChangeAttributes(false);
        $creationData->setLogID($this->researchLog->getID());
        
        $creationData->setCombtoOwnerParticipateInEvent(false); // не надо чтобы в евенте участвовал владелец разведанной соты
        
        $newEventQueueID = ME_SpyGroupAction::CreateGroupAction($creationData);
        
        $this->new = 1;
        $this->newevents[] = array('id_eventqueue' => $newEventQueueID);
    }
    
    private function _createRepairEvent()
    {   
        $defenceGroup = DE_SpyZondDefenceGroup::CreateFromCombID($this->combTo->getCombID());
        $defenderLoses = $defenceGroup->getStartAmount() - $defenceGroup->getAmount();
        $repairAmount = (int)ceil($defenderLoses * DE_WorldSettings::CreateForCurentWorld()->get('SPY_DEFENDER_ZONDS_REPAIR_PART'));
        
        $unitToRepair = $defenceGroup->getUnit();

        // Если у обороняющегося остался хоть один зонд на разведке, то пишем ему сообщение в желтую кнопку
        if ($defenceGroup->getAmount() > 0 ) {
            $messparams = array();
            $messparams[0] = $this->combTo->getFullName();
            $messparams[1] = $this->combFrom->getUserInstance()->getUser()->getName();
            
            $battleResult = ME_SpyZondsBattle::GetResult();
            $accuracy = GL_SpyLogAccuracy::GetForAttacker($battleResult);
            
            if($accuracy == 0 || $this->researchGroup->getAmount() == 0)
            {
                // На вашей соте {b}%{/b} была проведена разведка игроком {b}%{/b}. Данные не были получены.
                $msg = 'SPY_ATTACK_EMPTY';
            } else {
                // На вашей соте {b}%{/b} была проведена разведка игроком {b}%{/b}. Данные были получены %.
                $msg = 'SPY_ATTACK';
                $localization = array(GL_SpyLogAccuracy::EMPTY_LOG => 31065, GL_SpyLogAccuracy::LOW => 31057, GL_SpyLogAccuracy::MIDDLE => 31058, GL_SpyLogAccuracy::FULL => 31059);
                assert(key_exists($accuracy, $localization));
                $accuracyLang = DE_Localization::CreateForCurentLang()->get($localization[$accuracy]);
                $messparams[2] = $accuracyLang;
            }

            // На вашей соте % была проведена разведка игроком %
            //DE_SystemMessages::AddMessageFromAction(__CLASS__.'ACCUMULATE', $msg, $messparams, 2, 'GREEN', $this->combTo->getUserInstanceID());
            DE_SystemMessages::AddMessageFromMessageCompiler($this->event->getEndDate(), $this->combTo->getUserInstanceID(), $msg, $messparams, 2, 'GREEN');
        }
        
        if($repairAmount <= 0)
        {
            return;
        }
    
        // зонты в починке числятся в ЭВЭЕ
        $this->combTo->getAttributes()->deltaAttribute('AWAY_AMOUNT', $defenceGroup->getUnit()->getName(), $repairAmount);

        $dateFrom = $this->event->getEndDate();
        $time = (int)DE_WorldSettings::CreateForCurentWorld()->get('SPY_DEFENDER_ZONDS_REPAIR_TIME');
        $time *= (int)DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', $dateFrom->value + $time);
            
        $creationData = new GB_SpyActionCreationData();
        $creationData->setActionType(DE_SpyAction::CreateFromName('REPAIR'));
        $creationData->setCombFrom($this->combTo);
        $creationData->setPeriod(new GB_DatePeriod($dateFrom, $dateTo));
        $creationData->setUnit($unitToRepair);
        $creationData->setZondsAmount($repairAmount);
        $creationData->setNeedChangeAttributes(false);
        
        $newEventQueueID = ME_SpyGroupAction::CreateGroupAction($creationData);
        
        $this->newevents[] = array('id_eventqueue' => $newEventQueueID);
        
        $messparams = array();
        $messparams[0] = $this->combTo->getFullName();
        $messparams[1] = $repairAmount;
                    
        $unitName = DE_Localization::CreateFromLangID($this->combTo->getUserInstance()->getUser()->getLanguageID())->get($defenceGroup->getUnit()->getUnitLocolizedId());
        $messparams[2] = "&#34;".$unitName."&#34;";
            
        // На вашей соте {b}%{/b} {b}%{/b} зондов {b}%{/b} были отправлены на ремонт
        //DE_SystemMessages::AddMessageFromAction(__CLASS__.'ACCUMULATE', 'SEND_SPY_ZONDS_REPAIR', $messparams, 2, 'GREEN', $this->combTo->getUserInstanceID());
        DE_SystemMessages::AddMessageFromMessageCompiler($this->event->getEndDate(), $this->combTo->getUserInstanceID(), 'SEND_SPY_ZONDS_REPAIR', $messparams, 2, 'GREEN');

    }
    
    private function _createResearchRepairEvent()
    {   
        $researchLoses = $this->researchGroup->getStartAmount() - $this->researchGroup->getAmount();
        $repairAmount = (int)ceil($researchLoses * DE_WorldSettings::CreateForCurentWorld()->get('SPY_DEFENDER_ZONDS_REPAIR_PART'));
        
        if($repairAmount <= 0)
        {
            return;
        }
        
        $unitToRepair = $this->researchGroup->getUnit();
    
        // зонты в починке числятся в ЭВЭЕ
        $this->combFrom->getAttributes()->deltaAttribute('AWAY_AMOUNT', $unitToRepair->getName(), $repairAmount);

        $dateFrom = $this->event->getEndDate(); 
            
        // Время починки
        $time = (int)DE_WorldSettings::CreateForCurentWorld()->get('SPY_DEFENDER_ZONDS_REPAIR_TIME');
        $time *= (int)DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
        
        // Высчитываем время на возвращение
        $moveSpeed = floatval(DE_WorldSettings::CreateForCurentWorld()->get('SPY_RESEARCH_GROUP_SPEED_ACLERATION_FROM') / 100);
        $timeToComeBack = GL_TravelTime::Count($this->combFrom, $this->combTo, GB_TypeFactory::CreateBase('GB_SpyMoveSpeed', (float)$moveSpeed));
    
        // Время окончания ремонта
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', $dateFrom->value + $time + $timeToComeBack->value);
            
        $creationData = new GB_SpyActionCreationData();
        $creationData->setActionType(DE_SpyAction::CreateFromName('REPAIR'));
        $creationData->setCombFrom($this->combFrom);
        $creationData->setPeriod(new GB_DatePeriod($dateFrom, $dateTo));
        $creationData->setUnit($unitToRepair);
        $creationData->setZondsAmount($repairAmount);
        $creationData->setNeedChangeAttributes(false);
        
        $newEventQueueID = ME_SpyGroupAction::CreateGroupAction($creationData);
        
        $this->newevents[] = array('id_eventqueue' => $newEventQueueID);
        
        $messparams = array();
        $messparams[0] = $this->combFrom->getFullName();
        $messparams[1] = $repairAmount;
        $unitName = DE_Localization::CreateFromLangID($this->combFrom->getUserInstance()->getUser()->getLanguageID())->get($unitToRepair->getUnitLocolizedId());
        $messparams[2] = "&#34;".$unitName."&#34;";
            
        // На вашей соте {b}%{/b} {b}%{/b} зондов {b}%{/b} были отправлены на ремонт
        //DE_SystemMessages::AddMessageFromAction(__CLASS__.'ACCUMULATE', 'SEND_SPY_ZONDS_REPAIR', $messparams, 2, 'GREEN', $this->combFrom->getUserInstanceID());
        DE_SystemMessages::AddMessageFromMessageCompiler($this->event->getEndDate(), $this->combFrom->getUserInstanceID(), 'SEND_SPY_ZONDS_REPAIR', $messparams, 2, 'GREEN');

    }
    
    private function _preHandle()
    {
        $messparams = array();
        $messparams[0] = $this->startAmount;
        $messparams[1] = $this->combFrom->getFullName();
        $messparams[2] = $this->combTo->getFullName();
        $messparams[3] = $this->combTo->getUserInstance()->getUser()->getName();
        $messparams[4] = $this->event->getEndDate()->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta());
        
        // Группа из % зондов с соты % прибудет для разведки соты % игрока %  %
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "SPY_MOVING_TO_RESEARCH", $messparams, 2, 'GREEN', $this->combFrom->getUserInstanceID());
    }
    
    public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $checkDate)
    {
        return false;
    }
    
    
    private function _generateLogs()
    {       
        $battleResult = ME_SpyZondsBattle::GetResult();
        ME_SpyFormLog::Init($this->researchGroup, $this->combTo, $battleResult, $this->_getChekTime());

        $action = DE_SpyAction::CreateFromName('RESEARCH');
        
        $accuracy = GL_SpyLogAccuracy::GetForAttacker($battleResult);

        // Если мы в результате получаем хоть како-то лог
        if ($accuracy != 0) {           
            //Если рефреш проводит не хозяин соты на которой бой то делаем внешний рефреш 
            if($this->uiid != $this->combTo->getUserInstanceID())
            {
                assert($this->refreshUserinstance($this->combTo->getUserInstanceID(), 1, $this->eqid));
            }
        }

        $text = ME_SpyFormLog::FormAttackerLog();
        $this->researchLog = DE_SpyLog::CreateInDB($this->combFrom->getUserInstance(), $action, $text, $this->event->getEndDate(), $this->unit, $this->combFrom, $this->combTo, $this->combFrom->getUserInstance()->getUser(), $this->combTo->getUserInstance()->getUser(), $accuracy);
        
        $action = DE_SpyAction::CreateFromName('DEFEND');
        $text = ME_SpyFormLog::FormDefenderLog();
        if(!empty($text))
        {
            DE_SpyLog::CreateInDB($this->combTo->getUserInstance(), $action, $text, $this->event->getEndDate(), $this->unit, $this->combFrom, $this->combTo, $this->combFrom->getUserInstance()->getUser(), $this->combTo->getUserInstance()->getUser(), $accuracy);
        }       
    }
    
    private function _canAttackComb()
    {
        $canAttackComb = new GL_CanAttackComb();
        $canAttackComb->canAttack($this->combFrom, $this->combTo, GB_TypeFactory::CreateConstant('GB_Date', $this->event->getEndDate()->getPgFormat()));

        // не прошла проверка на возвожность атковать соту
        // if($canAttackComb->getErrorID() && $canAttackComb->getErrorID() != 'COLONY_PEACEFUL_TIME')
        if ($canAttackComb->getErrorID())
        {
            switch ($canAttackComb->getErrorID())
            {
                case 'CLOSED_CENTRAL_GATES':
                    $messparams = array();
                    $messparams[0] = $this->startAmount;
                    $messparams[1] = "&#34;".$this->unit->getUnitNameLocolized()."&#34;";
                    $messparams[2] = $this->combFrom->getFullName();
                    $messparams[3] = $this->combTo->getFullName();

                    // Группа из {b}%{/b} зондов {b}%{/b} возвращается на соту {b}%{/b}, потому что на соте {b}%{/b} были закрыты центральные ворота.
                    $message = 'SPY_ZOND_MOVE_BACK_CLOSED_CENTRAL_GATES';
                    break;
                    
                case 'CANT_ATTACK_USER_IN_VOCATION_MODE':
                    $messparams = array();
                    $messparams[0] = $this->startAmount;
                    $messparams[1] = "&#34;".$this->unit->getUnitNameLocolized()."&#34;";
                    $messparams[2] = $this->combTo->getFullName();
                    $messparams[3] = $this->combFrom->getFullName();
                    $messparams[4] = $this->combTo->getUserInstance()->getUser()->getName();
                            
                    // Группа из {b}%{/b} зондов {b}%{/b} возвращаеться с соты {b}%{/b}  на соту {b}%{/b} так как игрок {b}%{/b} вошел в режим отпуска.
                    $message = 'SPY_ZOND_MOVE_BACK_VOCATION_MODE';
                    break;

                case 'CANT_ATTACK_USER_IN_POWER_SHILD':                                     
                    $messparams = array();
                    $messparams[0] = "{b}".$this->startAmount."{/b}";
                    $messparams[1] = "&#34;".$this->unit->getUnitNameLocolized()."&#34;";
                    $messparams[2] = $this->combFrom->getFullName();
                    $messparams[3] = $this->combTo->getFullName();
                    $messparams[4] = $this->combTo->getUserInstance()->getUser()->getName();
                        
                    // Группа из % зондов % возвращается на соту % так как сота % игрока % защищена силовым щитом       
                    $message = 'SPY_ZOND_MOVE_BACK_POWER_SHILD';
                    break;
                    
                case 'PEACE_PERIOD':
                    $messparams = array();
                    $messparams[0] = $this->startAmount;
                    $messparams[1] = "&#34;".$this->unit->getUnitNameLocolized()."&#34;";
                    $messparams[2] = $this->combFrom->getFullName();
                    
                    // Группа из {b}%{/b} зондов {b}%{/b} возвращается на соту {b}%{/b}, так как военные действия приостановлены
                    $message = 'SPY_ZOND_MOVE_BACK_PEACE_PERIOD';
                    break;

                case 'ARMY_ACTION_BAN':
                    $messparams = array();
                    $messparams[0] = $this->startAmount;
                    $messparams[1] = "&#34;".$this->unit->getUnitNameLocolized()."&#34;";
                    $messparams[2] = $this->combFrom->getFullName();
                    $messparams[3] = $this->combTo->getUserInstance()->getUser()->getName();
                    
                    // Группа из {b}%{/b} зондов {b}%{/b} возвращается на соту {b}%{/b} так как игрок {b}%{/b} был забанен.
                    $message = 'SPY_ZOND_MOVE_BACK__BAN';
                    break;

                case 'DENIED_ATTACK_BANNED_USER_MESS':
                    $messparams = array();
                    $messparams[0] = $this->startAmount;
                    $messparams[1] = "&#34;".$this->unit->getUnitNameLocolized()."&#34;";
                    $messparams[2] = $this->combFrom->getFullName();
                    $messparams[3] = $this->combTo->getUserInstance()->getUser()->getName();
                    
                    // Группа из {b}%{/b} зондов {b}%{/b} возвращается на соту {b}%{/b} так как игрок {b}%{/b} был забанен.
                    $message = 'SPY_ZOND_MOVE_BACK__BAN';
                    break;
                    
                case 'DENIED_ATTACK_USER_WITH_SMALLER_LEAGUE':
                    // {b}Внимание!{/b} Атака игрока младшей лиги запрещено. Ваша лига - %, лига игрока % - %.
                    $messparams = array();
                    $messparams[0] = DE_League::CreateFromID((int)$this->combFrom->getUserInstance()->getLeagueId())->getLeagueName();
                    $messparams[1] = $this->combTo->getUserInstance()->getUser()->getName();            
                    $messparams[2] = DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName();        
                    
                    $message = 'DENIED_ATTACK_USER_WITH_SMALLER_LEAGUE';
                    break;
                    
                case 'DENIED_ATTACK_USER_WITH_BIGGER_LEAGUE':
                    // {b}Внимание!{/b} Атака игрока старшей лиги запрещено. Ваша лига - %, лига игрока % - %.
                    $messparams = array();
                    $messparams[0] = DE_League::CreateFromID((int)$this->combFrom->getUserInstance()->getLeagueId())->getLeagueName();
                    $messparams[1] = $this->combTo->getUserInstance()->getUser()->getName();            
                    $messparams[2] = DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName();                
                    
                    $message = 'DENIED_ATTACK_USER_WITH_BIGGER_LEAGUE';
                    break;

                case 'COLONY_PEACEFUL_TIME':
                    return false;

                default:
                    // В дефолтном случае ничего не делаем
                    return true;
            }

            // Шлем сообщение тому, кто послал зонды
            DE_SystemMessages::AddMessageFromMessageCompiler(GB_TypeFactory::CreateBase('GB_Date', time()), $this->combFrom->getUserInstance()->getID(), $message, $messparams, 2, "GREEN");
            return false;
        } 
        
        return true;
    }
}

?>