<?php $CVS_Version = '$Revision$ ; $Date$';
require_once('../Main.php');

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 1;

class SU_AddUserallowedFromFile
{
	private $common;
	
	private $worldId;
	
	private $dbId;
	
	private $fileName;
	
	public function __construct($data, $files)
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		$this->_getParams($data, $files);
	}
	
	private function _getParams($data, $files)
	{			
		if(key_exists('filename', $files) && key_exists('tmp_name', $files['filename']) && key_exists('world', $data))
		{
			$this->fileName = $files['filename']['tmp_name'];
			assert(file_exists($this->fileName));
			list($this->dbId, $this->worldId) = explode("_", $data['world']);
			settype($this->dbId, 'int');
			settype($this->worldId, 'int');
			assert($this->worldId > 0);
			assert($this->dbId >= 0);
			
			$this->_addToUserallowed();
		} else {
			$this->_outFirstScreen();
		}
	}
	
	private function _outFirstScreen()
	{
		foreach($GLOBALS['dbWorldAccess'] AS $l => $db)
		{
			$this->common->setDbId($l);
			$sql = "SELECT * FROM ds_world";
			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));
			foreach($res as $k => $world)
			{
				$worlds[$k]['dbId'] = $l;
				$worlds[$k]['worldname'] = $world['worldname'];
				$worlds[$k]['id_world'] = $world['id_world'];
			}			
		}	
		
		echo '
			<h1>Select file with USERNAMES and WORLD</h1>
			<form enctype="multipart/form-data" action="" method="post">
				File: <input type="file" name="filename"><br><br>
				World: 
				<select name="world">
			';	
		foreach($worlds as $k => $world)
		{	
			echo '<option value='.$world['dbId']."_".$world['id_world'].'>'.$world['worldname'];
		}
	
		
		echo '</select><br><br>
				<input type="submit" value="add">
			</form>
			';
	}

	private function _addToUserallowed()
	{
		//$logins = file("TURNIR_USERSALLOWED.csv");
		$logins = file($this->fileName);
		
		$this->common->executeQuery("begin");
		
		$usersWithNoId = array();
		
		echo "<xmp>";

		echo "TOTAL USERS : ".count($logins)."\n";

		$id_world = 0;

		$inserted = array();

		foreach($logins as $k => $login)
		{
			$login = trim($login);
			
			if(in_array($login, $inserted))
			{
				echo "$login : THIS USER WAS ALLREADY ADDED\n";
				continue;
			}
			$this->common->setDbId(0);
			$sql = "
				SELECT 
					id_user
				FROM 
					ds_user
				WHERE
					username = '".$login."'
				";
	
			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));
			if(count($res) == 1)
			{
				$id_user = $res[0]['id_user'];
				echo "login: $login => $id_user\n";
			} else {
				echo "no id_user for $login\n";
				$usersWithNoId[] = $login;
				continue;
			}
	
			$this->common->setDbId($this->dbId);
			$sql = "INSERT INTO ds_usersallowed VALUES(".$id_user.", ".$this->worldId.")";
		
			$inserted[] = $login;
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
		}
		$this->common->executeQuery("commit");
		
		echo "\n\nUSERS WITH NO ID : ".count($usersWithNoId)."\n";
		
		print_r($usersWithNoId);

		echo "</xmp>";
	}
}

$v = new SU_AddUserallowedFromFile($_POST, $_FILES);

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();

?>