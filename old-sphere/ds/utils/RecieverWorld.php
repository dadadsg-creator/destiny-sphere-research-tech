<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2007/10/04 08:43:42 $';
ini_set('memory_limit', '128M');

require_once '../Main.php';
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';


$worlds = array();
foreach($GLOBALS['dbWorldAccess'] as $dbId => $db) 
{
    $common = DS_Common::CreateForCurentDB($dbId);
    $_SESSION['dbId'] = (int)$dbId;

    $sql = "SELECT id_world FROM ds_world";
    $res = $common->getResultArray("", $sql, "", "", "");
    assert(empty($common->errormsg));

    foreach ($res as $k => $w)
    {
        $_SESSION['world'] = (int)$w['id_world'];
        
        $sql = "
            SELECT
                value
            FROM
                ds_gamemodifier
            WHERE
                modifiername = 'WORLD_TRANSFER_ENABLE_AUTO_TRANSFER'
                AND
                id_world = ".$w['id_world']."
            ";
        $common->setDbId((int)$dbId);
        $res2 = $common->getResultArray("", $sql, "", "", "");
        
        $param = $res2[0]['value'];

        if($param == 1)
        {
            $worlds[] = array('db' => (int)$dbId, 'world' => (int)$w['id_world']);
        }
    }
}

foreach ($worlds as $k => $w)
{
    $_SESSION['dbId'] = (int)$w['db'];
    $_SESSION['world'] = (int)$w['world'];
    $rw = new SU_RecieverWorldTransfers($w['db'], $w['world']);
    $rw->Exec();
}
?>