<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс обработчика ивента движения арми на соту для боя.
 *
 */
class EH_ArmyMovementToAttack extends EH_Army
{

    function __construct()
    {
        parent::__construct();
    }

    public function handleEvent($eqid)
    {
        if(!parent::handleEvent($eqid))
        {
            return true;
        }

        if($this->_isTimeToHandle())
        {
            $this->_handle();
        }else{
            $this->_preHandle();
        }

        return true;
    }

    private function _handle()
    {
        //Если рефреш проводит не хозяин соты на которой бой то внешний рефреш делаем
        if($GLOBALS['params']['refreshedUiid'] != $this->combTo->getUserInstance()->getID())
        {
            assert($this->refreshUserinstance($this->combTo->getUserInstance()->getID(), 1, $this->eqid));
        }

        //Проверям можно ли атаковать соту
        if($this->canAttack->canAttack($this->combFrom, $this->combTo, $this->eventEndTimeObject))
        {
            //Начинаме бой
            $this->_startBattle();
        }else{
            //Несмогли начать бой
            $this->_endBattle();
        }
    }//END OF METHOD

    private function _endBattle()
    {
        $messparams = array();

        $errorID = $this->canAttack->getErrorID();
        // assert(!empty($errorID));

        switch($errorID)
        {
            case 'CLOSED_CENTRAL_GATES':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
                $messparams[3] = "{b} ".$this->combTo->getFormatedAddress()."{/b} ";
                $messparams[4] = "{b} ".$this->combTo->getName()."{/b} ";
                break;
            case 'CLOSED_SELF_CENTRAL_GATES':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
                $messparams[3] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[4] = "{b} ".$this->combFrom->getName()."{/b}";
                break;
            case 'IN_SOME_ALLIANCE':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
                break;
            case 'CANT_ATTACK_USER_IN_VOCATION_MODE':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
                $messparams[3] = "{b} ".$this->combTo->getFullName()."{/b}";
                break;
            case 'DENIED_ATTACK_BANNED_USER_MESS':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getName()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[3] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
                break;
            case 'ARMY_ACTION_BAN':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b}".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
                $messparams[3] = "{b} ".$this->combFrom->getName()."{/b}";
                break;
            case 'NO_GONDOLA_TRANSPORTER':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getName()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                break;
            case 'CANT_ATTACK_USER_IN_POWER_SHILD':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                $messparams[2] = "{b} ".$this->combTo->getFullName()."{/b}";
                $messparams[3] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
                break;
            case 'DENIED_ATTACK_COMB_ON_YOUR_DEFEND':
                $messparams[0] = 0;
                break;
            case 'PEACE_PERIOD':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
                break;
            case 'TEMPORARY_DEFEND_COLONY':
                // Армия % возвращается на соту %, потому что соте % игрока % включена защита.
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                $messparams[2] = "{b} ".$this->combTo->getFullName()."{/b}";
                $messparams[3] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
                break;
            case 'ATTACK_YOURSELF':
                // Ваша армия % возвращается на соту %, так как нельзя нападать на самого себя.
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                break;
            case 'COLONY_PEACEFUL_TIME':
                // Армия % возвращается на соту %, так как на соте % начался нерест ихтиандров.
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
                $messparams[2] = "{b} ".$this->combTo->getFullName()."{/b}";
                break;
            case 'DENIED_ATTACK_USER_WITH_SMALLER_LEAGUE':
                    $errorID = 'ATTACK_USER_WITH_ENOTHER_LEAGUE';
                    // Армия % возвращается на соту %, потому что игрок % перешл в лигу %.
                    $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                    $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                    $messparams[2] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
                    $messparams[3] = "{b} ".DE_League::CreateFromID((int)$this->combFrom->getUserInstance()->getLeagueId())->getLeagueName()."{/b}";
                    break;
                    
            case 'DENIED_ATTACK_USER_WITH_BIGGER_LEAGUE':
                    $errorID = 'ATTACK_USER_WITH_ENOTHER_LEAGUE';
                    // Армия % возвращается на соту %, потому что игрок % перешл в лигу %.
                    $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                    $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                    $messparams[2] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
                    $messparams[3] = "{b} ".DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName()."{/b}";
                    break;
            default:
                //Временная затыка
                $errorID = 'IN_SOME_ALLIANCE';
                $messparams[0] = 0;
                break;
        }

        DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ARMY_MOVE_BACK_'.$errorID, $messparams, 1, "GREEN");

        //Создаем ивент обратного движения
        $this->_createComeBackEvent();
    }

    private function _startBattle()
    {
        //Формируем сиситемное сообщение о том что начался бой
        $messparams[0] = "{b}".$this->army->getName()."{/b}";
        $messparams[1] = "{b}".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";

        if (constant('VERSION_NAME') == 'VKONTAKTE') {
            $messparams[2] = "{b}".$this->combFrom->getFullName()."{/b}";
            $messparams[3] = "{b}".$this->combTo->getFullName()."{/b}";
            $messparams[4] = "{b}".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
            $message = 'BATTLE_START_WITH_FROM_COMB';
        } else {
            $messparams[2] = "{b}".$this->combTo->getFullName()."{/b}";
            $messparams[3] = "{b}".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
            $message = 'BATTLE_START';
        }
        $colors[$this->combFrom->getUserInstance()->getID()] = 'GREEN';
        $colors[$this->combTo->getUserInstance()->getID()] = 'RED';
        DE_SystemMessages::AddMessageToEventParticipants($this->eqid, $message, $messparams, 1, $colors);

        //Пометили что армия прибыла на эту соту
        $this->army->setCurentComb($this->combTo);

        //Создаем бой и запись о бое
        $this->battleController = CR_Battle::CreateWithBattle($this->army, $this->combFrom, $this->combTo, $this->eventEndTimeObject);
        $this->battle = $this->battleController->getBattle();

        //Логирование системное стартуем для боя
        DE_BattleSystemLog::StartWork($this->battle);
        $strTmp  = "\n======== EH_ARMY_MOVEMENT_TO ID = ".$this->eqid."\n";
        $strTmp .= "START_DATE = ".$this->eventStartTimeObject->getPgFormat()."\n";
        $strTmp .= "END_DATE = ".$this->eventEndTimeObject->getPgFormat()."\n";
        $strTmp .= "ARMY = ".$this->army->getName()." (".$this->army->getID().") of user ".$this->army->getUserInstance()->getUser()->getName()."\n";
        $strTmp .= "COMB_FROM = ".$this->combFrom->getFullName()." (".$this->combFrom->getID().") of user ".$this->combFrom->getUserInstance()->getUser()->getName()."\n";
        $strTmp .= "COMB_TO = ".$this->combTo->getFullName()." (".$this->combTo->getID().") of user ".$this->combTo->getUserInstance()->getUser()->getName()."\n";
        DE_BattleSystemLog::Insert($strTmp);

        $nobattle = 0;
        //Проверяем мож но ли начинать бой или нет
        if(GL_CanStartBattle::CanStart($this->army, $this->combDefendArmy))
        {
            DE_BattleSystemLog::Insert("\n======== CAN START BATTLE AND TRY TO HIT\n");
            $roundDuration = $this->battleController->startRound((int)1);
            $this->_createRoundEvent($roundDuration, 1);

        }else{
            DE_BattleSystemLog::Insert("\n======== CANT START BATTLE AND TRY TO END BATTLE REASON CODE:".GL_CanStartBattle::$testResult->getReasonCode()." WINNER:".GL_CanStartBattle::$testResult->getWinnerCode()->value."\n");
            $this->battleController->battleEnd(GL_CanStartBattle::$testResult);

            //Поехали домой
            $this->_createComeBackEvent();

            $nobattle = 1;
        }

        //Закрыли сиситемное логирование
        DE_BattleSystemLog::EndWork();

    }

    private function _preHandle()
    {
        $messparams[0] = "{b}".$this->army->getName()."{/b}";
        $messparams[1] = "{b}".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";

        if (constant('VERSION_NAME') == 'VKONTAKTE') {
            $messparams[2] = "{b}".$this->combFrom->getFullName()."{/b}";
            $messparams[3] = "{b}".$this->combTo->getFullName()."{/b}";
            $messparams[4] = "{b}".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
            $messparams[5] = "{b}".$this->_getTimePeriodToEnd()->getDSFormat()."{/b}";
            $message = 'ATTACK_MESS1_WITH_FROM_COMB';
        } else {
            $messparams[2] = "{b}".$this->combTo->getFullName()."{/b}";
            $messparams[3] = "{b}".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
            $messparams[4] = "{b}".$this->_getTimePeriodToEnd()->getDSFormat()."{/b}";
            $messparams[5] = $this->army->useArtefacts && $this->uiid==$this->combFrom->getUserInstance()->getID()? "С артефактами в атаке.":"";
            $message = 'ATTACK_MESS1';
        }
        

        // системное сообщение
        $colors[$this->combFrom->getUserInstance()->getID()] = 'GREEN';
        $colors[$this->combTo->getUserInstance()->getID()] = 'RED';

        DE_SystemMessages::AddMessageToEventParticipants($this->eqid, $message, $messparams, 1, $colors, 'NOTFORSTORE');

    }//END OF METHOD

    public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
    {
        if($event->getEndDate()->value > $chekDate->value)
        {
            return false;
        }
        
        $userInstanceTo = DE_Comb::CreateFromID((int)$event->getAttributes()->getAttribute('ID_CombTo'))->getUserInstance();

        if ($refreshedInstance==null || $userInstanceTo==null) return false;
        if($refreshedInstance->getID() == $userInstanceTo->getID())
        {
            return true;
        }else{
            return false;
        }
    }

}//END OF CLASS


?>