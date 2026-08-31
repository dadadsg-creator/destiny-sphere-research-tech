<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс созхздающий инстанс в мире на основание сохраненнокго
 *
 */
class SI_ControlRestoreUserInstance extends SI_Base
{
    static private $newUIID = 0;
    static private $worldID = 0;
    static private $combCoords;
    
    /**
     * Enter description here...
     *
     * @var SI_ResultUserInstance
     */
    static private $userInstanceResult;
    static private $worldToData = array();
    
    static public function Restore($worldID, $combCoords, SI_ResultUserInstance $userInstanceResult, $log = true, $sendMessage = true)
    {       
        assert((int)$worldID > 0);
        assert(is_array($combCoords));

        self::$worldID = (int)$worldID;     
        self::$combCoords = $combCoords;
        self::$userInstanceResult = $userInstanceResult;
        
        self::$worldToData = SI_SearchUserInstance::GetWorldData(self::$worldID);
        
        //Проверяем возможность преезда
        SI_TestRestoreUserInWorld::Test($worldID, $combCoords, $userInstanceResult->getUserName(), $userInstanceResult->getEmail());
        
        //Занесли в словарик
        self::$idDictionary['ID_World'][self::$userInstanceResult->getWorldID()] = self::$worldID;
                
        self::_out('START RESTORE UserInstance');
        
        /*if(SI_SearchUserInstance::GetUserInstanceDataByUserID(self::$userInstanceResult->getUserID(), self::$worldID))
        {
            ds_error('User is alredy have instance in this world');
        }*/
        
        //self::_validateCombCoords();      
        
        self::_createInstance();
        
        self::_lockUser();
        
        self::_cretaeSciences();
        
        self::_createReligion();
        
        self::_createBattleXP();
        
        self::_createCombs();
        
        self::_createEvents();
        
        self::_restoreColonizeChain();
        
        self::_restoreColonizators();
                
        self::_createAbilityes();
        
        self::_restoreMessageScopes();
        
        self::_restoreVocationMode();
        
        // Восстанавлисваем возможность строить ДО
        self::_restoreGrandBuilding();
        
        // Восстанавливаем бонусы
        self::_restoreUserBonuses();
        
        // Восстанавливаем вес армии
        self::_restoreArmyWeight();
        
        // Восстанавливаем рейтинг
        self::_restoreRanking();
        
        // Шлем письмо в зеденку
        if ($sendMessage) {
          // self::_sendMessage();
        }
        
        $msg = '------ COMPLIT RESTORE OF UserInstance; Log ID: '.self::$logRecordId.' -------';
        self::_out($msg);
        
        return self::$newUIID;
    }
    
    static private function _restoreMessageScopes()
    {
        self::_out('Restore message scopes');
        
        SI_SearchUserInstance::InsertMessageScope(self::$newUIID, 1);
        SI_SearchUserInstance::InsertMessageScope(self::$newUIID, 3);
    }
    
    static private function _validateCombCoords()
    {           
        self::_out('Start validate comb coordinates');
        $combs = self::$userInstanceResult->getComb();
        foreach($combs as $k=>$resultComb)
        {
            if(!key_exists($resultComb->getCombName(), self::$combCoords))
            {
                ds_error('Cant find new coord for comb '.self::$resultComb->getCombName());
            }

            self::_validateCombCoord(self::$combCoords[$resultComb->getCombName()]);        
        }
    }
    
    static private function _validateCombCoord($combCoords)
    {       
        assert(key_exists('RING',$combCoords));
        assert(key_exists('COMPLEX',$combCoords));
        assert(key_exists('COMB',$combCoords));
        
        //Проверям что такое комбы есть и сводобны
        $combID = SI_SearchComb::GetCombIDByCoordinates(self::$worldID, $combCoords['RING'], $combCoords['COMPLEX'], $combCoords['COMB']);
                
        $combCoordStr = $combCoords['RING'].".".$combCoords['COMPLEX'].".".$combCoords['COMB']." in world ".self::$worldID;
        
        if($combID == 0)
        {
            ds_error('Not exists comb '.$combCoordStr);
        }
        
        $combData = SI_SearchComb::GetCombDataByID($combID);
        
        if(!empty($combData['id_userinstance']))
        {
            ds_error('This comb have owner '.$combCoordStr);
        }               
    }
    
    static private function _createInstance()
    {           
        self::_out('Start create new UIID');        

        $lastRefreshDateObject = GB_TypeFactory::CreateBase('GB_Date', time() + self::$userInstanceResult->getLastRefreshDelta());  

        /*        
        $userData = SI_SearchUserInstance::GetUserDataByName(self::$userInstanceResult->getUserName());     
        if(count($userData) == 0)
        {
            $userData = SI_SearchUserInstance::GetUserDataByEmail(self::$userInstanceResult->getEmail());
        }
        
        if(count($userData) == 0)
        {
            //ds_error('Cant find user for name = '.self::$userInstanceResult->getUserName());
            $userID = SI_SearchUserInstance::InsertUser(self::$userInstanceResult->getUserData());
            
        }else{
            $userID = $userData['id_user'];
        }
        */

        $userData = self::$userInstanceResult->getUserData();
        $userID = $userData['id_user'];
        
        $newbDefend = self::$userInstanceResult->getNewbDefend();
        
        if($newbDefend->value < time())
        {
            $newbDefend->value = time();
        }
        
        // Добавляем защиту новичка для только что переехавшего
        $_SESSION['dbId'] = self::$common->getCurrentDBID();
        $_SESSION['world'] = self::$worldID;
        $newbDefendAfterTransfer = (int)DE_WorldSettings::CreateForCurentWorld()->get('WORLD_TRANSFER_NEWB_DEFEND') * 24 * 3600;
        $newbDefend->value += $newbDefendAfterTransfer;
        
        self::$newUIID = SI_SearchUserInstance::InsertNewInstance($userID, self::$worldID, self::$userInstanceResult->getRaceID(), $lastRefreshDateObject->getPgFormat(), $newbDefend, self::$userInstanceResult->getLeague(), self::$userInstanceResult->getNubhelplevel());
        
        //Занесли в словарик
        self::$idDictionary['ID_UserInstance'][self::$userInstanceResult->getUIID()] = self::$newUIID;
        
        self::_out('New UIID = '.self::$newUIID);
    }
    
    static private function _lockUser()
    {
        SU_Locker::Create('USERACTION', self::GetCommon()->dbId)->lock(self::$newUIID);
    }
    
    static private function _cretaeSciences()
    {
        self::_out('Start create sciences');
        
        $sciences = self::$userInstanceResult->getSciences();
        
        foreach($sciences as $k=>$scienceData)
        {           
            $scienceID = SI_SearchUserInstance::InsertUserScience(self::$newUIID, $scienceData['id_science'], $scienceData['inresearch']);          
        }
    }
    
    static private function _createReligion()
    {
        self::_out('Start create religion');
                        
        $religionData = self::$userInstanceResult->getReligion();
        
        if(!is_array($religionData))
        {
            return;
        }

        SI_SearchUserInstance::SetReligion($religionData['id_religion'], self::$newUIID, $religionData['id_status']);
    }
    
    static private function _createBattleXP()
    {
        self::_out('Start create battleXP');
        
        $battleXP = self::$userInstanceResult->getBattleXP();
        // Затыка для старых шаблонов
        if (!is_array($battleXP)) {
            $battleXP = array('value' => $battleXP, 'absolute_value' => $battleXP);
        }
        SI_SearchUserInstance::SettBattleXP(self::$newUIID, $battleXP);
    }
    
    static private function _createCombs()
    {
        $combsArray = self::$userInstanceResult->getComb();
        
        self::_out('Start create combs. Selected '.count($combsArray).' combs');
        
        foreach($combsArray as $k=>$resultComb)
        {
            SI_ExecRestoreComb::Exec(self::$worldID, self::$newUIID, self::$combCoords[$resultComb->getCombName()], $resultComb);
        }
        
    }   
    
    static private function _createEvents()
    {   
        SI_ExecRestoreUserEvents::Exec(self::$userInstanceResult, self::$newUIID, self::$worldID);      
    }
    
    static private function _createAbilityes()
    {
        self::_out('Start create abilities');
        
        $abilityes = self::$userInstanceResult->getAbilityes();     
        
        foreach($abilityes as $k=>$abilityID)
        {
            SI_SearchUserInstance::InsertUserAbility(self::$newUIID, $abilityID);
        }
    }   

    static private function _restoreColonizeChain()
    {
        $colonizeChain = self::$userInstanceResult->getColonizeChain();
        
        foreach($colonizeChain as $k=>$colonyData)
        {
            // Если у нас не захватываемая колония (для которой нет данных)
            if (key_exists($colonyData['id_comb_to'], self::$idDictionary['ID_Comb'])) {
                $combFromID = self::$idDictionary['ID_Comb'][$colonyData['id_comb_from']];
                $combToID = self::$idDictionary['ID_Comb'][$colonyData['id_comb_to']];
                SI_SearchUserInstance::InsertColony($combFromID, $combToID);
            }
            
        }
    }
    
    static private function _restoreColonizators()
    {
        $colonizatorsData = self::$userInstanceResult->getColonizators();
        
        foreach($colonizatorsData as $k=>$colonizatorData)
        {
            if(!key_exists($colonizatorData['id_basecomb'], self::$idDictionary['ID_Comb']))
            {
                continue;
            }
            
            $baseCombID = self::$idDictionary['ID_Comb'][$colonizatorData['id_basecomb']];
            
            if((int)$colonizatorData['id_currentcomb'] == 0)
            {
                $currentCombID = 0;
            }else{
                $currentCombID = self::$idDictionary['ID_Comb'][$colonizatorData['id_currentcomb']];    
            }
            
            SI_SearchUserInstance::InsertColonyzators(self::$newUIID, $baseCombID, $currentCombID);
        }
    }
    
    static private function _restoreVocationMode()
    {
        self::_out('Start restore vocation mode');
        
        $vocationMode = self::$userInstanceResult->getVocationMode();
        
        if(!is_array($vocationMode))
        {
            return;
        }
        
        assert(is_array($vocationMode));
        assert(key_exists('START_DATE', $vocationMode));
        assert(key_exists('END_DATE', $vocationMode));
        assert(key_exists('PASSWORD', $vocationMode));
        
        if(empty($vocationMode['START_DATE']))
        {
            return;
        }
        
        SI_SearchUserInstance::InsertVocationMode(self::$newUIID, $vocationMode['START_DATE'], $vocationMode['END_DATE'], $vocationMode['PASSWORD']);
    }
    
    static private function _restoreGrandBuilding()
    {
        self::_out('Start restore grand building');
        $grandBuilding = self::$userInstanceResult->getGrandBuilding();
        
        if(empty($grandBuilding) || $grandBuilding == 0)
        {
            return;
        }
        
        assert(is_int($grandBuilding) || $grandBuilding > 0);

        SI_SearchUserInstance::InsertGrandBuilding(self::$newUIID, $grandBuilding);
    }   
    
    static private function _restoreUserBonuses()
    {
        self::_out('Start restore user bonuses');
                        
        $userBonuses = self::$userInstanceResult->getUserBonuses();
        
        if(!is_array($userBonuses))
        {
            return;
        }

        foreach($userBonuses as $k => $bonus)
        {
            SI_SearchUserInstance::SetUserBounus($bonus['id_attribute'], self::$newUIID, $bonus['value'], $bonus['start_date'], $bonus['end_date']);
        }
    }
    
    static private function _restoreArmyWeight()
    {
        self::_out('Start restore army weight');
                        
        $armyWeight = self::$userInstanceResult->getArmyWeight();
        
        if(!is_array($armyWeight))
        {
            return;
        }

        assert(count($armyWeight) == 1);
        
        SI_SearchUserInstance::SetArmyWeight(self::$newUIID, $armyWeight[0]['army_weight_built'], $armyWeight[0]['army_weight_lost']);
    }   
    
    static private function _restoreRanking()
    {
        self::_out('Start restore ranking');
                        
        $ranking = self::$userInstanceResult->getRanking();
        
        if(!is_array($ranking))
        {
            return;
        }

        //assert(count($ranking) == 1);
        
        SI_SearchUserInstance::SetRanking(self::$newUIID, $ranking);
    }   
    
    static private function _sendMessage()
    {
        self::_out('Start send message to user');
        
        $recieverName = self::$userInstanceResult->getUserName();

        // Пишем игроку в зеленку
        require_once($_SERVER['DOCUMENT_ROOT']."/ds/language/".constant("DEFAULT_LANGUAGE_SHORT")."/world_transfer_latter.lang");
        require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

        // создаем объект смарти
        $tpl = new Smarty;
        $tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;

        $tpl->assign('lang', $lang);

        $tpl->assign('USERNAME', $recieverName);

        $message = $tpl->fetch('world_transfer_latter.tpl');
        
        $messageScopeID = DS_Message::GetMessageScopeIDByName('SYSTEM');
        assert($messageScopeID > 0);

        $fromUIID = 0;

        $subject ="Внимание!";

        $subject = pg_escape_string(urldecode($subject));
        assert(!empty($subject));
        
        $shortMassage = substr($message, 0, 100);
        $message = pg_escape_string(GL_EncodeGameMessage::Encode(urldecode($message)));
        assert(!empty($message));

        $senderName = 'DestinySphere';
        $messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);
        DS_Message::AddMessageToUser($messageID, self::$newUIID);

    }
}

?>