<?php
//CopyComb2.php

ini_set('memory_limit', '50M');
set_time_limit(0);

require_once 'Main.php';

class CopyComb
{
	static function Exec()
	{
		$acauntFrom = 'CTPELOK777';
		$cobmFrom = 'MimicryTower24';
		
		$acauntTo = 'Annita22222';
		$cobmbTo= 'MimicryTower60';
		
		// Весы
		$dbIdFrom = 0;
		$worldIdFrom = 9;
		
		// Телец3
		$dbIdTo = 0; //3;
		$worldIdTo = 9; //2;

		// Коннектимся к миру игрока, с которого будем копировать соту
		$common = DS_Common::CreateForCurentDB($dbIdFrom);
		$_SESSION['dbId'] = $dbIdFrom;


		// Получаем юзеринстанс игрока
		$sql =
		"
				SELECT
				      ui.*, u.email
				FROM
				    ds_user u
				LEFT JOIN     
				     ds_userinstance ui USING (id_user)
				WHERE
				     u.username = '$acauntFrom'
				     AND
				     ui.id_world = $worldIdFrom;
				";

		$res = $common->getResultArray('', $sql, '', '', '');
//SU_DebugTools::LogAll($sql, 'AnnitaCopyComb.log');
		assert(empty($common->errormsg));

		$UIID = (int)$res[0]['id_userinstance'];
		assert($UIID > 0);

		$userId = (int)$res[0]['id_user'];
		assert($UIID > 0);
		
		$userMail = $res[0]['email'];
		assert(!empty($userMail));

		// Сохраняем игрока
		SI_ExecStoreUser::SetCommon($common);
		$storeUserInstance = SI_ExecStoreUser::Exec($dbIdFrom, $UIID, $worldIdFrom, 1, 'admin', 'copy comb', '0000-00-00 00:00:00', 0);
		$id_storeuserdata = SI_ExecStoreUser::GetUserDataId();

		// Получаем сохраненные данные игрока
		$sql =
		"
				SELECT
				      *
				FROM
				    ds_storeuserinstancedata
				WHERE
				     id_storeuserdata = $id_storeuserdata;
				";

		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));

		$userInstanceData = $res[0]['userinstance_data'];

		$sql =
		"
				SELECT
				      *
				FROM
				    ds_storeusercombs
				WHERE
				     id_storeuserdata = $id_storeuserdata;
				";

		$storeUserComb = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		
			$newUserCombCoords = "4.75.3";
			
			// Получаем Id нового игрока
			$common = DS_Common::CreateForCurentDB(0);
			$_SESSION['dbId'] = 0;

			$sql = "SELECT * FROM ds_user WHERE username = '$acauntTo'";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));


			$newIdUser = (int)$res[0]['id_user'];
			assert($newIdUser > 0);
			
			// Получаем следующий айдишник сохраненных данных игрока
			$sql = "SELECT nextval('ds_storeuserdata_id_storeuserdata_seq')";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			$newIdStoreuserdata = (int)$res[0]['nextval'];
			assert($newIdStoreuserdata > 0);

			// Формируем новые данные
			$newUserinstanceData = str_replace('s:7:"id_user";s:'.strlen($userId).':"'.$userId.'"', 's:7:"id_user";s:'.strlen($newIdUser).':"'.$newIdUser.'"', $userInstanceData);
			$newUserinstanceData = str_replace('username";s:'.strlen($acauntFrom).':"'.$acauntFrom.'"', 'username";s:'.strlen($acauntTo).':"'.$acauntTo.'"', $newUserinstanceData);
			$newUserinstanceData = str_replace('combs";a:1:{s:'.strlen($cobmFrom).':"'.$cobmFrom.'";', 'combs";a:1:{s:'.strlen($cobmbTo).':"'.$cobmbTo.'";', $newUserinstanceData);
			$newUserinstanceData = str_replace('combName";s:'.strlen($cobmFrom).':"'.$cobmFrom.'";', 'combName";s:'.strlen($cobmbTo).':"'.$cobmbTo.'";', $newUserinstanceData);
			$newUserinstanceData = str_replace('email";s:'.strlen($userMail).':"'.$userMail.'";', 'email";s:'.strlen($acauntTo).':"'.$acauntTo.'";', $newUserinstanceData);

			// Записываем новые данные
			$sql =
			"
					INSERT INTO
						ds_storeuserdata
						(id_storeuserdata, id_user, db_name_from, id_world_from, world_from_name, store_admin_name, store_date, id_storeuserreason, comments)
					VALUES
					    ($newIdStoreuserdata, $newIdUser, 'test', 1, 'test', 'admin', now(), 1, 'quest comb')
					";

			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			$sql =
			"
					INSERT INTO
						ds_storeuserinstancedata
						(id_storeuserdata, userinstance_data)
					VALUES
					    ($newIdStoreuserdata, '$newUserinstanceData')
					";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));			

			$sql =
			"
					INSERT INTO
						ds_storeusercombs
						(id_storeuserdata, id_comb_old, comb_name_old, comb_name_new, coordinates_new, coordinates_old)
					VALUES
					    ($newIdStoreuserdata, ".$storeUserComb[0]['id_comb_old'].", '".$storeUserComb[0]['comb_name_old']."', '$cobmbTo', '$newUserCombCoords', '".$storeUserComb[0]['coordinates_old']."')
					";

			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			// Восстанавливаем игрока
			SI_ExecRestoreUser::SetCommon($common);
			SI_ExecRestoreUser::Exec($newIdStoreuserdata, $dbIdTo, $worldIdTo, false, 'admin', false);

			echo "<br>-------------USER <b>$acauntTo</b> CREATED-------------<br><br>";
		}
}

CopyComb::Exec();

?>