<?php
ini_set('memory_limit', '158M');
require_once "../Main.php";
require_once "./classes/ArtefactPriceControl.class.php";
require_once "./classes/ArtefactPriceOut.class.php";
require_once "./classes/ArtefactPriceSearch.class.php";
require_once "./classes/TypeSearch.class.php";

echo ArtefactPriceControl::Exec($_REQUEST);

?>