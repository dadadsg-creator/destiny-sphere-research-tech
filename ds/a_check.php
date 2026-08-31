<?

if ( !@session_start () ) {
	exit;
}

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}

require_once ('a_bounds.php');

//$db;

$nextclickvalue;

$user;

if (!empty ( $_SESSION["uid"] )) {

	$user = $_SESSION["uid"];
	
	$sql = "select extract(second from (now () - (lastclick + interval '" . $min_click_waiting . " seconds'))) as nextclickvalue from ds_useradsstatistics where id_user = " . $user;
	
	$res = $common->getResultArray ( "nexclickvalue_id_user_".$user, $sql, "", "", "NO_CACHE" );
	if ( $res === false ) {
		die ( "SystemError - a_check.php1" );
	}

	if ( sizeof ( $res ) == 0 ) {

		$common->executeQuery ( "begin" );

		$sql = sprintf ("insert into
	  			  ds_useradsstatistics (id_user, views, clicks, remainingviews, nextstep, lastclick, lastview) 
	  			  values (" . $user . ", 0, 1, " . $max_views . ", " . $first_step . ", now(), now())");

		$res = $common->executeQuery ( $sql );
		if ( $res === false ) {
			die ( "SystemError - a_check.php2" );
		}

		$common->executeQuery ( "commit" );

	} else {

		$nextclickvalue = $res[0]['nextclickvalue'];
		
		$sql = "select remainingviews, nextstep, lastclick from ds_useradsstatistics where id_user = " . $user;

		$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		if ( $res === false ) {
			print "SQL/database - error ";
			exit;
		}
		
		$remaining = $res[0]['remainingviews'];
		
		if (($remaining <= $max_views) && ($nextclickvalue >= 0)) {
	  
			$common->executeQuery ( "begin" );

			$sql = sprintf ( "update ds_useradsstatistics
					set clicks = clicks + 1, remainingviews = " . $max_views . ", nextstep = " . $first_step . ", lastclick = now()  
		  			where id_user = " . $user );
			
			$res = $common->executeQuery ( $sql );
			if ( $res === false ) {
				die ( "SystemError - a_check.php3" );
			}

			$common->executeQuery ( "commit" );
		}
	}
}

unset ( $_SESSION["interstitial"] );

?>
