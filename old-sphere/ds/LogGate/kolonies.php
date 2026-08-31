<?php
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
$world = $_REQUEST['idw'];

LG_ColoniesListExport::Export($world);
?>