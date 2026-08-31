<?
require_once $_SERVER['DOCUMENT_ROOT']."/sys/define.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/MYSQLManager.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/UserManager.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/session.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/AdminMenu.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
$inc = new IncludeManager();

if (isset($change_lang_id)) $LanguageID = $change_lang_id;
if (!isset($LanguageID)) $LanguageID = "ru";
if (!isset($user_id)) $user_id = $session->user_id;
$session = new session();

if(!$session->user_id){
    headerLocation("/Admin/login.php");
}

$UserManager = new UserManager($session->user_id);
$adminMenu = new AdminMenu($UserManager);

if (!$UserManager->CheckUser())
{
    $UserManager->Logout();
    $session->logoff();
    headerLocation("/Admin/login.php");
}

if(!$UserManager->isSuperAdmin())
{

    if ($GLOBALS['easyadmin'] != 'easyadmin')
    {

        if(!$adminMenu->chekMenu())
        {
            $UserManager->Logout();
            $session->logoff();
            headerLocation("/Admin/login.php");
            die('Session End!');

        }
    }
}
?>
