<?php
ini_set('memory_limit', '158M');
require_once "../Main.php";
require_once "./classes/TradeStatisticController.class.php";
require_once "./classes/TradeStatisticSearch.class.php";
require_once "./classes/TradeStatisticOut.class.php";

echo TradeStatisticController::Exec($_REQUEST);
?>