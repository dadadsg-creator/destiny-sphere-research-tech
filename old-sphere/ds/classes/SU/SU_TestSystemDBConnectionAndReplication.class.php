<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemDBConnectionAndReplication extends SU_TestSystemBase
{
	private $connections = array();
	private $testReplicationStringData = array();
	
	public function exec()
	{
		$this->_testGameDown();
		$this->_testConnect();
		
		//Если игра не закрыта на Game Down
		if(!GameDown(0))
		{
			$this->_testReplication();
		} 
	}

	private function _testConnect()
	{
		if (!key_exists('dbWorldAccess', $GLOBALS))
		{
			$result = "Cant do CONNECTION TEST!!! <<<<<<<<<<<<< ERROR!";
			$this->_writeError('DB', "Cant do CONNECTION TEST");
			$this->_outString($result, 8);
			return;
		}

		$this->_outString('START CONNECTION TEST', 4);

		foreach($GLOBALS['dbWorldAccess'] as $DBID => $serverData)
		{
			if (!is_array($serverData) || count($serverData) <= 0)
			{
				$result = "Cant do CONNECTION TEST!!! <<<<<<<<<<<<< ERROR!";
				$this->_writeError('DB', "Cant do CONNECTION TEST on DB $DBID");
				$this->_outString($result, 8);
				continue;
			}

			$string = 'Connect to DB on host ['.$serverData['host'].'] name ['.$serverData['dbname'].'] aliase ['.$serverData['server'].']';
			$this->_outString($string, 8);

			if(GameDown($DBID))
			{
				$this->_outString('Base is closed by GameDown', 8);

				//Чтобы не делать тест на репликацию
				$this->connections[$DBID] = false;
				continue;
			}

			$conn_string = "host=".$serverData['host']." dbname=".$serverData['dbname']." user=".$serverData['user']." password=".$serverData['password']."";
			$conn = @pg_connect($conn_string);

			if(is_resource($conn))
			{
				$result = "OK!";
				$this->connections[$DBID] = $conn;
			}else{
				$result = "Cant connect! <<<<<<<<<<<<< ERROR!";
				$this->_writeError('DB', "Cant connect to $DBID base", $serverData['shortname']);
				$this->connections[$DBID] = false;
			}

			$this->_outString($result, 8);
		}
	}
	
	private function _testGameDown()
	{
		if (!key_exists('dbWorldAccess', $GLOBALS))
		{
			$result = "Cant do GAME DOWN TEST!!! <<<<<<<<<<<<< ERROR!";
			$this->_writeError('GD', "Cant do GAME DOWN TEST");
			$this->_outString($result, 8);
			return;
		}

		$this->_outString('START GAME DOWN TEST', 4);

		foreach($GLOBALS['dbWorldAccess'] as $DBID => $serverData)
		{
			if (!is_array($serverData) || count($serverData) <= 0)
			{
				$result = "Cant do GAME DOWN TEST!!! <<<<<<<<<<<<< ERROR!";
				$this->_writeError('GD', "Cant do GAME DOWN TEST");
				$this->_outString($result, 8);
				return;
			}

			$string = 'Check DB Game Down on host ['.$serverData['host'].'] name ['.$serverData['dbname'].'] aliase ['.$serverData['server'].']';
			$this->_outString($string, 8);

			if(GameDown($DBID))
			{
				$startGameDown = 4;
				$endGameDown = 7;
				
				//Если это черная дыра 
				if ($serverData['server'] == 'hell') {
					$endGameDown = 8;
				}
				
				//Если это немецкая версия, учитываем разницу во времени с немцами
				if (constant('GAME_HOST') == 'http://ds.destinysphere.net') {
					$startGameDown = 2;
					$endGameDown = 6;
				}				
				
				//Смотрим на время
				if ($endGameDown < date(H) OR date(H) < $startGameDown)
				{					
					//В это время база на Game Down закрыта быть не должна
					//Пишем ошибку
					$this->_writeError('GD', "Data Base $DBID closed by GameDown!", $serverData['shortname']);

					$result = 'Base is closed by GameDown! <<<<<<<<<<<<< ERROR!';
					$this->_outString($result, 8);

					continue;
				} else {
					//Если все нормально
					$this->_outString('Base is closed by GameDown', 8);
				}
			} else {
				$this->_outString('OK!', 8);
			}
		}
	}

	private function _testReplication()
	{
		$this->_outString('START REPLICATION TEST', 4);

		if(!is_resource($this->connections[0]))
		{
			$msg = 'Cant do replication test. Muster is failed.';
			$this->_outString($msg, 8);
			return;
		}

		foreach ($GLOBALS['dbWorldAccess'] as $DBID => $serverData)
		{

			if(!is_resource($this->connections[$DBID]))
			{
				continue;
			}

			$this->_outString('Start test of ['.$serverData['server'].']', 8);

			if($DBID == 0)
			{
				if(!$this->_startRepricationTest($DBID, $serverData))
				{
					return;
				}
			}else{
				$this->_processWorldBase($DBID, $serverData);
			}
			
			//Закрываем соединение с базой
			if(is_resource($this->connections[$DBID]))
			{
				$resultclose = pg_close($this->connections[$DBID]);
			}

		}

		$this->_endReplicationTest(0);
	}

	private function _startRepricationTest($DBID, $serverData)
	{
		if(!is_array($serverData) || count($serverData) <= 0)
		{
			$result = "Cant do REPLICATION TEST! Wrong server Data<<<<<<<<<<<<< ERROR!";
			$this->_writeError('RP', "Cant do REPLICATION TEST");
			$this->_outString($result, 8);
			return;
		}
			
		if(!is_resource($this->connections[$DBID]))
		{
			$result = "Cant do REPLICATION TEST! Connections to DB $DBID failed<<<<<<<<<<<<< ERROR!";
			$this->_writeError('RP', "Cant do REPLICATION TEST");
			$this->_outString($result, 8);
			return;
		}
		
		$this->_outString('Select test record from ['.$serverData['server'].']', 12);

		$sql = "SELECT * FROM ds_user WHERE username = 'DestinySphere'";
		$res = pg_query($this->connections[$DBID], $sql);
		if(!$res)
		{
			$this->_outString('Cant select test data. '.pg_last_error($this->connections[$DBID]), 12);
			return false;
		}

		if(pg_num_rows($res) != 1)
		{
			$this->_outString('No system user!. <<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR', 12);
			$this->_writeError('RP', 'No system user');
			return false;
		}

		$data = pg_fetch_row($res);

		$this->testReplicationStringData = array('email' => $data[3]);

		$this->_outString('OK!', 12);

		return true;
	}

	private function _processWorldBase($DBID, $serverData)
	{
		if (count($this->testReplicationStringData) <= 0)
		{
			$this->_outString('No system user!. <<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR', 12);
			$this->_writeError('RP', 'No system user');
			return false;
		}
		
		if(!is_array($serverData) || count($serverData) <= 0)
		{
			$result = "Cant do REPLICATION TEST! Wrong server Data<<<<<<<<<<<<< ERROR!";
			$this->_writeError('RP', "Cant do REPLICATION TEST");
			$this->_outString($result, 8);
			return;
		}
		
		if(!is_resource($this->connections[$DBID]))
		{
			$result = "Cant do REPLICATION TEST! Connections to DB $DBID failed<<<<<<<<<<<<< ERROR!";
			$this->_writeError('RP', "Cant do REPLICATION TEST");
			$this->_outString($result, 8);
			return;
		}

		$this->_outString('Test existing of test record', 12);

		$sql = "SELECT * FROM ds_user WHERE email = '".$this->testReplicationStringData['email']."'";
		
		$res = pg_query($this->connections[$DBID], $sql);
	
		if(!$res)
		{
			$this->_outString('Cant do select. ERROR: '.pg_last_error($this->connections[$DBID]), 12);
			return false;
		}

		if(pg_num_rows($res) > 0)
		{
			$this->_outString('OK!', 12);
		}else{				
			$this->_outString('Replication failed <<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!', 12);
			$this->_writeError('RP', "Replication failed on db $DBID", $serverData['shortname']);
		}

		return true;
	}

	private function _endReplicationTest($DBID)
	{
		$conn_string = "host=".$GLOBALS['dbWorldAccess'][$DBID]['host']." dbname=".$GLOBALS['dbWorldAccess'][$DBID]['dbname']." user=".$GLOBALS['dbWorldAccess'][$DBID]['user']." password=".$GLOBALS['dbWorldAccess'][$DBID]['password']."";
		
		$conn = @pg_connect($conn_string);
		if(is_resource($conn))
		{
			$result = "OK!";
			$this->connections[$DBID] = $conn;
		}else{			
			$result = "Cant connect! <<<<<<<<<<<<< ERROR!";
			$this->_writeError('DB', "Cant connect to $DBID database", $GLOBALS['dbWorldAccess'][$DBID]['shortname']);
			$this->connections[$DBID] = false;
		}
				
		if(!is_resource($this->connections[$DBID]))
		{			
			$result = "Cant do REPLICATION TEST! Connections to DB $DBID failed<<<<<<<<<<<<< ERROR!";
			$this->_writeError('RP', "Cant do REPLICATION TEST", $GLOBALS['dbWorldAccess'][$DBID]['shortname']);
			$this->_outString($result, 8);
			return;
		}
		
		$this->_outString('Update test replication record', 8);

		$email = GB_RandomPassword::GenerateEmail(20);
		$sql = "UPDATE ds_user SET email = '".$email."' WHERE username = 'DestinySphere'";
		
		$res = pg_query($this->connections[$DBID], $sql);

		if(!$res)
		{
		
			$this->_outString('Cant update test record. ERROR: '.pg_last_error($this->connections[$DBID]), 8);
			$this->_writeError('RP', 'Cant update test record. ERROR: '.pg_last_error($this->connections[$DBID]), $GLOBALS['dbWorldAccess'][$DBID]['shortname']);
			return false;
		}else{
			$this->_outString('OK!', 8);
		}
		
		//Закрываем соединение с базой
		if(is_resource($this->connections[$DBID]))
		{
			$resultclose = pg_close($conn);
		}

	}
}

?>