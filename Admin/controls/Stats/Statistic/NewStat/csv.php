<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once "./classes/RegStatisticSearch.class.php";
require_once "./classes/RegStatisticOut.class.php";
require_once "./classes/RegStatisticController.class.php";

$s = new RegStatisticController($_REQUEST);
?>