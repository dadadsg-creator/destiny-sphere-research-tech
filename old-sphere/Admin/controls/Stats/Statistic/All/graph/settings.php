<?

define ( "ZPS_INSTALLED", 0 );

define ( "CACHING_PREFIX", "reliz_ru" );

define ( "DB_DEFAULT_TIMEOUT", "60000" );
define ( "DB_EXTENDED_TIMEOUT", "80000" );
define ( "DB_CACHE_PATH", "/usr/local/tmp");

define ( "DEBUG_LOGS", "/usr/local/var/rlogs/"); 
define ( "FORUM_TIMEZONE", "3");
define ( "FORUM_LANGUAGE", "russian");	
define ( "FORUM_PREFIX", 'forum');


//Параметры для подключечения к бд имеющемся в наличие
global $dbWorldAccess, $dbForum;
$dbWorldAccess = array (

		0 => array ( //База с нулевым инлдексом считаеться мастером
			'host' => '192.168.0.10',
			'dbname' => 'destiny1',
			'user' => 'rouslan',
			'password' => 'rous12lan34',
			'server' => 'master'
		),
		1 => array (
			'host' => '192.168.0.10',
			'dbname' => 'release',
			'user' => 'rouslan',
			'password' => 'rous12lan34',
			'server' => 'release'
		)

		

	);


$dbForum = array (
			'host' => 'gamedb9.destinysphere.ru',
			'dbname' => 'forum_ru',
			'user' => 'dsgame',
			'password' => 'REDACTED_DB_PASSWORD'
);
?>
