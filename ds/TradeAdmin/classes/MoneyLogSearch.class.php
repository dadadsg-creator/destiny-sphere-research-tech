<?php

class MoneyLogSearch
{
	static public function getMoneyLogsCount($userName, $fromDate, $toDate, $DBID, $worldID)
	{
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurrentDB(0);
		
		$where = '';
		
		if(!empty($userName))
		{
			$where .= " AND u.username = '$userName'";
		}
		
		if(!empty($fromDate))
		{
			$where .= " AND ml.add_date > to_timestamp($fromDate)";
		}
		
		if(!empty($toDate))
		{
			$where .= " AND ml.add_date < to_timestamp($toDate)";
		}
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_money_list as ml
				-- INNER JOIN ds_money_action_types AS mat ON (mat.id_money_action = ml.id_action)
				INNER JOIN ds_worldcollection AS wc ON (wc.world_identifier = ml.world_identifier)
				INNER JOIN ds_user AS u ON (u.id_user = ml.id_user)
			WHERE
				wc.id_world = ".$worldID."
				AND
				wc.dbname = '".$GLOBALS['dbWorldAccess'][$DBID]['server']."'
				$where
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res[0]['count'];
	}
	
	static public function getMoneyLogs($userName, $fromDate, $toDate, $limit, $offset, $DBID, $worldID)
	{
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($limit) && $limit >= 0);
		assert(is_int($offset) && $offset >= 0);
		
		$common = DS_Common::CreateForCurrentDB(0);
		
		$where = '';
		
		if(!empty($userName))
		{
			$where .= " AND (u.username = '$userName' OR u2.username = '$userName')";
		}
		
		if(!empty($fromDate))
		{
			$where .= " AND ml.add_date > to_timestamp($fromDate)";
		}
		
		if(!empty($toDate))
		{
			$where .= " AND ml.add_date < to_timestamp($toDate)";
		}
		
		$limit = '';
		
		if($limit > 0)
		{
			//$limit = " OFFSET $offset LIMIT $limit";
		}
		
		$sql = "
SELECT
    ml.*,
    mat.action_name,
	mat.log_action_table,
	'$DBID' as dbid,
	ml.id_list,
    
    u2.username as user_from,
    
    u.username as user_to,
    
	map1.gc_delta,
	
	map1.sc_delta
   
FROM
    ds_money_list AS ml
    
    INNER JOIN ds_money_account_payments AS map1 ON (ml.id_list = map1.id_list)
    INNER JOIN ds_money_account_payments AS map2 ON (ml.id_list = map2.id_list)
    
    INNER JOIN ds_money_action_types AS mat ON (mat.id_money_action = ml.id_action)
    INNER JOIN ds_worldcollection AS wc ON (wc.world_identifier = ml.world_identifier)

    INNER JOIN ds_user AS u ON (u.id_user = map1.id_user)
    INNER JOIN ds_user AS u2 ON (u2.id_user = map2.id_user)
WHERE
	(
    	map1.gc_delta > 0
    	OR
    	map1.sc_delta > 0
    )
    AND
    (
         (
              map2.gc_delta < 0
              AND
		      abs(map2.gc_delta) = map1.gc_delta
         )
         OR
         (
              map2.sc_delta < 0
              AND
              abs(map2.sc_delta) = map1.sc_delta
         )
    )
    AND
	wc.id_world = ".$worldID."
	AND
	wc.dbname = '".$GLOBALS['dbWorldAccess'][$DBID]['server']."'			
	$where
ORDER BY ml.add_date DESC
$limit
		";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if(count($res) == 0)
		{
			return array();
		}
		
		return $res;
	}
	
	static public function GetAllWorldsList()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$out = array();
		
		$sql = "SELECT id_world, worldname, dbname FROM ds_worldcollection";
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
			
		foreach($res as $k => $v)
		{
			foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
			{
				if($db['server'] == $v['dbname'])
				{
					$v['dbid'] = $dbId;
					break;
				}
			}
			$out[] = $v;
		}
		
		return $out;
	}
}

?>