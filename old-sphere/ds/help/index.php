<?php 

require_once('../Main.php');
require_once(constant('INCLUDE_PATH').'/GameDown.inc.php');

if(GameDown())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit;
}

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регитрация финализатора
register_shutdown_function ('finilazer');

$help = new GH_MainInterface($_REQUEST);

?>
