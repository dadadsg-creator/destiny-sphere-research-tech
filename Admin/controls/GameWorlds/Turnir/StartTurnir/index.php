<?php
ini_set('memory_limit', '16M');
set_time_limit(0);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
//require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class TurnirStart
{
	private $common;
	
	private $worldId;
	
	private $dbId;
	
	private $turnitName = "";
	
	function __construct($data)
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		$this->_getPostData($data);
	}
	
	private function _getPostData($data)
	{
		if(!isset($data['worldId']))
		{
			$this->_outFirstScreen();
		} else {
			list($this->dbId, $this->worldId) = explode("_", $data['worldId']);
			$this->turnitName = $data['name'];
			settype($this->dbId, 'int');
			settype($this->worldId, 'int');
			assert($this->worldId > 0);
			assert($this->dbId >= 0);
			assert(!empty($this->turnitName));
			
			if (!$this->_startTurnir()) {
				$this->_outFirstScreen();
			}
		}
	}
	
	private function _outFirstScreen()
	{
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			$this->common->setDbId($dbId);
			$sql = "
				SELECT
					w.id_world,
					w.worldname
				FROM
					ds_world AS w
					INNER JOIN ds_gamemodifier AS gm ON (gm.id_world = w.id_world)
				WHERE
					gm.modifiername = 'TURNIR_WORLD'
					AND
					gm.value = '1'
				";
			
			$res = $this->common->getResultArray("", $sql , "", "", "");
			assert(empty($this->common->errormsg));
			
			foreach($res as $k => $world)
			{
				$worlds[$world['worldname']] = $dbId."_".$world['id_world'];
			}
		}
		
		
		if(count($worlds) == 0)
		{
			echo "<h1>No turnir worlds</h1>";
			return;
		}
		
		echo '
		<form action="" method="GET">
		<input type="hidden" name="action" value="showStat">
		<table border=0>
			<tr>
				<td>
					<font color=#F8F8FF><b>Turnir Name: </b></font>
				</td>
				<td>
					<input type="text" size=20 name="name" value="'.$this->turnitName.'">
				</td>
			</tr>
			<tr>
				<td>
					<font color=#F8F8FF><b>Turnir World: </b></font>
				</td>
				<td>
				<select name="worldId">';
		foreach($worlds as $worldname => $world)
		{
	
			if ($this->dbId.'_'.$this->worldId == $world) {
				$selected = 'selected';
			} else  {
				$selected = '';				
			}
			echo '
					<option value="'.$world.'" '.$selected.'>'.$worldname."\n";
		}
		echo '
				</select>
				</td>
			</tr>
		</table>
		<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;Start turnir&nbsp;&nbsp;&nbsp;&nbsp;">
		</form>';
	}
	
	
	private function _startTurnir()
	{
		
		$this->common->setDbId($this->dbId);
		
		assert(is_int($this->worldId) && $this->worldId > 0);		
		assert(!empty($this->turnitName));	
		
		// Проверяем наличие стартованного турнира в этом мире
		$currentTurnir = TN_Search::GetNotFinishedTurnirs();
		if (count($currentTurnir) > 0) {
			ShowCMSMessage('В данном мире уже идет турнир!', 2);
			return false;
		}
		
		// Проверяем наличие турнира с таким же именем
		$turnirWithSomeName = TN_Search::GetTurnirByName($this->turnitName);
		if (count($turnirWithSomeName) > 0) {
			ShowCMSMessage('Турнир с таким именем уже существует', 2);
			return false;
		}
				
		$this->common->executeQuery("begin");
		echo '<font color="white">
		<h1>STARTING TURNIR</h1> - '.date('H:m:i');
		flush();
		
		$sql = "
			UPDATE
				ds_vocationmode
			SET
				enddate = NOW()
			WHERE
				id_userinstance IN
				(
					SELECT
						ui.id_userinstance
					FROM
						ds_userinstance AS ui
					WHERE
						ui.id_world = ".$this->worldId."
				);
			";		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		echo "Vacation mode cleared<br> - ".date('H:m:i');
		flush();
		
		$sql = "
			UPDATE
				ds_userinstance
			SET
				lastrefresh = NOW()
			WHERE
				id_userinstance IN
				(
					SELECT
						ui.id_userinstance
					FROM
						ds_userinstance AS ui
					WHERE
						ui.id_world = ".$this->worldId."
				);
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		echo "Lastrefresh updated<br> - ".date('H:m:i');
		flush();
		
		// Создаем классический турнир
		$turnirId = TN_Search::InsertNewTurnir(1, "", $this->turnitName);
		assert($turnirId > 0);
		
		echo "Turnir Crated<br> - ".date('H:m:i');
		flush();
		
		$this->common->setDbId($this->dbId);
		
		$_SESSION['world'] = $this->worldId;
		$_SESSION['dbId'] = $this->dbId;
		$allowEnterAfterStart = (int)DE_WorldSettings::CreateForCurentWorld()->get('TURNIR_ALLOW_ENTER_AFTER_START');
		
		if($allowEnterAfterStart == 0)
		{	
			$sql = "
				UPDATE
					ds_gamemodifier
				SET
					value = 0
				WHERE
					modifiername = 'MAX_COUNT_WORLD_USER_INSTANCE'
					AND
					id_world = ".$this->worldId.";			
				";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
			
			$status = 'REGISTRATION_CLOSE';	
			echo "Registration closed<bt> - ".date('H:m:i');
			flush();
		} else {
			$status = 'TURNIR_STARTED';
		}
			
		$sql = "
			UPDATE
				ds_world
			SET
				world_status = (
					SELECT
						id_worldstatus
					FROM
						ds_worldstatus
					WHERE
						status_name = '$status'
				)
			WHERE
				id_world = ".$this->worldId."		
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		$this->common->executeQuery("commit");	
		
		$collectWorlds = new SU_CollectWorlds();
		
		ob_start();
		$collectWorlds->allowBases = array($this->dbId);
		$collectWorlds->collect();
		ob_clean();
		
		// Обновляем рейтинг
		//$u = new SU_UpdateRanking($this->dbId);
		//$u->Update();
		
		echo "<br>Rancking updated<br> - ".date('H:m:i');
		flush();
		
		echo "<h1>TURNIR OPENED</h1> - ".date('H:m:i');
		flush();
		
		actionLog( "Start turnir ".$this->turnitName);

		return true;
	}
}

$v = new TurnirStart($_REQUEST);
	
// turnir stop
//
//		ds_vacationmode.end_date => null
//	
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";	
?>