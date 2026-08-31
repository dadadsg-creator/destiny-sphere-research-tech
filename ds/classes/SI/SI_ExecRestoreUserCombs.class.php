<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/07/01 08:54:15 $';

class SI_ExecRestoreUserCombs extends SI_Base
{
	/**
	 * —нимаем резервирование в комбов на которые восстанавливаем юзера
	 *
	 * @param unknown_type $id_userdata
	 * @param unknown_type $dbIdTo
	 */
	static public function clearReserveComb($id_userdata, $dbIdTo)
	{
		settype($id_userdata, 'integer');
		assert($id_userdata > 0);
		settype($dbIdTo, 'integer');
		assert(is_int($dbIdTo));

		self::$common->setDbId(0);
		$sql = "
			SELECT
				u.username
			FROM 
				ds_user AS u
				INNER JOIN ds_storeuserdata AS sud ON sud.id_user = u.id_user
			WHERE
				sud.id_storeuserdata = $id_userdata
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		$userName = $res[0]['username'];

		self::$common->setDbId($dbIdTo);
		$sql = "DELETE FROM ds_reservedcomb WHERE username = '$userName'";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}

	/**
	 * ‘ункци€ получени€ новых координат комбов юзера
	 *
	 * @param unknown_type $id_userdata
	 * @param unknown_type $dbIdTo
	 * @param unknown_type $moveToWorld
	 * @param unknown_type $is_reserved
	 * @param unknown_type $moveToCombCoords
	 * @return unknown
	 */
	static public function GetNewCombCoords($id_userdata, $dbIdTo, $moveToWorld, $is_reserved = 0, $moveToCombCoords = false)
	{
		assert($id_userdata);
		settype($dbIdTo, 'integer');
		assert($dbIdTo > -1);
		settype($moveToWorld, 'integer');
		assert($moveToWorld > 0);

		$_SESSION["dbId"] = $dbIdTo;
		$combCoords = array();

		self::$common->setDbId(0);
		$sql = "
			SELECT
				suc.comb_name_old,
				suc.coordinates_old,
				suc.comb_name_new,
				suc.coordinates_new,
				substring(suc.coordinates_old from '^.') as ringnumber,
				substring(suc.coordinates_old from '[.](.+)[.]') as complexnumber
			FROM
				ds_storeusercombs AS suc
				INNER JOIN ds_storeuserdata AS sud ON sud.id_storeuserdata = suc.id_storeuserdata
			WHERE
				suc.id_storeuserdata = ".$id_userdata."
			ORDER BY ringnumber, complexnumber, suc.id_comb_old
			";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		self::$common->setDbId($dbIdTo);

		// нету ли зарезервированных сот дл€ этого игрока
		$reservedCombs = self::GetReservedCombsByStoreUserId($id_userdata, $dbIdTo, $moveToWorld);

		$err = 0;
		if($reservedCombs)
		{
			foreach($reservedCombs as $k => $reservedComb)
			{
				// »дем по каждой соте, которую надо перевезти
				foreach($res as $l => $storedComb)
				{
					$combData = SI_SearchComb::GetCombDataByID($reservedComb['id_comb']);
					list($ring, $complex, $comb) = explode(".", $storedComb['coordinates_old']);

					// ≈сть ли соответствие соте в спец табличке
					self::$common->setDbId(0);
					$sql = "
						SELECT
							id_cobm_to
						FROM
							ds_worldtransfercoordrelation
						WHERE
							id_comb_from = ".$combData['id_comb']."
						";

					$res = self::$common->getResultArray("", $sql, "", "", "");
					assert(empty(self::$common->errormsg));

					self::$common->setDbId($dbIdTo);

					// ≈сли есть
					if (count($res) > 0) {
						$combCoords[$storedComb['comb_name_old']]['RING'] = $combData['ringnumber'];
						$combCoords[$storedComb['comb_name_old']]['COMPLEX'] = $combData['complexnumber'];
						$combCoords[$storedComb['comb_name_old']]['COMB'] = $combData['combnumber'];
						$combCoords[$storedComb['comb_name_old']]['info'] = 'reserved';
						unset($res[$l]);
						continue 2;
					} else {
						if($ring == $combData['ringnumber'] && ($l == $reservedComb['priority'] || $reservedComb['priority'] == 0))
						{
							$combCoords[$storedComb['comb_name_old']]['RING'] = $combData['ringnumber'];
							$combCoords[$storedComb['comb_name_old']]['COMPLEX'] = $combData['complexnumber'];
							$combCoords[$storedComb['comb_name_old']]['COMB'] = $combData['combnumber'];
							$combCoords[$storedComb['comb_name_old']]['info'] = 'reserved';
							unset($res[$l]);
							continue 2;
						}
					}
				}
			}
		}

		$existsCombs = array();
		// если заданы желаемые координаты, то провер€ем свободны ли они
		if($moveToCombCoords)
		{
			assert(is_array($moveToCombCoords));

			self::$common->setDbId($dbIdTo);
			SI_SearchComb::SetCommon(self::$common);
			SI_SearchInterface::SetCommon(self::$common);

			foreach($moveToCombCoords as $name => $coords)
			{
				// не получили ли координаты из зарезервированных сот
				if(key_exists($name, $combCoords))
				{
					continue;
				}
				$coordErr = false;
				list(	$combCoords[$name]['RING'],
				$combCoords[$name]['COMPLEX'],
				$combCoords[$name]['COMB']	) = explode(".", $coords);

				// номер кольца не может изменитьс€!
				/*foreach($oldcombCoords as $k => $comb)
				{
				if($comb['name'] == $name)
				{
				$combCoords[$name]['RING'] = substr($comb['coord'], 0, 1);
				}
				}*/

				$combID = SI_SearchComb::GetCombIDByCoordinates($moveToWorld, $combCoords[$name]['RING'], $combCoords[$name]['COMPLEX'], $combCoords[$name]['COMB']);

				if($combID == 0)
				{
					$coordErr = true;
				} else {
					$combData = SI_SearchComb::GetCombDataByID($combID);
					$combCoords[$name]['info'] = 'free';

					if(!empty($combData['id_userinstance']) || $combData['is_capture'] == 't')
					{
						$coordErr = true;
					}
				}

				if($coordErr)
				{
					$combId = GL_GetCombForPlacement::GetComb($combCoords[$name]['RING'], $moveToWorld, $existsCombs);
					if($combCoords[$name]['RING'] == 4)
					{
						$existsCombs[] = $combId;
					}
					$combData = SI_SearchComb::GetCombDataByID($combId);
					$combCoords[$name]['RING'] = $combData['ringnumber'];
					$combCoords[$name]['COMPLEX'] = $combData['complexnumber'];
					$combCoords[$name]['COMB'] = $combData['combnumber'];
					$combCoords[$name]['info'] = 'ocupied';
				}
			}
			//			self::UpdateCombCoords($combCoords);

			return $combCoords;
		}

		// в обычном случае берем любые свободные соты.
		foreach($res as $k => $comb)
		{
			// не получили ли координаты из зарезервированных сот
			if(key_exists($comb['comb_name_old'], $combCoords))
			{
				continue;
			}

			list(	$combCoords[$comb['comb_name_old']]['RING'],
			$combCoords[$comb['comb_name_old']]['COMPLEX'],
			$combCoords[$comb['comb_name_old']]['COMB']	) = explode(".", $comb['coordinates_old']);

			$combId = GL_GetCombForPlacement::GetComb($combCoords[$comb['comb_name_old']]['RING'], $moveToWorld, $existsCombs);
			if($combCoords[$comb['comb_name_old']]['RING'] == 4)
			{
				$existsCombs[] = $combId;
			}

			$combData = SI_SearchComb::GetCombDataByID($combId);

			$combCoords[$comb['comb_name_old']]['RING'] = $combData['ringnumber'];
			$combCoords[$comb['comb_name_old']]['COMPLEX'] = $combData['complexnumber'];
			$combCoords[$comb['comb_name_old']]['COMB'] = $combData['combnumber'];
			$combCoords[$comb['comb_name_old']]['info'] = 'minfree';
		}
		//		self::UpdateCombCoords($combCoords);
		return $combCoords;
	}


	static public function UpdateCombCoords($combCoords, $id_userdata)
	{
		assert(is_array($combCoords));

		self::$common->setDbId(0);
		foreach($combCoords as $name => $coords)
		{
			$coords_new = $coords['RING'].".".$coords['COMPLEX'].".".$coords['COMB'];
			$sql = "
				UPDATE
					ds_storeusercombs
				SET
					coordinates_new = '$coords_new'
				WHERE
					comb_name_old = '$name'
					AND
					id_storeuserdata = '$id_userdata'
				";

			self::$common->executeQuery($sql);
			assert(empty(self::$common->errormsg));
		}
	}

	static public function updateCombData($UIID, $dbIdTo, $combCoords, $deleteGondola = false)
	{
		assert(is_int($dbIdTo));
		assert((int) $UIID > 0);

		self::$common->setDbId($dbIdTo);

		$sql = "
			SELECT
				c.id_comb
			FROM
				ds_comb AS c 				
			WHERE
				c.id_userinstance = $UIID
			";		
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		self::$common->setDbId(0);

		foreach($res as $k => $comb)
		{
			$newComb = SI_SearchComb::GetCombDataByID($comb['id_comb']);

			$comb_coords = $newComb['ringnumber'].".".$newComb['complexnumber'].".".$newComb['combnumber'];

			foreach($combCoords as $name => $coords)
			{
				$coords_new = $coords['RING'].".".$coords['COMPLEX'].".".$coords['COMB'];

				if($coords_new == $comb_coords)
				{
					$sql = "
						UPDATE
							ds_storeusercombs
						SET
							id_comb_new = ".$newComb['id_comb'].",
							comb_name_new = '".$newComb['combname']."'
						WHERE
							coordinates_new = '$coords_new'
						";
					self::$common->executeQuery($sql);
					assert(empty(self::$common->errormsg));
					break;
				}
			}

			if($deleteGondola)
			{
				self::$common->setDbId($dbIdTo);
				self::deleteGondolaTransporter($comb['id_comb']);
			}
		}
	}

	static public function GetReservedCombsByStoreUserId($id_storeuserdata, $dbIdTo, $moveToWorld)
	{
		assert($id_storeuserdata);
		assert($moveToWorld > 0);
		self::$common->setDbId(0);
		$sql = "
			SELECT 
				sud.id_user,
				u.username
			FROM 
				ds_storeuserdata AS sud
				INNER JOIN ds_user AS u ON (u.id_user = sud.id_user)
			WHERE 
				sud.id_storeuserdata = $id_storeuserdata
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		$userName = $res[0]['username'];

		self::$common->setDbId($dbIdTo);
		$sql = "
			SELECT 
				rc.id_comb,
				rc.priority,
				c.combnumber,
				cc.complexnumber,
				r.ringnumber
			FROM 
				ds_reservedcomb AS rc
				INNER JOIN ds_comb AS c ON (c.id_comb = rc.id_comb)
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
			WHERE 
				rc.username = '$userName'
				AND
				r.id_world = $moveToWorld
			ORDER BY rc.priority DESC";
		$res = self::$common->getResultArray("", $sql, "", "", "");

		assert(empty(self::$common->errormsg));

		return $res;
	}

	// удадение гандолы-транспортера с соты
	static public function deleteGondolaTransporter($id_comb)
	{
		settype($id_comb, 'integer');
		assert($id_comb > 0);

		$sql = "
			SELECT
				ab.id_attachedbuilding
			FROM
    			ds_attachedbuilding AS ab
    			INNER JOIN ds_building AS b ON b.id_building = ab.id_building
			WHERE
     			ab.id_comb = $id_comb
     			AND b.buildingname = 'GONDOLA_TRANSPORTER'";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		if($res)
		{
			$id_building = $res[0]['id_attachedbuilding'];
		} else {
			return false;
		}

		//delete building
		$dsql = "DELETE	FROM ds_attachedbuildinghasattribute WHERE id_attachedbuilding = $id_building";
		$res = self::$common->executeQuery($dsql);
		assert(empty(self::$common->errormsg));

		$dsql = "DELETE FROM ds_attachedbuilding WHERE id_attachedbuilding = $id_building";
		$res = self::$common->executeQuery($dsql);
		assert(empty(self::$common->errormsg));

		$sql = "SELECT id_eventqueue FROM ds_attachedbuildingbindeventqueue	WHERE id_attachedbuilding = $id_building";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		if($res)
		{
			$id_event = $res[0]['id_eventqueue'];
		} else {
			// событий нету
			return true;
		}

		$sql = "DELETE FROM ds_attachedbuildingbindeventqueue WHERE id_eventqueue = $id_event";
		$res = self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));

		$sql = "UPDATE ds_eventqueue SET istrash = true WHERE id_eventqueue = $id_event";
		$res = self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));

		return true;
	}
}
?>