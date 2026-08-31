<?php
ini_set('memory_limit', '128M');

include "../Main.php";

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

SU_ValidateDoubleCombsControl::Exec($_REQUEST);
?>