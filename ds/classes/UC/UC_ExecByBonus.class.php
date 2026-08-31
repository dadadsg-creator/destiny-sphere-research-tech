<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/09/11 08:05:53 $';


class UC_ExecByBonus extends UC_PaymentServiceBase
{
    // Количество дней, на которое предоставляется бонус
    static private $days = 14;
    // Тип бонуса
    /*static private $bonusType = array(
    0 => 'military',
    1 => 'resource',
    );  */

    /*static private $resources = array(
    0 => 'STASIS_ENERGY',
    1 => 'FOOD',
    2 => 'KADERIUM',
    3 => 'NANO_CRYSTAL',
    4 => 'PRODIUM'
    );  */

    static private $resources = array(
    'STASIS_ENERGY' => 25,
    'FOOD' => 25,
    'KADERIUM' => 25,
    'NANO_CRYSTAL' => 25,
    'PRODIUM' => 25
    );

    static private $resourcesSingle = array(
    'STASIS_ENERGY' => 25,
    'FOOD' => 25,
    'KADERIUM' => 25,
    'NANO_CRYSTAL' => 25,
    'PRODIUM' => 25
    );

    static private $militaryBonuses = array(
    0 => 'ATTACK',
    1 => 'DEFENCE',
    2 => 'DAMAGE',
    3 => 'HITPOINTS',
    );

    static private $resouce;

    public function Exec()
    {
        $_SESSION['dbId'] = 0;

        //получаем цену
        //self::_GetPrice();

        self::_getParams();

        switch(self::$action)
        {
            case 'index':
                self::_ShowIndex();
                break;

            case 'show_second_menu':
                self::_ShowSecondMenu();
                break;

            case 'by_bonus':
                if (!self::_BuyBonus()) {
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

        //Если мир один выводим запрос на подтвержление удаления
        //Получаем список миров игрока
        $userworlds = self::_getUserWorlds();
        /*
        //Если мир у игрока один
        if(count($userworlds) == 1)
        {
        self::$worldId = (int)$userworlds[0]['id_world'];
        self::$dbId = (int)$userworlds[0]['dbId'];
        self::$worldName = $userworlds[0]['worldname'];

        self::_ShowSecondMenu();
        }
        else{
        */
        //Если миров несколько, то выводим выбор миров
        self::$data['USER_WORLDS'] = $userworlds;
        //      }
    }

    static private function _ShowSecondMenu()
    {
        assert(self::$dbId >= 0);
        assert(self::$worldId > 0);

        if(GameDown(self::$dbId, self::$worldId))
        {
            header('Location: index_down.html');
            exit;
        }


        $_SESSION['dbId'] = self::$dbId;
        // Получам userinstance игрока
        $userInstanse = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);

        //$term = $_REQUEST[];

        switch (self::$data['PS'])
        {
            // Покупка бонуса на отдельный продукт
            case 'PS_BY_BONUS_RESOURCE_SINGLE':

                // Проходимся по каждому ресурсу
                foreach (self::$resourcesSingle as $resName => $persent)
                {
                    // Получаем название ресурса
                    self::$data['RES_BONUSES'][$resName]['NAME'] = DE_Resource::CreateFromName($resName)->getLocolizedName();
                    self::$data['RES_BONUSES'][$resName]['PERSENT'] = $persent;

                    $_SESSION['dbId'] = 0;

                    // Получаем стоимомсть услуги

                    if (self::$days > 14)
                    $service_name = 'PS_BY_BONUS_'.$resName."_".self::$days;
                    else
                    $service_name = 'PS_BY_BONUS_'.$resName;

                    $actionType = DE_MoneyActionType::CreateFromName($service_name);
                    $MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
                    $price = $MoneyActionType->getAmount();
                    $credit = $MoneyActionType->getCreditName();

                    self::$data['RES_BONUSES'][$resName]['PRICE'] = $price;
                    self::$data['RES_BONUSES'][$resName]['CREDIT'] = $credit;

                    $_SESSION['dbId'] = self::$dbId;

                    // Проверяем наличие действующего бонуса у игрока
                    /*
                    if (DS_Bonus::IsUserHasBonuser($userInstanse, $resName)) {
                        // Помечаем, что у игрока уже есть этот бонус
                        self::$data['RES_BONUSES'][$resName]['ALREADY_HAS'] = 1;
                    } else {
                        self::$data['RES_BONUSES'][$resName]['ALREADY_HAS'] = 0;
                    }
                    */
                    self::$data['RES_BONUSES'][$resName]['ALREADY_HAS'] = 0;
                }

                self::$data['MODE'] = 'BY_BONUS_SINGLE_SECOND_MENU';
                break;

                // Покупка пакета бонусов
            case 'PS_BY_BONUS_RESOURCE':

                //получаем цену
                //self::_GetPrice();
                if (self::$days > 14)
                $service_name = 'PS_BY_BONUS_RESOURCE_'.self::$days;
                else
                $service_name = 'PS_BY_BONUS_RESOURCE';
                //echo "service_name=$service_name";
                $actionType = DE_MoneyActionType::CreateFromName($service_name);
                $MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);

                self::$data['PRICE'] = $MoneyActionType->getAmount();
                self::$data['CREDIT'] = $MoneyActionType->getCreditName();

                //Смотрим достаточно ли денег у игрока
                if (!self::_CheckEnoughMoney())
                {
                    return false;
                }

                $_SESSION['dbId'] = self::$dbId;
                self::$data['MODE'] = 'BY_BONUS_SECOND_MENU';

                // Проходимся по каждому ресурсу
                foreach (self::$resources as $resName => $persent)
                {
                    // Получаем название ресурса
                    self::$data['RES_BONUSES'][DE_Resource::CreateFromName($resName)->getLocolizedName()] = $persent;

                    // Проверяем наличие действующего бонуса у игрока
                    self::$data['ALREADY_HAS'] = 0;
                    /*
                    if (DS_Bonus::IsUserHasBonuser($userInstanse, $resName)) {
                        self::$data['ALREADY_HAS'] = 1;
                        // Если у игрока есть бонус хотябы на один ресурс, дальше не проверяем
                        break;
                    }
                    */
                }
                break;

                // Военные бонусы
            case 'PS_BY_BONUS_MILITARY':
                break;

            default:
                return false;
        }

        self::$data['DAYS'] = self::$days;

        self::$data['EXEC_ACTION'] = 'by_bonus';

        self::$data['WORLD_NAME'] = self::$worldName;
        self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
    }

    static private function _BuyBonus()
    {
        assert(self::$dbId >= 0);
        assert(self::$worldId > 0);

        // Получаем еинстанс игрока
        $_SESSION['dbId'] = self::$dbId;
        $userInstanse = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);
        // Получаем World Identifier мира (для лога)
        $WI = DE_World::CreateFromID(self::$worldId)->getShortName();
        assert(!empty($WI));

        $_SESSION['dbId'] = 0;

        $bonuses = '';
        $detail = '';

        switch (self::$data['PS'])
        {
            // Покупка бонуса на отдельный продукт
            case 'PS_BY_BONUS_RESOURCE_SINGLE':
                if (empty(self::$resouce)) {
                    return false;
                }

                //$action = 'PS_BY_BONUS_'.self::$resouce;

                if (self::$days > 14)
                $service_name = 'PS_BY_BONUS_'.self::$resouce."_".self::$days;
                else
                $service_name = 'PS_BY_BONUS_'.self::$resouce;

                $resName = DE_Resource::CreateFromName(self::$resouce)->getLocolizedName();

                $actionType = DE_MoneyActionType::CreateFromName($service_name);

                $MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
                self::$data['PRICE'] = $MoneyActionType->getAmount();
                self::$data['CREDIT'] = $MoneyActionType->getCreditName();
                $bonuses = ", ".self::$resourcesSingle[self::$resouce].'% '.$resName;

                // Массив с данными о ресе
                $resourcesArr[self::$resouce] = self::$resourcesSingle[self::$resouce];


                self::$data['PS'] = $service_name;
                //Смотрим достаточно ли денег у игрока
                if (!self::_CheckEnoughMoney())
                {
                    self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
                    return false;
                }
                self::$data['PS'] = 'PS_BY_BONUS_RESOURCE_SINGLE';

                // Для показа странички об удачной покупке бонусов
                $detail = '&resource='.$resName.'&persent='.self::$resourcesSingle[self::$resouce];

                $_SESSION['dbId'] = self::$dbId;
                // Проверяем, чтобы у игрока не было бонуса на этот ресурс
                /*
                if (DS_Bonus::IsUserHasBonuser($userInstanse, $resName)) {
                    return false;
                }
                */
                break;

                // Покупка пакета бонусов
            case 'PS_BY_BONUS_RESOURCE':
                //Смотрим достаточно ли денег у игрока

                //получаем цену
                //self::_GetPrice();
                if (self::$days > 14)
                $service_name = 'PS_BY_BONUS_RESOURCE_'.self::$days;
                else
                $service_name = 'PS_BY_BONUS_RESOURCE';

                self::$data['PS'] = $service_name;
                if (!self::_CheckEnoughMoney())
                {
                    return false;
                }

                //echo "service_name=$service_name";
                $actionType = DE_MoneyActionType::CreateFromName($service_name);
                $MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);

                self::$data['PRICE'] = $MoneyActionType->getAmount();
                self::$data['CREDIT'] = $MoneyActionType->getCreditName();

                $resourcesArr = self::$resources;

                // Проверяем, чтобы у игрока не было бонуса ни на один ресурс
                $_SESSION['dbId'] = self::$dbId;

                // Проверяем наличие действующего бонуса у игрока
                /*
                if (DS_Bonus::IsUserHasBonuser($userInstanse)) {
                    return false;
                }
                */
                break;

                // Военные бонусы
            case 'PS_BY_BONUS_MILITARY':
                break;

            default:
                return false;
        }


        // Добавляем игроку ресурс
        DS_Bonus::IsertUserBonus($userInstanse, self::$days, $resourcesArr);

        $_SESSION['dbId'] = 0;

        //Снимаем деньги
        UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user,  $service_name, 1, $WI);

        //Логиреум
        if (!self::_Log('t', 'f', 't', 'f', $service_name, self::$data['PRICE'], self::$data['CREDIT'], "World: ".self::$worldName.$bonuses))
        {
            self::$data['MSG'] = 'ERROR';
            return false;
        }

        //Завершаем транзакцию
        $_SESSION['dbId'] = self::$dbId;
        GB_SingletonObjectStorage::CommitAllCommitable();

        ME_EndDBTransactions::Exec();
        header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&days=".self::$days."&worldname=".self::$worldName.'&'.$detail);
        return true;
    }

    private static function _ShowSuccessful ()
    {
        self::$data['MODE'] = 'SUCCESSFUL';

        self::$data['WORLD_NAME'] = $_REQUEST['worldname'];

        if (self::$data['PS'] == 'PS_BY_BONUS_RESOURCE_SINGLE')
        {
            self::$data['RESOURCE'] = $_REQUEST['resource'];
            self::$data['PERSENT'] = $_REQUEST['persent'];
        }
    }

    static private function _getParams()
    {

        if(key_exists('exec_action', self::$data))
        {
            self::$action = self::$data['exec_action'];
        }

        if(key_exists('resource', self::$data))
        {
            self::$resouce = self::$data['resource'];
        }

        if(key_exists('days', self::$data))
        {
            self::$days = intval(self::$data['days']);

            assert(in_array(self::$days, array(14, 30, 90, 180, 360)));
        }
        else
        {
            self::$days = 14;
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