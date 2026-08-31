<?php
// редирект на ds.rambler.ru
$serversToRedirect = array('destinysphere.ru', 'www.destinysphere.ru', 'ds-game.ru', 'www.ds-game.ru');
$server = getenv("HTTP_HOST");
if(in_array($server, $serversToRedirect))
{
	$redirect = "http://ds.rambler.ru".getenv("REQUEST_URI");
	header("Location: ".$redirect);
	exit;
}

$charset = array ('RU' => 'windows-1251', 'DE' => 'iso-8859-1', 'EN' => 'windows-1251');

//require_once "./conf.php";
require_once "./sys/define.php";
require_once "./sys/lib/PageCache.class.php";
require_once "./sys/tpl.php";
require_once "./sys/lib/PageCache.class.php";

header("Content-Type: text/html; charset=".$charset[strtoupper(constant('DEFAULT_LANG'))]." ");
header("Last-Modified: ".date("D, d M Y H:i:s")." GMT");

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

$conn_id = getMysqlConnection();
$sql = file_get_contents('v0.sql');
$comms = explode(';',$sql);
foreach ($comms as $sql)
{
  echo "sql=$sql<br>\n";
  $r = mysql_db_query(constant('DBName'), $sql, $conn_id );
  if (!$r)
    echo "error=".mysql_error()."<br>";
}


$sql = file_get_contents('v.sql');
$comms = explode(');',$sql);
foreach ($comms as $sql)
{
  $sql .= ");";
  echo "sql=$sql<br>\n";
  $r = mysql_db_query(constant('DBName'), $sql, $conn_id );
  if (!$r)
    echo "error=".mysql_error()."<br>";
}


//mysql_db_query(constant('DBName'), $sql, $conn_id );
echo "error=".mysql_error()."<br>";
echo "ok";
?>