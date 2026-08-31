<?php

require_once "../Main.php";
require_once "./classes/TradeLogsControl.class.php";
require_once "./classes/TradeLogOut.class.php";
require_once "./classes/TradeLogSearch.class.php";
require_once "./classes/TypeSearch.class.php";

echo TradeLogsControl::Exec($_REQUEST);

?>