<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2007/10/04 08:43:42 $';
ini_set('memory_limit', '128M');

require_once '../Main.php';
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';

foreach($GLOBALS['dbWorldAccess'] as $dbId => $db) 
{
    $common = DS_Common::CreateForCurentDB($dbId);
    $_SESSION['dbId'] = (int)$dbId;

    $sql = "
        insert into ds_ranking_archive (date, id_user, total, level, archeology, building, science, battle, npc)
        SELECT
            current_date,
            ui.id_user,
            COALESCE((
                COALESCE(r.science_ranking, 0) + 
                COALESCE(r.artefact_ranking, 0) + 
                COALESCE(r.building_ranking, 0) + 
                COALESCE(b.absolute_value, 0)*100
            ), 0),
            r.id_level,
            COALESCE(r.artefact_ranking, 0),
            COALESCE(r.building_ranking, 0),
            COALESCE(r.science_ranking, 0),
            COALESCE(b.absolute_value, 0),
            COALESCE(b.npc_xp, 0)
        FROM ds_userinstance ui
        LEFT JOIN ds_ranking r ON (r.id_userinstance = ui.id_userinstance)
        LEFT JOIN ds_battlexp b ON (b.id_userinstance = ui.id_userinstance)";
    $common->executeQuery($sql);

    $users = array();

    $sql = "select id_user from ds_ranking_archive where date=current_date order by total desc";
    $res = $common->getResultArray( "", $sql, "", "", "");
    $i = 1;
    foreach ($res as $val) {
        $users[$val['id_user']]['total'] = $i;
        $i++;
    }
    $sql = "select id_user from ds_ranking_archive where date=current_date order by archeology desc";
    $res = $common->getResultArray( "", $sql, "", "", "");
    $i = 1;
    foreach ($res as $val) {
        $users[$val['id_user']]['arch'] = $i;
        $i++;
    }
    $sql = "select id_user from ds_ranking_archive where date=current_date order by building desc";
    $res = $common->getResultArray( "", $sql, "", "", "");
    $i = 1;
    foreach ($res as $val) {
        $users[$val['id_user']]['build'] = $i;
        $i++;
    }
    $sql = "select id_user from ds_ranking_archive where date=current_date order by science desc";
    $res = $common->getResultArray( "", $sql, "", "", "");
    $i = 1;
    foreach ($res as $val) {
        $users[$val['id_user']]['science'] = $i;
        $i++;
    }
    $sql = "select id_user from ds_ranking_archive where date=current_date order by battle desc";
    $res = $common->getResultArray( "", $sql, "", "", "");
    $i = 1;
    foreach ($res as $val) {
        $users[$val['id_user']]['battle'] = $i;
        $i++;
    }
    $sql = "select id_user from ds_ranking_archive where date=current_date order by npc desc";
    $res = $common->getResultArray( "", $sql, "", "", "");
    $i = 1;
    foreach ($res as $val) {
        $users[$val['id_user']]['npc'] = $i;
        $i++;
    }
    foreach ($users as $id_user=>$places) {
        $sql = "insert into ds_ranking_archive_places (date, id_user, total, archeology, building, science, battle, npc)
            values (current_date, $id_user, {$places['total']}, {$places['arch']}, {$places['build']}, {$places['science']}, {$places['battle']}, {$places['npc']})";
        $common->executeQuery($sql);
    }
}
?>




