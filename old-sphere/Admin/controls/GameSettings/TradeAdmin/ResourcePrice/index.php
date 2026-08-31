<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once "../classes/CoursesControl.class.php";
require_once "../classes/CoursesOut.class.php";
require_once "../classes/CoursesSearch.class.php";
require_once "../classes/TypeSearch.class.php";

echo CoursesControl::Exec($_REQUEST);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>