<?php

require_once "../Main.php";

require_once "./classes/TypeControl.class.php";
require_once "./classes/TypeSearch.class.php";
require_once "./classes/TypeOut.class.php";
require_once "./classes/TypeFormTableExec.class.php";

echo TypeControl::Exec($_REQUEST);

?>
