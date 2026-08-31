<?php
class SU_GetFolderTree
{
	static public $root;
	
	static public $tree;
	
	static private $dirArrs = array();

	static private $currentPath = '/';
	
	static protected $dirData = array();
	
	static private $i = '0';

	static function Exec($root)
	{
		self::$currentPath = '/home/www/htdocs/defo/ds';
		self::$dirArrs[0] = '/home/www/htdocs/defo/ds';
		
		self::GetTree($root);
//print_r(self::$dirData);	
echo "<xmp>";	
print_r(self::$tree);		
echo "</xmp>";	
exit;
//kvan rodil devchonke
		return  self::$tree;
	}
	static function GetTree($dir)
	{
//echo $dir;	
//echo "<br>";
	
		$dh = opendir( $dir );

		while( ( $f = readdir( $dh ) ) !== false )
		{
			if ( $f != "." && $f != ".." )
			{
				$path = $dir ."/" . $f;

				if( is_dir( $path ) )
				{
					self::$i ++;
					self::$dirArrs[self::$i] = $path;
					self::$currentPath = $path;
					self::GetTree($path);
					self::$tree[self::$dirArrs[self::$i-1]][] = self::$dirArrs[self::$i];
					self::$i --;
					self::$currentPath = self::$dirArrs[self::$i];
				}
				else {
					if(strpos($f, '.php'))
					{
						$pathTmp = explode('/', self::$currentPath);

						$endFolder = $pathTmp[count($pathTmp) - 1];

						$curPath = "/";

						//Убираем /home/www/htdocs/defo/ds
						for ($i = 6; $i <count($pathTmp); $i ++)
						{
							$curPath .= $pathTmp[$i]."/";
						}
						$fileTime = filemtime(self::$currentPath."/".$f);
						self::$dirData[$curPath][$f] = $fileTime;
					}
				}
			}
		}
		closedir($dh);
	}
}

?>