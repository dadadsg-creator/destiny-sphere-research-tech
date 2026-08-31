<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/09/11 08:05:53 $';


class UC_ExecWorldAbility extends UC_PaymentServiceBase
{
    public function Exec()
    {
        self::_getParams();
        
        $_SESSION['dbId'] = 0;
        
        //получаем цену
        self::_GetPrice();  
        
        switch(self::$action)
        {
            case 'show_second_menu':
                    if (!self::_ShowSecondMenu()) {
                        self::_ShowIndex();
                    }
                break;  
                
            case 'add_world_ability':
                if (!self::_AddWorldAbility()) 
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
        
        //Получаем список миров, где игрок еще незареган
        $userworlds = self::_GetWorldsWhereUserNotRegistered();

        //Если у игрока нет миров, где он не зареган
        if(count($userworlds) == 0)
        {
            self::$data['MODE'] = 'WORLD_ABILITY_SECOND_MENU';
            self::$data['USER_REGIRTEREG_IN_ALL_WORLDS'] = 'YES';
            return;
        }
        
        self::$data['USER_WORLDS'] = $userworlds;           
    }
    
    static private function _ShowSecondMenu()
    {
        assert(self::$dbId >= 0);
        assert(self::$worldId > 0);

        $_SESSION['dbId'] = 0;
        
        //Смотрим достаточно ли денег у игрока
        if (!self::_CheckEnoughMoney())
        {
            return false;
        }
        
        $GLOBALS['billing_script_local_mode'] = true;

        $dataArr['id_user'] =self:: $user->getId();
        
        $dataArr['worldname'] = self::$worldName;
        
        $allower = new BG_AddWorldAbility($dataArr);
        
        //Проверка, не может ли игрок уже играть в этом мире
        $w = $allower->check();
        $res = $allower->GetResult();

        assert(isset($res['checking_result']));

        if ($res['checking_result'] != 'add_ability_ok')
        {
            self::$data['MSG'] = $res['checking_result'];
            return false;
        }
        
        self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
        self::$data['WORLD_NAME'] = self::$worldName;
        
        self::$data['MODE'] = 'WORLD_ABILITY_SECOND_MENU';
        self::$data['EXEC_ACTION'] = 'add_world_ability';
        self::$data['BUTTON_TEXT'] = 'Получить';
        
        return true;
    }
    
    
    static private function _AddWorldAbility()
    {
        assert(self::$dbId >= 0);
        assert(self::$worldId > 0);
        
        $_SESSION['dbId'] = self::$dbId;
        $_SESSION['world'] = self::$worldId;
        
        // Получаем World Identifier мира (для лога)
        $WI = DE_World::CreateFromID(self::$worldId)->getShortName();
        assert(!empty($WI));
        
        $_SESSION['dbId'] = 0;
        
        //Смотрим достаточно ли денег у игрока
        if (!self::_CheckEnoughMoney())
        {
            return false;
        }
        
        $GLOBALS['billing_script_local_mode'] = true;

        $dataArr['id_user'] =self:: $user->getId();
        
        $dataArr['worldname'] = self::$worldName;
        
        $allower = new BG_AddWorldAbility($dataArr);
        
        //Проверка, не является ли игрок уже обладателем этой возможности
        $w = $allower->check();
        $res = $allower->GetResult();

        assert(isset($res['checking_result']));

        if ($res['checking_result'] != 'add_ability_ok')
        {
            self::$data['MSG'] = $res['checking_result'];
            return false;
        }
            
        $allower->add();
        $res = $allower->GetResult();


        if ($res['ability_ok'] != 1)
        {
            self::$data['MSG'] = 'ERROR';
            return false;
        }

        $_SESSION['dbId'] = 0;
        
        //Снимаем деньги
        UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_WORLD_ABILITY', 1, $WI);
        
        //Логиреум
        if (!self::_Log('t', 'f', 't', 'f', 'PS_WORLD_ABILITY', self::$data['PRICE'], self::$data['CREDIT'], "World: ".self::$worldName))
        {
            self::$data['MSG'] = 'ERROR';
            return false;
        }
        
        ME_EndDBTransactions::Exec();
        header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&worldName=".self::$worldName);
        return true;
    }
    
    private static function _ShowSuccessful()
    {
        self::$data['WORLD_NAME'] = $_REQUEST['worldName'];
                
        self::$data['MODE'] = 'SUCCESSFUL';
    }
    static private function _getParams()
    {
        if(key_exists('exec_action', self::$data))
        {
            self::$action = self::$data['exec_action'];
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
    
    private static function _GetWorldsWhereUserNotRegistered()
    {
        $common = self::getCommon();
        
        GE_SearchBase::SetCommon($common);
        $worlds1 = GE_SearchStart::GetWorldsWhereUserNotRegistered(self::$user->getId());

        $key = 0;
        
        foreach ($worlds1 as $k => $world)
        {
            //Если в это открытый мир и в нем можно регаться
            if ($world['world_status'] == 1 && $world['for_new_players']=='f') {
                $worlds[$key] = $world;
                $worlds[$key]['dbId'] = $common->getDbIdByName($world['dbname']);
                $key ++;
            }
            
        }
        return $worlds;
    }   
}

?>