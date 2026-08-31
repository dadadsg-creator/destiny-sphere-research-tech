<?PHP
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}

class EndVocation {

	private $dbId;
	
	private $wid;
	
	private $username = "";
	
	private $errormsg = "";

	function EndVocation( ) {
		$this->getRequest ( $_POST );
	}

	function getRequest ($request)
	{
		if (key_exists('username', $request)) {
			$this->username = $request['username'];
		}
		
		if (key_exists('id_world', $request)) {
			$wid = explode ('_', $request['id_world']);
			$this->wid = (int)$wid[0];
			$this->dbId = (int)$wid[1];
			
			assert($this->wid > 0);
			assert($this->dbId >= 0);
		}
	
		$this->showFirstMenu($this->errormsg);
		
		if (isset($request['showSecondMenu']))
		{
			$this->showSecondMenu();
		} else {
			if (isset($request['end_vocation'])) 
			{
				$this->EndUserVocation();
			} 
		}
		return true;
	}

	function showFirstMenu ( $str = "" ) 
	{
		global $common;

		if ( $this->errormsg  ) {
			// Выводим ошибку
			print ShowCMSMessage($this->errormsg, 2);
		}

		$sql = sprintf ( "select id_world, worldname from ds_world" );
		$res = $common->getMultySql ($sql);

		if ( !$res ) {
			$this->errormsg = "DeleteBuildings::showFirstMenu !!! ".$sql;
			return false;
		}

		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			$this->worlds_list[$res[$i]['id_world']."_".$res[$i]['dbId']] = $res[$i]['worldname'];
		}

		echo Forms::openForm('',$_SERVER['PHP_SELF'], 'post');
		echo Forms::hidden('showSecondMenu',1);
		print $str."
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=4>
			<tr>
				<td bgcolor='444E6C' class=th_2>
					".local( 'DS_USER_WORLD' )."</td>
				<td bgcolor='444E6C' class=th_2>
					".local( 'DS_USER_USERNAME' )."</td>
			</tr>
			<tr>
				<td bgcolor='444E6C'>";
		
		echo Forms::selectBox($this->worlds_list, $selected=$this->wid."_".$this->dbId, $name='id_world', $size=1, $multiple=false, $additional='');
		echo "	</td>
			
				<td bgcolor='444E6C' valign=top>";
		echo Forms::textField($value = $this->username, $name = 'username', $hidden = false );
		echo "</td>
			</tr>
			<tr>
				<td colspan=2 bgcolor='444E6C' class=maintxt>";
		echo Forms::hidden('unique',1);
		//		echo "&nbsp;";
		/*		echo Forms::checkBox(1,'unique',true, 'class=check').local('DS_SEARCH_ONLY_UNIQUE');*/
		/*Чек-бокс не включать, пока не будет добавлен в скрипт пересчет бонусов при удалении не */
		/*уникальных зданий*/
		echo "</td>
			</tr>
		</table></td></tr></table>";
		echo Forms::submitButton(local( 'DS_ACTION_SEARCH' ),$name = 'next', '','',' class = submit');
		echo Forms::closeForm();
		return true;
	}	//Это запчасть от первой фрмы




	function showSecondMenu () {
		global $common;
		
		assert($this->dbId >= 0);
		assert($this->wid > 0);

		$_SESSION['dbId'] = $this->dbId;
		$common->setDbId( $this->dbId );
		
		// Проверяем ввденые данные о игроке
		if (!VD_UserName::Validate($this->username)) {
			ShowCMSMessage("Wrong UserName!", 2);
			return false;
		}

		// Смотрим, есть ли такой игрок
		$sql = "
					SELECT
						*
					FROM
		    			ds_user u
					WHERE
                         u.username = '".$this->username."'";

		$userInfo = $common->getResultArray ("", $sql, "", "", "" );
		assert(empty($common->errormsg));

		if ( sizeof($userInfo) == 0 ) {
			//Игрока с таким именем нет
			print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);
			return false;
		}

		// Смотрим, зареган ли игрок в мире
		assert(sizeof($userInfo) == 1);

		//Смотрим, в каких мирах зареган игрок
		$sql = "
					SELECT
						ui.id_userinstance
					FROM
		    			ds_user u
		    			LEFT JOIN ds_userinstance ui ON (u.id_user = ui.id_user)		    			
		    		WHERE
                         u.username = '".$this->username."'
						AND
                         ui.id_world = ".$this->wid;
		;
		$userInstance = $common->getMultySql ( $sql );
		assert(empty($common->errormsg));

		if ( sizeof($userInstance) == 0 ) {
			//Игрока не зареган в этом мире
			print ShowCMSMessage('USER NOT REGISTERED IN WORLD', 2);
			return false;
		}

		/*Смотрим, включен ли у игрока отпуск*/
		$sql = "
			SELECT vm.*
			FROM ds_vocationmode vm
			LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = vm.id_userinstance)
			LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
			WHERE 
			u.username = '".$this->username."'
			AND
			ui.id_world = ".$this->wid."
			AND
			(vm.enddate > now()
			OR
			vm.enddate IS NULL
			)";

		$res = $common->getQueryArray ( $sql );
		assert(empty($common->errormsg));

		// Если у игрока нет отпуска
		if (count($res) == 0) {
			ShowCMSMessage("User is not in Vocation", 1);
			return false;
		}

		foreach ($res as $key => $value){
			echo "<font color = 'Aqua'><b>Vocation  from ".$res[0]['startdate'];
			if (!empty($res[0]['enddate'])) {
				echo "to ".$res[0]['enddate'];
			}
			echo "</b></font><br>";
			echo Forms::openForm('',$_SERVER['PHP_SELF'], 'post');
			echo Forms::hidden('end_vocation', 1);
			echo Forms::hidden('id_world', $this->wid."_".$this->dbId);
			echo Forms::hidden('username', $this->username);
			echo Forms::submitButton('End vocation', $name = 'next', '','',' class = submit');
			echo Forms::closeForm();
		}

		return true;
	}

	function EndUserVocation () {
		global $common;

		assert($this->dbId >= 0);
		assert($this->wid > 0);
		
		$_SESSION['dbId'] = $this->dbId;
		$common->setDbId($this->dbId);
		
		// Проверяем ввденые данные о игроке
		if (!VD_UserName::Validate($this->username)) {
			ShowCMSMessage("Wrong UserName!", 2);
			return false;
		}
		
		/*Смотрим, включен ли у игрока отпуск*/
		$sql = "
			UPDATE 
				ds_vocationmode 
			SET 
				enddate = now()
			WHERE
				id_userinstance = 
					(SELECT 
						ui.id_userinstance
					FROM
						ds_userinstance ui
					LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
					WHERE 
						u.username = '".$this->username."'
						AND
						ui.id_world = ".$this->wid.")
			";

		$res = $common->getQueryArray ( $sql );
		assert(empty($common->errormsg));
		
		echo  ShowCMSMessage('OK', 1);
		
		return true;
	} //END BuildDelete
}

$EndVocation = new EndVocation();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>