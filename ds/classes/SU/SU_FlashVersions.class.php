<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_FlashVersions
{
	
	public $flashpath = "flash/";
	
	public $ininame = "version.ini";	// filename	
	
	public $inipath;

	public $files;	/* Массив с данными о версиях файлов
			**
			** Формат:
			**	$files
			**		[$basefilename]			// localdata || bild_bio.lib
			**			['version']	= $version;
			**			['timestamp']	= $timestamp;
			*/
	
		
	function __construct()
	{
		
		$this->setINIFile($this->flashpath, $this->ininame);
	}
	
	
	/*
	** Устанавливаем имя и путь до ini-файла
	*/
	function setINIFile($path, $file)
	{	
//SU_DebugTools::LogAll($path, 'Annita.log');
//SU_DebugTools::LogAll($file, 'Annita.log');
		if( file_exists($path.$file) )
		{		
			$this->inipath = $path;
			$this->ininame = $file;						
		}else{		
			$this->error("path 2 .ini file doesn't exists");
		}
	}
	
	
	/*
	** Загружаем информацию о версиях
	*/
	function getVersions()
	{
		$path = $this->inipath.$this->ininame;
		
		$this->files = parse_ini_file($path, true) or $this->error('.ini file not found');
		
		SU_FlashVersionControlInUseraction::StoreFilesVersionInSession($this->files);
	}
	
	public function getFiles()
	{
		return $this->files;
	}
	
	/*
	** Сзодает из базвого имени файла и номера версии новое имя фалйа
	** !!! не переименновывает файл
	*/
	function makeNewName($filename, $version)
	{
		$pos = strpos($filename, '.');
					
		$fileNameArr = array
		(
			substr($filename, 0, $pos), 
			substr($filename, $pos)
		);
		
		return $fileNameArr[0].'-'.$version.$fileNameArr[1];
	}
	
	
	/*
	** Создает и возвращает XML документ
	** 
	** Формат:
		<version>
			<file name="localdata" version="23228"/>
			<file name="name.swf" version="108"/>
		</version>
	*/
	function makeXML(){
		
		if (version_compare(PHP_VERSION,'5','>=')){
	
			import ('domXML');
		}
		
		$xmldoc = domxml_new_xmldoc ( "1.0" );
		
		$root = $xmldoc->add_root ( "version" );
		
		if ( sizeof ( $this->files ) > 0 ) {
		
			foreach($this->files as $libname => $params){
					
				$ch = $root->new_child ( "file" );
				$ch->set_attribute ( "name", 	$libname );
				$ch->set_attribute ( "version",	$params['version'] );
			}
		}
		
		return $xmldoc->dump_mem();
	}
	
	
	/*
	** Обрабатываем ошибку
	*/
	function error($str){
		
		die($str);
	}
}

?>