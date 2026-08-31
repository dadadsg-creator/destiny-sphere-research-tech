<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '16M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 1;

SU_DebugTools::TimerStart('TEST');
//
/*********** BEGIN WORK AREA **************/
$GLOBALS['dbWorldAccess'] = array
(//Доступ к игровым базам
		0 => array ( //База с нулевым инлдексом считаеться мастером
			'host' => '192.168.0.10',
			'dbname' => 'destiny1',
			'user' => 'dsgame',
			'password' => 'REDACTED_DB_PASSWORD',
			'server' => 'master',
			'dir' => '/ds/'			
		),
		
		1 => array 
		( //База с нулевым инлдексом считаеться мастером
			'host' => 'gamedb16.destinysphere.ru',
			'dbname' => 'destiny1',
			'user' => 'defo',
			'password' => '14528914',
			'server' => 'BIG_ALPHA'			
		)
);



class SU_DBCompareForeignKeys
{
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	private $common;
	
	private $serversInfo = array();
	
	private $masterData = array();
	
	private $currentSlaveData = array();
	
	public function __construct()
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		
		assert(key_exists('dbWorldAccess', $GLOBALS) && is_array($GLOBALS['dbWorldAccess']));
		$this->serversInfo = $GLOBALS['dbWorldAccess'];
	}
	
	public function exec()
	{		
		$this->_out("----------========== START COMPARE ==========----------");		
		
		$this->_out("Get data from [master]");	
		$this->masterData = $this->_getDataFromServer(0);
		$this->_out("OK");	
				
		
		foreach($this->serversInfo as $DBID=>$serverInfo)
		{
			if($DBID == 0)
			{
				continue;
			}
			
			$this->_out("START compare [master] with slave [".$serverInfo['server']."]");	
			$this->currentSlaveData = $this->_getDataFromServer($DBID);
			
			$this->_compareMasterAndSlave();			
		}
	}	
	
	private function _compareMasterAndSlave()
	{
		assert(is_array($this->masterData));
		assert(is_array($this->currentSlaveData));
				
		//Прямая проверка
		foreach ($this->masterData AS $masterKeyName => $masterKeyData)
		{		
			$haveErrors = false;
			$exists = true;
							
			if(!key_exists($masterKeyName, $this->currentSlaveData))
			{				
				$this->_out("ERRROR: Not exists on slave! <<<<<<<<<<<<<<<<<<<< ERROR!");					
				$haveErrors = true;	
				$exists = false;	
			}				
			
			if(!$haveErrors)
			{
				$slaveKeyData = $this->currentSlaveData[$masterKeyName];

				foreach ($masterKeyData as $key => $masterValue)
				{
					$slaveValue = $slaveKeyData[$key];
					if($slaveValue != $masterValue)
					{
						$this->_out("ERROR: Mismatch key [$key] values MASTER: $masterValue SLAVE: $slaveValue <<<<<<<<<<<<<<<<<<<< ERROR!");
						$haveErrors = true;
					}
				}
			}
						
			if($haveErrors)
			{
				$this->_out("Server name: [master] ".$this->_formKeyOeverString($masterKeyName, $masterKeyData));
				$sql = $this->_formSQL($masterKeyName, $masterKeyData, $exists);
				
				$this->_out("SQL:\n$sql");
			}
		}
		
		//Обратная проверка
		foreach ($this->slaveData AS $slaveKeyName => $slaveKeyData)
		{		
			$haveErrors = false;
							
			if(!key_exists($slaveKeyName, $this->masterData))
			{				
				$this->_out("ERRROR: Not exists on master!");					
				$haveErrors = true;		
			}				
						
						
			if($haveErrors)
			{
				$this->_out("FOR [slave] ".$this->_formKeyOeverString($slaveKeyName, $slaveKeyData));
				$this->_out("");
			}
		}
	}
	
	
	private function _formSQL($masterKeyName, $masterKeyData, $exists)
	{
		assert(!empty($masterKeyName));
		assert(is_array($masterKeyData) && count($masterKeyData));
		assert(is_bool($exists));		
		
		$sql = "";

		switch($masterKeyData['UPDATE_ACTION'])
		{
			case 'c':
				$updateAction = 'CASCADE';
				break;
			case 'r':
				$updateAction = 'RESTRICT';
				break;
			case 'a':
				$updateAction = 'NO ACTION';
				break;
			default:
				assert(false);
		}
		
		switch($masterKeyData['DELETE_ACTION'])
		{
			case 'c':
				$deleteAction = 'CASCADE';
				break;
			case 'r':
				$deleteAction = 'RESTRICT';
				break;
			case 'a':
				$deleteAction = 'NO ACTION';
				break;
			default:
				assert(false);
		}
		 
		
		$sqlDrop = ' ALTER TABLE "public"."'.$masterKeyData['FROM_TABLE'].'" DROP CONSTRAINT "'.$masterKeyName.'" RESTRICT; '."\n";	
		
		$sqlCreate  = ' ALTER TABLE "public"."'.$masterKeyData['FROM_TABLE'].'"'."\n";
		$sqlCreate .= ' ADD CONSTRAINT "'.$masterKeyName.'" FOREIGN KEY ("'.$masterKeyData['FROM_FIELD'].'")'."\n";
    	$sqlCreate .= ' REFERENCES "public"."'.$masterKeyData['TO_TABLE'].'"("'.$masterKeyData['TO_FIELD'].'")'."\n";
    	$sqlCreate .= ' ON DELETE '.$deleteAction.''."\n";
    	$sqlCreate .= ' ON UPDATE '.$updateAction.''."\n";
    	$sqlCreate .= ' NOT DEFERRABLE; '."\n";

		if($exists)
		{
			$sql .= $sqlDrop;
		}
		
		$sql .= $sqlCreate;
		
		echo $sql."\n";
		
		return $sql;
	}
	
	private function _formKeyOeverString($masterKeyName, $masterKeyData)
	{
		assert(!empty($masterKeyName));
		assert(is_array($masterKeyData) && count($masterKeyData));
		
		$out = "\nKey name: [$masterKeyName]\n";
		$out .= "Fileds: ".$masterKeyData['FROM_TABLE'].".".$masterKeyData['FROM_FIELD']." => ".$masterKeyData['TO_TABLE'].".".$masterKeyData['TO_FIELD']."\n";
		$out .= "Actions: delete:".$masterKeyData['DELETE_ACTION']." update: ".$masterKeyData['UPDATE_ACTION'];		
		
		return $out;
	}
	
	private function _getDataFromServer($DBID)
	{
		assert(is_int($DBID) && $DBID >= 0);
		
		$this->common->setDbId($DBID);
		
		$data = array();
		
		$rs = $this->_getKeysInfo();		
		foreach($rs as $k=>$keyData)
		{
			$data[$keyData['key_name']] = array
			(
				'FROM_TABLE' => trim($keyData['from_table']),
				'FROM_FIELD' => trim($keyData['from_field']),
				'TO_TABLE' => trim($keyData['to_table']),
				'TO_FIELD' => trim($keyData['to_field']),
				'UPDATE_ACTION' => trim($keyData['update_acion']),
				'DELETE_ACTION' => trim($keyData['delete_action'])
			);
		}		
		
		return $data;
	}
	
	private function _getKeysInfo()
	{
		$sql = "
			SELECT
      			cn.conname AS key_name,
      			cl_from.relname AS from_table,
      			atr_from.attname AS from_field,
      			'-->' AS direction,
      			cl_to.relname AS to_table,
      			atr_to.attname AS to_field,
      			cn.confupdtype AS update_acion,
      			cn.confdeltype AS delete_action
			FROM
    			pg_constraint AS cn
    			INNER JOIN pg_class AS cl_to ON (cl_to.oid = cn.confrelid)
    			INNER JOIN pg_class AS cl_from ON (cl_from.oid = cn.conrelid)
    			LEFT JOIN pg_attribute AS atr_from ON (atr_from.attnum = ANY(cn.conkey) AND atr_from.attrelid = cn.conrelid)
    			LEFT JOIN pg_attribute AS atr_to ON (atr_to.attnum = ANY(cn.confkey) AND atr_to.attrelid = cn.confrelid)
			WHERE
     			cn.contype = 'f'
			ORDER BY cl_from.relname, cn.conname, cl_to.relname";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	private function _out($msg)
	{
		//echo $msg."\n";
		flush();
	}
}

echo "<xmp>";
$a = new SU_DBCompareForeignKeys;
$a->exec();
echo "</xmp>";
/*********** END WORK AREA **************/
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();

?>