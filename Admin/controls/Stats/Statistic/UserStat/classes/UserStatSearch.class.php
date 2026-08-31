<?php

class UserStatSearch
{
	static public function GetUresrStat($from, $to, $detail)
	{
		$common = DS_Common::CreateForCurentDB(0);

		$sql = self::_getSQL($from, $to, $detail);
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
/*echo "<font color=white><pre>";
print_r($res);
echo "</pre>";
exit;*/
		
		return $res;
	}
	
	static public function GetMaxValue($from, $to, $detail)
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
		SELECT
    		min(t.count) as min,
    		max(t.count) as max
		FROM
    		(
    		".self::_getSQL($from, $to, $detail)."
    		) as t
		";
	
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
				
		return $res[0];
	}
	
	static public function GetSum($from, $to)
	{
		$common = DS_Common::CreateForCurentDB($dbId);
		
		$sql = "
			SELECT
				count(*) as count
			FROM
				ds_loginhistory
			WHERE
				lastvisit > '$from'
				AND
				lastvisit < '$to'
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return (int)$res[0]['count'];
	}
	
	private function _formSQLPart($detail, $alias = false)
	{
		$dateParts = array(
		0 => 'year',
		1 => 'month',
		2 => 'week',
		3 => 'day',
		4 => 'hour',
		);

		for($i=0; $i<=$detail; $i++)
		{
			$sql[$i] = "extract(".$dateParts[$i]." from lastvisit)";
			if($alias)
			{
				$sql[$i] .= ' as '.$dateParts[$i];
			}
		}

		return implode(', ', $sql);
	}


	/*static public function GetWorldList()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$sql = "
			SELECT
				dbname,
				id_world,
				worldname
			FROM
				ds_worldcollection
			WHERE
				world_identifier != 'UC'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		$worlds = array();
		
		foreach($res as $k => $world)
		{
			foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
			{
				if($db['server'] == $world['dbname'])
				{
					$worlds[$world['dbname'].'_'.$world['id_world']] = $world['worldname'];
					continue 2;
				}
			}
		}

		return $worlds;
	}*/
	
	/*static public function GetWorldNameById($worldId)
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				worldname
				FROM
					ds_world
				WHERE
				 id_world = '$worldId'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		
		return $res[0]['worldname'];
	}*/
		
	static private function _getSQL($from, $to, $detail)
	{
		$sql = "
			
						SELECT								
								".self::_formSQLPart($detail, true).",
								count(*) as count
							FROM
								ds_loginhistory
							WHERE
								 lastvisit > '$from'
								AND
								 lastvisit < '$to'
							GROUP BY
								".self::_formSQLPart($detail)."
							ORDER BY
								".self::_formSQLPart($detail)."
							";
		return $sql;
	}
}
?>