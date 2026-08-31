<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once "../classes/Core/MoneyAccount.class.php";

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;

$ac = new MoneyAccount();

echo $ac->main($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>