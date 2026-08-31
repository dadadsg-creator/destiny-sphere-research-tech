<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once 'include/functions.php';
require_once 'classes/PeriodsHandler.class.php';
//require_once 'templates/root.tpl';


$ph = new PeriodsHandler();
$ph->handleRequest();
$ph->display();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>