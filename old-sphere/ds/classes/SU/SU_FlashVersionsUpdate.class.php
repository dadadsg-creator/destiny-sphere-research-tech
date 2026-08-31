<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_FlashVersionsUpdate extends SU_FlashVersions 
{
	public $files_old;	// Резервная копия массива с версиями фалов, заполняется после checkOut'a

	function __construct()
	{
		$this->flashpath = '../'.$this->flashpath;
		$this->setINIFile($this->flashpath, $this->ininame);		
	}

	/*
	 ** Проверяем на обновления
	 */	
	function checkOut()
	{
		$this->addLog("Checkout new files");
		$this->files_old = $this->files;
		
		foreach($this->files as $filename => $params)
		{
			$this->addLog("Checking: ".$filename);
			$time = filemtime($this->flashpath.$filename);

			if($time > $params['timestamp'])
			{
				$this->addLog("Updating...".$filename);
				$newFileName = $this->updateFile($filename);
				$this->files[$filename]['version']++;
				$this->files[$filename]['timestamp'] = @filemtime($this->flashpath.$newFileName);
				
			}else{
				$this->addLog("<font color='green'>$filename</font>");

			}
		}
	}
	
	/*
	 ** Выводим список всех файлов
	 */	
	
	function ShowFiles()
	{
		$this->files_old = $this->files;
		
		echo '
						<table border="0">
								';	
		
		foreach($this->files as $filename => $params)
		{
			echo '<tr><td valign="top">
								';	

			$time = filemtime($this->flashpath.$filename);

			if($time > $params['timestamp'])
			{
				//Помечаем файл
				echo "<font color='red'>$filename</font>";
			}else{
				echo "<font color='green'>$filename</font>";

			}
			
			echo '</td><td>';
			
			//Добавляем кнопочку
			echo '<form  action="" method="GET">
			<input type="hidden" name="file_to_update" value="'.$filename.'">
			<input type="hidden" name="act" value="UpdateFile">
			<input type="submit" value="Update">
			</form>';

			echo '</td></tr>
						';	
			
		}
		
		echo '
						</table>';	
	}
	
	/*
	 ** Обновляем выбранный файл
	 */	
	
	function updateCoosenFile($filename)
	{
		if (empty($filename)) {
			echo "Error!!!";
			exit;
			
		}
		
		$this->addLog("<b>Updating...".$filename."</b>");
		$newFileName = $this->updateFile($filename);
		$this->files[$filename]['version']++;
		$this->files[$filename]['timestamp'] = @filemtime($this->flashpath.$newFileName);
	}
	
	
	/*
	** Проверяем на обновления
	*/	
	function refreshAll()
	{		
		$this->addLog("Refresh all files disabled");
return;
		$this->files_old = $this->files;

		foreach($this->files as $filename => $params)
		{
			$newFileName = $this->updateFile($filename);
			$this->files[$filename]['version']++;
			$this->files[$filename]['timestamp'] = @filemtime($this->flashpath.$newFileName);
		}
	}
	
	
	/*
	** Работает c файлами (переименновывает/копирует/удаляет)
	** возвращает  имя нового файла
	*/
	function updateFile($filename)
	{
		$newFileName = $this->makeNewName($filename, ($this->files[$filename]['version'] + 1));		
		$oldFileName = $this->makeNewName($filename, $this->files[$filename]['version']);
		
		if(file_exists($this->flashpath.$newFileName))
		{
			$this->addLog("Delete allready existing file with new version...");
			$del = unlink ($this->flashpath.$newFileName) or $this->error('Cant delete already exists File');
			$this->addLog("Done!");
		}

		$this->addLog("Coping new version file...");
		//echo $this->flashpath.$newFileName."|old=".$this->flashpath.$filename."<br>";
		$cop = copy(($this->flashpath.$filename), ($this->flashpath.$newFileName)) or $this->error('Cant copy file');
		touch($this->flashpath.$filename);
		$this->addLog("Done!");
				
		if(file_exists($this->flashpath.$oldFileName))
		{
			$this->addLog("Delete old version file...");
			$del = unlink ($this->flashpath.$oldFileName) or $this->error('Cant delete file');
			$this->addLog("Done!");
		}
		
		return $newFileName;
	}
	
	
	/*
	** Сохраняем информацию о версиях в ini-файл
	*/
	function updateVersions()
	{
		$ini_data = $this->makeINI();
		
		// Логируем на всякий пожарный
		SU_DebugTools::LogAll($ini_data, 'FlashUpdate.log');
		
		$this->addLog("Open .ini file for writing new versions");
		
		$write = file_put_contents($this->inipath.$this->ininame, $ini_data);
		
		if ($write > 0) {
			$this->addLog('Version .ini file Updated!');
		} else {
			$this->addLog('Version .ini file NOT updated!');
		}
		
		/*$fh = fopen($this->inipath.$this->ininame, 'wt') or $this->error("Can't open .ini file");
		
		$this->addLog("Lock .ini file for writing new versions");
		
		if(flock($fh, LOCK_EX)) 
		{
			$this->addLog("Write new versions");
   			fwrite($fh, $ini_data);
    			
   			$this->addLog("Unlock .ini file");
   			flock($fh, LOCK_UN);
    		
   			$this->addLog('Version .ini file Updated!');
		} else {
			$this->addLog("Can't lock file!!!");
			$this->addLog('Version .ini file NOT updated!');
		}
		
		$this->addLog("Unlock .ini file");
		fclose($fh);*/
	}
	
	
	/*
	** Создает и возвращает содержимое для записи в ini-файл
	*/
	function makeINI()
	{
		$data = '';
		
		foreach($this->files as $sectionName => $sectionParams)
		{
			$data .= "[".$sectionName."]\n";
			$data .= $this->makeINISection($sectionParams)."\n";
		}
		
		return $data;
	}
	
	
	/*
	** Создает и возвращает строки секции для ini-файла
	**
	** Обрабатывает значения в секции
	*/
	function makeINISection($sectionParams)
	{
		$data = '';
		
		foreach($sectionParams as $name => $value)
		{
			$data .= $name." = ".$value."\n";
		}
		
		return $data;
	}
	
	function addLog($mess)
	{
		echo $mess."<br>\n";
		flush();
	}
}

?>