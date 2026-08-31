<?php

ini_set('memory_limit', '128M');

require_once "../Main.php";

require_once "./classes/CheckMainController.class.php";
require_once "./classes/ChechSearch.class.php";
require_once "./classes/CheckManager.class.php";

CheckMainController::Exec($_REQUEST);

?>