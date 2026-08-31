<?php $CVS_Version = '$Revision$ ; $Date$';

//ini_set('memory_limit', '128M');
//set_time_limit(1300);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

SU_DebugTools::TimerStart('TEST');

$_SESSION["dbId"] = 0;

$addAvatar = new SU_AddAvatar();
echo $addAvatar->main($_REQUEST);

?>