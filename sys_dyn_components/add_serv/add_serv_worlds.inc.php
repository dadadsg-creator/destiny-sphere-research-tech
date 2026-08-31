<?php
require_once("./ds/Main.php");
$common = DS_Common::CreateForCurentDB(0);
$sql = "
	SELECT
		dbname||id_world as id,
		worldname
	FROM
		ds_worldcollection
	WHERE
		world_status != 5
	";
$res = $common->getResultArray("", $sql, "", "", "");

foreach ($res as $k => $w)
{
	$worlds[$w['id']] = $w['worldname'];
}

unset($res);

$str = '';
	
foreach ($worlds as $k => $world)
{
	$str .= "<option value=$k>$world\n";		
}
	
$htmlWorldsList = $str;

unset($str);

?>