<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2007/06/26 10:58:51 $';

/**
 * Запросы для системы репликации
 *
 */
class SU_ReplicationSearch
{
	static private $error = '';
	
	static public function FetchError()
	{
		return self::$error;
	}
	
	/**
	 * Получение информации об измененых записях
	 *
	 * @param DS_Common $common
	 * @param int $limit
	 * @return array
	 */
	static public function GetReplicationData(DS_Common $common, $limit = 10)
	{
		assert(is_int($limit) && $limit > 0);

		$sql = "
			SELECT
				*
			FROM
				ds_replication
			ORDER BY id_replication
			LIMIT $limit
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		
		self::$error = $common->errormsg;
		
		return $res;
	}
	
	/**
	 * Получение списка рабов
	 *
	 * @param DS_Common $common
	 * @return array
	 */
	static public function GetSlaveList(DS_Common $common)
	{
		$sql = "SELECT * FROM ds_replication_slave";
		$res = $common->getResultArray("", $sql, "", "", "");
		self::$error = $common->errormsg;

		return $res;
	}
	
	/**
	 * Получение информации для репликации
	 *
	 * @param DS_Common $common
	 * @param SU_ReplicationEntityData $rec
	 * @return array
	 */
	static public function GetReplicationTableData(DS_Common $common, SU_ReplicationEntityData $rec)
	{
		$rec->validate();
		
		$where = array();
		foreach ($rec->fields as $k => $field)
		{
			$where[] = trim($field)." = '".trim($rec->id[$k])."'";
		}
		
		$sql = "SELECT * FROM ".trim($rec->tableName)." WHERE ".implode(' AND ', $where);
		$res = $common->getResultArray("", $sql, "", "", "");
		self::$error = $common->errormsg;
		
		return $res;
	}
	
	/**
	 * Удаление инфы об изменившихся данных с мастера
	 *
	 * @param DS_Common $common
	 * @param int $id
	 */
	static public function DeleteReplicationData(DS_Common $common, $id)
	{
		assert(is_int($id) && $id > 0);
		$sql = "DELETE FROM ds_replication WHERE id_replication = $id";
		$common->executeQuery($sql);
	}
	
	/**
	 * Синхронизация данных
	 *
	 * @param DS_Common $common
	 * @param SU_ReplicationEntityData $replicationData
	 */
	static public function ReplicateData(DS_Common $common, SU_ReplicationEntityData $replicationData)
	{
		$replicationData->validate();
		
		// защита от повторной вставки данных
			// когда будет реализован запрет записи на слейвы, будет особо не нужна такая проверка
		if($replicationData->action == 'I' && count(self::GetReplicationTableData($common, $replicationData)) > 0)
		{
			// если уже есть такая запись на слейве, то апдейтим
			$replicationData->action = 'U';
		}
		
		switch ($replicationData->action)
		{
			case 'U':
				$sql = self::_makeUpdateSQL($replicationData);
				break;
				
			case 'I':
				$sql = self::_makeInsertSQL($replicationData);
				break;
				
			case 'D':
				$sql = self::_makeDeleteSQL($replicationData);
				break;
		}
		
		$common->executeQuery($sql);
		
		self::$error = $common->errormsg;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param SU_ReplicationEntityData $replicationData
	 * @return string
	 */
	static private function _makeUpdateSQL(SU_ReplicationEntityData $replicationData)
	{
		$replicationData->validate();
		$replicationData->validateData();
		
		$fields = array();
	
		foreach($replicationData->data as $field => $value)
		{
			if(in_array($field, $replicationData->fields))
			{
				continue;
			}
			
			if ($field == 'text') {
				$value = addslashes($value);
			}
						
			if(is_null($value))
			{
				$value = 'null';
			} else {
				$value = "'".$value."'";
			}
			
			$fields[] = $field."=".$value;
		}
		
		$where = array();
		foreach ($replicationData->fields as $k => $field) 
		{
			$where[] = $field." = '".$replicationData->id[$k]."'";
		}		
		
		$sql = "
			UPDATE
				".$replicationData->tableName."
			SET
				".implode(', ', $fields)."
			WHERE
				".implode(' AND ', $where)."
			";
		return $sql;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param SU_ReplicationEntityData $replicationData
	 * @return string
	 */
	static private function _makeInsertSQL(SU_ReplicationEntityData $replicationData)
	{
		$replicationData->validate();
		$replicationData->validateData();
		
		$fields = array();
		$values = array();
			
		foreach ($replicationData->data as $field => $value)
		{
			$fields[] = $field;
			
			if ($field == 'text') {
				$value = addslashes($value);
			}
	
			if(is_null($value))
			{
				$value = 'null';
			} else {
				$value = "'".$value."'";
			}
			$values[] = $value;
		}
		
		$sql = "
			INSERT INTO	".$replicationData->tableName."
				(".implode(', ', $fields).")
			VALUES
				(".implode(', ', $values).")
			";
		
		return $sql;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param SU_ReplicationEntityData $replicationData
	 * @return string
	 */
	static private function _makeDeleteSQL(SU_ReplicationEntityData $replicationData)
	{
		$replicationData->validate();
		
		$where = array();
		foreach ($replicationData->fields as $k => $field) 
		{
			$where[] = $field." = '".$replicationData->id[$k]."'";
		}		

		$sql = "DELETE FROM ".$replicationData->tableName." WHERE ".implode(' AND ', $where);
		
		return $sql;
	}
	
/************ Запросы для синхронизации данных таблиц при добавлении на репликацию *************/
	
	/**
	 * Данные о таблице
	 *
	 * @param DS_Common $common
	 * @param string $tableName
	 * @return array
	 */
	static public function GetReplicationTableInfo(DS_Common $common, $tableName)
	{
		assert(!empty($tableName));
		$sql = "
			SELECT
				*
			FROM
				ds_replication_tables
			WHERE
				table_name = '$tableName'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		self::$error = $common->errormsg;
		
		return $res;
	}
	
	/**
	 * Выборка всех данных из таблицы
	 *
	 * @param DS_Common $common
	 * @param string $tableName
	 * @return array
	 */
	static public function GetAllTableData(DS_Common $common, $tableName)
	{
		assert(!empty($tableName));
		$sql = "SELECT * FROM $tableName";
		$res = $common->getResultArray("", $sql, "", "", "");
		self::$error = $common->errormsg;
		
		return $res;
	}
	
	/**
	 * Отмечаем дату последнего сравнения таблиц (в дальнейшем можно использовать 
	 * для регулярных сравнений, а не только при добавлении репликации)
	 *
	 * @param DS_Common $common
	 * @param string $tableName
	 */
	static public function MarkTableSyncronized(DS_Common $common, $tableName)
	{
		assert(!empty($tableName));
		$sql = "UPDATE ds_replication_tables SET last_sync_time = now() WHERE table_name = '$tableName'";
		$res = $common->executeQuery($sql);
		self::$error = $common->errormsg;
	}
	
	/**
	 * Отключение тригеров (в том числе и системных, от внешних ключей)
	 *
	 * @param DS_Common $common
	 * @param string $tableName
	 */
	static public function DisableTriggers(DS_Common $common, $tableName)
	{
		assert(!empty($tableName));
		$sql = "ALTER TABLE $tableName DISABLE TRIGGER ALL";
		$res = $common->executeQuery($sql);
		self::$error = $common->errormsg;
	}
	
	/**
	 * Включение тригеров
	 *
	 * @param DS_Common $common
	 * @param string $tableName
	 */
	static public function EnableTriggers(DS_Common $common, $tableName)
	{
		assert(!empty($tableName));
		$sql = "ALTER TABLE $tableName ENABLE TRIGGER ALL";
		$res = $common->executeQuery($sql);
		self::$error = $common->errormsg;
	}
}

?>