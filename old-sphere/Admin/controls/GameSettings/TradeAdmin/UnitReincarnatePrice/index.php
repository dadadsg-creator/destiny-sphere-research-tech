<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once "../classes/UnitReincarnatePriceControl.class.php";
require_once "../classes/UnitReincarnatePriceOut.class.php";
require_once "../classes/UnitReincarnatePriceSearch.class.php";
require_once "../classes/TypeSearch.class.php";
require_once "../classes/UnitPriceSearch.class.php";

//echo UnitReincarnatePriceControl::Exec($_REQUEST);
echo UnitReincarnatePriceControl::Exec($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>