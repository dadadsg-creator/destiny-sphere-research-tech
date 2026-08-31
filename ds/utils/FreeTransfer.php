<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/02/18 14:20:19 $';

require_once "../Main.php";
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';

ob_start();

$worlds = array();
foreach($GLOBALS['dbWorldAccess'] as $dbId => $db) 
{
	$common = DS_Common::CreateForCurentDB($dbId);
	$sql = "SELECT id_world FROM ds_world";
	$res = $common->getResultArray("", $sql, "", "", "");
	assert(empty($common->errormsg));

	foreach ($res as $k => $w)
	{
		/*$_SESSION['dbId'] = (int)$dbId;
		$_SESSION['world'] = (int)$w['id_world'];
		
		$sql = "
			SELECT
				value
			FROM
				ds_gamemodifier
			WHERE
				modifiername = 'FREE_RING_TRANSFER_ALLOWED'
				AND
				id_world = ".$w['id_world']."
			";
		$common->setDbId((int)$dbId);
		$res2 = $common->getResultArray("", $sql, "", "", "");
		
		$param = $res2[0]['value'];

		if($param == 1)
		{
			$worlds[] = array('db' => (int)$dbId, 'world' => (int)$w['id_world']);
		}*/
		
		$worlds[] = array('db' => (int)$dbId, 'world' => (int)$w['id_world']);
	}
}

echo "<pre>";

foreach ($worlds as $k => $w)
{
	$_SESSION['dbId'] = (int)$w['db'];
	$_SESSION['world'] = (int)$w['world'];
	SU_ExecFreeTransfer::Exec($w['db'], $w['world']);
}

echo "</pre>";
ob_end_clean();
//ob_flush();
?>