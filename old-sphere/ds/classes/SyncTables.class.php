<?php $CVS_Version = '$Revision$ ; $Date$';

class SyncTables
{	
	/**
	 * Список соединений с БД
	 *
	 * @var array
	 */
	public $conn = array();	
	
	/**
	 * Парамеры досутпа к БД
	 *
	 * @var array
	 */
	public $settings = array (
		"master"=>array('host' => 'localhost', 'dbname' => 'destiny1', 'user' => 'dsgame', 'password' => ''),
		"slave"=>array('host' => 'ltdb.destinysphere.net', 'dbname' => 'destiny1', 'user' => 'annita', 'password' => 'REDACTED_DB_PASSWORD'),
	); 
	
	
	/**
	 * Таблицы для синхронизации
	 *
	 * @var unknown_type
	 */
	public $tables = array
	(
		//'ds_user',
		//'ds_usersallowed',
		//'ds_userprofidata',
		
		'ds_localizedtext',
		'ds_localizedtextlanguage',
		'ds_interface',
		'ds_language',
		'ds_messagescope',
		'ds_specialfunctionrelation',
		'ds_ability',
		'ds_artefact', 
		'ds_artefacthasbonus',
		'ds_artefactprobability',
		'ds_artefactscope',
		'ds_artefactscopehasprobability',
		'ds_artefacttype',
		'ds_artefacttypeforbuilding',		
		'ds_attributename',
		'ds_attributetype',
		'ds_attribute',		
		'ds_specialfunctionrelation',
		'ds_auctionsort',
		'ds_battleaction',
		'ds_battleendreasons',
		'ds_building',
		'ds_buildingcanbebuildon',
		'ds_buildingcanbeupgraded',
		'ds_buildingcanbuildbyrace',
		'ds_buildinghasattribute',
		'ds_specialfunction',
		'ds_buildinghasspecialfunction',
		'ds_buildinghastradescope',
		'ds_buildingneedsbuilding',
		'ds_buildingneedsscience',
		'ds_buildingtype',
		'ds_celltemplate',
		'ds_celltemplateelement',
		'ds_celltype',
		'ds_combcomplextype',
		'ds_event',
		'ds_eventhandler',		
		'ds_eventoption',		
		'ds_gamemodifiertemplate',
		'ds_groundtype',
		'ds_partner',
		'ds_produceableunit',
		'ds_race',
		'ds_racehasattribute',
		'ds_racehasbasering',		
		'ds_religion',
		'ds_religionhasattribute',
		'ds_religionneedsbuilding',
		'ds_religionstatus',
		'ds_resource',
		'ds_ringtemplate',
		'ds_science',
		'ds_sciencebranch',
		'ds_sciencebranchattributename',
		'ds_sciencecanberesearchedbyrace',
		'ds_sciencehasability',
		'ds_sciencehasattribute',
		'ds_scienceneedsbuilding',
		'ds_scienceneedsscience',
		'ds_systemmessage',
		
		//'ds_tradecategory',
		//'ds_tradescope',
		
		'ds_unit',
		'ds_unitattacktype',
		'ds_unitcanbebuildbyrace',
		'ds_unitclass',
		'ds_unitclassbonus',
		'ds_unitcontaineractiontype',
		'ds_unithasattacktype',
		'ds_unithasattribute',
		'ds_unithasclass',
		'ds_unithasvulnerability',
		'ds_unitneedsbuilding',
		'ds_unitneedsscience',
		'ds_unitorcontainertype',
		'ds_unitvsunit',
			
		'ds_useractiontype',				
		'ds_worldtype',
		'ds_terraforming',
		'ds_terraforminghasattribute',
		'ds_terraformingneedscience',
		'ds_serversideinterface',
		'ds_powershild_status_name',
		'ds_powershild_status_cc2',
		
		'ds_log_delete_game_object_types',
		'ds_worldstatus',
		
		'ds_useralliancestatus',
		
		'ds_help_section',
		'ds_help',
		'ds_units_death_reasons',
		
		'ds_money',
		'ds_money_action_types',
		'ds_trade_type',
		'ds_trade_category',
		'ds_trade_course',
		'ds_trade_scope',
		
		'ds_userbanreason',
		'ds_artefactsorttype',
		
		'ds_trade_menu_filters',
		'ds_trade_menu_sorting',
		
		'ds_gamemodifiertype',
		
		'ds_spy_action_type',
		'ds_occurrenceaction',
		'ds_occurrence',
		'ds_money_payments_price',
	);
	
	/**
	 * Уже просинхронизированные таблицы
	 *
	 * @var array
	 */
	public $resTables = array();
	
	function __construct()
	{		
		
		$this->_out("START SYNC TABLES");
		$this->_out("Have ".count($this->tables)." for sync!");
		
		$this->conn['master'] = $this->connect('master');
		$this->_out("Master connect complit.");
		
		$this->conn['slave'] = $this->connect('slave');
		$this->_out("Slave connect complit.");
		
		$counter = 0;
		$this->_out("Start sync iterations");
		while(count($this->tables) > 0)
		{			
			$this->_out("------- ITERATION: ".$counter++." -------");
			foreach ($this->tables as $k=>$tableName)
			{											
				if($this->sync($tableName))
				{
					unset($this->tables[$k]);
				}				
			}		
						
			srand((float)microtime() * 1000000);
			shuffle($this->tables);
			$this->_out("Shuffle complit");
		}
	}
	
	function sync($table)
	{
		assert(!empty($table));
		
		$this->_out("Try sync table $table");
		
		$sql = "SELECT count(*) FROM ".$table;
		$result = pg_query($this->conn['slave'], $sql);
		assert($result);		
		$row = pg_fetch_array($result, NULL, PGSQL_ASSOC);

		if ($row['count'] > 0)
		{
			$this->_out("RESULT: Already have records.");			
			return true;
		}

		//Начинаем транзацию на слейве			
		$result = pg_query($this->conn['slave'], "BEGIN");
		assert($result);
		
		//Взяли данные из таблички в массив
		$rows = pg_copy_to($this->conn['master'],$table);			
		
		$result = pg_query($this->conn['slave'], "set STATEMENT_TIMEOUT to 0");
		assert($result);
		
		//А тперь пихаем в целевую табличку.		
		$res = pg_copy_from($this->conn['slave'], $table, $rows);
		
		//Нормально ли прошло копирование
		if(!$res)
		{			
			$this->_out("RESULT: Problem in copy process.");
			
			$result = pg_query($this->conn['slave'], "ROLLBACK");
			assert($result);	
			
			return false;
		}
		
		//Сравним колличество записей в ихсодной и целевой табличке		
		$sql = "SELECT count(*) FROM ".$table;
		$result = pg_query($this->conn['slave'], $sql);
		assert($result);
		$row = pg_fetch_array($result, NULL, PGSQL_ASSOC);
		
		if($row['count'] != sizeof($rows))
		{
			$this->_out("RESULT: Mismatch records count. MASTER:".sizeof($rows)." SLAVE:".$row['count']);
			
			$result = pg_query($this->conn['slave'], "ROLLBACK");
			assert($result);
			
			return false;
		}	
					
		//Успешно завешаем траназкцию
		$result = pg_query($this->conn['slave'], "COMMIT");
		assert($result);
		
		$this->_out("RESULT: OK!!!!!!!!!!");
		
		return true;
	}
	
	function connect($db)
	{
		$conn = pg_connect("host=".$this->settings[$db]['host']." dbname=".$this->settings[$db]['dbname']." user=".$this->settings[$db]['user']." password=".$this->settings[$db]['password']);
		assert(is_resource($conn));
		return $conn;
	}

	
	function _out($msg)
	{
		echo $msg."\n";
		flush();
	}

}
?>