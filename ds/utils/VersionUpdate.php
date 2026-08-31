<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2008/01/23 12:25:24 $';

require_once "../Main.php";

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
	<meta http-equiv="Content-Style-Type" content="text/css">

	<title>VERSION UPDATE</title>
	<link rel="stylesheet" href="../dsstyle.css" type="text/css">
</head>

<body>

<br>
<hr>
<a href="?act=CheckOut">Проверить обновления</a> <BR><BR>
<!-- a href="?act=RefreshAll">Обновить все файлы</a> <BR>-->
<a href="?act=Up_Loc">Обновить локализацию</a> <BR><BR>
<a href="?act=ShowFiles">Показать все файлы</a> <BR><BR>
<hr>
<br>
<?php

$uv = new SU_FlashVersionsUpdate();
$uv->getVersions();

if(!isset($_GET['act']))
{
	$_GET['act'] = 'default';
}

switch($_GET['act'])
{	
	case 'CheckOut':
		$uv->checkOut();
		//$uv->updateVersions();
		break;
		
	case 'RefreshAll':
		$uv->refreshAll();	
		$uv->updateVersions();
		break;	

	case 'Up_Loc':
		touch($_SERVER['DOCUMENT_ROOT'].'/ds/flash/localdata');	
		break;
		
	case 'ShowFiles':
		$uv->showFiles();
		break;
				
	case 'UpdateFile':
		$uv->updateCoosenFile($_GET['file_to_update']);
		$uv->updateVersions();
		$uv->showFiles();
		break;
}

?>
</body>
</html>