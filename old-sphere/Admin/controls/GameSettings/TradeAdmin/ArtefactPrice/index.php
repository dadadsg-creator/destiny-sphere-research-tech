<?php
ini_set('memory_limit', '158M');

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

//require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once "../classes/ArtefactPriceControl.class.php";
require_once "../classes/ArtefactPriceOut.class.php";
require_once "../classes/ArtefactPriceSearch.class.php";
require_once "../classes/TypeSearch.class.php";


echo ArtefactPriceControl::Exec($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>