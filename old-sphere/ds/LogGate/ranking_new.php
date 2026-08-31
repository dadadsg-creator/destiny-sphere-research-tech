<?php $CVS_Version = '$Revision$ ; $Date$';
ini_set('memory_limit', '64M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регитрация финализатора
register_shutdown_function ('finilazer');

assert(key_exists('idw', $_REQUEST));
$world = $_REQUEST['idw'];

$common = DS_Common::CreateForCurrentDB(0);

$sql = "SELECT dbname, id_world
    FROM ds_worldcollection
    WHERE world_identifier = '$world'";
$res = $common->getResultArray("", $sql, "", "", "");
assert(empty($common->errormsg));
assert(count($res) == 1);

$id_world = $res[0]['id_world'];
$dbId = 0;
$dbName = $res[0]['dbname'];
foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
{
    if($db['server'] == $dbName)
    {
        $common->setDbId($dbId);
        break;
    }
}

$sql = "SELECT COALESCE(
            (
            COALESCE(r.science_ranking, 0)
            + 
            COALESCE(r.artefact_ranking, 0)
            + 
            COALESCE(r.building_ranking, 0)
            + 
            COALESCE(b.absolute_value, 0)*100
            ), 0)::BIGINT as thefinal,
            COALESCE(r.artefact_ranking, 0)::BIGINT as artefactranking,
            COALESCE(r.building_ranking, 0)::BIGINT as buildingranking,
            COALESCE(r.science_ranking, 0)::BIGINT as scienceranking,
            u.username,
            COALESCE(b.absolute_value, 0) as bo,
            a.alliancename,
            ui.id_userinstance,
            lg.league_name,
            r.id_level,
            COALESCE(value, 0) as battlexp,
            COALESCE(absolute_value, 0) as battlexpabs,
            a.alliancetag,
            l.text as racename,
            a.id_alliance,
            rac.id_race
    FROM 
        ds_userinstance ui
    LEFT JOIN ds_user u ON (ui.id_user = u.id_user)
    LEFT JOIN ds_ranking r ON (r.id_userinstance = ui.id_userinstance)
    LEFT JOIN ds_battlexp b ON (b.id_userinstance = ui.id_userinstance)
    LEFT JOIN ds_userhasalliance uha ON (uha.id_userinstance = ui.id_userinstance)
    LEFT JOIN ds_alliance a ON (a.id_alliance = uha.id_alliance)
    LEFT JOIN ds_race rac ON (rac.id_race = ui.id_race)
    LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = rac.id_localizedname AND l.id_language = ".constant('DEFAULT_LANGUAGE').")
    LEFT JOIN ds_league lg ON (lg.id_league = ui.id_league)
    WHERE
        ui.id_world = $id_world
        AND (u.npc is null or u.npc=false)
    order by thefinal desc";

$res = $common->getResultArray("", $sql, "", "", "");

$i = 1;
foreach ($res as $row) {
    $username = str_replace('"','""', $row['username']);
    $alliancename = str_replace('"','""', $row['alliancename']);
    echo "$i,\"$username\",{$row['thefinal']},{$row['artefactranking']},{$row['buildingranking']},{$row['scienceranking']},{$row['bo']},\"$alliancename\",{$row['id_userinstance']},{$row['league_name']},{$row['id_level']},{$row['battlexp']},{$row['battlexpabs']},{$row['alliancetag']},{$row['racename']},{$row['id_alliance']},{$row['id_race']}\n";
    $i++;
}

?>