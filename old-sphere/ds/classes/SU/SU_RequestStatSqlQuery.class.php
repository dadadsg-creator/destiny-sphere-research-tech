<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс содержит функции, которые формируют SQL запрос
 *
 */
class SU_RequestStatSqlQuery
{
	/**
	 * Переменная содержит SQL запрос
	 *
	 * @var unknown_type
	 */
	static private $sql = "
				SELECT
				    uat.tagname,
				    extract(YEAR from rl.add_date) as year,
				    extract(MONTH from rl.add_date) as month,
				    extract(DAY from rl.add_date) as day,
				    extract(HOUR from rl.add_date) as hour,
				    count(uat.tagname)
				FROM
					ds_request_log AS rl
				INNER JOIN ds_useractiontype AS uat ON (uat.id_useractiontype = rl.id_useractiontype)";

	function GenerateQueryWorld($worldId, $startday, $endday)
	{
		assert(!empty($worldId));
		assert(!empty($startday));
		assert(!empty($endday));
		$sql = self::$sql."
						INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = rl.id_userinstance)
						INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
	    			WHERE 
	    				w.id_world = '$worldId'
						AND
						rl.add_date >= '$startday 00:00:01'
						 AND
						 rl.add_date <= '$endday 23:59:59'
					GROUP BY  uat.tagname, year, month, day, hour
					ORDER BY year, month, day, hour, uat.tagname";
		return $sql;
	}

	function GenerateQueryUser($userName, $worldId, $startday, $endday)
	{
		assert(!empty($userName));
		assert(!empty($worldId));
		assert(!empty($startday));
		assert(!empty($endday));
				
		$sql = self::$sql."
						INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = rl.id_userinstance)
				    	INNER JOIN ds_user AS u ON (u.id_user = ui.id_user) 
				    	INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
				    WHERE 
				    	w.id_world = '$worldId' AND u.username = '$userName'
						AND
						rl.add_date >= '$startday 00:00:01'
						 AND
						 rl.add_date <= '$endday 23:59:59'
					GROUP BY  uat.tagname, year, month, day, hour
					ORDER BY year, month, day, hour, uat.tagname";
		return $sql;
	}

	function GenerateQueryDataBase($startday, $endday)
	{
		assert(!empty($startday));
		assert(!empty($endday));
		
		$sql = self::$sql."
					WHERE 
						 rl.add_date >= '$startday 00:00:01'
						 AND
						 rl.add_date <= '$endday 23:59:59'
					GROUP BY  uat.tagname, year, month, day, hour
					ORDER BY year, month, day, hour, uat.tagname";
		return $sql;
	}
	
	function GenerateQueryAllWorld($startday, $endday)
	{
		assert(!empty($startday));
		assert(!empty($endday));
		
		$sql = "SELECT 
				    extract(YEAR from rl.add_date) as year,
				    extract(MONTH from rl.add_date) as month,
				    extract(DAY from rl.add_date) as day,
				    extract(HOUR from rl.add_date) as hour,
				    w.world_identifier,
				    w.worldname,
				    count(w.world_identifier)
				FROM
					ds_request_log AS rl
		 			INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = rl.id_userinstance)
					INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
				WHERE 
						rl.add_date >= '$startday 00:00:01'
						 AND
						 rl.add_date <= '$endday 23:59:59'
				GROUP BY w.world_identifier, w.worldname, year, month, day, hour
				ORDER BY year, month, day, hour, w.world_identifier";
		
		return $sql;
	}
		
	function GenerateQuerybByUserAndWorlg($userName, $worldId, $startday, $endday)
	{
		assert(!empty($userName));
		assert(!empty($worldId));
		assert(!empty($startday));
		assert(!empty($endday));
				
		$s = "SELECT
					rl.add_date,
				    rl.xmldata,
				    uat.tagname
				    
				FROM
					ds_request_log AS rl
						INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = rl.id_userinstance)
						INNER JOIN ds_useractiontype AS uat ON (uat.id_useractiontype = rl.id_useractiontype)
				    	INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
				    	INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
				    WHERE 
				    	w.id_world = '$worldId' AND u.username = '$userName'
						AND
						rl.add_date >= '$startday 00:00:00'
						 AND
						 rl.add_date <= '$endday 23:59:59'
					ORDER BY rl.add_date";
		return $s;
	}

}

?>