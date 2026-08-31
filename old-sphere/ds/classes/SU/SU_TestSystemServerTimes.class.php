<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemServerTimes extends SU_TestSystemBase
{
	private $connections = array();
	
	private $masterTime;
	
	private $sql = "SELECT now() as now";
	
	public function exec()
	{
		$this->_outString('START SERVER TIMES TEST', 4);
		
		//Смотрим время на мастере
		$this->_outString('Get time on db 0', 8);
		
		//Смотрим Game Down
		if(GameDown(0))
		{
			$this->_outString('Cant do server time test. Master closed by GameDown!', 12);
			return false;
		}
		
		//Смотрим, не было ли у нас ошибок коннекта к мастеру
		if (isset(self::$haveErrors) && key_exists('DB', self::$haveErrors) && in_array('Cant connect to 0 base', self::$haveErrors['DB'])) {
			$this->_outString('Cant do server time test. Master failed', 12);
			return false;
		}

		$this->masterTime = $this->_GetTime($GLOBALS['dbWorldAccess'][0]);
		$checkTime = getmicrotime();
		
		if (!$this->masterTime || $this->masterTime === -1) {
			$this->_outString('Cant get server time <<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!', 12);
			$this->_writeError('ST', 'Cant get server time on master', $GLOBALS['dbWorldAccess'][0]['shortname']);
			return false;
		}
		
		$result = "Time: ".date('Y-m-d H:i:s', $this->masterTime);
		$this->_outString($result, 12);

		foreach($GLOBALS['dbWorldAccess'] as $DBID => $serverData)
		{
			if ($DBID == 0)
			{
				continue;
			}
			
			//Смотрим время на сервере
			$this->_outString('Get time on db '.$DBID, 8);
			
			
			//Смотрим Game Down
			if(GameDown($DBID))
			{
				$this->_outString('Cant get server time. DB '.$DBID.' closed by GameDown!', 12);
				continue;
			}

			
			
			//Смотрим, не было ли у нас ошибок коннекта к мастеру
			if (isset(self::$haveErrors) && key_exists('DB', self::$haveErrors) && in_array('Cant connect to '.$DBID.' base', self::$haveErrors['DB'])) {
				$this->_outString('Cant get server time. DB '.$DBID.' failed', 12);
				continue;
			}

			$serverTime = $this->_GetTime($serverData)-(getmicrotime()-$checkTime);

			if (!$serverTime || $serverTime === -1) {
				$this->_outString('Cant get server time <<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!', 12);
				$this->_writeError('ST', 'Cant get server time on host ['.$serverData['host'].'] name ['.$serverData['dbname'].'] aliase ['.$serverData['server'], $serverData['shortname']);
				return false;
			}
			
			$result = "Time: ".date('Y-m-d H:i:s', $serverTime);
			$this->_outString($result, 12);
			
			//Смотрим расхождение по времени
			if (abs($serverTime - $this->masterTime) >= 5) {
				$this->_outString('Server times synchronization error! Time delta='.abs($serverTime - $this->masterTime).' sec <<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!', 12);
				$this->_writeError('ST', 'Cant get server time on host ['.$serverData['host'].'] name ['.$serverData['dbname'].'] aliase ['.$serverData['server'], $serverData['shortname']);
				continue;
			} else {
				$this->_outString('OK!', 12);
			}
		}
	}
	
	private function _GetTime($serverData)
	{
		$conn_string = "host=".$serverData['host']." dbname=".$serverData['dbname']." user=".$serverData['user']." password=".$serverData['password']."";
		$conn = @pg_connect($conn_string);
			
		if(!is_resource($conn))
		{
			return false;
		}
		
		$res = pg_query($conn, $this->sql);
		if(!$res)
		{
			return false;
		}

		if(pg_num_rows($res) != 1)
		{
			return false;
		}

		$data = pg_fetch_row($res);
		
		//Закрываем соединение с базой
		if(is_resource($conn))
		{
			pg_close($conn);
		} 
		
		return strtotime($data[0]);
	}
		
	
}

?>