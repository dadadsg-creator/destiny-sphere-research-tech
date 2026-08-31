<?php $CVS_Version = '$Revision$ ; $Date$';

class GE_ControlStart
{
	public $errormsg;
	
	public $errorname;
	
	/**
	 * ОБъект для работы с бд
	 *
	 * @var DS_Common
	 */
	private $common;
	
	/**
	 * ползователь ск оторым работаем
	 *
	 * @var DE_User
	 */
	private $user;
	
	function __construct()
	{	
		$this->_validSession();
			
		$this->common = DS_Common::CreateForCurrentDB();
		
		$this->user = DE_User::CreateFromID($_SESSION['uid']);
		
		if ( !$this->getPostData ( $_POST ) )
		{

			echo $this->errormsg;
		}
	}

	
	private function _validSession()
	{
		assert(key_exists("uid", $_SESSION));
		assert(key_exists("lang", $_SESSION));
		assert(key_exists("language", $_SESSION));
		assert(key_exists("time_delta", $_SESSION));
		assert(key_exists("s_login", $_SESSION));
		assert(key_exists("s_pass", $_SESSION));
		assert(key_exists("profi_game", $_SESSION));
		assert(key_exists("after_reg_trial", $_SESSION));
		assert(key_exists("dbid", $_SESSION));
	}
	
	function getPostData($data)
	{
		global $common;
		global $language;
		global $questions;

		$error = array();

		if ( sizeof ( $data ) == 0 )
		{			
			if(!$this->showStartGame($_SESSION["uid"]))
			{
				return false;
			}
		}elseif(isset($data['start'])){
			
			$uiidArr = explode('_', $data['uiid']);
			$uiid = $uiidArr[0];
			$dbId = $uiidArr[1];
			$_POST['uiid'] = $uiid;

			$_SESSION['dbId'] = $dbId;

			//require_once ( 'Validator.php' );
			$v=new ValidateUiid ( $uiid );

			if ( !$v->isValid () ) {
				$error = $v->getError ();
			}

			$_SESSION['dbId'] = $dbId;
			
			$common->setDbId($_SESSION['dbId']);
			
			//Генерируем код для блокировки окошек
			$windowKey = mt_rand(10, 99);
			$_SESSION['windowKey'] = $windowKey;
			
			$sql = "DELETE FROM ds_userinstancewindowkey
					WHERE id_userinstance = ".$_POST['uiid'];
			$res = $common->executeQuery($sql);
			assert(empty($common->errormsg));
			
			$sql = "INSERT INTO ds_userinstancewindowkey
					(id_userinstance, windowkey)
					VALUES
					(".$_POST['uiid'].", ".$windowKey.")";
			$res = $common->executeQuery ( $sql );
			assert(empty($common->errormsg));
			
						
			//Это ловушка для людей которые обходят авторизациюс помощью подмены UIID
			$sql = "SELECT *
					FROM
						ds_userinstance ui
					WHERE
						ui.id_userinstance = ".$_POST['uiid'];

			$res = $common->getResultArray ( "is_world_catch_profi_id_userinstance".$_POST['uiid'], $sql, "", "", "GLOBAL_CACHE" );

			if(!$res){
				$sql = "SELECT *
						FROM
							ds_userinstance ui
						WHERE
			ui.id_userinstance = ".$_POST['uiid'];

				$res = $common->getResultArray ( "is_world_catch_profi_id_userinstance".$_POST['uiid'], $sql, "", "", "GLOBAL_CACHE" );

				if(!$res){
					$res[0]['username'] = $_POST['uiid'].' - несуществующий UIID';
				}

				$logMsg = $_SESSION["s_login"]." as ".$res[0]['username']."\n";

				header("Location: index_login.php");
				exit();
			}
			//конец ловушки

			if ( (is_array($error) && isset($error[1]) ) || ( isset($error) && !is_array($error)) ) {

				if ( !$this->showStartGame ( $_SESSION["uid"], $error[1], 1 ) )
				{
					return false;
				}

			} else {

				$sql = sprintf ( "
						SELECT
							w.id_world
						FROM
							ds_world w,
							ds_userinstance ui
						WHERE
							w.id_world = ui.id_world AND
							w.profi = true AND
							ui.id_userinstance = %d",
				$_POST['uiid'] );

				$res = $common->getResultArray ( "is_world_profi_id_userinstance".$_POST['uiid'], $sql, "", "", "NO_CACHE" );
				if ( $res === false ) {
					$this->errormsg = "SYSTEM ERROR - Start::getPostData7";
					return false;
				}

				if ( sizeof ( $res ) > 0 ) {

					$sql = sprintf ( "
							SELECT
								upd.id_user
							FROM
								ds_userprofidata upd,
								ds_userinstance ui
							WHERE
								upd.id_user = ui.id_user AND
								upd.profi = true AND
								upd.active = true AND
								upd.enddate > now() AND
								ui.id_userinstance = %d",
					$_POST['uiid'] );

					$res = $common->getResultArray ( "is_user_profi_id_useristance_".$_POST['uiid'], $sql, "", "", "NO_CACHE" );
					if ( $res === false ) {
						$this->errormsg = "SYSTEM ERROR - Start::getPostData8-1";
						return false;
					}

					$sql = sprintf ( "
							SELECT
								id_user
							FROM
								ds_usersallowed
							WHERE
								id_user = %d
							",
					$_SESSION['uid']
					);

					$res2 = $common->getResultArray ( "is_user_allowed_id_user_".$_SESSION['uid'], $sql, "", "", "GLOBAL_CACHE" );
					if ( $res2 === false ) {
						$this->errormsg = "SYSTEM ERROR - Start::getPostData-UserAllowed";
						return false;
					}


					if ( sizeof ( $res ) == 0 && sizeof ( $res2 ) == 0) {

						include_once ( "./language/".$language."/index_start0.lang" );

						if ( !@$lang ) {
							$this->errormsg = "SYSTEM ERROR - Start::getPostData6";
							return false;
						}

						$this->showStartGame ( $_SESSION["uid"], $lang['PROFI_USERS_ONLY'], 1 );

						return true;
					}

					if(sizeof ( $res ) != 0)
					{
						$this->setScaling($_SESSION["uid"]);
					}

				} //END OF if ( sizeof ( $res ) > 0 )

				$_SESSION["uiid"] = $_POST['uiid'];

				$sql = sprintf ( "
						SELECT
							u.ID_Language,
							u.id_user,
							wo.ID_World,
							ra.ID_Race,
							ri.ID_Ring,
							cc.ID_CombComplex,
							co.ID_Comb,
							co.CombNumber,
							l.shortname,
							co.combscore,
							wt.worldtype
						FROM
							DS_UserInstance ui,
							DS_User u,
							DS_World wo,
							DS_Race ra,
							DS_Ring ri,
							DS_CombComplex cc,
							DS_Comb co,
							ds_language l,
							ds_worldhastype wht,
							ds_worldtype wt
						WHERE
							wht.id_world = wo.id_world AND
							wht.id_worldtype = wt.id_worldtype

							and ui.ID_User = u.ID_User
							and ui.ID_World = wo.ID_World
							and ui.ID_Race = ra.ID_Race
							and co.ID_UserInstance = ui.ID_UserInstance
							and co.ID_CombComplex = cc.ID_CombComplex
							and cc.ID_Ring = ri.ID_Ring
							and ri.ringnumber != 4
							and ui.ID_UserInstance = %d
							and u.id_language = l.id_language
							and u.username = '%s'
							and u.password = '%s'
						ORDER BY ui.id_userinstance",
				$_SESSION["uiid"],
				$_SESSION["s_login"],
				$_SESSION["s_pass"] );

				$res = $common->getResultArray ( "userinfo_id_userinstance_".$_SESSION["uiid"], $sql, "", "", "NO_CACHE" );
				if ( !$res ) {
					$this->errormsg = "SystemError - Start: Username ".$_SESSION["s_login"]." and password {censored} does not match to ".$_SESSION["uiid"]; //Start::getPostData1
					return false;
				}


				$_SESSION["shortname"] = $res[0]['shortname'];
				$_SESSION["lang"] = $res[0]['id_language'];
				$_SESSION["race"] = $res[0]['id_race'];
				$_SESSION["world"] = $res[0]['id_world'];
				$_SESSION["ring"] = $res[0]['id_ring'];
				$_SESSION["combcomplex"] = $res[0]['id_combcomplex'];
				$_SESSION["comb"] = $res[0]['id_comb'];
				$_SESSION["celltype"] = 1;
				$_SESSION["combnumber"] = $res[0]['combnumber'];
				$_SESSION["userscore"] = $res[0]['combscore'];
				$_SESSION["worldtype"] = $res[0]['worldtype'];

				if ( isset ( $_SESSION['profi_game'] ) ) {


					$common->executeQuery ( "begin" );

					$sql = sprintf ( "delete from ds_singleuseraction2
							where id_userinstance = %d",
					$_SESSION["uiid"] );

					$res = $common->executeQuery ( $sql );
					if ( $res === false ) {
						$this->errormsg = "SystemError - Start::getPostData2";
						return false;
					}

					$res = "";

					if (isset($_SERVER['HTTP_CLIENT_IP']))
					{
						$ip_proxy = $_SERVER['HTTP_CLIENT_IP'];
						$ip = $_SERVER['REMOTE_ADDR'];
					} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
					{
						$ip = $_SERVER['REMOTE_ADDR'];
						$ip_proxy = $_SERVER['HTTP_X_FORWARDED_FOR'];
					} else {
						$ip_proxy = '';
						$ip = $_SERVER['REMOTE_ADDR'];
					}

					$common->executeQuery ( "commit" );
					
					$common->setDbId($dbId);
					ME_LoginHistory::Exec($_SESSION["uid"], $common);
												
					$str = "Location: detect_player.php";
					if ( session_id() ) $str .= "?".session_name()."=".session_id();
					Header ( $str );
				} else {


					$common->setDbId(0);
					$common->executeQuery ("begin");

					$sql = "delete from ds_loginqueue where id_userinstance = " . $_POST['uiid'];
					$res = $common->executeQuery ( $sql );
					if ( $res === false ) {
						$this->errormsg = "SystemError - Start::getPostData3";
						return false;
					}

					$sql = "insert into ds_loginqueue (id_userinstance, logintime, lastupdate) values (" . $_POST['uiid'] . ", now(), now())";

					$res = $common->executeQuery ( $sql );
					if ( $res === false ) {
						$this->errormsg = "SystemError - Start::getPostData4";
						return false;
					}

					$common->executeQuery ( "commit" );

					$_SESSION["uiid"] = $_POST['uiid'];

					$res = "";

					$common->setDbId($dbId);
					ME_LoginHistory::Exec($_SESSION["uid"], $common);

					$str = "Location: index_queue.php";
					if ( SID ) $str .= "?".SID;
					Header ( $str );
					exit;
				}
			}

		} else if ( isset ( $data["add"] ) )
		{

			$worldArr = explode('_', $_POST['world']);
			$_POST['world'] = $worldArr[0];
			$dbId = $worldArr[1];

			//require_once( 'Validator.php' );

			$common->setDbId($dbId);

			$v[1]=new ValidateComb ( $_POST['comb'] );
			foreach ( $v as $validator )
			{
				if ( !$validator->isValid () )
				{
					$error=$validator->getError ();
					if ( !empty($error[1]) )
					{
						if (isset ($errorMsg) )
						{

							$errorMsg.="<li>".$error[1]."</li>\n";
						} else {
							$errorMsg ="<li>".$error[1]."</li>\n";
							//unset($errorMsg);
						}
					}
				}
			}
			if ( isset ( $errorMsg ) )
			{
				$this->showStartGame( $_SESSION["uid"], $errorMsg, 2 );
			} else {

				$sql = sprintf ( "select id_world
						from ds_world
						where profi = true
						and id_world = %d",
				$_POST['world'] );
				$res = $common->getResultArray ( "is_world_profi_".$_POST['world'], $sql, "", "", "GLOBAL_CACHE" );
				if ( $res === false ) {
					$this->errormsg = "SYSTEM ERROR - Start::getPostData7";
					return false;
				}

				if ( sizeof ( $res ) > 0 )
				{

					$sql = sprintf ( "select upd.id_user
							from ds_userprofidata upd
							where upd.profi = true
							and upd.active = true
							and upd.enddate > now()
							and upd.id_user = %d",
					$_SESSION['uid'] );

					$res = $common->getResultArray ( "is_user_profi_id_user_".$_SESSION['uid'], $sql, "", "", "NO_CACHE" );
					if ( $res === false ) {
						$this->errormsg = "SYSTEM ERROR - Start::getPostData8-2";
						return false;
					}

					$sql = sprintf ( "
							SELECT
								id_user
							FROM
								ds_usersallowed
							WHERE
								id_user = %d
							",
					$_SESSION['uid']
					);

					$res2 = $common->getResultArray ( "is_user_allowed_id_user_".$_SESSION['uid'], $sql, "", "", "GLOBAL_CACHE" );
					if ( $res2 === false ) {
						$this->errormsg = "SYSTEM ERROR - Start::getPostData8-3";
						return false;
					}

					if ( sizeof ( $res ) == 0 && sizeof ( $res2 ) == 0) {

						include_once ( "./language/".$language."/index_start0.lang" );

						if ( !@$lang ) {
							$this->errormsg = "SYSTEM ERROR - Start::getPostData6";
							return false;
						}

						$this->showStartGame( $_SESSION["uid"], $lang['PROFI_USERS_ONLY'], 2 );

						return true;
					}

					if(sizeof ( $res ) != 0)
					{
						$this->setScaling($_SESSION["uid"]);
					}

				}

				$sql = sprintf ( "
								SELECT
									wt.worldtype
								FROM
										ds_world w
									INNER JOIN ds_worldhastype wht ON (w.id_world = wht.id_world)
									INNER JOIN ds_worldtype wt ON (wht.id_worldtype = wt.id_worldtype)
								WHERE
									w.id_world = %d",
				$_POST['world']);

				$res = $common->getResultArray ( "world__type_by_world_id_".$_POST['world'], $sql, "", "", "GLOBAL_CACHE" );
				if ( $res === false ) {
					$this->errormsg = "SYSTEM ERROR - Start::getPostData7";
					return false;
				}

				$world_type = $res[0]['worldtype'];
				$_SESSION['world_type'] = $world_type;

				$sql = sprintf ( "
						SELECT
							COUNT(co.ID_Comb) AS c
						FROM
							DS_Comb co,
							DS_CombComplex cc,
							DS_Ring ri,
							DS_World wo
						WHERE
							co.ID_CombComplex = cc.ID_CombComplex AND
							cc.ID_Ring = ri.ID_Ring AND
							ri.ID_World = wo.ID_World AND
							co.ID_UserInstance is NULL AND
							wo.id_world = %d AND
							ri.ringnumber =	(
								SELECT ringnumber
								FROM ds_racehasbasering
								WHERE id_race = %d
							) AND
							NOT EXISTS (
								SELECT id_comb
								FROM ds_reservedcomb
								WHERE id_comb = co.id_comb
							)",
				$_POST["world"],
				$_POST["race"] );

				$res = $common->getResultArray ( "free_comb_on_ring_id_world_id_race_".$_POST["world"]."_".$_POST["race"], $sql, "", "", "" );

				if ( $res === false ) {
					$this->errormsg = "SYSTEM ERROR - Start::getPostData5";
					return false;
				}

				if ( $res[0]['c'] == 0 )
				{
					include_once ( "./language/".$language."/index_start0.lang" );

					if ( !@$lang ) {
						$this->errormsg = "SYSTEM ERROR - Start::getPostData6";
						return false;
					}

					$this->showStartGame( $_SESSION["uid"], $lang['NO_FREE_COMBS'], 2 );

					return true;
				}

				$wu = & ClassFactory::getInstance ( "WorldUtils" );

				if ( !$wu )
				{
					$this->errormsg = "SystemError - Start::getPostData6";
					$this->showStartGame ( $_SESSION["uid"], $this->errormsg, 2 );
					return true;
				}

				if ( !$wu->addNewInstance( $_SESSION["uid"], $_POST['world'], $_POST["race"], $_POST["comb"] , $world_type) )
				{
					echo $wu->errormsg;
					return false;
				}

				$_SESSION["comb"] = "";
				$_SESSION["world"] = "";
				$_SESSION["race"] = "";

				$res = "";

				$str = "Location: index_start.php";
				if ( session_id() ) $str .= "?".session_name()."=".session_id();

				Header ( $str );

				exit;

			}
		}  else if ( isset ( $data["question"] ) )
		{

			$sql = sprintf ( "
					SELECT id_user, id_gender_new
					FROM ds_form
					WHERE id_user = %d",
			$_SESSION["uid"] );

			$res = $common->getResultArray ("2questions".$_SESSION["uid"], $sql, "", "", "");

			if ($res && $res[0]['id_gender_new'] <= 0 )
			{
				$_SESSION['day'] = (int)$data['day'];
				$_SESSION['month'] = (int)$data['month'];
				$_SESSION['year'] = (int)$data['year'];
				$_SESSION['gender'] = (int)$data['gender'];
				$_SESSION['country'] = (int)$data['country'];
				$_SESSION['city'] = (int)$data['city'];


				$this->errormsg = "";
				if ($_SESSION['day'] <= 0) {
					$this->errormsg = 'Enter Day!';
				} elseif ($_SESSION['month'] <= 0) {
					$this->errormsg = 'Enter Month!';
				} elseif ($_SESSION['year'] <= 0) {
					$this->errormsg = 'Enter Year!';
				} elseif ($_SESSION['gender'] <= 0) {
					$this->errormsg = 'Enter Gender!';
				} elseif ($_SESSION['country'] <= 0) {
					$this->errormsg = 'Enter Country!';
				} elseif ($_SESSION['city'] <= 0) {
					$this->errormsg = 'Enter City!';
				}


				if (!empty($this->errormsg))
				{
					$this->showStartGame ( $_SESSION["uid"], $this->errormsg, 1 );
					return false;
				} else {
					//p.paymentdate < to_timestamp ('".$_REQUEST['eDate']."', 'YYYY-MM-DD HH24:MI')
					$_SESSION['birthdate'] = $_SESSION['year'].'-'.$_SESSION['month'].'-'.$_SESSION['day'];
					$sql = "
					UPDATE
						ds_form
					SET
						id_city = ".$_SESSION['city'].",
						id_country = ".$_SESSION['country'].",
						id_gender_new = ".$_SESSION['gender'].",
						birthdate = to_timestamp ('".$_SESSION['birthdate']."', 'YYYY-MM-DD')
					WHERE id_user = ".$_SESSION["uid"]."

				";
					$res = $common->executeQuery ( $sql );

					if (!$res)
					{
						$this->errormsg = "Error update Questions.";
						return false;
					}




					if ( !$this->showStartGame( $_SESSION["uid"] ) ) {
						return false;
					}
				}




			}else {
				if ( !$this->showStartGame( $_SESSION["uid"] ) )
				{
					return false;
				}
			}
		}



		return true;
	}

	function setScaling($uid)
	{
		global $common;
		$sql = sprintf ( "
							SELECT
      								scaling
							FROM
      							ds_userprofitechdata
							WHERE
     							 id_user = %d",
		$uid);

		$res = $common->getResultArray ( "scaling".$uid, $sql, "", "", "NO_CACHE" );
		if ( $res === false )
		{
			$this->errormsg = "SYSTEM ERROR - Start::setScaling()";
			return false;
		}

		if($res && count($res)>0)
		{
			$_SESSION["scaling"] = $res[0]['scaling'];
		}

		return true;

	}

	function showStartGame($uid, $str = "", $block = "" )
	{
		
		if($_SESSION['profi_game'])
		{
			$this->setScaling($uid);
		}


		$tpl = new HTML_Template_IT ("./templates");

		// template 1
		include_once("./language/".$language."/index_start1.lang");
		assert(is_array($lang));		

		$tpl->loadTemplatefile("index_start1.tpl", true, true);

		foreach($lang as $key=>$val)
		{
			$tpl->setVariable ( $key, $lang[$key] ) ;
		}
		unset($lang);
		
		if(!isset($_SESSION['profi_game']))
		{
			$tpl->setVariable ( "LOGO", "images/logo_sp3k.gif" ) ;
		} else {
			$tpl->setVariable ( "LOGO", "images/logo_DS_profi.gif" ) ;
		}

		$tpl->show ();
		unset( $lang );

		if ( $uid == "" )
		{
			$this->errormsg = "SystemError - Start::showStartGame2";
			return false;
		}

		$GLOBALS['USER_HAS_UIID'] = false;
		$GLOBALS['USER_HAS_UIIDS'] = false;
		$userInstances = array();

		//Извлекаем список инстансов пользователя
		$sql = "SELECT
				*
			FROM
				ds_userinstancecollection uic,
				ds_worldcollection wc
			WHERE
				uic.id_world = wc.id_world AND
				wc.dbname = uic.dbname AND
				uic.ID_User = ".$uid;
		$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );

		if(count($res)>0)
		{
			foreach ($res as $kW =>$vW)
			{
				$sttr = false;

				/* Запоминаем все инстансы где юзер зарегистрирован*/
				$GLOBALS['USER_HAS_UIIDS'][] = $vW['dbname'];

				if (isset($GLOBALS['dbWorldAccess']) && is_array($GLOBALS['dbWorldAccess']))
				{
					if (count($GLOBALS['dbWorldAccess']) > 0)
					{
						foreach ($GLOBALS['dbWorldAccess'] as $kdbid => $vdb)
						{
							if ($vdb['server'] == $vW['dbname'])
							{
								$sttr = $vW['id_world'].'_'.$vW['dbname'];
							}
						}
					}
				}

				if ($sttr != false)
				{
					$userInstances[] = $sttr;
				}
			}
			if (count($userInstances)>0)
			{
				$GLOBALS['USER_HAS_UIID'] = $userInstances;
			}
		}

		/********************** Затычка для проведения дополнительного опросника *******************/
		$sql = sprintf ( "
						SELECT
							id_user, id_gender_new
						FROM
							ds_form
						WHERE
							id_user = %d",
		$_SESSION["uid"] );

		$res2 = $common->getResultArray ("questions2".$_SESSION["uid"], $sql, "", "", "");
		/*******************************************************************************************/

		if ($res2 && empty($res2[0]['id_gender_new']))
		{
			if ( $sttr != "" && $block == 1 ) {
				// template 5
				$tpl->loadTemplatefile ( "index_start5.tpl", true, true );
				$tpl->setVariable ( "TEXT1", $str ) ;
				$tpl->show ();
			}


			//тут показываем формочку перекрывая выбор мира в котором уже зарегистрированы

			include_once ( "./language/".$language."/index_start14.lang" );

			if ( !@$lang ) {
				$this->errormsg = "SystemError - Start::showStartGame5";
				return false;
			}

			$tpl->loadTemplatefile ( "index_start14.tpl", true, true );

			foreach ( $lang as $key=>$val ) {

				$tpl->setVariable ( $key, $lang[$key] ) ;
			}

			$sql = sprintf ( "select g.id_form_country,
					ltl.text as localizedtext
					from ds_form_country g,
					ds_localizedtextlanguage ltl
					where g.id_localizedtext = ltl.id_localizedtext
					and ltl.id_language = %d
					order by g.id_form_country",
			$_SESSION['lang'] );

			$res = $common->getResultArray ( "country2", $sql, "", "", "" ); //GLOBAL_CACHE
			if ( !$res )
			{
				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm10";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$tpl->setCurrentBlock ( "COUNTRY" );
				$tpl->setVariable ( "COUNTRY_VALUE", $res[$i]['id_form_country'] );
				$tpl->setVariable ( "COUNTRY_TEXT", $res[$i]['localizedtext'] );
				if ( @$_SESSION['country'] == $res[$i]['id_form_country'] )
				{
					$tpl->setVariable ( "COUNTRY_SELECTED", "selected" );
				}
				$tpl->parseCurrentBlock ( "COUNTRY" );
			}

			/* START GENDER */
			$sql = sprintf ( "select g.id_gender,
					ltl.text as localizedage
					from ds_form_gender g,
					ds_localizedtextlanguage ltl
					where g.id_localizedgender = ltl.id_localizedtext
					and ltl.id_language = %d
					order by g.id_gender",
			$_SESSION['lang'] );

			$res = $common->getResultArray ( "gender2", $sql, "", "", "" ); //GLOBAL_CACHE
			if ( !$res )
			{
				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm3";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$tpl->setCurrentBlock ( "GENDER" );
				$tpl->setVariable ( "GENDER_VALUE", $res[$i]['id_gender'] );
				$tpl->setVariable ( "GENDER_TEXT", $res[$i]['localizedage'] );
				if ( @$_SESSION['gender'] == $res[$i]['id_gender'] ) {
					$tpl->setVariable ( "GENDER_SELECTED", "selected" );
				}
				$tpl->parseCurrentBlock ( "GENDER" );
			}
			/* END GENDER */


			/* START CITY */
			$sql = sprintf ( "select g.id_form_city,
					ltl.text as localizedtext
					from ds_form_city g,
					ds_localizedtextlanguage ltl
					where g.id_localizedtext = ltl.id_localizedtext
					and ltl.id_language = %d
					order by g.id_form_city",
			$_SESSION['lang'] );

			$res = $common->getResultArray ( "city2", $sql, "", "", "" ); //GLOBAL_CACHE
			if ( !$res )
			{
				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm3";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$tpl->setCurrentBlock ( "CITY" );
				$tpl->setVariable ( "CITY_VALUE", $res[$i]['id_form_city'] );
				$tpl->setVariable ( "CITY_TEXT", $res[$i]['localizedtext'] );
				if ( @$_SESSION['city'] == $res[$i]['id_form_city'] ) {
					$tpl->setVariable ( "CITY_SELECTED", "selected" );
				}
				$tpl->parseCurrentBlock ( "CITY" );
			}
			/* END CITY */

			/* START DAY */
			for ( $i=1; $i<=31; $i++ )
			{
				$tpl->setCurrentBlock ( "DAY" );
				$tpl->setVariable ( "DAY_VALUE", $i );
				$tpl->setVariable ( "DAY_TEXT", $i );
				if ( @$_SESSION['day'] == $i ) {
					$tpl->setVariable ( "DAY_SELECTED", "selected" );
				}
				$tpl->parseCurrentBlock ( "DAY" );
			}
			/* END DAY */


			/* START MONTH */
			for ( $i=1; $i<=12; $i++ )
			{
				$tpl->setCurrentBlock ( "MONTH" );
				$tpl->setVariable ( "MONTH_VALUE", $i );
				$tpl->setVariable ( "MONTH_TEXT", $i );
				if ( @$_SESSION['month'] == $i ) {
					$tpl->setVariable ( "MONTH_SELECTED", "selected" );
				}
				$tpl->parseCurrentBlock ( "MONTH" );
			}
			/* END MONTH */

			if (@$_SESSION['year'] == "" || empty($_SESSION['year'])) {$_SESSION['year'] = 1987;}

			/* START YEAR */
			for ( $i=1900; $i<=date("Y"); $i++ )
			{
				$tpl->setCurrentBlock ( "YEAR" );
				$tpl->setVariable ( "YEAR_VALUE", $i );
				$tpl->setVariable ( "YEAR_TEXT", $i );
				if ( @$_SESSION['year'] == $i ) {
					$tpl->setVariable ( "YEAR_SELECTED", "selected" );
				}
				$tpl->parseCurrentBlock ( "YEAR" );
			}
			/* END YEAR */






			$tpl->show ();
			unset ( $lang );


		} else {


			if ( sizeof ( $res ) == 0 )
			{
				// template 2

				/*include_once ( "./language/".$language."/index_start2.lang" );

				if ( !@$lang ) {
					$this->errormsg = "SystemError - Start::showStartGame4";
					return false;
				}

				$tpl->loadTemplatefile ( "index_start2.tpl", true, true );

				foreach ( $lang as $key=>$val ) {

					$tpl->setVariable ( $key, $lang[$key] ) ;
				}

				$tpl->show ();
				unset ( $lang );*/

			} else {


				if($str != "" && $block == 1)
				{
					// template 5
					$tpl->loadTemplatefile ( "index_start5.tpl", true, true );
					$tpl->setVariable ( "TEXT1", $str ) ;
					$tpl->show ();
				}

				// template 3
				include_once ( "./language/".$language."/index_start3.lang" );

				if ( !@$lang ) {
					$this->errormsg = "SystemError - Start::showStartGame5";
					return false;
				}

				$tpl->loadTemplatefile ( "index_start3.tpl", true, true );

				foreach ( $lang as $key=>$val ) {

					$tpl->setVariable ( $key, $lang[$key] ) ;
				}

				$instanceArray = array();
				for ( $i=0; $i<sizeof ( $res ); $i++ )
				{
					$array = array();
					$sttr = false;

					if (isset($GLOBALS['dbWorldAccess']) && is_array($GLOBALS['dbWorldAccess']))
					{
						if (count($GLOBALS['dbWorldAccess']) > 0)
						{
							foreach ($GLOBALS['dbWorldAccess'] as $kdbid => $vdb)
							{
								if ($vdb['server'] == $res[$i]['dbname'])
								{
									$sttr = true;
								}
							}
						}
					}

					if ($sttr == false)
					{
						continue;
					}

					$filename = 'GAMEdown_'.$common->getDbIdByName($res[$i]['dbname']).'_'.$res[$i]['id_world'];
					if (GameDown($common->getDbIdByName($res[$i]['dbname']), $res[$i]['id_world']))
					{
						$world_closed = true;
					} else {
						$world_closed = false;
					}
					$admin = $res[$i]['admin'] == 't' ? 1 : 0;

					if ($world_closed)
					{
						$array['VALUE'] = '-1';
					} else {
						$array['VALUE'] = $res[$i]['id_userinstance'].'_'.$common->getDbIdByName($res[$i]['dbname'])."_".$res[$i]['id_world'];
					}

					$array['WORLDNAME'] = $res[$i]['worldname'].($world_closed ? " (".$lang['world_closed'].")" : "")."";

					$instanceArray[] = $array;
				}

				if (count($instanceArray)>0)
				{
					foreach ($instanceArray as $value)
					{
						$tpl->setCurrentBlock ( "BLOCK1" );
						$tpl->setVariable ( "VALUE", $value['VALUE'] ) ;
						$tpl->setVariable ( "WORLDNAME",  $value['WORLDNAME']) ;
						$tpl->parseCurrentBlock ( "BLOCK1" );
					}

					$tpl->show ();
				}



				unset ( $lang );

			}
		}

		// template 4
		include_once ( "./language/".$language."/index_start4.lang" );

		if ( !@$lang ) {
			$this->errormsg = "SystemError - Start::showStartGame6";
			return false;
		}

		$tpl->loadTemplatefile ( "index_start4.tpl", true, true );

		foreach ( $lang as $key=>$val ) {
			$tpl->setVariable ( $key, $lang[$key] ) ;
		}

		$tpl->show ();
		unset ( $lang );

		$connections = $GLOBALS['dbWorldAccess'];
		$allrs = array();



		//Список миров в которых можно регистрироваться
		if ( $isAdmin ){
			$sql = "
					SELECT
						*
					FROM
						ds_worldcollection as wc";
		} else {
			if(!$fullprofi)
			{
				$additionalCondition = " and not (wc.profi) ";
			}else{
				$additionalCondition = "";
			}

			$sql = "
					SELECT
						*
					FROM
						ds_worldcollection AS wc INNER JOIN ds_worldtype AS wt ON (wt.id_worldtype = wc.id_worldtype)
					WHERE
						wt.worldtype != 'TURNIR' AND
						wc.max_users > 0 ".$additionalCondition;
		}

		$resTemp = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		if($common->errormsg)
		{
			$this->errormsg = "SystemError - Start::Cant select future worlds";
			return false;
		}

		$res = array();

		if ( (!constant('ONE_WORLD_REGISTRATION_ONLY') || $isAdmin) && $GLOBALS['USER_HAS_UIID'])
		{
			foreach ( $resTemp as $kW=>$vW )
			{
				$sttr = false;

				if (isset($GLOBALS['dbWorldAccess']) && is_array($GLOBALS['dbWorldAccess']))
				{
					if (count($GLOBALS['dbWorldAccess']) > 0)
					{
						foreach ($GLOBALS['dbWorldAccess'] as $kdbid => $vdb)
						{

							if ($vdb['server'] == $vW['dbname'])
							{
									$sttr = true;
							}
						}
					}
				}

				if ($sttr == false)
				{
					continue;
				}

				$chek = $vW['id_world'].'_'.$vW['dbname'];
				if( in_array($chek, $GLOBALS['USER_HAS_UIID']) )
				{
					continue;
				}
				$res[$kW] = $vW;
			}//END OF FOREACH
		} else {
			foreach ( $resTemp as $kW=>$vW )
			{
				$sttr = false;

				if (key_exists('USER_HAS_UIIDS', $GLOBALS) && is_array($GLOBALS['USER_HAS_UIIDS']) && in_array($vW['dbname'], @$GLOBALS['USER_HAS_UIIDS']) && $vW['profi'] == "f" && !$isAdmin)
				{
					$res = array();
					break;
				}

				if (isset($GLOBALS['dbWorldAccess']) && is_array($GLOBALS['dbWorldAccess']))
				{
					if (count($GLOBALS['dbWorldAccess']) > 0)
					{
						foreach ($GLOBALS['dbWorldAccess'] as $kdbid => $vdb)
						{
							if ($vdb['server'] == $vW['dbname'])
							{
									$sttr = true;
							}
						}
					}
				}

				if ($sttr == false)
				{
					continue;
				}


				if( $GLOBALS['USER_HAS_UIID'] && constant('ONE_WORLD_REGISTRATION_ONLY'))
				{
					$chek = $vW['id_world'].'_'.$vW['dbname'];

					if( in_array($chek, $GLOBALS['USER_HAS_UIID']) )
					{
						continue;
					}else{
						if($GLOBALS['USER_HAS_UIID'] && !$vW['multy_access'] && constant('ONE_WORLD_REGISTRATION_ONLY')){
							continue;
						}else{
							$res[$kW] = $vW;
						}
					}
				} else {
					$res[$kW] = $vW;
				}//END OF IF
			}//END OF FOREACH

		}//END OP ELSE

		/***** Затыка для турнира*******/
		$sql = "SELECT * FROM ds_usersallowedcollection WHERE id_user = ".$uid;
		$resTurnir = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );

		$turnirWorlds = array();

		if(count($resTurnir)>0 && $resTurnir)
		{
			$sql = "
					SELECT
						*
					FROM
						ds_worldcollection as wc
					WHERE
						id_world = '".$resTurnir[0]['id_world']."' AND
						dbname = '".$resTurnir[0]['dbname']."'";
			$resTurnirWorlds = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
			if($common->errormsg){
				$this->errormsg = "SystemError - Start::get turnir worlds";
				return false;
			}

			if (count($resTurnirWorlds) > 0)
			{
				foreach ( $resTurnirWorlds as $kT => $vT)
				{
					$chek = $vT['id_world'].'_'.$vT['dbname'];

					if ($GLOBALS['USER_HAS_UIID'])
					{
						if(in_array($chek, $GLOBALS['USER_HAS_UIID'])) continue;
						$resTurnirWorlds2[] = $vT;
					}
				}
			}
		}
		/***** Конец затыки ******/

		if ( sizeof($res)==0  && count(@$resTurnirWorlds2)==0)
		{
			if ( $str != "" && $block == 2 )
			{
				// template 5
				$tpl->loadTemplatefile ( "index_start5.tpl", true, true );
				$tpl->setVariable ( "TEXT1", $str ) ;
				$tpl->show ();
			}

			// template 6
			if(
(@$GLOBALS['USER_HAS_UIID'] && constant('ONE_WORLD_REGISTRATION_ONLY') && !$isAdmin)
||
(!@$GLOBALS['USER_HAS_UIID'] && constant('ONE_WORLD_REGISTRATION_ONLY') && !$isAdmin && $GLOBALS['USER_HAS_UIIDS'])
)
			{
				include_once ( "./language/".$language."/index_start6-2.lang" );
			}else{
				include_once ( "./language/".$language."/index_start6-1.lang" );
			}

			if(!@$lang)
			{
				$this->errormsg = "SystemError - Start::showStartGame8";
				return false;
			}

			$tpl->loadTemplatefile ( "index_start6.tpl", true, true );

			foreach($lang as $key=>$val)
			{
				$tpl->setVariable ( $key, $lang[$key] ) ;
			}

			$tpl->show ();
			unset($lang);
		} else {
			if($str != "" && $block == 2)
			{
				// template 7
				$tpl->loadTemplatefile ( "index_start7.tpl", true, true );
				$tpl->setVariable ( "TEXT1", $str ) ;
				$tpl->show ();
			}

			// template 8
			include_once ( "./language/".$language."/index_start8.lang" );
			if ( !@$lang )
			{
				$this->errormsg = "SystemError - Start::showStartGame9";
				return false;
			}

			$tpl->loadTemplatefile ( "index_start8.tpl", true, true );
			foreach($lang as $key=>$val)
			{
				$tpl->setVariable($key, $lang[$key]) ;
			}

			if(isset($resTurnirWorlds2))
			foreach ( $resTurnirWorlds2 as $kT => $vT) {

				$tpl->setCurrentBlock ( "BLOCK1" );
				$tpl->setVariable ( "WORLD_VALUE", $vT['id_world'].'_'.$common->getDbIdByName($vT['dbname']) ) ;
				$tpl->setVariable ( "WORLD_NAME", $vT['worldname'] ) ;
				if ( $vT['id_world'] == @$_SESSION["world"] ) $tpl->setVariable ( "SELECTED", "selected" );
				$tpl->parseCurrentBlock ( "BLOCK1" );
			}

			foreach ( $res as $k => $v ) {
				$tpl->setCurrentBlock ( "BLOCK1" );
				$tpl->setVariable ( "WORLD_VALUE", $v['id_world'].'_'.$common->getDbIdByName($v['dbname']) ) ;
				$tpl->setVariable ( "WORLD_NAME", $v['worldname'] ) ;
				if ( $v['id_world'] == @$_SESSION["world"] ) $tpl->setVariable ( "SELECTED", "selected" );
				$tpl->parseCurrentBlock ( "BLOCK1" );
			}

			$common->setDbId(0);
			$sql = sprintf ( "
					select
						r.ID_Race,
						ltl.text as racename
					from
						DS_Race r,
						DS_LocalizedText l,
						DS_LocalizedTextLanguage ltl
					where
							r.id_localizedname = l.id_localizedtext
						and l.id_localizedtext = ltl.id_localizedtext
						and ltl.id_language = %d
					order by id_race", $_SESSION['lang'] );
			$res = $common->getResultArray ( "all_races_lang".$_SESSION['lang'], $sql, "", "", "GLOBAL_CACHE" );

			if ( $res === false ) {
				$this->errormsg = "SystemError - Start::showStartGame10";
				return false;
			}


			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

				$tpl->setCurrentBlock ( "BLOCK2" );
				$tpl->setVariable ( "RACE_VALUE", $res[$i]['id_race'] ) ;
				$tpl->setVariable ( "RACE_NAME", $res[$i]['racename'] ) ;
				if ( $res[$i]['id_race'] == @$_SESSION["race"] ) $tpl->setVariable ( "SELECTED", "selected" );
				$tpl->parseCurrentBlock ( "BLOCK2" );
			}

			if ( isset ( $_SESSION["comb"] ) ) $tpl->setVariable ( "COMB", $_SESSION["comb"] );

			$tpl->setVariable ( "UID", $uid );

			$tpl->show ();
			unset ( $lang );
		}

		// template 11

		include_once ( "./language/".$language."/index_start11.lang" );

		if ( !@$lang ) {
			$this->errormsg = "SystemError - Start::showStartGame11";
			return false;
		}

		$tpl->loadTemplatefile ( "index_start11.tpl", true, true );

		$tpl->setVariable ( "TEXT1", $lang['TEXT1'] ) ;

		$tpl->show ();

		$sql = "	SELECT
						 w.id_world,
						 w.worldname,
						 w.description,
						 w.profi,
						 w.max_users as userсount,
						 w.id_worldtype,
						 w.dbname
					 FROM
						 ds_worldcollection as w
					 WHERE
						 w.id_language = ".$_SESSION['lang']."
					ORDER BY w.profi, w.id_worldtype, w.worldname";

		$res = $common->getResultArray ( "WORLDS_NEW_LIST_".$fullprofi, $sql, "", "", "GLOBAL_CACHE" );

		if ( sizeof ( $res ) == 0 ) {

			// template 12

			include_once ( "./language/".$language."/index_start12.lang" );

			if ( !@$lang ) {
				$this->errormsg = "SystemError - Start::showStartGame12";
				return false;
			}

			$tpl->loadTemplatefile ( "index_start12.tpl", true, true );

			$tpl->setVariable ( "TEXT1", $lang['TEXT1'] ) ;

			$tpl->show ();
		}


		$tpl->loadTemplatefile ( "index_start13.tpl", true, true );

		for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			if ( $res[$i]['profi'] == 'f' ||
			($res[$i]['profi'] != 'f' && $fullprofi == 1 ) ) {

				$color = 'ind_st1';

			} else {

				$color = 'ind_st2';

			}

			// template13
			if($res[$i]['userсount'] < 0){
				$res[$i]['userсount'] = 0;
			}

			@$freePlaces = $res[$i]['value'] - $res[$i]['usercount'];
			$sqlType = "
					SELECT
						ltl.text
					FROM
						ds_worldtype wt,
						ds_localizedtextlanguage ltl
					WHERE
						wt.id_worldtype = ".$res[$i]['id_worldtype']." AND
						wt.id_localizedtype = ltl.id_localizedtext AND
						ltl.id_language = ".$_SESSION['lang'];

			$resType = $common->getResultArray ( "get_world_type_new_locolized_".$res[$i]['id_worldtype'], $sqlType, "", "", "GLOBAL_CACHE" );

			$sqlOver = "
					SELECT
						ltl.text
					FROM
						ds_worldtype wt,
						ds_localizedtextlanguage ltl
					WHERE
						wt.id_worldtype = ".$res[$i]['id_worldtype']." AND
						wt.id_localizeddescription = ltl.id_localizedtext AND
						ltl.id_language = ".$_SESSION['lang'];

			$resOver = $common->getResultArray ( "get_world_over_new_locolized_".$res[$i]['id_worldtype'], $sqlOver, "", "", "GLOBAL_CACHE" );

			$tpl->setCurrentBlock ( "BLOCK1" );
			$tpl->setVariable ( "COLOR", $color );
			$tpl->setVariable ( "WORLDPIC", $res[$i]['dbname']."_".$res[$i]['id_world'] );
			$tpl->setVariable ( "WORLDNAME", $res[$i]['worldname'] ) ;

			$tpl->setVariable ( "WORLDTYPE", @$resType[0]['text'] ) ;
			$tpl->setVariable ( "WORLDTYPE_OVER", @$resOver[0]['text'] ) ;

			$tpl->setVariable ( "PLACES", $res[$i]['userсount'] ) ;
			$tpl->setVariable ( "DESCRIPTION", $res[$i]['description'] ) ;
			$tpl->parseCurrentBlock ( "BLOCK1" );
		}

		$tpl->show ();

		// profi account enter template 10

		include_once ( "./language/".$language."/index_start10.lang" );

		if ( !@$lang ) {
			$this->errormsg = "SystemError - Start::showStartGame13";
			return false;
		}

		$tpl->loadTemplatefile ( "index_start10.tpl", true, true );

		foreach ( $lang as $key=>$val ) {

			$tpl->setVariable ( $key, $lang[$key] ) ;
		}

		$tpl->setVariable ( "ID_USER", $_SESSION['uid'] );
		$tpl->setVariable ( "LANGUAGE", $language );

		if ( sizeof ( $this->errorname ) > 0 ) {

			$sql = sprintf ( "select text
					from ds_localizedtextlanguage ltl,
					ds_systemmessage sm
					where sm.id_localizedtext = ltl.id_localizedtext
					and ltl.id_language = %d
					and sm.messagename = '%s'",
			$_SESSION['lang'],
			$this->errorname[0] );

			$res = $common->getResultArray ( "errormsg_id_language_messagename_".$_SESSION['lang'].$this->errorname[0]."_".$this->errorname[0], $sql, "", "", "GLOBAL_CACHE" );
			if ( $res === false ) {
				$this->errormsg = "SystemError - Start::showStartGame14";
				return false;
			}

			$tpl->setVariable ( "ERROR", $res[0]['text']."<br><br>" );
		}

		if ( isset ( $_SESSION['profi_game'] ) && !isset ( $_SESSION['after_reg_trial'] ) ) {

			$common->setDbId(0);
			$sql = sprintf ( "select date_part ( 'day', enddate - now()) as day
					from ds_userprofidata
					where id_user = %d",
			$uid );

			$res = $common->getResultArray ( "valid_days".$uid, $sql, "", "", "NO_CACHE" );
			if ( $res === false ) {
				$this->errormsg = "SystemError - Start::showStartGame15";
				return false;
			}

			$valid_days = (int)number_format ( @$res[0]['day'], 0, ".", " " );

			if ( $valid_days < 0 ) $valid_days = 0;

			if ( $valid_days <= 1 ) $color = "Orange"; else $color = "Lime";

			$tpl->setVariable ( "TEXT4", $lang["TEXT4_"] );
			$tpl->setVariable ( "DAY", $lang["DAY_"] );

			$tpl->setVariable ( "VALID_DAYS", $valid_days );
			$tpl->setVariable ( "COLOR", $color );

			$link = $lang['LINK1'];
			$text =$lang['TEXT3_1'];

		} else {

			$link = $lang['LINK2'];
			$text = $lang['PREMIUM_BANER'];
		}

		$tpl->setVariable ( "TEXT3", $text );
		$tpl->setVariable ( "HREF", $link );

		$tpl->show ();

		unset ( $lang );

		// template 9

		include_once ( "./language/".$language."/index_start9.lang" );

		if ( !@$lang ) {
			$this->errormsg = "SystemError - Start::showStartGame15";
			return false;
		}

		$tpl->loadTemplatefile ( "index_start9.tpl", true, true );

		foreach ( $lang as $key=>$val ) {

			$tpl->setVariable ( $key, $lang[$key] ) ;
		}

		$tpl->show ();

		unset ( $lang );

		return true;
	}

	function isUserNeedPresent($uid)
	{
		global $common;

		if(!isset($uid))
			return false;

		$sql = sprintf("SELECT
      							*
						FROM
    						ds_user u
						WHERE
     						u.id_user = %d
     						AND id_user NOT IN
     						(
      						 SELECT
            						id_user
      							FROM
            						ds_present
     						)
     						AND id_user NOT IN
     						(
      						 SELECT
            						id_user
      							FROM
          							ds_userprofidata
      							WHERE
           							enddate > now()
     						)",
						$uid);
		$res = $common->getResultArray("",$sql,"","","");
		if(isset($res) && sizeof($res) > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}


?>