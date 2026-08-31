<?
session_register("WorkNewsType");

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_without_cvs.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once "./common.php";



if (!empty($_REQUEST['FieldDescript']) && strlen($_REQUEST['FieldDescript']) > 400)
{
//	echo $_REQUEST['FieldDescript'] = substr($_REQUEST['FieldDescript'], 1, 200);
	$a = $_REQUEST['FieldDescript'];
	$a  = substr($a, 0, 400);
	$pos = strrpos($a, ' ');
	if ($pos)
	{
		$a = substr($a, 0, $pos);
	}

	$_POST['FieldDescript'] = $_REQUEST['FieldDescript'] = $a.' ...';

}

$Options[Order] = "NewsDate DESC";
$Options[Where] = "Placer = 'smsnews2'";

$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;





$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";


?>