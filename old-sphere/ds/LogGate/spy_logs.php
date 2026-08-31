<?php $CVS_Version = '$Revision$ ; $Date$';
ini_set('memory_limit', '64M');
set_time_limit(0);

require_once '../Main.php';

require_once(constant('INCLUDE_PATH').'/GameDown.inc.php');
if(GameDown())
{
	die("GameDown");
}

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регитрация финализатора
register_shutdown_function ('finilazer');

if(version_compare(PHP_VERSION,'5','>='))
{
	import ('domXML');
}
assert(key_exists('nick', $_REQUEST));
$userName = $_REQUEST['nick'];

assert(key_exists('idw', $_REQUEST));
$world = $_REQUEST['idw'];

assert(key_exists('idl', $_REQUEST));
$logID = (int)$_REQUEST['idl'];

LG_XMLSpyLogsExport::export($userName, 0, $world, $logID);
?>