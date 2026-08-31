<?php

class SU_ReplicationTableDataSync
{
	static private $onlyValidate = false;
	
	static private $tableFields;
	
	static private $tableName;
	
	static private $slaveList;
	
	static private $masterTableData;
	
	static private $slaveTableData;
	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static public function SetCommon(DS_Common $common)
	{
		self::$common = $common;
	}
	
	/**
	 * Синхронизировать данные таблицы на слейвах с данными на мастере
	 *
	 * @param string $tableName
	 */
	static public function Sync($tableName, $onlyValidate = false)
	{
		assert(!empty($tableName));
		assert(is_bool($onlyValidate));
		assert(self::$common instanceof DS_Common);
		
		$dbId = self::$common->dbId;
		
		self::$tableName = $tableName;
		
		self::$onlyValidate = $onlyValidate;
		
		self::_getTableInfo();
		
		self::_getSlaveList();
		
		self::_getAllTableData();
		
		self::_compareData();
		
		self::_generateSQL();
		
		self::_syncData();
		
		self::$common->setDbId($dbId);
		
		SU_ReplicationSearch::MarkTableSyncronized(self::$common, self::$tableName);
		
		return true;
	}
	
	static private function _getTableInfo()
	{
		$tableInfo = SU_ReplicationSearch::GetReplicationTableInfo(self::$common, self::$tableName);
		
		assert(is_array($tableInfo));
		assert(count($tableInfo) == 1);
		assert(key_exists('table_name', $tableInfo[0]));
		assert(key_exists('field_name', $tableInfo[0]));
				
		self::$tableFields = explode(',', $tableInfo[0]['field_name']);
		assert(count(self::$tableFields) > 0);
	}
	
	static private function _getSlaveList()
	{
		self::$slaveList = SU_ReplicationSearch::GetSlaveList(self::$common);
		assert(count(self::$slaveList) > 0);
		
		foreach (self::$slaveList as $k => $slave)
		{
			assert(is_array($slave));
			assert(key_exists('id_slave', $slave));
			assert(key_exists('server', $slave));
			self::$slaveList[$k]['SYNC_DATA'] = array();
		}
	}
	
	static private function _getAllTableData()
	{
		self::$masterTableData = SU_ReplicationSearch::GetAllTableData(self::$common, self::$tableName);
		
		foreach (self::$slaveList as $k => $slave)
		{
			self::$common->setDbId(self::$common->getDbIdByName($slave['server']));
			self::$slaveTableData[$k] = SU_ReplicationSearch::GetAllTableData(self::$common, self::$tableName);
		}
	}
	
	static private function _compareData()
	{
		foreach (self::$slaveList as $k => $slave)
		{
			foreach (self::$masterTableData as $l => $data)
			{
				$res = self::_compareMasterAndSlaveData($k, $data);
				if($res != '-')
				{
					self::$slaveList[$k]['SYNC_DATA'][] = array('ACTION' => $res, 'DATA' => $data);
				}
			}
		}
		
		self::$masterTableData = array();
		
		// что осталось из данных после прохода слейвов, помечаем на удаление
		foreach (self::$slaveTableData as $k => $slaveData)
		{
			foreach ($slaveData as $l => $data)
			{
				self::$slaveList[$k]['SYNC_DATA'][] = array('ACTION' => 'D', 'DATA' => $data);
			}
		}

		self::$slaveTableData = array();
	}
	
	static private function _compareMasterAndSlaveData($slave, array $masterData)
	{
		assert(is_int($slave));
		assert(key_exists($slave, self::$slaveTableData));
		foreach (self::$tableFields as $k => $field)
		{
			assert(key_exists($field, $masterData));
		}
		
		foreach (self::$slaveTableData[$slave] as $k => $slaveData)
		{
			assert(count($slaveData) == count($masterData));

			foreach(self::$tableFields as $l => $field)
			{
				assert(key_exists($field, $slaveData));
				if($slaveData[$field] != $masterData[$field])
				{
					// это не та запись не нужно боьлше сравнивать
					continue 2;
				}
			}
			
			// если дошли сюда, значит первичные ключи полей совпадают
			
			// удаляем запись, чтобы в конце остались в массиве self::$slaveTableData[$slave] только те,
			// которые следует удалить из таблицы на слейве. да и сравнивать будем с меньшим кол-вом записей
			unset(self::$slaveTableData[$slave][$k]);
			
			foreach ($slaveData as $field => $value)
			{
				if($value != $masterData[$field])
				{
					// нужно проапдейтить запись
					return 'U';
				}
			}
			
			// и ключи и поля совпадаюст, ничего с записью делать не нужно
			return '-';
		}
		
		// такой записи на слейве не нашли, нужно вставить
		return 'I';
	}
	
	static private function _generateUpdateSQL(array $data)
	{
		$set = array();
		$where = array();
		
		foreach ($data as $field => $value)
		{
			if(in_array($field, self::$tableFields))
			{
				$where[] = $field." = '".$value."'";
			} else {
				if(is_null($value))
				{
					$value = 'NULL';
				} else {
					$value = "'".$value."'";
				}
				
				$set[] = $field.' = '.$value;
			}
		}
		
		$sql = "
			UPDATE
				".self::$tableName."
			SET
				".implode(',', $set)."
			WHERE
				".implode(' AND ', $where)."
			";
		
		return $sql;
	}
	
	static private function _generateInsertSQL(array $data)
	{
		$fields = array_keys($data);
		
		foreach ($data as $field => $value)
		{
			if(is_null($data[$field]))
			{
				$data[$field] = 'NULL';
			} else {
				$data[$field] = "'".$value."'";
			}
		}
				
		$sql = "
			INSERT INTO ".self::$tableName."
				(".implode(',', $fields).")
			VALUES
				(".implode(',', $data).")
			";
		
		return $sql;
	}
	
	static private function _generateDeleteSQL(array $data)
	{
		$where = array();
		
		foreach ($data as $field => $value)
		{
			if(in_array($field, self::$tableFields))
			{
				$where[] = $field." = '".$value."'";
			}
		}
		
		$sql = "
			DELETE FROM 
				".self::$tableName."
			WHERE
				".implode(' AND ', $where)."
			";
		
		return $sql;
	}
	
	static private function _generateSQL()
	{
		if(self::$onlyValidate)
		{
			print_r(self::$slaveList);
		}
		
		foreach (self::$slaveList as $k => $slave)
		{
			foreach ($slave['SYNC_DATA'] as $l => $data)
			{
				assert(key_exists('ACTION', $data));
				assert(key_exists('DATA', $data));
				
				switch ($data['ACTION'])
				{
					case 'U':
						$sql = self::_generateUpdateSQL($data['DATA']);
						break;
				
					case 'I':
						$sql = self::_generateInsertSQL($data['DATA']);
						break;

					case 'D':
						$sql = self::_generateDeleteSQL($data['DATA']);						
						break;
				}
				
				self::$slaveList[$k]['SYNC_DATA'][$l]['SQL'] = $sql;
			}
		}
	}
	
	static private function _syncData()
	{
		if(self::$onlyValidate)
		{
			return true;
		}
		
		foreach (self::$slaveList as $k => $slave)
		{
			self::$common->setDbId(self::$common->getDbIdByName($slave['server']));
			
			SU_ReplicationSearch::DisableTriggers(self::$common, self::$tableName);
			if(!empty(self::$common->errormsg))
			{
				return false;
			}
				
			self::$common->startTrans();
			
			foreach ($slave['SYNC_DATA'] as $l => $data)
			{
				assert(key_exists('SQL', $data));
				self::$common->executeQuery($data['SQL']);
				if(!empty(self::$common->errormsg))
				{
					self::$common->closeTrans('ROLLBACK');
					return false;
				}
			}
			
			self::$common->closeTrans('COMMIT');
			
			SU_ReplicationSearch::EnableTriggers(self::$common, self::$tableName);
			assert(empty(self::$common->errormsg));
		}
		
		return true;
	}
}

?>