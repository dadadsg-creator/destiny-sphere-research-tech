<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

/*if (in_array($_REQUEST, 'action') && $_REQUEST['action'] == 'add'){
echo "<a href='?action=add' style='color:white'>Добавить админа</a><p class=maintxt>";
} else {
echo "<a href='?action=add' style='color:Yellow'>Добавить админа</a><p class=maintxt>";
}*/

if (key_exists('action', $_REQUEST) && $_REQUEST['action'] == 'show'){
	echo "<h1>Список админов</h1>";
} else {
	echo "<a href='?action=show' style='color:white'>Показать список админов</a>";
}

ChatManager::Exec($_REQUEST);

class ChatManager
{
	static private $action = 'showIndex';
	static private $username = '';

	static public function Exec($data)
	{
		self::_GetParams($data);

		switch (self::$action)
		{
			case 'show':
				self::_ShowUsers();
				break;
			case 'add':
				self::_AddUser();
				break;
			case 'delete':
				self::_DeleteUser();
				break;
			default:
				self::_ShowIndex();
				break;
		}
	}

	static private function _ShowIndex()
	{
		echo "
			<h1>Chat Manager</h1>
			<form action='' method='post'>
				<input type='hidden' name='action' value='add'>
				<SPAN class=mess_info>User name: </SPAN><input type='text' name='username' value='".self::$username."' size=20>&nbsp; 
				<input class='submit' type='submit' value='&nbsp;&nbsp;ADD&nbsp;&nbsp;'>
			</form>
			";
	}

	static private function _ShowUsers()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$sql = 'SELECT * FROM ds_chat_admin c
				LEFT JOIN ds_user u ON (u.id_user = c.id_user);';
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		echo "<a href='?action=showIndex' style='color:white'>Back</a><br><br>";
		//print_r($res);
		if (empty($res)) {
			echo "<font color='red'>No Admins<br><br></font>";
		} else {
			echo
			"<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
					<tr>
						<td>
							<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";
			foreach ($res as $key => $data)
			{

				echo "
								<tr>
									<td bgcolor='444E6C' class=maintxt>
										".$data['username']."
									</td>
									<td bgcolor='444E6C' class=maintxt>
										<a href='index.php?action=delete&username=".$data['username']."'><img src='../../../img/knopka_del.gif'></a>
									</td>
								</tr>";
			}

			echo
			"
							</table>
						</td>
					</tr>
				</table>
				";

		}
	}

	static private function _AddUser()
	{
		$common = DS_Common::CreateForCurentDB(0);

		// Проверяем ввденые данные о игроке
		if (!VD_UserName::Validate(self::$username)) {
			self::_ShowIndex();
			ShowCMSMessage("Wrong UserName!", 2);
			return;
		}

		$userId = (int)VD_UserName::CheckIfExists(self::$username, $common);
		if(!$userId)
		{
			self::_ShowIndex();
			ShowCMSMessage("User Not Exist!", 2);
			return;
		}

		// Смотрим, не является ли этот игрок уже админом
		$sql = 'SELECT * FROM ds_chat_admin WHERE id_user = '.$userId;
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if (!empty($res)) {
			self::_ShowIndex();
			ShowCMSMessage("User Already Admin!", 2);
			return;
		}

		$sql = 'INSERT INTO ds_chat_admin (id_user) VALUES ('.$userId.')';
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		actionLog( "Add chat admin '".self::$username."'", "");

		self::_ShowIndex();
		ShowCMSMessage("User Addad!", 1);
		return;

	}

	static private function _DeleteUser()
	{
		$common = DS_Common::CreateForCurentDB(0);

		// Проверяем ввденые данные о игроке
		if (!VD_UserName::Validate(self::$username)) {
			self::_ShowIndex();
			ShowCMSMessage("Wrong UserName!", 2);
			return;
		}
		
		$userId = (int)VD_UserName::CheckIfExists(self::$username, $common);
		if(!$userId)
		{
			self::_ShowIndex();
			ShowCMSMessage("User Not Exist!", 2);
			return;
		}
		
		$sql = 'DELETE FROM ds_chat_admin WHERE id_user = '.$userId;
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		actionLog( "Delete chat admin '".self::$username."'", "");
			
		echo "<br><br><a href='?action=showIndex' style='color:white'>Back</a><br><br>";

		ShowCMSMessage("User Deleted!", 1);
		return;
	}

	static private function _GetParams($data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}

		if(key_exists('username', $data))
		{
			self::$username = $data['username'];
		}
	}
}




require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>