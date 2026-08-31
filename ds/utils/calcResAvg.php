<?php
ini_set('memory_limit', '16M');

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

SU_CalcWorldAverageResources::Calc();
?>