<?php $CVS_Version = '$Revision$ ; $Date$';
ini_set('memory_limit', '128M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

$artPair = array(
	0 => array(1084, 1087),
	1 => array(1085, 1086)
);

$artTransform = array(
	0 => 1,
	1 => 2
);

echo "<xmp>";

foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
{
	$_SESSION["dbId"] = $dbId;
	
	$common = DS_Common::CreateForCurentDB($dbId);

	echo date("Y-d-m H:i:s")." --- START ARTEFACT REPLACEMENT IN DB [".$db['server']."] --- \n";

	$presentArts = '1084, 1085, 1086, 1087';

	$sql = "
		SELECT
			DISTINCT cha.id_comb,
			cn.combname,
			u.username
		FROM
			ds_combhasartefact AS cha
			INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
			INNER JOIN ds_combname AS cn ON (cn.id_comb = cha.id_comb)
			INNER JOIN ds_comb AS c ON (c.id_comb = cn.id_comb)
			INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = c.id_userinstance)
			INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
		WHERE
			ai.id_artefact IN ($presentArts)
	";
	$res = $common->getResultArray("", $sql, "", "", "");
	assert(empty($common->errormsg));

	echo "SELECTED ".count($res)." COMBS WITH PRESENT ARTEFACTS ($presentArts)\n";

	echo "--=============================================================--\n\n";

	$common->startTrans();

	foreach($res as $k => $combHasArt)
	{
		echo "--== ".date("H:i:s")." START REPLACEMENT ON COMB ".$combHasArt['combname']." (".$combHasArt['username'].") ==--\n";

		$presentCount = array();

		foreach ($artPair as $l => $pair)
		{
			$countArtInPair = array();

			foreach($pair as $m => $artID)
			{
				$sql = "
       				SELECT
       					count(cha.*) as count
        			FROM
        				ds_combhasartefact AS cha
        				INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
        			WHERE
            			ai.id_artefact = ".$artID."
        				AND
        				cha.id_comb = ".$combHasArt['id_comb']."
				";
				$res2 = $common->getResultArray("", $sql, "", "", "");
				assert(empty($common->errormsg));

				$countArtInPair[] = (int)$res2[0]['count'];
			}

			$presentCount[$l] = min($countArtInPair);

			echo "\tpairs for ".implode(",",$pair).": ".$presentCount[$l]."";
		}
		
		echo "\n";

		// get arts to delete
		foreach($presentCount as $l => $count)
		{
			if($count <= 0)
			{
				continue;
			}

			foreach ($artPair[$l] as $m => $artID)
			{
				$sql = "
					SELECT
						cha.*
					FROM
						ds_combhasartefact AS cha
       					INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
       				WHERE
           				ai.id_artefact = $artID
       					AND
       					cha.id_comb = ".$combHasArt['id_comb']."
       				LIMIT $count
				";
				$res2 = $common->getResultArray("", $sql, "", "", "");
				assert(empty($common->errormsg));

				$i = 0;

				foreach ($res2 as $n => $art)
				{
					$sql = "
						DELETE FROM 
							ds_combhasartefact
						WHERE 
							id_comb = ".$art['id_comb']."
							AND
							id_artefact = ".$art['id_artefact']."
					";
					$common->executeQuery($sql);
					assert(empty($common->errormsg));

					$sql = "
						DELETE FROM
							ds_artefactinstance
						WHERE
							id_artefactinstance = ".$art['id_artefact']."
					";
					$common->executeQuery($sql);
					assert(empty($common->errormsg));

					$i++;
				}
			}

			for($j=0; $j<$count; $j++)
			{
				// создаем артефакт инстанс
				$artefactInstance = DE_Artefact::CreateInDB((int)$artTransform[$l]);

				// добавляем его в хранилище
				$id = $artefactInstance->getID()."_".$combHasArt['id_comb'];
				$combArtefact = DE_CombHasArtefact::CreateInDB($id);

				$artefactInstance->commit();
				$combArtefact->commit();
			}
		}

		echo "--== ".date("H:i:s")." END REPLACEMENT ON COMB ".$combHasArt['combname']." ==--\n\n";
	}

	echo "--====================== DB [".$db['server']."] DONE =======================--\n\n";

	$common->closeTrans("COMMIT");
}
echo "</xmp>";

?>