<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";


RegUserInTurnir::Exec();

class RegUserInTurnir
{
	static $action = 'index';

	static public function  Exec()
	{
		if (isset($_REQUEST["action"])) {
			self::$action = $_REQUEST["action"];
		}
	
		switch (self::$action)
		{
			case 'index':
				self::showIndex();
				break;
			case 'regUser':
				if (!self::regUser()) {
					self::showIndex();
				}
				break;
		}
	}

	static private function showIndex()
	{
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDbId(0);

		
		// Выбираем все рассы
		$sql = "   
						SELECT 
							r.id_race, l.text
		                FROM 
		                	ds_race r
						LEFT JOIN ds_localizedtextlanguage l ON (r.id_localizedname = l.id_localizedtext)
		                WHERE 
		                	l.id_language = 3";
		
		$races = $common->getResultArray ("", $sql, "", "", "" );
		assert(empty($common->errormsg));
		
		echo '
		<form method="post" action="'.$_SERVER["PHP_SELF"].'">		  
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
		<input name="action" type="hidden" value="regUser">
			<tr>
				<td>
					<table width=100% border=0 cellspacing=1 cellpadding=3>
						<tr>
							<td bgcolor="444E6C" class=th_2>
								'.local( 'DS_USER_USERNAME' ).'
							</td>
						</tr>
						<tr>
							<td bgcolor="#444E6C" class=maintxt>
								<input name="username" type="text" size="50" value="'.@$_REQUEST["username"].'">
							</td>
						</tr>
						<tr>
							<td bgcolor="#444E6C" class=maintxt align="left">
								Race: 
								<select name="race">';
								foreach ($races as $key => $race)
								{
									echo "
									<option  value='".$race['id_race']."'>".$race['text']."</option>";
								}
							echo '
								</select>
							</td>
						</tr>
						<tr>
							<td bgcolor="#444E6C" class=maintxt align="right">
								<input type="submit" class=Submit name="search" value="'.local( 'DS_ACTION_SEARCH' ).'">
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		';
	}

	static private function regUser()
	{
		if (empty($_REQUEST["username"])) {
			print ShowCMSMessage('Enter User Name', 2);
			return false;
		}
		global $common ;
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDbId(0);

		//Выбираем общую инфу о игроке
		$sql = "
		SELECT
						*
					FROM
		    			ds_user u
					WHERE
                         u.username = '".$_REQUEST["username"]."'";

		$userInfo = $common->getResultArray ("", $sql, "", "", "" );
		assert(empty($common->errormsg));
		
		if ( sizeof($userInfo) == 0 ) {
			//Игрока с таким именем нет
			print ShowCMSMessage(local('DS_USER_NOT_FOUND'), 2);
			return false;

		}
		assert(sizeof($userInfo) == 1);

		$turnirDbID = 1;
		$turnirWorldID = 3;
		$turnirBdName = 'turnir';
		$turnirWorldCollection = 5;
		
		/*$turnirDbID = 3;
		$turnirWorldID = 1;
		$turnirBdName = 'UnlimSecond';
		$turnirWorldCollection = 3;*/
		
		$common->setDbId($turnirDbID);

		$userId = (int)$userInfo[0]['id_user'];
		assert($userId > 0);
		
		// Смотрим, есть ли такой игрок в базе турнира
		$sql = "
		SELECT
						*
					FROM
		    			ds_user u
					WHERE
                         u.username = '".$_REQUEST["username"]."'";

		$res = $common->getResultArray ("", $sql, "", "", "" );
		assert(empty($common->errormsg));
		
		if ( sizeof($res) == 0 ) {
			//Игрока с нет, то регаем его
			$sql = "
				INSERT INTO
		    			ds_user
		    	(id_user, id_language, username, email, admin, accountactive, selfdescription, password, time_delta, activate_mail)
		    	VALUES
		    	(".$userInfo[0]['id_user'].", ".$userInfo[0]['id_language'].", '".$userInfo[0]['username']."', '".$userInfo[0]['email']."', '".$userInfo[0]['admin']."', '".$userInfo[0]['accountactive']."', '".$userInfo[0]['selfdescription']."', '".$userInfo[0]['password']."', ".$userInfo[0]['time_delta'].", ".$userInfo[0]['activate_mail'].")";
			$res = $common->getResultArray ("", $sql, "", "", "" );
			assert(empty($common->errormsg));
		}
		

		//Смотрим, не зареган ли игрок уже в турнире
		$sql = "
				SELECT 
					* 
				FROM 
					ds_userinstance ui 
                WHERE
	                ui.id_user = ".$userId."
	                 AND
	                ui.id_world = ".$turnirWorldID;


		$res = $common->getResultArray ("", $sql, "", "", "" );

		if ( count($res) > 0 ) {
			//Игрок уже зареган там
			print ShowCMSMessage('Игрок уже зарегистрирован в Турнире', 2);
			return false;

		}
		
		$isProfi = 't';
		$race = (int)$_REQUEST["race"];
		assert(1 <= $race &&  $race <= 3);
		$combName = $_REQUEST["username"];
		assert(!empty($combName));

		$_SESSION['dbId'] = $turnirDbID;
		$_SESSION['world'] = $turnirWorldID;
		$common->setDbId($turnirDbID);

		$user = DE_User::CreateFromID($userId);
		GE_SearchStart::SetCommon($common);
		$world_type = GE_SearchStart::GetWorldType($turnirWorldID);

		// проверяем имя соты
		if(!VD_CombName::Validate($combName, true, $common))
		{
			print ShowCMSMessage(VD_CombName::FetchErrorCode(), 2);
			return false;
		}

		// проверяем есть ли свободное место
		if(GE_SearchStart::GetFreeCombs($turnirWorldID, $race) == 0)
		{
			print ShowCMSMessage('NO_FREE_COMBS', 2);
			return false;
		}

		$wu = &ClassFactory::getInstance("WorldUtils");
		if(!$wu)
		{
			print ShowCMSMessage('UNABLE_TO_CREATE_INSTANCE', 2);
			return false;
		}

		$newUIID = $wu->addNewInstance($userId, $turnirWorldID, $race, $combName, $world_type);
		if(!$newUIID)
		{
			print ShowCMSMessage('UNABLE_TO_CREATE_INSTANCE', 2);
			return false;
		}
		
		// Получаем новый UIID (потому что wu->addNewInstance почему-то возвращает 1)
		$sql = "
				SELECT 
                    ui.id_userinstance 
                FROM 
                    ds_userinstance ui 
                WHERE
                    ui.id_user = ".$userId."
                     AND
                    ui.id_world = ".$turnirWorldID."
					";
		$res = $common->getResultArray ("", $sql, "", "", "" );
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		$newUIID = (int)$res[0]['id_userinstance'];
		assert($newUIID > 0);
		
		$worldStatus = GE_SearchStart::GetWorldStatusDirect($turnirWorldID, $turnirDbID);
		// выставляем режим отпуска
		$common->setDbID($turnirDbID);
		if($worldStatus == 'OPEN')
		{
			$sql = "
						INSERT INTO	ds_vocationmode
							(startdate, id_userinstance)
						VALUES
							(NOW(), $newUIID)
					";
		} else {
			$sql = "
						INSERT INTO	ds_vocationmode
							(startdate, enddate, id_userinstance)
						VALUES
							(NOW()-'2 HOUR'::interval, NOW()-'1 HOUR'::interval, $newUIID)
					";
		}
		$res = $common->getResultArray ("", $sql, "", "", "" );
		assert(empty($common->errormsg));
			
		$common->setDbId(0);
		// Вставляем в мастер инфу о том, что игрок может входить в турнирный мир
		// Смотрим, нет ли этой записи
		$sql = "
		          SELECT 
		          		*
		          FROM  
		          		ds_userinstancecollection
		          WHERE 
		          		id_user = ".$userId." 
		          		 AND 
		          		id_world = ".$turnirWorldID."
               			 AND 
               			dbname = '".$turnirBdName."'";
		$res = $common->getResultArray ("", $sql, "", "", "" );
		assert(empty($common->errormsg));
		
		if (count($res) > 0) {
			$sql = "
						INSERT INTO	ds_userinstancecollection
							(id_user, id_userinstance, id_world, dbname, admin, id_worldcollection)
						VALUES
                        	(".$userId.", ".$newUIID.", ".$turnirWorldID.", '".$turnirBdName."', 'f', ".$turnirWorldCollection.")
					";
			$res = $common->getResultArray ("", $sql, "", "", "" );
			assert(empty($common->errormsg));	
		}
		
		print ShowCMSMessage('User Reg Successful', 1);
	}
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>