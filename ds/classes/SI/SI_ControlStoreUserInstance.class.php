<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс реулизует сохранение инстанса пользователя в мире в виде объекта.
 *
 */
class SI_ControlStoreUserInstance extends SI_Base
{       
    /**
     * Результат сохранения инстанса
     *
     * @var SI_ResultUserInstance
     */
    
    static private $userInstanceStore;
    
    /**
     * Собственно само сохранение
     *
     * @param integer $UIID
     * @param DS_Common $common
     * @return SI_ResultUserInstance
     */
    static public function Exec($UIID, $tempColony = 0)
    {
        self::_out('START STORE UIID '.$UIID);              
        
        //Содаем объект результата сохранения инстанса
        self::$userInstanceStore = new SI_ResultUserInstance($UIID);

        // заносим запись в лог
        self::_insertLogRecord();
        
        // рефреш пользователя
        self::_refreshUser();
        
        // лочим игрока
        self::_lockUser();

        //Сохраняем ивенты
        self::_storeEvents();   
        
        //Сохраняем религию пользователя
        self::_storeReligion();
        
        //Сохраняем науку пользователя
        self::_storeScience();
        
        //Сохраняем военный опыт пользователя
        self::_storeBattleXP();
        
        //Сохраняем комбы инстанса
        self::_storeCombs($tempColony);     
        
        //Сохраняем бонусы игрока
        self::_storeUserBonuses();

        // Сохраняем информацию о продовесе армий
        self::_storeArmyWeight();   
        
        // Сохраняем рейтинг
        self::_storeRanking();  
        
        //Сохраняем цепочку колонизации
        self::_storeColonizeChain();
        
        self::_storeColonizators();
        
        self::_storeAbilityes();
        
        self::_out('------ COMPLIT STORE OF UserInstance-------');
        
        return self::$userInstanceStore;
    }   
    
    // Рефреш игрока
    static private function _refreshUser()
    {
        self::_out('Start user refresh');
        $common = self::GetCommon();
        $command = constant('PHP_PATH')." -f ".$_SERVER['DOCUMENT_ROOT'].$GLOBALS['dbWorldAccess'][$common->dbId]['dir']."useraction.php ".$common->dbId." ".self::$userInstanceStore->getUIID()." 0 default 1";

        $out = '';
        $str = exec($command, $out);
        $outString = implode("\n", $out);
        //assert(strpos($outString, 'refresh='));
    }

    static private function _lockUser()
    {
        // SU_Locker::Create('USERACTION', self::GetCommon()->dbId)->lock(self::$userInstanceStore->getUIID());
    }

    static private function _insertLogRecord()
    {
        self::$logRecordId = SI_SearchComb::AddLogRecord(self::$userInstanceStore->getUserID(), self::$userInstanceStore->getWorldID());
    }
    
    static private function _storeReligion()
    {
        self::_out('Start store religion');
        $religionData = SI_SearchUserInstance::GetReligion(self::$userInstanceStore->getUIID());
        self::$userInstanceStore->setReligion($religionData);
    }
    
    static private function _storeScience()
    {
        self::_out('Start store science');
        $sciences = SI_SearchUserInstance::GetScinces(self::$userInstanceStore->getUIID());
        self::$userInstanceStore->setScience($sciences);
    }
    
    static private function _storeBattleXP()
    {
        self::_out('Start store battleXP');
        $battleXP = SI_SearchUserInstance::GetBattleXP(self::$userInstanceStore->getUIID());
        self::$userInstanceStore->setBattleXP($battleXP);
    }
    
    static private function _storeCombs($tempColony = 0)
    {       
        $userCombs = SI_SearchUserInstance::GetUserCombs(self::$userInstanceStore->getUIID());
        self::_out('Start store combs. Selected '.count($userCombs).' combs');
            
        foreach($userCombs as $k=>$combData)
        {                   
            $temp= false;
            if ($combData['id_comb'] == $tempColony) {
                // Эта колония станет временной
                $temp= true;
//SU_DebugTools::LogAll(' temp = true '.$combData['id_comb'] , 'AnnitaTr.log');
            }
            
            $resultComb = SI_ExecStoreComb::Exec($combData['id_comb'], $temp);
            self::$userInstanceStore->setComb($resultComb);
        }
    }

    static private function _storeUserBonuses()
    {
        self::_out('Start store user bonuses');
        $bonuses = SI_SearchUserInstance::GetUserBonuses(self::$userInstanceStore->getUIID());      
        self::$userInstanceStore->setUserBonuses($bonuses);
    }
    
    static private function _storeArmyWeight()
    {
        self::_out('Start store army weight');
        $armyWeight = SI_SearchUserInstance::GetArmyWeight(self::$userInstanceStore->getUIID());        
SU_DebugTools::LogAll($armyWeight, 'MassTransferArmyWeigh.log');            
        self::$userInstanceStore->setArmyWeight($armyWeight);
    }
    
    static private function _storeRanking()
    {
        self::_out('Start store ranking');
        $ranking = SI_SearchUserInstance::GetRanking(self::$userInstanceStore->getUIID());      
        self::$userInstanceStore->setRanking($ranking[0]);
    }
    
    static private function _storeEvents()
    {           
        SI_ExecStoreUserEvents::Exec(self::$userInstanceStore);     
    }
    
    static private function _storeColonizeChain()
    {           
        $colonizeChain = SI_SearchUserInstance::GetColonizeChail(self::$userInstanceStore->getUIID());
        self::$userInstanceStore->setColonizeChain($colonizeChain);
    }
    
    static private function _storeColonizators()
    {
        $colonizatorsData = SI_SearchUserInstance::GetColonizators(self::$userInstanceStore->getUIID());
        
        self::_out('Start store colonizators'); 
        
        self::$userInstanceStore->setColonizators($colonizatorsData);
    }
    
    static private function _storeAbilityes()
    {
        self::_out('Start store abilities');
        
        $abilityes = SI_SearchUserInstance::GetUserAbility(self::$userInstanceStore->getUIID());

        foreach($abilityes as $k=>$ability)
        {               
            self::$userInstanceStore->setAbility($ability['id_ability']);
        }
    }
}

?>