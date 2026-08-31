<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/07/01 08:46:46 $';

/**
 * Статтический Класс контейнер для SQL зпросов свзяанных с миром.
 */
class DS_World  extends DS_Base
{
	static public function GetByID($id)
	{				
		assert((int)$id > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT *
				FROM ds_world
				WHERE id_world = $id";

		$res = $common->getResultArray(self::FormMark(__METHOD__, $id), $sql, "", "", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		
		if(count($res) == 0) 
		{			
			return $res;
		}else{
			return $res[0];
		}
	}//END OF METHOD
		
	static public function GetNoTradePeriod($id)
	{
		assert((int)$id > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT 
				*
			FROM 
				ds_notradeperiods
			WHERE 
				id_world = $id
				AND
				start_date < now()
				AND
				end_date > now()
			";
		
		$res = $common->getResultArray(self::FormMark(__METHOD__, $id), $sql, "", "", "NO_CACHE");
		assert(empty($common->errormsg));
		
		if(count($res) == 0)
		{
			return false;
		}else{
			$result = array
			(
				'START_DATE' => GB_TypeFactory::CreateBase('GB_Date', $res[0]['start_date']),
				'END_DATE' => GB_TypeFactory::CreateBase('GB_Date', $res[0]['end_date'])
			);
			
			return $result;
		}
		
		return $res;
	}
	
	static public function GetWorldStatus($id)
	{
		assert(is_int($id) && $id > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT 
				ws.status_name
			FROM
				ds_worldstatus as ws
				INNER JOIN ds_world AS w ON (w.world_status = ws.id_worldstatus)
			WHERE
				w.id_world = $id
			";
		
		$res = $common->getResultArray(self::FormMark(__METHOD__, $id), $sql, "", "", "NO_CACHE");
		assert(empty($common->errormsg));
		
		if(count($res) == 0)
		{
			return '';
		}
		
		return $res[0]['status_name'];
	}
	
	static public function GetWorldFromCollectionByShortName($shortName)
	{
		assert(is_string($shortName) && !empty($shortName));
		
		$common = self::getCommon();
		
		$sql = "SELECT * FROM ds_worldcollection WHERE world_identifier = '".$shortName."'";

		$res = $common->getResultArray(self::FormMark(__METHOD__, $shortName), $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");
		assert(empty($common->errormsg) && count($res) == 1);
				
		return $res[0];
	}
	
	/**
	 * Получение списка всех иснтансов мира
	 * используется для отправки почты
	 *
	 * @param int $worldID
	 * @return array
	 */
	static public function GetAllWorldInstances($worldID, $race = array())
	{
		assert(is_int($worldID) && $worldID > 0);
		$common = self::getCommon();
		
		$where = "";
		$or = "";

		if (count($race) > 0 && count($race) != 3) {
			$where = " AND (";
			foreach ($race as $k => $idRace) {
				$where .= $or." id_race = $idRace ";
				$or = " OR ";
			}
			$where .= ")";
		}
		$sql = "
			SELECT
				id_userinstance
			FROM
				ds_userinstance
			WHERE
				id_world = $worldID
				AND
				id_userinstance > 0
			$where
			";
		$res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty($common->errormsg));
		
		return $res;
	}
	/**
	 * Получение номера порта, к которому будет коннектиться чат 
	 * в зависимости от мира
	 *
	 * @param int $worldID
	 * @return int
	 */
	static public function GetPortByWorldId($worldID)
	{
		assert(is_int($worldID) && $worldID > 0);
		$common = self::getCommon();
		
		$sql = "
			SELECT
				port
			FROM
				ds_chat_port
			WHERE
				id_world = $worldID
			";
		$res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty($common->errormsg));
		
		if (count($res) > 0) {
			return (int)$res[0]['port'];
		}
		
		// Если соответствия нет, возврашаем дефалтный порт
		return 1;
	}
	
}//END OF CLASS


?>