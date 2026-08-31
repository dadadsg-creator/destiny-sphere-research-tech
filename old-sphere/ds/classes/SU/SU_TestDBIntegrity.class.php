<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestDBIntegrity
{	
	/**
	 * Коннект к БД.
	 *
	 * @var unknown_type
	 */
	private $conn;
	
	/**
	 * Нпмтроки коннект к БД 
	 *
	 * @var unknown_type
	 */
	private $settings;
	
	/**
	 * ID базы с котрым работаем
	 *
	 * @var integer
	 */
	private $DBID;
	
	/**
	 * Список табличек на проверку
	 */
	private $tablesList = array
	(
		
	);
	
	private $testsList = array
	(
		'_testBadTables',
		'_testMultiplayRows'
	);
	
	public function __construct($DBID)
	{
		assert(is_int($DBID) && $DBID>=0);
		
		assert(key_exists('dbWorldAccess', $GLOBALS) && is_array($GLOBALS['dbWorldAccess']) && count($GLOBALS['dbWorldAccess']) > 0);
		$this->settings = $GLOBALS['dbWorldAccess'];
		
		$this->DBID = $DBID;				
		
		$this->_connect();
		
		$this->_formTablesList();
	}
	
	public function test()
	{
		$this->_out(" --------------- TEST OF DBID = ".$this->settings[$this->DBID]['server']." ----------------");
		foreach($this->tablesList as $tableName=>$tableData)
		{
			$this->_out("Test table [".$tableName."]");
			
			foreach($this->testsList as $k1=>$testName) 
			{
				$res = $this->$testName($tableName, $tableData);
				$this->_out("    ".$testName.": ".$res);
			}
		}
		$this->_out(" --------------- COMPLIT ----------------");
	}
	
	private function _formTablesList()
	{
		$sql = "
			SELECT *
			FROM pg_class
			WHERE
     			reltuples > 0
     			AND
     			relkind = 'r'
     			AND
     			relname LIKE 'ds_%'";
		$res = $this->_execSQL($sql);
		assert(!empty($res));
		
		$array = pg_fetch_all($res);
		
		foreach($array as $k=>$v)
		{
			$this->tablesList[$v['relname']] = $v;
		}
	}
	
	private function _testBadTables($tableName, $tableData)
	{
		assert(!empty($tableName));
		assert(is_array($tableData) && count($tableData) > 0);
		
		$sql = "SELECT * FROM $tableName";
		$res = $this->_execSQL($sql);
		
		if($res)
		{
			return "OK";
		}else{
			return pg_last_error($this->conn);
		}
				
	}
	
	private function _testMultiplayRows($tableName, $tableData)
	{
		assert(!empty($tableName));
		assert(is_array($tableData) && count($tableData) > 0);
		
		if(!$tableData['relhasoids'])
		{
			return "Without OID";
		}
		
		$sql = "
			SELECT oid
      		FROM  $tableName	
     		GROUP BY oid
     		HAVING count(*) > 1";
		$res = $this->_execSQL($sql);
		//assert(!empty($res));		
		
		if(empty($res))
		{
			return "Without OID";
		}
		
		$array = pg_fetch_all($res);

		if($array && count($array) > 0)
		{
			return "BAD! Have ".count($array)." doupled rows!";
		}else{
			return "OK";	
		}
		
	}
	
	private function _connect()
	{
		assert(is_int($this->DBID) && $this->DBID >= 0);
		assert(key_exists($this->DBID, $this->settings));
		
		$connString = 	" host=".$this->settings[$this->DBID]['host'].
						" dbname=".$this->settings[$this->DBID]['dbname'].
						" user=".$this->settings[$this->DBID]['user'].
						" password=".$this->settings[$this->DBID]['password'];
	
		$this->conn = pg_connect($connString);
		assert(is_resource($this->conn));
	}
	
	private function _execSQL($sql)
	{
		assert(!empty($sql));
		
		$result = pg_query($this->conn, $sql);

		return $result;			
	}
		
	private function _out($msg)
	{
		echo date('Y-m-d H:i:s')." ".$msg."\n";
		flush();
	}
	
}

?>