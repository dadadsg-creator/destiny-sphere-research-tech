<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2008/12/25 10:04:54 $';
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

assert(key_exists('idw', $_REQUEST));
assert(key_exists('from', $_REQUEST));
assert(key_exists('to', $_REQUEST));
assert(key_exists('nick', $_REQUEST));

$world = $_REQUEST['idw'];
$from = (int)$_REQUEST['from'];
$to = (int)$_REQUEST['to'];
$nick = $_REQUEST['nick'];
$exlid = $_REQUEST['exlid'];

LG_CVSBattleListExport::Export($from, $to, $world, $nick, $exlid);
?>