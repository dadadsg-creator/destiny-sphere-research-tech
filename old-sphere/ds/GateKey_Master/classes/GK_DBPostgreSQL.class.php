<?php

/**
 * Класс для работы с базой PostgreSQL
 *
 */
class GK_DBPostgreSQL implements GK_DBInterface
{
	private $connection;
	
	public $connected = false;
	
	public $error = '';
	
	public function __construct()
	{
		$conn_string = 
				"host=".constant('GATE_KEY_DB_HOST').
				" dbname=".constant('GATE_KEY_DB_NAME').
				" user=".constant('GATE_KEY_DB_USER').
				" password=".constant('GATE_KEY_DB_PASS');
		
		if(constant('GATE_KEY_DB_PORT'))
		{
			$conn_string .= " port=".constant('GATE_KEY_DB_PORT');
		}
		
		$this->connection = pg_connect($conn_string);

		if(!is_resource($this->connection))
		{
			$this->error = 'Can`t connect to DB';
			$this->connected = false;
			return;
		}
		
		$this->connected = true;
	}
	
	public function executeQuery($sql)
	{
		if(!$this->connected)
		{
			$this->erorr = 'No connection to DB';
		}
		
		$res = pg_query($this->connection, $sql);
		if(!$res)
		{
			$this->error = pg_last_error($this->connection);
			return false;
		}
		
		return true;
	}
	
	public function getResultArray($sql)
	{
		if(!$this->connected)
		{
			$this->error = 'No connection to DB';
		}
		
		$res = pg_query($this->connection, $sql);
		if(!$res)
		{
			$this->error = pg_last_error($this->connection);
			return false;
		}
		
		if(pg_num_rows($res)>0)
		{
			$res_assoc = pg_fetch_all($res);
		} else {
			$res_assoc = array ();
		}

		return $res_assoc;
	}
	
	public function getLastError()
	{
		return $this->error;
	}
}

?>