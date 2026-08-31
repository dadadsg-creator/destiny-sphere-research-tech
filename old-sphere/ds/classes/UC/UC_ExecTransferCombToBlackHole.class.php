<?php $CVS_Version = '$Revision: 1.9 $ ; $Date: 2009/02/16 10:52:11 $';


class UC_ExecTransferCombToBlackHole extends UC_PaymentServiceBase
{
    static private $dbNameFrom;
    
    static private $worldIdFrom;
    
    static private $dbIdFrom;
    
    static private $worldIdTo = 3;
    
    static private $dbIdTo = 9;
    
    static private $dbNameTo = 'hell';
    
    static private $oldCombName;

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
                
            case 'transfer_comb_to_black_hole':
                if (!self::_TransferCombToBlackHole()) 
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

        foreach ($userworlds as $key=>$world) {
            if ($world['dbId'] == 8 || $world['dbId'] == 10)
                unset($userworlds[$key]);
        }
        
        //Смотрим, нет ли у игрока сот в черном мире
        $combInBlackHole = self::_getUserCombInWorld(self::$dbIdTo, self::$worldIdTo);
        if (count($combInBlackHole) > 0) 
        {
            self::$data['ERROR_TEXT'] = 'YOU_ALREADY_HAVE_COMB_IN_BLACK_HOLE';
            self::$data['MODE'] = 'TRANSFER_COMB_TO_BLACK_HOLE_SECOND_MENU';
            return;
        }
        
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
        assert(self::$dbId >= 0);
        assert(self::$worldId > 0);

        //Проверка на гейм даун
        if(GameDown(self::$dbId, self::$worldId))
        {
            header('Location: index_down.html');
            exit;
        }
        
        $_SESSION['dbId'] = 0;
        
        //Смотрим достаточно ли денег у игрока
        if (!self::_CheckEnoughMoney())
        {
            return false;
        }
            
        self::$data['MODE'] = 'TRANSFER_COMB_TO_BLACK_HOLE_SECOND_MENU';
        self::$data['WORLD_NAME'] = self::$worldName;
        self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
        
        //Смотрим, не заказал ли уже игрок перемещение своей соты в этом мире
        $_SESSION['dbId'] = 0;
        
        $info = DS_WorldTransfer::getTransferInfo((int)self::$dbId, (int)self::$worldId, self::$user->getID());
        //Если есть записи о переездах в этом мире  
        if (count($info) > 0) 
        {
            //Проходимся по всем переездам      
            foreach ($info as $k => $trans)
            {
                //Если переезд был заказан
                if ($trans['status'] == 1) 
                {
                    self::$data['ERROR_TEXT'] = 'YOU_ALREADY_HAS_BEEN_ORDER_TRANSFER_IN_THIS_WORLD';
                    return;
                }
            }
        }
        
        //Смотрим, не заказал ли уже игрок перемещение какой либо из своих сот в Черную Дыру
        $orderRestore = DS_WorldTransfer::getUserTransferToWorld((int)self::$dbIdTo, (int)self::$worldIdTo, self::$user->getID());
        //Если есть записи о переездах в этом мире  
        if (count($orderRestore) > 0) 
        {
            //Проходимся по всем переездам      
            foreach ($orderRestore as $k => $restore)
            {
                //Если переезд был заказан
                if ($restore['status'] == 1) 
                {
                    self::$data['ERROR_TEXT'] = 'YOU_ALREADY_HAS_BEEN_ORDER_TRANSFER_TO_BLACK_HOLE';
                    return;
                }
            }
        }
        
        //Смотрим, нет ли у игрока сот в черном мире
        $combInBlackHole = self::_getUserCombInWorld(self::$dbIdTo, self::$worldIdTo);
        if (count($combInBlackHole) > 0) 
        {
            self::$data['ERROR_TEXT'] = 'YOU_ALREADY_HAVE_COMB_IN_BLACK_HOLE';
            return;
        }

        //Смотрим, есть ли у игрока соты в данном мире
        $combInThithWorld = self::_getUserCombInWorld(self::$dbId, self::$worldId);

        if (empty($combInThithWorld) || count($combInThithWorld) == 0) 
        {
            self::$data['ERROR_TEXT'] = 'YOU__HAS_NOT_COMB_IN__THIS_WORLD';
            return;
        }

    }
    
    static private function _getUserCombInWorld($dbId, $worldId)
    {
        assert($dbId >= 0);
        assert($worldId > 0);
        
        $_SESSION['dbId'] = $dbId;
        
        $common = self::getCommon();
        $common->setDbId($dbId);
        
        //Получаем соты, перевезенные автоматом
        $sql = "
            SELECT 
                c.id_comb
            FROM 
                ds_user u
             LEFT JOIN ds_userinstance ui ON (ui.id_user = u.id_user)
             LEFT JOIN ds_comb c ON (c.id_userinstance = ui.id_userinstance)
            WHERE 
                u.username = '".self::$user->getName()."'
                AND
                ui.id_world = ".$worldId."
            ";

        $res = $common->getResultArray("", $sql, "", "", "");       
        assert(empty($common->errormsg));

        return $res;
    }
    
    
    static private function _TransferCombToBlackHole()
    {
        assert(self::$worldId > 0);
        
        $_SESSION['dbId'] = 0;
        
        
        //Смотрим достаточно ли денег у игрока
        if(!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation(self::$user, 'PS_TRANSFER_COMB_TO_BLACK_HOLE'))
        {
            self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
            self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';

            //Получаем имя мира, в котором есть достаточно денег для данной операции
            $worldName = UC_PaymentServiceMoneyOperation::CheckMoneyInWorlds(self::$user, 'PS_TRANSFER_COMB_TO_BLACK_HOLE');
            
            //Если такой мир есть
            if($worldName)
            {
                self::$data['WORLD_WHERE_ENOUGH_MONEY'] = $worldName;
            }
            return false;
        }
        
        //Заносим данные в табличку
        if (!self::_SetTransferInfo()) {
            self::$data['MSG'] = 'ERROR';
            return false;
        }
    
        //Снимаем деньги
        UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_TRANSFER_COMB_TO_BLACK_HOLE');
        
        //Логиреум
        if (!self::_Log('t', 'f', 't', 'f', 'PS_TRANSFER_COMB_TO_BLACK_HOLE', self::$data['PRICE'], self::$data['CREDIT'],  "World: ".self::$worldName))
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
    
    static private function _SetTransferInfo()
    {
        $_SESSION['dbId'] = 0;
        
        $common = self::getCommon();
        $common->setDbId(0);
        
        $dbNameFrom = $GLOBALS['dbWorldAccess'][self::$dbId]['server'];
        $sql = "
                INSERT INTO ds_worldtransfer
                    (id_user, db_from, db_to, id_world_from, id_world_to, transfer_type, status, transfer_date)
                VALUES
                    (".self::$user->getId().", '".$dbNameFrom."', '".self::$dbNameTo."', ".self::$worldId.", ". self::$worldIdTo.", 0, 1, now())
            ";
        $common->executeQuery($sql);

        if (empty($common->errormsg) || $common->errormsg == '')
        {
            return true;
        }
        
        return false;
    }
    
    
    static private function _getParams()
    {
        if(key_exists('exec_action', self::$data))
        {
            self::$action = self::$data['exec_action'];
        }
        
        if(key_exists('id_world_from', self::$data))
        {
            self::$worldIdFrom = (int)self::$data['id_world_from'];
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