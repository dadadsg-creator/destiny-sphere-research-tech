<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/tree.class.php";

echo "<a href=\"/Admin/controls/Portal/PagesTree/?".session_name().'='.session_id()."\" target=_top>Start page</a>";


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
