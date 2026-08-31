<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс действия укрытия армии
 *
 */
class UA_HideArmy extends UA_Action {
    
    /**
     * Амия с которой и работаем
     *
     * @var DE_Army
     */
    private $army;

    /**
     * Время на которе сохраняем армию в турнах
     *
     * @var int
     */
    private $periodInSeconds;   
    
    /**
     * Коды ошибки при проверка на возможность действия
     *
     * @var string
     */
    private $checkActionError = '';
    
    /**
     * Сота откуда отправляем
     *
     * @var DE_Comb
     */
    private $combFrom;
    
    function __construct()
    {
        $this->UA_Action();
        
    }//END OF CONSTRUCTOR
    
    function checkLocalParams ()
    {
        $armyID = (int)$this->root->get_attribute("id_army");       
        assert($armyID > 0);
        
        $this->army = DE_Army::CreateFromID($armyID);
        
        $turns = (int)$this->root->get_attribute("timehide");       
        assert($turns > 0 && $turns <= DE_WorldSettings::CreateForCurentWorld()->get('ARMYS_HIDDING_MAX_PERIOD'));
        
        $this->periodInSeconds = (int)($turns * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60);       
        assert($this->periodInSeconds > 0);
        
        $this->combFrom = DE_Comb::CreateFromID($this->comb);
        
        return true;
    }//END OF FUNCTION
    
    function checkAction ()
    {
        if(!GL_CanHideArmy::Test($this->army))
        {
            $this->checkActionError = GL_CanHideArmy::$errorCode;
        }
        
        return true;
    }//END OF FUNCTION
    
    function performAction ()
    {
        global $common;

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
        
        if(!empty($this->checkActionError))
        {
            ds_error('Cant hide army ['.$this->army->getName().']. Reason: '.GL_CanHideArmy::$errorCode);
        }
                
        //Создаем ивент
        $dateFrom = GB_TypeFactory::CreateBase('GB_Date', time());
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', time() + $this->periodInSeconds);
                
        $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);
        
        //Рассчитали сколько всего надо ресурсов на перемещение
        $needResources = GL_FullUnitsMovementCost::CountHideArmy($this->army, $datePeriod);

        //Сняли ресурсы за перемещение
        if(!ME_GetUpkeepResourcesFromComb::GetMoovUpkeep($this->combFrom, $needResources))
        {
            // Не хватает ресов
            if(!$this->refresh(2))
            {
                return false;
            }

            return true;
        }

        // Минимальный рефрешь
        $noCellData = true;
        if(!$this->refresh (2, "", "", 1, $noCellData))
        {
            return false;
        }
        

        $eventParticipants = new GB_EventParticipants;
        $eventParticipants->setParticipant(DE_UserInstance::CreateFromID($this->uiid));
        
        $actionData = new GB_ArmyActionCreationData('HIDE_ARMY');

        $actionData->setArmy($this->army);
        $actionData->setDatePeriod($datePeriod);
        $actionData->setParticipants($eventParticipants );
    
        $actionData->setPath(new GB_TravelPath($this->combFrom, $this->combFrom));

        $this->eqid = ME_CreateArmyAction::CreateAction($actionData);
        
        $timePeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $this->periodInSeconds);
        
        $messparams[0] = $this->army->getName();
        $messparams[1] = $timePeriod->getDSFormat();
        $messparams[2] = $needResources->getShortPayList();
        
        // посылаем системное сообщение
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "HIDE_ARMY", $messparams, 1, 'GREEN');

          // Снимаем флаг артефактов в атаке с армии
        $sql = "update ds_unitcontainer set use_artefacts=false where id_unitcontainer=".$this->army->getID();
        $common->executeQuery($sql);
      
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

        $root->set_attribute("id_army", $this->army->getID());
        $root->set_attribute("inaction", 1);

        $ch = $root->new_child("transfers");
        $ch = $root->new_child("actions");
                
        // корректировка времени
        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', time() + $this->periodInSeconds);
            
        $messageparams[0] = $dateTo->getFormatedDate($delta);
        $messageparams[1] = $dateTo->getFormatedTime($delta);
        
        $currentaction = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('HIDE_ARMY_PN', $messageparams, null, null, false);
            
        $elem = $doc->create_text_node($currentaction);
        $ch->append_child($elem);

        SU_OutputHandler::Singleton()->addString($doc->dump_mem(), 'helper');
    }//END OF FUNCTION sendHalper   
}

?>