<?php
//CopyComb.php

ini_set('memory_limit', '50M');
set_time_limit(0);

require_once 'Main.php';

class CopyComb
{
	static private $endDateOld;

	static function Exec()
	{
		$acauntFrom = 'Sixto';
		$cobmFrom = 'Keystone';
		
		$acauntFrom = 'Samtakoy';
		$cobmFrom = 'Samtakoy';
		
		$acauntFrom = 'Annita';
		$cobmFrom = 'Annita_886';
		
		$passworld = 'qwerty13';
		$dbIdFrom = 0;
		$worldIdFrom = 5;
		
		// Телец3
		//$dbIdTo = 3;
		//$worldIdTo = 2;
		
		// Орел
		$dbIdTo = 1;
		$worldIdTo = 3;
		
		// Волк
		//$dbIdTo = 15;
		//$worldIdTo = 2;
		
		// Ворон
		//$dbIdTo = 6;
		//$worldIdTo = 1;
		
		// Феникс
		//$dbIdTo = 1;
		//$worldIdTo = 1;

		// Стрелец
		//$dbIdTo = 12;
		//$worldIdTo = 1;

		$toUserArr = array(
0 => array(
'username' => 'Joshma',
'combName' => 'PSKOV',
'coordinates' => '3.11.2'
),
1 => array(
'username' => 'Elissa44',
'combName' => 'Omega',
'coordinates' => '3.27.6'
),
2 => array(
'username' => 'Skotina',
'combName' => 'Zaraza',
'coordinates' => '3.44.1'
),
3 => array(
'username' => 'GectorLove',
'combName' => 'konec',
'coordinates' => '3.77.3'
),
4 => array(
'username' => 'TAPAH88',
'combName' => 'TOPMO3',
'coordinates' => '3.93.5'
),
5 => array(
'username' => 'harizmius',
'combName' => 'ziben',
'coordinates' => '3.124.6'
),
6 => array(
'username' => 'meskoz',
'combName' => 'gazmyaz',
'coordinates' => '3.131.3'
),
7 => array(
'username' => 'Clearwater',
'combName' => 'Cloudwork',
'coordinates' => '3.139.2'
),
8 => array(
'username' => 'reddick',
'combName' => 'simbia',
'coordinates' => '3.158.5'
),
9 => array(
'username' => 'AndyHoffman',
'combName' => 'Luxemburg',
'coordinates' => '1.49.5'
),
10 => array(
'username' => 'Semen7ich',
'combName' => '7ichland',
'coordinates' => '1.124.2'
),
11 => array(
'username' => 'DaniilLucker',
'combName' => 'ujdinah',
'coordinates' => '1.213.1'
),
		);

		// Коннектимся к сиру игрока, которого будем размножать
		$common = DS_Common::CreateForCurentDB($dbIdFrom);
		$_SESSION['dbId'] = $dbIdFrom;


		/*// Получаем юзеринстанс игрока
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
		$storeUserInstance = SI_ExecStoreUser::Exec($dbIdFrom, $UIID, $worldIdFrom, 1, 'admin', 'quest template', '0000-00-00 00:00:00', 0);
		$id_storeuserdata = SI_ExecStoreUser::GetUserDataId();*/
		
		//$id_storeuserdata = 2034; // Turiza
		//$id_storeuserdata = 2082; // Samtakoy
		//$id_storeuserdata = 2096; // Sixto
		/*$id_storeuserdata = 2097; // Annita

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
		
		// Проходимся по всем игрокам
		foreach ($toUserArr as $toKey => $toData)
		{
			$newUserName = $toData['username'];
			$newUserComb = $toData['combName'];
			$newUserCombCoords = $toData['coordinates'];
			
			// Создаем игрока
			$common = DS_Common::CreateForCurentDB(0);
			$_SESSION['dbId'] = 0;


			//***************************************** ЗАКОМЕНТИТЬ 
			// Смотрим, есть ли игрок с таким именем
			$sql = "SELECT * FROM ds_user WHERE username = '$newUserName'";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			if (count($res > 0)) {
				$newIdUser = (int)$res[0]['id_user'];
				assert($newIdUser > 0);
			} else {
				$sql = "SELECT nextval('ds_user_id_user_seq')";
				$res = $common->getResultArray('', $sql, '', '', '');
				assert(empty($common->errormsg));

				$newIdUser = (int)$res[0]['nextval'];
				assert($newIdUser > 0);

				$sql =
					"
					INSERT INTO ds_user
					(id_user, id_language, username, email, admin, accountactive, password, activate_mail, registrationdate)
					VALUES
					($newIdUser, 3, '$newUserName', '$newUserName', false, true, md5('$passworld'), 1, now());
					";

				$res = $common->getResultArray('', $sql, '', '', '');
				assert(empty($common->errormsg));
			}

			// Получаем следующий айдишник сохраненных данных игрока
			$sql = "SELECT nextval('ds_storeuserdata_id_storeuserdata_seq')";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			$newIdStoreuserdata = (int)$res[0]['nextval'];
			assert($newIdStoreuserdata > 0);

			// Формируем новые данные
			$newUserinstanceData = str_replace('s:7:"id_user";s:'.strlen($userId).':"'.$userId.'"', 's:7:"id_user";s:'.strlen($newIdUser).':"'.$newIdUser.'"', $userInstanceData);
			$newUserinstanceData = str_replace('username";s:'.strlen($acauntFrom).':"'.$acauntFrom.'"', 'username";s:'.strlen($newUserName).':"'.$newUserName.'"', $newUserinstanceData);
			$newUserinstanceData = str_replace('combs";a:1:{s:'.strlen($cobmFrom).':"'.$cobmFrom.'";', 'combs";a:1:{s:'.strlen($newUserComb).':"'.$newUserComb.'";', $newUserinstanceData);
			$newUserinstanceData = str_replace('combName";s:'.strlen($cobmFrom).':"'.$cobmFrom.'";', 'combName";s:'.strlen($newUserComb).':"'.$newUserComb.'";', $newUserinstanceData);
			$newUserinstanceData = str_replace('email";s:'.strlen($userMail).':"'.$userMail.'";', 'email";s:'.strlen($newUserName).':"'.$newUserName.'";', $newUserinstanceData);

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
					    ($newIdStoreuserdata, ".$storeUserComb[0]['id_comb_old'].", '".$storeUserComb[0]['comb_name_old']."', '$newUserComb', '$newUserCombCoords', '".$storeUserComb[0]['coordinates_old']."')
					";

			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			// Восстанавливаем игрока
			SI_ExecRestoreUser::SetCommon($common);
			SI_ExecRestoreUser::Exec($newIdStoreuserdata, $dbIdTo, $worldIdTo, false, 'admin', array($newUserComb => $newUserCombCoords));

			echo "<br>-------------USER <b>$newUserName</b> CREATED-------------<br><br>";
		}*/
		
		
		// Снимаем защиту новичка
		// Создаем игрока
		$common = DS_Common::CreateForCurentDB($dbIdTo);
		$_SESSION['dbId'] = $dbIdTo;
			
		// Проходимся по всем игрокам
		foreach ($toUserArr as $toKey => $toData)
		{
			$newUserName = $toData['username'];
			// Смотрим, есть ли игрок с таким именем
			$sql = "UPDATE 
						ds_userinstance 
					SET 
						newb_defend_enddate = now()
					WHERE id_user = (select id_user from ds_user where username = '$newUserName')";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));
			
			
			// Смотрим, есть ли игрок с таким именем
			$sql = "update ds_attachedbuilding set 
				active = 'f'
				WHERE 
				id_attachedbuilding = (
				SELECT
            attb.id_attachedbuilding
            
            FROM
                ds_user u
                INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
                INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
                LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
                LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                INNER JOIN ds_combname cn ON (c.id_comb = cn.id_comb)
                INNER JOIN ds_attachedbuilding attb ON (cn.id_comb = attb.id_comb)
                INNER JOIN ds_building b ON (attb.id_building = b.id_building)
            WHERE
                u.username = '$newUserName'
                AND
                b.id_building = 6)";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			echo "<br>-------------USER <b>$newUserName</b> CREATED-------------<br><br>";
		}
	}
}

CopyComb::Exec();

?>