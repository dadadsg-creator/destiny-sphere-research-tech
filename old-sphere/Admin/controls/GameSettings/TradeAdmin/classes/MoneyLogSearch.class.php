<?php

class MoneyLogSearch
{
	static public function getMoneyLogsCount()
	{
		$userName = $_SESSION['user'];
		$fromDate = $_SESSION['startDate'];
		$toDate = $_SESSION['endDate'];
		$worlds = $_SESSION['worlds'];
		$actionTypes = $_SESSION['actionTypes'];

		assert(is_array($worlds));
		assert(is_array($actionTypes));
		assert($offset >= 0);


		$count = 0;

		//Проходимся по каждому миру
		foreach ($worlds as $key => $val)
		{
			$arr = explode('_', $val);
			$dbID = (int)$arr[0];
			$worldID = (int)$arr[1];

			assert($dbID >= 0);
			assert($worldID >= 0);

			$common = DS_Common::CreateForCurrentDB($dbID);

			$where = '';

			if(!empty($userName))
			{
				$where .= " AND (u2.username = '$userName' AND u.username != '$userName')";
			}

			if(!empty($fromDate))
			{
				$where .= " AND ml.add_date > to_timestamp($fromDate)";
			}

			if(!empty($toDate))
			{
				$where .= " AND ml.add_date < to_timestamp($toDate)";
			}

			//Фильтр по операции
			$whereAction = '';
			$or = '';
			$whereAction = "AND (";

			foreach ($actionTypes as $key => $type)
			{
				$whereAction .= " $or mat.action_name='$type'";
				$or = 'OR';
			}
			$whereAction .= ")";


			if(!empty($userName))
			{
				$sql = "
                                SELECT
                                    count(*)
                                   
                                FROM
                                    ds_money_list AS ml
                                    
                                    INNER JOIN ds_money_account_payments AS map1 ON (ml.id_list = map1.id_list)
                                    INNER JOIN ds_money_account_payments AS map2 ON (ml.id_list = map2.id_list)
                                    
                                    INNER JOIN ds_money_action_types AS mat ON (mat.id_money_action = ml.id_action)
                                    INNER JOIN ds_worldcollection AS wc ON (wc.world_identifier = ml.world_identifier)
                                
                                    INNER JOIN ds_user AS u ON (u.id_user = map1.id_user)
                                    INNER JOIN ds_user AS u2 ON (u2.id_user = map2.id_user)
                                WHERE
                                        wc.id_world = ".$worldID."
                                        AND
                                        wc.dbname = '".$GLOBALS['dbWorldAccess'][$dbID]['server']."'                    
                                        $where
										$whereAction
                                
                ";
			}
			else {

				$sql = "
                                SELECT
                                   count(*)
                                   
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
                                        wc.dbname = '".$GLOBALS['dbWorldAccess'][$dbID]['server']."'                    
                                        $where
										$whereAction
                ";
			}

			echo "<pre>";

			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));

			$count += $res[0]['count'];
		}

		return $count;
	}

	static public function getMoneyLogs($offset)
	{
		$userName = $_SESSION['user'];
		$fromDate = $_SESSION['startDate'];
		$toDate = $_SESSION['endDate'];
		$worlds = $_SESSION['worlds'];
		$actionTypes = $_SESSION['actionTypes'];


		assert(is_array($worlds));
		assert(is_array($actionTypes));
		assert($offset >= 0);

		$result = array();

		//Проходимся по каждому миру
		foreach ($worlds as $key => $val)
		{
			$arr = explode('_', $val);
			$dbID = (int)$arr[0];
			$worldID = (int)$arr[1];

			assert($dbID >= 0);
			assert($worldID >= 0);

			$common = DS_Common::CreateForCurrentDB($dbID);

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

			//Фильтр по операции
			$whereAction = '';
			$or = '';
			$whereAction = "AND (";

			foreach ($actionTypes as $key => $type)
			{
				$whereAction .= " $or mat.action_name='$type'";
				$or = 'OR';
			}
			$whereAction .= ")";

			if(!empty($userName))
			{
				$sql = "
                                SELECT
                                    ml.*,
                                    mat.action_name,
                                    mat.log_action_table,
                                    '$dbID' as dbid,
                                    ml.id_list,
                                   -- u2.username as user_from,
                                   (
                                   		SELECT 
                                   			DISTINCT(username) 
                                   		FROM ds_money_account_payments map2
										INNER JOIN ds_user AS u2 ON (u2.id_user = map2.id_user)
										WHERE 
											map2.id_list = ml.id_list
											AND 
											map2.id_user != u.id_user
									) as user_to,
                                    u.username as user_from,
                                    map1.gc_delta,
                                    map1.sc_delta,
                                    wc.worldname,
                                    (SELECT wc.worldname
                                    FROM ds_worldcollection as wc
                                    WHERE wc.world_identifier = (
                                    case
                                     WHEN
			         mat.id_money_action = 1
			        THEN
			         (
			         SELECT mlis.to_world_idetifier
			         FROM
			         ds_money_list_interworld_send as mlis
			         WHERE
			         mlis.id_list = ml.id_list
                        )
                                          WHEN
			         mat.id_money_action = 17
			        THEN
			         (
			         SELECT mlir.from_world_identifier
			         FROM
			         ds_money_list_interworld_recive as mlir
			         WHERE
			         mlir.id_list = ml.id_list
                        ) END 
                        )
                        ) as detail
                                FROM
                                    ds_money_list AS ml
                                    
                                    INNER JOIN ds_money_account_payments AS map1 ON (ml.id_list = map1.id_list)
                                  --  INNER JOIN ds_money_account_payments AS map2 ON (ml.id_list = map2.id_list)
                                    
                                    INNER JOIN ds_money_action_types AS mat ON (mat.id_money_action = ml.id_action)
                                    INNER JOIN ds_worldcollection AS wc ON (wc.world_identifier = ml.world_identifier)
                                
                                    INNER JOIN ds_user AS u ON (u.id_user = map1.id_user)
                                  --  INNER JOIN ds_user AS u2 ON (u2.id_user = map2.id_user)
                                WHERE
                                        wc.id_world = ".$worldID."
                                        AND
                                        wc.dbname = '".$GLOBALS['dbWorldAccess'][$dbID]['server']."'                    
                                        $where
										$whereAction
                                ORDER BY ml.add_date, ml.id_list DESC
                                
                ";
			}
			else {

				$sql = "
                                SELECT
                                    ml.*,
                                    mat.action_name,
                                    mat.log_action_table,
                                    '$dbID' as dbid,
                                    ml.id_list,
                                    u2.username as user_from,
                                    u.username as user_to,
                                    map1.gc_delta,
                                    map1.sc_delta,
                                    wc.worldname,
                                    (SELECT wc.worldname
                                    FROM ds_worldcollection as wc
                                    WHERE wc.world_identifier = (
                                    case
                                     WHEN
			         mat.id_money_action = 1
			        THEN
			         (
			         SELECT mlis.to_world_idetifier
			         FROM
			         ds_money_list_interworld_send as mlis
			         WHERE
			         mlis.id_list = ml.id_list
                        )
                                          WHEN
			         mat.id_money_action = 17
			        THEN
			         (
			         SELECT mlir.from_world_identifier
			         FROM
			         ds_money_list_interworld_recive as mlir
			         WHERE
			         mlir.id_list = ml.id_list
                        ) END 
                        )
                        ) as detail
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
                                        wc.dbname = '".$GLOBALS['dbWorldAccess'][$dbID]['server']."'                    
                                        $where
										$whereAction
                                ORDER BY ml.add_date, ml.id_list DESC
                                
                ";
			}
 //echo "<pre>".$sql."<br><br>";  
			$res = $common->getResultArray("", $sql, "", "", "");
		
			if (!empty($common->errormsg))
			{
				echo "<font colol='red'>Error: $common->errormsg</font>";
				exit;
			}

			//Проходимся по выборке
			foreach ($res as $key => $data)
			{
				$fromUser = '';
				
				//Если тип операции перевод кредитов игроку DestinySphere или налоги на торговлю
				if ($data['action_name'] == 'TRADE_PAYMENT' 
						OR 
						$data['action_name'] == 'PAY_TRADE_TAX'
						OR 
						$data['action_name'] == 'TRADE_PAYMENT'
						OR 
						$data['action_name'] == 'MOVE_TO_RESERV'
						OR 
						$data['action_name'] == 'MOVE_FROM_RESERV')
				{
					if ($data['action_name'] == 'MOVE_FROM_RESERV')
					{
						$from = 'ds_money_list_trade_payments';
					}
					
					if ($data['action_name'] == 'TRADE_PAYMENT')
					{
						$from = 'ds_money_list_trade_payments';
					}
					
					if ($data['action_name'] == 'PAY_TRADE_TAX')
					{
						$from = 'ds_money_list_trade_tax';
					}
					
					if ($data['action_name'] == 'MOVE_TO_RESERV')
					{
						$from = 'ds_money_list_to_reserv';
						$fromUser = ' (
										SELECT u.username 
										FROM ds_user as u
		            					WHERE u.id_user = th.from_user_id) as userName,
		            				';
					}
					
					if ($data['action_name'] == 'MOVE_FROM_RESERV')
					{
						$from = 'ds_money_list_from_reserv';
						$fromUser = ' (
										SELECT u.username 
										FROM ds_user as u
		            					WHERE u.id_user = th.from_user_id) as username,
		            				';
					}
					
					//Мыястяем что именно было продано
					$sql = "SELECT
		            th.from_ammount,
		            $fromUser
		            CASE
					     -- 1 recource
					     WHEN
					      th.id_from_category = 1
					        THEN
					          (
					           SELECT
					            ltl.text
					           FROM
					            ds_attributename an,
					            ds_localizedtextlanguage ltl
					                WHERE
					                 th.id_from_article = an.id_attributename
					                 AND an.id_localizedname = ltl.id_localizedtext
					                    AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
					            )
					        -- 2 units
					        WHEN
					         th.id_from_category = 2
					        THEN
					         (
					          SELECT
					           ltl.text
					          FROM
					           ds_unit uni,
					           ds_localizedtextlanguage ltl
					                WHERE
					                 th.id_from_article = uni.id_unit
					                 AND uni.id_localizedname = ltl.id_localizedtext
					                    AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
					            )
					        -- 3 arts
					        WHEN
					         th.id_from_category = 3
					        THEN
					         (
					          SELECT
					           ltl.text
					          FROM
					           ds_artefact art,
					           ds_localizedtextlanguage ltl
					                WHERE
					                 th.id_from_article = art.id_artefact
					                 AND art.id_localizedname = ltl.id_localizedtext
					                    AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
					            )
					        -- 4 money
					        WHEN
					         th.id_from_category = 4
					        THEN
					         (
					          SELECT
					           ltl.text
					          FROM
					           ds_money mon,
					           ds_localizedtextlanguage ltl
					                WHERE
					                 th.id_from_article = mon.id_money
					                 AND mon.id_locolizedname = ltl.id_localizedtext
					                    AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
					            )
					        
					    END AS goods_from
		                                  FROM
		                                  ds_trade_history as th
		                                  WHERE th.id_trade=(
		                                  						SELECT 
		                                  							mt.id_trade
																FROM
                                  									$from as mt
                                  								WHERE 
                                  									mt.id_list=".$data['id_list']."
                                  							)
                                 ";

					$goods = $common->getResultArray("", $sql, "", "", "");
					assert(empty($common->errormsg));
					
					//Добавляем к массиву новое поле
					$res[$key]['detail'] = number_format($goods[0]['from_ammount'])." ".$goods[0]['goods_from'];
					
					if (key_exists('username', $goods[0])) {
						$res[$key]['detail'] .= '. User: '.$goods[0]['username'];
					}
				}
			}
			//Объединяем массивы
			$result = array_merge($result, $res);
		}
		return $result;
	}

	static public function getCurrentAccount()
	{
		$userName = $_SESSION['user'];

		if(empty($userName))
		{
			return;
		}

		//$common = DS_Common::CreateForCurrentDB($DBID);
		$common = DS_Common::CreateForCurrentDB();

		//Проверяем, есть ли пользователь с введенным ником
		$sql = "SELECT
                                 u.id_user
                                FROM ds_user as u
                                WHERE
                                 u.username = '$userName'";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if (count($res)==0)
		{
			return;
		}

		$IDuser = (int)$res[0]['id_user'];

		$world = DE_WorldFromCollection::CreateFromID('UC');

		$user = new DE_User($IDuser);
		$member = new MU_EntityOperationMember($user, $world);
		$data['USER'] = MU_SearchOperations::GetAccountData($member);

		GE_SearchStart::SetCommon($common);
		$userWorlds = GE_SearchStart::GetWorldsWhereUserIsRegistered($IDuser);

		foreach($userWorlds as $k => $instance)
		{
			foreach($GLOBALS['dbWorldAccess'] as $k => $db)
			{
				if($k['server'] == $instance['dbname'])
				{
					$dbId = $k;
					break;
				}
			}

			$world = DE_WorldFromCollection::CreateFromID($instance['world_identifier']);
			assert($world instanceof DE_WorldFromCollection);
			
			$member = new MU_EntityOperationMember($user, $world);
			assert($member instanceof MU_EntityOperationMember);

			$account = MU_SearchOperations::GetAccountData($member);

			if(count($account) > 0)
			{
				$data['USER_INSTANCE'][$instance['worldname']] = $account;
				$data['USER_INSTANCE'][$instance['worldname']]['instance'] = $dbId.'_'.$instance['world_identifier'].'_'.$instance['id_userinstance'];
			}
		}

		return $data;
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

	static public function getActionType($DBID)
	{
		$common = DS_Common::CreateForCurrentDB(0);


		$sql = "
                        SELECT *
                        FROM ds_money_action_types                      ";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}

}

?>
