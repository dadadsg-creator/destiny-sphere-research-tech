<?php $CVS_Version = '$Revision$ ; $Date$';
ini_set('memory_limit', '128M');
set_time_limit(3540);
require_once '../Main.php';

assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 1;
/************************************************/

SU_RequestStatControllerMain::Show();

?>