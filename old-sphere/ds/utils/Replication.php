<?php
ini_set('memory_limit', '128M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');
define('DEBUG_MODE', 0);

$replicator = new SU_ReplicationControl();
$replicator->Exec();
?>
