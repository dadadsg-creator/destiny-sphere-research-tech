<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
session_start();

$common = DS_Common::CreateForCurentDB(0);

$worlds = array();
$sql = "SELECT worldname, id_world, dbname FROM ds_worldcollection";
$res = $common->getResultArray("", $sql);
foreach ($res as $row) {
    $worlds[$row['dbname'].'_'.$row['id_world']] = $row['worldname'];
}

if ($_POST['act']=='selectWorld') {
    $pos = strrpos($_POST['world'], '_');
    $server = substr($_POST['world'], 0, $pos);
    foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db) {
        if ($db['server']==$server) {
            $_SESSION['dbId'] = $dbId;
            break;
        }
    }
    $_SESSION['world'] = substr($_POST['world'], $pos+1);
    $_SESSION['worldFull'] = $_POST['world'];
}

$common->setDbId($_SESSION['dbId']);

if ($_POST['act']=='savePrices') {
    foreach ($_POST['tc'] as $key=>$val) {
        $key = (int) $key;
        $val = (int) $val;
        $sql = "update ds_protective_field_restore_price set value=$val where id_tradecourse=$key";
        $common->executeQuery($sql);
    }
}

$sql = "SELECT
            CASE
                WHEN an.attributename IS NULL THEN m.money_name
                WHEN m.money_name IS NULL THEN an.attributename
            END as full_name,
            CASE
                WHEN an.attributename IS NULL THEN m.short_name
                WHEN m.money_name IS NULL THEN substr(an.attributename, 0, 3)
            END as name,
            trcd.value as price,
            trcd.id_tradecourse
        FROM
            ds_protective_field_restore_price AS trcd
            INNER JOIN ds_trade_course AS tc ON (tc.id_tradecourse = trcd.id_tradecourse)
            LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
            LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
            LEFT JOIN ds_gamemodifier gm ON (gm.id_world = trcd.id_world AND gm.modifiername = 'UNIT_REINCARNATION_MULTIPLIER')              
        WHERE
            trcd.id_world = {$_SESSION['world']}
        ORDER BY trcd.id_tradecourse";

$res = $common->getResultArray("", $sql);

// =================================================
?>
<form method="post">
<font color="#f0ffff">
<h3>Цена на защитное поле</h3>
Мир:</font>
<input type="hidden" name="act" value="selectWorld">
<select name="world">
<? foreach ($worlds as $key=>$val) { ?>
    <option value="<?=$key?>"<?=$_SESSION['worldFull']==$key?' selected':''?>><?=$val?></option>
<? } ?>
</select>
<input type="submit" value="Выбрать">
</form>

<form method="post">
<input type="hidden" name="act" value="savePrices">
<table border="0" bordercolor="black" cellspacing="1" cellpadding="4">
<? foreach ($res as $row) { ?>
    <tr>
       <td class="th_3"><?=$row['full_name']?></td>
       <td bgcolor="#f0ffff"><input name="tc[<?=$row['id_tradecourse']?>]" value="<?=$row['price']?>"></td>
    </tr>
<? } ?>
    <tr>
        <td></td><td><input type="submit" value="Сохранить"></td>
    </tr>
</table>
</form>