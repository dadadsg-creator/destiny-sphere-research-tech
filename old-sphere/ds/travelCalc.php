<?
header("Content-Type: text/html; charset=windows-1251");
require_once ('Main.php');
$_SESSION['dbId'] = 13;
$_SESSION['world'] = 1;
$_SESSION['lang'] = 3;

if ($_REQUEST['submit']) {
    $comb1 = DE_Comb::CreateFromCoordinates($_SESSION['world'], (int)$_REQUEST['ring1'], (int)$_REQUEST['complex1'], (int)$_REQUEST['comb1']);
    $comb2 = DE_Comb::CreateFromCoordinates($_SESSION['world'], (int)$_REQUEST['ring2'], (int)$_REQUEST['complex2'], (int)$_REQUEST['comb2']);
    $time = GL_TravelTime::Count($comb1, $comb2, GB_TypeFactory::CreateBase('GB_SpyMoveSpeed', (float)$_REQUEST['speed']))->getDSFormat();
}
?>
<form method="post">
Сота 1:
<input name="ring1" style="width: 40px" value="<?=$_REQUEST['ring1']?>">:
<input name="complex1" style="width: 40px" value="<?=$_REQUEST['complex1']?>">:
<input name="comb1" style="width: 40px" value="<?=$_REQUEST['comb1']?>">
<br>
Сота 2:
<input name="ring2" style="width: 40px" value="<?=$_REQUEST['ring2']?>">:
<input name="complex2" style="width: 40px" value="<?=$_REQUEST['complex2']?>">:
<input name="comb2" style="width: 40px" value="<?=$_REQUEST['comb2']?>">
<br>
Скорость: <input name="speed" style="width: 60px" value="<?=$_REQUEST['speed']?>">
<br>
<input type="submit" name="submit" value="Посчитать время">
</form>
<? if (isset($time)) { ?>
<br><br>Время полёта: <?=$time?>
<? } ?>
