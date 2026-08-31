<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/10/24 10:18:50 $';

include('SU_DebugTools.class.php');
/**
 * Класс для просмотра логфайлов
 */

class SU_LogView
{
	private $pathes = null;
	private $path = null;
	private $request = null;
// 100000
	private static $viewDelta = 300000;
	private $msg = '';
	private static $packTarget = "LogViewDownLoad.gzip";
	private $queryStr = '';
	private $noDelFile=array();
	private $freqOpenFile=array();

	public function __construct($pathes)
	{
		assert(is_array($pathes) && count($pathes)>0);
		
		$this->pathes = $pathes;
	
		foreach($this->pathes as $key => $path)
		{
			$_SESSION['pathActive'][$key] = isset($_SESSION['pathActive'][$key]) ? $_SESSION['pathActive'][$key] : 0;
		}

		$this->_getPackFileName();
	}

	public function main($request)
	{
		//SU_DebugTools::LogAll(date('Y-m-d H:i:s') . '; class: ' . __CLASS__ . ', method: ' . __METHOD__ . ' on line ' . __LINE__ . "\n");
		$this->_invariant();
		assert(is_array($request));

		$this->request = $request;
		$this->path = @$request['dir'];

		$this->queryStr = $_SERVER['QUERY_STRING'];

		
		switch ($this->_getExternalParam('action'))
		{
			case 'activate':
				$_SESSION['pathActive'][$this->_getExternalParam('num')] = !$_SESSION['pathActive'][$this->_getExternalParam('num')];
				$this->queryStr = '';
			case 'list':
				$out = $this->_showList();
				break;
			case 'show':
				$part = $this->_getExternalParam('part') ? $this->_getExternalParam('part') : 1;
				$out = $this->_showLog($this->_getExternalParam('file'), $part);
				break;
			case 'show_full':
				$a = self::$viewDelta;
				self::$viewDelta = null;
				$out = $this->_showLog($this->_getExternalParam('file'));
				self::$viewDelta = $a;
				break;
			case 'reset':
				$this->_resetLog($this->_getExternalParam('file'));

				if($this->_getExternalParam('after') == 'show')
				{
					$out = $this->_showLog($this->_getExternalParam('file'));
				}else{
					$out = $this->_showList();
				}
				break;
			case 'pack':
				if(file_exists(self::$packTarget))
				{
					unlink(self::$packTarget);
				}

				$this->_packFile($this->_getExternalParam('file'), self::$packTarget);

				if(file_exists(self::$packTarget))
				{
					//$this->msg = "Упаковка прошла успешно. Скачать архив <a href=".self::$packTarget.">ЗДЕСЬ</a> Размер: ".number_format(filesize(self::$packTarget), 0, ".", " ")."";
					//Header("Location: ".self::$packTarget);

					Header("Content-Type: application/x-gzip");
					$fp = fopen(self::$packTarget, "r");
					fpassthru($fp);
					fclose($fp);
				}else{
					$this->msg = "Не удалось запаковать файл.";
				}

				$out = '';//$this->_showList();
				break;
			case 'reset_dir':
				$this->_resetDir($this->_getExternalParam('dir'));
				$out = $this->_showList();
				break;
			case 'delete_dir':
				$this->_deleteDir($this->_getExternalParam('dir'));
				$out = $this->_showList();
				break;
			case 'delete':
				$this->_deleteLog($this->_getExternalParam('file'));
				$out = $this->_showList();
				break;
			case 'delete_checked_files':
				$this->_deleteCheckedFiles($this->_getExternalParam('del_files'));
				$out = $this->_showList();
				break;
			case 'delete_all':
				$dir = $this->pathes[$this->_getExternalParam('num')];
				//die('Directory: ' . $dir);
				if (!empty($dir)) {
					$this->_deleteAllFiles($dir);
				}
				$out = $this->_showList();
				break;
			default:
				//echo "Action: ".$this->_getExternalParam('action');
				$out = $this->_showList();
		}

		echo $out;
		
		
	}

	private function _getPackFileName()
	{
		self::$packTarget = $_SERVER['DOCUMENT_ROOT'].'/ds/utils/files/'.self::$packTarget;
	}

	private function _deleteCheckedFiles($files)
	{
		foreach ($files as $key => $patch)
		{
			$this->_deleteLog($patch);
		}

		$str = 'Location: ./LogView.php';//.basename($_SERVER['PHP_SELF'])."?dir=".$this->_getExternalParam('dir');
		header($str);
		die();
	}

	private function _deleteAllFiles($dir)
	{
		//DS_DebugTools::WriteToLog('_deleteAllFiles called, dir=' . $dir . "\n", 'LogViewTesting.log');
		//die('_deleteAllFiles called, dir=' . $dir . "\n");
		assert(!empty($dir));
        $dp = opendir($dir);
        //print_r($dp);die();

        while ($f = readdir($dp)) {
        	//DS_DebugTools::WriteToLog('Trying to delete file ' . $f . "\n");

        	if ('.' !== $f && '..' !== $f && !is_dir($f)) {
          		//@unlink($f);
          		$this->_deleteLog($dir . $f);
        	}
        }
	}

	private function _showList()
	{
		$noDelFile=$_SERVER['DOCUMENT_ROOT'].'ds/utils/files/noDelete.txt';
		$favFile=$_SERVER['DOCUMENT_ROOT'].'ds/utils/files/favorit.txt';
		if(file_exists($noDelFile))
		{
			$this->noDelFile=$this->takeColor($noDelFile);
		}
		if(file_exists($favFile))
		{
			$this->freqOpenFile=$this->takeColor($favFile);
		}
		$r=count($this->noDelFile);
		$g=count($this->freqOpenFile);

		$out = '<html>
				<HEAD>
				<title>LogView for '.$_SERVER['SERVER_NAME'].'</title>
				</head>
				<body>';
		$out .= "<h1>LogFiles List</h1>";
		$out .= $this->msg."<br>";
		/*if($this->queryStr)
		{
			$href = $_SERVER['PHP_SELF']."?".$this->queryStr;
			$backHref = '<a href="'.$_SERVER['PHP_SELF'].'">BACK</a>&nbsp;|&nbsp;';
		} else {*/
			$href = $_SERVER['PHP_SELF'];
			$backHref = '';
//		}

		$out .= $backHref;
        $out .= '<a href="'.$href.'">REFRESH</a> Last time: '.date ("Y-m-d  H:i:s.").'<br><a href="patternAdder.php" name="patternAdder.php">GO TO PATTERN UTILITY: add or delete patterns for non-deletable and frequently used files.</a>';
        $out .= '<form method=POST action="'.$_SERVER["PHP_SELF"].'?dir='.$this->_getExternalParam('dir').'" name="deleteForm" id="deleteForm">';
		$out .= '<table border=1>';
		$out .= '<tr>
					<th>&nbsp;</th>
					<th>File</th>
					<th>Size</th>
					<th>Updatesize Delta</th>
					<th>Last Update</th>
					<th>Action</th>
				</tr>';
		
		foreach($this->pathes as $key => $path)
		{
			$activePath = false;
			
			
			
			if(is_null($this->path))
			{

				if($_SESSION['pathActive'][$key])
				{
					$activePath = true;
					$normalPath = false;
					$out .= '<tr>
								<td colspan=5><!--<a href="?dir='.$path.'">--><b>'.$path.'</b><!--</a>--></td>
								<TD align="center">
									<A href="'.$_SERVER["PHP_SELF"].'?action=activate&num='.$key.'">hide</A>
								</TD>
							</tr>';
				} else {
					$out .= '<tr>
								<td colspan=5><!--<a href="?dir='.$path.'">--><b>'.$path.'</b><!--</a>--></td>
								<TD align="center">
									<A href="'.$_SERVER["PHP_SELF"].'?action=activate&num='.$key.'">show</A>
								</TD>
							</tr>';
					continue;
				}
			} else {
				$normalPath = true;
			}

			if(($this->path != $path) && !$activePath)
			{
				continue;
			}
			if(!$activePath)
			{
				$out .= "<tr>
							<td colspan=6 align=left >
								<b>".$path."</b>&nbsp;&nbsp;&nbsp;
								<a href=\"javascript: if (confirm('Удалить все из \'$path\' ?')) document.location.href='".$_SERVER['PHP_SELF']."?action=delete_dir&dir=".$path."'\">Delete All In Folder</a>&nbsp;
							</td>
						</tr>";
			}
			if (!($handle = opendir($path)))
			{
				$out .= "<tr>Uncorrect dir!<br>";
				continue;
			}

			$files = array();

			//Сперва формируем список файлов
			while ($file = readdir($handle))
			{
		    	if($file == '.' || $file == '..' || is_dir($file))
		    	{
		    		continue;
		    	}

		    	$files[] = $file;
			}

			asort($files);
			$showBtn = 0;
			$i=0;
			
		    foreach($files as $k => $file)
		 	{
		 		$tdColor='';
				$indColor=0;
				$ifCheckbox="<input type=\"checkbox\" name=\"del_files[]\" value=\"".$path.$file."\" id=\"checkboxes".$i.$key."\" >";
				$anyColored = '';
											
				while($indColor<=$r)
				{
					if(!empty($this->noDelFile[$indColor]))
					{
						$anyColored=@ereg($this->noDelFile[$indColor], $file);
					}
					if($anyColored)
					{
						$ifCheckbox="&nbsp;";
						$indColor=$r;
					}
					$indColor++;
				}
				
				$indColor=0;
		 		while($indColor<=$g)
				{
					$anyColored=@ereg($this->freqOpenFile[$indColor], $file);
					$indColor++;
					if($anyColored)
					{
						$tdColor="style='background-color:#FFD700'";
						$indColor=$g;
					}
				}

		    	if($file == '.' || $file == '..' || is_dir($file))
		    	{
		    		continue;
		    	}
				
		    	if(@$_SESSION['LOG_FILE_SIZES'][$path.$file]==null)
		    	{
		    		$delta=0;
		    	}
				else 
				{
		    	$delta = filesize($path.$file) - @$_SESSION['LOG_FILE_SIZES'][$path.$file];
				}
		    	
				if(($activePath/* && $delta*/) || $normalPath)
				{
					$showBtn = 1;
		        	$out .= "<tr>
		        				<td>$ifCheckbox</td>
		        				<td ".$tdColor.">&nbsp;<a href='".$_SERVER['PHP_SELF']."?action=show&file=".$path."".$file."'>".$file."</a>&nbsp;</td>
		        				<td align=center>&nbsp;".number_format(filesize($path.$file), 0, ".", " ")."&nbsp;</td>
		        				<td align=center>&nbsp;".$delta."&nbsp;</td>
		        				<td align=center>".date ("Y-m-d  H:i:s.", filemtime($path.$file))."</td>
		        				<td align=center>&nbsp;
		        					<a href=\"javascript: if (confirm('Показать полностью \'$file\' ?')) document.location.href='".$_SERVER['PHP_SELF']."?action=show_full&file=".$path."".$file."'\">ShowFull</a>&nbsp;
		        					<a href=\"javascript: if (confirm('Запаковать \'$file\' ?')) document.location.href='".$_SERVER['PHP_SELF']."?action=pack&file=".$path."".$file."'\">PackFile</a>&nbsp;
		        				</td>
		        			</tr>";
				}
		        @$_SESSION['LOG_FILE_SIZES'][$path.$file] = filesize($path.$file);
		        
		        if($ifCheckbox !== "&nbsp;")
		        {
		        	$i++;
		        }
		        $tdColor='';
    		}
    		
    		closedir($handle);
    		if($normalPath || $showBtn)
    		{
				$out .= '
								<tr>
							<td><input type="checkbox" name="del_all_files" id="isChecked'.$key.'" onclick="allChecked('.$key.')"></td>
							<input type="hidden" name="action" value="delete_checked_files">
							<td><input type="button" onclick="confirmDelete();" name="delete" value="Delete checked" /></td>
							<td colspan="3">&nbsp;</td>
							<td>
								<!-- a href="?action=delete_all&num=' . $key . '" onClick="confirmDeleteAll()">Delete all files</a >
								'."<a href=\"javascript: if(confirm('Удалить все файлы ?')) document.location.href='".$_SERVER['PHP_SELF']."?action=delete_all&num=$key'\">Delete All files</a-->&nbsp;".'
							</td>
						</tr>';
    		}
		}
		$out .= '</table>';
		
		$out .= '</form>
				<SCRIPT language="javascript">
					function confirmDelete(){
						if(confirm("Удалить отмеченные файлы?")){
							document.getElementById("deleteForm").submit();
						}
					}

					function confirmDeleteAll() {
						if (!confirm("Удалить все файлы?")) {
							return false;
						}
					}
					function allChecked(key)
					{
						var boxID="isChecked"+key;
						var i=0;
						var name="checkboxes0"+key;
						
						if(document.getElementById(boxID).checked == true)
						{
							while(document.getElementById(name))
							{
								document.getElementById(name).checked=true;
								i++;
								name="checkboxes"+i+key;
							}
						}
						else
						{
    						while(document.getElementById(name))
							{
								document.getElementById(name).checked=false;
								i++;
								name="checkboxes"+i+key;
							}
						}
					}
				</SCRIPT>';
		
		$out .= '</body>
				</html>';
		return $out;
	}

	private function _showLog($fileName, $part = 1)
	{
		assert(!empty($fileName) && file_exists($fileName));

		$out = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Transitional//EN">
				<html>
				<HEAD>
				<title>LogView for '.$_SERVER['SERVER_NAME'].'</title>
				</head>
				<body>';

		$out .= "<A NAME='up'>";
		$out .= "<h2>File: $fileName  [".SU_DebugTools::GetServerName()."]</h2>";
		
		$size = filesize($fileName);

		$nextPartHref = '';
		$prevPartHref = '';
		if(($size < self::$viewDelta * $part) || is_null(self::$viewDelta))
		{
			$startPosition = 0;
			$limit = $size;
		}else{
			$startPosition = filesize($fileName) - self::$viewDelta * $part;
			$limit = self::$viewDelta + 256;
			$prevPart = $part + 1;
			$prevPartHref = " | <A href='".$_SERVER['PHP_SELF']."?action=show&file=".$fileName."&part=".$prevPart."'>Previous part</A>";

			if($part > 1)
			{
				$nextPart = $part - 1;
				$nextPartHref = " | <A href='".$_SERVER['PHP_SELF']."?action=show&file=".$fileName."&part=".$nextPart."'>Next part</A>";
			}
		}

		$backUrl = dirname($fileName).'/';

		$out .= "<a href='".$_SERVER['PHP_SELF']./*'?dir='.$backUrl.*/"'>Back To List</a> | <a href='".$_SERVER['PHP_SELF']."?action=show&file=".$fileName."'>Refresh</a> | <a href='".$_SERVER['PHP_SELF']."?action=reset&after=show&file=".$fileName."' onClick='if(!confirm(\"Really reset?\")) return false'>Reset</a> | <A HREF='#down'>Down</a>".$prevPartHref.$nextPartHref;

		$out .= "<hr>";
		$out .= "<xmp>";

		$fp = fopen($fileName, 'r');
		fseek($fp, $startPosition);

		$bytesRead = 0;
		while (!feof($fp) && ($bytesRead <= $limit))
		{
			$str = fgets($fp, 4096);
    		$out .= $str;
			$bytesRead += strlen($str);
		}

		$out .= "</xmp>";
		$out .= "<A NAME='down'>";
		$out .= "<hr>";
		$out .= "<a href='".$_SERVER['PHP_SELF']./*'?dir='.$backUrl.*/"'>Back To List</a> | <a href='".$_SERVER['PHP_SELF']."?action=show&file=".$fileName."'>Refresh</a> | <a href='".$_SERVER['PHP_SELF']."?action=reset&after=show&file=".$fileName."' onClick='if(!confirm(\"Really reset?\")) return false'>Reset</a> | <A HREF='#up'>Up</a>".$prevPartHref.$nextPartHref;

		return $out;
	}

	private function _getExternalParam($name)
	{
		assert(is_array($this->request));
		assert(!empty($name));

		if(key_exists($name, $this->request))
		{
			return $this->request[$name];
		}else{
			return '';
		}
	}

	private function _resetDir($dir)
	{
		assert(!empty($dir));

		$dp = opendir($dir);
		assert($dp);

		while($file = readdir($dp))
		{
			$this->_resetLog($dir."/".$file);
		}
	}

	private function _deleteDir($dir)
	{
		assert(!empty($dir));

		$dp = opendir($dir);
		assert($dp);

		while($file = readdir($dp))
		{
			$this->_deleteLog($dir."/".$file);
		}
	}

	private function _resetLog($file)
	{
		assert(!empty($file) && file_exists($file));
		$fp = @fopen($file, 'w');
		@fclose($fp);
	}

	private function _packFile($sorceFile, $targerFile)
	{
		assert(file_exists($sorceFile));

		$zp = gzopen($targerFile, "w9");

		if (!$zp) {
			return false;
		}

		$fp = fopen($sorceFile, 'r');

		while (!feof($fp))
		{
    		$buffer = fgets($fp, 4096);
			gzwrite($zp, $buffer);
		}

		gzclose($zp);
		fclose($fp);
	}

	private function _deleteLog($file)
	{
		assert(!empty($file) && file_exists($file));
		@unlink($file);
	}

	private function _invariant()
	{
		assert(is_array($this->pathes) && count($this->pathes)>0);
	}
	
	private function takeColor($file)
	{
		@assert(file_exists($file) && !empty($file));
		$fileHandler=fopen($file, 'r');
		if($fileHandler && filesize($file)!==0)
		{
			$fileList=fread($fileHandler, filesize($file));
			$fileList=str_replace('slashslash', "\\",$fileList);
			$fileList=explode(' ', $fileList);
		}
		
		return $fileList;
	}
}//END OF CLASS

?>