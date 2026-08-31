<?php
ini_set('memory_limit', '1024M');
set_time_limit(1300);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

$_SESSION["dbId"] = 0;

SI_FinalizeWorldTransfer::Finalize();
?>