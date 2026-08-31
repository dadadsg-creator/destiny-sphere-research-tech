<?


require_once "include/SessionContinue.inc.php";
if ( !@sessionContinue () )
{
	exit;
}

if ( !@$_SESSION['uid'] ) exit;


require_once ( 'Main.php' );
require_once ( "IT.php" );


//Проверям не закрыта ли игра
require_once('include/GameDown.inc.php');
if (GameDown($_SESSION['dbId'], $_SESSION['world']))
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit();
}






if ( !isset ( $GLOBALS["common"] ) ) {

	$common = & ClassFactory::getInstance ( "DS_Common" );

	if ( !$common->setDbId(0) ) {

		echo "DB-connection failed";

		exit;

	}

}







if ( !isset ( $_SESSION['lang'] ) ) $_SESSION['lang'] = DEFAULT_LANGUAGE;



if ( !isset ( $_SESSION["language"] ) ) {

			

	$sql = sprintf ("select shortname

			from ds_language

			where id_language = %d",

			DEFAULT_LANGUAGE );



	$res = $common->getResultArray ( "language", $sql, "", "", "NO_CACHE" );



	if ( $res === false ) {

		$this->errormsg = "SystemError - showFirstPage1";

		

		return false;

	}

			

	$language = $res[0]['shortname'];

			

} else {

			

	$language = $_SESSION['language'];

}



class Questions {



	var $errormsg;

	var $errorname;



	function Questions() {



		$this->errorname = array ();



		if (!$this->getPostData($_POST)) {

			echo $this->errormsg;

			exit;

		}

	}



	function getPostData($data) {

		

		global $common;



		if ( sizeof ( $data ) == 0 ) {



			if ( !$this->showLoginForm() ) return false;



		} else {



			if ( @$_POST['age'] ) {

				

				$_SESSION['AGE_CHECKED'] = $_POST['age'];

			

			} else {

				

				$this->errorname[] = "NO_AGE";

				unset ( $_SESSION['AGE_CHECKED'] );

			}



			if ( @$_POST['gender'] ) {

				

				$_SESSION['GENDER_CHECKED'] = $_POST['gender'];

			

			} else {

				

				$this->errorname[] = "NO_GENDER";

				unset ( $_SESSION['GENDER_CHECKED'] );

			}

			

			if ( @$_POST['busy'] ) {

				

				$_SESSION['BUSY_CHECKED'] = $_POST['busy'];

			

			} else {

				

				$this->errorname[] = "NO_BUSY";

				unset ( $_SESSION['BUSY_CHECKED'] );

			}



			if ( @$_POST['profession'] ) {



				$_SESSION['PROFESSION_SELECTED'] = $_POST['profession'];



			} else {



				$this->errorname[] = "NO_PROFESSION";

				unset ( $_SESSION['PROFESSION_SELECTED'] );

			}



			if ( @$_POST['income'] ) {



				$_SESSION['INCOME_SELECTED'] = $_POST['income'];



			} else {



				$this->errorname[] = "NO_INCOME";

				unset ( $_SESSION['INCOME_SELECTED'] );

			}



			if ( @$_POST['interest'] ) {



				$_SESSION['INTEREST_CHECKED'] = $_POST['interest'];



			} else {



				$this->errorname[] = "NO_INTEREST";

				unset ( $_SESSION['INTEREST_CHECKED'] );

			}



			if ( @$_POST['family'] ) {



				$_SESSION['FAMILY_CHECKED'] = $_POST['family'];



			} else {



				$this->errorname[] = "NO_FAMILY";

				unset ( $_SESSION['FAMILY_CHECKED'] );

			}



			if ( @$_POST['kids'] ) {



				$_SESSION['KIDS_SELECTED'] = $_POST['kids'];



			} else {



				$this->errorname[] = "NO_KIDS";

				unset ( $_SESSION['KIDS_SELECTED'] );

			}

			

			if ( @$_POST['place'] ) {



				$_SESSION['PLACE_SELECTED'] = $_POST['place'];



			} else {



				$this->errorname[] = "NO_PLACE";

				unset ( $_SESSION['PLACE_SELECTED'] );

			}

			

			if ( @$_POST['online'] ) {



				$_SESSION['ONLINE_CHECKED'] = $_POST['online'];



			} else {



				$this->errorname[] = "NO_ONLINE";

				unset ( $_SESSION['ONLINE_CHECKED'] );

			}

			

			if ( @$_POST['hours'] ) {



				$_SESSION['HOURS_CHECKED'] = $_POST['hours'];



			} else {



				$this->errorname[] = "NO_HOURS";

				unset ( $_SESSION['HOURS_CHECKED'] );

			}



			if ( @$_POST['internet_where'] ) {



				$_SESSION['INTERNET_WHERE'] = $_POST['internet_where'];



			} else {



				$this->errorname[] = "NO_INTERNET_WHERE";

				unset ( $_SESSION['INTERNET_WHERE'] );

			}



			if ( @$_POST['avto'] ) {



				$_SESSION['AVTO'] = $_POST['avto'];



			} else {



				$this->errorname[] = "NO_AVTO";

				unset ( $_SESSION['AVTO'] );

			}



			if ( @$_POST['prof_educ'] ) {



				$_SESSION['PROF_EDUC'] = $_POST['prof_educ'];



			} else {



				$this->errorname[] = "NO_PROF_EDUC";

				unset ( $_SESSION['PROF_EDUC_CHECKED'] );

			}



			if ( @$_POST['worker_amount'] ) {



				$_SESSION['WORKER_AMOUNT'] = $_POST['worker_amount'];



			} else {



				$this->errorname[] = "NO_WORKER_AMOUNT";

				unset ( $_SESSION['WORKER_AMOUNT'] );

			}



			if ( @$_POST['ds_how_often'] ) {



				$_SESSION['DS_HOW_OFTEN'] = $_POST['ds_how_often'];



			} else {



				$this->errorname[] = "NO_DS_HOW_OFTEN";

				unset ( $_SESSION['DS_HOW_OFTEN'] );

			}



			if ( @$_POST['buy_own'] ) {



				$_SESSION['BUY_OWN'] = $_POST['buy_own'];



			} else {



				$this->errorname[] = "NO_BUY_OWN";

				unset ( $_SESSION['BUY_OWN'] );

			}



			if ( @$_POST['buy_price_quality'] ) {



				$_SESSION['BUY_PRICE_QUALITY'] = $_POST['buy_price_quality'];



			} else {



				$this->errorname[] = "NO_BUY_PRICE_QUALITY";

				unset ( $_SESSION['BUY_PRICE_QUALITY'] );

			}



			if ( sizeof ( $this->errorname ) == 0 ) {

				

				$sql = sprintf ( "select id_user

						from ds_form

						where id_user = %d",

						$_SESSION['uid'] );

						

				$res = $common->getResultArray ( "is_tested", $sql, "", "", "" );

				if ( $res === false ) {

					$this->errormsg = "SYSTEM ERROR Questions::getPostData1";

					return false;

				}

				

				if ( sizeof ( $res ) == 0 ) {



					$common->executeQuery ( "begin" );



					$sql = sprintf ( "insert into ds_form ( id_user, id_age, id_gender, id_busy, id_profession, 

					id_income, id_family, id_kids, id_place, id_online, id_hours, id_avto,

					id_prof_educ, id_worker_amount, id_ds_how_often ) values

					( %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d )",

					$_SESSION['uid'], $_POST['age'], $_POST['gender'], $_POST['busy'], $_POST['profession'], $_POST['income'], $_POST['family'],

					$_POST['kids'], $_POST['place'], $_POST['online'], $_POST['hours'],

					$_POST['avto'], $_POST['prof_educ'], $_POST['worker_amount'],

					$_POST['ds_how_often'] );

				

					$res = $common->executeQuery ( $sql );

				

					if ( $res === false ) {

						$this->errormsg = "SYSTEM ERROR Questions::getPostData2";

						return false;

					}



					$interest = $_POST['interest'];



					if ( is_array($interest) ) {



						foreach ( $interest as $key=>$val) {



							$sql = sprintf ( "insert into ds_form_userhasinterest ( id_user, id_interest )

									values ( %d, %d )",

									$_SESSION['uid'],

									$val );

				

							$res = $common->executeQuery ( $sql );



							if ( $res === false ) {

								$this->errormsg = "SYSTEM ERROR Questions::getPostData3";

								return false;

							}

							

							//echo $sql."<br>";

						}

					}

					

					$internet_where = $_POST['internet_where'];



					if ( is_array($internet_where) ) {



						foreach ( $internet_where as $key=>$val) {



							$sql = sprintf ( "insert into ds_form_userhasinternetwhere ( id_user, id_internetwhere )

									values ( %d, %d )",

									$_SESSION['uid'],

									$val );



							$res = $common->executeQuery ( $sql );



							if ( $res === false ) {



								$this->errormsg = "SYSTEM ERROR Questions::getPostData4";

								return false;

							}

							

							//echo $sql."<br>";

						}

					}

					

					$buy_own = $_POST['buy_own'];



					if ( is_array($buy_own) ) {



						foreach ( $buy_own as $id=>$id_var) {



							$sql = sprintf ( "insert into ds_form_userhas_buy_own ( id_user, id_buy_own, id_buy_own_variant)

									values ( %d, %d, %d )",

									$_SESSION['uid'],

									$id,

									$id_var );



							$res = $common->executeQuery ( $sql );



							if ( $res === false ) {

								$this->errormsg = "SYSTEM ERROR Questions::getPostData5 ".$sql;

								return false;

							}

							

							//echo $sql."<br>";

						}

					}

					

					$buy_price_quality = $_POST['buy_price_quality'];



					if ( is_array($buy_price_quality) ) {



						foreach ( $buy_price_quality as $id=>$id_var) {



							$sql = sprintf ( "insert into ds_form_userhas_buy_price_quality ( id_user, id_buy_own, id_buy_price_quality_variant)

									values ( %d, %d, %d )",

									$_SESSION['uid'],

									$id,

									$id_var );



							$res = $common->executeQuery ( $sql );



							if ( $res === false ) {

								$this->errormsg = "SYSTEM ERROR Questions::getPostData6 ".$sql;

								return false;

							}

							

							//echo $sql."<br>";

						}

					}

					

					$common->executeQuery ( "commit" );

				}







				$_SESSION['finished'] = 1;



				header ("Location: questions.php");



			} else {



				if ( !$this->showLoginForm ( $this->errorname ) ) {

					return false;

				}

			}

		}



		return true;

	}



	function showLoginForm ( $errorname = array() ) {



		global $common;

		global $language;

		

		include_once ( "./language/".$language."/questions.lang" );



		if ( !@$lang ) {

			$this->errormsg = "Login::showLoginForm1";

			return false;

		}



		$tpl = new HTML_Template_IT ("./templates");





		if ( !@$_SESSION['finished'] ) {



			$tpl->loadTemplatefile ( "questions.tpl", true, true ); 



			foreach ( $lang as $key=>$val ) {



				$tpl->setVariable ( $key, $lang[$key] ) ;

			}

		

			for ( $i=0; $i<sizeof ( $errorname ); $i++ ) {



				$tpl->setCurrentBlock ( "ERROR" );

				$tpl->setVariable ( "ERROR", $lang[$errorname[$i]] );

				$tpl->parseCurrentBlock ( "ERROR" );

				//$tpl->setVariable ( "ANY_ERROR", $lang["ERROR"] ) ;

			}



			$sql = sprintf ( "select age.id_age,

					ltl.text as localizedage

					from ds_form_age age,

					ds_localizedtextlanguage ltl

					where age.id_localizedage = ltl.id_localizedtext

					and ltl.id_language = %d

					order by age.id_age",

					$_SESSION['lang'] );

				

			$res = $common->getResultArray ( "age", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm2";

				return false;

			}

		

			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "AGE" );

			

				$tpl->setVariable ( "AGE_VALUE", $res[$i]['id_age'] );

				$tpl->setVariable ( "AGE_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['AGE_CHECKED'] == $res[$i]['id_age'] ) {

					$tpl->setVariable ( "AGE_CHECKED", "checked" );

				}



				$tpl->parseCurrentBlock ( "AGE" );

			}



			$sql = sprintf ( "select g.id_gender,

					ltl.text as localizedage

					from ds_form_gender g,

					ds_localizedtextlanguage ltl

					where g.id_localizedgender = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_gender",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "gender", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm3";

				return false;

			}

		

			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "GENDER" );



				$tpl->setVariable ( "GENDER_VALUE", $res[$i]['id_gender'] );

				$tpl->setVariable ( "GENDER_TEXT", $res[$i]['localizedage'] );

			

				if ( @$_SESSION['GENDER_CHECKED'] == $res[$i]['id_gender'] ) {

					$tpl->setVariable ( "GENDER_CHECKED", "checked" );

				}



				$tpl->parseCurrentBlock ( "GENDER" );

			}



			$sql = sprintf ( "select g.id_busy,

					ltl.text as localizedage

					from ds_form_busy g,

					ds_localizedtextlanguage ltl

					where g.id_localizedbusy = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_busy",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "busy", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm4";

				return false;

			}

		

			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "BUSY" );

			

				$tpl->setVariable ( "BUSY_VALUE", $res[$i]['id_busy'] );

				$tpl->setVariable ( "BUSY_TEXT", $res[$i]['localizedage'] );

			

				if ( @$_SESSION['BUSY_CHECKED'] == $res[$i]['id_busy'] ) {

					$tpl->setVariable ( "BUSY_CHECKED", "checked" );

				}



				$tpl->parseCurrentBlock ( "BUSY" );

			}



			$sql = sprintf ( "select g.id_profession,

					ltl.text as localizedage

					from ds_form_profession g,

					ds_localizedtextlanguage ltl

					where g.id_localizedprofession = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_profession",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "profession", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm5";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "PROFESSION" );

			

				$tpl->setVariable ( "PROFESSION_VALUE", $res[$i]['id_profession'] );

				$tpl->setVariable ( "PROFESSION_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['PROFESSION_SELECTED'] == $res[$i]['id_profession'] ) {

					$tpl->setVariable ( "PROFESSION_SELECTED", "selected" );

				}



				$tpl->parseCurrentBlock ( "PROFESSION" );

			}



			$sql = sprintf ( "select g.id_income,

					ltl.text as localizedage

					from ds_form_income g,

					ds_localizedtextlanguage ltl

					where g.id_localizedincome = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_income",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "income", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm6";

				return false;

			}

		

			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "INCOME" );

			

				$tpl->setVariable ( "INCOME_VALUE", $res[$i]['id_income'] );

				$tpl->setVariable ( "INCOME_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['INCOME_SELECTED'] == $res[$i]['id_income'] ) {

					$tpl->setVariable ( "INCOME_SELECTED", "selected" );

				}



				$tpl->parseCurrentBlock ( "INCOME" );

			}

		

			$sql = sprintf ( "select g.id_interest,

					ltl.text as localizedage

					from ds_form_interest g,

					ds_localizedtextlanguage ltl

					where g.id_localizedinterest = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_interest",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "interest", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm7";

				return false;

			}

		

			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "INTEREST" );

			

				$tpl->setVariable ( "INTEREST_VALUE", $res[$i]['id_interest'] );

				$tpl->setVariable ( "INTEREST_TEXT", $res[$i]['localizedage'] );

				$tpl->setVariable ( "INT", $i );



				$interest = @$_SESSION['INTEREST_CHECKED'];



				if ( is_array($interest) ) {

							

					if ( in_array( $res[$i]['id_interest'], $interest ) ) {

						$tpl->setVariable ( "INTEREST_CHECKED", "checked" );

					}

				}

			

				$tpl->parseCurrentBlock ( "INTEREST" );

			}



			$sql = sprintf ( "select g.id_family,

					ltl.text as localizedage

					from ds_form_family g,

					ds_localizedtextlanguage ltl

					where g.id_localizedfamily = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_family",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "family", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm8";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "FAMILY" );

			

				$tpl->setVariable ( "FAMILY_VALUE", $res[$i]['id_family'] );

				$tpl->setVariable ( "FAMILY_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['FAMILY_CHECKED'] == $res[$i]['id_family'] ) {

					$tpl->setVariable ( "FAMILY_CHECKED", "checked" );

				}



				$tpl->parseCurrentBlock ( "FAMILY" );

			}



			$sql = sprintf ( "select g.id_kids,

					ltl.text as localizedage

					from ds_form_kids g,

					ds_localizedtextlanguage ltl

					where g.id_localizedkids = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_kids",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "kids", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm9";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "KIDS" );

			

				$tpl->setVariable ( "KIDS_VALUE", $res[$i]['id_kids'] );

				$tpl->setVariable ( "KIDS_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['KIDS_SELECTED'] == $res[$i]['id_kids'] ) {

					$tpl->setVariable ( "KIDS_SELECTED", "selected" );

				}



				$tpl->parseCurrentBlock ( "KIDS" );

			}



			$sql = sprintf ( "select g.id_place,

					ltl.text as localizedage

					from ds_form_place g,

					ds_localizedtextlanguage ltl

					where g.id_localizedplace = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_place",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "place", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm10";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {



				$tpl->setCurrentBlock ( "PLACE" );



				$tpl->setVariable ( "PLACE_VALUE", $res[$i]['id_place'] );

				$tpl->setVariable ( "PLACE_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['PLACE_SELECTED'] == $res[$i]['id_place'] ) {

					$tpl->setVariable ( "PLACE_SELECTED", "selected" );

				}



				$tpl->parseCurrentBlock ( "PLACE" );

			}



			$sql = sprintf ( "select g.id_online,

					ltl.text as localizedage

					from ds_form_online g,

					ds_localizedtextlanguage ltl

					where g.id_localizedonline = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_online",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "online", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm11";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "ONLINE" );

			

				$tpl->setVariable ( "ONLINE_VALUE", $res[$i]['id_online'] );

				$tpl->setVariable ( "ONLINE_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['ONLINE_CHECKED'] == $res[$i]['id_online'] ) {

					$tpl->setVariable ( "ONLINE_CHECKED", "checked" );

				}



				$tpl->parseCurrentBlock ( "ONLINE" );

			}



			$sql = sprintf ( "select g.id_hours,

					ltl.text as localizedage

					from ds_form_hours g,

					ds_localizedtextlanguage ltl

					where g.id_localizedhours = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_hours",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "hours", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm12";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "HOURS" );

			

				$tpl->setVariable ( "HOURS_VALUE", $res[$i]['id_hours'] );

				$tpl->setVariable ( "HOURS_TEXT", $res[$i]['localizedage'] );

	

				if ( @$_SESSION['HOURS_CHECKED'] == $res[$i]['id_hours'] ) {

					$tpl->setVariable ( "HOURS_CHECKED", "checked" );

				}

			

				$tpl->parseCurrentBlock ( "HOURS" );

			}



			$sql = sprintf ( "select g.id_internetwhere,

					ltl.text as localizedage

					from ds_form_internetwhere g,

					ds_localizedtextlanguage ltl

					where g.id_localizedinternetwhere = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_internetwhere",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "internetwhere", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm13";

				return false;

			}



			$internet_where = @$_SESSION['INTERNET_WHERE'];

			

			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "INTERNET_WHERE" );

			

				$tpl->setVariable ( "INTERNET_WHERE_VALUE", $res[$i]['id_internetwhere'] );

				$tpl->setVariable ( "INTERNET_WHERE_TEXT", $res[$i]['localizedage'] );

				$tpl->setVariable ( "INT", $i );



				if ( is_array($internet_where) ) {

							

					if ( in_array( $res[$i]['id_internetwhere'], $internet_where ) ) {

						$tpl->setVariable ( "INTERNET_WHERE_CHECKED", "checked" );

					}

				}

			

				$tpl->parseCurrentBlock ( "INTERNET_WHERE" );

			}



			$sql = sprintf ( "select g.id_avto,

					ltl.text as localizedage

					from ds_form_avto g,

					ds_localizedtextlanguage ltl

					where g.id_localizedavto = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_avto",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "avto", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm14";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "AVTO" );

			

				$tpl->setVariable ( "AVTO_VALUE", $res[$i]['id_avto'] );

				$tpl->setVariable ( "AVTO_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['AVTO'] == $res[$i]['id_avto'] ) {

					$tpl->setVariable ( "AVTO_CHECKED", "checked" );

				}



				$tpl->parseCurrentBlock ( "AVTO" );

			}



			$sql = sprintf ( "select g.id_profeduc,

					ltl.text as localizedage

					from ds_form_profeduc g,

					ds_localizedtextlanguage ltl

					where g.id_localizedprofeduc = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_profeduc",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "profeduc", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm15";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {



				$tpl->setCurrentBlock ( "PROF_EDUC" );



				$tpl->setVariable ( "PROF_EDUC_VALUE", $res[$i]['id_profeduc'] );

				$tpl->setVariable ( "PROF_EDUC_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['PROF_EDUC'] == $res[$i]['id_profeduc'] ) {

					$tpl->setVariable ( "PROF_EDUC_CHECKED", "checked" );

				}



				$tpl->parseCurrentBlock ( "PROF_EDUC" );

			}



			$sql = sprintf ( "select g.id_worker_amount,

					ltl.text as localizedage

					from ds_form_worker_amount g,

					ds_localizedtextlanguage ltl

					where g.id_localizedworker_amount = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_worker_amount",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "worker_amount", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm16";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			

				$tpl->setCurrentBlock ( "WORKER_AMOUNT" );

			

				$tpl->setVariable ( "WORKER_AMOUNT_VALUE", $res[$i]['id_worker_amount'] );

				$tpl->setVariable ( "WORKER_AMOUNT_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['WORKER_AMOUNT'] == $res[$i]['id_worker_amount'] ) {

					$tpl->setVariable ( "WORKER_AMOUNT_CHECKED", "checked" );

				}



				$tpl->parseCurrentBlock ( "WORKER_AMOUNT" );

			}



			$sql = sprintf ( "select g.id_ds_how_often,

					ltl.text as localizedage

					from ds_form_ds_how_often g,

					ds_localizedtextlanguage ltl

					where g.id_localizedds_how_often = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_ds_how_often",

					$_SESSION['lang'] );



			$res = $common->getResultArray ( "ds_how_often", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm17";

				return false;

			}



			for ( $i=0; $i<sizeof ( $res ); $i++ ) {



				$tpl->setCurrentBlock ( "DS_HOW_OFTEN" );

			

				$tpl->setVariable ( "DS_HOW_OFTEN_VALUE", $res[$i]['id_ds_how_often'] );

				$tpl->setVariable ( "DS_HOW_OFTEN_TEXT", $res[$i]['localizedage'] );



				if ( @$_SESSION['DS_HOW_OFTEN'] == $res[$i]['id_ds_how_often'] ) {

					$tpl->setVariable ( "DS_HOW_OFTEN_CHECKED", "checked" );

				}



				$tpl->parseCurrentBlock ( "DS_HOW_OFTEN" );

			}



			$sql = sprintf ( "select g.id_buy_own,

					ltl.text as localizedage

					from ds_form_buy_own g,

					ds_localizedtextlanguage ltl

					where g.id_localizedbuy_own = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_buy_own",

					$_SESSION['lang'] );



			$buy_res = $common->getResultArray ( "buy_own", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$buy_res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm18";

				return false;

			}

			

			$sql = sprintf ( "select g.id_buy_own_variant,

					ltl.text as localizedage

					from ds_form_buy_own_variants g,

					ds_localizedtextlanguage ltl

					where g.id_localizedbuy_own_variant = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_buy_own_variant",

					$_SESSION['lang'] );



			$var_res = $common->getResultArray ( "buy_own_variants", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$var_res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm19";

				return false;

			}	



			for ( $i=0; $i<sizeof ( $var_res ); $i++ ) {



				$tpl->setCurrentBlock ( "BUY_OWN_VARIANTS_NAME" );

				$tpl->setVariable ( "VARIANT_NAME", $var_res[$i]['localizedage'] );

				$tpl->parseCurrentBlock ( "BUY_OWN_VARIANTS_NAME" );

			}



			$buy_own = @$_SESSION['BUY_OWN'];



			for ( $i=0; $i<sizeof ( $buy_res ); $i++ ) {



				for ( $e=0; $e<sizeof ( $var_res ); $e++ ) {



					$tpl->setCurrentBlock ( "BUY_OWN_VARIANTS" );

					$tpl->setVariable ( "BUY_OWN_VALUE", $var_res[$e]['id_buy_own_variant'] );

					$tpl->setVariable ( "INT", $buy_res[$i]['id_buy_own'] );

					

					if ( is_array($buy_own) ) {

						if ( isset ( $buy_own[$i] ) ) {

							if ( $buy_own[$i] == $var_res[$e]['id_buy_own_variant'] ) {

								$tpl->setVariable ( "BUY_OWN_CHECKED", "checked" );

							}

						}

					}

					

					$tpl->parseCurrentBlock ( "BUY_OWN_VARIANTS" );					

				}



				$tpl->setCurrentBlock ( "BUY_OWN" );

				$tpl->setVariable ( "BUY_OWN_NAME", $buy_res[$i]['localizedage'] );				

				$tpl->parseCurrentBlock ( "BUY_OWN" );

			}





			$sql = sprintf ( "select g.id_buy_price_quality_variant,

					ltl.text as localizedage

					from ds_form_buy_price_quality_variants g,

					ds_localizedtextlanguage ltl

					where g.id_localizedvariant = ltl.id_localizedtext

					and ltl.id_language = %d

					order by g.id_buy_price_quality_variant",

					$_SESSION['lang'] );



			$var_res = $common->getResultArray ( "buy_price_quality_variants", $sql, "", "", "GLOBAL_CACHE" );

			if ( !$var_res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm20";

				return false;

			}



			for ( $i=0; $i<sizeof ( $var_res ); $i++ ) {



				$tpl->setCurrentBlock ( "BUY_PRICE_QUALITY_VARIANTS_NAME" );

				$tpl->setVariable ( "VARIANT_NAME", $var_res[$i]['localizedage'] );

				$tpl->parseCurrentBlock ( "BUY_PRICE_QUALITY_VARIANTS_NAME" );

			}



			$buy_price_quality = @$_SESSION['BUY_PRICE_QUALITY'];



			for ( $i=0; $i<sizeof ( $buy_res ); $i++ ) {



				for ( $e=0; $e<sizeof ( $var_res ); $e++ ) {



					$tpl->setCurrentBlock ( "BUY_PRICE_QUALITY_VARIANTS" );

					$tpl->setVariable ( "BUY_PRICE_QUALITY_VALUE", $var_res[$e]['id_buy_price_quality_variant'] );

					$tpl->setVariable ( "INT", $buy_res[$i]['id_buy_own'] );



					if ( is_array($buy_price_quality) ) {

						if ( isset ( $buy_price_quality[$i] ) ) {

							if ( $buy_price_quality[$i] == $var_res[$e]['id_buy_price_quality_variant'] ) {

								$tpl->setVariable ( "BUY_PRICE_QUALITY_CHECKED", "checked" );

							}

						}

					}



					$tpl->parseCurrentBlock ( "BUY_PRICE_QUALITY_VARIANTS" );

				}



				$tpl->setCurrentBlock ( "BUY_PRICE_QUALITY" );

				$tpl->setVariable ( "BUY_PRICE_QUALITY_NAME", $buy_res[$i]['localizedage'] );

				$tpl->parseCurrentBlock ( "BUY_PRICE_QUALITY" );

			}



		} else {

			

			$tpl->loadTemplatefile ( "questions2.tpl", true, true );



			$tpl->setVariable ( "FINISHED_TEXT", $lang['FINISHED_TEXT'] );

		}



		$tpl->show ();

	}

}



$log = new Questions();



?>