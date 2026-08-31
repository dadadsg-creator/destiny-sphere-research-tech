<?

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	if ( !$common->connect() ) {
		echo "DB-connection failed";
		exit;
	}
}

if ( @$_POST["email"] ) {

	$sql = sprintf ( "BEGIN; INSERT INTO ds_recommendation (id_recommendation, email, recommenddate) VALUES (DEFAULT, '" . $_POST["email"] . "', now()); COMMIT;");

	$res = $common->executeQuery ( $sql );
	if ( ! ($res === false ) ) {
		$header = "MIME-Version: 1.0\n";
		$header .= "Content-type: text/plain; charset=iso-8859-1\n";
		$header .= "From: noreply@destinysphere.com\n";
		$header .= "Reply-To: support@destinysphere.com\n";
		$header .= "X-Mailer: PHP/" . phpversion();

		mail ( trim ( $_POST["email"]), "Eine Empfehlung für DestinySphere Online-Spiel (für den Inhalt der Nachricht übernimmt DestinySphere GmbH keinerlei Haftung)", $_POST["recommend"], $header ); 
	}
}

include_once ( "IT.php" );

if ( !isset ( $_SESSION["language"] ) ) {
			
	$sql = sprintf ("select shortname
			from ds_language
			where id_language = %d",
			DEFAULT_LANGUAGE );

	$res = $common->getResultArray ( "language", $sql, "", "", "NO_CACHE" );

	if ( $res === false ) {
		echo "SystemError - Recommend1";
		exit;
	}
		
	$language = $res[0]['shortname'];
			
} else {
			
	$language = $_SESSION['language'];
}

include_once ( "./language/".$language."/index_recommend.lang" );

if ( !@$lang ) {
	echo "SystemError - Recommend1";
	exit;
}

$tpl = new HTML_Template_IT ("./templates");
$tpl->loadTemplatefile ( "index_recommend.tpl", true, true );   

foreach ( $lang as $key=>$val ) {
	
	$tpl->setVariable ( $key, $lang[$key] ) ;
}
			
$tpl->show ();
unset ( $lang );

?>