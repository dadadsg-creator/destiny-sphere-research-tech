<?


require_once "SessionContinue.php";
if ( !@sessionContinue () ) {

	exit;

}

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

	if ( !$common->connect() ) {

		echo "DB-connection failed";

		exit;

	}

}



$common->setDbId(0);



include_once ( "IT.php" );



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



class QuestionsResults {



	var $errormsg;

	var $errorname;



	function QuestionsResults() {



		$this->errorname = array ();



		if (!$this->getPostData($_POST)) {

			echo $this->errormsg;

			exit;

		}

	}



	function getPostData($data) {

		

		global $common;



		if ( !$this->showLoginForm() ) return false;



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

		

		$tpl->loadTemplatefile ( "questions3.tpl", true, true ); 



		foreach ( $lang as $key=>$val ) {



			$tpl->setVariable ( $key, $lang[$key] ) ;

		}

		

		$sql = sprintf ( "select id_user

				from ds_form" );

		

		$res = $common->getResultArray ( "all_form_users", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm1";

			return false;

		}

			

		$count = sizeof ( $res );



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_age age,

				ds_localizedtextlanguage ltl,

				(select fage.id_age,

				count(f.id_age)

				from ds_form_age fage

				left join ds_form f 

				on f.id_age = fage.id_age

				group by fage.id_age) t

				where age.id_age = t.id_age

				and age.id_localizedage = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );

				

		$res = $common->getResultArray ( "age", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm1";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "AGE" );

		

			$tpl->setVariable ( "AGE_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "AGE_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "AGE_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "AGE" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_gender age,

				ds_localizedtextlanguage ltl,

				(select fage.id_gender,

				count(f.id_gender)

				from ds_form_gender fage

				left join ds_form f 

				on f.id_gender = fage.id_gender

				group by fage.id_gender) t

				where age.id_gender = t.id_gender

				and age.id_localizedgender = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );

				

		$res = $common->getResultArray ( "gemder", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm2";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "GENDER" );

		

			$tpl->setVariable ( "GENDER_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "GENDER_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format ( ($res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "GENDER_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "GENDER" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_busy age,

				ds_localizedtextlanguage ltl,

				(select fage.id_busy,

				count(f.id_busy)

				from ds_form_busy fage

				left join ds_form f 

				on f.id_busy = fage.id_busy

				group by fage.id_busy) t

				where age.id_busy = t.id_busy

				and age.id_localizedbusy = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );

				

		$res = $common->getResultArray ( "busy", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm3";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "BUSY" );

		

			$tpl->setVariable ( "BUSY_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "BUSY_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "BUSY_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "BUSY" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_profession age,

				ds_localizedtextlanguage ltl,

				(select fage.id_profession,

				count(f.id_profession)

				from ds_form_profession fage

				left join ds_form f 

				on f.id_profession = fage.id_profession

				group by fage.id_profession) t

				where age.id_profession = t.id_profession

				and age.id_localizedprofession = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );

				

		$res = $common->getResultArray ( "profession", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm4";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "PROFESSION" );

		

			$tpl->setVariable ( "PROFESSION_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "PROFESSION_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, 0, ".", " ") . "%)";



			$tpl->setVariable ( "PROFESSION_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "PROFESSION" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_income age,

				ds_localizedtextlanguage ltl,

				(select fage.id_income,

				count(f.id_income)

				from ds_form_income fage

				left join ds_form f 

				on f.id_income = fage.id_income

				group by fage.id_income) t

				where age.id_income = t.id_income

				and age.id_localizedincome = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "income", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm5";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "INCOME" );

		

			$tpl->setVariable ( "INCOME_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "INCOME_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "INCOME_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "INCOME" );

		}

		

		$sql = sprintf ( "select *

				from ds_form_userhasinterest" );

		

		$res = $common->getResultArray ( "all_form_usershasinterest", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm6";

			return false;

		}



		$count_interest = sizeof ( $res );



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_interest age,

				ds_localizedtextlanguage ltl,

				(select fage.id_interest,

				count(f.id_interest)

				from ds_form_interest fage

				left join ds_form_userhasinterest f 

				on f.id_interest = fage.id_interest

				group by fage.id_interest) t

				where age.id_interest = t.id_interest

				and age.id_localizedinterest = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "interest", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm7";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "INTEREST" );

		

			$tpl->setVariable ( "INTEREST_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "INTEREST_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format( ( $res[$i]['c'] / $count_interest) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "INTEREST_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "INTEREST" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_family age,

				ds_localizedtextlanguage ltl,

				(select fage.id_family,

				count(f.id_family)

				from ds_form_family fage

				left join ds_form f 

				on f.id_family = fage.id_family

				group by fage.id_family) t

				where age.id_family = t.id_family

				and age.id_localizedfamily = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "family", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm8";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "FAMILY" );

		

			$tpl->setVariable ( "FAMILY_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "FAMILY_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "FAMILY_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "FAMILY" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_kids age,

				ds_localizedtextlanguage ltl,

				(select fage.id_kids,

				count(f.id_kids)

				from ds_form_kids fage

				left join ds_form f 

				on f.id_kids = fage.id_kids

				group by fage.id_kids) t

				where age.id_kids = t.id_kids

				and age.id_localizedkids = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "kids", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm9";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "KIDS" );

		

			$tpl->setVariable ( "KIDS_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "KIDS_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "KIDS_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "KIDS" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_place age,

				ds_localizedtextlanguage ltl,

				(select fage.id_place,

				count(f.id_place)

				from ds_form_place fage

				left join ds_form f 

				on f.id_place = fage.id_place

				group by fage.id_place) t

				where age.id_place = t.id_place

				and age.id_localizedplace = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "place", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm10";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "PLACE" );

		

			$tpl->setVariable ( "PLACE_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "PLACE_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "PLACE_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "PLACE" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_online age,

				ds_localizedtextlanguage ltl,

				(select fage.id_online,

				count(f.id_online)

				from ds_form_online fage

				left join ds_form f 

				on f.id_online = fage.id_online

				group by fage.id_online) t

				where age.id_online = t.id_online

				and age.id_localizedonline = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "online", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm11";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "ONLINE" );

		

			$tpl->setVariable ( "ONLINE_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "ONLINE_TEXT", $res[$i]['localizedname'].":" );



			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "ONLINE_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "ONLINE" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_hours age,

				ds_localizedtextlanguage ltl,

				(select fage.id_hours,

				count(f.id_hours)

				from ds_form_hours fage

				left join ds_form f 

				on f.id_hours = fage.id_hours

				group by fage.id_hours) t

				where age.id_hours = t.id_hours

				and age.id_localizedhours = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "hours", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm12";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "HOURS" );

		

			$tpl->setVariable ( "HOURS_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "HOURS_TEXT", $res[$i]['localizedname'].":" );



			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "HOURS_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "HOURS" );

		}





		$sql = sprintf ( "select *

				from ds_form_userhasinternetwhere" );



		$res = $common->getResultArray ( "all_form_usershasinternetwhere", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm13";

			return false;

		}



		$count_interest = sizeof ( $res );



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_internetwhere age,

				ds_localizedtextlanguage ltl,

				(select fage.id_internetwhere,

				count(f.id_internetwhere)

				from ds_form_internetwhere fage

				left join ds_form_userhasinternetwhere f 

				on f.id_internetwhere = fage.id_internetwhere

				group by fage.id_internetwhere) t

				where age.id_internetwhere = t.id_internetwhere

				and age.id_localizedinternetwhere = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "internetwhere", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm14";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "INTERNET_WHERE" );

		

			$tpl->setVariable ( "INTERNET_WHERE_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "INTERNET_WHERE_TEXT", $res[$i]['localizedname'].":" );

				

			$per = "(".number_format( ( $res[$i]['c'] / $count_interest) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "INTERNET_WHERE_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "INTERNET_WHERE" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_avto age,

				ds_localizedtextlanguage ltl,

				(select fage.id_avto,

				count(f.id_avto)

				from ds_form_avto fage

				left join ds_form f 

				on f.id_avto = fage.id_avto

				group by fage.id_avto) t

				where age.id_avto = t.id_avto

				and age.id_localizedavto = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "avto", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm11";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "AVTO" );

		

			$tpl->setVariable ( "AVTO_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "AVTO_TEXT", $res[$i]['localizedname'].":" );



			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "AVTO_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "AVTO" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_profeduc age,

				ds_localizedtextlanguage ltl,

				(select fage.id_profeduc,

				count(f.id_prof_educ)

				from ds_form_profeduc fage

				left join ds_form f 

				on f.id_prof_educ = fage.id_profeduc

				group by fage.id_profeduc) t

				where age.id_profeduc = t.id_profeduc

				and age.id_localizedprofeduc = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "profeduc", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm12";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "PROF_EDUC" );

		

			$tpl->setVariable ( "PROF_EDUC_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "PROF_EDUC_TEXT", $res[$i]['localizedname'].":" );



			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "PROF_EDUC_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "PROF_EDUC" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_worker_amount age,

				ds_localizedtextlanguage ltl,

				(select fage.id_worker_amount,

				count(f.id_worker_amount)

				from ds_form_worker_amount fage

				left join ds_form f 

				on f.id_worker_amount = fage.id_worker_amount

				group by fage.id_worker_amount) t

				where age.id_worker_amount = t.id_worker_amount

				and age.id_localizedworker_amount = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "worker_amount", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm13";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "WORKER_AMOUNT" );

		

			$tpl->setVariable ( "WORKER_AMOUNT_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "WORKER_AMOUNT_TEXT", $res[$i]['localizedname'].":" );



			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "WORKER_AMOUNT_PERCENT", $per );

				

			$tpl->parseCurrentBlock ( "WORKER_AMOUNT" );

		}



		$sql = sprintf ( "select t.count as c,

				ltl.text as localizedname

				from ds_form_ds_how_often age,

				ds_localizedtextlanguage ltl,

				(select fage.id_ds_how_often,

				count(f.id_ds_how_often)

				from ds_form_ds_how_often fage

				left join ds_form f 

				on f.id_ds_how_often = fage.id_ds_how_often

				group by fage.id_ds_how_often) t

				where age.id_ds_how_often = t.id_ds_how_often

				and age.id_localizedds_how_often = ltl.id_localizedtext

				and ltl.id_language = %d",

				$_SESSION['lang'] );



		$res = $common->getResultArray ( "ds_how_often", $sql, "", "", "" );

		if ( !$res ) {

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm14";

			return false;

		}



		for ( $i=0; $i<sizeof ( $res ); $i++ ) {



			$tpl->setCurrentBlock ( "DS_HOW_OFTEN" );

		

			$tpl->setVariable ( "DS_HOW_OFTEN_COUNT", $res[$i]['c'] );

			$tpl->setVariable ( "DS_HOW_OFTEN_TEXT", $res[$i]['localizedname'].":" );



			$per = "(".number_format( ( $res[$i]['c'] / $count) * 100, 0, ".", " ") . "%)";



			$tpl->setVariable ( "DS_HOW_OFTEN_PERCENT", $per );

				

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



			$sql = sprintf ( "select count(*) as vsego

					from ds_form_userhas_buy_own uhbo

					where uhbo.id_buy_own = %d",

					$buy_res[$i]['id_buy_own'] );

			

			$res = $common->getResultArray ( "vsego", $sql, "", "", "" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm20";

				return false;

			}

			

			$vsego = $res[0]['vsego'];



			for ( $e=0; $e<sizeof ( $var_res ); $e++ ) {



				$sql = sprintf ( "select count(*) as variant

						from ds_form_userhas_buy_own uhbo

						where uhbo.id_buy_own = %d

						and id_buy_own_variant = %d",

						$buy_res[$i]['id_buy_own'],

						$var_res[$e]['id_buy_own_variant'] );



				$res = $common->getResultArray ( "variant", $sql, "", "", "" );

				if ( !$res ) {

					$this->errormsg = "SYSTEM ERROR Questions::showLoginForm21";

					return false;

				}

			

				$variant = $res[0]['variant'];



				$tpl->setCurrentBlock ( "BUY_OWN_VARIANTS" );



				$tpl->setVariable ( "BUY_OWN_COUNT", $variant );

				

				if ( $vsego > 0 ) {

					$per = "(".number_format( ( $variant / $vsego) * 100, 0, ".", " ") . "%)";

				} else {

					$per = "(0%)";

				}



				$tpl->setVariable ( "BUY_OWN_PERCENT", $per );



					

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

			$this->errormsg = "SYSTEM ERROR Questions::showLoginForm22";

			return false;

		}



		for ( $i=0; $i<sizeof ( $var_res ); $i++ ) {



			$tpl->setCurrentBlock ( "BUY_PRICE_QUALITY_VARIANTS_NAME" );

			$tpl->setVariable ( "VARIANT_NAME", $var_res[$i]['localizedage'] );

			$tpl->parseCurrentBlock ( "BUY_PRICE_QUALITY_VARIANTS_NAME" );

		}



		$buy_own = @$_SESSION['BUY_PRICE_QUALITY'];



		for ( $i=0; $i<sizeof ( $buy_res ); $i++ ) {



			$sql = sprintf ( "select count(*) as vsego

					from ds_form_userhas_buy_price_quality uhbo

					where uhbo.id_buy_own = %d",

					$buy_res[$i]['id_buy_own'] );



			$res = $common->getResultArray ( "vsego", $sql, "", "", "" );

			if ( !$res ) {

				$this->errormsg = "SYSTEM ERROR Questions::showLoginForm23";

				return false;

			}



			$vsego = $res[0]['vsego'];



			for ( $e=0; $e<sizeof ( $var_res ); $e++ ) {



				$sql = sprintf ( "select count(*) as variant

						from ds_form_userhas_buy_price_quality uhbo

						where uhbo.id_buy_own = %d

						and uhbo.id_buy_price_quality_variant = %d",

						$buy_res[$i]['id_buy_own'],

						$var_res[$e]['id_buy_price_quality_variant'] );



				$res = $common->getResultArray ( "variant", $sql, "", "", "" );

				if ( !$res ) {

					$this->errormsg = "SYSTEM ERROR Questions::showLoginForm24";

					return false;

				}

			

				$variant = $res[0]['variant'];



				$tpl->setCurrentBlock ( "BUY_PRICE_QUALITY_VARIANTS" );



				$tpl->setVariable ( "BUY_PRICE_QUALITY_COUNT", $variant );

				

				if ( $vsego > 0 ) {

					$per = "(".number_format( ( $variant / $vsego) * 100, 0, ".", " ") . "%)";

				} else {

					$per = "(0%)";

				}



				$tpl->setVariable ( "BUY_PRICE_QUALITY_PERCENT", $per );



					

				$tpl->parseCurrentBlock ( "BUY_PRICE_QUALITY_VARIANTS" );					

			}



			$tpl->setCurrentBlock ( "BUY_PRICE_QUALITY" );

			$tpl->setVariable ( "BUY_PRICE_QUALITY_NAME", $buy_res[$i]['localizedage'] );				

			$tpl->parseCurrentBlock ( "BUY_PRICE_QUALITY" );

		}		



		$tpl->show ();

	}

}



$log = new QuestionsResults();



?>