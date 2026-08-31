<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2010/07/07 09:00:42 $';


class UC_ExecChangeCombName extends UC_PaymentServiceBase
{
    static private $newCombName;
    
    static private $oldCombName;
    
    static private $combId;
    
    public function Exec()
    {
        self::_getParams();
        
        $_SESSION['dbId'] = 0;
        
        //получаем цену
        self::_GetPrice();
        
        switch(self::$action)
        {
            case 'show_second_menu':
                    self::_ShowSecondMenu();
                break;  
                
            case 'change_comb_name':
                if (!self::_ChangeCombName()) 
                {
                    self::_ShowSecondMenu();
                }
                break;
                
            case 'successful':
                    self::_ShowSuccessful();
                break;  
                
            default:
                self::_ShowIndex();
                break;
        }
        
        UC_OutUserCabinet::Out('PaymantServices', self::$data);
    }
    
    static private function _ShowIndex()
    {
        self::$data['MODE'] = 'INDEX';
        self::$data['EXEC_ACTION'] = 'show_second_menu';

        $_SESSION['dbId'] = 0;
        
        //Получаем список миров игрока
        $userworlds = self::_GetUserWorlds();
        
        //Если мир у игрока один
        if(count($userworlds) == 1)
        {
            self::$worldId = (int)$userworlds[0]['id_world'];
            self::$dbId = (int)$userworlds[0]['dbId'];
            self::$worldName = $userworlds[0]['worldname'];
            
            self::_ShowSecondMenu();
        } 
        else{
            self::$data['USER_WORLDS'] = $userworlds;           
        }
    }
    
    static private function _ShowSecondMenu()
    {
        $_SESSION['dbId'] = 0;
        
        //Проверка на гейм даун
        if(GameDown(self::$dbId, self::$worldId))
        {
            header('Location: index_down.html');
            exit;
        }
        
        //Смотрим достаточно ли денег у игрока
        if (!self::_CheckEnoughMoney())
        {
            return false;
        }
        
        assert(self::$dbId >= 0);
        assert(self::$worldId > 0);
        
        self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
        self::$data['WORLD_NAME'] = self::$worldName;
        
        $_SESSION['dbId'] = self::$dbId;

        if (self::$dbId==0 && self::$user->getId() == 180040) {
            $combs = array();
        }
        else {
            //Получаем список всех сот игрока в данном мире
            $userInstanse = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);
            //Получаем айдишники сот игрока в этом мире
            $combs = DS_Comb::GetCombsByUserInstance($userInstanse, true);
        }
        
        self::$data['COMBS'] = $combs;
        
        if (!empty(self::$combId)) {
            self::$data['COMB_ID'] = self::$combId;
        } else {
            self::$data['COMB_ID'] = $combs[0]['id_comb'];
        }
        
        self::$data['MODE'] = 'CHANGE_COMB_NAME_SECOND_MENU';
        self::$data['EXEC_ACTION'] = 'change_comb_name';
        self::$data['BUTTON_TEXT'] = 'RENAME';
    }
    
    
    static private function _ChangeCombName()
    {
        assert(self::$dbId >= 0);
        assert(self::$worldId > 0);
        assert(self::$combId > 0);
        
        $_SESSION['dbId'] = 0;
        
        //Смотрим достаточно ли денег у игрока
        if (!self::_CheckEnoughMoney())
        {
            return false;
        }

        //Если поле с название соты было оставлено пустым
        if (empty(self::$newCombName))
        {
            self::$data['MSG'] = 'EMPTY_COMB_NAME';
            return false;
        }           
        
        $_SESSION['dbId'] = self::$dbId;
        $_SESSION['world'] = self::$worldId;
        $common = self::getCommon();
        
        // Получаем World Identifier мира (для лога)
        $WI = DE_World::CreateFromID(self::$worldId)->getShortName();
        assert(!empty($WI));
        
        // проверяем имя соты
        if(!VD_CombName::Validate(self::$newCombName, true, $common))
        {
            self::$data['MSG'] = VD_CombName::FetchErrorCode();
            return false;
        }
        
        $comb = DE_Comb::CreateFromID(self::$combId);
        
        // Проверяем, что сота не захватываемая
        assert(!$comb->getIsCapture());
        
        //Изменяем название
        $comb->setName(self::$newCombName);
        
        //Создаем обьект Кольцо
        $_SESSION['world'] = self::$worldId;
        $ring = DE_Ring::CreateFromNumber($comb->getRingNumber());
        
        //Сбрасываем кеш вида соты
        UA_ComplexView::ResetCache(self::$worldId, $ring->getID(), $comb->getComplexID());
        $_SESSION['dbId'] = 0;
        
        //Снимаем деньги
        UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_CHANGE_COMB_NAME', 1, $WI);
        
        //Логиреум
        if (!self::_Log('t', 'f', 't', 'f', 'PS_CHANGE_COMB_NAME', self::$data['PRICE'], self::$data['CREDIT'], "Old name: ".self::$oldCombName.", New name: ".self::$newCombName.", World: ".self::$worldName))
        {
            self::$data['MSG'] = 'ERROR';
            return false;
        }
        
        //Завершаем транзакцию
        $_SESSION['dbId'] = self::$dbId;
        GB_SingletonObjectStorage::CommitAllCommitable();
        
        ME_EndDBTransactions::Exec();
        header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&oldName=".self::$oldCombName."&newName=".self::$newCombName);
        return true;
    }
    
    private static function _ShowSuccessful ()
    {               
        self::$data['OLD_NAME'] = $_REQUEST['oldName'];
        self::$data['NEW_NAME'] = $_REQUEST['newName'];
        self::$data['MODE'] = 'SUCCESSFUL';
    }
    
    static private function _getParams()
    {
        if(key_exists('exec_action', self::$data))
        {
            self::$action = self::$data['exec_action'];
        }
        
        if(key_exists('comb_name', self::$data))
        {
            self::$newCombName = self::$data['comb_name'];
        }
        
        if(key_exists('comb', self::$data))
        {
            $strpos = strpos(self::$data['comb'], '_');
            assert($strpos > 0);
            
            self::$combId = (int)substr(self::$data['comb'], 0, $strpos);
            self::$oldCombName = substr(self::$data['comb'], $strpos+1);
            
            assert(self::$combId > 0);
            assert(!empty(self::$oldCombName));
        }
        
        if(key_exists('world', self::$data))
        {
            $arr = explode('_', self::$data['world']);
            self::$dbId = (int)$arr[0];
            self::$worldId = (int)$arr[1];
            self::$worldName = $arr[2];
            
            assert(self::$dbId >= 0);
            assert(self::$worldId > 0);
            assert(!empty(self::$worldName));
        }
    }
}

?>