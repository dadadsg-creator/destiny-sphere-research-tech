<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestClassesRelease
{
	static private $filesCVS = array();

	static protected $CVSData = array();

	static private $i = '0';

	static protected $dirData = array();

	static protected $currentVal = array();

	static protected $previousVal = array();

	static protected $pathArr = array();

	static private $timeDelta = 0;

	static private $type = '';

	static private $out = '';

	static public function Exec($releaseFileName, $listingFileName, $action)
	{
		assert(file_exists($listingFileName));
		assert(is_dir($releaseFileName));

		self::$CVSData = self::_getCVSFiles($listingFileName);

		//Получам дерево каталогов
		self::$dirData = SU_TestClassesVersionsGetFolderTree::Exec($releaseFileName, true);

		//Преобразуем массивы к одинаковому виду
		self::$CVSData = self::_convertCVS(self::$CVSData);
		self::$dirData = self::_convertdir(self::$dirData);

		//self::$timeDelta = $timeDelta;
		//self::$type = $timeDelta * 60 * 60;

		if ($action == 'newCVS')
		{
			echo '<h2>New files on CVS</h2>';
			$arr = self::_compareVersions(self::$CVSData, self::$dirData);
			self::_outNewFiles($arr, 'CVS');
		}

		if ($action == 'newVersion')
		{
			echo '<h2>New files on Release</h2>';
			$arr = self::_compareVersions(self::$dirData, self::$CVSData);
			self::_outNewFiles($arr, 'Version');
		}
		echo 'TEST COMPLIT.';
	}


	static private function _compareVersions($master, $slave)
	{
		assert(is_array($master));
		assert(is_array($slave));

		$key = 0;

		foreach ($master as $fileName => $data)
		{
			if(!key_exists($fileName, $slave))
			{
				if (key_exists('CVS_Version', $data))
				{
					$CVS_Version = $data['CVS_Version'];;
				}else {
					$CVS_Version = "";
				}
				$arr[$key]['file'] = $fileName;

				$arr[$key]['notExist'] = "YES";

				$arr[$key]['maxDate'] = date ("Y-m-d H:i:s", $data['time']);
				$arr[$key]['minDate'] = "-";

				$arr[$key]['zend'] = $data['zend'];

			} else {

				$slaveChangeTime = $slave[$fileName]['time'];
				$masterChangeTime = $data['time'];

				if (key_exists('CVS_Version', $data))
				{
					$CVS_Version = $data['CVS_Version'];
				}

				if (key_exists('CVS_Version', $slave[$fileName]))
				{
					$CVS_Version = $slave[$fileName]['CVS_Version'];
				}



				if($masterChangeTime > $slaveChangeTime)
				{
					$arr[$key]['file'] = $fileName;
					$arr[$key]['CVS_Version'] = $CVS_Version;
					$arr[$key]['notExist'] = "NO";

					$arr[$key]['maxDate'] = date ("Y-m-d H:i:s", $masterChangeTime);
					$arr[$key]['minDate'] = date ("Y-m-d H:i:s", $slaveChangeTime);
					$arr[$key]['zend'] = $data['zend'];
				}
			}
			$key ++;

		}

		return $arr;
	}

	static private function _getCVSFiles($listingFileName)
	{
		assert(file_exists($listingFileName));

		$data = file($listingFileName);

		$allowStart = array('0','1','2','3','4','5','6','7','8','9');

		$currentCVSPath = '/';
		$cvsData = array();

		foreach($data as $k=>$str)
		{

			//Убрали лишние пробелы
			while (ereg('  ', $str))
			{
				$str = str_replace('  ', ' ', $str);
			}

			if(in_array($str[0], $allowStart))
			{//Это файл
				$arrayTMP = explode(' ', $str);

				foreach($arrayTMP as $k=>$v)
				{
					$arrayTMP[$k] = trim($v);
				}

				if($arrayTMP[3] == '.' || $arrayTMP[3] == '..')
				{
					continue;
				}

				if($arrayTMP[2] == '<DIR>')
				{
					continue;
				}

				$fileTime = strtotime($arrayTMP[0].' '.$arrayTMP[1]);

				$fileName = $arrayTMP[3];

				if(!strpos($fileName, '.php'))
				{
					continue;
				}

				$cvsData[$currentCVSPath][$fileName] =  $fileTime;

			}else{//Это новая директория
				if(!strpos($str, 'Directory of '))
				{
					continue;
				}
				$arrayTMP = explode(' ', trim($str));

				foreach($arrayTMP as $k=>$v)
				{
					$arrayTMP[$k] = trim($v);
				}


				$pathTmp = explode('\\', $arrayTMP[2]);

				$endFolder = $pathTmp[count($pathTmp) - 1];

				if($endFolder == 'CVS')
				{
					continue;
				}

				$currentCVSPath = "/";

				//Убираем  C:\ServerX и заменяем "\" на "/"
				//for ($i = 2; $i <count($pathTmp); $i ++)
				for ($i = 3; $i <count($pathTmp); $i ++)
				{
					$currentCVSPath .= $pathTmp[$i]."/";
				}
			}
		}
		//sort($cvsData);
		ksort($cvsData);

		return $cvsData;
	}

	static private function _getDirData($folder, $filesPath)
	{
		assert(is_dir($filesPath));
		$filesPath = $filesPath.$folder;
		//echo $filesPath;
		$handle = opendir($filesPath);

		assert($handle);

		$currentPathName = $filesPath;
		$filesData = array();

		while (false !== ($file = readdir($handle)))
		{
			if($file == '.' || $file == '..')
			{
				continue;
			}
			if($file[0] == '.')
			{
				continue;
			}
			if(!strpos($file, '.php'))
			{
				continue;
			}
			if(is_dir($subDirName))
			{
				continue;
			}
			else {
				$fileTime = filemtime($filesPath.$file);

				if(strpos($file, '.php'))
				{
					$filesData[$file] = $fileTime;
				}
			}
		}
		//sort($filesData);
		ksort($filesData);

		return $filesData;
	}

	static private function _convertdir($Array)
	{
		foreach ($Array as $folder => $fileArr)
		{
			foreach ($fileArr as $file => $fileAttr)
			{
				$newArr[$folder.$file]['time'] = $fileAttr['time'];
				$newArr[$folder.$file]['zend'] = $fileAttr['zend'];
				$newArr[$folder.$file]['CVS_Version'] = $fileAttr['CVS_Version'];
			}
		}
		return $newArr;
	}

	static private function _convertCVS($Array)
	{
		foreach ($Array as $folder => $fileArr)
		{
			foreach ($fileArr as $file => $time)
			{
				$newArr[$folder.$file]['time'] = $time;
				$newArr[$folder.$file]['zend'] = "-";
			}
		}
		return $newArr;
	}

	static private function _outNewFiles($tree, $masterName)
	{
		$nbsp = "";
		self::$i = 0;
		$counFiles = 0; //Счетчик файлов
		self::$pathArr[0] = '/';


		echo "<table border=1 bgcolor='#669999' bordercolor='#669999'>";
		echo "<tr><th>";
		echo "File";
		echo "</th>";

		echo "<th>";
		echo "Encript";
		echo "</th>";

		echo "<th>";

		if ($masterName == 'CVS')
		{
			echo "CVS data";
			echo "</th>";
			echo "<th>";
			echo "Version data";
			echo "</th>";
		}

		if ($masterName == 'Version')
		{
			echo "Version data";
			echo "</th>";
			echo "<th>";
			echo "CVS data";
			echo "</th>";
		}

		echo "<th>";
		echo "Time delta";
		echo "</th>";

		echo "<th>";
		echo "Include in result file";
		echo "</th></tr>";

		echo '<form action="" method="post">';
		foreach ($tree as $key => $data)
		{
			$path = explode("/", $data['file']);

			foreach ($path as $key => $string)
			{
				self::$i ++;
				self::$currentVal[self::$i] = $string;
				if (self::$currentVal[self::$i] != self::$previousVal[self::$i])
				{
					if(strpos($string, '.php'))
					{
						if ($data['notExist'] == "YES")
						{
							$color = '#CCE6FF';
						} elseif ($data['CVS_Version'] == "UNDEFINED")
						{
							$color = "#FFCCFF";
						} elseif ($data['CVS_Version'] == "NO")
						{
							$color = "#FFFFCC";
						} else
						{
							$color = "#CCFFE6";
						}

						echo "<tr bgcolor=$color><td>";
						echo $nbsp.$string;
						echo "</td>";

						echo "<td>";
						echo $data['zend'];
						echo "</td>";

						echo "<td>";
						echo $data['maxDate'];
						echo "</td>";

						echo "<td align=center>";
						echo $data['minDate'];
						echo "</td>";

						echo "<td align='right'> ";
						//Вычисляем разность двух дат
						$maxData = strtotime($data['maxDate']) - strtotime($data['minDate']);
						echo self::format_time($maxData);
						echo " </td>";

						echo "<td align='center'>";
						echo '<input type=checkbox name=resultFiles['.$data['file'].'] value=t checked>';
						echo "</td></tr>";

						$counFiles ++; //Увеличиваем счетчик файлов
					} else {
						echo "<tr><td>";
						echo $nbsp."<b>".$string."</b>";
						echo "</td>";
						echo "<td>";
						echo "&nbsp";
						echo "</td>";
						echo "<td>";
						echo "&nbsp";
						echo "<td>";
						echo "&nbsp";
						echo "</td>";
						echo "<td>";
						echo "&nbsp";
						echo "</td>";
						echo "</td></tr>";
					}
				}
				self::$previousVal[self::$i] = $string;
				$nbsp .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			}
			self::$i = 0;
			$nbsp = '';
		}
		//Выводим обшее количество файлов
		echo "<tr><td colspan=4>";
		echo "<b>Total Files:&nbsp;&nbsp;&nbsp;&nbsp;";
		echo $counFiles;
		echo "</b></td></tr>";

		echo "</table><br>";
		echo '<input type="submit" name="generateFile" value="Generate file">';
		echo '</form>';
		echo "<br>";
		echo "<br>";
	}

	function format_time($sec) {
		$a = array(
		86400 => ' day', // количество секунд в сутках
		3600  => ' hour', // количество секунд в часе
		60    => ' min', // количество секунд в минуте
		//1     => ' sec' // количество секунд в секунде
		);
		$out = '';
		foreach ($a as $k=>$v) {
			$tmp = floor($sec/$k);
			$out .= ($tmp?$tmp.$v.' ':'');
			$sec -= $tmp*$k;
		}
		return $out;
	}

}
?>

