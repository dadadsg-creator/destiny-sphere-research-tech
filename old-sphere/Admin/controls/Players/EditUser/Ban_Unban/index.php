<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

class UserBan {

	private $action = "index";

	private $username = "";

	private $common;

	private $comment = "";

	function UserBan( ) {
		echo "<h1>User Ban</h1>";

		//Начинаем транзакцию
		//DS_Common::SetConnectWithTransaction(true);

		$this->getRequest ( $_REQUEST );
		
		switch ($this->action)
		{
			case 'ban':
				$this->_ban();
				$this->_showFirstMenu();
				break;
			case 'unban':
				$this->_unban();
				$this->_showFirstMenu();
				break;
			case 'showInfo':
				if (!$this->_showSecondMenu()) {
					$this->_showFirstMenu();
				}
				break;
			default:
				// Выводим индекс
				$this->_showFirstMenu();
				break;
		}

		// Закрываем транзакцию
		//ME_EndDBTransactions::Exec(true);
	}

	private function _showFirstMenu ()
	{
		print Forms::openForm('',$_SERVER["PHP_SELF"],'post');
		print Forms::hidden('showSecondMenu',1);
		print "
		<input type='hidden' name='action' value='showInfo'>
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
			<tr>
				<td>
					<table width=100% border=0 cellspacing=1 cellpadding=3>
						<tr>
							<td bgcolor='444E6C' class=th_2>
								<span class=maintxt><b>".local( 'DS_USER_USERNAME' )." : </b></span>
							</td>
						</tr>
						<tr>
							<td bgcolor='444E6C' class=maintxt align=center>
								".Forms::textField($this->username, 'username')."
							</td>
						</tr>
						<tr>
							<td bgcolor='444E6C' class=maintxt>
								".Forms::submitButton(local( 'DS_ACTION_SEARCH' ),'next','','',' class=submit')."
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>";
		print Forms::closeForm();

		return true;
	}	//Это запчасть от первой формы

	private function _showSecondMenu () {
		// Проверяем ввденые данные о игроке
		if (!VD_UserName::Validate($this->username)) {
			ShowCMSMessage("Unknown UserName!<br>", 2);
			return false;
		}

		$res = $this->_getUserInfo();
		if (count($res) == 0)
		{
			ShowCMSMessage("Unknown UserName!", 2);
			return false;
		}

		echo '
					<form action="'.$_SERVER["PHP_SELF"].'" method="post">
					<input type="hidden" name="showSecondMenu" value="1">
					<input type="hidden" name="username" value="'.$this->username.'">
					<input type="hidden" name="action" value="'.($res[0]['accountactive'] == 't' ? "ban" : "unban").'">
					<table border=0>
						<tr>
							<td>
								<span class=maintxt><b>Username: </b>
							</td>
							<td>
								</span> <span class=select_txt><b>'.$this->username.'</b></span>
							</td>
						</tr>
						<tr>
							<td>
								<span class=maintxt><b>Премиум: </b></span>
							</td>
							<td>';
		if ($res[0]['premiumactive'] == 1) {
			echo "
								<span class=select_txt><b>Да</b></span>\n";
		} else {
			echo "
								<span class=select_txt>Нет</span>\n";
		}
		echo'
							</td>
						</tr>';
		if ($res[0]['accountactive'] == 't') {
			echo'
						<tr>
							<td>
								<span class=maintxt><b>Comment: </b></span>
							</td>
							<td>
								<input type="text" name="comment" size="25" value="">
							</td>
						</tr>';
		}


		echo
		'</table>';
		print Forms::submitButton(local( ($res[0]['accountactive'] == 't' ? "DS_ACTION_BAN" : "DS_ACTION_UNBAN") ),'next','','',' class=submit');
		print Forms::closeForm();

		return true;
	}

	private function _ban()
	{
		// Проверяем ввденые данные о игроке
		if (!VD_UserName::Validate($this->username)) {
			ShowCMSMessage("Unknown UserName!<br>", 2);
			return false;
		}

		// Получаем данные игрока
		$parent = $this->_getUserInfo();
		if (count($parent) == 0)
		{
			ShowCMSMessage("Unknown UserName!", 2);
			return false;
		}

		$this->common = DS_Common::CreateForCurentDB(0);

		$sql = "UPDATE ds_user SET accountactive = false WHERE username = '".$this->username."'";
		$this->common->executeQuery ( $sql );
		assert(empty($this->common->errormsg));

		// Коммитим
		GB_SingletonObjectStorage::CommitAllCommitable();

		//Отзываем армии с защиты
		ME_BanUser::ReturnUserArmyOnDefend((int)$parent[0]['id_user'], $this->common);

		ShowCMSMessage('Забанен'.((int)$parent[0]['premiumactive'] === 1 ? "! Ахтунг!!! Вы забанили ПРЕМИУМ игрока, да как Вы посмели?":""), 1);
		
		$str = "User Name: '".$parent[0]['username']."' is ".local( 'DS_ACTION_BAN' );
		
		actionLog($str, $this->comment);
		
	}

	private function _unban()
	{
		// Проверяем ввденые данные о игроке
		if (!VD_UserName::Validate($this->username)) {
			ShowCMSMessage("Unknown UserName!<br>", 2);
			return false;
		}

		// Получаем данные игрока
		$parent = $this->_getUserInfo();
		if (count($parent) == 0)
		{
			ShowCMSMessage("Unknown UserName!", 2);
			return false;
		}

		$this->common = DS_Common::CreateForCurentDB(0);

		$sql = "UPDATE ds_user SET accountactive = true WHERE id_user = ".$parent[0]['id_user']."";
		$this->common->executeQuery ( $sql );
		assert(empty($this->common->errormsg));
		
		ShowCMSMessage('Разбанен', 1);
		actionLog( "User Name: '".$this->username."' is ".local( 'DS_ACTION_UNBAN' ) );
			
	}

	private function _getUserInfo()
	{
		$this->common = DS_Common::CreateForCurentDB(0);

		$sql = "
					SELECT 
					u.id_user,
					u.username,
					u.accountactive,
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
						(username = '".$this->username."')
					";			
		$res = $this->common->getQueryArray($sql);
		assert(empty($this->common->errormsg));

		return $res;
	}

	function getRequest ( $request )
	{
		if (key_exists('username', $request)) {
			$this->username = $request['username'];
		}

		if (key_exists('action', $request)) {
			$this->action = $request['action'];
		}

		if (key_exists('comment', $request)) {
			$this->comment = addslashes($request['comment']);
		}

		return true;
	}

}

$Ban = new UserBan();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>