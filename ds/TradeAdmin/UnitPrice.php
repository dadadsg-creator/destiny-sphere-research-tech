<?php

require_once "../Main.php";
require_once "./classes/UnitPriceControl.class.php";
require_once "./classes/UnitPriceOut.class.php";
require_once "./classes/UnitPriceSearch.class.php";
require_once "./classes/TypeSearch.class.php";

echo UnitPriceControl::Exec($_REQUEST);

?>