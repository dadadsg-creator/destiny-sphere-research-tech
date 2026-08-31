<?
require_once($_SERVER['DOCUMENT_ROOT']."/ds/Main.php");


/*
$GLOBALS['dbWorldAccess'] = array  
(
		0 => array ( //База с нулевым инлдексом считаеться мастером
			'host' => 'game12',
			'dbname' => 'destiny1',
			'user' => 'dsgame',
			'password' => 'REDACTED_DB_PASSWORD',
			'server' => 'volk',
			'dir' => '/ds/'
		),
);
/*
global $UserManager;
*/
/*$GLOBALS['dbWorldAccess'] = array  
(
		0 => array ( //База с нулевым инлдексом считаеться мастером
			'host' => 'gamedb14',
			'dbname' => 'destiny8',
			'user' => 'dsgame',
			'password' => 'REDACTED_DB_PASSWORD',
			'server' => 'master',
			'dir' => '/ds/'
		),
		
		1 => array ( //База с нулевым инлдексом считаеться мастером
			'host' => 'gamedb6',
			'dbname' => 'destiny1',
			'user' => 'dsgame',
			'password' => 'REDACTED_DB_PASSWORD',
			'server' => 'phoenix',
			'dir' => '/ds/'
		),
);*/
/*
// убираем недоступные для пользователя базы
if(!$UserManager->isSuperAdmin())
{
	$allowedDBs = $UserManager->GetAllowedDB();
	
	foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
	{
		if(!in_array($dbId, $allowedDBs))
		{
			unset($GLOBALS['dbWorldAccess'][$dbId]);
		}
	}
}*/
?>