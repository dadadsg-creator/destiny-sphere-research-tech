<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2007/08/28 13:46:56 $';

ini_set('memory_limit', '16M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

/*********** BEGIN WORK AREA **************/


SU_ReplicationInterfaceControl::Exec($_REQUEST);

?>