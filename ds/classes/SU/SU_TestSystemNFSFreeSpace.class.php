<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemNFSFreeSpace extends SU_TestSystemBase
{			
	public function exec()
	{
		assert(key_exists('LOGING_DEBUG_LOGS_PATH', $GLOBALS));
		foreach($GLOBALS['LOGING_DEBUG_LOGS_PATH'] as $pathName => $path)
		{
			$this->_outString("Start test path [$pathName] : [$path]", 4);		
			
			$filesCount = 0;
			$filesSize = 0;
			
			if (!($handle = opendir($path))) 
			{
				$this->_outString("Cant open path $path <<<<<<<<<<<<<<<<< ERROR!", 8);	
				$this->_writeError('NFS', "Cant open path $path");
				continue;
			}
			
			//Сперва формируем список файлов
			while ($file = readdir($handle)) 
			{
	   			if($file == '.' || $file == '..' || is_dir($file)) 
	   			{
	   				continue;
	   			}

	   			$fileSize = filesize($path.$file);  	
	   			
			   	$filesCount++;
			   	$filesSize += $fileSize;
			   	
			   	if(filesize($path.$file) > 2000000000)
			   	{
			   		$this->_outString("Critical size of file [$file] = ".number_format($fileSize, 0, ".", " ")." <<<<<<<<<<<<<<<<< ERROR!", 8);
			   		$this->_writeError('NFS', "Critical size of file [$file] = ".number_format($fileSize, 0, ".", " ")." ");
			   	}
			}
			
			$msg = "FilesCount: ".number_format($filesCount, 0, ".", " ")." FilesSize: ".number_format($filesSize, 0, ".", " ");
			$this->_outString($msg, 8);
						
			$freeSpace = disk_free_space($path);		
			if($freeSpace < 200000000)
			{
				$this->_outString("Critical free space = ".number_format($freeSpace, 0, ".", " ")." <<<<<<<<<<<< ERROR!", 8);
				$this->_writeError('NFS', "Critical free space");
			}else{
				$this->_outString("Free space = ".number_format($freeSpace, 0, ".", " "), 8);
			}
		}
	}
}

?>