<?
include '../Main.php';

switch ($_REQUEST['act']) {
    case 'getUserID':
        $common = DS_Common::CreateForCurrentDB(0);
        echo DS_Validate::GetUserIdByUserNameAndPassword($_REQUEST['username'], $_REQUEST['password'], $common);
        break;

    case 'collect':
        $collectWorlds = new SU_CollectWorlds();
        $userData = array(
            'id_user' => $_REQUEST['uid'],
            'id_userinstance' => $_REQUEST['uiid'],
            'id_world' => 1,
            'admin' => 'f'
        );
        $collectWorlds->collectUserInstance($userData, 'turnir');
        break;
}
?>
