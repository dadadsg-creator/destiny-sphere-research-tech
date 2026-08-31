<?php $CVS_Version = '$Revision: 1.13 $ ; $Date: 2009/02/16 10:52:11 $';


class UC_ExecRestoreCombFromBlackHole extends UC_PaymentServiceBase
{
    static private $dbNameFrom;
    
    static private $worldIdFrom;
    
    static private $dbIdFrom;
    
    static private $worldIdTo = 3;
    
    static private $dbIdTo = 9;
    
    static private $dbNameTo = 'hell';

    public function Exec()
    {
        self::_getParams();
        
        $_SESSION['dbId'] = 0;
        
        //получаем цену
        self::_GetPrice();
        
        switch(self::$action)
        {
            case 'restore_comb_from_black_hole':
                if (!self::_RestoreCombFromBlackHole()) 
                {
                    self::_ShowIndex();
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
        self::$data['MODE'] = 'RESTORE_COMB_FROM_BLACK_HOLE_INDEX';
        self::$data['EXEC_ACTION'] = 'restore_comb_from_black_hole';
                
        //Получем информацию о переезде сот в черную дыру
        $combInBlackHole = self::_getUserCombInWorld(self::$dbIdTo, self::$worldIdTo);

        if (count($combInBlackHole) <= 0) 
        {
            self::$data['ERROR_TEXT'] = 'YOU_HAS_NOT_COMB_IN_BLACK_HOLE';
            return false;
        }
        
        $_SESSION['dbId'] = 0;

        //Смотрим, не заказал ли уже игрок восстановление соты
        $info = DS_WorldTransfer::getTransferInfo((int)self::$dbIdTo, (int)self::$worldIdTo, self::$user->getID());
        //Если есть записи о переездах в этом мире  
        if (count($info) > 0) 
        {
            //Проходимся по всем переездам      
            foreach ($info as $k => $trans)
            {
                //Если переезд был заказан
                if ($trans['status'] == 1) 
                {
                    self::$data['ERROR_TEXT'] = 'YOU_ALREDY_HAS_BEEN_ORDERED_RESTORE_FROM_BLACK_HOLE';
                    return;
                }
            }
        }
        
        //Получаем данные о переезде
        $transferData = self::_GetUserTransferData();   
        
        //Смотрим, нет ли у игрока уже соты в том мире, куда он хочет переехать
        $combInDestinationWorld = self::_getUserCombInWorld($transferData['db_id_from'], $transferData['id_world_from']);
        if (count($combInDestinationWorld) > 0) 
        {
            $worldData = DS_World::GetByID($transferData['id_world_from']);
            $worldName = $worldData['worldname'];
            self::$data['ERROR_TEXT'] = 'У вас уже есть сота в мире '.$worldName;
            return false;
        }

        
        self::$data['transfer_date'] = $transferData['transfer_date'];
        self::$data['db_id_from'] = $transferData['db_id_from'];
        self::$data['id_world_from'] = $transferData['id_world_from'];
        self::$data['db_name_from'] = $transferData['db_name_from'];
    }
    
    static private function _GetUserTransferData()
    {
        $_SESSION['dbId'] = 0;
        
        $common = self::getCommon();
        $common->setDbId(0);
        
        //Получаем соты, перевезенные автоматом
        $sql = "
            SELECT * 
            FROM 
                ds_worldtransfer_stat 
            WHERE
                db_to = '".self::$dbNameTo."'
                 AND
                id_world_to = ".self::$worldIdTo."
                 AND
                id_user = ".self::$user->getId()."
            ORDER BY transfer_date DESC
            LIMIT 1
            ";

        $res = $common->getResultArray("", $sql, "", "", "");       
        assert(empty($common->errormsg));
        
        if ($res) 
        {
            $transferData['db_id_from'] =  $common->getDbIdByName($res[0]['db_from']);
            $transferData['id_world_from'] = $res[0]['id_world_from'];
            $transferData['transfer_date'] = $res[0]['transfer_date'];
            $transferData['db_name_from'] = $res[0]['db_from'];
            
            return $transferData;
        }
        
        //Получаем соты, перевезенные ручками
        $sql = "
            SELECT * 
            FROM 
                ds_storeuserdata 
            WHERE
                db_name_to = '".self::$dbNameTo."'
                 AND
                id_world_to = ".self::$worldIdTo."
                 AND
                id_user = ".self::$user->getId()."
            ORDER BY store_date DESC
            LIMIT 1
            ";

        $res = $common->getResultArray("", $sql, "", "", "");       
        assert(empty($common->errormsg));
        
        if ($res) 
        {
            $transferData['db_id_from'] =  $common->getDbIdByName($res[0]['db_name_from']);
            $transferData['id_world_from'] = $res[0]['id_world_from'];
            $transferData['transfer_date'] = $res[0]['store_date'];
            $transferData['db_name_from'] = $res[0]['db_name_from'];
            
            return $transferData;
        }
        
        return false;
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
    
    
    static private function _RestoreCombFromBlackHole()
    {
        //Проверка на гейм текущей базы
        if(GameDown(self::$dbIdFrom, self::$worldIdFrom))
        {
            header('Location: index_down.html');
            exit;
        }

        //Смотрим, нет ли у игрока уже соты в том мире, куда он хочет переехать
        $combInDestinationWorld = self::_getUserCombInWorld(self::$dbIdFrom, self::$worldIdFrom);
        if (count($combInDestinationWorld) > 0) 
        {
            return false;
        }
        
        //Проверка на гейм даун черной дыры
        if(GameDown(self::$dbIdTo, self::$worldIdTo))
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
        
        //Заносим данные в табличку
        if (!self::_SetTransferInfo()) {
            self::$data['MSG'] = 'ERROR';
            return false;
        }
    
        //Снимаем деньги
        UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_RESTORE_COMB_FROM_BLACK_HOLE');
        
        //Логиреум
        if (!self::_Log('t', 'f', 't', 'f', 'PS_RESTORE_COMB_FROM_BLACK_HOLE', self::$data['PRICE'], self::$data['CREDIT'],  "DbNameTo: ".self::$dbNameTo.", DbNameFrom: ".self::$dbNameFrom.", WorldIdTo: ".self::$worldIdTo.", WorldIdFrom ". self::$worldIdFrom))
        {
            self::$data['MSG'] = 'ERROR';
            return false;
        }
        
        ME_EndDBTransactions::Exec();
        header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful");
        return true;    
    }
    
    private static function _ShowSuccessful()
    {
        self::$data['MODE'] = 'SUCCESSFUL';
    }
    
    static private function _SetTransferInfo()
    {
        $common = self::getCommon();
        $common->setDbId(0);
        $sql = "
                INSERT INTO ds_worldtransfer
                    (id_user, db_from, db_to, id_world_from, id_world_to, transfer_type, status, transfer_date)
                VALUES
                    (".self::$user->getId().", '".self::$dbNameTo."', '".self::$dbNameFrom."', ".self::$worldIdTo.", ". self::$worldIdFrom.", 0, 1, now())
            ";

        $common->executeQuery($sql);
        if (!empty($common->errormsg))
        {
            return false;
        }
        
        return true;
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
        
        if(key_exists('db_id_from', self::$data))
        {
            self::$dbIdFrom = (int)self::$data['db_id_from'];
        }
            
        if(key_exists('db_name_from', self::$data))
        {
            self::$dbNameFrom = self::$data['db_name_from'];
        }
    }
}

?>