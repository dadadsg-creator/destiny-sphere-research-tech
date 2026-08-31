<?
require_once '../Main.php';
$common = DS_Common::CreateForCurentDB(2);

$sql = "select id_ring from ds_ring";
$resRings = $common->getResultArray("", $sql);
$sql = "begin";
$common->executeQuery($sql);
foreach ($resRings as $ring) {
    $id_ring = $ring['id_ring'];
    $sql = "select count(id_comb) as cnt, cc.id_combcomplex, cc.complexnumber, cc.id_complextype from ds_combcomplex cc
        left join ds_comb c on cc.id_combcomplex=c.id_combcomplex and c.id_userinstance is not null
        where cc.id_ring=$id_ring group by cc.id_combcomplex, cc.complexnumber, cc.id_complextype order by cc.complexnumber";
    $res = $common->getResultArray("", $sql);
    $emptyRows = array();
    foreach ($res as $row) {
        if ($row['cnt'] > 0) {
            $lastRow = array_shift($emptyRows);
            if ($lastRow == NULL) continue;
            $sql = "update ds_combcomplex set complexnumber=0, id_complextype={$lastRow['id_complextype']} where id_combcomplex={$row['id_combcomplex']}";
            $common->executeQuery($sql);
            if (!empty($common->errormsg)) {
                echo $common->errormsg;
                exit(0);
            }
            $sql = "update ds_combcomplex set complexnumber={$row['complexnumber']}, id_complextype={$row['id_complextype']} where id_combcomplex={$lastRow['id_combcomplex']}";
            $common->executeQuery($sql);
            if (!empty($common->errormsg)) {
                echo $common->errormsg;
                exit(0);
            }
            $sql = "update ds_combcomplex set complexnumber={$lastRow['complexnumber']}, id_complextype={$lastRow['id_complextype']} where id_combcomplex={$row['id_combcomplex']}";
            $common->executeQuery($sql);
            if (!empty($common->errormsg)) {
                echo $common->errormsg;
                exit(0);
            }
            $lastRow['complexnumber'] = $row['complexnumber'];
            array_push($emptyRows, $lastRow);
        }
        else {
            array_push($emptyRows, $row);
        }
    }
}
$sql = "commit";
$common->executeQuery($sql);
