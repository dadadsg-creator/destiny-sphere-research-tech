<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Команда отправки зондов на шпиёнское дело
 * 
 * <sendzond id="ZOND_PSI_1" count="1" action="SCAN_0" time="123" ring="1" compl="1" sota="1"/>
 *
 */
class UA_SpySendZonds extends UA_Action 
{
    /**
     * Сота на которую отправляем (для защиты не нужен этот параметр)
     *
     * @var DE_Comb
     */
    private $combTo = null;
    
    /**
     * Сота с которой отправляем
     *
     * @var DE_Comb
     */
    private $combFrom;
    
    /**
     * Период действия
     *
     * @var GB_TimePeriod
     */
    private $time;
    
    /**
     * Тип юнита
     *
     * @var DE_Unit
     */
    private $unit;
    
    /**
     * Проверка возможности нападаения
     *
     * @var GL_CanAttackComb
     */
    private $canAttackComb;
    
    /**
     * Кол-во отправляемых зондов
     *
     * @var integer
     */
    private $amount;

    /**
     * Тип действия
     *
     * @var DE_SpyAction
     */
    private $action;
    
    /**
     * Enter description here...
     *
     * @var bool
     */
    private $oneRound = null;
    
    static private $unitAllowedForDefence = 'SPY_ZOND_SCOUT';
    
    function __construct() 
    {
        parent::UA_Action();
        
        $this->canAttackComb = new GL_CanAttackComb();
    }   
    
    function checkLocalParams() 
    {
        $this->amount = (int)$this->root->get_attribute("count");
        assert($this->amount > 0);
        
        $action = $this->root->get_attribute("action");
        assert(preg_match('/^[A-Z_]+$/', $action));
        $this->action = DE_SpyAction::CreateFromName($action);
        
        $unit = $this->root->get_attribute("id");
        assert(preg_match('/^[A-Z_]+$/', $unit));
        
        // список всех шпионских юнитов
        $validUnit = false;
        $spyUnits = DS_SpyMenu::GetSpyUnits();
        // смотрим шпионский ли юнит пытаемся отослать
        foreach ($spyUnits as $k => $u)
        {
            if($u['unitname'] == $unit)
            {
                $validUnit = true;
                break;
            }
        }
        assert($validUnit);
        
        $this->unit = DE_Unit::CreateFromName($unit);
        
        $this->combFrom = DE_Comb::CreateFromID($this->comb);
        
        if($this->action->getName() == 'RESEARCH')
        {
            // если летим в разведку, то нужны координаты и скорость
            $this->_getParamsForResearch();
        } else {
            // если защищаем, то нужно время
            $this->_getTimeFromInput();
        }
        
        return true;
    }
    
    private function _getParamsForResearch()
    {
        // координаты разведываемой соты
        $dest_ring = (int)$this->root->get_attribute("ring");
        $dest_combcomplex = (int)$this->root->get_attribute("compl");
        $dest_comb = (int)$this->root->get_attribute("sota");
        assert($dest_ring > 0 && $dest_combcomplex > 0 && $dest_comb > 0);

        $this->combTo = DE_Comb::CreateFromCoordinates($this->world, $dest_ring, $dest_combcomplex, $dest_comb);
        //assert($this->combTo instanceof DE_Comb);  // проверяется в GL_CanAttackComb
        
        // время движения
        if($this->combTo instanceof DE_Comb && $this->combTo->getCombID() != $this->combFrom->getCombID())
        {
            $moveSpeed = (float)$this->root->get_attribute("sd") / 100;
            $moveSpeedMax = floatval(DE_WorldSettings::CreateForCurentWorld()->get('SPY_RESEARCH_GROUP_SPEED_ACLERATION_TO') / 100);
            assert($moveSpeed >= 0.1 && $moveSpeed <= $moveSpeedMax);
            
            $this->time = GL_TravelTime::Count($this->combFrom, $this->combTo, GB_TypeFactory::CreateBase('GB_SpyMoveSpeed', $moveSpeed));
            // $this->time = ceil($this->time / $moveSpeed);
        }
        
        // один раунд
        $oneRound = $this->root->get_attribute("oneround");
        if($oneRound == 'true')
        {
            $this->oneRound = 1;
        } else {
            $this->oneRound = 0;
        }
    }
        
    private function _getTimeFromInput()
    {
        $time = (int)$this->root->get_attribute("time");
        $maxTime = (int)DE_WorldSettings::CreateForCurentWorld()->get('SPY_MAX_DEFENCE_TIME');
        assert($time > 0 && $time <= $maxTime);
        
        $this->time = GB_TypeFactory::CreateBase('GB_TimePeriod', $time * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60);
    }
    
    function checkAction() 
    {
        // есть ли нужное кол-во свободных зондов
        $freeAmount = $this->combFrom->getAttributes()->getAttribute('CURRENT_AMOUNT', $this->unit->getName());
        assert($this->amount <= $freeAmount);
        
        if($this->action->getName() == 'RESEARCH')
        {
            // если летим в разведку, то проверяем можно ли напасть на соту
            $this->canAttackComb->canAttack($this->combFrom, $this->combTo, GB_TypeFactory::CreateBase('GB_Date', time()));
            $modifierGroupLimit = 'SPY_MAX_RESEARCH_GROUPS';
            $modifierGroupSizeLimit = 'SPY_MAX_ZONDS_IN_RESEARCH_GROUPS';
        } else {
            assert($this->unit->getName() == self::$unitAllowedForDefence);
            $modifierGroupLimit = 'SPY_MAX_DEFENCE_GROUPS';
            $modifierGroupSizeLimit = 'SPY_MAX_ZONDS_IN_DEFENCE_GROUP';
        }
        
        $maxGroups = (int)DE_WorldSettings::CreateForCurentWorld()->get($modifierGroupLimit);
        $maxGroupSize = (int)DE_WorldSettings::CreateForCurentWorld()->get($modifierGroupSizeLimit);
        
        // текущее кол-во групп, занятых в действии
        $currentGroups = (int)DS_SpyMenu::GetCountGroupsInAction($this->combFrom, $this->unit, $this->action);
        // для разведки учитыаем и возвращающиеся группы
        if($this->action->getName() == 'RESEARCH')
        {
            $currentGroups += (int)DS_SpyMenu::GetCountGroupsInAction($this->combFrom, $this->unit, DE_SpyAction::CreateFromName('MOVE_BACK'));
        }
        
        // ограничение на максимальное кол-во групп
        if($currentGroups >= $maxGroups)
        {
            $this->errormsg = 'SPY_MAX_'.$this->action->getName().'_GROUPS_LIMIT';
            return true;
        }
        
        // ограничение на кол-во зондов в группе
        if($this->amount > $maxGroupSize)
        {
            //$this->amount = $maxGroupSize; // SystemMessage ???
            $this->errormsg = 'SPY_MAX_'.$this->action->getName().'_GROUP_SIZE';
            return true;
        }
        
        return true;
    }
    
    function performAction() 
    {
        if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
        {
            $this->_vocationModeAlert();            
            return true;
        }

        if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
        {
            $this->_shildModeAlert();           
            return true;
        }
        
        // шпионаж отключен
        if(!DE_WorldSettings::CreateForCurentWorld()->get('SPY_ENABLED'))
        {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'SPY_DISABLED', array());
            return true;
        }
        
        // не прошла проверка на ограничения кол-ва
        if(!empty($this->errormsg))
        {
            switch ($this->errormsg) {
                case 'SPY_MAX_DEFEND_GROUPS_LIMIT':
                    $messparams[0] = '{b}'.DE_WorldSettings::CreateForCurentWorld()->get('SPY_MAX_DEFENCE_GROUPS').'{/b}';
                    break;
                    
                case 'SPY_MAX_RESEARCH_GROUPS_LIMIT':
                    $messparams[0] = '{b}'.DE_WorldSettings::CreateForCurentWorld()->get('SPY_MAX_RESEARCH_GROUPS').'{/b}';
                    break;
                    
                case 'SPY_MAX_DEFEND_GROUP_SIZE':
                    $messparams[0] = '{b}'.DE_WorldSettings::CreateForCurentWorld()->get('SPY_MAX_ZONDS_IN_DEFENCE_GROUP').'{/b}';
                    break;
                    
                case 'SPY_MAX_RESEARCH_GROUP_SIZE':
                    $messparams[0] = '{b}'.DE_WorldSettings::CreateForCurentWorld()->get('SPY_MAX_ZONDS_IN_RESEARCH_GROUPS').'{/b}';
                    break;
            
                default:
                    $messparams[0] = 0;
                    break;
            }
            
            DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->errormsg, $messparams, 2, 'RED');
            
            return true;
        }
        
        // не прошла проверка на возвожность атковать соту (только для разведки)
        if($this->action->getName() == 'RESEARCH' && $this->canAttackComb->getErrorID() && $this->canAttackComb->getErrorID() != 'COLONY_PEACEFUL_TIME')
        {
            switch ($this->canAttackComb->getErrorID())
            {
                case 'CLOSED_CENTRAL_GATES':
                    $messparams[0] = "{b} ".$this->combTo->getFormatedAddress()."{/b}";
                    break;

                case 'WRONG_DESTINATION':
                    $messparams[0] = "{b} ".$this->combTo->getFormatedAddress()."{/b}";
                    break;

                case 'DENIED_ATTACK_COMB_ON_YOUR_DEFEND':
                    $messparams[0] = 0;
                    break;
                    
                case 'CANT_ATTACK_USER_IN_VOCATION_MODE':
                    $messparams = array();                  
                    break;

                case 'CANT_MOOVE_ARMY_AFTER_FREEZ':                                     
                    $period = GB_TypeFactory::CreateBase('GB_TimePeriod', GL_CanMooveArmysAfterFreez::GetDelta());                  
                    $messparams[0] = $period->getDSFormat();                    
                    break;
                    
                case 'PEACE_PERIOD':
                    $peacePeriod = DS_Battle::GetCurrentPeacePeriod();
                    $tmp = $peacePeriod['END_DATE']->value - time();
                    $timeToEnd = GB_TypeFactory::CreateBase('GB_TimePeriod', $tmp);
                    $messparams[0] = "{b} ".$timeToEnd->getDSFormat()."{/b}";
                    break;
                    
                case 'DENIED_ATTACK_USER_WITH_SMALLER_LEAGUE':
                    // {b}Внимание!{/b} Атака игрока младшей лиги запрещено. Ваша лига - %, лига игрока % - %.
                    $messparams[0] = DE_League::CreateFromID((int)$this->combFrom->getUserInstance()->getLeagueId())->getLeagueName();
                    $messparams[1] = $this->combTo->getUserInstance()->getUser()->getName();            
                    $messparams[2] = DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName();        
                    break;
                    
                case 'DENIED_ATTACK_USER_WITH_BIGGER_LEAGUE':
                    // {b}Внимание!{/b} Атака игрока старшей лиги запрещено. Ваша лига - %, лига игрока % - %.
                    $messparams[0] = DE_League::CreateFromID((int)$this->combFrom->getUserInstance()->getLeagueId())->getLeagueName();
                    $messparams[1] = $this->combTo->getUserInstance()->getUser()->getName();            
                    $messparams[2] = DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName();                
                    break;

                default:
                    $messparams[0] = 0;
                    break;
            }

            DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->canAttackComb->getErrorID(), $messparams, 2, 'RED');
            assert($this->refresh ( 2, "", "", "", true));
            return true;
        }
            
        // создаем действие
        $this->_createAction();
        
        // отправляем хелпер
        $this->_sendHelper();
        
        // отправляем системное сообщение
        $this->_sendSystemMessage();
        
        return true;
    }
    
    /**
     * Создание действия
     *
     */
    private function _createAction()
    {
        $dateFrom = GB_TypeFactory::CreateBase('GB_Date', time());      
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', $dateFrom->value + $this->time->value);     
        $this->endtimeObject = $dateTo; // это для системного сообщения
        $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);
        
        $creationData = new GB_SpyActionCreationData();
        $creationData->setActionType($this->action);
        $creationData->setCombFrom($this->combFrom);
        if(!is_null($this->combTo))
        {
            $creationData->setCombTo($this->combTo);
        }
        $creationData->setPeriod($datePeriod);
        $creationData->setUnit($this->unit);
        $creationData->setZondsAmount($this->amount);
        $creationData->setNeedChangeAttributes(true);
        $creationData->setOneRound($this->oneRound);

        ME_SpyGroupAction::CreateGroupAction($creationData);
    }
    
    /**
     * Хелпер
     *
     */
    private function _sendHelper()
    {
        if(!isset($_SESSION['profi_game']) || !$_SESSION['profi_game'])
        {
            return;
        }
        
        // <sendzondok id="SPY_ZOND_SCOUT" count="11" action="sdafasdf" time="dasddsa" log="0" addr="asdasd"/>
        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><sendzondok />";
        $doc = domxml_open_mem($xml);
        $root = $doc->document_element();
        $root->set_attribute("id", $this->unit->getName());
        $root->set_attribute("zname", $this->unit->getLocalizedName());
        $root->set_attribute("zcolor", GB_SpyMenuColor::$color[$this->unit->getName()]);
        $root->set_attribute("count", $this->amount);
        $root->set_attribute("action", $this->action->getLocalizedName());
        $root->set_attribute("time", $this->endtimeObject->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta()));
        $root->set_attribute("log", 0);
        if($this->combTo instanceof DE_Comb)
        {
            $root->set_attribute("addr", $this->combTo->getFormatedAddress());
        } else {
            $root->set_attribute("addr", $this->combFrom->getFormatedAddress());
        }
        
        SU_OutputHandler::Singleton()->addString($doc->dump_mem(), 'helper');
    }
    
    /**
     * посылаем системное сообщение
     *
     */
    private function _sendSystemMessage()
    {
        $messparams = array();
        
        if($this->action->getName() == 'RESEARCH')
        {
            $messparams[0] = $this->combTo->getFullName();
            $messparams[1] = $this->combTo->getUserInstance()->getUser()->getName();
            $messparams[2] = $this->amount;
            $messparams[3] = $this->endtimeObject->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta());
            $msg = "SPY_SEND_TO_RESEARCH";
        } else {    
            $messparams[0] = $this->amount;
            $messparams[1] = $this->combFrom->getFullName();
            $messparams[2] = $this->endtimeObject->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta());
            $msg = "SPY_SEND_TO_DEFEND";
        }
        
        DE_SystemMessages::AddMessageFromAction(__CLASS__, $msg, $messparams, 2, 'GREEN');
    }

}

?>