<?php 

  
if ( !session_start() ) {
	header ( "Location: https://".$_SERVER['HTTP_HOST'] );
	exit;
}
  
 if ( !isset ( $_SESSION['uid'] ) ) {
	header ( "Location: https://".$_SERVER['HTTP_HOST'] );
	exit;
}
  
if ( !isset ( $_POST['code'] ) ) {

	header ( "Location: https://".$_SERVER['HTTP_HOST'] );
	exit;

} else {

	if ( !is_numeric ( $_POST['code'] ) ) {
		header ( "Location: https://".$_SERVER['HTTP_HOST'] );
		exit;
	}

	if ( !isset ( $GLOBALS["common"] ) ) {
		$common = & ClassFactory::getInstance ( "DS_Common" );
		if ( !$common->connect() ) {
			echo "DB-connection failed";
			exit;
		}
	}

	$sql = sprintf ( "select id_user
			from ds_usercheckcode
			where id_user = %d
			and code = '%s'",
			$_SESSION['uid'],
			pg_escape_string ( $_POST['code'] ) );

	$res = $common->getResultArray ( "is_code_valid", $sql, "", "", "" );
	if ( !$res ) {
		@session_destroy();
		header ( "Location: https://".$_SERVER['HTTP_HOST'] );
		exit;
	}

	unset ($_SESSION["interstitial"]);
	$_SESSION["first_banner"] = "shown";

	$str = "Location: index.php";

	if ( session_id() ) $str .= "?".session_name()."=".session_id();

	header ( $str );
	exit;
}
?>