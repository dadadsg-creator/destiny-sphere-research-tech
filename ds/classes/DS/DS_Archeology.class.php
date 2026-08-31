<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Archeology extends DS_Base
{
    static public function getCountArcheologyTeam($uiid)
    {
        assert(is_int($uiid) && $uiid > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    count(at.teamnumber) as count
                FROM
                    ds_archeologicalteam AS at
                    INNER JOIN ds_comb AS c ON (c.id_comb = at.id_comb)
                WHERE
                    c.id_userinstance = ".$uiid."
                ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static public function getCountArefacts($combId)
    {
        assert(is_int($combId) && $combId > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                count(id_artefact) as count
            FROM
                ds_combhasartefact
            WHERE
                id_comb = ".$combId."
            ";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)$res[0]['count'];
    }

    static public function getArtefactSizeByScopeAndScience($id_scope, $science)
    {
        assert(is_int($id_scope) && $id_scope > 0);

        $common = self::getCommon();

        $sql = "
                SELECT ap.* FROM ds_artefactprobability AS ap, ds_artefactscopehasprobability AS ashp
                WHERE ap.id_artefactprobability = ashp.id_artefactprobability
                    AND ashp.id_artefactscope = $id_scope
                    AND (min_chance != 0 or max_chance != 0)
                    order by id_artefactprobability";
        $res = $common->getResultArray("artefact_scope_".$id_scope."_probability", $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        if (!count($res)) return 0;

        $probs = array();
        $lastMin = 0;
        $lastMax = 0;
        foreach ($res as $row) {
            $lastMin += $row['min_chance'];
            $lastMax += $row['max_chance'];
            $probs[$row['id_artefactprobability']] = $lastMin * (1 - $science / 100) + $lastMax * $science / 100;
        }
        $r = mt_rand() / mt_getrandmax() * $probs[$row['id_artefactprobability']];
        foreach ($probs as $size=>$p) {
            if ($r < $p) break;
        }
        return $size;
    }

    static public function getArtefactTypeByScopeAndPrefix($id_scope, $id_prefix)
    {
        assert(is_int($id_scope) && $id_scope > 0);
        assert(is_int($id_prefix) && $id_prefix > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    DISTINCT id_artefacttype
                FROM
                    ds_artefact
                WHERE
                    id_artefactscope = $id_scope
                    AND
                    id_artefactprobability = $id_prefix
                    AND
                    can_be_found
                ORDER BY id_artefacttype
            ";
        $res = $common->getResultArray("artefact_scope_".$id_scope."_prefix_".$id_prefix, $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        $types = array();
        foreach ($res as $k => $type)
        {
            $types[] = $type['id_artefacttype'];
        }

        return $types;
    }

    static public function getRaceBonus($raceID)
    {
        assert(is_int($raceID) && $raceID > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    rha.value
                FROM
                    ds_racehasattribute rha,
                    ds_attribute a,
                    ds_attributename atn,
                    ds_attributetype att
                WHERE
                    rha.id_race = ".$raceID."
                    AND rha.id_attribute = a.id_attribute
                    AND a.id_attributename = atn.id_attributename
                    AND a.id_attributetype = att.id_attributetype
                    AND att.typename = 'BONUS_RELATIVE'
                    AND atn.attributename = 'ARCHEOLOGY'";

        $res = $common->getResultArray("archeology_racebonus_".$raceID, $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        if(count($res) > 0)
        {
            return (float)$res[0]['value'];
        }

        return (float)0;
    }

    static public function getArtefactScopeByCellType($id_celltype)
    {
        assert(is_int($id_celltype) && $id_celltype > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    *
                FROM
                    ds_artefactscope
                WHERE
                    id_celltype = ".$id_celltype;
        $res = $common->getResultArray("artefact_scope_for_celltype".$id_celltype, $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        return $res;
    }

    static public function getCellLocalizedName($id_celltype)
    {
        assert(is_int($id_celltype) && $id_celltype > 0);

        $common = self::getCommon();

        $sql = "
                SELECT
                    ltl.text AS localizedname
                FROM
                    ds_celltype AS ct,
                    ds_localizedtextlanguage AS ltl
                WHERE
                    ct.id_localizedname = ltl.id_localizedtext
                    AND
                    ltl.id_language = ".$_SESSION['lang']."
                    AND
                    ct.id_celltype = ".$id_celltype."
                ";
        $res = $common->getResultArray("artefact_celltype_name_lang".$id_celltype.$_SESSION['lang'], $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        return $res[0]['localizedname'];
    }

    static public function deleteArcheologyTeam($id_comb, $teamnumber)
    {
        assert(is_int($id_comb) && $id_comb > 0);
        assert(is_int($teamnumber) && $teamnumber > 0);

        $common = self::getCommon();

        $sql = "
            DELETE FROM
                ds_archeologicalteam
            WHERE
                id_comb = ".$id_comb."
                AND
                teamnumber = ".$teamnumber."
            ";

        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    static public function getArtefactIdByScopPrefixAndType($id_scope, $id_prefix, $id_type)
    {
        assert(is_int($id_scope) && $id_scope > 0);
        assert(is_int($id_prefix) && $id_prefix > 0);
        assert(is_int($id_type) && $id_type > 0);

        $common = self::getCommon();

        $sql = "
            SELECT
                a.*,
                (
                    SELECT
                        text
                    FROM
                        ds_localizedtextlanguage
                    WHERE
                        id_localizedtext = a.id_localizedname
                        AND
                        id_language = ".$_SESSION['lang']."
                ) AS localizedname
            FROM
                ds_artefact AS a
            WHERE
                a.id_artefactscope = $id_scope
                AND
                a.id_artefacttype = $id_type
                AND
                a.id_artefactprobability = $id_prefix
                AND
                a.can_be_found
            ";

        $res = $common->getResultArray("artefact_id_fo_scope_prefix_type_".$id_scope.$id_prefix.$id_type, $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        if(count($res) > 0)
        {
            return $res[0];
        }

        return array();
    }

    static public function getArtefactIdByName($artefactName)
    {
        assert(!empty($artefactName));

        $common = self::getCommon();

        $sql = "
            SELECT
                a.*,
                (
                    SELECT
                        text
                    FROM
                        ds_localizedtextlanguage
                    WHERE
                        id_localizedtext = a.id_localizedname
                        AND
                        id_language = ".$_SESSION['lang']."
                ) AS localizedname
            FROM
                ds_artefact AS a
            WHERE a.artefactname = '".$artefactName."'
            ";

        $res = $common->getResultArray("artefact_id_quest_by_name_".$artefactName, $sql, "", "", "GLOBAL_CACHE");

        if(count($res) > 0)
        {
            return $res[0];
        }

        return array();
    }

    static public function LogQuestArtefactSearch(array $eventParams, DE_Artefact $foundArtefact, $scopeProbability)
    {
        assert(key_exists('CountOfArcheologist', $eventParams));
        assert(key_exists('TimeToSearch', $eventParams));
        assert(key_exists('ID_Comb', $eventParams));
        assert(is_real($scopeProbability));

        $common = self::getCommon();

        $sql = "
            INSERT INTO ds_quest_artefact_search_log
                (id_artefact, creation_date, probability, archeologist_count, search_time, id_comb, id_user, id_artefactinstance)
            VALUES
                (
                 ".$foundArtefact->getArtefactID().",
                 now(),
                 $scopeProbability,
                 ".(int)$eventParams['CountOfArcheologist'].",
                 ".(int)$eventParams['TimeToSearch'].",
                 ".(int)$eventParams['ID_Comb'].",
                 ".(int)$_SESSION['uid'].",
                 ".$foundArtefact->getID()."
                )
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    static public function LogQuestSearch(array $eventParams, $probability, $amount, $localizedartefactName)
    {
        assert(key_exists('CountOfArcheologist', $eventParams));
        assert(key_exists('TimeToSearch', $eventParams));
        assert(key_exists('ID_Comb', $eventParams));
        assert(is_real($probability));
        assert(is_int($amount) && $amount > 0);
        assert(!empty($localizedartefactName));

        $common = self::getCommon();

        $sql = "
            INSERT INTO ds_quest
                (id_user, id_world, amount, found, id_comb, probability, archeologist_count, search_time)
            VALUES
                (
                 ".(int)$_SESSION['uid'].",
                 ".(int)$_SESSION['world'].",
                 ".$amount.",
                 '".pg_escape_string($localizedartefactName)."',
                 ".(int)$eventParams['ID_Comb'].",
                 ".$probability.",
                  ".(int)$eventParams['CountOfArcheologist'].",
                 ".(int)$eventParams['TimeToSearch']."
                )
            ";

        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    // Выбираем все квестовые юниты, котрые могуть быть найдены
    static public function getQuestUnitToSearch()
    {
        $common = self::getCommon();

        $sql = "
                SELECT
                    *
                FROM
                    ds_quest_unit
                WHERE
                    can_be_found = true
            ";

        $res = $common->getResultArray("quest_unit_can_be_found", $sql, "", "", "WORLD_CACHE");
        assert(empty($common->errormsg));

        if(count($res) > 0)
        {
            return $res;
        }

        return false;
    }

    static public function LogQuestUnitSearch(array $eventParams, $unitName, $scopeProbability)
    {
        assert(key_exists('CountOfArcheologist', $eventParams));
        assert(key_exists('TimeToSearch', $eventParams));
        assert(key_exists('ID_Comb', $eventParams));
        assert(is_real($scopeProbability));
        assert(!empty($unitName));

        $common = self::getCommon();

        $sql = "
            INSERT INTO ds_quest_unit_search_log
                (unitname, creation_date, probability, archeologist_count, search_time, id_comb, id_user)
            VALUES
                (
                 '".$unitName."',
                 now(),
                 $scopeProbability,
                 ".(int)$eventParams['CountOfArcheologist'].",
                 ".(int)$eventParams['TimeToSearch'].",
                 ".(int)$eventParams['ID_Comb'].",
                 ".(int)$_SESSION['uid']."
                )
            ";

        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    static public function addArtefacToComb($combId, $artefactId, $lifetime = 0)
    {
        assert(is_int($combId) && $combId > 0);
        assert(is_int($artefactId) && $artefactId > 0);
        assert(is_int($lifetime) && $lifetime >= 0);

        $common = self::getCommon();

        $sql = "SELECT
                        add_artefact_to_comb(".(int)$combId.", ".(int)$artefactId.", ".$lifetime. ")";

        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
}
?>