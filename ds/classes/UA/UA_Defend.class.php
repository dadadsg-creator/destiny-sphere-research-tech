<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отправить армию на защиту соты
class UA_Defend extends UA_Action
{
    private $army;

    private $speed;

    private $combFrom;

    private $combTo;

    private $id_army;

    function UA_Defend ()
    {
        UA_Action::UA_Action ();
    }

    // проверяем параметры
    function checkLocalParams ()
    {
        //Откуда
        $this->combFrom = DE_Comb::CreateFromID((int)$this->comb);

        // место назначения
        $this->destination = $this->root->get_attribute ( "destination" );
        assert(!empty($this->destination));
        assert(preg_match ( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $this->destination));
        assert(strlen($this->destination) < 50);

        $dest = explode ( ".", $this->destination );

        $this->dest_ring = (int)$dest[0];
        assert($this->dest_ring > 0);

        $this->dest_combcomplex = (int)$dest[1];
        assert($this->dest_combcomplex > 0);

        $this->dest_comb = (int)$dest[2];
        assert($this->dest_comb > 0);

        $this->combTo = DE_Comb::CreateFromCoordinates($this->world, $this->dest_ring, $this->dest_combcomplex, $this->dest_comb);

        //Армия
        $armyID = (int)$this->root->get_attribute("id_army");
        assert($armyID > 0);

        $this->id_army = $armyID;

        $this->army = DE_Army::CreateFromID($armyID);

        $this->speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)($this->root->get_attribute("speed") / 100));

        return true;
    }

    // проверяем допустимость действия
    function checkAction ()
    {
        //Армия своя
        assert($this->army->getUserInstance()->getID() == $this->params['uiid']);

        //Армия не дефолтовая
        assert($this->army->isDefault() == 'f');
        
        //Армия не участвует не в каком дествие
        assert(!$this->army->getInAction());

        return true;
    }



    // выполняем действие
    function performAction()
    {
        global $params;
        global $common;

        
        $noCellData = true;
    
        if(!GL_CanDefendComb::CanDefend($this->combFrom, $this->combTo, GB_TypeFactory::CreateBase('GB_Date', time())))
        {
            switch (GL_CanDefendComb::GetErrorCode())
            {
                case 'NO_FROM_CENTRAL_GATE':
                    $msgName = 'WRONG_DEFEND_RVR';
                    $messparams[0] = "{b} ".$this->combTo->getFullName()."{/b}";
                    break;
                case 'DENIED_DEFEND_BANNED_USER':
                    $msgName = 'WRONG_DESTINATION';
                    $messparams[0] = "{b} ".$this->combTo->getFullName()."{/b}";
                    break;
                case 'WRONG_TARGET':
                    $msgName = 'WRONG_DESTINATION';
                    $messparams[0] = "{b} ".$this->combTo->getFullName()."{/b}";
                    break;
                case 'NO_GONDONOTRANSPORTER':
                    $msgName = 'NO_GONDOLA_TRANSPORTER';
                    $messparams[0] = 0;
                    break;
                case 'NO_RING_GATE':
                    $msgName = 'NO_RING_GATES';
                    $messparams[0] = 0;
                    break;
                case 'NO_TO_CENTRAL_GATE':
                    $msgName = 'CLOSED_CENTRAL_GATES';
                    $messparams[0] = "{b} ".$this->combTo->getFullName()."{/b}";
                    break;
                case 'CANT_MOOVE_ARMY_AFTER_FREEZ':
                    $msgName = 'CANT_MOOVE_ARMY_AFTER_FREEZ';
                    
                    $period = GB_TypeFactory::CreateBase('GB_TimePeriod', GL_CanMooveArmysAfterFreez::GetDelta());                  
                    $messparams = array(
                        0 => $period->getDSFormat()
                    );      
                    
                    break;
                case 'CANT_DEFEND_USER_IN_VOCATION_MODE':
                    $msgName = 'CANT_DEFEND_USER_IN_VOCATION_MODE';
                    $messparams[0] = 0;
                    break;
                case 'NO_ALLIANCE':
                    $msgName = 'NO_ALLY_TARGET';
                    $messparams[0] = 0;
                    break;
                case 'OTHER_RACE_RVR':
                    $msgName = 'NO_ALLY_TARGET';
                    $messparams[0] = 0;
                    break;
                case 'SAME_USER_COMB':                    
                    $msgName = 'CANT_DEFEND_ARMY_BASE_COMB';
                    $messparams = array();
                    break;
                case 'DENIED_DEFEND_COMB_WHEN_YOU_ATTACK_HER':
                    $msgName = 'DENIED_DEFEND_COMB_WHEN_YOU_ATTACK_HER';
                    $messparams = array();
                    break;
                case 'CANT_DEFEND_USER_IN_POWER_SHILD':
                    $msgName = 'CANT_DEFEND_USER_IN_POWER_SHILD';
                    $messparams = array();
                    break;
                case 'PEACE_PERIOD':            
                    $msgName = 'PEACE_PERIOD';        
                    $peacePeriod = DS_Battle::GetCurrentPeacePeriod();      
                    
                    $tmp = $peacePeriod['END_DATE']->value - time();
                    
                    $timeToEnd = GB_TypeFactory::CreateBase('GB_TimePeriod', $tmp);
                            
                    $messparams[0] = "{b} ".$timeToEnd->getDSFormat()."{/b}";
                    break;
                case 'CANT_DEFEND_NEWB':
                    $msgName = 'CANT_DEFEND_NEWB';
                    $messparams = array();
                    break;
                case 'EMPTY_COMB':
                    $msgName = 'EMPTY_COMB';
                    $messparams = array();
                    break;
                case 'ARMY_ACTION_PREMIUM_BAN':
                    $msgName = 'ARMY_ACTION_PREMIUM_BAN';
                    $messparams = array();
                    break;
                case 'CANT_DEFEND_TEMPORARY_DEFEND_COLONY':
                    $msgName = 'CANT_DEFEND_TEMPORARY_DEFEND_COLONY';
                    $messparams = array();
                    break;
                case 'NO_DEFEND_ANY_COMB':
                    $msgName = 'CAN_DEFEND_IN_THIS_WORLD';
                    $messparams = array();
                    break;
                case 'DENIED_DEFEND_COMB_WHIT_SMALLER_LEAGUE':
                    $msgName = 'DENIED_DEFEND_COMB_WHIT_SMALLER_LEAGUE';
                    $messparams = array();
                    $messparams[0] = "{b} ".DE_League::CreateFromID((int)$this->combFrom->getUserInstance()->getLeagueId())->getLeagueName()."{/b}";
                    $messparams[1] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
                    $messparams[2] = "{b} ".DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName()."{/b}";
                    break;
                case 'DEFEND_NPC_COMB':
                    $msgName = 'DEFEND_NPC_COMB';
                    $messparams = array();
                    break;
                default:
                    $msgName = 'CANT_DEFEND_COMB';
                    $messparams = array();
                    break;
            }

            DE_SystemMessages::AddMessageFromAction(__CLASS__, $msgName, $messparams, 1, 'RED');
            assert($this->refresh(2, "", "", "", $noCellData));
            
            return true;
        }

        // Отпуск
        if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
        {
            $this->_vocationModeAlert();            
            return true;
        }

        // Щит
        if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
        {
            $this->_shildModeAlert();           
            return true;
        }
        
        // Наличие юнита
        if (DS_Army::IsArmyHasQuetsUnit($this->army->getId())) {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CANT_MOOVE_ARMY_WITH_QUEST_UNIT', array(), 1, 'RED');
            return true;
        }
        
        // Проверям нет ли неоконченных боев на этой соте 
        $res = DS_Battle::NotEndedBattlesOnComb($this->combFrom);
        if(count($res) > 0 )
        {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CANT_TRANSFER_UNITS_WHEN_BATTLE_IN_PROGRESS', array(), 1, 'RED');
            return true;
        }
        
        // снимаем защиту нового игрока (Если игрок не летит на нпс соту)
        $userInstance = DE_UserInstance::CreateFromID((int)$_SESSION["uiid"]);
        if($userInstance->haveNewbDefend() && $this->combTo->getUserInstance()->getUser()->getIsUserNpc() == 'f')
        {
            $userInstance->clearNewbDefend();
            $messparams = array();
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_NEWB_DEFEND", $messparams, 0, 'YELLOW');
        }
        
        $travelTimePeriod = GL_TravelTime::Count($this->combFrom, $this->combTo, $this->speed);
        $dateFrom = GB_TypeFactory::CreateBase('GB_Date', time());
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', $dateFrom->value + $travelTimePeriod->value);
        $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);

        $needResources = GL_FullUnitsMovementCost::CountDefend($this->army, $this->combFrom, $this->combTo, $this->speed, $dateFrom);

        if(!ME_GetUpkeepResourcesFromComb::GetMoovUpkeep($this->combFrom, $needResources))
        {
            if(!$this->refresh(2))
            {
                return false;
            }
            return true;
        }

        //Указываем в атрибутаъх армии, ее сокрость.
        DE_ArmyAttributes::CreateFromArmyID($this->army->getID())->setAttribute('CURRENT_AMOUNT', 'MOVE_SPEED', $this->speed->value);

        $eventParticipants = new GB_EventParticipants;
        $eventParticipants->setParticipant($this->combTo->getUserInstance());
        
        if($this->combFrom->getUserInstance()->getID() != $this->combTo->getUserInstance()->getID())
        {
            $eventParticipants->setParticipant($this->combFrom->getUserInstance());
        }
        

        $actionData = new GB_ArmyActionCreationData('MOVE_TO_DEFEND');

        $actionData->setArmy($this->army);
        $actionData->setDatePeriod($datePeriod);
        $actionData->setParticipants($eventParticipants );
        $actionData->setPath(new GB_TravelPath($this->combFrom, $this->combTo));

        $this->eqid = ME_CreateArmyAction::CreateAction($actionData);

        $messparams[0] = "{b}".$this->army->getName()."{/b}";
        $messparams[1] = "{b}".$this->combTo->getFullName()."{/b}";
        $messparams[2] = "{b}".$travelTimePeriod->getDSFormat()."{/b}";
        $messparams[3] = "{b}".$needResources->getShortPayList()."{/b}";        

        // системное сообщение
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "DEFEND_MESS1", $messparams, 1, 'GREEN');

        // Снимаем флаг артефактов в атаке с армии
        $sql = "update ds_unitcontainer set use_artefacts=false where id_unitcontainer=".$this->army->getID();
        $common->executeQuery($sql);

/************** Конец экшена  *************************/
        $noCellData = true;

        // рефреш с актуализацией ресурсов
        if(!$this->refresh( 2, "", "", 1, $noCellData))
        {
            return false;
        }

        if(isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == true))     
        {
            $this->_sendHelper();   
        }

        return true;
    }
    
    private function _sendHelper()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><army />";
        $doc = domxml_open_mem($xml);
        $root = $doc->document_element();

        $root->set_attribute("id_army", $this->id_army);
        $root->set_attribute("inaction", 1);

        $ch = $root->new_child("transfers");

        $ch = $root->new_child("actions");
        $action = DS_Army::GetActionByName('CANCEL_DEFEND', $this->langId);
        $ch2 = $ch->new_child("possible");
        $ch2->set_attribute("id_action", $action['actiontypename']);
        $ch2->set_attribute("actionname", urlencode($action['localizedtypename']));

        // корректировка времени
        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
        $armyAction = $this->army->getArmyAction();
        
        $messageparams[0] = $this->combTo->getFullName();
        $messageparams[1] = $armyAction->getEndDate()->getFormatedDate($delta);
        $messageparams[2] = $armyAction->getEndDate()->getFormatedTime($delta);
        
        $currentaction = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ARMY_DEFEND', $messageparams, null, null, false);

        $elem = $doc->create_text_node($currentaction);
        $ch->append_child($elem);

        SU_OutputHandler::Singleton()->addString($doc->dump_mem(), 'helper');
    }
}
?>