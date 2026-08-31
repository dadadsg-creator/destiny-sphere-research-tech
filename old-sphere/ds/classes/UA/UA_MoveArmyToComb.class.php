<?php $CVS_Version = '$Revision$ ; $Date$';

// переправка армии на другую соту 
class UA_MoveArmyToComb extends UA_Action
{
    private $army;

    private $speed;

    private $combFrom;

    private $combTo;

    private $id_army;
    
    private $travelTime;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function checkLocalParams()
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
    public function checkAction ()
    {
        //Армия своя
        assert($this->army->getUserInstance()->getID() == $this->params['uiid']);

        //Армия не дефолтовая
        assert($this->army->isDefault() == 'f');
        
        //Армия не участвует не в каком дествие
        assert(!$this->army->getInAction());
        
        //Только для премиум игроков
        if(!$_SESSION['profi_game'])
        {
            SU_OutputHandler::Singleton()->addString("<?xml version=\"1.0\" encoding=\"utf-8\"?><key addr=\"".constant('GAME_HOST')."/?p=premium_info_page \" target_load=\"_blank\"/>", 'loadkey');
            echo SU_OutputHandler::Singleton()->fetchAll();
            exit;
        }
        assert($_SESSION['profi_game']);

        return true;
    }
    
    public function performAction()
    {
        global $common;

        //if($_SESSION['profi_game'])
        //{
            $noCellData = true;
        //} else {
        //  $noCellData = false;
        //}
        
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
        
        // Проверям нет ли неоконченных боев на этой соте 
        $res = DS_Battle::NotEndedBattlesOnComb($this->combFrom);
        if(count($res) > 0 )
        {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CANT_TRANSFER_UNITS_WHEN_BATTLE_IN_PROGRESS', array(), 1, 'RED');
            return true;
        }
        
        if(!GL_CanMoveArmyToComb::CanMove($this->combFrom, $this->combTo))
        {
            $msg = GL_CanMoveArmyToComb::GetErrorCode();
            
            switch (GL_CanMoveArmyToComb::GetErrorCode())
            {
                case 'NO_TO_CENTRAL_GATE':
                    $msg = 'CLOSED_CENTRAL_GATES';
                    $messparams[0] = "{b} ".$this->combTo->getFormatedAddress()."{/b}";
                    break;
                    
                case 'NO_FROM_CENTRAL_GATE':
                    $msg = 'CLOSED_CENTRAL_GATES';
                    $messparams[0] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                    break;

                case 'WRONG_DESTINATION':
                    $messparams[0] = "{b} ".$this->combTo->getFormatedAddress()."{/b}";
                    break;
                    
                case 'EMPTY_COMB':
                    $messparams[0] = "{b} ".$this->combTo->getFormatedAddress()."{/b}";
                    break;  
            
                case 'CANT_MOOVE_ARMY_AFTER_FREEZ':                                     
                    $period = GB_TypeFactory::CreateBase('GB_TimePeriod', GL_CanMooveArmysAfterFreez::GetDelta());                  
                    $messparams = array(
                        0 => $period->getDSFormat()
                    );                  
                    break;

                default:
                    $messparams[0] = 0;
                    break;
            }
            
            DE_SystemMessages::AddMessageFromAction(__CLASS__, $msg, $messparams, 1, 'RED');

            if(!$this->refresh(2, "", "", "", $noCellData))
            {
                return false;
            }
            
            return true;
        }
        
        // Смотрим, нет ли в армии квестового юнита
        if (DS_Army::IsArmyHasQuetsUnit($this->id_army)) {          
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CANT_MOOVE_ARMY_WITH_QUEST_UNIT', array(), 1, 'RED');
            return true;
        }
        
        // получаем время окончания эвента
        if(!$this->getEventEndTime())
        {
            return false;
        }

        $travelTimePeriod = GB_TypeFactory::CreateConstant('GB_TimePeriod', (int)$this->travelTime);
        $dateFrom = GB_TypeFactory::CreateConstant('GB_Date', (int)$this->starttime);
        
        //Рассчитали сколько всего надо ресурсов на перемещение
        $needResources = GL_FullUnitsMovementCost::CountPath($this->army, $this->combFrom, $this->combTo, $this->speed, $dateFrom, 'ATTACK');

        //Сняли ресурсы за перемещение
        if(!ME_GetUpkeepResourcesFromComb::GetMoovUpkeep($this->combFrom, $needResources))
        {
            if(!$this->refresh(2))
            {
                return false;
            }

            return true;
        }
        
        //Указываем в атрибутаъх армии, ее сокрость и приоритетный ресурс.
        $armyAttributes = DE_ArmyAttributes::CreateFromArmyID($this->army->getID());

        $armyAttributes->setAttribute('CURRENT_AMOUNT', 'MOVE_SPEED', $this->speed->value);
        
        //Создаем ивент
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', $this->endtime);
        $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);
        
        $eventParticipants = new GB_EventParticipants;
        $eventParticipants->setParticipant($this->combTo->getUserInstance());
        
        $actionData = new GB_ArmyActionCreationData('MOVE_TO_COMB');
        
        $actionData->setArmy($this->army);
        $actionData->setDatePeriod($datePeriod);
        $actionData->setParticipants($eventParticipants );
        $actionData->setPath(new GB_TravelPath($this->combFrom, $this->combTo));

        $this->eqid = ME_CreateArmyAction::CreateAction($actionData);
        
        $messparams[0] = "{b} ".$this->army->getName()."{/b}";
        $messparams[1] = "{b} ".$this->combTo->getFullName()."{/b}";
        $messparams[2] = "{b} ".$dateTo->getSystemMessageFormat()."{/b}";
        $messparams[3] = "{b} ".$needResources->getShortPayList()."{/b}";
        
        // посылаем системное сообщение
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "ARMY_MOVE_TO_COMB_MESS", $messparams, 1, 'GREEN' );

        // Снимаем флаг артефактов в атаке с армии
        $sql = "update ds_unitcontainer set use_artefacts=false where id_unitcontainer=".$this->army->getID();
        $common->executeQuery($sql);

        // Запоминаем новые координаты и скорость
        $sql = "update ds_unitcontainer set lastcoord='".$this->destination."', speed=".(int)$this->root->get_attribute("speed")." where id_unitcontainer=".$this->id_army;
        $this->common->executeQuery($sql);
        
        if(!$this->refresh(2, "", "", 1, $noCellData))
        {
            return false;
        }

        if($_SESSION['profi_game'] == true)
        {
            $this->_sendHelper();
        }
        
        return true;
    }
    
    // выдает время окончания эвента
    function getEventEndTime ()
    {
        // Если мы трансферим на захватываемую колонию или с нее, время трансфера свое
        if ($this->combFrom->getIsCapture() || $this->combTo->getIsCapture()) {
            $this->travelTime = (float)$this->worldSettings->get('TRANSFER_CAPTURE_COLONIES_TIME')*60*$this->worldSettings->get('TURN_TIME');
        } else {
            $this->travelTime = GL_TravelTime::Count($this->combFrom, $this->combTo, $this->speed)->value;
        }

        $this->endtime = time() + $this->travelTime;
        $this->starttime = time();
        return true;
    }
    
    private function _sendHelper()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><army />";
        $doc = domxml_open_mem($xml);
        $root = $doc->document_element();

        $root->set_attribute("id_army", $this->army->getID());
        $root->set_attribute("inaction", 1);

        $ch = $root->new_child("transfers");
        $ch = $root->new_child("actions");
        
        // корректировка времени
        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', $this->endtime);

        $messageparams[0] = $this->combTo->getName();
        $messageparams[1] = $dateTo->getFormatedDate($delta);
        $messageparams[2] = $dateTo->getFormatedTime($delta);
        
        $currentaction = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ARMY_MOVE_TO_COMB', $messageparams, null, null, false);

        $elem = $doc->create_text_node($currentaction);
        $ch->append_child($elem);

        SU_OutputHandler::Singleton()->addString($doc->dump_mem(), 'helper');
    }
}

?>