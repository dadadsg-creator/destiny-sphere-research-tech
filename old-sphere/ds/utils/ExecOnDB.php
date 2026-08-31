<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '128M');
set_time_limit(0);

session_start();

require_once '../Main.php';

assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

echo "<h1>ExecOnDB on server <b>".$_SERVER['SERVER_NAME']."</b></h1>";


$a = new SU_ExecSQL();

?>