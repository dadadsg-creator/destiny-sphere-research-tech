<? 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Не могу подключиться");

$version = $memcache->getVersion();
echo "Версия сервера: ".$version."<br/>\n";
?>

