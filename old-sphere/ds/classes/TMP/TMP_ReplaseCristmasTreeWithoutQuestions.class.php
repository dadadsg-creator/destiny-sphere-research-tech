<?php

/**
 * Класс для замены новогодних елочек на рандомные арты.
 * replaseNewYearTree.php
 *
 */
class TMP_ReplaseCristmasTreeWithoutQuestions
{
    static private $dbID;

    //static private $worldID;

    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;

    static private $artsVersion = array(
    0   => 'COMMON_ICE_TREE1',
    1   => 'COMMON_ICE_TREE2',
    );

    static private $targetArtVersion = array();

    static private $arts;

    static private $targetArt;

    static private $combs;

    /**
     * Обрабатываем один мир за раз
     *
     * @param int $dbID
     * @param int $worldID
     */
    static public function Exec($dbID)
    {
        assert(is_int($dbID) && $dbID >= 0);
        //assert(is_int($worldID) && $worldID > 0);

        self::$dbID = $dbID;

        //self::$worldID = $worldID;

        self::$common = DS_Common::CreateForCurentDB(self::$dbID);

        // выбираем айдишники для версии
        self::_initArtIDByVersion();

        // собираем список сот, на которых есть арты
        self::_getCombsWithArtefacts();

        self::_log("Found ".count(self::$combs)." combs with artefacts<br>");

        $totalCount = 0;
        
        // Проходимся по каждому арту
        foreach (self::$combs as $keyComb => $artData)
        {
            self::_replaceArts((int)$artData['id_comb'], (int)$artData['id_artefactinstance'], $artData['user']);
            $totalCount++;
        }

        echo "<br>$totalCount arts proccessed!<br>";
    }


    static private function _log($msg)
    {
        echo $msg."\n";
    }

    static private function _initArtIDByVersion()
    {
        foreach (self::$artsVersion as $id => $name)
        {
            self::$arts[$id] = self::_getArtefactIdByName($name);
        }

        /*$targetArtVersion = self::_GetTargetArt();

        foreach ($targetArtVersion as $id => $art)
        {
        self::$targetArt[] = $art['id_artefact'];
        }*/

        /*self::$targetArt = array(
        'PAID_BUILDING_IGROMIR_KADERIUM_SALE' => 1111,
        'PAID_BUILDING_IGROMIR_NANO_SALE' => 1113,
        'PAID_BUILDING_IGROMIR_FOOD_SALE' => 1115,
        'PAID_BUILDING_IGROMIR_STATIC_SALE' => 1117,
        'PAID_BUILDING_IGROMIR_PRODIUM_SALE' => 1119
        );*/

        self::$targetArt = array(
        'MIGHTY_SPHERE_KADERIUM_EGG' => 1059, // Могучая Сфера Санкторума 
        'MIGHTY_SPHERE_NANO_CRYSTAL_EGG' => 1060, // Могучая Сфера Неомортана 
        'MIGHTY_SPHERE_PRODIUM_EGG' => 1061, // Могучая Сфера Терминуса
        'MIGHTY_SPHERE_FOOD_EGG' => 1062, // Могучая Сфера Виталое
        'MIGHTY_SPHERE_STASIS_ENERGY_EGG' => 1063, // Могучая Сфера Энервитриума
        'OUTSTANDING_SPHERE_KADERIUM_EGG' => 1064, // Великая Сфера Санкторума
        'OUTSTANDING_SPHERE_NANO_CRYSTAL_EGG' => 1065, // Великая Сфера Неомортана
        'OUTSTANDING_SPHERE_PRODIUM_EGG' => 1066, // Великая Сфера Терминуса
        'OUTSTANDING_SPHERE_FOOD_EGG' => 1067, // Великая Сфера Виталое
        'OUTSTANDING_SPHERE_STASIS_ENERGY_EGG' => 1068, // Великая Сфера Энервитриума
        'SUPREME_SPHERE_KADERIUM_EGG' => 1069, // Колоссальная Сфера Санкторума
        'SUPREME_SPHERE_NANO_CRYSTAL_EGG' => 1070, // Колоссальная Сфера Санкторума
        'SUPREME_SPHERE_PRODIUM_EGG' => 1071, // Колоссальная Сфера Терминуса 
        'SUPREME_SPHERE_FOOD_EGG' => 1072, // Колоссальная Сфера Виталое 
        'SUPREME_SPHERE_STASIS_ENERGY_EGG' => 1073 // Колоссальная Сфера Энервитриума 
        );
    }

    static private function _replaceArts($id_comb, $idArtefartInstance, $username)
    {
        /*
        3 % колосальные сферки рендомно одна из 5 видов
        4 % великие сферки рендомно одна 5 видов
        8 % могучая сферка рендомно одна из 5 видов
        1 % альянсовый артефакт предельный на кадериум
        1 % альянсовый артефакт предельный на кристаллы
        1 % альянсовый предельный на свет
        1 % предельный альянсовый на еду
        0.5 % золотые сотовые артефакты на свет
        0.5 % золотые сотовые артефакты на еду
        0.5 % серебряные сотовые на кадериум
        0.5 % серебряные сотовые на нанокристаллы
        0.5 % серебряные сотовые на продиум
        0.5 % сотовый артефакт игромира на кадериум
        0.5 % сотовый артефакт игромира на нанокристаллы
        */
        
        
        // Кидаем кубик
        srand();
        $range = rand(1, 100);
        if ($range <= 0) {
            // колосальные сферки рендомно одна из 5 видов
            $targetArtArr = array(
                'SUPREME_SPHERE_KADERIUM_EGG' => 1069, // Колоссальная Сфера Санкторума
                'SUPREME_SPHERE_NANO_CRYSTAL_EGG' => 1070, // Колоссальная Сфера Санкторума
                'SUPREME_SPHERE_PRODIUM_EGG' => 1071, // Колоссальная Сфера Терминуса 
                'SUPREME_SPHERE_FOOD_EGG' => 1072, // Колоссальная Сфера Виталое 
                'SUPREME_SPHERE_STASIS_ENERGY_EGG' => 1073 // Колоссальная Сфера Энервитриума 
                );
            $keyRand = array_rand($targetArtArr);
            $newIdArtefart = (int)$targetArtArr[$keyRand];

        } elseif ($range <= 0) {
            //великие сферки рендомно одна 5 видов
            $targetArtArr = array(
                'OUTSTANDING_SPHERE_KADERIUM_EGG' => 1064, // Великая Сфера Санкторума
                'OUTSTANDING_SPHERE_NANO_CRYSTAL_EGG' => 1065, // Великая Сфера Неомортана
                'OUTSTANDING_SPHERE_PRODIUM_EGG' => 1066, // Великая Сфера Терминуса
                'OUTSTANDING_SPHERE_FOOD_EGG' => 1067, // Великая Сфера Виталое
                'OUTSTANDING_SPHERE_STASIS_ENERGY_EGG' => 1068, // Великая Сфера Энервитриума
                );
            $keyRand = array_rand($targetArtArr);
            $newIdArtefart = (int)$targetArtArr[$keyRand];

        } elseif ($range <= 0) {
            //великие сферки рендомно одна 5 видов
            $targetArtArr = array(
                'MIGHTY_SPHERE_KADERIUM_EGG' => 1059, // Могучая Сфера Санкторума 
                'MIGHTY_SPHERE_NANO_CRYSTAL_EGG' => 1060, // Могучая Сфера Неомортана 
                'MIGHTY_SPHERE_PRODIUM_EGG' => 1061, // Могучая Сфера Терминуса
                'MIGHTY_SPHERE_FOOD_EGG' => 1062, // Могучая Сфера Виталое
                'MIGHTY_SPHERE_STASIS_ENERGY_EGG' => 1063, // Могучая Сфера Энервитриума
                );
            $keyRand = array_rand($targetArtArr);
            $newIdArtefart = (int)$targetArtArr[$keyRand];

            // пропущенные
            //'SUPERIOR_ALLIANCE_WELDING' => 423, // Огромный альянсовый артефакт сварки 
            //'SUPERIOR_ALLIANCE_SYNTHESIS ' => 444, // Огромный альянсовый артефакт синтеза 
            //'SUPERIOR_ALLIANCE_ATOMIZATION  ' => 430, // Огромный альянсовый артефакт атома
            //'SUPERIOR_ALLIANCE_FERTILIZATION  ' => 437, // Огромный альянсовый артефакт удобрений


        } elseif ($range <= 0) {
            $targetArtArr = array(
                'GOLD_COMB_ROAD_GEKKON  ' => 1075, // Золотой сотовый артефакт путь геккона
                'GOLD_COMB_MAGIC_CRISTAL  ' => 1078, // Золотой сотовый артефакт магия кристалла
                'GOLD_COMB_MAGIC_CRISTAL  ' => 1081, // Золотой сотовый артефакт сила прогресса
            );
            $keyRand = array_rand($targetArtArr);
            $newIdArtefart = (int)$targetArtArr[$keyRand];

        } elseif ($range <= 0) {
            $targetArtArr = array(
                'SILVER_COMB_ROAD_GEKKON  ' => 1076, // Серебрянный сотовый артефакт путь геккона
                'SILVER_COMB_MAGIC_CRISTAL  ' => 1079, // Серебрянный сотовый артефакт магия кристалла
                'SILVER_COMB_POWER_PROGRESS  ' => 1082, // Серебрянный сотовый артефакт сила прогресса
            );
            $keyRand = array_rand($targetArtArr);
            $newIdArtefart = (int)$targetArtArr[$keyRand];

        }elseif ($range <= 0) {
            $targetArtArr = array(
                'GOLD_COMB_IGROMIR_NANO' => 1096, // Кристаллический артефакт Игромира-2008
                'GOLD_COMB_IGROMIR_FOOD' => 1097, // Питательный артефакт Игромира-2008
                'GOLD_COMB_IGROMIR_STATIC' => 1098, // Энергетический артефакт Игромира-2008
                'GOLD_COMB_IGROMIR_NANO' => 1099, // Продиумный артефакт Игромира-2008
                'GOLD_COMB_IGROMIR_KADERIUM' => 1100, // Кадериумный артефакт Игромира-2008
            );
            $keyRand = array_rand($targetArtArr);
            $newIdArtefart = (int)$targetArtArr[$keyRand];
        } elseif ($range <= 100) {
            $targetArtArr = array(                  // Валентинки
                'REDS_LOVE_KADERIUMHEART' => 2220,
                'REDS_LOVE_NANOHEART' => 2221,
                'REDS_LOVE_ENERGYHEART' => 2222,
                'REDS_LOVE_FOODHEART' => 2223,
                'REDS_LOVE_PRODIUMHEART' => 2224
            );
            $keyRand = array_rand($targetArtArr);
            $newIdArtefart = (int)$targetArtArr[$keyRand];
        } else {
            // Ничего       
            return;
        }
        
        
        assert(is_int($id_comb) && $id_comb > 0);
        assert(is_int($idArtefartInstance) && $idArtefartInstance > 0);

        // Удаляем с соты старый артефакт
        $sql = "DELETE FROM ds_combhasartefact WHERE id_artefact = $idArtefartInstance AND id_comb = $id_comb";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));


        // удаляем инстанс старого aртефакта
        $sql = "DELETE FROM ds_artefactinstance WHERE id_artefactinstance = $idArtefartInstance";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));

        // Берем рандомный арт
        assert(is_int($newIdArtefart) && $newIdArtefart > 0);
        //assert($newIdArtefart <= 1073);
        //assert($newIdArtefart >= 1059);

        // Добавляем на соту новый артефакт
        // Добавляем на соту КОРОБКУ
        $sql = "SELECT add_artefact_to_comb(".$id_comb.", ".$newIdArtefart.", 0) as id_artefactinstance";
        $res = self::$common->getResultArray("", $sql);
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);

        $idArtInstance = (int)$res[0]['id_artefactinstance'];

        SU_DebugTools::LogAll('CombId = '.$id_comb.', newArt = '.$newIdArtefart.' newArtInstance = '.$idArtInstance, 'ReplaseTree.log', 0);

        echo "$username : $keyRand<br>";
    }


    static private function _getCombsWithArtefacts()
    {
        assert(is_array(self::$arts));

        $sql = "
            SELECT
                cha.id_comb, ai.id_artefactinstance, u.username||'('||r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber||')' as user
            FROM
                ds_combhasartefact cha
            LEFT JOIN ds_artefactinstance ai ON (ai.id_artefactinstance = cha.id_artefact)
            LEFT JOIN ds_artefact a ON (a.id_artefact = ai.id_artefact)
            left join ds_comb c ON (c.id_comb = cha.id_comb)
            left join ds_userinstance ui ON (ui.id_userinstance = c.id_userinstance)
            left join ds_user u ON (u.id_user = ui.id_user)
            LEFT JOIN ds_combcomplex cc ON (cc.id_combcomplex = c.id_combcomplex)
            LEFT JOIN ds_ring r ON (r.id_ring = cc.id_ring)
            WHERE
                a.id_artefact IN (".implode(",\n", self::$arts).")";

        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        self::$combs = $res;
    }


    static private function _getArtefactIdByName($name)
    {
        assert(!empty($name));

        $sql = "
            SELECT
                id_artefact
            FROM
                ds_artefact
            WHERE
                artefactname = '$name'
            ";
        $res = self::$common->getResultArray("", $sql);
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);

        return (int)$res[0]['id_artefact'];
    }
}

?>