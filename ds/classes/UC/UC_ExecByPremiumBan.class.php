<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2009/07/02 07:47:30 $';


class UC_ExecByPremiumBan extends UC_PaymentServiceBase
{
    static private $countOfDays = 0;

    static private $maxCountOfDays = 14;
    
    static private $minCountOfDays = 1;

    public function Exec()
    {
        self::_getParams();

        $_SESSION['dbId'] = 0;

        //получаем цену
        self::_GetPrice();
        
        if (self::$data['PS'] == 'PS_BY_PREMIUM_BAN_30') {
            self::$maxCountOfDays = 30; 
            self::$minCountOfDays = 15; 
        } 

        switch(self::$action)
        {
            case 'add_pmemium_ban':
                if (!self::_AddPremiumBan())
                {
                    self::_ShowIndex();
                }
                break;

            case 'pmemium_ban_confirm':
                if (!self::_AddPremiumBanConfirm())
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
        self::$data['MODE'] = 'BY_PREMIUM_BAN_INDEX';
        self::$data['EXEC_ACTION'] = 'pmemium_ban_confirm';

        // Смотрим, нет ли у игрока уже премиум-бана
        $userBan = self::$user->getPremiumBan();

        // Если у игрока есть премиум бан
        if($userBan->getStartDate()->value - $userBan->getEndDate()->value != 1)
        {
            // Если он еще не закончился
            if($userBan->getEndDate()->value > time())
            {
                // Если он еще не начался
                if ($userBan->getStartDate()->value > time()) {
                    // Время до активации
                    $now = GB_TypeFactory::CreateBase('GB_Date', time());
                    $period = GB_TypeFactory::CreateBase('GB_TimePeriod', $userBan->getStartDate()->value - $now->value);
                    self::$data['TIME_TO_ACTIVATION'] = $period->getDSFormat();
                } else {
                    // Есди он во всю идет
                    $now = GB_TypeFactory::CreateBase('GB_Date', time());
                    $period = GB_TypeFactory::CreateBase('GB_TimePeriod', $userBan->getEndDate()->value - $now->value);
                    self::$data['TIME_TO_END_BAN'] = $period->getDSFormat();
                }

                self::$data['ALREADY_HAVE'] = true;
            } else {
                // Смотрим, прошло ли 3 месяца с мометна начала последнего премиум бана
                // Если игрок покупал бан (а не банили с админки)
                if (self::$user->getPremiumBanComment() == 'cabinet') {
                    if ($userBan->getStartDate()->value > strtotime("-45 day")) {
                        self::$data['MOUNTH_DELAY'] = true;
                        // Время до того момента, когда игрок сможет заказать услугу снова

                        //$start = GB_TypeFactory::CreateBase('GB_Date', strtotime("-3 month"));
                        //$end = GB_TypeFactory::CreateBase('GB_Date', $userBan->getStartDate()->value);
                        //$period = GB_TypeFactory::CreateBase('GB_TimePeriod', $end->value - $start->value);

                        $period = GB_TypeFactory::CreateBase('GB_TimePeriod', $userBan->getStartDate()->value - strtotime("-3 month"));
                        self::$data['TIME_TO_NEXT_ORDER'] = $period->getDSFormat();
                    }
                }


            }
        }
    }

    static private function _AddPremiumBanConfirm()
    {
        //Смотрим достаточно ли денег у игрока
        if (!self::_CheckEnoughMoney())
        {
            return false;
        }

        // Проверяем корректность введенных данных
        assert(is_int(self::$countOfDays));
        if (self::$countOfDays < self::$minCountOfDays || self::$countOfDays > self::$maxCountOfDays) {
            self::$data['MSG'] = 'WRONG_COUNT_OF_DAYS';
            return false;
        }

        self::$data['COUNT_OD_DAYS'] = self::$countOfDays;

        self::$data['MODE'] = 'BY_PREMIUM_BAN_CONFIM';
        self::$data['EXEC_ACTION'] = 'add_pmemium_ban';

        return true;
    }


    static private function _AddPremiumBan()
    {
        $_SESSION['dbId'] = 0;
        
        //Смотрим достаточно ли денег у игрока
        if (!self::_CheckEnoughMoney())
        {
            return false;
        }

        // Проверяем, нет ли у игрока уже незаконченного премиум бана
        $userBan = self::$user->getPremiumBan();
        if(count($userBan) > 0 && $userBan->getEndDate()->value > time())
        {
            return false;
        }

        // Проверяем введенное количество дней
        assert(is_int(self::$countOfDays));
        if (self::$countOfDays < self::$minCountOfDays || self::$countOfDays > self::$maxCountOfDays) {
            self::$data['MSG'] = 'WRONG_COUNT_OF_DAYS';
            return false;
        }

        // Формируем дату
        $startDate = GB_TypeFactory::CreateBase('GB_Date', strtotime("+12 hours"));
        $endDate = GB_TypeFactory::CreateBase('GB_Date', strtotime("+".self::$countOfDays." days 12 hours"));

        $comments = 'cabinet';

        // Устанавливем бан
        $period = new GB_DatePeriod($startDate, $endDate);
        self::$user->setPremiumBan($period, $comments);

        //Снимаем деньги
        UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, self::$data['PS']);

        //Логиреум
        if (!self::_Log('t', 'f', 't', 'f', self::$data['PS'], self::$data['PRICE'], self::$data['CREDIT'], 'DAYS: '.self::$countOfDays))
        {
            self::$data['MSG'] = 'ERROR';
            return false;
        }

        
        // Проходим по всем мирам, где зареган игрок
        $userworlds = self::_GetUserWorlds();
        foreach($userworlds as $k => $world)
        {
            $_SESSION['dbId'] = (int)$world['dbId'];
            $_SESSION['world'] = (int)$world['id_world'];

            //Проверка на гейм даун
            if(GameDown($_SESSION['dbId'], $_SESSION['world'])) {
                continue;
            }

            // Получаем все соты игрока
            $usersCombs = self::_GetCombIdByUID($_SESSION['dbId'], $_SESSION['world']);

            foreach ($usersCombs as $kcomb => $comb)
            {
                $ringId = (int)$comb['id_ring'];
                $combComplexId = (int)$comb['id_combcomplex'];

                // Сбрасываем кеши комплекса
                UA_ComplexView::ResetCache($_SESSION['world'], $ringId, $combComplexId);
            }
        }
        
        $_SESSION['dbId'] = 0;

        // Коммитим
        GB_SingletonObjectStorage::CommitAllCommitable();

        // Завершаем транзакцию
        ME_EndDBTransactions::Exec();
        header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&days=".self::$countOfDays);
        return true;
    }

    static private function _GetCombIdByUID($dbId, $worldId)
    {
        $common = self::getCommon();

        $sql = "
            SELECT
                c.id_comb,
                ui.id_userinstance,
                r.id_ring,
                cc.id_combcomplex
            FROM
                ds_comb AS c
                INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = c.id_userinstance)
            WHERE
                ui.id_user = ".self::$user->getID()."
                AND
                r.id_world = ".$worldId."
            order by r.ringnumber, cc.complexnumber, c.combnumber
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }

    private static function _ShowSuccessful()
    {
        self::$data['MODE'] = 'SUCCESSFUL';
        self::$data['COUNT_OF_DAYS'] = self::$data['days'];
    }

    static private function _getParams()
    {
        if(key_exists('exec_action', self::$data))
        {
            self::$action = self::$data['exec_action'];
        }

        if(key_exists('count_of_days', self::$data))
        {
            self::$countOfDays = (int)self::$data['count_of_days'];
        }
    }
}

?>