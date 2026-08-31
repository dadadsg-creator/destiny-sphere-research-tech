<?php
ini_set('memory_limit', '512M');
//set_time_limit(0);
session_start();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once "../classes/MoneyLogsControl.class.php";
require_once "../classes/MoneyLogOut.class.php";
require_once "../classes/MoneyLogSearch.class.php";
require_once "../classes/TypeSearch.class.php";

if ($_SESSION['limit'] == '')
{
    $_SESSION['limit'] = 30;
}

if ($_SESSION['page'] == '')
{
    $_SESSION['page'] = 0;
}

echo MoneyLogsControl::Exec($_REQUEST);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>