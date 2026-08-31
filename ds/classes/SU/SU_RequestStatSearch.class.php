<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Кдасс делает выборки из базы
 *
 * @return unknown
 */
class SU_RequestStatSearch
{
	static public function GetMinDate()
	{
		/**
		 * Функция GetMinDate() выбирает минимальную дату из всех баз
		 *
		 * @return unknown
		 */
		$mintimes = array();
		$common = DS_Common::CreateForCurentDB();

		$sql = "SELECT
						min(add_date) FROM ds_request_log";
		$mintime = $common->getMultyArray('', $sql);
		assert(empty($common->errormsg) && count($mintime) > 0);

		$min = strtotime($mintime[0][min]);
		for ($i=0; $i<count($mintime); $i++)
		{
			$mintimes = strtotime($mintime[$i][min]);

			if ($min > $mintimes)
			{
				$min = $mintimes;
			}
		}

		//$res  = GB_TypeFactory::CreateBase(GB_Date, $min);
		//$res = $res->getPgFormat();
		//return $res;
		return $min;
	}


	static public function GetWorldList()
	{
		/**
		 * Функция GetWorldList() выбирает список всез миров из всех баз
		 *
		 * @return unknown
		 */
		$common = DS_Common::CreateForCurentDB();
		
		$sql = "
			SELECT
      			*
			FROM
    			ds_world";
		$res = $common->getMultyArray("", $sql, "", "", "");
		assert(empty($common->errormsg) && count($res) > 0);

		return $res;
	}

	static public function ExecQuery($query, $dbId)
	{
		/**
		 * Функция ShowWorld() исполняет определенный запров в определенной базе
		 *
		 * @return unknown
		 */

		assert(!empty($query));
		
		$dbId = (int)$dbId;
		assert(dbId >= 0);
		
		$common = DS_Common::CreateForCurentDB($dbId);

		$res = $common->getResultArray('', $query);
		assert(empty($common->errormsg));

		return $res;
	}
	
	static public function ExecQueryAllWorld($query)
	{
		/**
		 * Функция  ExecQueryAllWorld() исполняет определенный запров в определенной базе
		 *
		 * @return unknown
		 */

		assert(!empty($query));
		
		assert(key_exists('dbWorldAccess', $GLOBALS) && is_array($GLOBALS['dbWorldAccess']));
		
		$res = array();
		
		foreach($GLOBALS['dbWorldAccess'] as $DBID => $serverData)
		{
			assert(is_array($serverData) && count($serverData) > 0);
		
			$common = DS_Common::CreateForCurentDB($DBID);
			//set STATEMENT_TIMEOUT to 0;
			$resdb = $common->getResultArray('', $query);
			assert(empty($common->errormsg));
			$res = array_merge($res, $resdb);
		}

		return $res;
	}
}
?>