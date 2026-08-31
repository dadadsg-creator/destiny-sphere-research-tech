<?php $CVS_Version = '$Revision: 1.13 $ ; $Date: 2009/11/09 11:06:55 $';


/**
 * Базовый классс статический для платных услуг
 */
class UC_PaymentServiceBase {
        
    static protected $action = 'index';
    
    static protected $user;
    
    static protected $data = array();
    
    static protected $dbId;
    
    static protected $worldId;
    
    static protected $worldName;
    
    function __construct(DE_User $user, $data)
    {
        assert(is_array($data));
        
        self::$user = $user;
        
        self::$data = $data;
        
        self::$data['PS'] = $data['ps_action'];
    }
    
    /**
    * @return Boolean
    * @desc Возвращает услуги для кабинета
    */
    public static function GetPaymentServices()
    {
        $common = self::getCommon();
        $common->setDbId(0);
        
        $sql = "
                SELECT
                     ps.service_name, 
                     ps.handler_class_name, 
                     ps.section
                FROM 
                    ds_payment_services ps
                LEFT JOIN ds_money_action_types mat ON (mat.action_name = ps.money_ation_tupe_accordance)                    
                WHERE
                mat.is_active = 't'
                ORDER BY sort_order
                 ";

        $res = $common->getResultArray("PAYMENT_SERVICES", $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        foreach ($res as $key => $data)
        {
            $services[$data['service_name']]['header'] = $data['handler_class_name'];
            $services[$data['service_name']]['section'] = $data['section'];
        }
        
        return $services;
    }
    
        /**
    * @return Boolean
    * @desc Возвращает активна ли услуга
    */
    public static function IsServiceActive($secvice)
    {
        $common = self::getCommon();
        $common->setDbId(0);
        
        $sql = "
            SELECT
                 mat.is_active
            FROM ds_payment_services ps
            LEFT JOIN ds_money_action_types mat ON (mat.action_name = ps.money_ation_tupe_accordance)  
            WHERE
                 ps.service_name = '$secvice'";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        assert(count($res) == 1);
        
        return $res[0]['is_active'];
    }
    
    /**
    * @return Array
    * @desc Возвращает список миров, в которых зареган игрок
    */
    protected static function _GetUserWorlds ($checkTrainSpeedup=false)
    {               
        $common = self::getCommon();
        
        GE_SearchBase::SetCommon($common);
        $worldsWhereUserIsRegistered = GE_SearchStart::GetWorldsWhereUserIsRegistered(self::$user->getId());

        $i = 0;
        
        foreach ($worldsWhereUserIsRegistered as $k => $worldData)
        {
            $dbId = (int)$common->getDbIdByName($worldData['dbname']);
            $worldId = (int)$worldData['id_world'];

            //Смотрим, не закрыта ли игра на GameDown
            if (GameDown($dbId, $worldId)) {
                continue;
            }

            $_SESSION['dbId'] = $dbId;
            $_SESSION['world'] = $worldId;

            //Смотрим, открыт ли в этом мире кабинет
            $settings = DE_WorldSettings::CreateForCurentWorld();
            $allowPaymentServices = (int)$settings->get('ALLOW_PAYMENT_SERVICES');
            if ($checkTrainSpeedup && $allowPaymentServices==1) $allowPaymentServices = (int)$settings->get('TRAIN_SPEEDUP');

            if ($allowPaymentServices == 1) {
                $userWorlds[$i] = $worldData;
                $userWorlds[$i]['dbId'] = $dbId;
                $i ++;
            }
        }
        return $userWorlds;
    }//END OF METHOD
    
    /**
    * @return Boolean
    * @desc Возвращает значение, показывающее достаточно ли у грока денег
    */
    protected static function _CheckEnoughMoney($serviceDetail = '')
    {               
        $_SESSION['dbId'] = 0;

        if(!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation(self::$user, self::$data['PS'].$serviceDetail))
        {
            self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
            self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';

            //Смотрим, может есть достаточное количество золотых крелитов
            if (UC_PaymentServiceMoneyOperation::CheckEnoughtMoney(self::$user, self::$data['PS'].$serviceDetail, 'UC')) {
                self::$data['ENOUGHT_GOLD'] = 'true';
                //UC_OutUserCabinet::Out('PaymantServices', self::$data);
                return false;
            }
            
            //Получаем имя мира, в котором есть достаточно денег для данной операции
            $worldName = UC_PaymentServiceMoneyOperation::CheckMoneyInWorlds(self::$user, self::$data['PS'].$serviceDetail);
            
            //Если такой мир есть
            if($worldName)
            {
                self::$data['WORLD_WHERE_ENOUGH_MONEY'] = $worldName;
            }
            //UC_OutUserCabinet::Out('PaymantServices', self::$data);
            return false;
        }
        return true;
    }//END OF METHOD
     
    /**
    * @return Boolean
    * @desc Возвращает значение, показывающее достаточно ли у НУЖНЫХ КРЕДИТОВ денег
    * ЛИШНИЙ МЕТОД!!! ОБЬЕДИНИТЬ С ВЕРХНИМ!!!
    */
    protected static function _CheckEnoughCredit($creditName)
    {               
        $_SESSION['dbId'] = 0;
        assert($creditName == 'SC' || $creditName == 'GC');

        //Получаем цену
        $actionType = DE_MoneyActionType::CreateFromName(self::$data['PS']);
        $MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
        
        // Если мы хотим заплатить в сере
        if ($creditName == 'SC') {
            assert($MoneyActionType->getAmount() != 0);
            assert($MoneyActionType->getCreditName() == 'SC');
            if (!UC_PaymentServiceMoneyOperation::CheckMoney(self::$user, $MoneyActionType->getAmount(), $MoneyActionType->getCreditName(), 'UC'))
            {
                self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
                return false;
            }
        } else {
            assert($MoneyActionType->getPriceInGolg() != 0);
            if (!UC_PaymentServiceMoneyOperation::CheckMoney(self::$user, $MoneyActionType->getPriceInGolg(), 'GC', 'UC'))
            {
                self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
                return false;
            }
        }
        
        return true;
    }//END OF METHOD 
    
    /**
    * @return Int
    * @desc Возвращает цену услуги
    * @desc Возвращает цену услуги
    */
    protected static function _GetPrice()
    {               
        $_SESSION['dbId'] = 0;
        
        $actionType = DE_MoneyActionType::CreateFromName(self::$data['PS']);
        $MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);

        self::$data['PRICE'] = $MoneyActionType->getAmount();
        self::$data['CREDIT'] = $MoneyActionType->getCreditName();
        
        self::$data['PRICE_IN_GOLD'] = $MoneyActionType->getPriceInGolg();
    }//END OF METHOD
    
    /**
    * @return Array
    * @desc Возвращает список миров, в которых зареган игрок
    */
    /*protected static function _ShowSuccessful ()
    {               
        if (self::$data['CREDIT'] == 'SC')
        {
            $creditName = 'серебряных кредитов';
        } 
        
        if (self::$data['CREDIT'] == 'GC')
        {
            $creditName = 'золотых кредитов';
        }
                
        self::$data['MODE'] = 'SUCCESSFUL';
        self::$data['SUCCESSFUL'] = $_REQUEST['desc']."<br>
                                    С вашего счета в кабинете снято <b>".self::$data['PRICE']."</b> $creditName.";
    }//END OF METHOD*/
    
    
    /**
    * @return DS_Common
    * @desc Возвращяет из глобальнйо области DS_Common экземпляр
    */
    protected static function getCommon ()
    {               
        return DS_Common::CreateForCurentDB();
    }//END OF METHOD
    
    
    /**
    * @return 
    * @desc Логирует действие
    */
    protected static function _Log ($isPaid, $isOrdered, $isComplete, $isCanceled, $actionName,  $prise, $credit, $info, $usersArr = 0)
    {               
        $common = self::getCommon();
        
        $idLog = DS_Base::getNextSeqVal($common, 'ds_paymentserviceslog_id_paymentserviceslog_seq');
        if(!empty($common->errormsg))
        {
            return false;
        }
        
        if ($credit == 'SC')
        {
            $SC = $prise;
        } else {
            $SC = 0;
        }
        
        if ($credit == 'GC')
        {
            $GC = $prise;
        } else {
            $GC = 0;
        }
        
        if ($isPaid == 't')
        {
            $PaidDate = 'now()';
        } else {
            $PaidDate = 'NULL';
        }
        
        if ($isOrdered == 't')
        {
            $OrderedDate = 'now()';
        } else {
            $OrderedDate = 'NULL';
        }
        
        if ($isComplete == 't')
        {
            $CompleteDate = 'now()';
        } else {
            $CompleteDate = 'NULL';
        }
        
        if ($isCanceled == 't')
        {
            $CancelDate = 'now()';
        } else {
            $CancelDate = 'NULL';
        }
        
        $sql = "INSERT INTO ds_paymentserviceslog 
                    (
                    id_paymentserviceslog,
                    is_paid,
                    paid_date,
                    is_ordered,
                    ordered_date,
                    is_complete,
                    complite_date,
                    is_canceled,
                    cancel_date,
                    action_name,
                    gc_price,
                    sc_price,
                    info
                    ) 
                VALUES 
                    (
                    $idLog,
                    '$isPaid',
                    $PaidDate,
                    '$isOrdered',
                    $OrderedDate,
                    '$isComplete',
                    $CompleteDate,
                    '$isCanceled',
                    $CancelDate,
                    '$actionName',
                    $GC,
                    $SC,
                    '$info'
                    )";
        
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        //Если игрок связанный с услугой один
        if ($usersArr == 0)
        {
            $sql = "INSERT INTO ds_paymentserviceslog_users 
                    (
                    id_paymentserviceslog,
                    id_user,
                    is_initiator,
                    is_payer
                    ) 
                VALUES 
                    (
                    $idLog,
                    ".self::$user->getId().",
                    't',
                    't'
                    )";

            $common->executeQuery($sql);
            
            assert(empty($common->errormsg));
        } else {
            //проходимся по каждому игроку, связанному с данной услугой
            foreach ($usersArr as $key => $data)
            {
                $sql = "INSERT INTO ds_paymentserviceslog_users 
                    (
                    id_paymentserviceslog,
                    id_user,
                    is_initiator,
                    is_payer
                    ) 
                VALUES 
                    (
                    $idLog,
                    ".$data['id_user'].",
                    '".$data['is_initiator']."',
                    '".$data['is_payer']."'
                    )";

                $common->executeQuery($sql);
                assert(empty($common->errormsg));
            }
        }
        
        return true;
        
    }//END OF METHOD
    
}//END OF CLASS


?>