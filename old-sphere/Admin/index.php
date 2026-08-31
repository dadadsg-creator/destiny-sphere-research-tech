<?
foreach($_POST as $key=>$val) $$key = $val;
require_once $_SERVER['DOCUMENT_ROOT']."/sys/define.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/MYSQLManager.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/UserManager.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/session.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/AdminMenu.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";

new IncludeManager();

if (isset($change_lang_id)) $LanguageID = $change_lang_id;
if (!isset($LanguageID)) $LanguageID = "ru";
if (!isset($user_id)) $user_id = $session->user_id;

$session = new session();

$UserManager = new UserManager($session->user_id);

if ( !$UserManager->CheckUser()) {
    $UserManager->Logout();
    $session->logoff();
    headerLocation("login.php");
}
else {
$adminMenu = new AdminMenu($UserManager);

$url = $adminMenu->getFirstChilde();

headerLocation ( $url );
}
?>