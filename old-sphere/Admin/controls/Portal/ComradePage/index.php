<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_without_cvs.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once "./common.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=ComradePage><font size=3><b>HELP</b></font></a></p>';

if (!empty($_REQUEST['FieldDescript']) && strlen($_REQUEST['FieldDescript']) > 200)
{
//  echo $_REQUEST['FieldDescript'] = substr($_REQUEST['FieldDescript'], 1, 200);
    $a = $_REQUEST['FieldDescript'];
    $a  = substr($a, 0, 200);
    $pos = strrpos($a, ' ');
    if ($pos)
    {
        $a = substr($a, 0, $pos);
    }

    $_POST['FieldDescript'] = $_REQUEST['FieldDescript'] = $a.' ...';

}

$Options[Order] = "authors";
$Options[Where] = "Placer = 'comradepages'";

$se = new SimpleEdit($Table, $Fields, $Options);

$se->ProcessEvent($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";


?>