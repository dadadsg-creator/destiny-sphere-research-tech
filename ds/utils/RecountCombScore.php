<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2007/10/04 08:43:42 $';
ini_set('memory_limit', '1024M');

require_once '../Main.php';
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';

foreach($GLOBALS['dbWorldAccess'] as $dbId => $db) 
{
    $common = DS_Common::CreateForCurentDB($dbId);
    $_SESSION['dbId'] = (int)$dbId;

    $sql = "select ab.id_comb, sum(b.building_score) as score from ds_attachedbuilding ab, ds_building b where b.id_building=ab.id_building group by ab.id_comb";
    $res = $common->getResultArray("", $sql);
    foreach($res as $row) {
        $sql = "update ds_comb set combscore=".$row['score']." where id_comb=".$row['id_comb'];
        $common->executeQuery($sql);
    }
    echo 'Done for DB '.$dbId.'<br>';
}
