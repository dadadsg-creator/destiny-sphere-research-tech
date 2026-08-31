<?php

require_once '../Main.php';
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';

function gauss($mu, $sigma) {
    $NV_MAGICCONST = 4 * exp(-0.5)/sqrt(2);
    while (true) {
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = 1 - mt_rand() / mt_getrandmax();
        $z = $NV_MAGICCONST*($u1-0.5)/$u2;
        $zz = $z*$z/4;
        if ($zz <= -log($u2)) break;
    }
    return $mu + $z*$sigma;
}

function cmpPrizes($a, $b) {
    if ($a['prize']['prob'] == $b['prize']['prob']) return 0;
    return ($a['prize']['prob'] > $b['prize']['prob']) ? -1 : 1;
}

function addPrizeToUser($userID, $type, $id, $num) {
    $id = (int) $id;
    $num = (int) $num;
    $common = DS_Common::CreateForCurentDB();
    $sql = "SELECT c.id_comb, ui.id_userinstance, u.username from ds_comb c, ds_combcomplex cc, ds_ring r, ds_userinstance ui, ds_user u
        WHERE c.id_userinstance=ui.id_userinstance
        and c.id_combcomplex=cc.id_combcomplex
        and cc.id_ring=r.id_ring
        and r.ringnumber < 4
        and ui.id_user=u.id_user
        and ui.id_user=".$userID;
    $res = $common->getResultArray("", $sql);
    $combID = (int) $res[0]['id_comb'];
    $username = $res[0]['username'];
    $uiid = $res[0]['id_userinstance'];
    $message = "Вы выиграли приз в лотерее Мимикри\n";
    if ($type=='u') {
        $comb = DE_Comb::CreateFromID($combID);
        $unit = DE_Unit::CreateFromID($id);
        $date = GB_TypeFactory::CreateBase('GB_Date', time());
        ME_AddUnitsToHomeArmy::AddUnit($comb, $unit, (float) $num, $date);
        $message .= $unit->getLocalizedName().': '.$num;
    }
    else if ($type=='a') {
        $artLifeTime = DE_WorldSettings::CreateForCurentWorld()->get('NPC_LOTTERY_ARTEFACT_LIFETIME');
        $sql = "SELECT add_artefact_to_comb($combID, $id, $artLifeTime)";
        $common->executeQuery($sql);
        $art = DE_Artefact::CreateFromArtefactID($id);
        $message .= $art->getLocalizedName();
    }
    else if ($type=='r') {
        $attr = array(1=>44, 2=>46, 3=>45, 4=>47, 5=>48);
        $sql = "update ds_combhasresource set value=value+$num where id_comb=$combID and id_attribute=".$attr[$id];
        $common->executeQuery($sql);
        $resource = DE_Resource::CreateFromID($id);
        $message .= $resource->getName().': '.$num;
    }

    $subject = "Приз в лотерее Мимикри";
    $messageID = DS_Message::CreateMessage($message, $subject, 'DestinySphere', $username, 10, 0);
    DS_Message::AddMessageToUser($messageID, $uiid);
}

$_SESSION['lang'] = 3;
$_SESSION['dbId'] = 1;
$common = DS_Common::CreateForCurentDB($dbId);
$res = $common->getResultArray("", "select id_world from ds_world");
$_SESSION['world'] = $res[0]['id_world'];
addPrizeToUser(35073, 'r', 1, 1);

?>