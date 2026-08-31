<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemMySQLConnection extends SU_TestSystemBase
{
	public function exec()
	{
		if (!key_exists('MySQLBases', $GLOBALS))
		{
			$result = "MySQLBases not found";
			$this->_outString($result, 8);
			return;
		}
		
		foreach($GLOBALS['MySQLBases'] as $ServerName => $serverData)
		{
			if (!is_array($serverData) || count($serverData) <= 0)
			{
				$result = "Cant do MySQL CONNECTION TEST!!! <<<<<<<<<<<<< ERROR!";
				$this->_writeError('MS', "Cant do CONNECTION TEST on MySQL $ServerName");
				$this->_outString($result, 4);
				continue;
			}
			
			$string = "Start test of [$ServerName]";
			$this->_outString($string, 4);
			
			$string = 'Connect to MySQL server ['.$ServerName.'] on host ['.$serverData['host'].']';
			$this->_outString($string, 8);
			
			$conn = @mysql_connect($serverData['host'], $serverData['user'], $serverData['password']);

			if(is_resource($conn))
			{	
				$this->_outString('OK!', 8);
			} else {		
				$result = "Cant connect! <<<<<<<<<<<<< ERROR!";
				$this->_writeError('MS', "Cant connect to MySQL server [$ServerName]");
				$this->_outString($result, 8);
				continue;
			}
			
			$string = 'Select db ['.$serverData['dbname'].'] in ['.$ServerName.']';
			$this->_outString($string, 8);

			if(@mysql_select_db($serverData['dbname'], $conn))
			{
				$this->_outString('OK!', 8);
			} else {
				$result = "Cant select db ".$serverData['dbname']." ! <<<<<<<<<<<<< ERROR!";
				$this->_writeError('MS', "Cant select db [".$serverData['dbname']."] on MySQL server [$ServerName]");
				$this->_outString($result, 8);
				continue;
			}
		}
		
		
	}
}

?>