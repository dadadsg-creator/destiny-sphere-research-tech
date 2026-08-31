<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=TradeBan><b>HELP</b></a></p>';

$obj = new TradeBan();

class TradeBan
{
	private $common = false;
	private $username = "";
	private $world = "";
	private $banReason = "";
	private $duration = "";
	private $date = array();
	
	function TradeBan()
	{	
		if(!$this->getPostData($_POST))
		{
			echo "<font color=red>Error: Can't get post data</font>";
		}
	}
	
	function getPostData($data)
	{
//echo "<font color=whete><pre>";
//print_r($data);
//etit;
		isset($data['username'])? $this->username = $data['username']:$username = '';
		isset($data['world'])? $this->world= $data['world']:$world = '';
		isset($data['banReason'])? $this->banReason = $data['banReason']:$banReason = '';
		isset($data['duration'])? $this->duration = $data['duration']:$duration = '';
		
		isset($data['td'])? $this->date['td'] = $data['td']:$this->date['td'] = date("d");
		isset($data['tm'])? $this->date['tm'] = $data['tm']:$this->date['tm'] = date("m");
		
		//isset($data['action'])? $this->v = $data['action']:$this->action = "";

		switch ($data['action'])
		{
			case 'ban':
				$this->_ban();
				break;
			case 'unBan':
				$this->_unBan();
				break;
			case 'updateban':
				$this->_updateBan();
				break;	
			case 'showBanned':
				$this->_showBanned();
				break;
			default:
				$this->showRequestForm();
				break;
		}
								
		/*if(isset($data['ban']) && isset($data['username']))
		{
			$this->_ban();
		}
			
		if(isset($data['unBan']) && isset($data['username']))
		{
			$this->_unBan();
		}
						
		if(isset($data['updateban']) && isset($data['username']))
		{
			$this->_updateBan();
		}
		
		if(isset($data['showBanned']))
		{
			$this->_showBanned();
		}

				$this->showRequestForm();*/

		return true;
	}
	
	function showRequestForm()
	{

		$worlds = $this->getWordList();
		$banReasons = $this->getBanReasons();
		
		echo "<h1>Ban user</h1>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<input type='hidden' name='action' value='ban'>
						<table border=1 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Username:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							World:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Ban Start:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Duration:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Ban reason:
							</TD>			
						</TR>
						<TR>
								<TD bgcolor='#444E6C' class=maintxt>
								<input type='text' name='username' value='".$this->username."'>
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								<select name=world>";
		
								foreach ($worlds as $k => $worldName)
								{
			echo 					"<option value='".$k."'>$worldName</option>";
								}
								
			echo "				
								</select>
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>";
			
		echo "<select name='td'> ";
		for($i=1; $i<=31; $i++)
		{
			$selected = '';			
			if((int)$this->date['td'] == $i)
			{
				$selected = 'SELECTED';
			}
			echo "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		echo "</select>
					<select name='tm'>";
		
		for($i=1;$i<=12;$i++)
		{
			$selected = '';			
			if((int)$this->date['tm'] == $i)
			{
				$selected = 'SELECTED';
			}
			echo "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		echo "</select>
					<select name='ty'>";
		
		for ($i=date('Y');$i<=date('Y')+2;$i++ )
		{
			$selected = '';			
			if((int)$this->date['ty'] == $i)
			{
				$selected = 'SELECTED';
			}
			echo "<OPTION ".$selected." value=".$i.">".$i."</OPTION>";
		}
		echo "</select>";
								
			echo "				</TD>
								<TD bgcolor='#444E6C' class=maintxt align='right'>
								<input type='text' name='duration' value='".$this->duration."' size=4>
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								<select name=banReason>";
			foreach ($banReasons as $key => $reason)
			{
				echo 			"<option value='".$reason['id_ban_reason']."'>".$reason['name']."</option>";
			}
								
			echo "				</select>
								</TD>								
						</TR>
				</TABLE>";
						
			echo "<br><input type='submit' name='ban' class='Submit' value='Ban user'>
				</form>";

			echo "<h1>UnBan user</h1>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<input type='hidden' name='action' value='unBan'>
				<table border=1 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Username:
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							World:
							</TD>
						</TR>
						<TR>
								<TD bgcolor='#444E6C' class=maintxt>
								<input type='text' name='username' value='".$this->username."'>
								</TD>
								<TD bgcolor='#444E6C' class=maintxt>
								<select name=world>";
		
								foreach ($worlds as $k => $worldName)
								{
			echo 					"<option value='".$k."'>$worldName</option>";
								}
								
			echo "				
								</select>
								</TD>
						</TR>
				</TABLE>
				<br><input type='submit' name='unBan' class='Submit' value='Unban user'>
				</form>";
				
			echo "<h1>Show all banned users</h1>
				<form method='post' action='".$_SERVER["PHP_SELF"]."'>
				<input type='hidden' name='action' value='showBanned'>
				<table border=1 cellspacing=1 cellpadding=3>
						<TR>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							World:
							</TD>
						</TR>
						<TR>
								<TD bgcolor='#444E6C' class=maintxt>
								<select name=world>";
		
								foreach ($worlds as $k => $worldName)
								{
			echo 					"<option value='".$k."'>$worldName</option>";
								}
								
			echo "				
								</select>
								</TD>
							</TR>
						</TABLE>
				<br><input type='submit' name='showBanned' class='Submit' value='Show'>
				</form>";
				
						
		return true;			
	}
	
	private function _ban()
	{
		if (empty($this->username))
		{		
			//Выводим кнопку назад
		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
		<input type='submit' class='Submit' value='Back'>
		</form>";
		
		echo "<font color=red>Error: Empty user name!</font>";
			return false;
		}

		if (empty($this->world))
		{		
			echo "<font color=red>Error: Empty world!</font>";
			return false;
		}

		if (empty($this->banReason))
		{		
			echo "<font color=red>Error: Empty banReason!</font>";
			return false;
		}

		//$banStart = mktime(date(H), date(i), date(s), $this->date['tm'], $this->date['td'], $this->date['ty']);
		
		//if (!empty($this->duration))
		//{
		//	$banEnd = strtotime("+ $this->duration hour");
//			$partSql = ", end_date";
		//}
//echo "banStart = ".$banStart."<br>";
//echo date(" Y-m-d H:i:s", $banStart);
//exit;*/


		$tempArr = explode('_', $this->world);
		$dbId = $tempArr['0'];
		$worldId = $tempArr['1'];

		assert($dbId >= 0 );
		assert($worldId >= 0 );
		
		$conn = $this->getDBConnection();
		$conn->setDbId($dbId);
		
		//Получаем id пользавателя по его имени
		$sql = "SELECT u.id_user
											FROM
												ds_user as u
											WHERE
												u.username = '$this->username'
					";
		$res = $conn->getResultArray('', $sql, '', '', '');
		assert(empty($conn->errormsg));
		
		//Если такого пользователя нет, то выводим ошибку
		if (empty($res))
		{
			 //Выводим кнопку назад
		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
		<input type='submit' class='Submit' value='Back'>
		</form>";
			 
			echo "<font color=red>Error: User '$this->username' not exist!</font>";
			return false;
		}
		$userId = $res[0]['id_user'];
		
		//Проверяем не забанен ли уже этот пользаватель
		$sql = "SELECT								
						b.*			
					FROM
						ds_trade_user_ban as b				
					WHERE
						b.id_user = $userId
						AND
						b.id_world = $worldId
		";
		$res = $conn->getResultArray("", $sql, "", "", "");
		assert(empty($conn->errormsg));
/*echo "<font color=white><pre>";
print_r($res);
exit;		*/
		if (!empty($res))
		{
			//Смотрим на дату окончания бана
			//если она меньше текущей даты
			if (strtotime($res[0]['end_date']) < time())
			{
				//то обновляем дату окончания бана
				$this->_updateBan();
				return true;
			} else 
			{
				//Спрашиваем, надо ли обновить дату бана
				$this->showConfirmForm($res[0]['start_date'], $res[0]['end_date']);
				return true;
			}
		} else {
			$this->_setBan($userId);
		}
		
		return true;
	}
	
	private function _unBan()
	{
		if (empty($this->username))
		{		
			echo "<font color=red>Error: Empty user name!</font>";
			return false;
		}

		if (empty($this->world))
		{		
			echo "<font color=red>Error: Empty world!</font>";
			return false;
		}

		$tempArr = explode('_', $this->world);
		$dbId = $tempArr['0'];
		$worldId = $tempArr['1'];

		assert($dbId >= 0 );
		assert($worldId >= 0 );
		
		$conn = $this->getDBConnection();
		$conn->setDbId($dbId);
		
		//Получаем id пользавателя по его имени
		$sql = "SELECT u.id_user
											FROM
												ds_user as u
											WHERE
												u.username = '$this->username'
					";
		$res = $conn->getResultArray('', $sql, '', '', '');
		assert(empty($conn->errormsg));
		
		//Если такого пользователя нет, то выводим ошибку
		if (empty($res))
		{
			echo "<font color=red>Error: User '$this->username' not exist!</font>";
			return false;
		}
		$userId = $res[0]['id_user'];
		
		//Проверяем не забанен ли уже этот пользаватель
		$sql = "DELETE								
					FROM
						ds_trade_user_ban as b				
					WHERE
						b.id_user = $userId
						AND
						b.id_world = $worldId
		";
/*echo "<font color=white><pre>";
echo $sql;
exit;*/		
		$res = $conn->getResultArray("", $sql, "", "", "");
		assert(empty($conn->errormsg));

				//Получаем имя мира по его id
		$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = $worldId";

		$res = $conn->getResultArray("", $sql, "", "", "");
		assert(empty($conn->errormsg));
		$worldName = $res[0]['worldname'];
		
		actionLog(
			'Trade user unban',
			' Username: '.$this->username.',<br>'.
			' World: '.$worldName
		);
		
		//Выводим кнопку назад
		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
		<input type='submit' class='Submit' value='Back'>
		</form>";
		
		echo "<h1>OK!</h1>";
		
		return true;
	}
	private function _setBan($userId)
	{
		$tempArr = explode('_', $this->world);
		$dbId = $tempArr['0'];
		$worldId = $tempArr['1'];

		assert($dbId >= 0 );
		assert($worldId >= 0 );
		
		$conn = $this->getDBConnection();
		$conn->setDbId($dbId);
		
		$banStart = mktime(date(H), date(i), date(s), $this->date['tm'], $this->date['td'], $this->date['ty']);
		
		if (!empty($this->duration))
		{
			$banEnd = ", to_timestamp(".strtotime("+ $this->duration hour").")";
			$partSql = ", end_date";
		}

		$sql = "INSERT INTO ds_trade_user_ban 
								(id_user, id_world, id_ban_reason, start_date$partSql) 
								values 
								($userId, $worldId, $this->banReason, to_timestamp($banStart) $banEnd)
					";
/*echo "<font color=white><pre>";
echo $sql;
exit;*/		
		$res = $conn->executeQuery($sql);
		assert(empty($conn->errormsg));
		
		
		if (!empty($this->duration))
		{
			$duration = $this->duration.' hour';
		} else {
			$duration = 'forever';
		}
		
		//Получаем имя мира по его id
		$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = $worldId";

		$res = $conn->getResultArray("", $sql, "", "", "");
		assert(empty($conn->errormsg));
		$worldName = $res[0]['worldname'];
		
		//Получаем имя reason по его id
		$sql = "SELECT
						COALESCE(ltl.text, tubr.reason_name) as name						
					FROM
					   ds_trade_user_ban_reason as tubr
  			   		LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = tubr.id_localizedname 
  			   														AND 
  			   														ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
  			   		WHERE
  			   		tubr.id_ban_reason = $this->banReason";

		$res = $conn->getResultArray("", $sql, "", "", "");
		assert(empty($conn->errormsg));
		$reasonName = $res[0]['name'];
		
		actionLog(
			'Trade user ban',
			' Username: '.$this->username.',<br>'.
			' World: '.$worldName.',<br>'.
			' Reason: '.$reasonName.',<br>'.
			' Start time: '.date(" Y-m-d H:i:s", $banStart).',<br>'.
			' Time: '.$this->duration.' hour'
		);
		
		//Выводим кнопку назад
		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
		<input type='submit' class='Submit' value='Back'>
		</form>";
		
		echo "<h1>Set ban successful!</h1>";

	}
	
	
	
	
	private function showConfirmForm($oldStartDate, $oldEndDate)
	{
		$str =  "User $this->username already was banned";
		if (!empty($oldEndDate))
		{
			$str .= " from $oldStartDate to $oldEndDate";
		} else {
			$str .= " forever!";
		}
		$str .= "<br>Change ban end date?";
		
		echo "<font color = white>$str</font>";
		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>";
		echo "<input type='hidden' name='username' value='".$this->username."'>
			<input type='hidden' name='world' value='".$this->world."'>
			<input type='hidden' name='td' value='".$this->date['td']."'>
			<input type='hidden' name='tm' value='".$this->date['tm']."'>
			<input type='hidden' name='ty' value='".$this->date['ty']."'>
			<input type='hidden' name='duration' value='".$this->duration."'>
			<input type='hidden' name='banReason' value='".$this->banReason."'>
		
			<input type='hidden' name='action' value='updateban'>";
						
		echo "<input type='submit' name='updateban' class='Submit' value='Yes'><input type='submit' name='noUpdateban' class='Submit' value='No'>";
		echo "</form>";
		
	}
	
	private function _updateBan()
	{
		$tempArr = explode('_', $this->world);
		$dbId = $tempArr['0'];
		$worldId = $tempArr['1'];

		assert($dbId >= 0 );
		assert($worldId >= 0 );
		
		$conn = $this->getDBConnection();
		$conn->setDbId($dbId);
		
		//Получаем id пользавателя по его имени
		$sql = "SELECT u.id_user
											FROM
												ds_user as u
											WHERE
												u.username = '$this->username'
					";
		$res = $conn->getResultArray('', $sql, '', '', '');
		assert(empty($conn->errormsg));
		
		//Если такого пользователя нет, то выводим ошибку
		if (empty($res))
		{
			//Выводим кнопку назад
		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
		<input type='submit' class='Submit' value='Back'>
		</form>";
		
		echo "<font color=red>Error: User '$this->username' not exist!</font>";
			return false;
		}
		$userId = $res[0]['id_user'];
		
		$banStart = mktime(date(H), date(i), date(s), $this->date['tm'], $this->date['td'], $this->date['ty']);

		if (!empty($this->duration))
		{
			$end = " to_timestamp(".strtotime("+ $this->duration hour").")";
			$duration = $this->duration." hour";
		} else {
			$duration = "forever";
			$end = " null";
		}
		
		$sql = "UPDATE ds_trade_user_ban 
								SET
									id_ban_reason = $this->banReason,
									start_date =  to_timestamp($banStart),
									end_date =$end
								WHERE
									id_user = $userId
									AND
									id_world = $worldId
					";
/*echo "<font color=white><pre>";
echo $sql;
exit;	*/	
		$res = $conn->executeQuery($sql);
		assert(empty($conn->errormsg));
		
		//Получаем имя мира по его id
		$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = $worldId";

		$res = $conn->getResultArray("", $sql, "", "", "");
		assert(empty($conn->errormsg));
		$worldName = $res[0]['worldname'];	
		
		//Получаем имя reason по его id
		$sql = "SELECT
						COALESCE(ltl.text, tubr.reason_name) as name						
					FROM
					   ds_trade_user_ban_reason as tubr
  			   		LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = tubr.id_localizedname 
  			   														AND 
  			   														ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
  			   		WHERE
  			   		tubr.id_ban_reason = $this->banReason";

		$res = $conn->getResultArray("", $sql, "", "", "");
		assert(empty($conn->errormsg));
		$reasonName = $res[0]['name'];
		
		actionLog(
			'Update trade user ban',
			' Username: '.$this->username.',<br>'.
			' World: '.$worldName.',<br>'.
			' Reason: '.$reasonName.',<br>'.
			' Start time: '.date(" Y-m-d H:i:s", $banStart).',<br>'.
			' Duration: '.$duration
		);
		
		//Выводим кнопку назад
		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
		<input type='submit' class='Submit' value='Back'>
		</form>";
		
		echo "<h1>Update successful!</h1>";
		return true;
		
	}
	
	private function _showBanned()
	{
		$tempArr = explode('_', $this->world);
		$dbId = $tempArr['0'];
		$worldId = $tempArr['1'];

		assert($dbId >= 0 );
		assert($worldId >= 0 );
		
		$conn = $this->getDBConnection();
		$conn->setDbId($dbId);
		
		//Получаем id пользавателя по его имени
		$sql = "SELECT								
					b.end_date,
					b.start_date,
			        u.username,
       				COALESCE(ltl.text, tubr.reason_name) as reason_name			
				FROM
					ds_trade_user_ban	as b	
                LEFT JOIN ds_user  as u ON (u.id_user = b.id_user)
                LEFT JOIN ds_trade_user_ban_reason as tubr ON (tubr.id_ban_reason = b.id_ban_reason)
                LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = tubr.id_localizedname
  			   													AND
  			   													ltl.id_language = ".constant('DEFAULT_LANGUAGE').") 			
				WHERE
					b.id_world = $worldId
				ORDER BY b.end_date DESC
					";
/*echo "<font color=white><pre>";
echo $sql;
exit;*/		
		$res = $conn->getResultArray('', $sql, '', '', '');
		
		assert(empty($conn->errormsg));
/*echo "<font color=white><pre>";
print_r($res);
exit;*/
		//Выводим кнопку назад
		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>
		<input type='submit' class='Submit' value='Back'>
		</form>";

		echo "<table border=1 cellspacing=2 cellpadding=2>
						<TR>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Username
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Ban Start
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Ban End
							</TD>
							<TD bgcolor='444E6C' class=th_2 align='center'>
							Ban reason
							</TD>			
						</TR>";

		foreach ($res as $key =>$val)
		{
			echo "
						<TR>
							<TD bgcolor='#444E6C' class=maintxt>
							".$val['username']."
							</TD>
							<TD bgcolor='#444E6C' class=maintxt>
							".$val['start_date']."
							</TD>
							<TD bgcolor='#444E6C' class=maintxt>
							".$val['end_date']."
							</TD>
							<TD bgcolor='#444E6C' class=maintxt>
							".$val['reason_name']."
							</TD>			
						</TR>";
		}
		
		echo "</TABLE>";
		
		return true;
		
	}
	
	function getBanReasons()
	{
		$conn = $this->getDBConnection();

		$conn->setDbId(0);

		$sql = "SELECT
						tubr.id_ban_reason,
						COALESCE(ltl.text, tubr.reason_name) as name						
					FROM
					   ds_trade_user_ban_reason as tubr
  			   		LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = tubr.id_localizedname 
  			   														AND 
  			   														ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
					";
		$res = $conn->getResultArray('', $sql, '', '', '');
		assert(empty($conn->errormsg));

		return $res;
	}
	
	function getWordList()
	{
		$conn = $this->getDBConnection();

		$out = array();

		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$conn->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $conn->getResultArray('', $sql, '', '', '');
			assert(empty($conn->errormsg));

			foreach($res as $k=>$v)
			{
				$keyRes = $dbid."_".$v['id_world'];
				$out[$keyRes] = $v['worldname'];
			}
		}
		
		return $out;
	}
	
	function getDBConnection()
	{
		if(!$this->common)
		{
			$this->common = &ClassFactory::getInstance("DS_Common");
			if (!$this->common->connect()) 
			{
				$this->outputResult('DB connection failed');
				exit();
			}	
		}
		
		return $this->common;		
	}
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>