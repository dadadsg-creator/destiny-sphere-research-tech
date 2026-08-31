<?
require_once 'Main.php';
$_SESSION['dbId'] = 3;
$_SESSION['lang'] = 3;

$ui = DE_UserInstance::CreateFromID(79467);
TMP_NewYear::givePresent($ui);
?>