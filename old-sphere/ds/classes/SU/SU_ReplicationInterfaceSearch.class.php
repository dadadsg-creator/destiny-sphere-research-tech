<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Запросы для интерфейса управления репликацией
 *
 */
class SU_ReplicationInterfaceSearch
{
	/**
	 * Список всех таблиц
	 *
	 * @param DS_Common $common
	 * @return unknown
	 */
	static public function GetTableList(DS_Common $common)
	{
		$sql = "
			SELECT 
				relname,
				oid
			FROM 
				pg_class 
			WHERE 
				relkind = 'r'
				AND
				relname like 'ds_%'
			ORDER BY relname
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		$tablesTmp = array();
		
		foreach ($res as $k => $table)
		{
			$tablesTmp[$table['oid']] = $table['relname'];
		}
		
		return $tablesTmp;
	}
	
	/**
	 * Есть ли таблица в базе
	 *
	 * @param DS_Common $common
	 * @param unknown_type $tableName
	 * @return unknown
	 */
	static public function CheckTablePresenceInDB(DS_Common $common, $tableName)
	{
		assert(!empty($tableName));
		
		$sql = "
			SELECT 
				oid
			FROM 
				pg_class 
			WHERE 
				relkind = 'r'
				AND
				relname = '$tableName'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return (int)$res[0]['oid'];
		}
		
		return 0;
	}
	
	/**
	 * Есть ли у таблице тригеры для репликации (название ds_replication_названиетаблицы_действие)
	 *
	 * @param DS_Common $common
	 * @param unknown_type $TableID
	 * @return unknown
	 */
	static public function GetTableReplicationTriggers(DS_Common $common, $TableID)
	{
		assert(is_int($TableID));
		
		$sql = "
			SELECT 
				tgname
			FROM
				pg_trigger 
			WHERE
				tgrelid = '$TableID' 
				AND 
				tgname like 'ds_replication_ds_%';
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
				
		return $res;
	}
	
	/**
	 * Столбцы таблицы
	 *
	 * @param DS_Common $common
	 * @param unknown_type $TableID
	 * @return unknown
	 */
	static public function GetTableColumns(DS_Common $common, $TableID)
	{
		assert(is_int($TableID));
		
		$sql = "
			SELECT
    			attname
			FROM
    			pg_attribute
			WHERE
    			attrelid = '$TableID'
    			AND
    			attnum > 0
			ORDER BY attnum
		";		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	/**
	 * Функция вешает тригер репликации на определленый запрос (I = INSERT, U = UPDATE, D = DELETE)
	 *
	 * @param DS_Common $common
	 * @param string $tableName
	 * @param string $fieldName
	 * @param char $type
	 */
	static private function _createReplicationTriger(DS_Common $common, $tableName, array $fieldName, $type)
	{
		$functionName = 'replication_'.$tableName;
		
		switch ($type)
		{
			case 'I':
				$functionName = $functionName.'_insert';
				$statement = 'INSERT';
				$obj = 'NEW';
				break;
				
			case 'U':
				$functionName = $functionName.'_update';
				$statement = 'UPDATE';
				$obj = 'NEW';
				break;
				
			case 'D':
				$functionName = $functionName.'_delete';
				$statement = 'DELETE';
				$obj = 'OLD';
				break;
		
			default:
				assert(false);
				break;
		}
		
		$idList = array();
		foreach ($fieldName as $field)
		{
			$idList[] = $obj.'.'.$field;
		}
		
		$sql = '
			CREATE OR REPLACE FUNCTION "public"."'.$functionName.'" () RETURNS trigger AS
			$body$
			DECLARE
    			slave_v RECORD;

			BEGIN
    			FOR slave_v IN SELECT * FROM ds_replication_slave LOOP
        			INSERT INTO ds_replication
            			(id, table_name, field_name, action, id_slave)
        			VALUES
            			('.implode('||\',\'||', $idList).', \''.$tableName.'\', \''.implode(',', $fieldName).'\', \''.$type.'\', slave_v.id_slave);
    			END LOOP;

    			RETURN NEW;
			END;
			$body$
			LANGUAGE \'plpgsql\' VOLATILE CALLED ON NULL INPUT SECURITY INVOKER;
		';
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		$sql = '
			CREATE TRIGGER "ds_'.$functionName.'" AFTER '.$statement.'
			ON "public"."'.$tableName.'" FOR EACH ROW 
			EXECUTE PROCEDURE "'.$functionName.'"();
		';
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	/**
	 * Добавление таблицы в список реплицируемых
	 *
	 * @param DS_Common $common
	 * @param unknown_type $tableName
	 * @param array $fieldName
	 */
	static private function _addTableToList(DS_Common $common, $tableName, array $fieldName)
	{
		$sql = "
			INSERT INTO ds_replication_tables
				(table_name, field_name)
			VALUES
				('$tableName', '".implode(',', $fieldName)."')
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	/**
	 * Повесить репликацию на таблицу
	 *
	 * @param DS_Common $common
	 * @param unknown_type $tableName
	 * @param array $fieldName
	 */
	static public function AddTableToReplication(DS_Common $common, $tableName, array $fieldName)
	{
		assert(!empty($tableName));
		assert(!empty($fieldName));
		
		$types = array('I', 'U', 'D');
		
		$common->startTrans();
		
		foreach ($types as $k => $type)
		{
			self::_createReplicationTriger($common, $tableName, $fieldName, $type);
		}
		
		self::_addTableToList($common, $tableName, $fieldName);
		
		$common->closeTrans('COMMIT');
	}
	
	static private function _dropTrigger(DS_Common $common, $tableName, $type)
	{
		$functionName = 'replication_'.$tableName;
		
		switch ($type)
		{
			case 'I':
				$functionName = $functionName.'_insert';
				break;
				
			case 'U':
				$functionName = $functionName.'_update';
				break;
				
			case 'D':
				$functionName = $functionName.'_delete';
				break;
		
			default:
				assert(false);
				break;
		}
		
		$sql = 'DROP TRIGGER "ds_'.$functionName.'" ON "public"."'.$tableName.'";';
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		$sql = 'DROP FUNCTION "public"."'.$functionName.'"();';
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static private function _deleteTableFromList(DS_Common $common, $tableName)
	{
		$sql = "DELETE FROM ds_replication_tables WHERE table_name = '$tableName'";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	/**
	 * Снять таблицу с репликации
	 *
	 * @param DS_Common $common
	 * @param unknown_type $tableName
	 */
	static public function DropTableFromReplication(DS_Common $common, $tableName)
	{
		assert(!empty($tableName));
		
		$types = array('I', 'U', 'D');
		
		$common->startTrans();
		
		foreach ($types as $k => $type)
		{
			self::_dropTrigger($common, $tableName, $type);
		}
		
		self::_deleteTableFromList($common, $tableName);
		
		$common->closeTrans('COMMIT');
	}
	
	/**
	 * Удалить подчиненную базу
	 *
	 * @param DS_Common $common
	 * @param unknown_type $slave
	 */
	static public function DropSlave(DS_Common $common, $slave)
	{
		assert(is_int($slave) && $slave > 0);
		
		$sql = "
			DELETE FROM
				ds_replication_slave
			WHERE
				id_slave = $slave
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	/**
	 * Добавить базу
	 *
	 * @param DS_Common $common
	 * @param unknown_type $dbname
	 */
	static public function AddSlave(DS_Common $common, $dbname)
	{
		assert(!empty($dbname));
		
		$sql = "
			INSERT INTO ds_replication_slave
				(server)
			VALUES
				('$dbname')
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
}

?>