<?php

class TN_Search
{
    static public function ResetGameModifiersToDefault()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "
            UPDATE 
                ds_gamemodifier
            SET
                value = gmt.value
            FROM
                ds_gamemodifiertemplate AS gmt
            WHERE
                ds_gamemodifier.modifiername = gmt.modifiername
                AND
                ds_gamemodifier.id_world = ".TN_TurnirController::GetWorldID()."                
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function DeleteDisabledBuildings()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "DELETE FROM ds_disabledbuilding WHERE id_world = ".TN_TurnirController::GetWorldID();
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function SetModifier($modiferName, $value)
    {
        assert(is_numeric($value));
        assert(!empty($modiferName));
        
        $common = TN_TurnirController::GetCommon();
        $sql = "UPDATE ds_gamemodifier SET value = $value WHERE modifiername = E'".pg_escape_string($modiferName)."' AND id_world = ".TN_TurnirController::GetWorldID();
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function AddDisabledBuilding($buildingID)
    {
        assert(is_int($buildingID) && $buildingID > 0);
        
        $common = TN_TurnirController::GetCommon();
        $sql = "INSERT INTO ds_disabledbuilding (id_world, id_building) VALUES (".TN_TurnirController::GetWorldID().", $buildingID)";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));   
    }
    
    static public function SetVocationEndTime($date)
    {
        assert($date instanceof GB_Date || is_null($date));
        
        if($date instanceof GB_Date)
        {
            $dateStr = "'".$date->getPGFormat()."'";
        } else {
            $dateStr = 'null';
        }
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            UPDATE
                ds_vocationmode
            SET
                enddate = ".$dateStr."
            WHERE
                id_userinstance IN
                (
                    SELECT
                        ui.id_userinstance
                    FROM
                        ds_userinstance AS ui
                    WHERE
                        ui.id_world = ".TN_TurnirController::GetWorldID()."
                );";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function SetLastRefresh($date)
    {
        assert(($date instanceof GB_Date) || is_null($date));
        
        if(is_null($date))
        {
            $date = 'null';
        } else {
            $date = "'".$date->getPgFormat()."'";
        }
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            UPDATE
                ds_userinstance
            SET
                lastrefresh = ".$date."
            WHERE
                id_userinstance IN
                (
                    SELECT
                        ui.id_userinstance
                    FROM
                        ds_userinstance AS ui
                    WHERE
                        ui.id_world = ".TN_TurnirController::GetWorldID()."
                );
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function SetWorldStatus($status)
    {
        assert(!empty($status));
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            UPDATE
                ds_world
            SET
                world_status = (
                    SELECT
                        id_worldstatus
                    FROM
                        ds_worldstatus
                    WHERE
                        status_name = E'".pg_escape_string($status)."'
                )
            WHERE
                id_world = ".TN_TurnirController::GetWorldID()."
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function GetUsernameByEmail($email)
    {
        assert(VD_Email::Validate($email));
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                username
            FROM
                ds_user
            WHERE
                upper(email) = upper('".$email."')
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            return $res[0]['username'];
        }
        
        return '';
    }
    
    static public function GetCountInstances()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "SELECT count(*) FROM ds_userinstance WHERE id_world = ".TN_TurnirController::GetWorldID();
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return (int)$res[0]['count'];
    }
    
    static public function GetAllInstances()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "SELECT id_userinstance FROM ds_userinstance WHERE id_world = ".TN_TurnirController::GetWorldID();
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetTemplates()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                *
            FROM
                ds_turnir_templates
            ";
        $res = $common->getResultArray('', $sql, '', '', '');
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    
    static public function GetNotStartedTurnirs()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                *
            FROM
                ds_turnir
            WHERE
                end_date IS NULL
                AND
                start_date IS NULL
            ORDER BY id_turnir DESC
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetNotFinishedTurnirs()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                *
            FROM
                ds_turnir
            WHERE
                end_date IS NULL
                AND
                NOT start_date IS NULL
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
    
        return $res;
    }
    
    static public function GetTurnirByName($turnitName)
    {
        assert(!empty($turnitName));
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                *
            FROM
                ds_turnir
            WHERE
                turnir_name = '$turnitName'
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
    
        return $res;
    }
    
    static public function GetFinishedTurnirs()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                t.id_turnir, 
                CASE
                    WHEN t.id_turnir = 0 THEN NULL
                    ELSE t.start_date
                    END as start_date,
                 CASE
                    WHEN t.id_turnir = 0 THEN NULL
                    ELSE  t.end_date
                    END as  end_date,
                t.comments,
                t.turnir_name
            FROM
                ds_turnir t
            WHERE
                NOT end_date IS NULL
                AND
                NOT start_date IS NULL
            ORDER BY id_turnir DESC
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }

    static public function InsertNewTurnir($template, $comment = '', $turnir_name)
    {
        assert(is_int($template) && $template > 0);
        assert(!empty($turnir_name));
        
        $common = TN_TurnirController::GetCommon();
        
        $turnirID = (int)DS_Base::getNextSeqVal($common, 'ds_turnir_id_turnir_seq');
        
        $sql = "
            INSERT INTO ds_turnir
                (id_turnir, id_turnir_template, start_date, end_date, comments, turnir_name)
            VALUES
                ($turnirID, $template, now(), null, E'".pg_escape_string($comment)."',  E'".pg_escape_string($turnir_name)."')
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        return $turnirID;
    }
    
    static public function DeleteTurnir($id)
    {
        assert(is_int($id) && $id > 0);
        
        $common = TN_TurnirController::GetCommon();
        $sql = "DELETE FROM ds_turnir WHERE id_turnir = $id";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function SetTurnirStartDate($turnirID, GB_Date $startDate)
    {
        assert(is_int($turnirID) && $turnirID > 0);
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            UPDATE 
                ds_turnir
            SET 
                start_date = '".$startDate->getPgFormat()."'
            WHERE
                id_turnir = ".$turnirID."
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function SetTurnirEndDate($turnirID)
    {
        assert(is_int($turnirID) && $turnirID > 0);
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            UPDATE 
                ds_turnir
            SET 
                end_date = now()
            WHERE
                id_turnir = ".$turnirID."
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function SaveTurnirRanking($turnirID, $worldIdD)
    {
        assert(is_int($turnirID) && $turnirID > 0);
        assert(is_int($worldIdD) && $worldIdD > 0);
        
        $common = TN_TurnirController::GetCommon();
        
        $sql = "
               INSERT INTO ds_turnir_result_history
            (id_turnir, username, battle_xp, rating, 
            buildingsum, sciencesum, productionranking,
            artefactsum, armysum)
            SELECT
                $turnirID, ra.username, COALESCE(ubx.absolute_value, 0), ra.thefinal,
                COALESCE(br.buildingsum, 0) as buildingsum,
        COALESCE(sr.sciencesum, 0) as sciencesum,
        COALESCE(pr.finalranking, 0) as productionranking,
        COALESCE(ar.artefactsum, 0) as artefactsum,
        COALESCE(amr.armysum, 0) as armysum
           FROM
                ds_finalranking ra
       LEFT JOIN ds_battlexp ubx ON ( ra.id_userinstance = ubx.id_userinstance )
       LEFT JOIN subqry_buildingranking_final br ON (ra.id_userinstance = br.id_userinstance)
       LEFT JOIN subqry_scienceranking_final sr ON (ra.id_userinstance = sr.id_userinstance)
       LEFT JOIN subqry_productionranking_final pr ON (ra.id_userinstance = pr.id_userinstance)
       LEFT JOIN subqry_artefactranking_final ar ON (ra.id_userinstance = ar.id_userinstance)
       LEFT JOIN subqry_armyranking_final amr ON (ra.id_userinstance = amr.id_userinstance)
               WHERE 
                  ra.id_world = $worldIdD";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function GetAllTurnirs()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "
            select
                t.*,
                tt.descr,
                (
                    select
                        count(*)
                    from
                        ds_turnir_result_history as trh
                    where
                        trh.id_turnir = t.id_turnir
                ) as count_result,
                (
                    select
                        count(*)
                    from
                        ds_userinstance as ui
                    where
                        ui.id_world = ".TN_TurnirController::GetWorldID()."
                ) as count_ui
            from
                ds_turnir as t
                inner join ds_turnir_templates as tt using (id_turnir_template)
            order by t.id_turnir
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetTurnirByID($turnirID)
    {
        assert(is_int($turnirID) && $turnirID > 0);
        $common = TN_TurnirController::GetCommon();
        
        $sql = "
            SELECT
                t.*,
                date_trunc('minute', t.start_date) as trunc_start_date,
                date_trunc('minute', t.end_date) as trunc_end_date,
                tt.descr
            FROM
                ds_turnir AS t
                inner join ds_turnir_templates as tt using (id_turnir_template)
            WHERE
                t.id_turnir = $turnirID
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        if(count($res) == 0)
        {
            return array();
        }
        
        return $res[0];
    }
    
    static public function GetTopUsers($turnirID, $limit = 30)
    {
        assert(is_int($turnirID) && $turnirID > 0);
        assert(is_int($limit) && $limit > 0);
        $common = TN_TurnirController::GetCommon();
        
        $sql = "
            SELECT
                *
            FROM
                ds_turnir_result_history
            WHERE
                id_turnir = $turnirID
            ORDER BY battle_xp DESC
            LIMIT $limit
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function MarkUserAsRecievedPrize($turnirID, $userName, $prize)
    {
        assert(is_int($turnirID) && $turnirID > 0);
        assert(is_int($prize) && $prize > 0);
        assert(VD_UserName::Validate($userName));
        $common = TN_TurnirController::GetCommon();
        
        $sql = "
            UPDATE 
                ds_turnir_result_history
            SET
                prize_recieved = $prize
            WHERE
                id_turnir = $turnirID
                AND
                username = '$userName'
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function GetTurnirResults($turnirID, $sort, $limit=0, $offset=0)
    {
        assert(is_int($turnirID) && $turnirID >= 0);
        assert(!empty($sort));

        $common = TN_TurnirController::GetCommon();
        
        if ($turnirID == 0) {
            // Если нам надо вывести суммарный рейтинг
            $sql = "
            SELECT
                username,
                sum(battle_xp) as battle_xp,
                sum(rating) as rating,
                '' as prize_recieved
            FROM
                ds_turnir_result_history
            GROUP BY username
            ORDER BY $sort DESC
            ";
        } else {
            $sql = "
            SELECT
                username,
                battle_xp,
                rating,
                prize_recieved
            FROM
                ds_turnir_result_history
            WHERE
                id_turnir = $turnirID
            ORDER BY $sort DESC
            ";
        }
        
        if ($limit != 0) {
            $sql .= "
            LIMIT $limit OFFSET $offset";
        }
        $res = $common->getResultArray("turnir_ranking_".$turnirID."s_".$sort."l_".$limit."_o_".$offset, $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetTurnirDetailResults($turnirID, $sort,  $limit=0, $offset=0)
    {
        assert(is_int($turnirID) && $turnirID >= 0);
        assert(!empty($sort));
        

        $common = TN_TurnirController::GetCommon();
        
        if ($turnirID == 0) {
            // Если нам надо вывести суммарный рейтинг
            $sql = "
            SELECT
                username,
                sum(COALESCE(buildingsum, 0)) as buildingsum,
                sum(COALESCE(sciencesum, 0)) as sciencesum,
                sum(COALESCE(productionranking, 0)) as productionranking,
                sum(COALESCE(artefactsum, 0)) as artefactsum,
                sum(COALESCE(armysum, 0)) as armysum
            FROM
                ds_turnir_result_history
            GROUP BY username
            ORDER BY ".$sort." DESC
            ";
        } else {
            $sql = "
            SELECT
                *
            FROM
                ds_turnir_result_history
            WHERE 
                id_turnir = $turnirID
            ORDER BY ".$sort." DESC
            ";
        }
        
        if ($limit != 0) {
            $sql .= "
            LIMIT $limit OFFSET $offset";
        }
        
        $res = $common->getResultArray("turnir_detail_ranking_".$turnirID."s_".$sort."l_".$limit."_o_".$offset, $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetLastTurnirID()
    {
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                id_turnir
            FROM
                ds_turnir
            ORDER BY end_date DESC
            LIMIT 1
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        if(count($res) == 0)
        {
            return 0;
        }
        
        return (int)$res[0]['id_turnir'];
    }
    
    static public function UpdateCombTemplate($template)
    {
        assert(!empty($template));
        assert(preg_match('/^[a-Z0-9_]+$/', $template));
        $common = TN_TurnirController::GetCommon();
        $sql = "UDPATE ds_global_settings SET value = '$template' WHERE settings_name = 'COMB_TEMPLATE'";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function AddTurnirTemplate($name, $descr)
    {
        assert(!empty($name));
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            INSERT INTO ds_turnir_templates
                (template_name, descr)
            VALUES
                (E'".pg_escape_string($name)."', E'".pg_escape_string($descr)."')
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function DeleteTemplate($templateID)
    {
        assert(is_int($templateID) && $templateID > 0);
        $common = TN_TurnirController::GetCommon();
        $sql = "DELETE FROM ds_turnir_templates WHERE id_turnir_template = $templateID";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function GetAllModifiers($templateID)
    {
        assert(is_int($templateID) && $templateID > 0);
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                gmt.modifiername,
                COALESCE(ttm.value, gmt.value::varchar) AS value,
                COALESCE(ttm.value, '-') AS ttm_value,
                gmt.description,
                COALESCE(mt.typename, '_OUT_OF_GROUP') as typename
            FROM
                ds_gamemodifiertemplate AS gmt
                LEFT JOIN ds_gamemodifiertype AS mt USING (id_modifiertype)
                LEFT JOIN ds_turnir_template_modifier AS ttm ON (ttm.modifiername = gmt.modifiername AND ttm.id_turnir_template = $templateID)
            ORDER BY typename, modifiername
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function CheckTemplateModifier($modifierName, $value)
    {
        assert(preg_match('/^[A-Za-z_0-9 ]+$/', $modifierName));
        assert(is_numeric($value));
        
        $common = TN_TurnirController::GetCommon();
        $sql = "SELECT count(*) FROM ds_gamemodifiertemplate WHERE modifiername = '$modifierName' AND value = '$value'";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        if($res[0]['count'] == 0)
        {
            return true;
        }
        
        return false;
    }


    static public function InsertOrUpdateTemplateModifier($modifierName, $templateID, $value)
    {
        assert(is_int($templateID) && $templateID > 0);
        assert(preg_match('/^[[A-Za-z_0-9 ]+$/', $modifierName));
        assert(is_numeric($value));

        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                count(*)
            FROM
                ds_turnir_template_modifier AS ttm
            WHERE
                modifiername = '$modifierName'
                AND
                id_turnir_template = $templateID
            ";  
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        if($res[0]['count'] > 0)
        {
            $sql = "
                UPDATE ds_turnir_template_modifier 
                SET value = '$value' 
                WHERE
                    modifiername = '$modifierName'
                    AND
                    id_turnir_template = $templateID
                ";
        } else {
            $sql = "
                INSERT INTO ds_turnir_template_modifier
                    (id_turnir_template, modifiername, value)
                VALUES
                    ($templateID, '$modifierName', '$value')
                ";
        }
        
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    
    static public function GetNotDisabledBuildings($templateID)
    {
        assert(is_int($templateID) && $templateID > 0);
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                b.id_building,
                ARRAY_TO_STRING(
                    ARRAY
                    (
                        SELECT
                            DISTINCT ltl.text
                        FROM
                            ds_buildingcanbuildbyrace AS bcbr
                            INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = bcbr.id_localizedname)
                        WHERE
                            ltl.id_language = 3
                            AND
                            bcbr.id_building = b.id_building
                    ),
                    ', '
                ) as name
            FROM
                ds_building AS b
            WHERE
                NOT EXISTS
                (
                    SELECT
                        *
                    FROM
                        ds_turnir_disabled_buildings AS db
                    WHERE
                        db.id_turnir_template = $templateID
                        AND
                        db.id_building = b.id_building
                )
                AND
                b.id_building > 0
            ORDER BY id_building
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetDisabledBuildings($templateID)
    {
        assert(is_int($templateID) && $templateID > 0);
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                b.id_building,
                ARRAY_TO_STRING(
                    ARRAY
                    (
                        SELECT
                            DISTINCT ltl.text
                        FROM
                            ds_buildingcanbuildbyrace AS bcbr
                            INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = bcbr.id_localizedname)
                        WHERE
                            ltl.id_language = 3
                            AND
                            bcbr.id_building = b.id_building
                    ),
                    ', '
                ) as name
            FROM
                ds_turnir_disabled_buildings AS b
            WHERE
                b.id_turnir_template = $templateID
            ORDER BY id_building
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function AddDisabledBuildingToTemplate($templateID, $buildingID)
    {
        assert(is_int($templateID) && $templateID > 0);
        assert(is_int($buildingID) && $buildingID > 0);
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            INSERT INTO ds_turnir_disabled_buildings
                (id_turnir_template, id_building)
            VALUES
                ($templateID, $buildingID)
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function RemoveDisabledBuildingFromTemplate($templateID, $buildingID)
    {
        assert(is_int($templateID) && $templateID > 0);
        assert(is_int($buildingID) && $buildingID > 0);
        
        $common = TN_TurnirController::GetCommon();
        $sql = "
            DELETE FROM ds_turnir_disabled_buildings
            WHERE
                id_turnir_template = $templateID
                AND
                id_building = $buildingID
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function GetTemplateIDByName($templateName)
    {
        assert(!empty($templateName) && pregmatch('/^[a-zA-Z_ -]+$/', $templateName));
        $common = TN_TurnirController::GetCommon();
        $sql = "SELECT id_turnir_template FROM ds_turnir_template WHERE template_name = E'".pg_escape_string($templateName)."'";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg) && count($res) == 1);
        
        return (int)$res[0]['id_turnir_template'];
    }
    
    static public function GetTemplateModifiers($templateID)
    {
        assert(is_int($templateID) && $templateID > 0);
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                modifiername, value
            FROM
                ds_turnir_template_modifier
            WHERE
                id_turnir_template = $templateID
            ";
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res;
    }
    
    static public function GetTemplateDisabledBuildings($templateID)
    {
        assert(is_int($templateID) && $templateID > 0);
        $common = TN_TurnirController::GetCommon();
        $sql = "
            SELECT
                *
            FROM 
                ds_turnir_disabled_buildings
            WHERE
                id_turnir_template = $templateID
            ";  
        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res;    
    }
    
    static public function GetTemplateList()
    {
        $handle = opendir($_SERVER['DOCUMENT_ROOT'].'/ds/UI_Tamplates/');
        
        if(!$handle)
        {
            return array();
        }

        $res = array();
        
        while(false !== ($file = readdir($handle))) 
        {
            if(false !== ($pos = strpos($file, '_race')))
            {
                $name = substr($file, 0, $pos);
                if(!in_array($name, $res))
                {
                    $res[] = $name;
                }
            }
        }
        
        closedir($handle);
        
        return $res;
    }
    
    /*
    * Получаем шаблон для конкретного мира и конкретной рассы
    *
    *
    */
    static public function GetCombTemplate($worldId, $idRace)
    {
        //$common = TN_TurnirController::GetCommon();
        $common = DS_Common::CreateForCurrentDB();
        $sql = "
            SELECT 
                    * 
                FROM 
                    ds_world_template
                WHERE 
                    id_world =  $worldId
                    AND
                    id_race = $idRace";

        $res = $common->getResultArray("", $sql);
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res;    
    }
    
}


?>