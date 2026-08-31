<?
include 'ds/Main.php';
SU_GameSessionWork::SessionContinue();
if (!$_SESSION['uid']) exit;
$json = file_get_contents("http://api.dai2.ru/v1.3?method=getOffers&app_id=115&uid=".$_SESSION['uid']);
$obj = json_decode($json);
print_r ($obj);
?>