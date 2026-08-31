<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

/**
 * Управление баном игроков
 *
 */
class UserBanControler
{
	private $common;
	
	private $action;
	
	private $userName = "";
	
	private $user;
	
	private $banStartDate;
	
	private $banEndDate;
	
	private $banReason;
	
	private $banComments;
	
	private $active;
	
	public function __construct($data)
	{
		//Начинаем транзакцию
		DS_Common::SetConnectWithTransaction(true);
		
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->_getParams($data);
		
		switch($this->action)
		{
			case 'user_menu' : $this->_outUserMenu(); break;
			case 'ban' : $this->_banUser(); break;
			case 'clearban' : $this->_clearUserBan(); break;
			default: $this->_outFirstScreen();
		}
		
		// Закрываем транзакцию
		ME_EndDBTransactions::Exec(true);
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		if(key_exists('username', $data))
		{
			$this->userName = $data['username'];		
			
			// Проверяем ввденые данные о игроке
			if (!VD_UserName::Validate($this->userName)) {
				ShowCMSMessage("Wrong UserName!", 2);
				$this->action = 'first_screen';
				return;
			}
				
			$userId = (int)VD_UserName::CheckIfExists($this->userName, $this->common);
			if($userId > 0)
			{
				$this->user = DE_User::CreateFromID($userId);				
				
				$this->action = 'user_menu';
			} else {
				$this->action = 'first_screen';
				ShowCMSMessage("User Not Exist!", 2);
				return;
			}
		}
		
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
			if(key_exists('active', $data) && $data['active'] == 'on')
			{
				$this->active = true;
			} else {
				$this->active = false;				
			}
			$this->user->setAccountActive($this->active);
		}
	
		if(key_exists('startdate', $data))
		{
			$this->banStartDate = (int)$data['startdate']['year'].'-'.(int)$data['startdate']['month'].'-'.(int)$data['startdate']['day'].date(' H:i');
		}
		
		if(key_exists('enddate', $data))
		{
			$this->banEndDate = (int)$data['enddate'];			
		}
		
		if(key_exists('reason', $data))
		{
			$this->banReason = $data['reason'];
		}	
		
		if(key_exists('comments', $data))
		{
			$this->banComments = pg_escape_string($data['comments']);
		}
	}
	
	private function _outFirstScreen()
	{
		echo '<h1>Search user</h1>
		<form action="" method="get">
			<SPAN class=mess_info>Username:</span> <input type="text" name="username" value="'.$this->userName.'">&nbsp;&nbsp;<input class="submit" type="submit" value=" Search ">
		</form>';
	}
	
	private function _getUserBanReason()
	{
		$sql = "
			SELECT
				ub.startdate,
				ub.enddate,
				ubr.reasonname,
				ltl.text,
				comments
			FROM
				ds_userban AS ub
				INNER JOIN ds_userbanreason AS ubr ON (ubr.id_userbanreason = ub.id_userbanreason)
				RIGHT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ubr.id_localizedname)
			WHERE
				ub.id_user = ".$this->user->getID()."
				AND
				ltl.id_language = ".$this->user->getLanguageID()."
				AND
				ub.startdate < ub.enddate
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		if(count($res) == 0)
		{
			return $res;
		}
		
		return $res[0];
	}
	
	private function _getIsUserPremium()
	{
		$sql = "
			SELECT 
					u.id_user,
					(
						SELECT 
							count(upd.id_user)
						FROM 
							ds_userprofidata as upd 
						WHERE 
							upd.id_user = u.id_user 
							and upd.profi = true 
							and upd.active = true 
							and upd.enddate > now()
					) AS premiumactive
					FROM
						ds_user u
					WHERE
						u.id_user = ".$this->user->getID()."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		return $res[0]['premiumactive'];
	}
	
	private function _outUserMenu()
	{			
		$this->_outFirstScreen();
				
		$userBan = $this->_getUserBanReason();
		$isUserPremium = $this->_getIsUserPremium();
		$startDate = '';
		$endDate = '';
		$reason = '';
		$comments = '';
		$days = '';
		if(count($userBan) > 0)
		{
			$startDate = date("Y-m-d", strtotime($userBan['startdate']));
			$startDate = explode('-', $startDate);
			$endDate = $userBan['enddate'];
			$reason = $userBan['reasonname'];
			$comments = $userBan['comments'];
			// не верьте слову days все измеряется в часах
			$days = (int)strtotime($userBan['enddate']) - (int)strtotime($userBan['startdate']);
			$days = round($days / (3600));
		} else {
			$startDate[0] = date("Y");
			$startDate[1] = date("m");
			$startDate[2] = date("d");
			$endDate = " - ";
		}
		
		echo '
		<h1>User ban</h1>
		<SPAN class=mess_info>user: </span><span class="dir">'.$this->userName.'</span><br>
		<SPAN class=mess_info>Premium: </span><span class="dir">';
		if ($isUserPremium == 1) {
			echo "Да";
		} else {
			echo "Нет";
		}
		echo '</span><br>';
		
		if(count($userBan) > 0)
		{
			echo '<font color="#FF8888">'.$userBan['text'].'</font>';
		}
		
		echo '
		<form action="?action=ban" method="post">
			<input type="hidden" name="username" value="'.$this->userName.'">
			<table cellspacing=0 border=1 cellpadding=0>
				<tr class=th_2>
					<td>&nbsp;&nbsp;Active&nbsp;&nbsp;</td>
					<td>&nbsp;&nbsp;Ban Start&nbsp;&nbsp;</td>
					<td>&nbsp;&nbsp;Ban End&nbsp;&nbsp;</td>
					<td>&nbsp;&nbsp;Ban hours&nbsp;&nbsp;</td>
					<td>&nbsp;&nbsp;Ban Reason&nbsp;&nbsp;</td>
					<td>&nbsp;&nbsp;Comments&nbsp;&nbsp;</td>
				</tr>
				<tr>
					<td align="center"><input type="checkbox" name="active" style="border: 0; background-color: #444E6C; color: #cccccc"';
		if($this->user->isActive())
		{
			echo ' checked=1'; 
		}
		echo '></td>';
		

		
		$banReason = $this->_getBanReasonList();

		echo "
					<td>&nbsp;
						<input type=text size=4 maxlength=4 name='startdate[year]' value='{$startDate[0]}'>
						<!--input type=text size=2 maxlength=2 name='startdate[month]' value='{$startDate[1]}'-->
						<select name='startdate[month]'>
			";
		
		for($i=1; $i<13; $i++)
		{
			echo "<option value=";
			
			if($i < 10)
			{
				echo "0";
			}
			echo $i;
			if($startDate[1] == $i)
			{
				echo " selected=1";
			}
			echo ">";
			if($i < 10)
			{
				echo "0";
			}
			echo $i;
		}
		
		echo "
						</select>
						<input type=text size=2 maxlength=2 name='startdate[day]' value='{$startDate[2]}'>
						&nbsp;</td>
					<td align=center>&nbsp;$endDate&nbsp;</td>
					<td align=center>&nbsp;<input type=text name=enddate value='$days' size='5'>&nbsp;</td>
					<td align=center><select name='reason'>
			";
		foreach($banReason as $k => $r)
		{
			if($reason == $r['reasonname'])
			{
				$s = 'selected=1';
			} else {
				$s = '';
			}
			
			echo "<option value='".trim($r['reasonname'])."' $s>".$r['reasonname'].'('.$r['bantime'].'h)';
		}
		echo "
					</select></td>
					<td><input type='text' size=40 name=comments value='".$comments."'></td>
				</tr>
			</table><br>".'
			<input type="submit" value="Apply" style="width: 120px;">
		</form>
		<table border=1>
			';
		
		
		foreach($banReason as $k => $r)
		{
			echo '<tr><td bgcolor="#8888A0"><b>'.$r['reasonname'].'</b>('.$r['bantime'].'h)</td><td bgcolor="#8888A0">'.$r['text'].'</td></tr>';
		}
		
		echo '</table>';
	}
	
	private function _banUser()
	{
		$uid = (int)$this->user->getID();
		assert($uid > 0);
				
		$this->common->setDbId(0);
	
		// Проверяем валидность причины бана 
		$banReason = $this->_getBanReasonList();
		$validReason = false;
		foreach ($banReason as $k => $d)
		{
			if (trim($d['reasonname']) == $this->banReason) {
				$validReason = true;
				break;
			} 
		}
		
		if (!$validReason) {
			ShowCMSMessage("Wrong Ban Reason!", 2);
			return ;
		}
		
		$this->user->setBanWithReason($this->banReason, $this->banStartDate, $this->banEndDate, $this->banComments);
		$this->user->commit();
		
		// Коммитим 
		GB_SingletonObjectStorage::CommitAllCommitable();
		
		// Отзываем армии с защиты	
		ME_BanUser::ReturnUserArmyOnDefend($uid, $this->common);

		GB_SingletonObjectStorage::DeleteObject($this->user);
		$this->user = DE_User::CreateFromID($uid);
		echo "<h1>User ban info updated</h1>";
		actionLog(
			'User time ban'.
			' Username: '.$this->user->getName(),
			' Reason: '.$this->banReason.
			' Start time: '.$this->banStartDate.
			' Time: '.$this->banEndDate.
			' Comments: '.$this->banComments
		);
		$this->_outUserMenu();
	}
	
	private function _clearUserBan()
	{
		
	}
	
	private function _getBanReasonList()
	{
		$sql = "
			SELECT
				ubr.reasonname,
				ltl.text,
				ubr.bantime
			FROM
				ds_userbanreason AS ubr
				INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ubr.id_localizedname)
			WHERE
				ltl.id_language	= 3
			";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		return $res;
	}
}

$v = new UserBanControler($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>