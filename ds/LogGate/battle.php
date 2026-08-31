<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2008/04/28 13:27:50 $';
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

assert(key_exists('idb', $_REQUEST));
$battleID = (int)$_REQUEST['idb'];

if(key_exists('type', $_REQUEST))
{
    $type = $_REQUEST['type'];
} else {
    $type = 'xml';
}

LG_XMLBattleExport::export($userName, 0, $world, $battleID, $type);
?>