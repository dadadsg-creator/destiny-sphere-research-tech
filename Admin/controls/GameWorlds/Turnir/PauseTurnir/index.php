<?php
ini_set('memory_limit', '16M');
set_time_limit(0);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
//require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";


class TurnirPause
{
	private $action;

	private $common;

	private $worldId;

	private $dbId;

	private $id_turnir_pause;

	// Start Date
	private $startDate;

	// End Date
	private $endDate;

	function __construct($data)
	{
		echo "<h1>Pause Turnir</h1>";

		$this->common = DS_Common::CreateForCurentDB(0);
		if (!$this->_getPostData($data)) {
			$this->_outFirstScreen();
			return;
		}

		$this->_outFirstScreen();

		switch ($this->action) {
			case 'showPause':
				$this->_showPause();
				break;

			case 'pause':
				$this->_pauseTurnir();
				$this->_showPause();
				break;

			case 'update':
				$this->_update();
				$this->_showPause();
				break;

			case 'delete':
				$this->_delete();
				$this->_showPause();
				break;
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
		<input type="hidden" name="action" value="showPause">
		<table border=0>
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
		</table><br>
		<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;Show&nbsp;&nbsp;&nbsp;&nbsp;">
		</form>';
	}

	private function _showPause()
	{
		$this->common->setDbId($this->dbId);

		$sql = "
			SELECT * FROM ds_turnir_pause
			";	

		$pauses = $this->common->getResultArray("", $sql , "", "", "");
		assert(empty($this->common->errormsg));

		echo '
		<h3 style="font-family: Arial, sans; font-size: 1.1em">Add Pause to world:</h3>
		<form action="" method="GET">
		<input type="hidden" name="action" value="pause">
		<input type="hidden" name="worldId" value="'.$this->dbId.'_'.$this->worldId.'">
		<table border=0>
			<tr>
				<td>
					<font color=#F8F8FF><b>Start: </b></font>
				</td>
				<td>
					<input type="text" size="21" name="start_date" value="'.date('Y-m-d H:i:s').'" />
				</td>
			</tr>
			<tr>
				<td>
					<font color=#F8F8FF><b>End: </b></font>
				</td>
				<td>
					<input type="text" size="21" name="end_date" value="'.date('Y-m-d H:i:s').'" />
				</td>
			</tr>
		</table>
		<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;Add&nbsp;&nbsp;&nbsp;&nbsp;">
		</form>';


		if (count($pauses) > 0)
		{
			echo '
					<h3 style="font-family: Arial, sans; font-size: 1.1em">Pauses</h3>';

			foreach ($pauses as $pk => $p)
			{
				echo '
						<div style="font-family: Arial,sans">
						<form action="?" method="POST">
						<input type="hidden" name="id_turnir_pause" value="'.$p['id_turnir_pause'].'" />
						<input type="hidden" name="action" value="update" />
						<input type="hidden" name="worldId" value="'.$this->dbId.'_'.$this->worldId.'">
						<div><div style="font-family: Helvetica,Arial,sans; float: left; font-size: 0.7em; width: 30px; padding: 1px 10px">id: '.$p['id_turnir_pause'].'</div>
						<font color=#F8F8FF>Starts:&nbsp;</font>
						<input type="text" name="start_date" size="21" value="'.$p['start_date'].'" />
						<font color=#F8F8FF>&nbsp;&nbsp;Ends:&nbsp;</font>
						<input type="text" size="21" name="end_date" value="'.$p['end_date'].'" />&nbsp;
						<input type="submit" class="submit" value="Update" />&nbsp;
						<a href="?action=delete&worldId='.$this->dbId.'_'.$this->worldId.'&id_turnir_pause='.$p['id_turnir_pause'].'" class="alert" onclick="return confirm(\'Are you sure?\')">delete</a></div>
						</form>
						</div>';
			}
		}
	}


	private function _pauseTurnir()
	{
		$this->common->setDbId($this->dbId);

		$sql = "
			INSERT INTO
				ds_turnir_pause
			(start_date, end_date, id_world)	
			VALUES
			('$this->startDate', '$this->endDate', $this->worldId)";	

		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));

		return true;
	}

	private function _update()
	{
		$this->common->setDbId($this->dbId);

		$sql = "
			UPDATE
				ds_turnir_pause
			SET
				start_date = '".$this->startDate."',
				end_date = '".$this->endDate."'
			WHERE 
				id_turnir_pause = ".$this->id_turnir_pause;	

		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));

		return true;
	}

	private function _delete()
	{
		$this->common->setDbId($this->dbId);

		$sql = "
			DELETE FROM
				ds_turnir_pause
			WHERE 
				id_turnir_pause = ".$this->id_turnir_pause;	

		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));

		return true;
	}

	private function _getPostData($data)
	{
		if(key_exists('worldId', $data))
		{
			$wordArr = explode("_", $data['worldId']);
			$this->dbId = (int)$wordArr[0];
			$this->worldId = (int)$wordArr[1];
			assert($this->worldId > 0);
			assert($this->dbId >= 0);
		}

		if(key_exists('start_date', $data))
		{
			$this->startDate = $data['start_date'];

			if (!$this->_checkDateFormat($this->startDate)) {
				ShowCMSMessage('Wrong Data Fromat!', 2);
				return false;
			}
		}

		if(key_exists('end_date', $data))
		{
			$this->endDate = $data['end_date'];
			
			if (!$this->_checkDateFormat($this->endDate)) {
				ShowCMSMessage('Wrong Data Fromat!', 2);
				return false;
			}
		}

		if(key_exists('id_turnir_pause', $data))
		{
			$this->id_turnir_pause = (int)$data['id_turnir_pause'];
			assert($this->id_turnir_pause > 0);
		}

		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
		
		return true;
	}

	private function _checkDateFormat($dateStr)
	{
		$res = preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2})\:([0-9]{2})\:([0-9]{2})$/', $dateStr, $matches);

		$year = intval($matches[1]);
		$month = intval($matches[2]);
		$day = intval($matches[3]);

		$hours   = intval($matches[4]);
		$minutes = intval($matches[5]);
		$seconds = intval($matches[6]);

		if (!checkdate($month, $day, $year) || $hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59 || $seconds < 0 || $seconds > 59) {
			return false;
		}
		//echo '<pre>'; print_r($matches); echo "</pre>\n";
		return $res === 1 ? 1 : false;
	}
}

$v = new TurnirPause($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>