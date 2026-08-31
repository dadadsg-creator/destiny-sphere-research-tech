<?php

define('GK_PATH', $_SERVER['DOCUMENT_ROOT'].'/ds/GateKey_Master/');

require_once(GK_PATH."config.php");

function GK_LoadClass($className) 
{
	require_once(GK_PATH."classes/".$className.".class.php");
}

GK_LoadClass('GK_DBInterface');
GK_LoadClass('GK_DBPostgreSQL');
GK_LoadClass('GK_SearchBase');
GK_LoadClass('GK_HTTPRequest');
GK_LoadClass('GK_SlaveSearch');
GK_LoadClass('GK_Slave');
GK_LoadClass('GK_KeyMasterSearch');
GK_LoadClass('GK_GateKeyMasterController');
GK_LoadClass('GK_Generator');

?>