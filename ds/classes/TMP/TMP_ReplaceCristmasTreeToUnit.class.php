<?php

/**
 * Класс для замены новогодних елочек на рандомные арты.
 * replaseNewYearTree.php
 *
 */
class TMP_ReplaceCristmasTreeToUnit
{
    static private $dbID;

    static private $worldID;

    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;

    static private $artsVersion = array(
    // artefactname => id_artefact
    'COMMON_ICE_TREE1' => 1,
    'COMMON_ICE_TREE2' => 2,
    );

    //static private $targetArtVersion = array();

    static private $arts;

    //static private $targetArt;

    // Соты с артефактами
    static private $combs;

    /**
     * Обрабатываем один мир за раз
     *
     * @param int $dbID
     * @param int $worldID
     */
    static public function Exec($dbID, $worldID)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID > 0);

        self::$dbID = $dbID;

        self::$worldID = $worldID;

        self::$common = DS_Common::CreateForCurentDB(self::$dbID);

        // собираем список артефактов новогоднего настроения
        self::_getCombsWithArtefacts(1);

        self::_log("Found ".count(self::$combs)." combs with artefacts COMMON_ICE_TREE1<br>");

        $count = 1;
        $totalCount = 0;
        
        // Проходимся по каждому арту
        foreach (self::$combs as $keyComb => $artData)
        {

            if (!rand(0, 49)) {
                self::_replaceArtToUnit((int)$artData['id_comb'], (int)$artData['id_artefactinstance'], 96);
                echo " - Заменяем арт на аномалию";
                $totalCount ++;
            }
            // заменяем арт на аномалию
            echo "<br>";
        }
        echo "<br>$totalCount arts proccessed!<br><br><br>";
        
        
        $totalCount = 0;
        // собираем список артефактов новогоднего радости  
        self::_getCombsWithArtefacts(2);
        
        self::_log("Found ".count(self::$combs)." combs with artefacts COMMON_ICE_TREE2<br>");
        
        // Проходимся по каждому арту
        foreach (self::$combs as $keyComb => $artData)
        {
            if (!rand(0, 49)) {
                self::_replaceArtToUnit((int)$artData['id_comb'], (int)$artData['id_artefactinstance'], 81);
                echo " - Заменяем арт на Деда Мороза";
                // self::_replaceArtToUnit((int)$artData['id_comb'], (int)$artData['id_artefactinstance'], 82);
                // echo " - Заменяем арт на Cнегурку";
                $totalCount ++;
            }            
            echo "<br>";
        }

        GB_SingletonObjectStorage::CommitAllCommitable();
        
        echo "<br>$totalCount arts proccessed!<br>";
    }


    static private function _log($msg)
    {
        echo $msg."\n";
    }

    static private function _replaceArtToUnit($id_comb, $idArtefartInstance, $id_unit)
    {
        assert(is_int($id_comb) && $id_comb > 0);
        assert(is_int($idArtefartInstance) && $idArtefartInstance > 0);
        assert(is_int($id_unit) && $id_unit > 0);

        // Удаляем с соты старый артефакт
        $sql = "DELETE FROM ds_combhasartefact WHERE id_artefact = $idArtefartInstance AND id_comb = $id_comb";
        SU_DebugTools::LogAll($sql, 'ReplaseTree.log', 0);
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));

        // удаляем инстанс старого aртефакта
        $sql = "DELETE FROM ds_artefactinstance WHERE id_artefactinstance = $idArtefartInstance";
        SU_DebugTools::LogAll($sql, 'ReplaseTree.log', 0);
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));

        

        // Добавляем на соту новый юнит в домашку
        $count = 1.0;
        $unit = DE_Unit::CreateFromID($id_unit);
        $homeArmy = DE_Army::CreateHomeArmyByCombID($id_comb);
        $homeUnitGroup = $homeArmy->getUnitsGroupCollection()->GetUnitGroupByUnit($unit);
        if($homeUnitGroup === false)
        {
            $homeUnitGroup = DE_UnitsGroup::CreateInDB($homeArmy, $unit, $count);
            $homeArmy->getUnitsGroupCollection()->AddUnitGroup($homeUnitGroup);
        } else {
            $homeUnitGroup->deltaAmount($count);
        }

        SU_DebugTools::LogAll('CombId = '.$id_comb.', newUnit = '.$id_unit, 'ReplaseTree1.log', 0);

    }

    static private function _replaceArtsToArt($id_comb, $idArtefartInstance)
    {
        assert(is_int($id_comb) && $id_comb > 0);
        assert(is_int($idArtefartInstance) && $idArtefartInstance > 0);

        // Удаляем с соты старый артефакт
        $sql = "DELETE FROM ds_combhasartefact WHERE id_artefact = $idArtefartInstance AND id_comb = $id_comb";
        SU_DebugTools::LogAll($sql, 'ReplaseTree.log', 0);
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));


        // удаляем инстанс старого aртефакта
        $sql = "DELETE FROM ds_artefactinstance WHERE id_artefactinstance = $idArtefartInstance";
        SU_DebugTools::LogAll($sql, 'ReplaseTree.log', 0);
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));

        // Добавляем на соту новый артефакт 'MIGHTY_SPHERE_PRODIUM_EGG', 1061, Могучая Сфера Терминуса
        $newIdArtefart = 1061;
        $sql = "SELECT add_artefact_to_comb(".$id_comb.", ".$newIdArtefart.", 0) as id_artefactinstance";
        SU_DebugTools::LogAll($sql, 'ReplaseTreeSql.log', 0);
        $res = self::$common->getResultArray("", $sql);
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);

        $idArtInstance = (int)$res[0]['id_artefactinstance'];

        SU_DebugTools::LogAll('CombId = '.$id_comb.', newArt = '.$newIdArtefart.' newArtInstance = '.$idArtInstance, 'ReplaseTree.log', 0);

    }

    static private function _getCombsWithArtefacts($id_art)
    {
        assert(is_int($id_art) && $id_art > 0);
        assert($id_art == 1 || $id_art == 2);
        
        self::$combs = array();
        
        $sql = "
            SELECT
                cha.id_comb, ai.id_artefactinstance
            FROM
                ds_combhasartefact cha
            LEFT JOIN ds_artefactinstance ai ON (ai.id_artefactinstance = cha.id_artefact)
            LEFT JOIN ds_artefact a ON (a.id_artefact = ai.id_artefact)
            WHERE
                a.id_artefact = $id_art";

        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
/*echo "<pre>";
print_r($res);
exit;*/
        self::$combs = $res;
    }

    /*static private function _GetTargetArt()
    {
    $sql = "
    SELECT
    id_artefact, artefactname
    FROM
    ds_artefact
    WHERE
    id_artefactprobability IN (4, 5)  -- MAJOR(Крупный), GREAT(Большой)
    AND
    id_artefactscope = 2 -- COMB";

    $res = self::$common->getResultArray("", $sql, "", "", "");
    assert(empty(self::$common->errormsg));

    return $res;
    }*/

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