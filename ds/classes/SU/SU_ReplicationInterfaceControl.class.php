<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Контроль репликации:
 * 		- просмотр состояния										ok
 * 		- ссылка на лог-файл										ok
 * 		- управление скриптом (остановка, пауза)					ok
 *		- просмотр и редактирование списка подчиненных баз			ok
 * 		- просмотр и редактирование списка реплицируемых таблиц		ok
 */
class SU_ReplicationInterfaceControl
{
	static private $action = 'index';
	
	static private $table;
	
	static private $field;
	
	static private $dbname;
	
	static private $slaveID;
	
	static public function Exec(array $data)
	{
		self::_getParams($data);
		
		switch (self::$action)
		{
			case 'index':
				self::_outIndex();
				break;

			// управление
			case 'stop':
				self::_stopReplication();
				break;
				
			case 'pause':
				self::_pauseReplication();
				break;
				
			case 'resume':
				self::_resumeReplication();
				break;
				
				
			// список подчиненных баз
			case 'view_slave':
				self::_viewSlaveList();
				break;
				
			case 'add_slave':
				self::_addSlave();
				self::_viewSlaveList();
				break;
				
			case 'remove_slave':
				self::_removeSlave();
				self::_viewSlaveList();
				break;
				
				
			// список реплицируемых таблиц
			case 'view_table':
				self::_viewTableList();
				break;
				
			case 'add_table':
				self::_addTable();
				self::_viewTableList();
				break;
				
			case 'remove_table':
				self::_removeTable();
				self::_viewTableList();
				break;
		
			default:
				break;
		}
	}
	
	static private function _getParams(array $data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}
		
		if(key_exists('table', $data))
		{
			self::$table = $data['table'];
		}
		
		if(key_exists('col', $data))
		{
			self::$field = $data['col'];
		}
		
		if(key_exists('slave', $data))
		{
			self::$slaveID = (int)$data['slave'];
		}
		
		if(key_exists('dbname', $data))
		{
			self::$dbname = $data['dbname'];
		}
	}
	
	static private function _outIndex()
	{
		$status = trim(SU_ReplicationInterfaceComands::SendCommandToReplication('T'));
		if($status != 'OK')
		{
			if(empty($status))
			{
				$status = "ERROR";
			}
			$info = SU_ReplicationInterfaceComands::$error;
		} else {
			$info = SU_ReplicationInterfaceComands::SendCommandToReplication('D');
		}
		
		SU_ReplicationInterfaceOut::OutIndex($status, $info);
	}
	
	static private function _stopReplication()
	{
		$status = SU_ReplicationInterfaceComands::SendCommandToReplication('Q');
		
		SU_ReplicationInterfaceOut::OutIndex($status, '');
	}
	
	static private function _pauseReplication()
	{
		$status = SU_ReplicationInterfaceComands::SendCommandToReplication('P');
		
		SU_ReplicationInterfaceOut::OutIndex($status, '');
	}
	
	static private function _resumeReplication()
	{
		$status = SU_ReplicationInterfaceComands::SendCommandToReplication('R');
		
		SU_ReplicationInterfaceOut::OutIndex($status, '');
	}
	
	static private function _viewSlaveList()
	{
		$common = DS_Common::CreateForCurrentDB(0);
		
		$slaveList = SU_ReplicationSearch::GetSlaveList($common);
		
		$dbnames = array();
		
		foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			if($dbId == 0)
			{
				continue;
			}
			
			foreach ($slaveList as $k => $slave)
			{
				if($slave['server'] == $db['server'])
				{
					continue 2;
				}
			}
			
			$dbnames[] = $db['server'];
		}
		
		SU_ReplicationInterfaceOut::OutSlaveList($slaveList, $dbnames);
	}
	
	static private function _viewTableList()
	{
		$common = DS_Common::CreateForCurrentDB(0);
		
		$slaveList = SU_ReplicationSearch::GetSlaveList($common);
		
		$masterTables = SU_ReplicationInterfaceSearch::GetTableList($common);
		
		$replicatedTables = array();
		
		$trigers = array();
		
		$canBeReplicatedTables = array();
		
		$cantBeReplicatedTables = array();
		
		foreach ($masterTables as $oid => $name)
		{
			$notForReplication = false;
			
			$trigers = SU_ReplicationInterfaceSearch::GetTableReplicationTriggers($common, $oid);
			
			if(count($trigers) > 0)
			{
				$replicatedTables[] = array('ID' => $oid, 'NAME' => $name, 'TRIGGERS' => $trigers);
			} else {
				foreach ($slaveList as $k => $slave)
				{
					// check presence
					$DBID = $common->getDBIDByName($slave['server']);
					$common->setDbId($DBID);
					
					if(!SU_ReplicationInterfaceSearch::CheckTablePresenceInDB($common, $name))
					{
						$notForReplication = true;
						break;
					}
				}
				
				if($notForReplication)
				{
					$cantBeReplicatedTables[] = $name;
				} else {
					$common->setDbId(0);
					$cols = SU_ReplicationInterfaceSearch::GetTableColumns($common, $oid);
					$canBeReplicatedTables[] = array('NAME' => $name, 'COLS' => $cols);
				}
			}
			
			$common->setDbId(0);
		}
		
		SU_ReplicationInterfaceOut::OutTableList($replicatedTables, $canBeReplicatedTables, $cantBeReplicatedTables);
	}
	
	static private function _addTable()
	{
		assert(!empty(self::$table));
		assert(!empty(self::$field));
		
		$common = DS_Common::CreateForCurrentDB(0);
		
		$oid = SU_ReplicationInterfaceSearch::CheckTablePresenceInDB($common, self::$table);
		if($oid == 0)
		{
			return;
		}
		
		$repl = SU_ReplicationInterfaceSearch::GetTableReplicationTriggers($common, $oid);
		if(count($repl) > 0)
		{
			return;
		}
		
		SU_ReplicationInterfaceSearch::AddTableToReplication($common, self::$table, self::$field);
		
		//SU_ReplicationTableDataSync::SetCommon($common);
		//SU_ReplicationTableDataSync::Sync(self::$table);
	}
	
	static private function _removeTable()
	{
		assert(!empty(self::$table));
		
		$common = DS_Common::CreateForCurrentDB(0);
		
		$oid = SU_ReplicationInterfaceSearch::CheckTablePresenceInDB($common, self::$table);
		if($oid == 0)
		{
			return;
		}
		
		$repl = SU_ReplicationInterfaceSearch::GetTableReplicationTriggers($common, $oid);
		if(count($repl) == 0)
		{
			return;
		}
		
		SU_ReplicationInterfaceSearch::DropTableFromReplication($common, self::$table);
	}
	
	static private function _removeSlave()
	{
		assert(is_int(self::$slaveID) && self::$slaveID > 0);
		
		$common = DS_Common::CreateForCurrentDB(0);
		
		SU_ReplicationInterfaceSearch::DropSlave($common, self::$slaveID);
	}
	
	static private function _addSlave()
	{
		assert(!empty(self::$dbname));
		
		$common = DS_Common::CreateForCurrentDB(0);
		
		$slaveList = SU_ReplicationSearch::GetSlaveList($common);
		
		foreach ($slaveList as $k => $slave)
		{
			if($slave['server'] == self::$dbname)
			{
				return;
			}
		}

		SU_ReplicationInterfaceSearch::AddSlave($common, self::$dbname);
	}
}

?>