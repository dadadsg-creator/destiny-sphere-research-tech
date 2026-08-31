<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";

$adminMenu = new AdminMenu($UserManager);

$url = $adminMenu->getFirstChilde();

headerLocation ( $url );
?>