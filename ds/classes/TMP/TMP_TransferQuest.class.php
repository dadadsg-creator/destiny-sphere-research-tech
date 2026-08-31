<?php
/**
* Клас для реализации квестового мира
* Игроки заказывают переезд через интерфейс класса FreeWorldTransferQuest.php
* Мы их перевозим в квестовый мир, при этом не удаляя из основного (то есть копируем соту)
*
**/

class TMP_TransferQuest
{
	static private $common;
	
	static public function Exec()
	{
		self::$common = DS_Common::CreateForCurentDB(0);
		
		// выбираем список игроков, заказавших переезд
		$moveArr = self::_getReadyToMove();
		
		foreach ($moveArr as $key => $user)
		{
			// Имя игрока
			$username = $user['username'];
			assert(!empty($username));
			
			// Его айдишник
			$user_id = (int)$user['id_user'];
			assert($user_id > 0);
			
			// Его инстанс
			$UIIDToStore = (int)$user['id_userinstance'];
			assert($UIIDToStore > 0);
			
			$moveFromWorld = (int)$user['id_world_from'];
			assert($moveFromWorld > 0);
			
			$dbIdFrom = (int)$user['dbId_from'];
			assert($dbIdFrom >= 0);
			
			// Куда
			$dbIdTo = (int)$user['dbId_to'];
			assert($dbIdTo >= 0);
			
			$moveToWorld = (int)$user['id_world_to'];
			assert($moveToWorld > 0);
			
			$storeReason = 2;
			$comments = 'Копирование соты в турнирный мир';
			$storeTill = '0000-00-00 00:00:00';
			$reserve = 0;
			
			// Сохраняем игрока
			$_SESSION["dbId"] = $dbIdFrom;
		
			echo '<h1>START SAVING USER</h1><br>';
			flush();
		
			self::$common->setDbId($dbIdFrom);
			SI_ExecStoreUser::SetCommon(self::$common);		
			$storeUserInstance = SI_ExecStoreUser::Exec($dbIdFrom, $UIIDToStore, $moveFromWorld, $storeReason, 'admin', $comments, $storeTill, $reserve, $username);
			$id_storeuserdata = SI_ExecStoreUser::GetUserDataId();
			
			echo '<h1>USER STORED</h1><br>';
			echo flush();
			
			// Копируем игрока в квестовый мир
			$_SESSION["dbId"] = $dbIdTo;
			flush();
			echo '<h1>START RESTORE USER</h1><br>';
			
			self::$common->setDbId($dbIdTo);
			
			// Получаем минимальные свободные координаты для всех сот игрока
			$moveToCombCoords = SI_ExecRestoreUserCombs::GetNewCombCoords($id_storeuserdata, $dbIdTo, $moveToWorld, false);
			
			
			// Из-за разности файлов на локали и релизе, нафиг не надо
			/*// Получаем зарезервированные соты игрока
			$reserveComb = SI_ExecRestoreUserCombs::GetReservedCombsByStoreUserId($id_storeuserdata, $dbIdTo, $moveToWorld);
			if (count($reserveComb) > 0) {
				// Заменяем координаты домашки 
				foreach ($moveToCombCoords as $k => $d)
				{
					if ($d['RING'] != 4) {
						assert($d['RING'] == $reserveComb[0]['ringnumber']);
						
						$moveToCombCoords[$k]['COMB'] = $reserveComb[0]['combnumber'];
						$moveToCombCoords[$k]['COMPLEX'] = $reserveComb[0]['complexnumber'];;
					}
				}
			}*/
			$moveToCombCoords = false;
			
			SI_ExecRestoreUser::SetCommon(self::$common);
			SI_ExecRestoreUser::Exec($id_storeuserdata, $dbIdTo, $moveToWorld, false, 'admin', $moveToCombCoords);
		
			echo '<h1>USER RESTORED</h1><br>';
		
			// Помечаем игрока, как переехавшего
			$_SESSION["dbId"] = 0;
			self::$common->setDbId(0);
			$sql = "
				UPDATE
					ds_worldtransferquest
				SET 
					status = 2
				WHERE 
					id_user = ".$user_id;
				
			$res = self::$common->getResultArray("", $sql, "", "", "");
			assert(empty(self::$common->errorMsg));

			echo '<h1>TRANSFER COMPLITE</h1><br>';			
		}
	}
	
	static private function _getReadyToMove()
	{
		$sql = "
			SELECT
                u.username,
                wt.*,
                w_from.world_identifier as w_from,
                w_to.world_identifier as w_to,
                uic.id_userinstance
            FROM
                ds_worldtransferquest as wt
                INNER JOIN ds_user AS u ON (u.id_user = wt.id_user)
                INNER JOIN ds_worldcollection AS w_from ON (w_from.dbname = wt.db_from AND w_from.id_world = wt.id_world_from)
				INNER JOIN ds_worldcollection AS w_to ON (w_to.dbname = wt.db_to AND w_to.id_world = wt.id_world_to)
                LEFT JOIN ds_userinstancecollection uic ON (uic.id_user = wt.id_user AND uic.id_world = wt.id_world_from AND uic.dbname = wt.db_from)
			WHERE
				status = 1
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errorMsg));
		
		foreach($res as $k => $transfer)
		{
			$res[$k]['dbId_from'] = self::$common->getDbIdByName($transfer['db_from']);
			$res[$k]['dbId_to'] = self::$common->getDbIdByName($transfer['db_to']);
		}
			
		return $res;
	}
}

?>