<?php

require_once("Main.php");

class BirthdayPresent
{
	private $common;
	
	private $accum;
	
	function __construct()
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->accum = '';
		
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			if($dbId == 0)
			{
				//continue;
			}
			$this->_out("START WORLD ".$db['server']);
			$this->common->setDbId($dbId);
			
			$users = $this->_getUserList();
			$this->_out("SELECTED ".count($users)." USERS");
			foreach($users as $k => $user)
			{
				$this->_addPresent($user);				
			}
			$this->_out("WORLD DONE");
		}
		
		SU_DebugTools::LogAll($this->accum, __CLASS__, 0);
	}
	
	private function _getUserList()
	{
		$sql = "
			SELECT
				ui.id_userinstance,
				ui.id_world,
                c.id_comb,
                u.username		
			FROM
				ds_userinstance AS ui
				INNER JOIN ds_comb AS c ON (c.id_userinstance = ui.id_userinstance)
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
			WHERE
				u.registrationdate < '01-01-2006 00:00'
				AND
				NOT r.ringnumber = 4
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	private function _addPresent($user)
	{
		$this->common->startTrans();		
		
		//$id_artefact = 1075; // GOLD_COMB_ROAD_GEKKON
		$id_artefact = 1072; // SUPREME_SPHERE_FOOD_EGG
		$artefactInstance = DE_Artefact::CreateInDB((int)$id_artefact);
		
		// добавляем его в хранилище
		$sql = sprintf("INSERT INTO ds_combhasartefact VALUES ( %d, %d )", $user['id_comb'], $artefactInstance->getID());
		
		$this->common->executeQuery($sql);
		
		$this->common->closeTrans('COMMIT');

		$this->_out("PRESENT ADDED TO ".$user['username']);
	}
	
	private function _out($msg)
	{
		$msg = $msg."\n";
		$this->accum .= $msg;
		echo $msg;
	}
}

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 2;

SU_DebugTools::TimerStart('TEST');

echo "<xmp>";
$p = new BirthdayPresent();
echo "</xmp>";

echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();
?>