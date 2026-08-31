<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_without_cvs.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once "./common.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Lastnews><font size=3><b>HELP</b></font></a></p>';

if (!empty($_REQUEST['FieldDescript']) && strlen($_REQUEST['FieldDescript']) > 400)
{
//  echo $_REQUEST['FieldDescript'] = substr($_REQUEST['FieldDescript'], 1, 200);
    $a = $_REQUEST['FieldDescript'];
    $a  = substr($a, 0, 400);
    $pos = strrpos($a, ' ');
    if ($pos)
    {
        $a = substr($a, 0, $pos);
    }

    $_POST['FieldDescript'] = $_REQUEST['FieldDescript'] = $a.' ...';

}

//$Options[Theme][Dir] = $_SERVER['DOCUMENT_ROOT'].'/templates/Admin/';
//$Options[Theme][Name] = 'default';

$Options[Order] = "NewsDate DESC";
$Options[Where] = "Placer = 'news'";

$se = new SimpleEdit($Table, $Fields, $Options);

$Params['action'] = $_GET['action'];
$Params['ID'] = $_GET['ID'];

$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";


?>