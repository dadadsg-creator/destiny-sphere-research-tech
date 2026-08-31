<?
include '../Main.php';
if (VERSION_NAME!="RU_UNLIM") exit(0);

SU_GameSessionWork::SessionStart();
$common = DS_Common::CreateForCurrentDB(0);
if (!$_SESSION['uid']) {
    header("Location: /ds/index_login.php");
    exit;
}

copycomb_header();
//echo 'Копирование в Сферу Гончих Псов закрыто.';
//copycomb_footer();
//exit;

if (isset($_REQUEST['world'])) {

    $tokens = explode('_', $_REQUEST['world']);
    $dbID = (int) $tokens[0];
    $worldID = (int) $tokens[1];

    $newUserID = file_get_contents("http://dsga.me/ds/copycomb/gate.php?act=getUserID&username={$_REQUEST['username']}&password={$_REQUEST['password']}");
    if (!$newUserID) {
        echo 'Неверный логин/пароль';
        show_copy_form($common);
        copycomb_footer();
        exit;
    }
    $_SESSION["dbId"] = $dbID;
    $common->setDbId($dbID);

    $sql = "select id_user from ds_world_copy where id_user=".$_SESSION['uid'];
    $res = $common->getResultArray("", $sql, "", "", "");
    if (count($res)) {
        echo 'Вы уже копировали свою соту в Сферу Гончих Псов';
        copycomb_footer();
        exit;
    }

    $oldUIID = DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($_SESSION['uid'],$worldID);
    SI_ControlStoreUserInstance::SetCommon($common);
    $obj = SI_ControlStoreUserInstance::Exec($oldUIID);
    $obj->userData['id_user'] = $newUserID;
    $combRings = array();
    foreach ($obj->getComb() as $comb) {
        $combInfo = DS_Comb::GetByID($comb->getCombID());
        $combRings[$comb->getCombName()] = $combInfo['ringnumber'];
    }
    $_SESSION["dbId"] = 13;
    $common->setDbId(13);
    $uiid = DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID((int)$newUserID, 1);
    if ($uiid) {
        echo "У игрока {$_REQUEST['username']} уже есть сота в Сфере Гончих Псов";
        exit;
    }
    $newCombIds = array();
    foreach ($combRings as $combName=>$ring) {
        $newCombIds[$combName] = SI_SearchInterface::GetMinCombIDForPlacementOnRing($ring, 1, $newCombIds);
        if (!$newCombIds[$combName]) {
            echo "В Сфере Гончих Псов нет свободных сот.";
            exit;
        }
    }
    $newCombCoords = array();
    foreach ($newCombIds as $combName=>$combID) {
        $newCombInfo = DS_Comb::GetById($combID);
        $newCombCoords[$combName] = array(
            'RING' => $newCombInfo['ringnumber'],
            'COMPLEX' => $newCombInfo['complexnumber'],
            'COMB' => $newCombInfo['combnumber']
        );
    }
    $newUIID = SI_ControlRestoreUserInstance::Restore(1, $newCombCoords, $obj, false, false);
    file_get_contents("http://dsga.me/ds/copycomb/gate.php?act=collect&uid=$newUserID&uiid=$newUIID");

    $_SESSION["dbId"] = $dbID;
    $common->setDbId($dbID);
    $instance = DE_UserInstance::CreateFromID($oldUIID);
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < 12; $i++) {
        $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
    }
    DS_Freez::StartVacationMode($instance, $randomString);
    $sql = "insert into ds_world_copy (id_user) values ({$_SESSION['uid']})";
    $common->executeQuery($sql);
    echo 'Копирование в Сферу Гончих Псов завершено.';
}   


// -------------------------------------------
// Формочка с выбором мира, логином и паролем
// -------------------------------------------
else {
    show_copy_form($common);
} 

copycomb_footer();

//======================================================================

function copycomb_header() {
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <link rel="stylesheet" href="/dsstyle.css" type="text/css">
    <link rel="top" href="index.php" title="" />
    <link rel="search" href="search.php" title="" />
    <link rel="help" href="faq.php" title="" />
    <link rel="author" href="memberlist.php" title="" />
    <title>DestinySphere :: Копирование соты в Сферу Гончих Псов</title>
</head>
<body>
<table width=100% height=95% border=0 cellpadding=0 cellspacing=0>
    <tr>
        <td>
            <table width=100% border=0 cellpadding=0 cellspacing=0>
                <!-- header begin -->
                <tr height=86>
                    <td valign=top>
                        <table width=100% height=86 border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td width=500>
                                    <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="500" height="86" id="header_1" align="middle">
                                        <param name="allowScriptAccess" value="sameDomain">
                                        <param name="movie" value="/img/flash/header.swf">
                                        <param name="quality" value="low">
                                        <param name="menu" value="false">
                                        <param name="settings" value="false">
                                        <param name="wmode" value="opaque">
                                        <param name="bgcolor" value="#ffffff">
                                        <embed src="/img/flash/header.swf" quality="low" wmode="opaque" bgcolor="#ffffff" width="500" height="86" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
                                    </object></td>
                                <td width=265><img src="/img/background/header_bg.jpg" border=0></td>
                                <td width=100% background="/img/background/header_bg2.gif"></td>  
                            </tr>
                        </table>    
                    <td>    
                </tr>
                <!--begin-->
                <tr height=3>
                    <td background="/img/background/buttons_bg.gif" align=center class=wnd_gen_txt></td>
                </tr>
                <!--end-->
                <tr height=5>
                    <td background="/img/background/bg.gif" align=center class=wnd_gen_txt></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td valign=top height=100%>
            <table width="100%" border="0" cellpadding="0" cellspacing="10">
                <tr>
                <td valign=top>
                        <table width="100%" border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td width=5 background="/img/border/01/l.gif"></td>
                                <td width=100% class=wnd_outer valign=top>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <!-- -->
                                        <tr>
                                            <td width=4 background="/img/border/02/l.gif"></td>
                                            <td width=100% align="center" class=wnd_inner>
<?
}


function copycomb_footer() {
?>
                                            </td>
                                            <td width=4 background="../img/border/02/r.gif"></td>
                                        </tr>
                                        <!--Description world-->
                                    <!-- end world collection -->
                                    </table>
                                </td>
                                <td width=5 background="../img/border/01/r.gif"></td>
                             </tr>
                         </table>
                     </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?
}

function show_copy_form($common) {
    GE_SearchStart::SetCommon($common);
    $worlds = array();
    $userWorlds = GE_SearchStart::GetWorldsWhereUserIsRegistered((int)$_SESSION['uid']);
    foreach ($userWorlds as $k => $world)
    {
        $dbID = $common->getDbIdByName($world['dbname']);
        $worlds[$dbID.'_'.$world['id_world']] = $world['worldname'];
    }
?>

<form method="post">
<table>
    <tr>
        <td>Логин в Destiny Sphere Classic: </td>
        <td><input name="username"></td>
    </tr>
    <tr>
        <td>Пароль в Destiny Sphere Classic: </td>
        <td><input name="password" type="password"></td>
    </tr>
    <tr>
        <td>Откуда копировать: </td>
        <td><select name="world">
         <? foreach ($worlds as $key=>$val) { ?>
            <option value="<?=$key?>"><?=$val?></option>
         <? } ?>
         </select></td>
    </tr>
    <tr>
        <td></td>
        <td><input type="submit" value="Скопировать соту в Сферу Гончих Псов"></td>
    </tr>
</table>
</form>

<?
}
?>
