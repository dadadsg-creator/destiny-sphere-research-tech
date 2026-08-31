<?php

ini_set('memory_limit', '16M');
set_time_limit(600);

require_once './Main.php';

assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$test = new SU_TestSystemController;
$test->responsToExternalStatusRequest();

?>