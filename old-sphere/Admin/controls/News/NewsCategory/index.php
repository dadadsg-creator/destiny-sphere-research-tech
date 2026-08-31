<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_without_cvs.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";

require_once "./common.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=NewsCategory><font size=3><b>HELP</b></font></a></p>';

$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;

$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";


?>