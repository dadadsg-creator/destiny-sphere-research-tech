<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

define('PAID_ARTEFACTS', 1);

if (!isset($GLOBALS["common"])) {
	$common = & ClassFactory::getInstance("DS_Common");
	$common->connect();
}

/*-----------------------------------------------------------------------------------------------*/

/**
 * ¬озвращает список всех миров их всех баз
 * @return array
 */
function GetAllWorldsList() {
	$common = & ClassFactory::getInstance("DS_Common");
	$common->connect();
	$out = array();
	foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData) {
		$common->setDbId((int)$dbid);
		$sql = "SELECT id_world, worldname FROM ds_world";
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		foreach($res as $k=>$v) {
			$v['dbid'] = $dbid;
			$out[] = $v;
		}
	}
	return $out;
}

/*-----------------------------------------------------------------------------------------------*/

$worlds = GetAllWorldsList();

if(isset($_SESSION['pay_art_world']) && isset($_SESSION['pay_art_db'])) {
	$world_id = $_SESSION['pay_art_world'];
	$dbId =  $_SESSION['pay_art_db'];
}
else {
	$world_id = $worlds[0]['id_world'];
	$dbId =  $worlds[0]['dbid'];
}

/*-----------------------------------------------------------------------------------------------*/

if(isset($_REQUEST['action']) && !empty($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'switch_world':
			if(isset($_REQUEST['db_world']) && !empty($_REQUEST['db_world'])) {
				list($dbId, $world_id) = explode("_", $_REQUEST['db_world']);
				$_SESSION['pay_art_world'] = $world_id;
				$_SESSION['pay_art_db'] = $dbId;
			}
			break;
	}
}


require_once 'view.php';

?>