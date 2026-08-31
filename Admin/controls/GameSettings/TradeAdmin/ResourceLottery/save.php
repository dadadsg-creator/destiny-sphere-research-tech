<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
session_start();

$common = DS_Common::CreateForCurentDB($_SESSION['dbId']);

$tokens = explode("|", $_POST['key']);

switch ($tokens[0]) {
    case "prob":
        $id = (int) $tokens[1];
        $num = (int) $tokens[2];
        $val = (int) $_POST['val'];
        $sql = "update ds_npc_lottery set prob=$val where type='r' and id=$id and num=$num";
        $common->executeQuery($sql);
        break;
    case "del":
        $id = (int) $tokens[1];
        $num = (int) $tokens[2];
        $sql = "delete from ds_npc_lottery where type='r' and id=$id and num=$num";
        $common->executeQuery($sql);
        return;
}
echo $val;
?>