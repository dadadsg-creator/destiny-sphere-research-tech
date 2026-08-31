<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ќбработка аккаунтов после переезда
 * заморозка, проверка, удаление ...
 *
 */
class SI_FinalizeWorldTransfer
{
    static private $uid;
    
    static private $fromDBID;
    
    static private $toDBID;
    
    static private $fromWorldID;
    
    static private $toWorldID;
    
    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;
    
    static private $moveResult;
    
    static private $fromUIID;
    
    static private $toUIID;
    
    static private $fromWorldIdentifier;
    
    static private $error = '';
    
    static private $logFile = 'MoveFinalize_debug.log'; // 'MassMove.log'
    
    static public function Finalize()
    {
        // проверим, запустились ли из командной строки
        // self::_checkCommandLine();
        
        self::$common = DS_Common::CreateForCurentDB(0);
        
        SI_SearchFinalizeTransfer::SetCommon(self::$common);
        
        // получение параметров
        self::_getParams();
        
        // проверка был ли переезд успешным
        self::_checkTransferResult();

        // провер€ем не была ли заморожена сота еще до переезда
        self::_checkSrcCombFreez();

        // «амораживаем инстанс в мире из которого приехали
        self::_freezSrcComb();
        self::_checkStage('account_freezed');
        
        // «апрещаем переезд этого инстанса
        self::_disableSrcTransfer();
        self::_checkStage('transfer_disabled');
        
        // —равниваем основные параметры начального и конечного инстансов
        self::_validateSrcAndDstCheckSum();
        self::_checkStage('checksum_validated');
        
        // ƒобавл€ем в очередь на удаление инстанс в мире из которого приехали
        self::_addSrcToDeleteList();
        self::_checkStage('added_to_delete_list');
        
        // обновление данных в коллекции
        self::_updateInstanceCollection();
        self::_checkStage('added_to_collection');
        
        // помечаем, что переезд прошел удачно
        self::_updateTransferStatus();
        self::_checkStage('transfer_status_updated');
                
        // выводим результат
        self::_outResult();
    }
    
    /**
     * ѕроверка был ли вызван скрипт из командной строки
     *
     */
    static private function _checkCommandLine()
    {
        $sapi = php_sapi_name();
        if($sapi == "apache")
        {
            self::_log('ERROR: Script must be called from command line!', true);
        }
    }
    
    /**
     * ≈сли переезд был неудачным, то ничего не делаем
     *
     * @return unknown
     */
    static private function _checkTransferResult()
    {
        if(self::$moveResult >= 1)
        {
            return;
        }
        
        die('OK. RESULT: '.self::$moveResult);
    }
    
    /**
     * ѕровер€ем не была ли заморожена сота еще до переезда, 
     * и, если была, замораживаем в мире, в который приехали
     *
     */
    static private function _checkSrcCombFreez()
    {
        if(!empty(self::$error))
        {
            return;
        }
        
        self::$common->setDbId(self::$fromDBID);
        
        if(SI_SearchFinalizeTransfer::CheckVocation(self::$fromUIID))
        {
            self::_freezDstComb();
        }
    }
    
    /**
     * «аморозка инстанса в мире, в который переехали
     *
     */
    static private function _freezDstComb()
    {
        if(!empty(self::$error))
        {
            return;
        }
        
        self::$common->setDbId(self::$toDBID);
        
        SI_SearchFinalizeTransfer::AddVocation(self::$toUIID);
    }
    
    /**
     * «аморозка инстанса в исходном мире 
     *
     */
    static private function _freezSrcComb()
    {
        if(!empty(self::$error))
        {
            return;
        }
        
        self::$common->setDbId(self::$fromDBID);
        
        SI_SearchFinalizeTransfer::AddVocation(self::$fromUIID);
    }
    
    /**
     * Ѕлокируем переезды замороженной соты
     *
     */
    static private function _disableSrcTransfer()
    {
        if(!empty(self::$error))
        {
            return;
        }
        
        self::$common->setDbId(0);
        
        SI_SearchFinalizeTransfer::DisableManualTransfer(self::$uid, self::$fromDBID, self::$fromWorldID);
        
        self::$common->setDbId(self::$fromDBID);
        
        SI_SearchFinalizeTransfer::DisableAutoTransfer(self::$fromUIID);
    }
    
    /**
     * ѕроверка основных параметров сот до и после переезда
     *
     */
    static private function _validateSrcAndDstCheckSum()
    {
        if(!empty(self::$error))
        {
            return;
        }
        
        $srcCheckSum = self::_getInstanceCheckSum('from');
        
        $dstCheckSum = self::_getInstanceCheckSum('to');
        
        foreach ($srcCheckSum as $type => $res)
        {
            if($res != $dstCheckSum[$type])
            {
                self::_log('ERORR: check sum '.$type.' validation failed ', true);
                return;
            }
        }
    }
    
    static private function _getInstanceCheckSum($type)
    {
        assert($type = 'from' || $type = 'to');
        
        if($type = 'from')
        {
            $dbID = self::$fromDBID;
            $UIID = self::$fromUIID;
        } else {
            $dbID = self::$toDBID;
            $UIID = self::$toUIID;
        }
        
        self::$common->setDbId($dbID);
        $result = array();
        
        $result['ARMY']      = SI_SearchFinalizeTransfer::GetUserArmySum($UIID);
        $result['RESOURCE']  = SI_SearchFinalizeTransfer::GetUserResourceSum($UIID);
        $result['ARTEFACTS'] = SI_SearchFinalizeTransfer::GetUserArtefactSum($UIID);
        $result['BUILDINGS'] = SI_SearchFinalizeTransfer::GetUserBuildingSum($UIID);

        return $result;
    }
        
    /**
     * ƒобавл€ем исходный инстанс в очередь на удаление
     *
     */
    static private function _addSrcToDeleteList()
    {
        if(!empty(self::$error))
        {
            return;
        }
        
        $user = DE_User::CreateFromID(self::$uid);
        $world = DE_WorldFromCollection::CreateFromID(self::$fromWorldIdentifier);
        
        SU_DeleteUserList::AddToList($user, $world, 'TRANSFER');
    }
    
    static private function _updateTransferStatus()
    {
        if(!empty(self::$error))
        {
            return;
        }
        
        self::$common->setDbId(0);
        
        SI_SearchFinalizeTransfer::UpdateTransferStatus(self::$uid, self::$fromWorldID, self::$fromDBID, self::$toWorldID, self::$toDBID);
    }
    
    static private function _updateInstanceCollection()
    {
        if(!empty(self::$error))
        {
            return;
        }
        
        self::$common->setDbId(0);
        
        SI_SearchFinalizeTransfer::AddUserToCollection(self::$uid, self::$toUIID, self::$toDBID, self::$toWorldID);
    }
    
    /**
     * ќтправка результата (просто вывод на экран)
     *
     */
    static private function _outResult()
    {
        $res = 'OK';
        
        if(!empty(self::$error))
        {
            $res = self::$error;
        }
        
        echo $res;
    }
    
    static private function _checkStage($name)
    {
        if(self::$moveResult > 1)
        {
            self::$common->setDbId(self::$fromDBID);
            SI_SearchFinalizeTransfer::MarkSuccessFinalizeStage($name, self::$moveResult);
        }
    }
    
    /**
     * Ћогирование
     *
     * @param string $msg
     * @param bool $exit прервать ли исполниение
     */
    static private function _log($msg = '', $exit = false)
    {
        $msg .= "\n";
        SU_DebugTools::LogAll($msg, self::$logFile, 0);
        
        if($exit)
        {
            self::$error = $msg;
        }
    }
    
    /**
     * ѕолучение параметров из комендной строки
     *
     */
    static private function _getParams()
    {
        if(!empty(self::$error))
        {
            return;
        }
        
        assert(key_exists('1', $_SERVER['argv']));
        self::$uid = (int)$_SERVER['argv'][1];
        assert(self::$uid > 0); 
        
        assert(key_exists('2', $_SERVER['argv']));
        self::$fromDBID = (int)$_SERVER['argv'][2];
        assert(self::$fromDBID >= 0);
        
        assert(key_exists('3', $_SERVER['argv']));
        self::$fromWorldID = (int)$_SERVER['argv'][3];
        assert(self::$fromWorldID > 0);
        
        assert(key_exists('4', $_SERVER['argv']));
        self::$toDBID = (int)$_SERVER['argv'][4];
        assert(self::$toDBID >= 0);
        
        assert(key_exists('5', $_SERVER['argv']));
        self::$toWorldID = (int)$_SERVER['argv'][5];
        assert(self::$toWorldID > 0);
        
        assert(key_exists('6', $_SERVER['argv']));
        self::$fromUIID = (int)$_SERVER['argv'][6];
        assert(self::$fromUIID > 0);
        
        assert(key_exists('7', $_SERVER['argv']));
        self::$moveResult = (int)$_SERVER['argv'][7];
        assert(self::$moveResult >= 0);
        
        // буквенный »ƒ мира, из которого приехали
        self::$fromWorldIdentifier = SI_SearchFinalizeTransfer::GetWorldIdentifier(self::$fromWorldID, self::$fromDBID);
        
        // »ƒ инстанса в мире, в который приехали
        $_SESSION['dbId'] = self::$toDBID;
        self::$common->setDbId(self::$toDBID);
        self::$toUIID = DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$uid, self::$toWorldID);
    }
}

?>