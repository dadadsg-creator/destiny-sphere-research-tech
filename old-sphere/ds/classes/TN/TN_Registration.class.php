<?php
/**
 * –егистраци€ игрока в турнире
 * 
 * 
 */
class TN_Registration
{
	static public function Registration($userId, $dbId, $worldId, $race, $combName)
	{
		assert(is_int($userId) && $userId >= 0);
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		$common = DS_Common::CreateForCurentDB($dbId);
				
		// ѕровер€ем то, что это турнирный мир
		$sql = "
				SELECT
					w.worldname
				FROM
					ds_world AS w
					INNER JOIN ds_gamemodifier AS gm ON (gm.id_world = w.id_world)
				WHERE
					gm.modifiername = 'TURNIR_WORLD'
					 AND
					gm.value = '1'
					 AND
					w.id_world = ".$worldId."
					 AND 
					w.world_status != 4
				";
		$res = $common->getResultArray("", $sql , "", "", "");
		assert(empty($common->errormsg));
		
		if ($res == 0) {
			return false;
		}
		
		// провер€ем, чтобы у игрока уже не было регистрации в мире
		if (self::CheckEnryTurnirAbility($userId, $dbId, $worldId)) {
			return 'ERROR';
		}
		
		$data['world'] = $worldId."_".$dbId;
		$data['race'] = $race;
		$data['comb'] = $combName;
		
		// пробуем создать инстанс
		if(!GE_ExecStartCreateInstance::Exec($data, false, true))
		{
			return GE_ExecStartCreateInstance::FetchErrorCode();
		} 
			
		return 'SACCESSFUL';
	}
	
	/**
	 * ѕроверка нет ли регистрации в турнире
	 *
	 * @return bool
	 */
	static public function CheckEnryTurnirAbility($userId, $dbId, $worldId)
	{
		assert(is_int($userId) && $userId >= 0);
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		// —мотрим, нет ли у игрока инстанса в этом мире
		$common = DS_Common::CreateForCurentDB($dbId);
		
		
		$sql = "SELECT * FROM
					ds_userinstance ui
				WHERE 
					ui.id_user = ".$userId."
					AND 
					ui.id_world = ".$worldId;

		$res = $common->getResultArray("", $sql , "", "", "");
		assert(empty($common->errormsg));
		
		assert(count($res) == 0 || count($res) == 1);
		
		if (count($res) == 1) {
			return true;
		}
		return false;
	}
	
	/**
	 * ѕроверка, есть ли турнирные миры
	 *
	 * @return bool
	 */
	static public function GetTurnirWorlds($onlyRegAnabled = false)
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$i = 0;
		
		$sql = "
				SELECT
					w.id_world,
					w.worldname,
                    gm2.value as allow_reg_after_start,
                    w.world_status
				FROM
					ds_world AS w
					INNER JOIN ds_gamemodifier AS gm ON (gm.id_world = w.id_world)
					INNER JOIN ds_gamemodifier AS gm2 ON (gm2.id_world = w.id_world)
				WHERE
					gm.modifiername = 'TURNIR_WORLD'
					AND
					gm.value = '1'
					AND
                    gm2.modifiername = 'TURNIR_ALLOW_ENTER_AFTER_START'
				";
		
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			$common->setDbId($dbId);
			
			$res = $common->getResultArray("", $sql , "", "", "");
			assert(empty($common->errormsg));
			
			foreach($res as $k => $world)
			{
				if ($onlyRegAnabled == true) {
					// ≈сли у нас разрешена регистраци€ после сарта турникр
					if ($world['allow_reg_after_start'] == 1) {
						// ≈сли статус мира не TURNIR_STARTED и не FROZEN
						if ($world['world_status'] != 5 && $world['world_status'] != 3) {
							continue;
						}
					} else {
						// ≈сли статус мира не FROZEN
						if ($world['world_status'] != 5) {
							continue;
						}
					}
				}
				
				$worlds[$i]['dbId'] = $dbId;
				$worlds[$i]['id_world'] = $world['id_world'];
				$worlds[$i]['worldname'] = $world['worldname'];
				
				$i ++;
			}
		}
		
		return $worlds;
		
	}
	
	/**
	 * ¬озвращает число игроков, на данный момент зареганных в турнире
	 *
	 * @return bool
	 */
	static public function GetCurrentCountOfPlayers($dbId, $worldId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		// —мотрим, нет ли у игрока инстанса в этом мире
		$common = DS_Common::CreateForCurentDB($dbId);
		
		
		$sql = "SELECT count(*) as count FROM
					ds_userinstance ui
				WHERE 
					ui.id_world = ".$worldId;

		$res = $common->getResultArray("", $sql , "", "", "");
		assert(empty($common->errormsg));
		
		if (count($res) == 0) {
			return 0;
		}
		
		return (int)$res[0]['count'];
	}
	
	static public function GetError()
	{
		return self::$error;
	}
	
	static public function GetDetails()
	{
		return self::$details;
	}
}

?>