<?php

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

require_once "Main.php";

if(!SU_GameSessionWork::SessionContinue())
{
	SU_OutputHandler::Singleton()->addString("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key valid=\"0\" addr=\"index_login.php\"/>", 'loadkey');
	echo SU_OutputHandler::Singleton()->fetchAll();
	exit;
}

$vs = new SU_FlashVersions();

$vs->getVersions();

SU_OutputHandler::Singleton()->addString( $vs->makeXML(), 'xmldata');			
echo SU_OutputHandler::Singleton()->fetchAll();

?>