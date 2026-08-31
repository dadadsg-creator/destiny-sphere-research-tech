<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
session_start();

$common = DS_Common::CreateForCurentDB($_SESSION['dbId']);

$tokens = explode("|", $_POST['key']);

switch ($tokens[0]) {
    case "lt":
        $uid = (int) $tokens[1];
        $key = $tokens[2];
        $val = (int) $_POST['val'];
        if ($key=='league') {
            $sql = "update ds_userinstance set id_league=$val where id_userinstance=$uid";
            $common->executeQuery($sql);
        }
        else if ($key=='type') {
            $sql = "select type from ds_npchastype where id_userinstance=$uid";
            $res = $common->getResultArray("", $sql);
            if (count($res))
                $sql = "update ds_npchastype set type=$val where id_userinstance=$uid";
            else 
                $sql = "insert into ds_npchastype (id_userinstance, type) values ($uid, $val)";
            $common->executeQuery($sql);
        }
        else if ($key=='level') {
            $sql = "update ds_ranking set id_level=$val where id_userinstance=$uid";
            $common->executeQuery($sql);
        }
        break;
    case "settings":
        $league = (int) $tokens[1];
        $type = (int) $tokens[2];
        $key = pg_escape_string($tokens[3]);
        $val = pg_escape_string($_POST['val']);
        $sql = "update ds_npc_army_settings set $key='$val' where league=$league and type=$type";
        $common->executeQuery($sql);
        break;
    case "prob":
        $league = (int) $tokens[1];
        $type = (int) $tokens[2];
        $condType = (int) $tokens[3];
        $condValue = (int) $tokens[4];
        $val = (float) $_POST['val'];
        $sql = "update ds_npc_attack_chance set probability=$val where league=$league and type=$type and condType=$condType and condValue=$condValue";
        $common->executeQuery($sql);
        break;
    case "repeat":
        $first_fight = pg_escape_string($tokens[1]);
        $pillager = pg_escape_string($tokens[2]);
        $winner = pg_escape_string($tokens[3]);
        $have_losses = (int) $tokens[4];
        $have_spoils = (int) $tokens[5];
        $key = pg_escape_string($tokens[6]);
        $val = (float) $_POST['val'];
        $sql = "update ds_npc_repeat_attack set $key=$val where first_fight='$first_fight' and pillager='$pillager' and winner='$winner' and have_losses=$have_losses and have_spoils=$have_spoils";
        $common->executeQuery($sql);
        break;
 }
echo $val;
?>