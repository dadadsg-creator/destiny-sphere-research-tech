<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
session_start();

$common = DS_Common::CreateForCurentDB(0);

$resources = array('Кадериум', 'Нанокристаллы', 'Энергия', 'Еда', 'Продиум');

$worlds = array();
$sql = "SELECT worldname, id_world, dbname FROM ds_worldcollection";
$res = $common->getResultArray("", $sql);
foreach ($res as $row) {
    $worlds[$row['dbname'].'_'.$row['id_world']] = $row['worldname'];
}

if ($_REQUEST['act']=="selectWorld") {
    $pos = strrpos($_POST['world'], '_');
    $server = substr($_POST['world'], 0, $pos);
    foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db) {
        if ($db['server']==$server) {
            $_SESSION['dbId'] = $dbId;
            break;
        }
    }
    $_SESSION['worldFull'] = $_POST['world'];
    $_SESSION['world'] = substr($_POST['world'], $pos+1);
}

if (isset($_SESSION['dbId'])) {
    $common->setDbId($_SESSION['dbId']);
}

if ($_REQUEST['act']=="add") {
    $id = (int) ($_REQUEST['res']);
    $num = (int) ($_REQUEST['num']);
    $prob = (float) ($_REQUEST['prob']);
    $sql = "insert into ds_npc_lottery (type, id, num, prob, active)
        values ('r', $id, $num, $prob, true)";
    $common->executeQuery($sql);
}

$sql = "select * from ds_npc_lottery where type='r'";
$res = $common->getResultArray("", $sql);

//----------------------------------------------------------------
?>
<script src="/Admin/js/jquery-2.1.4.min.js"></script>
<script>
function saveValue(el, key) {
    $.ajax({
        type: 'POST',
        url: 'save.php',
        data: 'key=' + key + '&val=' + el.value,
        success: function(data) {
            el.parentNode.onclick = function() {makeInput (this, key);};
            el.parentNode.innerHTML = data;
        }
    });
}

function makeInput(el, key) {
    el.onclick = null;
    el.innerHTML = '<input onBlur="saveValue(this, \''+key+'\')" value="'+el.innerHTML+'">';
    el.children[0].focus();
}

function deleteRow(el, key) {
    $.ajax({
        type: 'POST',
        url: 'save.php',
        data: 'key=' + key,
        success: function(data) {
            el.parentNode.onclick = function() {makeInput (this, key);};
            el.parentNode.innerHTML = data;
        }
    });
    el.parentNode.parentNode.removeChild(el.parentNode);
}
</script>
<font color="#ffffff">
Мир:
<form method="post">
<input type="hidden" name="act" value="selectWorld">
<select name="world">
<? foreach ($worlds as $key=>$val) { ?>
    <option value="<?=$key?>"<?=$_SESSION['worldFull']==$key?' selected':''?>><?=$val?></option>
<? } ?>
</select>
<input type="submit" value="Выбрать">
</form>

Добавить приз:
<form method="post">
<input type="hidden" name="act" value="add">
Ресурс: <select name="res">
<? foreach ($resources as $key=>$val) { ?>
    <option value="<?=$key?>"><?=$val?></option>
<? } ?>
</select>
Количество: <input name="num">
Редкость: <input name="prob">
<input type="submit" value="Добавить">
</form>
</font>

<input type="hidden" name="act" value="edit">
<table border="0" bordercolor="black" cellspacing="1" cellpadding="4">
    <th class="th_3">Ресурс</th>
    <th class="th_3">Количество</th>
    <th class="th_3">Редкость</th>
    <th class="th_3"></th>
    <? foreach ($res as $row) { ?>
    <tr>
        <td bgcolor="#f0ffff"><?=$resources[$row['id']]?></td>
        <td bgcolor="#f0ffff"><?=$row['num']?></td>
        <td bgcolor="#f0ffff" align="center" style="cursor: pointer" onClick="makeInput(this, '<?="prob|{$row['id']}|{$row['num']}"?>')"><?=$row['prob']?></td>
        <td bgcolor="#f0ffff" align="center" style="cursor: pointer" onClick="deleteRow(this, '<?="del|{$row['id']}|{$row['num']}"?>')">удалить</td>
    </tr>
    <? } ?>
</table>
