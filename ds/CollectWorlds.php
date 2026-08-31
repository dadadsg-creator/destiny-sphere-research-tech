<?
require_once('Main.php');
import ( 'DebugTools');



$a = new SU_CollectWorlds();

$servers = @$_REQUEST['servers'];

if(!$servers){
	$servers = false;
}else{
	$servers = explode('-', $servers);
}

$a->allowBases = $servers;
//$a->collect();


?>