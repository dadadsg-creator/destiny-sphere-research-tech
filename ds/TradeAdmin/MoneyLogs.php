<?php

require_once "../Main.php";
require_once "./classes/MoneyLogsControl.class.php";
require_once "./classes/MoneyLogOut.class.php";
require_once "./classes/MoneyLogSearch.class.php";
require_once "./classes/TypeSearch.class.php";

echo MoneyLogsControl::Exec($_REQUEST);

?>