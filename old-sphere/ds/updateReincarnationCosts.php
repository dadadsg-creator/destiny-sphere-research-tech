<?
exit;
require_once ('Main.php');
$_SESSION['dbId'] = 3;
$common = DS_Common::CreateForCurrentDB();

$f = file("urcosts.csv", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($f as $s) {
    $t = explode(';', $s);
    $sql = "select id_unit from ds_unit where id_localizedname in
        (select id_localizedtext from ds_localizedtextlanguage where id_language=3 and text='".pg_escape_string($t[0])."')";
    $res = $common->getResultArray("", $sql);
    if (!count($res)) continue;
    $unitID = $res[0]['id_unit'];
    for ($i=1;$i<7;$i++) {
        if ($t[$i]=="") continue;
        $sql = "update ds_unit_reincarnation_cost set value={$t[$i]} where id_unit=$unitID and id_tradecourse=$i";
        echo '<br>'.$sql;
        $common->executeQuery($sql);
    }
}
?>
