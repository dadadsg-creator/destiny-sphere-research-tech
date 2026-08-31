<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Базовый класс для гейта анализатора
 *
 */
class LG_Base
{
	static protected $world;
	
	static protected $common;
	
	static protected $worldID;
	
	/**
	 * Метода инициализирует значения worldID и dbID,
	 * создает объект DS_Common с подключением к нужной базе
	 *
	 */
	static protected function _getCommon()
	{
		if(GameDown(0))
		{
			die("GameDown");
		}
		
		$common = DS_Common::CreateForCurrentDB(0);
		
		$sql = "
			SELECT
				dbname,
				id_world
			FROM
				ds_worldcollection
			WHERE
				world_identifier = '".self::$world."'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		
		self::$worldID = $res[0]['id_world'];
		
		$dbId = 0;
		
		$dbName = $res[0]['dbname'];
		
		foreach($GLOBALS['dbWorldAccess'] as $db_Id => $db)
		{
			if($db['server'] == $dbName)
			{
				$dbId = $db_Id;
				break;
			}
		}
		
		if(GameDown($dbId))
		{
			die("GameDown");
		}
		
		$common->setDbId($dbId);
	
		self::$common = $common;
	}
}


?>