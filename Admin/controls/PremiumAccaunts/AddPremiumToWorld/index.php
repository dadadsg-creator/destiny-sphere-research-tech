<?

// Ставим ограничение на время выполнения скрипта 
ini_set('memory_limit', '160M');
set_time_limit(0);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;

$ac = new AddPremiumToWorld();

echo $ac->exec($_REQUEST);

class AddPremiumToWorld
{
	private $action = 'index';
	
	private $comment = "";
	
	private $countOfDays;
	
	private $worlds = array();
	
	private $years = array();
	
	private $monthes = array();
	
	private $days = array();
	
	private $startDate;
	
	private $endDate;
	
	private $premiumOnlyFlag = 0;
	
	private $usersSelected = array();
	
	function exec($data)
	{
		
		$this->_getAction($data);

		switch ($this->action)
		{
			case 'Give premium':
				$this->_getRequestForAction($data);
				$this->_addPremium();
				$this->_showIndex();
				break;
				
			case 'showGamersList':
				$this->_getRequestForList($data);
				$isOK = $this->_getGamersList();
				if(empty($isOK))
				{
					$this->_showIndex();
				}
				break;
				
			case "Cancel":
				$this->_showIndex();
				break;	
				
			default:
				$this->_showIndex();
				break;
		}
	}
	
	private function _getAction($data)
	{
		if (key_exists('action', $data)) {
			$this->action = $data['action'];
		}
	}
	
	private function _showIndex()
	{
		// Получаем список всех миров
		$worldList = $this->_getAllWorldsList();
		
		$this->days = $this->_getDays();
		
		$this->monthes = $this->_getMonths();
		
		$this->years = $this->_getYears();

		echo '<h2><font color=#F8F8FF>Add premium to worlds</h2>
		<h3> Step 1: Choose gamers</h3>';
		//Список миров
		echo '
		<form action="" method="POST">
		<input type="hidden" name="action" value="showGamersList">
		<table border=0><tr><td valign=top>
			<tr>
				<td valign=top>
					
					<font color=#F8F8FF><b>Date of registration in game: </b>from </font></b>
					<select name="startDay">
						'.$this->days.'
					</select>
					<select name="startMonth">
						'.$this->monthes.'
					</select>
					<select name="startYear">
						'.$this->years.'
					</select>
					&nbsp;&nbsp;&nbsp;
					<font color=#F8F8FF>to </font></b>
					<select name="endDay">
						'.$this->days.'
					</select>
					<select name="endMonth">
						'.$this->monthes.'
					</select>
					<select name="endYear">
						'.$this->years.'
					</select>
				</td>
			</tr>
			<tr>
				<td valign=top>
					<br>
					<font color=#F8F8FF><b>Worlds*:</b> </font></b><br>
				</td>
			</tr>
			<tr>
				<td valign=top>';

		foreach($worldList as $k => $world)
		{
			$dbName = $GLOBALS['dbWorldAccess'][$world['dbid']]['server'];
			assert(!empty($dbName));
			assert($world['id_world'] > 0);
						
			if(empty($this->worlds) || in_array($dbName.'_'.$world['id_world'], $this->worlds))
			{
				$c = ' checked ';
			} else {
				$c = '';
			}
			echo '
				<input type=checkbox name="worlds[]" value='.$dbName.'_'.$world['id_world'].' '.$c.'><font color=#F8F8FF> '.$world['worldname'].'</font><br>';
		}

		echo '
			</tr>
			<tr>
				<td>
					<br>
					<font color=#F8F8FF><b>For premium gamers only: </b></font>
					<input type="checkbox" name="OnlyPremium" style="width: 15px; height: 15px" checked >
				</td>
				
			</tr>
			<tr>
				<td valign=top>
					<input type="submit" class="submit" value="&nbsp;&nbsp;&nbsp;Show selected gamers.&nbsp;&nbsp;&nbsp;">
				</td>
			</tr>	
			<tr>	
				<td><br><br>
				<font color=#F8F8FF>*<small>Necessary fiels</small></font>
				</td>
			</tr>
		</table>
		</form>';
	}
	
	private function _getGamersList()
	{
		// выбрасываем при ошибке
		
		if (empty($this->worlds) || !is_array($this->worlds)) {
			ShowCMSMessage("Choose worlds!", 2);
			return false;
		}
		
		if(!empty($this->startDate) || !empty($this->endDate))
		{
			if($this->startDate == 'error')
			{
				ShowCMSMessage("Data set for start date is not complete", 2);
				return false;
			}
			elseif ($this->startDate == 'tooBigError')
			{
				ShowCMSMessage("The start registration date should be smaller then the end one. ", 2);
				return false;
			}
					
			if($this->endDate == 'error')
			{
				ShowCMSMessage("Data set for end date is not complete", 2);
				return false;
			}
		}
		
		// Начинаем транзакцию
		$common = DS_Common::CreateForCurentDB(0);
		$common->startTrans();

		// если выборка по старым условиям - идем в функцию где выбирается весь премиум мира
		if(empty($this->startDate) && empty($this->endDate) && !empty($this->premiumOnlyFlag))
		{
			$usersSelected = DS_Premium::GetAllUserWithPremium($this->worlds);
		}
		// если выборка предполагает выбор по дате или не только премиум людей - идем в местную функцию
		else
		{
			$usersSelected = $this->_getAllUsersForWorlds();
		}
			
		if(empty($usersSelected) || !is_array($usersSelected))
		{
			ShowCMSMessage("No users were selected.", 2);
			return false;
		}
		else 
		{
			$this->_showGamersList($usersSelected);			
		}
		
		// Завершаем транзакцию
		$common->closeTrans('commit');
		return true;
	}
	
	private function _getRequestForList($data)
	{
		if(key_exists('worlds', $data))
		{
			$this->worlds = $data['worlds'];
		}

		if($data['startDay'] != 0 || $data['startMonth'] != 0 || $data['startYear'] != 0)
		{
			$thisYear = date("Y");
							
			if(( is_numeric($data['startDay']) && !empty($data['startDay']) && $data['startDay'] <= 31) &&
				( is_numeric($data['startMonth']) && !empty($data['startMonth']) && $data['startMonth'] <= 12) &&
				( is_numeric($data['startYear']) && !empty($data['startYear']) && $data['startYear'] <= $thisYear))
			{
				// Здесь складывается дата в соответствии с тем, как она внесена в таблицу БД - локаль и тест отличаются. 
				$this->startDate = $data['startDay'].'.'.$data['startMonth'].'.'.$data['startYear'];  //так на тесте
//				$this->startDate =$data['startMonth'] .'.'.$data['startDay'].'.'.$data['startYear']; // так на локали
			}
			else 
			{
				$this->startDate = 'error';
			}
		}
		
		if($data['endDay'] != 0 || $data['endMonth'] != 0 || $data['endYear'] != 0)
		{
			if(( is_numeric($data['endDay']) && !empty($data['endDay']) && $data['endDay'] <= 31) &&
				( is_numeric($data['endMonth']) && !empty($data['endMonth']) && $data['endMonth'] <= 12) &&
				( is_numeric($data['endYear']) && !empty($data['endYear']) && $data['endYear'] <= $thisYear))
			{
				// Здесь складывается дата в соответствии с тем, как она внесена в таблицу БД - локаль и тест отличаются. 
				$this->endDate = $data['endDay'].'.'.$data['endMonth'].'.'.$data['endYear']; //так на тесте
//				$this->endDate = $data['endMonth'].'.'.$data['endDay'].'.'.$data['endYear']; // так на локали
			}
			else 
			{
				$this->endDate = 'error';
			}
		}
		
		if(!empty($this->startDate) && $this->startDate != 'error' && $this->endDate != 'error' && !empty($this->endDate))
		{
			$startDateStemp = mktime(0,0,0,$data['startMonth'], $data['startDay'], $data['startYear']);
			$endDateStemp = mktime(0,0,0,$data['endMonth'], $data['endDay'], $data['endYear']);
			if($startDateStemp >$endDateStemp)
				$this->startDate = 'tooBigError';
		}
		
		if(!empty($data['OnlyPremium']))
		{
			$this->premiumOnlyFlag = 1;
		}
	}
	
	private function _getAllUsersForWorlds()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$where = $this->_getWorldSQLLine();
		
		// если выбираются любые игроки 
		if(empty($this->premiumOnlyFlag))
		{
			// если выборка по дате регистрации
			if( !empty( $this->startDate) && !empty( $this->endDate ) )
			{
				$where = "(".$where.") AND u.registrationdate BETWEEN '".$this->startDate."' and '".$this->endDate."'";
			}
			
			$sql = " 
				SELECT
			        distinct u.id_user
			    FROM
			        ds_user AS u
			    JOIN 
			    	ds_userinstancecollection uic ON (u.id_user = uic.id_user)
			    WHERE
			    	".$where."
				ORDER BY u.id_user";
		}
		// если выбирается только премиум 
		else 
		{
			// если выборка по дате регистрации
			if( !empty( $this->startDate) && !empty( $this->endDate ) )
			{
				$where = "(".$where.") AND u.registrationdate BETWEEN '".$this->startDate."' and '".$this->endDate."'";
				$join = " JOIN ds_user AS u ON u.id_user=upd.id_user ";
			}
			
			$sql = "
				SELECT 
					distinct(upd.id_user) 
				FROM
					ds_userprofidata upd
				LEFT JOIN ds_userinstancecollection uic ON (upd.id_user = uic.id_user)	
				$join		
	            WHERE
					upd.enddate > now()
				AND
					($where)
				ORDER BY u.id_user";
		}
		
		return $common->getResultArray('', $sql, '', '');
	}
	
	private function _showGamersList($usersSelected)
	{
		$count = count($usersSelected);
		echo "
			<h2><font color=#F8F8FF>Add premium to worlds</h2>
			<h3> Step 2: Add premium</h3>
			<form action='' method='POST'>
			<table border=0>
				<tr> 
					<td>
						<font color=#F8F8FF><b>Days*: </b></font>
					</td>
					<td>
						<input type='text' name='countOfDays' size='4'>
					</td>
				</tr>
				<tr> 
					<td>
						<font color=#F8F8FF><b>Comment*: </b></font>
					</td>
					<td>
						<input type='text' name='comment' size='20'>
					</td>
				</tr>
			</table>
			<input type=submit name ='action' class='submit' value = 'Give premium' >&nbsp; &nbsp;
			<input type=submit name='action' class='submit'  value = 'Cancel'>	<br>
			<font color=#F8F8FF><b>Selected $count gamers:</b><br>";
			
		foreach ($usersSelected as $key => $value)
		{
			if ($value['id_user'] == 0) {
				continue;
			}
				
			$user = DE_User::CreateFromID((int)$value['id_user']);
			echo "
				<input type=checkbox name='premiumToUsers[]' value='{$value[id_user]}' checked>
				User '".$user->getName()."'<br>";
			
		}
		echo "
				
				<input type=submit name ='action' class='submit' value = 'Give premium' >&nbsp; &nbsp;
				<input type=submit name='action' class='submit'  value = 'Cancel'><br><br>
				*<small>Necessary fiels</small>
				</font>
				</form>";
	}
	
	private function _getRequestForAction($data)
	{
		if (key_exists('countOfDays', $data)) {
			$this->countOfDays = (int)$data['countOfDays'];
		}
		
		if( key_exists('premiumToUsers', $data ))
		{
			$this->usersSelected = $data['premiumToUsers'];
		}
		
		if( key_exists('comment', $data ))
		{
			$this->comment = $data['comment'];
		}
	}
	
	private function _addPremium($usersSelected)
	{
		if(empty($this->usersSelected) || !is_array($this->usersSelected))
		{
			ShowCMSMessage("No users were selected.", 2);
			return false;
		}
		
		
		if (empty($this->countOfDays) || $this->countOfDays <= 0) {
			ShowCMSMessage("Enter count of days!", 2);
			return false;
		}
		
		if (empty($this->comment)) {
			ShowCMSMessage("Enter comment!", 2);
			return false;
		}
		
		// Начинаем транзакцию
		$common = DS_Common::CreateForCurentDB(0);
		$common->startTrans();
		
		$countProcessed = 0;
		
		foreach ($this->usersSelected as $userID)
		{
			// Если игрок 'DestinySphere' 
			if ($userID == 0) {
				continue;
			}
				
			$user = DE_User::CreateFromID((int)$userID);
			echo "<font color=#F8F8FF>User '".$user->getName()."' - ";
				
			PU_ExecChangePremiumDays::Exec($user, $this->countOfDays, 'ACCOUNT_PROLONGED', $this->comment);
				
			echo "OK<br>";
			++$countProcessed;
		}
			
		echo "<br><b>Processed ".$countProcessed." users</b><br></font>";
		
		// Завершаем транзакцию
		$common->closeTrans('commit');

		ShowCMSMessage("Premium added!", 1);
	}
	
	
	
	private function _getAllWorldsList()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$out = array();

		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $common->getResultArray('', $sql, '', '', '');

			assert(empty($common->errormsg));

			foreach($res as $k=>$v)
			{
				$v['dbid'] = $dbid;
				$out[] = $v;
			}
		}
		return $out;
	}
	
	private function _getWorldSQLLine()
	{
		foreach ($this->worlds as $key => $world)
		{
			$tmp = explode('_', $world);
			$dbName = $tmp[0];
			$world_id = (int)$tmp[1];
			assert(!empty($dbName));
			assert($world_id > 0);
			$where .= $or."
				(	
				uic.dbname = '".$dbName."'
		   		 AND
		   		uic.id_world = ".$world_id."
		   		)
			";
			
			$or = ' OR ';
		}
		return $where;
	}
	
	private function _showSelect($value, $name='noName')
	{
		assert(is_array($value) && !empty($value));
		
		assert(is_string($name) && !empty($name));

		$vars='';
	
		foreach ($value as $key => $oneValue)
		{

			$selected = '';
			
			if(!empty($_REQUEST[$name]))
			{
				if($oneValue == $_REQUEST[$name])
				{
					$selected = ' selected';
				}
			}	
			
			$vars .= '<option value="'.$key.'" '.$selected.'>'.$oneValue.'</option>';
		}
	
		return $vars;
	}
	
	private function _getDays()
	{
		$daysArray[0] = "D";
		
		for ($i=1; $i<=31; $i++)
		{
			$daysArray[$i] = $i;
		}
		return $this->_showSelect($daysArray);
	}
	
	private function _getMonths()
	{
		$monthArray[0] = "M";
	
		for ($i=1; $i<=12; $i++)
		{
			$monthArray[$i] = $i;
		}
		
		return $this->_showSelect($monthArray);
	}
	
	private function _getYears()
	{
		$monthArray[0] = "Y";
	
		$thisYear = date('Y');
		
		$yearsArray[] = 'Y'; 
		
		for($i = 2003; $i<= $thisYear; $i++)
		{
			$yearsArray[$i] = $i;
		}
		return $this->_showSelect($yearsArray);
	}
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>