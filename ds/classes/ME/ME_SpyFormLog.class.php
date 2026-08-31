<?php

/**
 * Генерация логов разведки
 * 
 * @todo потенциально проблемы с видимостью данных не на время разведки
 *      типа зонды официально прилетели день назад, а в логах могут быть данные на момент рефреша
 */
class ME_SpyFormLog
{
    /**
     * Разведгруппа
     *
     * @var DE_SpyZondGroup
     */
    static private $researchGroup;

    /**
     * Сота, которую разведывали
     *
     * @var DE_Comb
     */
    static private $battleComb;

    /**
     * Результат боя
     * array('ROUND' => 'в каком раунде закончился бой', 'WINNER' => 'победитель A | D', 'REASON' => 'причина окончания боя')
     *
     * @var array
     */
    static private $battleResult;

    /**
     * Логи
     *
     * array('A' => 'лог атаковавшего', 'D' => 'лог защищающегося');
     * 
     * @var array
     */
    static private $log;

    /**
     * Аккумулятор для сбора данных о юнит группах
     * нужно для перемешивания реальных и фейковых групп
     *
     * @var array
     */
    static private $researchLogAccum = array();

    /**
     * Контрольное время, до которого сейчас обновляем игрока
     *
     * @var GB_Date
     */
    static private $checkDate;


    /**
     * Инициялизация начальных данных
     *
     * @param DE_SpyZondGroup $researchGroup
     * @param DE_Comb $battleComb
     * @param array $battleResult
     */
    static public function Init(DE_SpyZondGroup $researchGroup, DE_Comb $battleComb, array $battleResult, GB_Date $checkDate)
    {
        assert(key_exists('ROUND', $battleResult));
        assert(key_exists('WINNER', $battleResult));
        assert(key_exists('REASON', $battleResult));

        self::$researchGroup = $researchGroup;
        self::$battleComb = $battleComb;
        self::$battleResult = $battleResult;
        self::$checkDate = $checkDate;

        self::$log = array('A' => array(), 'D' => array());
        self::$researchLogAccum = array();
    }


    /**
     * Генерация лога для атакующего
     *
     */
    static public function FormAttackerLog()
    {
        switch (self::$researchGroup->getUnit()->getName())
        {
            // зонд скаут
            case 'SPY_ZOND_SCOUT':
                return self::_formLogForScoutZond();
                break;

                // зонд исследователь
            case 'SPY_ZOND_EXPLORER':
                return self::_formLogForExplorerZond();
                break;

            default:
                DS_Error('Unknown spy unit in research event');
                break;
        }
    }


    /**
     * Лог разведки, который получает зонд исследователь
     *      информация о ресурсах, защитниках и атаках на соту
     *      
     *
     * @return unknown
     */
    static private function _formLogForExplorerZond()
    {
        // заголовок лога
        self::_addAttackerHeader();

        // описание своих потерь
        self::_addSideLosesToLog('A', 'A', true);
        self::_addSideLosesToLog('D', 'A', false);
        // информация на сколько точный лог получен
        if(self::$battleResult['WINNER'] == 'A')
        {
            $accuracy = 3;
        } else {
            $accuracy = self::$battleResult['ROUND'];
        }
        self::_addAttackerLogAccuracy($accuracy);
        self::_addMessageToLog('A', '');

        // ресурсы на соте
        self::_getResourcesOnComb('CURRENT_AMOUNT');

        // производство ресурсов
        self::_getResourcesOnComb('TURN_PRODUCTION');

        // список игроков на защите
        self::_getDefenderList();

        // список атак на соту
        self::_getAttacksOnComb();

        return implode("\n", self::$log['A']);
    }


    /**
     * Лог разведки, который получает зонд скаут
     *      информация о войсках на соте
     *
     * @return unknown
     */
    static private function _formLogForScoutZond()
    {
        // определяем какой точности лог
        $accuracy = GL_SpyLogAccuracy::GetForAttacker(self::$battleResult);

        // заголовок лога
        self::_addAttackerHeader();

        // лог не пустой
        if($accuracy > GL_SpyLogAccuracy::EMPTY_LOG)
        {
            // описание своих потерь
            self::_addSideLosesToLog('A', 'A', true);

            // лог средней точности или полный
            if($accuracy > GL_SpyLogAccuracy::LOW)
            {
                // потери зондов защитника
                self::_addSideLosesToLog('D', 'A');
            }

            // информация на сколько точный лог получен
            self::_addAttackerLogAccuracy($accuracy);

            // сведения об армии, с определенной точностью
            self::_addArmyInfo($accuracy);
        } else {
            self::_addMessageToLog('A', self::_getAccuracyLocalization(GL_SpyLogAccuracy::EMPTY_LOG));
        }

        return implode("\n", self::$log['A']);
    }

    /**
     * Генерация лога для защитника
     *
     */
    static public function FormDefenderLog()
    {
        // определяем какой точности лог
        $accuracy = GL_SpyLogAccuracy::GetForDefender(self::$battleResult);

        // лог не пустой
        if($accuracy > GL_SpyLogAccuracy::EMPTY_LOG)
        {
            // заголовок
            self::_addDefenderHeader();

            // описание своих потерь
            self::_addSideLosesToLog('D', 'D', true);
        }

        if($accuracy > GL_SpyLogAccuracy::LOW)
        {
            // описание потерь противника
            self::_addSideLosesToLog('A', 'D');

            if($accuracy == GL_SpyLogAccuracy::MIDDLE)
            {
                // имя игрока, который прилетал
                self::_addAttackerInfo();
            } else {
                // имя и сота игрока, который прилетал
                self::_addAttackerInfo(true);
            }
        }

        return implode("\n", self::$log['D']);
    }

    /**
     * Списки кол-ва или производства ресурсов на соте 
     *
     * @param string $type 'CURRENT_AMOUNT' или 'TURN_PRODUCTION'
     */
    static private function _getResourcesOnComb($type)
    {
        assert($type == 'CURRENT_AMOUNT' || $type == 'TURN_PRODUCTION');
        $withBonus = false;

        if(DS_Science::GetSpyScienceBranchLevel(self::$researchGroup->getCombFrom()->getUserInstanceID()) >=
        DE_WorldSettings::CreateForCurentWorld()->get('SPY_SCIENCE_LEVEL_FOR_DETAILED_INFO'))
        {
            $withBonus = true;
        }

        if($type == 'CURRENT_AMOUNT')
        {
            self::_addMessageToLog('A', 'SPY_RESOURCES_ON_COMB', true);
        } else {
            if($withBonus)
            {
                self::_addMessageToLog('A', 'SPY_RESOURCES_PRODUCTION_ON_COMB_WITH_BONUS', true);
            } else {
                self::_addMessageToLog('A', 'SPY_RESOURCES_PRODUCTION_ON_COMB', true);
            }
        }

        $combAttributes = self::$battleComb->getAttributes();

        if(self::$battleResult['WINNER'] == 'A')
        {
            $accuracy = 3;
        } else {
            $accuracy = self::$battleResult['ROUND'];
        }

        foreach (GB_ResourcesList::$allResources as $k => $resourceName)
        {
            if($type == 'TURN_PRODUCTION' && $withBonus)
            {
                $currentAmount = GL_CountCombProductionOfResource::Count(self::$battleComb, $resourceName);
            } else {
                $currentAmount = $combAttributes->getAttribute($type, $resourceName);
            }

            $resource = DE_Resource::CreateFromName($resourceName);
            $resourcName = DE_Localization::CreateFromLangID(self::$researchGroup->getCombFrom()->getUserInstance()->getUser()->getLanguageID())->get($resource->getLocolizedId());

            self::_addMessageToLog('A', $resourcName.': '.GL_SpyLogResourceCountData::Get($resource, $type, $currentAmount, $accuracy));
        }

        self::_addMessageToLog('A', '');
    }

    /**
     * Список игроков, защищающих разведываемую соту
     *
     */
    static private function _getDefenderList()
    {
        $minScienceLevelForDetails = (int)DE_WorldSettings::CreateForCurentWorld()->get('SPY_SCIENCE_LEVEL_FOR_DETAILED_INFO');

        // если уровень науки "шпионаж" у разведчика ниже определенного значения, то не будет этих данных
        if(DS_Science::GetSpyScienceBranchLevel(self::$researchGroup->getCombFrom()->getUserInstanceID()) < $minScienceLevelForDetails)
        {
            $params = array();
            $params[0] = $minScienceLevelForDetails;
            self::_addMessageToLog('A', 'SPY_LOG_NOT_ENOUGTH_SCIENCE_FOR_DETAILS', true, $params);
            return;
        }

        $defendArmiesCollection = self::$battleComb->GetArmysOnCombDefendCollection();

        $info = array();

        while ($army = $defendArmiesCollection->next())
        {
            if($army->getUserInstance()->getID() != self::$battleComb->getUserInstanceID())
            {
                $info[] = array(1 => $army->getName(), 2 => $army->getUserInstance()->getUser()->getName(), 3 => $army->getBaseComb()->getFullName());
            }
        }

        if(self::$battleResult['WINNER'] == 'A')
        {
            $accuracy = 3;
        } else {
            $accuracy = self::$battleResult['ROUND'];
        }

        $params = array();
        // запись в логе
        if(!empty($info))
        {
            // защитники есть
            // Язык разведчика
            $params[0] = DE_Localization::CreateFromLangID(self::$researchGroup->getCombFrom()->getUserInstance()->getUser()->getLanguageID())->get(31173);
            //$params[0] = DE_Localization::CreateForCurentLang()->get(31173);
            if($accuracy > 1)
            {
                // их кол-во
                $params[0] .= ' ('.count($info).')';
            }
        } else {
            // защитников нет
            // Язык разведчика
            $params[0] = DE_Localization::CreateFromLangID(self::$researchGroup->getCombFrom()->getUserInstance()->getUser()->getLanguageID())->get(31172);
            //$params[0] = DE_Localization::CreateForCurentLang()->get(31172);
        }

        self::_addMessageToLog('A', 'SPY_LOG_DEFEND_ARMIES', true, $params);

        // полная информация
        if($accuracy > 2)
        {
            foreach ($info as $k => $i)
            {
                $i[0] = $k + 1;
                self::_addMessageToLog('A', 'SPY_LOG_INACTION_ARMY_INFO', true, $i);
            }
        }

        self::_addMessageToLog('A', '');
    }

    /**
     * Список армий, атакующих разведываемую соту
     *      все аналогично списку защитников
     *
     */
    static private function _getAttacksOnComb()
    {
        // если уровень науки "шпионаж" у разведчика ниже определенного значения, то не будет этих данных
        if(DS_Science::GetSpyScienceBranchLevel(self::$researchGroup->getCombFrom()->getUserInstanceID()) <
        DE_WorldSettings::CreateForCurentWorld()->get('SPY_SCIENCE_LEVEL_FOR_DETAILED_INFO'))
        {
            return;
        }

        if(self::$battleResult['WINNER'] == 'A')
        {
            $accuracy = 3;
        } else {
            $accuracy = self::$battleResult['ROUND'];
        }

        $attacksOnComb = DS_Army::GetArmiesMoveToAttackComb(self::$battleComb->getCombID(), self::$checkDate);

        $params = array();
        if(!empty($attacksOnComb))
        {
            // Язык разведчика
            $params[0] = DE_Localization::CreateFromLangID(self::$researchGroup->getCombFrom()->getUserInstance()->getUser()->getLanguageID())->get(31173);
            //$params[0] = DE_Localization::CreateForCurentLang()->get(31173);
            if($accuracy > 1)
            {
                $params[0] .= ' ('.count($attacksOnComb).')';
            }
        } else {
            // Язык разведчика
            $params[0] = DE_Localization::CreateFromLangID(self::$researchGroup->getCombFrom()->getUserInstance()->getUser()->getLanguageID())->get(31172);
            //$params[0] = DE_Localization::CreateForCurentLang()->get(31172);
        }

        self::_addMessageToLog('A', 'SPY_LOG_ATTACKS_ON_COMB', true, $params);

        if($accuracy > 2)
        {
            foreach ($attacksOnComb as $k => $a)
            {
                $army = DE_Army::CreateFromID($a['id_unitcontainer']);
                $params = array(0 => $k + 1, 1 => $army->getName(), 2 => $army->getUserInstance()->getUser()->getName(), 3 => $army->getBaseComb()->getFullName());
                self::_addMessageToLog('A', 'SPY_LOG_INACTION_ARMY_INFO', true, $params);
            }
        }

        self::_addMessageToLog('A', '');
    }




    /**
     * Информация об атакующем игроке для лога обороны
     *
     * @param bool $fullInfo
     */
    static private function _addAttackerInfo($fullInfo = false)
    {
        assert(is_bool($fullInfo));

        $messparams = array();
        $messparams[0] = self::$researchGroup->getCombFrom()->getUserInstance()->getUser()->getName();

        if($fullInfo)
        {
            $accuracy = GL_SpyLogAccuracy::GetForAttacker(self::$battleResult);

            if($accuracy == GL_SpyLogAccuracy::EMPTY_LOG)
            {
                $msg = 'SPY_LOG_ATTACKER_INFO_FULL_NOT_EMPTY_LOG';
            } else {
                $msg = 'SPY_LOG_ATTACKER_INFO_FULL_EMPTY_LOG';
                $messparams[2] = self::_getAccuracyLocalization($accuracy, 'D');
            }

            // полная информация (имя, сота, какой лог получил)
            $messparams[1] = self::$researchGroup->getCombFrom()->getFullName();
        } else {
            // только имя
            $msg = 'SPY_LOG_ATTACKER_INFO';
        }

        self::_addMessageToLog('D', '');
        self::_addMessageToLog('D', $msg, true, $messparams);
    }


    /**
     * Точность лога атакующего (для защитника)
     *
     * @param int $accuracy
     */
    static private function _addAttackerLogAccuracy($accuracy)
    {
        assert(is_int($accuracy) && $accuracy >= 0);

        $messparams = array();
        $messparams[0] = self::_getAccuracyLocalization($accuracy);

        self::_addMessageToLog('A', '');
        self::_addMessageToLog('A', 'SPY_LOG_ATTACKER_LOG_ACCURACY', true, $messparams);
    }


    /**
     * Локализация степени точности
     *
     * @param unknown_type $accuracy
     * @return unknown
     */
    static private function _getAccuracyLocalization($accuracy, $sideCode = 'A')
    {
        assert($sideCode == 'A' || $sideCode == 'D');

        $localization = array(GL_SpyLogAccuracy::EMPTY_LOG => 31065, GL_SpyLogAccuracy::LOW => 31057, GL_SpyLogAccuracy::MIDDLE => 31058, GL_SpyLogAccuracy::FULL => 31059);
        assert(key_exists($accuracy, $localization));

        if($sideCode == 'A')
        {
            $langId = self::$researchGroup->getCombFrom()->getUserInstance()->getUser()->getLanguageID();
        } else {
            $langId = self::$battleComb->getUserInstance()->getUser()->getLanguageID();
        }

        return DE_Localization::CreateFromLangID($langId)->get($localization[$accuracy]);
    }

    /**
     * Сведения о потерях заондов одной из сторон
     *
     * @param char $side A|D - потери какой стороны 
     * @param char $log A|D - в какой лог писать
     * @param bool $self - это свои патери или чужие
     */
    static private function _addSideLosesToLog($side, $log, $self = false)
    {
        assert($side == 'A' || $side == 'D');
        assert($log == 'A' || $log == 'D');
        assert(is_bool($self));

        if($self)
        {
            $msg = 'SPY_LOG_SELF_LOSES';
        } else {
            $msg = 'SPY_LOG_OPONENT_LOSES';
        }

        $messparams = array();

        if($side == 'A')
        {
            $messparams[0] = self::$researchGroup->getLoses();
            $messparams[1] = self::$researchGroup->getStartAmount();
        } else {
            $group = DE_SpyZondDefenceGroup::CreateFromCombID(self::$battleComb->getCombID());
            $messparams[0] = $group->getLoses();
            $messparams[1] = $group->getStartAmount();
        }

        self::_addMessageToLog($log, $msg, true, $messparams);
    }


    /**
     * Информация об армии на разведываемой соте с определенной точностью
     *
     * @param int $accuracy точность
     */
    static private function _addArmyInfo($accuracy)
    {
        assert(is_int($accuracy) && ($accuracy > GL_SpyLogAccuracy::EMPTY_LOG && $accuracy <= GL_SpyLogAccuracy::FULL));

        $params = self::_getArmyInfoDetailsByAccuracy($accuracy);
        assert(is_array($params));
        assert(key_exists('SKIP_PROBABILITY', $params));
        assert(key_exists('GROUP_NOISE', $params));
        assert(key_exists('FAKE_GROUPS', $params));

        $defenceArmy = DE_CombDefendArmy::CreateFromCombID(self::$battleComb->getID());
        $collection = $defenceArmy->getUnitsGroupCollection();

        // добавляем реальные группы юнитов
        self::_addRealUnitsGroup($collection, $params['SKIP_PROBABILITY'], $params['GROUP_NOISE']);

        // добавляем фейковые группы
        self::_addFakeUnitsGroup($defenceArmy, $params['FAKE_GROUPS']);

        $messparams = array();

        self::_addMessageToLog('A', '');
        self::_addMessageToLog('A', 'SPY_LOG_UNITS_FOUND', true);

        // юнитов нету на соте
        if(count(self::$researchLogAccum) == 0)
        {
            self::_addMessageToLog('A', 'SPY_LOG_NO_UNIT_GROUP', true, $messparams);
            return;
        }

        // перемешиваем список, иначе фальшивые группы юнитов будут всегда в конце списка и игроки сразу это заметят
        shuffle(self::$researchLogAccum);
        $totalWeight = 0;

        // пишем данные в лог
        foreach (self::$researchLogAccum as $k => $log)
        {
            assert(is_array($log));
            assert(key_exists('UNIT', $log));
            assert(key_exists('AMOUNT', $log));
            assert(key_exists('WEIGHT', $log));

            // суммарный вес армии считаем заодно
            $totalWeight += $log['WEIGHT'];

            $messparams[0] = $log['UNIT'];
            $messparams[1] = GB_Integer::Format($log['AMOUNT'], ' ');
            $messparams[2] = GB_Integer::Format($log['WEIGHT'] / 10, ' ');
            self::_addMessageToLog('A', 'SPY_LOG_UNIT_GROUP', true, $messparams);
        }

        // пишем в лог суммарный вес армии
        $messparams = array();
        $messparams[0] = GB_Integer::Format(round($totalWeight / 10), ' ');
        self::_addMessageToLog('A', '');
        self::_addMessageToLog('A', 'SPY_LOG_ATTACKER_SUM_WEIGHT', true, $messparams);
    }


    /**
     * Параметры точности данных об армии, в зависимости от степени точности лога
     *
     * @param int $accuracy
     * @return array
     */
    static private function _getArmyInfoDetailsByAccuracy($accuracy)
    {
        assert(is_int($accuracy) && ($accuracy > GL_SpyLogAccuracy::EMPTY_LOG && $accuracy <= GL_SpyLogAccuracy::FULL));

        // определяем точность данных об армии на соте
        if($accuracy == GL_SpyLogAccuracy::FULL)
        {
            // точные данные, вероятность что-то пропустить - 0
            $skipProbability = 0.0;
            $additionalGroups = 0;
            $groupNoise = false;
        } else {
            // уровни науки "шпионаж"
            $attackerScienceLevel = DS_Science::GetSpyScienceBranchLevel(self::$researchGroup->getCombFrom()->getUserInstanceID());
            $defenderScienceLevel = DS_Science::GetSpyScienceBranchLevel(self::$battleComb->getUserInstanceID());

            // уровень науки у атакера больше
            if($attackerScienceLevel > $defenderScienceLevel)
            {
                // вероятность пропустить юнит группу
                $skipProbability = 1000 - 10 * ($attackerScienceLevel - $defenderScienceLevel);
                // кол-во "левых" групп
                $additionalGroups = 0;
                // разброс кол-ва юнитов выкл.
                $groupNoise = false;
            } else {
                // уровень науки у защитника больше

                $scienceDelta = $defenderScienceLevel - $attackerScienceLevel;
                // вероятность пропустить юнит группу
                $skipProbability = 10 * $scienceDelta; // масштаб 0 - 1000, для большей плавности
                // кол-во "левых" групп
                $additionalGroups = rand(0, ceil($scienceDelta * 0.1));
                // разброс кол-ва юнитов вкл.
                $groupNoise = true;
            }

            // модификаторы вероятности для разных степеней точности
            if($accuracy == GL_SpyLogAccuracy::LOW)
            {
                // низкая точность
                $skipProbability *= DE_WorldSettings::CreateForCurentWorld()->get('SPY_ARMY_OBSERVE_PROBABILITY_LOW_DELTA');
            } else {
                // средняя
                $skipProbability *= DE_WorldSettings::CreateForCurentWorld()->get('SPY_ARMY_OBSERVE_PROBABILITY_MIDLE_DELTA');
            }

            // ограничение на максимальную и минимальную точность
            $maxSkipProbability = 1000 * DE_WorldSettings::CreateForCurentWorld()->get('SPY_ARMY_OBSERVE_PROBABILITY _MAX');
            $minSkipProbability = 1000 * DE_WorldSettings::CreateForCurentWorld()->get('SPY_ARMY_OBSERVE_PROBABILITY _MIN');

            if($skipProbability > $maxSkipProbability)
            {
                $skipProbability = $maxSkipProbability;
            }

            if($skipProbability < $minSkipProbability)
            {
                $skipProbability = $minSkipProbability;
            }
        }

        $result = array('SKIP_PROBABILITY' => (int)$skipProbability, 'GROUP_NOISE' => $groupNoise, 'FAKE_GROUPS' => $additionalGroups);

        return $result;
    }


    /**
     * Левые данные пихаем в лог
     *
     * @param DE_CombDefendArmy $army
     * @param int $additionalGroups
     */
    static private function _addFakeUnitsGroup(DE_CombDefendArmy $army, $additionalGroups)
    {
        assert(is_int($additionalGroups) && $additionalGroups >= 0);

        if($additionalGroups == 0)
        {
            return;
        }

        $messparams = array();

        $unitsID = array();
        if($_SESSION["worldtype"] == 'RACE_VS_RACE')
        {
            $unitsIDtmp = DS_Unit::GetAllRaceUnitsID((int)$_SESSION['race']);
        } else {
            $unitsIDtmp = DS_Unit::GetAllUnitsID();
        }

        foreach ($unitsIDtmp as $k => $uID)
        {
            $unitsID[] = (int)$uID['id_unit'];
        }

        $maxFakeArmyPower = 0.4 * $army->getArmyPower() / $additionalGroups;

        $collection = $army->getUnitsGroupCollection();

        $c = 0;
        $maxCount = 0;
        while(($c < $additionalGroups) && count($unitsID) > 0)
        {
            // Чтобы вдруг не зациклило
            $maxCount ++;
            if ($maxCount > 100) {
                break;
            }

            // берем случайный ид
            $k = array_rand(array_keys($unitsID));
            $ID = $unitsID[$k];
            unset($unitsID[$k]);

            // Если ключ $k нам уже попадался, и переменной $unitsID[$k] больше нет, идем дальше
            if (!key_exists($k, $unitsID)) {
                $c++;
                continue;
            }

            // проверяем нет ли такого юнита в армии
            $collection->resetNext();
            while($unitGroup = $collection->next())
            {
                if($unitGroup->getUnit()->getID() == $ID)
                {
                    // еесть, начинаем с начала
                    continue 2;
                }
            }

            $unit = DE_Unit::CreateFromID($ID);
            if($unit->getUnitPower() == 0)
            {
                // не очень хороший юнит, без веса
                continue;
            }

            $unitsAmount = rand(1, ceil($maxFakeArmyPower / $unit->getUnitPower()));

            //$messparams[0] = $unit->getLocalizedName();
            //$messparams[1] = GB_Integer::Format($unitsAmount, ' ');
            $unitName = DE_Localization::CreateFromLangID(self::$researchGroup->getCombFrom()->getUserInstance()->getUser()->getLanguageID())->get($unitGroup->getUnit()->getUnitLocolizedId());

            self::$researchLogAccum[] = array('UNIT' => $unitName, 'AMOUNT' => $unitsAmount, 'WEIGHT' => $unit->getUnitPower() * $unitsAmount);

            //self::_addMessageToLog('A', 'SPY_LOG_UNIT_GROUP', true, $messparams);

            $c++;
        }
    }


    /**
     * Добавление в лог данных о реально существующих на соте юнитах
     *
     * @param DC_CombDefendUnitGroups $collection
     * @param int $skipProbability
     * @param bool $groupNoise
     */
    static private function _addRealUnitsGroup(DC_CombDefendUnitGroups $collection, $skipProbability, $groupNoise)
    {
        assert(is_int($skipProbability) && $skipProbability >= 0 && $skipProbability < 1000);
        assert(is_bool($groupNoise));

        $messparams = array();
        $collection->resetNext();

        $groupNoiseSize = DE_WorldSettings::CreateForCurentWorld()->get('SPY_DEFEND_GROUPS_FAKE_COUNT_ERROR');

        // Уровень науки шпионажа атакера
        $spyLevelAtaker = DS_Science::GetSpyScienceBranchLevel(self::$researchGroup->getCombFrom()->getUserInstanceID());

        while($unitGroup = $collection->next())
        {
            // не повезло, пропускаем группу
            if($skipProbability > rand(0, 1000))
            {
                continue;
            }

            $unitsAmount = ceil($unitGroup->getAmount());

            // искажаем кол-во +/-
            if($groupNoise)
            {
                $unitsAmount *= (1 - 0.01 * (rand(0, round(200 * $groupNoiseSize)) - round(100 * $groupNoiseSize)));
            }

            //$messparams[0] = $unitGroup->getUnit()->getLocalizedName();
            //$messparams[1] = GB_Integer::Format($unitsAmount, ' ');

            $unitName = DE_Localization::CreateFromLangID(self::$researchGroup->getCombFrom()->getUserInstance()->getUser()->getLanguageID())->get($unitGroup->getUnit()->getUnitLocolizedId());
            self::$researchLogAccum[] = array('UNIT' => $unitName, 'AMOUNT' => $unitsAmount, 'WEIGHT' => $unitGroup->getUnit()->getUnitPower() * $unitsAmount);

            //self::_addMessageToLog('A', 'SPY_LOG_UNIT_GROUP', true, $messparams);
        }
    }


    /**
     * Заголовок лога атакующего
     *
     */
    static private function _addAttackerHeader()
    {
        $messparams = array();
        $messparams[0] = self::$researchGroup->getCombTo()->getFullName();
        $messparams[1] = self::$researchGroup->getCombFrom()->getFullName();
        
        $messparams[2] = self::$researchGroup->getStartAmount();


        self::_addMessageToLog('A', 'SPY_RESEARCH_LOG_HEADER', true, $messparams);
        self::_addMessageToLog('A', '');
    }


    /**
     * Заголовок лога защищающегося
     *
     */
    static private function _addDefenderHeader()
    {
        $messparams = array();
        $messparams[0] = self::$researchGroup->getCombTo()->getFullName();

        self::_addMessageToLog('D', 'SPY_DEFENCE_LOG_HEADER', true, $messparams);
        self::_addMessageToLog('D', '');
    }

    /**
     * Добавить строку в лог
     *
     * @param char $sideCode A|D - сторона
     * @param string $msg сообщение
     * @param bool $format нужна ли локализация и форматирование
     * @param array $messparams параметры
     */
    static private function _addMessageToLog($sideCode, $msg, $format = false, array $messparams = array())
    {
        assert($sideCode == 'A' || $sideCode == 'D');
        assert(is_string($msg));
        assert(is_bool($format));

        // чтоб подхватился язык игрока, выбираем нужный уиид
        if($sideCode == 'A')
        {
            $UIID = self::$researchGroup->getCombFrom()->getUserInstanceID();
        } else {
            $UIID = self::$battleComb->getUserInstanceID();
        }

        if($format)
        {
            self::$log[$sideCode][] = DE_SystemMessages::CreateFromUserInstanceID($UIID)->getFormatedMessage($msg, $messparams, '', null, false);
        } else {
            self::$log[$sideCode][] = $msg;
        }
    }
}

?>