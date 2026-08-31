<?php

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

if (isset($_POST['id_world'])) {
    $common = DS_Common::CreateForCurrentDB($_POST['id_world']);
    $sql = "select id_battle from ds_battle where battleenddate between '{$_POST['date1']}' and '{$_POST['date2']}'";
    $res = $common->getResultArray("", $sql);
    $logs = new SU_ViewBattleSystemLogs();
    $n = 0;
    foreach ($res as $row) {
        $logs->returnBattleById($row['id_battle']);
        $n++;
    }
    echo "<font color=\"beige\">$n battles rolled back</font><br><br>";
}


?>
<form method="post">
<font color="beige">Мир: </font><select name="id_world">
<? foreach ($GLOBALS['dbWorldAccess'] as $key=>$val) { ?>
<option value="<?=$key?>"><?=$val['dbname']?></option>
<? } ?>
</select><br>
<font color="beige"> Даты (ГГГГ-ММ-ДД ЧЧ:ММ) от </font><input name="date1"><font color="beige"> до </font><input name="date2"><br>
<input type="submit" value="Откатить бои">
</form>

<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>