<?php
//RestoreArt.php
class TMP_RestoreDeletedArtefacts
{
	static public function Restore($dbID, $userName, GB_Date $fromDate, GB_Date $toDate)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(VD_UserName::Validate($userName));

		$sql = "
			select
				l.id_object,
				l.id_comb,
				l.artefact_death_time
			from
    			ds_log_delete_game_objects as l
    			inner join ds_comb as c using(id_comb)
    			inner join ds_combname as cn using (id_comb)
    			inner join ds_userinstance as ui on (ui.id_userinstance = c.id_userinstance)
    			inner join ds_user as u on (u.id_user = ui.id_user)
			where
				l.id_object_type = 1
				AND
				l.delete_date  > '".$fromDate->getPgFormat()."'
				AND
				l.delete_date  < '".$toDate->getPgFormat()."'
				AND 
				u.username = '$userName'
		";

		$common = DS_Common::CreateForCurentDB($dbID);
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		$common->startTrans();

		$counter = 0;

		foreach ($res as $k=>$art)
		{
			// Если арт не бессмертный
			if (!empty($art['artefact_death_time'])) {
				$immortal = "'f'";
				$birth_time = 'now()';
				$death_time = "'".$art['artefact_death_time']."'";
			} else {
				// Бессмертный
				$immortal = "'t'";
				$lifetime = 0;
				$birth_time = 'null';
				$death_time = 'null';
			}

			// ID artefactInstance
			$sql = "SELECT nextval('ds_artefactinstance_id_artefactinstance_seq') as art_id";
			$r = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			$artId = $r[0]['art_id'];
			assert($artId > 0);

			// Создаем инстанс артефакта
			$sql = "
			INSERT INTO ds_artefactinstance
			(id_artefactinstance, id_artefact, immortal, birth_time, death_time)
       		VALUES 
       		($artId, ".(int)$art['id_object'].", $immortal, $birth_time, $death_time);";
			$common->executeQuery($sql);
			assert(empty($common->errormsg));

			// добавляем его в хранилище
			$sql = "
				INSERT INTO ds_combhasartefact
				(id_comb, id_artefact)
    			VALUES 
    			(".(int)$art['id_comb'].", ".$artId.");";
			$common->executeQuery($sql);
			assert(empty($common->errormsg));

			$counter++;
		}

		GB_SingletonObjectStorage::CommitAllCommitable();
		$common->closeTrans('COMMIT');

		//$common->closeTrans('ROLLBACK');



		return $counter;
	}
}

?>