<?php $CVS_Version = '$Revision$ ; $Date$';
//$syncUsers = new SyncUsers();
//$syncUsers->Start();

class SyncUsers
{

	var $connFrom;
	var $connTo;
	var $conn;
	var $res;
	var $errormsg = null;
	var $counts = array(
							'INSERT' => 0,
							'UPDATE' => 0,
							'DELETE' => 0
						);

	var $settings = array();

	var $tables = array(
	0=>array("table"=>array("From"=>'ds_user_history', "To"=>'ds_user'), "relation"=>array("From"=>'master', "To"=>'beta'), "PrimaryKey"=>'id_user'),
	1=>array("table"=>array("From"=>'ds_userprofidata_history', "To"=>'ds_userprofidata'), "relation"=>array("From"=>'master', "To"=>'beta'), "PrimaryKey"=>'id_user'),
	);

	var $convertFields = array(
	"ds_user_history"=>array(
							"lastrefresh"=>'timestamp',
							"registrationdate"=>'timestamp',
							"username"=>"varchar",
							"email"=>"varchar",
							"accountactive"=>"varchar",
							"admin"=>"varchar",
							"vacationmode"=>"varchar",
							"password"=>"varchar",
							"vacationstarttime"=>"timestamp",
							"selfdescription"=>"varchar",
							),
	"ds_userprofidata_history"=>array(
							"profi"=>"varchar",
							"trial"=>"varchar",
							"password"=>"varchar",
							"firstname"=>"varchar",
							"surname"=>"varchar",
							"gender"=>"varchar",
							"birthdate"=>'timestamp',
							"street"=>"varchar",
							"postcode"=>"varchar",
							"city"=>"varchar",
							"country"=>"varchar",
							"state"=>"varchar",
							"phone1"=>"varchar",
							"phone2"=>"varchar",
							"fax"=>"varchar",
							"email"=>"varchar",
							"creationdate"=>'timestamp',
							"startdate"=>'timestamp',
							"enddate"=>'timestamp',
							"canceldate"=>'timestamp',
							"cancelled"=>"varchar",
							"active"=>"varchar",
							"mphone"=>"varchar",
							)

	);

	function SyncUsers()
	{

		$this->_formSettings();
//die("END!!!!!!");
		$this->conn['master'] = $this->connect('master');
		$this->conn['beta'] = $this->connect('beta');

		foreach ($this->tables as $value)
		{
			if (!is_null($this->errormsg))
			{
				continue;
			}
			$this->sync($value);
		}


	}

	function GetError()
	{
		return $this->errormsg;
	}

	function _formSettings()
	{
		if (!isset($GLOBALS['dbWorldAccess']) || !is_array($GLOBALS['dbWorldAccess']) || sizeof($GLOBALS['dbWorldAccess']) <= 0)
		{
			$this->errormsg = "Error get settings array!";
			return false;
		}

		foreach ($GLOBALS['dbWorldAccess'] as $key => $value)
		{
			if (GameDown($key))
			{
				die("Game dbId=".$key." and servername=".$value['server']." is closed. Pleas try later.");
				
			}
	 		$this->settings[$value['server']] = array( 'host' => $value['host'], 'dbname' => $value['dbname'], 'user' => $value['user'], 'password' => $value['password']);
		}
	}

	function sync($value)
	{
		echo "Строим SQL и постим его в базу ".$value['relation']['To']." с ключевым полем ".$value['PrimaryKey']." в таблицу ".$value['table']['To']." \n";

		$sql = "SELECT count(*) FROM ".$value['table']['To'];
		$array = $this->query($this->conn[ $value['relation']['To'] ], $sql);

		$result = pg_query($this->conn[ $value['relation']['To'] ], $sql);
		$row = pg_fetch_array($result, NULL, PGSQL_ASSOC);

		if ($row['count'] <= 0)
		{
			/* Производим процедуру полного копирования таблиц, в целях экономии памяти и времени*/
			$rows = pg_copy_to($this->conn[ $value['relation']['From'] ], str_replace('_history','',$value['table']['From']) );
			//pg_query($db, "DELETE FROM $table_name");
			$res = pg_copy_from($this->conn[ $value['relation']['To'] ], $value['table']['To'], $rows);
			if ($res)
			{
				$this->query($this->conn[ $value['relation']['To'] ], "commit;");
			}

		} else {
			//echo "TRUNCATE\n";
/*
			$sql = "truncate ds_userprofidata";
			var_dump($sql);
			$array = $this->query($this->conn[ $value['relation']['To'] ], $sql);
			var_dump($array);

			$sql = "truncate ds_user2";
			var_dump($sql);
			$array = $this->query($this->conn[ $value['relation']['To'] ], $sql);
			var_dump($array);
*/
		}





		unset($array);
		$sql = "SELECT * FROM ".$value['table']['From']." ORDER BY ".$value['PrimaryKey'];
		$result = @pg_query($this->conn[ $value['relation']['From'] ], $sql);
		$array = array();

		if (!$result)
		{
			$this->errormsg = "DB: ".$value['relation']['From']."! ".pg_last_error($this->conn[ $value['relation']['From'] ]);
			return false;
		}

		while ($row = pg_fetch_array($result, NULL, PGSQL_ASSOC))
		{
			$array[] = $row;
		}

		if (count($array) == 0)
		{
			return true;
		}
		//$array = $this->query($this->conn[ $value['relation']['From'] ], $sql);
		foreach ($array as $k => $v)
		{
			foreach ($v as $kkk => $vvv)
			{
				if (is_null($vvv))
				{
					$array[$k][$kkk] = "NULL";
					continue;
				}
				if (key_exists($value['table']['From'], $this->convertFields) && key_exists($kkk, $this->convertFields[$value['table']['From']]))
				{
					switch ($this->convertFields[$value['table']['From']][$kkk])
					{
						case 'timestamp':
							$array[$k][$kkk] = "to_timestamp ( '".$v[$kkk]."', 'YYYY-MM-DD HH24:MI:SS' )";
							break;
						case 'varchar':
							$array[$k][$kkk] = "'".$v[$kkk]."'";
							break;

						default:
							break;
					}
				}
			}
		}

		foreach ($array as $v)
		{
			$sql = "SELECT id_user FROM ".$value['table']['To']." WHERE ".$value['PrimaryKey']." = '".$v[ $value['PrimaryKey'] ]."'";
			//echo $sql."'\n";
			//var_dump($v);
			$result = $this->query($this->conn[ $value['relation']['To'] ], $sql);


//			var_dump($v);
			if ($result == false)
			{

				unset($v['isdeleted']);
//			var_dump($v);
				$sql = QueryHelper::insert($value['table']['To'], $v, false);
				echo $sql."\n";
				$result = $this->query($this->conn[ $value['relation']['To'] ], $sql);

				$this->counts['INSERT']++;
				echo "INSERT ".$value['PrimaryKey']." = ".$v[ $value['PrimaryKey'] ]."\n";
			} else {
				unset($elements);

				if ($v['isdeleted'] == 'DELETE')
				{
					$sql = "DELETE FROM ".$value['table']['To']." WHERE ".$value['PrimaryKey']." = '".$v[ $value['PrimaryKey'] ]."'";
					$result = $this->query($this->conn[ $value['relation']['To'] ], $sql);

					$this->counts['DELETE']++;
					echo "DELETE Username = ".$v['username']." , ".$value['PrimaryKey']." = ".$v[ $value['PrimaryKey'] ]."\n";

				} else {
					unset($v['isdeleted']);
//			var_dump($v);
					foreach ($v as $keyy => $vall)
					{
						if ($keyy == $value['PrimaryKey'])
						{
							continue;
						}
						$elements[] = $keyy."=".$vall;
					}

					$sql = "UPDATE ".$value['table']['To']." SET ".implode(', ', $elements)." WHERE ".$value['PrimaryKey']." = '".$v[ $value['PrimaryKey'] ]."'";
					$result = $this->query($this->conn[ $value['relation']['To'] ], $sql);

					$this->counts['UPDATE']++;
					echo "UPDATE ".$value['PrimaryKey']." = ".$v[ $value['PrimaryKey'] ]."\n";
				}

			}

			$last[$value['table']['To']][$value['PrimaryKey']] = $v[ $value['PrimaryKey'] ];
			$sql = "DELETE FROM ".$value['table']['From']." WHERE ".$value['PrimaryKey']." = ".$v[ $value['PrimaryKey'] ];
			$a = $this->query($this->conn[ $value['relation']['From'] ], $sql);
		}







	}


	function getCounts()
	{
		return $this->counts;
	}

	function getType($field)
	{

		return pg_field_type($this->res, $field);
	}
	function query($conn, $sql)
	{
		if (!is_null($this->errormsg))
		{
			die($this->errormsg);
			//return false;
		}
		$result = pg_query($conn, $sql);

		if (!$result)
		{
			$error = pg_last_error();
			$this->errormsg = $error;
			return false;


		}
		$this->res = $result;

		if (!is_null($this->errormsg))
		{
			$result = pg_query($conn, "rollback;");
			echo 'ROLLBACK;';
			die();
		}

		assert($result);
		$array = pg_fetch_all($result);

		return $array;
	}


	function connect($db)
	{
		if (!key_exists($db, $this->settings))
		{
			$this->errormsg = "DB: ".$db." no exists in settings array!";
			return false;
		}

//if ($db == "beta")
//{
//$this->settings[$db]['user'] = "asdasdasd";
//}
		$conn = pg_connect("host=".$this->settings[$db]['host']." dbname=".$this->settings[$db]['dbname']." user=".$this->settings[$db]['user']." password=".$this->settings[$db]['password'])
or die('Could not connect: ' . pg_last_error() . "host=".$this->settings[$db]['host']." dbname=".$this->settings[$db]['dbname']);
echo $db;
echo pg_last_error($conn);
//die('AAAAAAAA');
		assert($conn);
		return $conn;
	}

	function Start()
	{

	}
}


?>