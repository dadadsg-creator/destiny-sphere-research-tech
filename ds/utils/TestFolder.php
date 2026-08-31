<?php
ini_set('memory_limit', '128M');
set_time_limit(0);

require_once '../Main.php';

class CheckSecurity
{
	static private $rootFolder;
	static private $dirs = array();
	static private $action = 'index';
	static private $deletFile = true;
	static private $testIndex = false;
	static private $htaccess;
	
	static private $options = array(
		'method' => 'GET', 
		'header' => '', 
		'user_agent' => '', 
		'content' => '', 
		'proxy' => '', 
		'request_fulluri' => '', 
		'max_redirects' => '',
		'timeout' => 10
	);
	
	static function Exec()
	{
		self::_GetParams($_REQUEST);
		
		switch (self::$action)
		{
			case 'index':
				self::_ShowIndex();
				break;
			case 'test':
				self::_Test();
				break;
		}
	}
	
	static private function _ShowIndex()
	{
		?>
		<h2>Test Folder</h2>
		<table border="0">
		<form action="" method="GET">
		<tr>
			<td>
				Path:
			</td>
			<td>
				 <input type="text" name="path" value="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>">
			</td>
		</tr>
		<tr>
			<td>
				Test index:
			</td>
			<td>
				  <input name="testIndex" type="checkbox">
			</td>
		</tr>
		<tr>
			<td>
				Delete file:
			</td>
			<td>
				  <input name="deleteFile" type="checkbox" checked>
			</td>
		</tr>
		<tr>
			<td>
				<input type="hidden" name="action" value="test">
				<input type="submit" value="Exec">
			</td>
		</tr>
		</form>
		</table>
		<?php
	}
	
	static private function _Test()
	{
		$login  = 'developmer';
		$password  = '331311CreationWorld';
		
		self::$htaccess = base64_encode($login.":".$password);
		
		//Проверяем существование пути
		if (!is_dir(self::$rootFolder)) {
			echo "<h2>Folder ".self::$rootFolder." not exist!</h2>";
			exit;
		}
		
		//Получаем список всех каталогов
		self::$dirs = array();
		self::_GetDirs(self::$rootFolder);
		
		if (empty(self::$dirs)) {
			echo "Error: Can't get dirs!";
			exit;
		}
		
		//То, что будем записывать в файл
		$string = '<?php echo "Hello World!" ?>';
		$opts = array('http' => self::$options);
		$context  = stream_context_create($opts);

		echo "<br><b>Initial dir: ".self::$rootFolder."</b><br>";
		echo "<b>Delete file: ";
		if (self::$deletFile === true) {
			echo 'Yes';
		} else {
			echo 'No';
		}
		echo "</b><br>";
		echo "<b>Test index: ";
		if (self::$testIndex === true) {
			echo 'Yes';
		} else {
			echo 'No';
		}
		echo "</b><br><br>";
		
		
		//Проходимся по каждому каталогу
		foreach (self::$dirs as $key => $dir)
		{
			if (self::$testIndex === true) {
				//Проверка на то, не выдает ли нам каталог список файлов
				$url = str_replace(self::$rootFolder, constant('GAME_HOST'), $dir);
				$result = @file_get_contents($url, false, $context, 0, 100000);
				
				if (strpos($result, 'Index of')) {
					echo "<font color='#8B0000'>$url <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< Show files list!!!!!!</font><br>";
				}	
			} 
			
			//Пытаемся записать туда файл
			if (!$handle = @fopen($dir.'/security_test.php', 'w')) {
				echo "<font color='#2E8B57'><b>$dir</b> - Can't create file<font><br>";
			} else {

				//Пытаемся записать в файл код
				if (fwrite($handle, $string) === FALSE)
				{
					echo "<font color='#2E8B57'><b>$dir</b> - File has created but can't write to it</font><br>";
				} else {
					//Пытаемся выполнить сценарий
					$url = str_replace(self::$rootFolder, constant('GAME_HOST'), $dir).'/security_test.php';
					$result = self::_GetContent($url);

					SU_DebugTools::LogAll($result, 'Annita.log');

					if (stripos($result, '<?php echo "Hello World!" ?>') !== false) {
						SU_DebugTools::LogAll($result, 'Annita.log');
						echo "<font color='#B03060'><b>$dir</b> <<<<<<<<<<<<<<<<<<<< File has created but not executed<br>";
					} elseif (stripos($result, "Hello World!") !== false) {
						echo "<font color='red'><b>$dir</b> <<<<<<<<<<<<<<<<<<<<<<< File has created and exesute!!!!</font><br>";
					} else {
						echo "<font color='#8B0000'><b>$dir</b> <<<<<<<<<<<<<<<<<<<<<<< Can't get file!!!!</font><br>";
					}
				}

				//Если в настройках стоит удалять за собой файл
				if (self::$deletFile === true) {
					unlink($dir.'/security_test.php');
				}
			}
		}
		echo "<br>_____________________________END____________________________________<br>";
	}
	
	static private function _GetParams($data)
	{
		if (key_exists('action', $data)) 
		{
			self::$action = $data['action'];
		}
		
		if (key_exists('path', $data)) 
		{
			self::$rootFolder = $data['path'];
		}
		
		if (key_exists('deleteFile', $data)) 
		{
			self::$deletFile = true;
		} else {
			self::$deletFile = false;
		}
		
		if (key_exists('testIndex', $data)) 
		{
			self::$testIndex = true;
		} else {
			self::$testIndex = false;
		}
	}
	
	
	static private function _GetContent($url)
	{
		$getContent = new SU_HTTPGetContent();
		$getContent->setUrl($url);
		$getContent->setMethod("GET");
	
		//Получаем содержимое
		$getContent->Start();
		$headers = $getContent->GetResponseHeaders();

		if (stripos($headers, 'Authorization Required') !== false){
			$getContent = new SU_HTTPGetContent();
			$getContent->setUrl($url);
			$getContent->setMethod("GET");		
			$getContent->setHeader("Authorization", "Basic ".self::$htaccess);
			$getContent->Start();
		}
				
		$content = $getContent->GetResponseContent();
		
		return $content;
	}
	
	static private function _GetDirs($dir)
	{
		$dh = opendir( $dir );

		while( ( $f = readdir( $dh ) ) !== false )
		{
			if ( $f != "." && $f != ".." )
			{
				$path = $dir ."/" . $f;
			
				
				if( is_dir( $path ) )
				{
					self::$dirs[] = $path."/";

					self::_GetDirs($path);
				}
			}
		}
		closedir($dh);
	}
}

CheckSecurity::Exec();
?>