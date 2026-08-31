<?php $CVS_Version = '$Revision: 1.9 $ ; $Date: 2011/04/22 15:26:38 $';
/**
 * Рассчет производства и потрбеления н соте.
 * Умирани юнитов и деактивация зданий.
 *
 */
class ME_TurnCalc
{
    /**
     * Дато до которой обсчет для внешнего рефреша
     *
     * @var GB_Date
     */
    static private $checkDate;

    /**
     * Объект соты которую рефрешим
     *
     * @var DE_Comb
     */
    static private $comb;

    /**
     * Атррибуты рассы
     *
     * @var DE_RaceAttributes
     */
    static private $raceAttributes;

    /**
     * Атрибуты соты
     *
     * @var DE_CombAttributes
     */
    static private $combAttributes;

    /**
     * ОБъект инстанса
     *
     * @var DE_UserInstance
     */
    static private $userInstance;

    /**
     * Объект с настройками мира
     *
     * @var DE_WorldSettings
     */
    static private $worldSettings;

    /**
     * Колличество ходов прошедшее с полденегор рефршеа игрока
     *
     * @var integer
     */
    static private $turns;

    static public function Count(DE_Comb $comb, $checkDate = null)
    {

        assert(is_null($checkDate) || $checkDate instanceof GB_Date);

        self::$userInstance = $comb->getUserInstance();

        self::$comb = $comb;

        self::$combAttributes = self::$comb->getAttributes();

        self::$raceAttributes = DE_RaceAttributes::CreateFromRaceID(self::$userInstance->getRaceID());

        self::$worldSettings = DE_WorldSettings::CreateForCurentWorld();

        self::_lockComb();

        //Проверям не находитьс яли пользовтаель в режиме отпуска
        if(GL_IsUserInVocationMode::Test(self::$userInstance))
        {
            return true;
        }

        if(DE_PowerShild::CreateFromUserInstance(self::$userInstance->getID())->isWorks())
        {
            return true;
        }

        if(is_null($checkDate))
        {
            self::$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
        }else{
            self::$checkDate = $checkDate;
        }

        //Рассчитываем колличество турнов с поледнего рефршеа
        self::_countTurns();

        //Если не на турнов не накопилось то уходим нафиг
        if(self::$turns == 0)
        {
            return true;
        }

        //Рачитываем рост рабочих
        self::_countWorkersBorn();

        //производство ресурсов
        self::_countResourceProduction();

        // потребление энергии зданиями
        self::_countBuildingsUpkeep();

        // потребление еды и убиение юнитов если еды недостаточно
        self::_countHumanUpkeep();

        //Расчет апкипа на жисть юнитов
        self::_countArmyUpkeep();

        return true;
    }

    static private function _lockComb()
    {
        assert(self::$comb instanceof DE_Comb);

        //Лочим обсчет соты
        if(!SU_Locker::CerateForCurrentDB('USERACTION')->lockWithTest(self::$comb->getUserInstance()->getID()))
        {
            DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), self::$comb->getID(), 'TURN_CALC');
        }
    }

    /**
     * Рассчет колличетсво ходов прошедшее с полденего рефреша
     *
     */
    static private function _countTurns()
    {
        self::$turns = (float)(
        (self::$checkDate->value -
        self::$userInstance->getLastRefresh()->value) /
        (self::$worldSettings->get('TURN_TIME') * 60));

        if(self::$turns < 0)
        {
            self::$turns = 0;
        }
    }

    /**
     * РАсчет проихзводства рабочих
     *
     */
    static private function _countWorkersBorn()
    {
        //максимальное кол-во рабочих
        $max = (int)self::$combAttributes->getAttribute('MAX_AMOUNT', 'WORKER');
        $current = (int)self::$combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER');

        //Если уже рабочих и так максимум то ненадо считать их естественный прирост
        if(!($max == $current))
        {
            if(self::$raceAttributes->haveAttribute('BONUS_RELATIVE', 'WORKER'))
            {
                $bonus = self::$raceAttributes->getAttribute('BONUS_RELATIVE', 'WORKER');
            }else{
                $bonus = 0;
            }

            $growthTMP = $max * self::$worldSettings->get('WORKER_GROWTH_RATE') * self::$turns;
            $growth = $growthTMP + ($growthTMP * $bonus);
            if (self::$userInstance->buildSpeedupEndTime > time()) $growth *= 2;

            if(($growth + $current) <= $max)
            {
                $work = $growth;
            }else{
                $work = $max - $current;
            }

            //добавляем рабочих
            self::$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'WORKER', (float)$work);
        }
    }

    /**
     * Рассчет производства ресурсов
     *
     */
    static private function _countResourceProduction()
    {
        $attributeNames = self::$combAttributes->getAttributeNamesByTypeName('CURRENT_AMOUNT');

        foreach($attributeNames as $k=>$attributeName)
        {

            if(!self::$combAttributes->haveAttribute('TURN_PRODUCTION', $attributeName))
            {
                continue;
            }

            $turnProduction = GL_CountCombProductionOfResource::Count(self::$comb, $attributeName);

            if($turnProduction <= 0)
            {
                continue;
            }

            $produced = (float)round($turnProduction * self::$turns);

            self::$combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, $produced);
        }
    }

    /**
     * Рассчет полтрбления зданиями
     *
     */
    static private function _countBuildingsUpkeep()
    {
        foreach (array('KADERIUM','NANO_CRYSTAL','STASIS_ENERGY','FOOD','PRODIUM') as $resource) {
            $energyUpkeep = self::$combAttributes->getAttribute('TURN_UPKEEP', $resource) * self::$turns;
            if (!$energyUpkeep) continue;
            $energyCurrentAmount = self::$combAttributes->getAttribute('CURRENT_AMOUNT', $resource);

            if($energyCurrentAmount >= $energyUpkeep)
            {
                self::$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resource, -(float)$energyUpkeep);
            }else{
                //Если не хватате энергии то деактивируем здания
                self::$combAttributes->setAttribute('CURRENT_AMOUNT', $resource, (float)0);

                //Если сейчас убисто выключенно то ничего не делаем
                if($resource!='STASIS_ENERGY' || DE_WorldSettings::CreateForCurentWorld()->get('DISABLE_UPKEEP_DEACTIVATE_BUILDINGS'))
                {
                    return true;
                }else{
                    //Деактивирем здания
                    ME_DeactivateBuildingsForUpkeep::Exec(self::$comb, $energyUpkeep, $energyCurrentAmount, self::$turns);
                }
            }
        }
    }//END OF METHOD

    /**
     * Считаем потребеление еды населением
     *
     */
    static private function _countHumanUpkeep()
    {
        // общее кол-во
        $combUnitsCount =   self::$combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER') +
        self::$combAttributes->getAttribute('CURRENT_AMOUNT', 'SCIENTIST') +
        self::$combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST') +
        self::$combAttributes->getAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST');

        // среднее значение потребления
        $unitFoodUpkeep = DE_Unit::CreateFromName('WORKER')->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD');

        // текущее кол-во еды
        $haveFood = self::$combAttributes->getAttribute('CURRENT_AMOUNT', 'FOOD');

        //Надо еды
        $needFood = $combUnitsCount * $unitFoodUpkeep * self::$turns;

        if($haveFood >= $needFood)
        {// еды достаточно
            self::$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'FOOD', -(float)$needFood);
        }else{ // еды недостаточно

            self::$combAttributes->setAttribute('CURRENT_AMOUNT', 'FOOD', (float)0);

            if(DE_WorldSettings::CreateForCurentWorld()->get('DISABLE_UPKEEP_DEATH_HUMANS'))
            {
                return true;
            }

            $killPart = ME_KillHumansForUpkeep::Exec(self::$comb, $needFood, $haveFood, $unitFoodUpkeep, (int)$combUnitsCount);

            $messparams[0] = "{b} ".self::$comb->getName()."{/b}";

            // Проходимся по всей убитой челяди
            foreach ($killPart as $name => $amount)
            {
                if ($amount == 0) {
                    continue;
                }

                $messparams[1] = "{b} ".$amount."{/b}";
                $messparams[2] = "{b} ".DE_Unit::CreateFromName($name)->getLocalizedName()."{/b}";
                DE_SystemMessages::AddMessageFromAction(__CLASS__, "TURN_CALC_MESS1", $messparams, 0, 'RED', self::$userInstance->getID());
            }

        }
    }

    /**
     * Рассчитываем апкип армии
     *
     * @return unknown
     */
    static private function _countArmyUpkeep()
    {
        $upkeepPoints = DE_LiveUpkeepPoints::GetInstance()->flushPointsForComb(self::$comb);

        if(!$upkeepPoints)
        {
            return true;
        }

        $needResorces = ME_CountPeriodLiveUpkeep::Count($upkeepPoints, self::$checkDate);

        $haveResources = array();
        $notEnoughtResorcesFlag = false;

        //ВЫясняем нехвататет ли нам чего нибудь.
        foreach(GB_ResourcesList::$allResources as $k => $resourceName)
        {
            $haveResources[$resourceName] = self::$combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);

            if(key_exists($resourceName, $needResorces) && $haveResources[$resourceName] < $needResorces[$resourceName])
            {
                $notEnoughtResorcesFlag = true;
            }
        }

        if($notEnoughtResorcesFlag)
        {
            //Если выключенно помираниеюнитов то выходим
            if(DE_WorldSettings::CreateForCurentWorld()->get('DISABLE_UPKEEP_DEATH_UNITS'))
            {
                foreach($needResorces as $resourceName => $value)
                {
                    self::$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -(float)$value);

                    if(self::$combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName) < 0)
                    {
                        self::$combAttributes->setAttribute('CURRENT_AMOUNT', $resourceName, (float)0);
                    }
                }
                return true;
            }

            //Если нехватила мочим войска на мясо и энергию и прочие ресурсы.
            ME_KillArmysForUpkeep2::Exec(self::$comb, $needResorces, $haveResources, self::$turns);

            $returnResources = ME_KillArmysForUpkeep2::GetReturnResorces();
            $killPart = ME_KillArmysForUpkeep2::GetDeadUnits();
//SU_DebugTools::LogAll($killPart, 'AnnitaUpkeep.log');

            //Заносим на соту что нужно вычесть и добавить
            foreach($returnResources as $resource => $amount)
            {
                self::$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resource, (float)$amount);
            }

            //На соте % из-за нехватки ресурсов погибло % юнитов %.
            $messparams[0] = "{b} ".self::$comb->getName()."{/b}";
            foreach ($killPart as $unitName => $amount)
            {
                if ($amount == 0) {
                    continue;
                }
                
                $messparams[1] = "{b} ".$amount."{/b}";
                $messparams[2] = "{b} ".$unitName."{/b}";
                DE_SystemMessages::AddMessageFromAction(__CLASS__, "UPKEEP_ARMY_LOOSES", $messparams, 0, 'RED', self::$userInstance->getID());
            }

        }else{
            //если всего хватило то просто вычитам ресурсы.
            foreach($needResorces as $resourceName => $value)
            {
                self::$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -(float)$value);
                assert(self::$combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName) >= 0);
            }
        }
    }
}

?>
?>