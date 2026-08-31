<?php
require_once($_SERVER['DOCUMENT_ROOT']."/ds/config/gp_config.php");

if(constant('GATE_KEY_ONLY_POST_DATA'))
{
	$data = $_POST;
} else {
	$data = $_REQUEST;
}

try {
	GK_Reciever::Exec($data);
} catch (Exception $e) {
	die($e->getMessage());
}


?>