<?php
require_once 'Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

header("Content-type: application/xml");
die(SU_PortalNews::Get('rss'));
?>