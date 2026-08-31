<?php

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once "./classes/AddArtefactsControl.class.php";
require_once "./classes/ArtFilter.class.php";
require_once "./classes/AddArtefactSearch.class.php";
require_once "./classes/ArtList.class.php";
require_once "./classes/WorldList.class.php";
require_once "./classes/AddArtefactOut.class.php";

AddArtefactsControl::Exec($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>