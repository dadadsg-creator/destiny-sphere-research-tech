<?php 
//Скрипт заменяет одну строчку на другую во всем дереве каталогов
//Путь к файлам
$DirPath = "/home/www/htdocs/defo/ds";

$tree = SU_GetFolderTree::Exec($DirPath);
/*echo "<xmp>";
print_r($tree);
echo "</xmp>";*/

class SU_GetFolderTree
{
	static public $root;

	static public $tree;

	static private $dirArrs = array();

	static private $keysarr = '';

	static private $currentPath = '/';

	static protected $dirData = array();

	static private $i = '0';

	static function Exec($root)
	{
		self::$currentPath = '/home/www/htdocs/defo/ds';
		self::$dirArrs[0] = '/';
		self::$keysarr = '/';

		self::GetTree($root);
		self::ReplaceString($root);
		//ksort(self::$tree);
		return  self::$tree;
	}
	static function GetTree($dir)
	{
		$dh = opendir( $dir );

		while( ( $f = readdir( $dh ) ) !== false )
		{
			if ( $f != "." && $f != ".." )
			{
				$path = $dir ."/" . $f;

				if( is_dir( $path ) )
				{
					self::$keysarr .= "$f/";
					self::$i ++;
					self::$dirArrs[self::$i] = self::$keysarr;
					//self::$currentPath = $path;
					self::GetTree($path);
					self::$keysarr = self::$dirArrs[self::$i-1];
					self::$i --;
				}
				else {
					if(strpos($f, '.php'))
					{
						$pathTmp = explode('/', self::$currentPath);

						$endFolder = $pathTmp[count($pathTmp) - 1];

						$curPath = "/";

						//Убираем /home/www/htdocs/defo/ds
						/*for ($i = 6; $i <count($pathTmp); $i ++)
						{
						$curPath .= $pathTmp[$i]."/";
						}
						//$fileTime = "000";*/

						$a = self::$keysarr;

						self::$tree[]= $a.$f;
					}
				}
			}
		}
		closedir($dh);
	}

	static function ReplaceString($root)
	{
		$newStr = '<?php $CVS_Version =';
		$newStr .= " '";
		$newStr .= '$Revision: 1.2 $ ; $Date: 2007/09/11 08:12:43 $';
		$newStr .= "';\n";
		$countStrings = 0;

		//Проходимся по всем файлам
		foreach (self::$tree as $key => $file)
		{
			unset($f);
			$countStr = 0;
			$filename = $root.$file;
			$f = file($filename);

			//Если файл начинается не начинается с <?php
			if (strpos($f[0], "<?php") === false)
			{
				continue;
			}

			//Если файл содержит @Zend
			if (strpos($f[0], "@Zend") !== false)
			{
				continue;
			}

			$f[0] = $newStr;
			$newFileContent = implode("", $f);

			/*if (!$handle = fopen($filename, 'w')) {
				echo "Не могу открыть файл ($filename)";
				exit;
			}*/

			// Записываем $somecontent в наш открытый файл.
			if (file_put_contents($filename, $newFileContent) === FALSE) {
				echo "Не могу произвести запись в файл ($filename)";
				exit;
			}

			echo "Файл $filename перезаписан<br>";

			//fclose($handle);
		
		}
		unset($f);
		echo "<b>OK</b>";

	}

}
?>