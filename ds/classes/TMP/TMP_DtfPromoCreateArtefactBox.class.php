<?php

/**
 * Класс для замены тройки квестовых артефактов Мышки, Джойстика и Мобильника, на коробку.
 * Для проведение промо-акции DTF.
 *
 */
class TMP_DtfPromoCreateArtefactBox
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
            'phone'     => 'SMALL_QUEST_PHONEICON',
            'mouse'     => 'MINOR_QUEST_MOUSEICON',
            'joystik'   => 'MINOR_QUEST_JOYSTIKICON',
            /*'classic' => array('phone' => 1088, 'mouse' => 1089, 'joystik' => 1090),
            'unlim'     => array('phone' => 1093, 'mouse' => 1094, 'joystik' => 1095),*/
        );
        
    static private $targetArtVersion = array(
            /*'classic'     => 1095,
            'unlim'     => 1088,*/
            'box'       => 'WHITE_QUEST_BOXIM'
        );
        
    static private $arts;
    
    static private $targetArt;
    
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
        
        // выбираем айдишники для версии
        self::_initArtIDByVersion();
        
        // собираем список сот, на которых есть тройки артов
        self::_getCombsWithArtefacts();
        
        self::_log("Found ".count(self::$combs)." combs with artefacts");
        
        // обрабатываем
        self::_processCombs();
    }
    
    static private function _processCombs()
    {
        foreach (self::$combs as $k => $comb)
        {
            $arr = array();
            
            foreach (self::$arts as $name => $id)
            {
                assert(key_exists($name, $comb));
                $arr[] = $comb[$name];
            }
            
            // смотрим, сколько всего полных троек есть на соте
            $tripleArtsCount = min($arr);
            
            self::_log("Add ".$tripleArtsCount." arts to ".$comb['username']);
            
            self::$common->startTrans();
            
            for($i=0; $i<$tripleArtsCount; $i++)
            {
                // получаем айдишники тройки артефактов
                $arts = self::_getArtsCombination((int)$comb['id_comb'], $i);

                // заменяем на коробку
                self::_replaceArts($arts, (int)$comb['id_comb']);
            }
            
            self::$common->closeTrans('COMMIT');
        }
    }
    
    static private function _log($msg)
    {
        echo $msg."\n";
    }
    
    static private function _initArtIDByVersion()
    {
        /*switch (constant('GAME_HOST')) 
        {
            case 'http://ds.rambler.ru':
            case 'http://classic':
                self::$arts = self::$artsVersion['classic'];
                self::$targetArt = self::$targetArtVersion['classic'];
                break;
                
            case 'http://ds-game.ru':
            case 'http://test.destinysphere.ru':
            case 'http://alpha.destinysphere.ru':
            case 'http://unlim':
                self::$arts = self::$artsVersion['unlim'];
                self::$targetArt = self::$targetArtVersion['unlim'];            
                break;
        
            default:
                throw new Exception('Unknown GAME_HOST');
                break;
        }*/
        foreach (self::$artsVersion as $id => $name)
        {
            self::$arts[$id] = self::_getArtefactIdByName($name);
        }
        
        self::$targetArt = self::_getArtefactIdByName(self::$targetArtVersion['box']);
    }
    
    static private function _replaceArts(array $arts, $id_comb)
    {
        assert(is_int($id_comb) && $id_comb > 0);
        assert(is_int(self::$targetArt) && self::$targetArt > 0);
        
        // Удаляем с соты артефакты, из которых собираем КОРОБКУ
        $sql = "DELETE FROM ds_combhasartefact WHERE id_artefact IN (".$arts['phone'].", ".$arts['mouse'].", ".$arts['joystik'].")";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
        
        // Добавляем на соту КОРОБКУ
        $sql = "SELECT add_artefact_to_comb(".$id_comb.", ".self::$targetArt.") as id_artefactinstance";
        $res = self::$common->getResultArray("", $sql);
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);
        
        $idArtInstance = (int)$res[0]['id_artefactinstance'];
        // Генерируем уникальный код артефакта
        $publicId = GB_RandomPassword::Generate(10);
        
        // Вставляем запись о коробке
        $sql = "
            INSERT INTO ds_promo_dtf_artefactbox
                (
                    id_artefactinstance, 
                    id_artefact_mouse, 
                    id_artefact_joystick, 
                    id_artefact_phone, 
                    creation_date, 
                    public_id
                )
            SELECT
                ".$idArtInstance.",
                (
                    SELECT
                        qa1.id_quest_artefact
                    FROM
                        ds_artefactinstance AS ai1
                        INNER JOIN ds_quest_artefact_search_log AS qa1 ON (qa1.id_artefactinstance = ai1.id_artefactinstance)
                    WHERE
                        ai1.id_artefactinstance = ".$arts['mouse']."
                ) as id_quest_artefact_mouse,
    
                (
                    SELECT
                        qa2.id_quest_artefact
                    FROM
                        ds_artefactinstance AS ai2
                        INNER JOIN ds_quest_artefact_search_log AS qa2 ON (qa2.id_artefactinstance = ai2.id_artefactinstance)
                    WHERE
                        ai2.id_artefactinstance = ".$arts['joystik']."
                ) as id_quest_artefact_joystik,

                (
                    SELECT
                        qa3.id_quest_artefact
                    FROM
                        ds_artefactinstance AS ai3
                        INNER JOIN ds_quest_artefact_search_log AS qa3 ON (qa3.id_artefactinstance = ai3.id_artefactinstance)
                    WHERE
                        ai3.id_artefactinstance = ".$arts['phone']."
                ) as id_quest_artefact_phone,
    
                now(),
                '".$publicId."'
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
        
        // удаляем инстансы aртефактов-запчастей
        $sql = "DELETE FROM ds_artefactinstance WHERE id_artefactinstance IN (".$arts['phone'].", ".$arts['mouse'].", ".$arts['joystik'].")";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static private function _getArtsCombination($id_comb, $offset = 0)
    {
        assert(is_array(self::$arts));
        assert(is_int($id_comb) && $id_comb > 0);
        assert(is_int($offset) && $offset >= 0);
        
        $arr = array();
        
        foreach (self::$arts as $name => $id)
        {
            $arr[] = "
                (
                    SELECT
                        ai1.id_artefactinstance
                    FROM
                        ds_combhasartefact AS cha1
                        INNER JOIN ds_artefactinstance AS ai1 ON (ai1.id_artefactinstance = cha1.id_artefact)
                    WHERE
                        cha1.id_comb = $id_comb
                        AND
                        ai1.id_artefact = $id
                    LIMIT 1
                    -- OFFSET $offset
                ) as $name
                ";
        }
                
        $sql = "SELECT \n\t".implode(",\n", $arr);
        $res = self::$common->getResultArray("", $sql);
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);

        return $res[0];
    }
    
    static private function _getCombsWithArtefacts()
    {
        assert(is_array(self::$arts));
        
        $arrCountParts = array();
        $arrWhereParts = array();
        foreach (self::$arts as $name => $id)
        {
            $arrCountParts[] = "
                (
                    SELECT
                        count(ai.id_artefactinstance)
                    FROM
                        ds_combhasartefact AS cha
                        INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
                    WHERE
                        cha.id_comb = c.id_comb
                        AND
                        ai.id_artefact = $id
                ) as $name
                ";
            
            $arrWhereParts[] = "
                EXISTS (
                    SELECT
                        *
                    FROM
                        ds_combhasartefact AS cha
                        INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
                    WHERE
                        cha.id_comb = c.id_comb
                        AND
                        ai.id_artefact = $id
                )
                ";
        }
        
        $sql = "
            SELECT
                c.id_comb,
                c.id_userinstance,
                u.id_user,
                u.username,
                ".implode(",\n", $arrCountParts)."
            FROM
                ds_comb AS c
                INNER JOIN ds_userinstance AS ui USING (id_userinstance)
                INNER JOIN ds_user AS u USING (id_user)
            WHERE
                ".implode("\n\t\t\t\tAND\n", $arrWhereParts)."
                --AND u.username = 'Stariy95'
            ";
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