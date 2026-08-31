<?php

ini_set('memory_limit', '16M');
set_time_limit(1300);

require_once './masmail/include/Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;

require_once "./masmail/classes/Search.class.php";

$common = DS_Common::CreateForCurentDB();
$search = new Search($common);

$emailMD = $_REQUEST['p'];
assert(!empty($emailMD));

$userData = $search->getUserByMailMD($emailMD);

$search->disableUser($userData['id_user'], $userData['email']);

echo "You've unsubscribed from our deliveries successfully.";


?>