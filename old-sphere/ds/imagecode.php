<?php
/*
$validRefferers = array(
	'192.168.0.37',
	'81.19.71.94',
	'81.19.71.67',
	'81.19.71.69',
	'81.19.71.79',
	'81.19.71.83',
	'81.19.71.84',
	'81.19.71.88'
);
$referer = $_SERVER['REMOTE_ADDR'];

if(!in_array($referer, $validRefferers))
{
	exit;
}*/

require_once("Main.php");

// define fonts
$aFonts = array('VeraMoBd.ttf');
$charSet = array(
				 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 
				 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 
				 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 
				 'Z'
				 );


// create new image
$oPhpCaptcha = new GE_ImageProtection($aFonts);
$oPhpCaptcha->SetCharSet($charSet);
$oPhpCaptcha->Create();
?>