<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2007/09/11 09:46:46 $';

ini_set('memory_limit', '16M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);
/*********** BEGIN WORK AREA **************/

echo "<h1>TEST CLASSES VERSIONS</h1>";

//Путь к файлам:

//путь к девелоперской версии
$DirPath = $_SERVER['DOCUMENT_ROOT']."/ds";

//путь ко временному файлу, в котором сохранаяем файлы cvs
$TempFile = $_SERVER['DOCUMENT_ROOT']."/ds/utils/files/TestClassesVersion.txt";
$TempLink = $_SERVER['DOCUMENT_ROOT']."/ds/utils/files/TestClassesVersion.txt";
//$TempLink = "/ds/utils/files/TestClassesVersion.txt";

echo "TempLink = ".$TempFile;

//Потом переделать!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//путь к релизу
$release = $_SERVER['DOCUMENT_ROOT']."/ds";

if (key_exists('generateFile', $_REQUEST))
{
	//Выводим кнопку back
	echo '
	<form action="" method="post" enctype="multipart/form-data">
	<input type="submit" value="Back to index">
	</form>';

	/*echo "<pre>";
	print_r($_REQUEST);
	echo "</pre>";*/

	//Проверяем, что файл существует и доступен для записи.
	assert(is_writable($TempFile)) ;
	//echo "TempFilenk = ".$TempFile;

	//Делаем файл пустым
	if (!$handle = fopen($TempFile, 'w')) {
		echo "Не могу открыть файл ($TempFile)";
		exit;
	}


	if (fwrite($handle, "") === FALSE) {
		echo "Не могу произвести запись в файл ($filename)";
		exit;
	}
	fclose($handle);

	//Записываем в файл имена файлов
	if (!$handle = fopen($TempFile, 'a')) {
		echo "Не могу открыть файл ($TempFile)";
		exit;
	}

	foreach ($_REQUEST['resultFiles'] as $fileN => $d)
	{
		$str = $fileN."\n";
		//echo $str."<br>";
		if (fwrite($handle, $str) === FALSE) {
			echo "Не могу произвести запись в файл ($filename)";
			exit;
		}
	}
	fclose($handle);

	echo '<a title="Save" href="'.$TempLink.'"><font size=4><b>Get file</b></font></a><br><br>';
} else {


	if (key_exists('compareVersion', $_REQUEST) OR key_exists('compareRelease', $_REQUEST))
	{
		//Выводим кнопку back
		echo '
	<form action="" method="post" enctype="multipart/form-data">
	<input type="submit" value="Back to index">
	</form>';

		$action = '';
		if (key_exists('newCVS', $_REQUEST))
		{
			$action = 'newCVS';
		}
		if (key_exists('newVersion', $_REQUEST))
		{
			$action = 'newVersion';
		}

		assert($action != '');

		if (key_exists('cvsFile', $_REQUEST))
		{
			$cvsFile = $_REQUEST['cvsFile'];
		}
		elseif (key_exists('tmp_name', $_FILES['listing']))
		{
			//Проверяем, что файл существует и доступен для записи.
			if(!is_writable($TempFile))
			{
				echo "Can't write to file $TempFile!";
				exit;
			}
			if(!file_exists($_FILES['listing']['tmp_name']))
			{
				exit("<h2><font color=red>Choose File!</font></h2>");
			}

			if (move_uploaded_file($_FILES['listing']['tmp_name'], $TempFile)) {
				//print "File is valid, and was successfully uploaded. ";
			} else {
				exit("Cant't upload file");
			}
		}
		else
		{
			exit("Chose CVS file");
		}
	}

	if (key_exists('compareVersion', $_REQUEST))
	{
		echo "<h2>Compare developers VERSION with CVS</h2>";

		echo '
	<form action="" method="post" enctype="multipart/form-data">
	<input type="HIDDEN" name="compareVersion" value="compare">	
	<input type="HIDDEN" name="cvsFile" value="'.$_FILES['listing']['tmp_name'].'">	
	<table border="0"><tr><td valign="top">
	<input type="submit" name="newCVS" value="&nbsp;&nbsp;&nbsp;Find new files on CVS&nbsp;&nbsp;&nbsp;"><br><br>
	</td><td>&nbsp;&nbsp;&nbsp;&nbsp;
	</td><td>
	</td><td valign="top">
	<input type="submit" name="newVersion" value="&nbsp;&nbsp;&nbsp;Find new files on Version&nbsp;&nbsp;&nbsp;"><br><br>
	</form>
	</td></tr></table></td></tr></table>
	';

		echo '	<table border="0">
				<tr>
				<td bgcolor="#669999">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>- Folder</td></tr><tr>
				<td bgcolor="#CCE6FF">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>- File not exist</td></tr><tr>
				<td bgcolor="#CCFFE6">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>- New File(CVS variable in version file exist)</td></tr><tr>
				<td bgcolor="#FFFFCC">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>- New File(CVS variable in version file not exist)</td>
				</tr></table>';
		
		SU_TestClassesVersions::Exec($TempFile, $DirPath, $_REQUEST['timedelta'], $action);
	} elseif (key_exists('compareRelease', $_REQUEST))
	{
		echo "<h2>Compare CVS with RELEASE</h2>";
		echo '
	<form action="" method="post" enctype="multipart/form-data">
	<input type="HIDDEN" name="compareRelease" value="compare">	
	<input type="HIDDEN" name="cvsFile" value="'.$_FILES['listing']['tmp_name'].'">	
	<table border="0"><tr><td valign="top">
	<input type="submit" name="newCVS" value="&nbsp;&nbsp;&nbsp;Find new files on CVS&nbsp;&nbsp;&nbsp;"><br><br>
	</td><td>&nbsp;&nbsp;&nbsp;&nbsp;
	</td><td>
	</td><td valign="top">
	<input type="submit" name="newVersion" value="&nbsp;&nbsp;&nbsp;Find new files on Release&nbsp;&nbsp;&nbsp;"><br><br>
	</form>
	</td></tr></table></td></tr></table>
	';

	echo '		<table border="0">
				<tr>
				<td bgcolor="#669999">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>- Folder</td></tr><tr>
				<td bgcolor="#CCE6FF">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>- File not exist</td></tr><tr>
				<td bgcolor="#CCFFE6">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>- New File(CVS variable in release file exits and determine)</td></tr><tr>
				<td bgcolor="#FFCCFF">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>- New File(CVS variable in release file exits, but undefined)</td></tr><tr>
				<td bgcolor="#FFFFCC">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>- New File(CVS variable in release file not exist)</td>
				</tr></table>';
		
		SU_TestClassesRelease::Exec($release, $TempFile, $action);
	}
	else
	{
?>	<body text="Blue">
	<table border="0"><tr><td>
	<form action="" method="post" enctype="multipart/form-data">
	<table border="0"><tr><td>
	Load CVS dir listing<br>
	(command: 'dir /s >ClasseListing.txt'):	<br><br>
	<input type="file" size="30" name="listing">
	</td><td>&nbsp;&nbsp;&nbsp;&nbsp;
	</td><td>
	Version TimeDelta in hours:<br><br><br><input type="text" name='timedelta' size="30" value=0>
	</td></tr></table></td></tr></table>
	
	<input type="HIDDEN" name='action' value='compare' ><br>	
	<input type="hidden" name="newVersion" value="Find new files on Version"><br>
	<input type="submit" name="compareVersion" value="Compare developers VERSION with CVS"><br><br>
	<input type="submit" name="compareRelease" value="           Compare CVS with RELEASE            "><br>
	</form>
	
	</body>
<?
	}
}

?>
