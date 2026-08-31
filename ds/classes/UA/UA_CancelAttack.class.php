<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отмена атаки
class UA_CancelAttack extends UA_Action 
{
        
    private $army;  
    private $errorName;     

    function __construct()
    {
        parent::UA_Action ();
    }

    function checkLocalParams ()
    {
        $armyID = (int)$this->root->get_attribute("id_army");
        assert($armyID >0);
        
        $this->army = DE_Army::CreateFromID($armyID);
        
        return true;
    }

    // проверяем допустимость действия
    function checkAction ()
    {       
        global $params;
        
        assert(key_exists("uiid", $params));
        $uiid = (int)$params["uiid"];
        assert($uiid > 0);
        $userInstance = DE_UserInstance::CreateFromID($uiid);
        
        if(!GL_CanCancelAttack::Count($userInstance, $this->army))
        {       
            $this->errorName = GL_CanCancelAttack::$errorCode;
        };
        
        return true;
    }
    
    private function _processError()
    {
        assert(!empty($this->errorName));
        
        switch ($this->errorName)
        {
            case 'NOT_USER_ARMY':
                ds_error('Try cancel not this user army');
                break;
            case 'ARMY_NOT_IN_ACTION':
                $messparams = array();
                DE_SystemMessages::AddMessageFromAction(__CLASS__, 'NOT_CANCEL_ARMY_ACTION', $messparams, 1, 'GREEN');
                
                break;              
            case 'CANT_CANCEL_ATTACK':
                $messparams = array();
                DE_SystemMessages::AddMessageFromAction(__CLASS__, 'NOT_CANCEL_ARMY_ACTION', $messparams, 1, 'GREEN');
                
                break;          
            default:
                ds_error('Unknown error code');
        }           
    }
    
    // выполняем действие
    function performAction ()
    {
        global $params;         
        
        if(!empty($this->errorName))
        {
            $this->_processError();
            return true;
        }
        
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
        
        $now = GB_TypeFactory::CreateBase('GB_Date', time());
        
        ME_CancelAttack::Cancel($this->army, $now);
        
        $this->eqid = ME_CancelAttack::$eventQueueID;
        
        $endDate = ME_CancelAttack::$armyAction->getEndDate();
                        
        $messparams[0] = "{b} ".ME_CancelAttack::$armyAction->getCombFrom()->getFullName()."{/b}";
        $messparams[1] = "{b} ".$this->army->getName()."{/b}";
        $messparams[2] = "{b} ".ME_CancelAttack::$armyAction->getCombTo()->getFullName()."{/b}";
        $messparams[3] = "{b} ".$endDate->getSystemMessageFormat()."{/b}";
        $messparams[4] = "";
        $messparams[5] = "{b} ".ME_CancelAttack::$returnResources->getShortReturnList()."{/b}"; 

        //системное сообщение
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_ATTACK_MESS", $messparams, 1, 'GREEN' );
            
        //if ( $_SESSION['profi_game'] )
        //{
            $noCellData = true;
        //} else {
        //  $noCellData = false;
        //}
        
        // рефреш с актуализацией ресурсов
        if(!$this->refresh(2, "", "", 1, $noCellData))
        {
            return false;
        }
        
        $this->_sendHelper();
        
        return true;
    }       
    
    private function _sendHelper()
    {   
        if(!(isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == true)))
        {
            return true;
        }
        
        $armyAction = $this->army->getArmyAction();

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><army />";
        $doc = domxml_open_mem($xml);
        $root = $doc->document_element();

        $root->set_attribute("id_army", $this->army->getID());
        $root->set_attribute("inaction", 2);
        if ($this->army->useArtefacts==1)
            $root->set_attribute("useArtefacts", $this->army->useArtefacts);

        $ch = $root->new_child("transfers");
        $ch = $root->new_child("actions");

        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
            
        $messageparams[0] = $armyAction->getCombTo()->getName();
        $messageparams[1] = $armyAction->getEndDate()->getFormatedDate($delta);
        $messageparams[2] = $armyAction->getEndDate()->getFormatedTime($delta);
        
        $currentaction = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ARMY_RETURN', $messageparams, null, null, false);

        $elem = $doc->create_text_node($currentaction);
        $ch->append_child($elem);

        SU_OutputHandler::Singleton()->addString($doc->dump_mem() , 'helper');
    }
}
?>