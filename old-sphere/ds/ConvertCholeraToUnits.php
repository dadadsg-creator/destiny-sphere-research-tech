<?php
//ConvertCholeraToUnits.php

ini_set('memory_limit', '50M');
set_time_limit(0);


require_once('Main.php');
echo '<pre>';
foreach($GLOBALS['dbWorldAccess'] as $k=>$v)
{
	echo $k.'BEGIN<br>';

	$common = DS_Common::CreateForCurentDB($k);
	$_SESSION['dbId'] = $k;
	
if ($k == 0) {
	continue;
}
	// выбираем id мира
	$sql = "
			SELECT
				id_world
			FROM
				ds_world
			";
	$worlds = $common->getResultArray("", $sql, "", "", "");
	assert(empty($common->errormsg));

	foreach ($worlds as $k1 => $world)
	{


		$worldId = (int)$world['id_world'];
		assert($worldId > 0);


		TMP_ConvertCholeraToUnits::Exec($k, $worldId);
	}


	echo $k.'END<br><br>';
}


echo "END!";

?>DE_Unit