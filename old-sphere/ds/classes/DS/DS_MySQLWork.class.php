<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_MySQLWork extends BS_Base 
{	
	/**
	 * Enter description here...
	 *
	 * @var DS_MySQLWork
	 */
	static private $instance;
	
	private $settings;
	
	private $connections = array();
	
	private $currentServerName;
	
	public function __construct()
	{
		assert(key_exists('MySQLBases', $GLOBALS) && is_array($GLOBALS['MySQLBases']));
		$this->settings = $GLOBALS['MySQLBases'];
	}
	

	public function connect($serverName)
	{
		//SU_DebugTools::LogAll('1','mysql.log');
		assert(key_exists($serverName, $this->settings));		
		

		$this->currentServerName = $serverName;
		
		if(key_exists($serverName, $this->connections))
		{
			return;
		}
		
		assert(key_exists('host', $this->settings[$serverName]));
		$host = $this->settings[$serverName]['host'];
		
		assert(key_exists('dbname', $this->settings[$serverName]));
		$dbName = $this->settings[$serverName]['dbname'];
		
		assert(key_exists('user', $this->settings[$serverName]));
		$user = $this->settings[$serverName]['user'];
		
		assert(key_exists('password', $this->settings[$serverName]));
		$password = $this->settings[$serverName]['password'];
				
		$conn = mysql_connect($host, $user, $password);
		if(!is_resource($conn))
		{
			throw new SU_MySQLException('Cant connect to MySQL on '.$host, 0);
		}
		
		assert(mysql_select_db($dbName, $conn));
		
		$this->connections[$serverName] = $conn;		
	}

	static public function Singleton()
	{
		if(!(self::$instance instanceof DS_MySQLWork))
		{
			self::$instance = new DS_MySQLWork;
		}
		
		return self::$instance;
	}
	
	
	public function exec($sql)
	{
		assert(!empty($sql));
		
		$result = mysql_query($sql, $this->connections[$this->currentServerName]);
		if(!$result)
		{
			throw new SU_MySQLException(mysql_error($this->connections[$this->currentServerName]), 0);
		}
		
		$this->_log($sql);
		
		return mysql_affected_rows($this->connections[$this->currentServerName]);
	}
	
	public function get($sql)
	{
		assert(!empty($sql));
		$result = mysql_query($sql, $this->connections[$this->currentServerName]);
		if(!$result)
		{
			throw new SU_MySQLException(mysql_error($this->connections[$this->currentServerName]), 0);
		}
		
		$this->_log($sql);
		
		$out = array();

		
		while($row = mysql_fetch_assoc($result)) 
		{
   			$out[] = $row;
		}
/*
$stroutdebug = $sql."\n".$this->currentServerName."\n".print_r($result,1)."\n".print_r($out,1);
SU_DebugTools::LogAll($stroutdebug,'!testDS_MySqlWork.log');	
*/
		mysql_free_result($result);
		
		return $out;
	}	
	
	private function _log($sql)
	{
		return;
		
		list($usec, $sec) = explode(" ", microtime());
    	$mkTime = (float)$usec + (float)$sec;
    	
		SU_DebugTools::LogAll($mkTime."\n".$sql, 'MySQL_Work.log');
	}
	
}

?>