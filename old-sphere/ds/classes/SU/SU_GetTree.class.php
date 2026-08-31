<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_GetTree
{
	static public $tree = '';

	static private $dirArrs = array();

	static private $keysarr = '';

	static private $i = '0';

	static private $flag = false;
	
	//Корневой каталог поиска
	static private $rootFolder = '/';
	
	//Массив с расширениями, которые нас интересуют
	static private $expansionArr;

	static function Exec($rootFolder, $expansionArr)
	{
		assert(!empty($rootFolder));
		assert(is_array($expansionArr));
		
		self::$rootFolder = $rootFolder;
		self::$expansionArr = $expansionArr;
		
		self::$tree = '';
		self::$dirArrs[0] = '/';
		self::$keysarr = '/';
		
		self::$flag = false;

		self::GetTree(self::$rootFolder);
		ksort(self::$tree);
		return  self::$tree;
	}
	static function GetTree($dir)
	{
		$dh = opendir($dir);

		while(($f = readdir( $dh )) !== false)
		{
			if ($f != "." && $f != "..")
			{
				$path = $dir ."/" . $f;

				if( is_dir($path))
				{
					self::$keysarr .= "$f/";

					self::$i ++;
					self::$dirArrs[self::$i] = self::$keysarr;
					self::GetTree($path);
					self::$keysarr = self::$dirArrs[self::$i-1];
					self::$i --;
				}
				else {
					foreach (self::$expansionArr as $key => $extension)
					{
						if(strpos($f, '.'.$extension))
						{
							$a = self::$keysarr;
	
							if ($a == '/')
							{
								$p = self::$rootFolder.$a.$f;
							}else{
								$p = self::$rootFolder.$a."/".$f;
							}
							
							//Если нет, то как дату берем последнее изменение файла
							$fileTime = filemtime($p);
							$fileSize = filesize ($p);
							
							self::$tree[$a.$f]['time']= $fileTime;
							self::$tree[$a.$f]['size']= $fileSize;
						}
					}
				}
			}
		}
		closedir($dh);
	}

}

?>