<?php
	$_SERVER['DOCUMENT_ROOT'] = '/home/www/htdocs';
	include_once('DropUserDead.class.php');

	$drop = new DropUserDead ();
	$drop->drop();
?>
