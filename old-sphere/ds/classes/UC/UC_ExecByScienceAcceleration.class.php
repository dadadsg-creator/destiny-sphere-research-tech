<?php  $CVS_Version = '$Revision: 1.6 $ ; $Date: 2009/10/21 12:58:35 $';


/**
 * Платная услуга по ускорению изучения науки
 *
 */

class UC_ExecByScienceAcceleration extends UC_PaymentServiceBase {

    public function Exec() {

        $_SESSION['dbId'] = 0;

        self::_getParams();

        switch(self::$action)
        {
            case 'show_second_menu':
             self::_ShowSecondMenu();
                break;
            case 'complete':
                if (!self::_ScienceAccelerate()) {
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


    static private function _ShowSecondMenu() {
        //Проверка на гейм даун
        if(GameDown(self::$dbId, self::$worldId)) {
            header('Location: index_down.html');
            exit;
        }
        self::$data['MODE'] = 'BUY_SCIENCE_ACCELERATION';

        $_SESSION['dbId'] = self::$dbId;
        $_SESSION['world'] = self::$worldId;

        $userInstanse = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);

        // Выбираем данные о науке игрока
        $scaeinceData = DS_PaymentService::GetUserScience($userInstanse);

        // Выбираем минимальный уровень науки, который можно ускорить
        $minScienceLevel = DE_WorldSettings::CreateForCurentWorld()->get('SCIENCE_ACCELERATION_MIN_LEVEL');
        assert($minScienceLevel >= 1 && $minScienceLevel <= 149);    
        // В данный момент у нас не изучается наука
        if (count($scaeinceData) == 0) {
            self::$data['NO_RESERCH'] = true;
        } elseif ($scaeinceData[0]['level'] < $minScienceLevel) {
            // Мы не можем ускорять этот уровень науки
            self::$data['MIN_SCIENCE_LEVEL'] = $minScienceLevel;
        } elseif ($scaeinceData[0]['accelerated'] != 0) {
            // У нас наука уже является ускоренной
            self::$data['SCIENCE_ALREADY_ACCELERATED'] = true;
        } else {
            self::$data['NO_RESERCH'] = false;      
            self::$data['RESERCH_LESS_HOUR'] = false;       
            // Выбираем множитель ускорения науки
            $acceletarorMultiplier = DE_WorldSettings::CreateForCurentWorld()->get('SCIENCE_ACCELERATION_MULTIPLIER');
            assert($acceletarorMultiplier >= 0 && $acceletarorMultiplier <= 1); 
            
            // Время, оставшееся у него до окончания изучения науки
            $now = GB_TypeFactory::CreateBase('GB_Date', time());
            $oldEndDate = GB_TypeFactory::CreateBase('GB_Date', $scaeinceData[0]['eventendtime']);
            // Если время изучения уже прошло
            if ($oldEndDate->value - $now->value <= 0) {
                self::$data['NO_RESERCH'] = true;
                return;
            }
            $oldPeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $oldEndDate->value - $now->value);

            //Новое время окончания
            $newEndDTime = $oldEndDate->value - $acceletarorMultiplier*($oldEndDate->value - $now->value);
            $newEndDate = GB_TypeFactory::CreateBase('GB_Date', $newEndDTime);
            $newPeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $newEndDTime - $now->value);
            
            // Множитель цены (на сколько часов ускоряем науку)
            $priceMultiplier = (int)ceil(($oldEndDate->value - $newEndDate->value)/3600);           
            assert($priceMultiplier > 0);

            $_SESSION['dbId'] = 0;
            
            //получаем цену
            $price = DS_PaymentService::GetSciencePrice($scaeinceData[0]['sciencename']);
            assert($price > 0);         
            
            // Умнежоем цену на множитель
            self::$data['PRICE'] = $price*$priceMultiplier;
            self::$data['CREDIT'] = 'SC';
            
            
            //Смотрим достаточно ли денег у игрока
            if(!UC_PaymentServiceMoneyOperation::CheckMoney(self::$user, self::$data['PRICE'], self::$data['CREDIT'], 'UC')){
                self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
                self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';

                return false;
            }
            
            $_SESSION['dbId'] = self::$dbId;
            
            // Формируем время
            $localization = DE_Localization::CreateForCurentLang();     
            $daysSimvol = $localization->get(30003);
            $hoursSimvol = $localization->get(30004);
            $minsSimvol = $localization->get(30005);
            
            // Старое время окончания науки
            $oldDateArr = $oldPeriod->getWithParts();
            self::$data['OLD_RESEARCH_DATE'] = $oldDateArr['DAYS']." ".$daysSimvol." ".$oldDateArr['HOURS']." ".$hoursSimvol." ".$oldDateArr['MINUTES']." ".$minsSimvol;

            // Новое время окончания науки
            $newDateArr = $newPeriod->getWithParts();
            self::$data['NEW_RESEARCH_DATE'] = $newDateArr['DAYS']." ".$daysSimvol." ".$newDateArr['HOURS']." ".$hoursSimvol." ".$newDateArr['MINUTES']." ".$minsSimvol;
            
            // Название науки
            self::$data['RESEARCH_NAME'] = $scaeinceData[0]['text'];
            // Уровень науки
            self::$data['LEVEL'] = $scaeinceData[0]['level'];
        }
        
        self::$data['WORLD_NAME'] = self::$worldName;
        self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;

    }


    static private function _ScienceAccelerate() {
        assert(self::$dbId >= 0);
        assert(self::$worldId > 0);

        $_SESSION['dbId'] = self::$dbId;
        $_SESSION['world'] = self::$worldId;

        $userInstanse = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);

        // Выбираем данные о науке игрока
        $scaeinceData = DS_PaymentService::GetUserScience($userInstanse);
        
        // В данный момент у нас не изучается наука
        if (count($scaeinceData) == 0) {
            self::$data['MSG'] = 'ERROR';
            return false;
        }
         
        // Наука уже ускорена
        if ($scaeinceData[0]['accelerated'] != 0) {
            self::$data['MSG'] = 'ERROR';
            return false;
        } 
        
        // Выбираем множитель ускорения науки
        $acceletarorMultiplier = DE_WorldSettings::CreateForCurentWorld()->get('SCIENCE_ACCELERATION_MULTIPLIER');
        assert($acceletarorMultiplier >= 0 && $acceletarorMultiplier <= 1);

        // Время, оставшееся у него до окончания изучения науки
        $now = GB_TypeFactory::CreateBase('GB_Date', time());
        $oldEndDate = GB_TypeFactory::CreateBase('GB_Date', $scaeinceData[0]['eventendtime']);
        $oldPeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $oldEndDate->value - $now->value);

        //Новое время окончания
        $newEndDTime = $oldEndDate->value - $acceletarorMultiplier*($oldEndDate->value - $now->value);
        $newEndDate = GB_TypeFactory::CreateBase('GB_Date', $newEndDTime);
        $newPeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $newEndDTime - $now->value);

        // Множитель цены (на сколько часов ускоряем науку)
        $priceMultiplier = (int)ceil(($oldEndDate->value - $newEndDate->value)/3600);

        $_SESSION['dbId'] = 0;
        
        //получаем цену
        self::$data['PRICE'] = DS_PaymentService::GetSciencePrice($scaeinceData[0]['sciencename']);         
        // Умнежоем цену на множитель
        self::$data['PRICE'] = self::$data['PRICE']*$priceMultiplier;
        self::$data['CREDIT'] = 'SC';

        // Получаем World Identifier мира (для лога)
        $WI = 'UC';
        
        //Смотрим достаточно ли денег у игрока
        if(!UC_PaymentServiceMoneyOperation::CheckMoney(self::$user, self::$data['PRICE'], self::$data['CREDIT'], $WI))
        {
            return false;
        }

        $_SESSION['dbId'] = self::$dbId;        
        $_SESSION['world'] = self::$worldId;
        
        assert(!empty($WI));
        // Ставим новое время окончания ивента
        DS_PaymentService::SetNewScienceEndDate($userInstanse, $newEndDate->getPgFormat());
        
        // Помечаем ивент как ускоренный
        DS_PaymentService::SetEventAsAccelerated($scaeinceData[0]['id_eventqueue']);
        
        $_SESSION['dbId'] = 0;
    
        //Снимаем деньги
        UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperation(self::$user, self::$data['PS'], self::$data['PRICE'], self::$data['CREDIT'], $WI);

        //Логиреум
        if (!self::_Log('t', 'f', 't', 'f', self::$data['PS'], self::$data['PRICE'], self::$data['CREDIT'], "Science: ".$scaeinceData[0]['text'].". Level: ".$scaeinceData[0]['level'].". World: ".self::$worldName.". Old end date: ".$oldEndDate->getPgFormat().". New end date: ".$newEndDate->getPgFormat()))
        {
            self::$data['MSG'] = 'ERROR';
            return false;
        }
        
        self::$data['WORLD_NAME'] = self::$worldName;
        self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
        
        // Формируем время
        $localization = DE_Localization::CreateForCurentLang();     
        $daysSimvol = $localization->get(30003);
        $hoursSimvol = $localization->get(30004);
        $minsSimvol = $localization->get(30005);
            
        // Старое время окончания науки
        $newDateArr = $newPeriod->getWithParts();
        $newDate = $newDateArr['DAYS']." ".$daysSimvol." ".$newDateArr['HOURS']." ".$hoursSimvol." ".$newDateArr['MINUTES']." ".$minsSimvol;

        //Завершаем транзакцию
        $_SESSION['dbId'] = self::$dbId;
        GB_SingletonObjectStorage::CommitAllCommitable();
        
        ME_EndDBTransactions::Exec();   
        header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&newEndDate=".$newDate."&researchName=".$scaeinceData[0]['text']."&price=".self::$data['PRICE']);
        return true;
    }

    static private function _ShowSuccessful() {
        self::$data['MODE'] = 'SUCCESSFUL';
        self::$data['PRICE'] = self::$data['price'];
        self::$data['NEW_RESEARCH_DATE'] = self::$data['newEndDate'];
        self::$data['RESEARCH_NAME'] = self::$data['researchName'];
    }

    static private function _getParams() {
        if(key_exists('exec_action', self::$data)) {
            self::$action = self::$data['exec_action'];
        }

        if(key_exists('world', self::$data)) {
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