<?php

class TransferStatSearch
{
	static public function GetTransferStat($from, $to, $worlds, $detail, $flag)
	{
		$transferDirection = array('from', 'to');

		if ($flag == true)
		{
			foreach ($worlds as $key => $worldArr)
			{
				$tempArr = explode('_', $worldArr);
				$dbId = $tempArr[0];
				$worldId = $tempArr[1];

				assert($dbId >= 0);
				assert($worldId >= 0);

				foreach ($transferDirection as $key => $direction)
				{
					$common = DS_Common::CreateForCurentDB(0);

					$sql = "
							SELECT
							count(*) as count,
								".self::_formSQLPart($detail, true)."
							FROM
								ds_worldtransfer_stat
							WHERE
								 transfer_date > '$from'
								AND
								 transfer_date < '$to'
								 AND
								 db_$direction = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."' AND id_world_$direction = '$worldId'
							GROUP BY
								".self::_formSQLPart($detail)."
							ORDER BY
								".self::_formSQLPart($detail)."
							";

					$res = $common->getResultArray("", $sql, "", "", "");

					if (!empty($common->errormsg))
					{
						echo "<font color=#F8F8FF>Ошибка SQL!!! $common->errormsg</font>";
						exit;
					}

					//Получаем имя мира по его id
					$worldName  = self::GetWorldNameById($dbId, $worldId);
					
					if(empty($worldName))
					{
						echo "<font color='white'>Wrong world name. dbName = ".$GLOBALS['dbWorldAccess'][$dbId]['server'].", dbId = $dbId, worldId = $worldId</font>";
						exit;
					}

					//Формируем дату
					foreach ($res as $k => $dateArr)
					{
						$separator = '';
						foreach ($dateArr as $period => $value)
						{
							if ($period == 'count')
							{
								continue;
							}
							$date .= $separator.$value;
					
							if ($period == 'year' OR $period == 'month') 
							{
								$separator = '-';
							}
							
							if ($period == 'day') 
							{
								$separator = ' ';
							}
								
							if ($period == 'hour') 
							{
								$separator = 'hour';
								
							}
						}
						$resArr[$worldArr."_".$worldName][$direction][$date] = $dateArr['count'];
						$date = '';
					}
				}
			}
		} else {
			foreach ($worlds as $key => $worldArr)
			{
				$tempArr = explode('_', $worldArr);
				$dbId = $tempArr[0];
				$worldId = $tempArr[1];

				assert($dbId >= 0);
				assert($worldId >= 0);

				foreach ($transferDirection as $key => $direction)
				{
					$common = DS_Common::CreateForCurentDB(0);

					$sql = "
							SELECT
								count(*)
							FROM
								ds_worldtransfer_stat as ws
							WHERE
								ws.transfer_date > '$from'
								AND
								ws.transfer_date < '$to'
								AND
								 ws.db_$direction = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."' AND ws.id_world_$direction = '$worldId'
							";

					$res = $common->getResultArray("", $sql, "", "", "");
					if (!empty($common->errormsg))
					{
						echo "<font color=#F8F8FF>Ошибка SQL!!! $common->errormsg</font>";
						exit;
					}
					
					//Получаем имя мира по его id
					$worldName  = self::GetWorldNameById($dbId, $worldId);
					if(empty($worldName))
					{
						echo "<font color='white'>Wrong world name. dbName = ".$GLOBALS['dbWorldAccess'][$dbId]['server'].", dbId = $dbId, worldId = $worldId</font>";
						exit;
					}
					
					$resArr[$worldName][$direction] = $res[0]['count'];
				}
			}

		}
		
		return $resArr;
	}
	
	static public function GetHeader($from, $to, $worlds, $detail)
	{
		$transferDirection = array('from', 'to');
		$header = array();

		$common = DS_Common::CreateForCurentDB(0);

		foreach ($worlds as $key => $worldArr)
		{
			$tempArr = explode('_', $worldArr);
			$dbId = $tempArr[0];
			$worldId = $tempArr[1];

			assert($dbName >= 0);
			assert($worldId >= 0);

			foreach ($transferDirection as $key => $direction)
			{
				$sql = "
				SELECT
					".self::_formSQLPart($detail, true)."
				FROM
					ds_worldtransfer_stat
				WHERE
					 transfer_date > '$from'
					AND
					 transfer_date < '$to'
					AND
					 db_$direction = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."' AND id_world_$direction = '$worldId'
				GROUP BY
					".self::_formSQLPart($detail)."
				ORDER BY
					".self::_formSQLPart($detail)."
				";

				$res = $common->getResultArray("", $sql, "", "", "");
		
				assert(empty($common->errormsg));

				//Проходимся по результату
				foreach ($res as $k => $dateArr)
				{
					$separator = '';
					foreach ($dateArr as $period => $value)
					{
						$data .= $separator.$value;
						
							if ($period == 'year' OR $period == 'month') 
							{
								$separator = '-';
							}
							
							if ($period == 'day') 
							{
								$separator = ' ';
							}
								
							if ($period == 'hour') 
							{
								$separator = 'hour';
								
							}					
					}
					
					//Если данного промежутка еще нет в результирующем массиве, то добавляем его
					if (!in_array($data, $resArr))
					{
						$resArr[] = $data;
					}
					$data = '';
				}
				
			}
		}

		sort($resArr);
		return $resArr;
	}

	private function _formSQLPart($detail, $alias = false)
	{
		$dateParts = array(
		0 => 'year',
		1 => 'month',
		//2 => 'week',
		2 => 'day',
		3 => 'hour',
		);

		for($i=0; $i<=$detail; $i++)
		{
			$sql[$i] = "extract(".$dateParts[$i]." from transfer_date)";
			if($alias)
			{
				$sql[$i] .= ' as '.$dateParts[$i];
			}
		}

		return implode(', ', $sql);
	}


	static public function GetWorldList()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$out = array();

		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			foreach($res as $k=>$v)
			{
				$keyRes = $dbid."_".$v['id_world'];
				$out[$keyRes] = $v['worldname'];
			}
		}
		
		return $out;
	}
	
	static public function GetWorldNameById($dbId, $worldId)
	{
		$common = DS_Common::CreateForCurentDB($dbId);
		
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
	}
	
	static public function GetTransferStatFrom($from, $to, $worldAndBdId, $detail, $direction)
	{
		$generalDirection = $direction;
		
		if ($direction == 'to') 
		{
			$secondDirection = 'from';
		} 
		if ($direction == 'from')
		{
			$secondDirection = 'to';
		}

		$tempArr = explode('_', $worldAndBdId);
		$dbId = $tempArr[0];
		$worldId = $tempArr[1];

		assert($dbId >= 0);
		assert($worldId >= 0);

		$common = DS_Common::CreateForCurentDB(0);

		//Получем список миров в которые переезжали люди
		$sql = "SELECT
				id_world_$secondDirection,
				db_$secondDirection
				FROM
					ds_worldtransfer_stat
				WHERE
				  transfer_date > '$from'
					AND
					 transfer_date < '$to'
					AND
					 db_$generalDirection = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."' AND id_world_$generalDirection = '$worldId'
					GROUP BY
					 id_world_$secondDirection,
					 db_$secondDirection";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		//Проходимся по каждому из этих миров
		foreach ($res as $key => $data)
		{
			$arrElementDb = 'db_'.$secondDirection;
			$arrElementWorld = 'id_world_'.$secondDirection;
			
			$common = DS_Common::CreateForCurentDB(0);
			
			$sql = "
				SELECT
				count(*) as count,
					".self::_formSQLPart($detail, true)."
				FROM
					ds_worldtransfer_stat
				WHERE
					 transfer_date > '$from'
					AND
					 transfer_date < '$to'
					AND
					 db_$generalDirection = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."' AND id_world_$generalDirection = '$worldId'
					AND 
					 db_$secondDirection = '".$data[$arrElementDb]."' AND id_world_$secondDirection = '".$data[$arrElementWorld]."'	
				GROUP BY
					id_world_$secondDirection, ".self::_formSQLPart($detail)."
				ORDER BY
					".self::_formSQLPart($detail)."
				";

			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));

			//Получаем имя мира по его id
			$dbId = $common->getDBIDByName($data[$arrElementDb]);
			assert($dbId >= 0);
			$worldName  = self::GetWorldNameById($dbId, $data[$arrElementWorld]);
			
			if(empty($worldName))
			{
				echo "<font color='white'>Wrong world name. dbName = $dbName, dbId = $dbId, worldId = $worldId</font>";
				exit;
			}

			//Формируем дату
			foreach ($res as $k => $dateArr)
			{
				$separator = '';
				foreach ($dateArr as $period => $value)
				{
					if ($period == 'count')
					{
						continue;
					}
					$date .= $separator.$value;

					if ($period == 'year' OR $period == 'month')
					{
						$separator = '-';
					}

					if ($period == 'day')
					{
						$separator = ' ';
					}

					if ($period == 'hour')
					{
						$separator = 'hour';
					}
				}
				$resArr[$worldName][$date] = $dateArr['count'];
				$date = '';
			}
		}

		return $resArr;
	}
	
	static public function GetHeaderFrom($from, $to, $worldAndBdId, $detail, $direction)
	{
		$generalDirection = $direction;
		
		if ($direction == 'to') 
		{
			$secondDirection = 'from';
		} 
		if ($direction == 'from')
		{
			$secondDirection = 'to';
		}

		$tempArr = explode('_', $worldAndBdId);
		$dbId = $tempArr[0];
		$worldId = $tempArr[1];

		assert($dbId >= 0);
		assert($worldId >= 0);

		$common = DS_Common::CreateForCurentDB(0);

		//Получем список миров в которые переезжали люди
		$sql = "SELECT
				id_world_$secondDirection,
				db_$secondDirection
				FROM
					ds_worldtransfer_stat
				WHERE
				  transfer_date > '$from'
					AND
					 transfer_date < '$to'
					AND
					 db_$generalDirection = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."' AND id_world_$generalDirection = '$worldId'
					GROUP BY
					 id_world_$secondDirection,
					 db_$secondDirection";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		//Проходимся по каждому из этих миров
		foreach ($res as $key => $data)
		{
			$arrElementDb = 'db_'.$secondDirection;
			$arrElementWorld = 'id_world_'.$secondDirection;
			
			$sql = "
				SELECT
					".self::_formSQLPart($detail, true)."
				FROM
					ds_worldtransfer_stat
				WHERE
					 transfer_date > '$from'
					AND
					 transfer_date < '$to'
					AND
					 db_$generalDirection = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."' AND id_world_$generalDirection = '$worldId'
					AND 
					 db_$secondDirection = '".$data[$arrElementDb]."' AND id_world_$secondDirection = '".$data[$arrElementWorld]."'	
					GROUP BY
					id_world_$secondDirection, ".self::_formSQLPart($detail)."
				ORDER BY
					".self::_formSQLPart($detail)."
				";
 
				$res = $common->getResultArray("", $sql, "", "", "");

				assert(empty($common->errormsg));

				//Проходимся по результату
				foreach ($res as $k => $dateArr)
				{
					$separator = '';
					foreach ($dateArr as $period => $value)
					{
						$date .= $separator.$value;

						if ($period == 'year' OR $period == 'month')
						{
							$separator = '-';
						}

						if ($period == 'day')
						{
							$separator = ' ';
						}

						if ($period == 'hour')
						{
							$separator = 'hour';
						}
					}
			
					//Если данного промежутка еще нет в результирующем массиве, то добавляем его
					if (!in_array($date, $resArr))
					{
						$resArr[] = $date;
					}
					$date = '';
				}
		}	
		sort($resArr);		

		return $resArr;
	}
}
?>