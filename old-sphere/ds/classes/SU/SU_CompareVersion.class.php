<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_CompareVersion
{
	static private $pathToDeveloperVersion;

	static private $pathToCVSVersion;
	
	static private $CVSFiles = array();
	
	static private $developerFiles = array();
	
	static private $diffferentFiles = array();
	
	static private $i;
	
	static private $pathArr;
	
	static private $finalPathArr;
	
	static private $currentVal;
	
	static private $previousVal;
	
	static private $showButton = false;
	
	static private $bothArrays = array();
	
	static private $partArray = array();
	
	static private $myFinalTree = array();
	
	static private $foldersAll=array();
	
	static public function  ShowFirst()
	{
		if(key_exists('addresses', $_REQUEST))
		{
			self::_putInFile();
			echo 'List of files is created in file unlim/ds/utils/files/fileList.txt';
		}
//		else 
//		{
//			echo 'no addresses';
//		}
		echo "<body><h1>COMPARISION VERSIONS</h1>
		<form id='FileTree' method='post' action='".$_SERVER['PHP_SELF']."'>";

		echo "<script language='javascript'>
		function changePathes()
		{
			//document.write('<input type=hidden name=changePaths>');
		}
		</script>";
		echo '	
		<table border="0">
		 <input type="HIDDEN" name="action" value="compare">
			<tr>
				<td>
					Путь до файлов девелоперской версии :&nbsp;&nbsp;&nbsp;&nbsp;
				</td>
				
				<td>';
		if( isset($_REQUEST) && $_REQUEST['submit']=='Change pathes')
		{
			echo '<input type="text" name="path_to_developer" size="30" value="'.$_REQUEST['path_to_developer'].'">
			</td>
				<td rowspan=2><input type="submit" name="submit" value="Change pathes" ></td>
			</tr>
			<tr>
				<td>
					Путь до файлов CVS :&nbsp;&nbsp;&nbsp;&nbsp;
				</td>
				<td>
					<input type="text" name="path_to_cvs" size="30" value="'.$_REQUEST['path_to_cvs'].'">';
			$treeAddress=array('path_to_developer'=>$_REQUEST['path_to_developer'], "path_to_cvs"=>$_REQUEST['path_to_cvs']);
		}
		else 
		{
			echo'<input type="text" name="path_to_developer" size="30" value="/home/unlim/ds">
					</td>
					<td rowspan=2><input type="submit" name="submit" value="Change pathes" ></td>
				</tr>
				<tr>
					<td>
						Путь до файлов CVS :&nbsp;&nbsp;&nbsp;&nbsp;
					</td>
					<td>
						<input type="text" name="path_to_cvs" size="30" value="/home/unlim/Server3">';
			$treeAddress=array('path_to_developer'=>'/home/unlim/ds', "path_to_cvs"=>"/home/unlim/Server3");
		}
		echo '</td>
			</tr></table>';
			
			self::getTrees($treeAddress);
			$keyCVS=array_keys(self::$CVSFiles);
			$keyLocal=array_keys(self::$developerFiles);
			$keyAll=array_merge($keyCVS,$keyLocal);
			$keyAll=array_unique($keyAll);
			sort($keyAll);

			//добываем дерево папок ds
			foreach ($keyAll as $key => $address)
			{
				$slashNum=strpos($address, '/', 1);
				$folder=substr($address, 0, $slashNum);
				if($folder=='')
				{
					$folder='.';
				}
				self::$foldersAll[$folder][]=$address;
				
			}
			$_SESSION=array_merge($_SESSION, self::$foldersAll);
			$foldersOnly=array_keys(self::$foldersAll);
			echo '<table border=0><tr><td><img src=../images/navigate/openfolder.gif width=24 height=22 border=noborder>DS</td></tr>';
			
			foreach ($foldersOnly as $key=>$value)
			{
				if($value=='/classes')
				{
					echo "<tr><td><input type='checkbox' name='choosedFolder[]' value='$value' checked><img src=../images/navigate/openfolder.gif width=24 height=22 border=noborder> classes</td></tr>";
				}
				else
				{
					$pritty=ereg_replace("/", ' ', $value);
					echo "<tr><td><input type='checkbox' name='choosedFolder[]' value='$value'><img src=../images/navigate/openfolder.gif width=24 height=22 border=noborder>$pritty</td></tr>";
				}

			}
			echo '<tr><td><input type="submit" name="submit" value="Show"></td></tr></table>';
	}
	
	static private function getTrees($postData)
	{
		$pathToDeveloperVersion = $postData['path_to_developer'];
		$pathToCVSVersion = $postData['path_to_cvs'];

		if (!is_dir($pathToDeveloperVersion))
		{
			echo "Путь до файлов девелоперской версии(<b>$pathToDeveloperVersion</b>) не является каталогом";
			return;
		}
		
		if (!is_dir($pathToCVSVersion))
		{
			echo "Путь до файлов девелоперской версии(<b>$pathToCVSVersion</b>) не является каталогом";
			return;
		}
		self::$pathToDeveloperVersion = $pathToDeveloperVersion;
		
		self::$pathToCVSVersion = $pathToCVSVersion;
		
		self::$CVSFiles = SU_CompareVersionGetFolderTree::Exec($pathToCVSVersion, true);		
		self::$developerFiles = SU_CompareVersionGetFolderTree::Exec($pathToDeveloperVersion);			
	}
	
	static public function ShowDifference($data)
	{
		echo "<body ><h1>COMPARISION VERSIONS</h1>
		<form id='FileTree' method='post' action='".$_SERVER['PHP_SELF']."'>";
		$fileName = $data['file'];
		assert(!empty($fileName));
		
		$pathToDeveloperVersion = $data['pathToDeveloperVersion'];
		$pathToCVSVersion = $data['pathToCVSVersion'];
		
		echo "ФАЙЛ <b>$fileName</b><br><br>";
		
		if (!is_dir($pathToDeveloperVersion))
		{
			echo "Путь до файлов девелоперской версии(<b>".$pathToDeveloperVersion."</b>) не является каталогом";
			return;
		}
		
		if (!is_dir($pathToCVSVersion))
		{
			echo "Путь до файлов девелоперской версии(<b>".$pathToCVSVersion."</b>) не является каталогом";
			return;
		}
		
		$command = "diff $pathToDeveloperVersion$fileName $pathToCVSVersion$fileName";

		$str = exec($command, $out);
		
		foreach ($out as $key => $data)
		{
			$strArr = str_split($data);
			
			if (preg_match('/^[0-9]+$/', $strArr[0]))
			{
				$color = 'red';
				$br = "<br>";
			} elseif (preg_match('/^[\-]+$/', $strArr[0])){
				$color = '';
				$br = "";
			} else {
				$color = 'green';
				$br = "";
			}
			
			echo "$br<font color='$color'>".htmlentities($data)."</font><br>";
		}
		
		
		echo "</table>";
	}
	
	
	static public function Exec($postData)
	{
		self::getTrees($postData);
		echo "<body onscroll=scrollFunc()><h1>COMPARISION VERSIONS</h1>
		<form id='FileTree' method='post' action=''>";
		echo '<input type="submit" name="submit" value="Back">';
	

		
		self::$bothArrays=array_merge(self::$CVSFiles, self::$developerFiles);
		
		$folders=$postData['choosedFolder'];

		$myFiles=array();
		foreach ($folders as $key=>$folder)
		{
			$myFiles=array_merge($myFiles, $_SESSION[$folder]);
		}
		$myFinalTree = array();
	
		foreach($myFiles as $number=>$key)
		{
			$myFinalTree[$key]=self::$bothArrays[$key];
		}
		self::$myFinalTree=$myFinalTree;
/*echo "<font color=red>myFinalTree:</font><br><pre>";
print_r(self::$developerFiles);
echo "<br>_______________________________________<br>";
echo "<font color=red>SVSFiles:</font><br>";
print_r(self::$CVSFiles);
echo "<br>_______________________________________<br>";*/		
		echo "<script language='javascript'>
		
		</script>";
		echo "<h3>Файлы, которые есть в CVSсе, файлы, которые есть на локали.</h3>";
		echo "";
		echo "<input type='hidden' name='addresses' id='as' value=''>";
		echo "<input type='hidden' name='path_to_developer'  value='".$postData['path_to_developer']."'>";
		echo "<input type='hidden' name='path_to_cvs' ' value='".$postData['pathToCVSVersion']."'>";
		echo "<div name='fly' id='fly' style='position:absolute; left:740px; background-color:white; height=50px; vertical-align:middle; border: 1px solid #aaaaaa'>
		<font color='9370DB'>-L - file is not on local</font>
		<input type='checkbox' name='allNotLocal' id='allNotLocal' onClick='checkboxes()'>
		&nbsp;<font color=#2E8B57>-C - file is not on CVS</font>&nbsp;
		<input type='checkbox' name='allNotCVS' id='allNotCVS' onClick='checkboxes()'><br>
		<font color=FF8C00>-V - file without variable</font>
		<input type='checkbox' name='allWithoutVar' id='allWithoutVar' onClick='checkboxes()'>
		&nbsp;&nbsp;&nbsp;Files are different
		<input type='checkbox' name='allDifferent' id='allDifferent' onClick='checkboxes()'>&nbsp;&nbsp;<input type='submit' name='submit' value='Create list' onClick='GetLine()'></div>";
		echo "<script language='javascript'>
		<!--
			var hidName;
			var index=0;
			var usedVar='not';
			var usedCVS='not';
			var usedLoc='not';
			var usedDif='not';
		
			function GetLine()
			{
				var formLength=FileTree.length;
				var i;
				var myLine='';
				var value;
				var folderName;
				
				
				for(i=8; i<formLength; i++)
				{
					if(FileTree.elements[i].checked == true)
 					{
						myLine+=FileTree.elements[i].name+' ';
						FileTree.elements[i].checked == false;
					}
				}
				
				//document.location.href='".$_SERVER['PHP_SELF']."?path_to_cvs=/home/www/htdocs/Server3&path_to_developer=/home/www/htdocs/ds&addresses'+myLine;
				FileTree.elements[1].value=myLine;
			}
			
			function checkboxes()
			{
				if(document.getElementById('allWithoutVar').checked == true && usedVar=='not')
				{
					usedVar='is';
					allSelected('withoutVar');
				}
				if(document.getElementById('allWithoutVar').checked == false && usedVar=='is')
				{
					usedVar='not';
					allDiselected('withoutVar');
				}
				
				if(document.getElementById('allDifferent').checked == true && usedDif=='not')
				{
					usedDif='is';
					allSelected('differ');
				}
				if(document.getElementById('allDifferent').checked == false && usedDif=='is')
				{
					usedDif='not';
					allDiselected('differ');
				}
				
				if(document.getElementById('allNotCVS').checked == true && usedCVS=='not')
				{
					usedCVS='is';
					allSelected('notCVS');
				}
				if(document.getElementById('allNotCVS').checked == false && usedCVS=='is')
				{
					usedCVS='not';
					allDiselected('notCVS');
				}
				
				if(document.getElementById('allNotLocal').checked == true && usedLoc=='not')
				{
					usedLoc='is';
					allSelected('notLocal');
				}
				if(document.getElementById('allNotLocal').checked == false && usedLoc=='is')
				{
					usedLoc='not';
					allDiselected('notLocal');
				}
				
			}
			
			function allSelected(hidName)
			{
				while(document.getElementById(hidName+index))
				{
					address=document.getElementById(hidName+index).value;
					document.getElementById(address).checked=true;
					index++;
				}
				index=0;
			}
			
			function allDiselected(hidName)
			{
				while(document.getElementById(hidName+index))
				{
					
					address=document.getElementById(hidName+index).value;
					document.getElementById(address).checked=false;
					index++;
				}
				index=0;
			}
		
			var layerPos;
		    function scrollFunc()
			{
				var scrolled=document.body.scrollTop;
				layerPos=scrolled+10;
				fly.style.top=layerPos;
			}
		
		//-->
		</script>";


		self::_newFileInCVS(self::$myFinalTree);
		
		self::_newFileInVersion();

	

	
			self::_differentFile();
			echo '<input type="hidden" name="DifferenceFiles">';
	

			self::_outNewFiles(self::$myFinalTree);
	
		echo 'TEST COMPLIT.';
	}
	
	static private function _newFileInCVS()
	{
		foreach (self::$CVSFiles as $file => $fileData)
		{	
			if (!key_exists($file, self::$developerFiles))
			{	
			self::$myFinalTree[$file]['isCVS']=0;
				
			} 
		}
		return $newFileInCVS;
	}
	
	/*static private function _newFileInCVS()
	{
		foreach (self::$myFinalTree as $file => $fileData)
		{	
			if (!key_exists($file, self::$developerFiles))
			{	
			self::$myFinalTree[$file]['isCVS']=0;
				
			} 
		}
		return $newFileInCVS;
	}*/

	static private function _newFileInVersion()
	{
		foreach (self::$developerFiles as $file => $fileData)
		{
			if (!key_exists($file, self::$CVSFiles))
			{
				self::$myFinalTree[$file]['isLocal']=0;
			}
		}
		return $newFileInVersion;
	}
	
	/*static private function _newFileInVersion()
	{
		foreach (self::$myFinalTree as $file => $fileData)
		{
			if (!key_exists($file, self::$CVSFiles))
			{
				self::$myFinalTree[$file]['isLocal']=0;
			}
		}
		return $newFileInVersion;
	}*/
	
	static private function _CVSWithoutVariable($arr)
	{
		foreach ($arr as $file => $fileData)
		{
			if ($fileData['CVS_Version'] != 'YES')
			{
				$filesWithoutVariable[$file] = $fileData;
			}
		}
		return $filesWithoutVariable;
	}
	
	static private function _differentFile()
	{
		foreach (self::$developerFiles as $file => $fileData)
		{
			self::_compareFile($file, $fileData);
		}		
	}

	static private function _compareFile($file, $fileData)
	{		
		if (key_exists($file, self::$CVSFiles) && key_exists($file, self::$developerFiles))
			{
				$developerFileArray = file(self::$pathToDeveloperVersion.$file);
				$CVSFileArray = file(self::$pathToCVSVersion.$file);
				
				foreach ($developerFileArray as $key => $line)
				{
					if ($key != 0)
					{
						if (trim($line) != trim($CVSFileArray[$key]))
						{							
							//self::$diffferentFiles[$file]['CVS_time'] = self::$CVSFiles[$file]['time'];
							//self::$diffferentFiles[$file]['local_time'] = $fileData['time'];
							
							//$myCVStime=format_time(self::$CVSFiles[$file]['time']);
							//$myLocalTime=format_time($fileData['time']);
							self::$myFinalTree[$file]['CVS_time'] = date ("Y-m-d H:i:s", self::$CVSFiles[$file]['time']);
							self::$myFinalTree[$file]['local_time'] = date ("Y-m-d H:i:s",$fileData['time']);
							return;
						}
					}
				}
			}
	}
	
	static private function _outNewFiles($tree)
	{		
		$nbsp = "";
		self::$i = 0;
		self::$pathArr[0] = '/';
		$indexVar=0;
		$indexCVS=0;
		$indexLoc=0;
		$indexDif=0;
		
		echo '<table border=0 cellspacing=0 cellpadding=0>
				<tr>
				<td valign = middle nowrap><img src=../images/navigate/openfolder.gif width=24 height=22 border=noborder><td valign=middle align=left nowrap><font size=-1 face="Arial, Helvetica">Корень</font></table>
				';

		foreach ($tree as $file => $data)
		{
			$path = explode("/", $file);

			//print_r($this->filesInBoth);
			$nextPathArr = explode("/", $tree[$key+1]);

			$curPath = '';

			$nextPath = '';

			for ($i=0; $i<count($path)-1; $i++)
			{
				$curPath .= $path[$i];
				//Распослдений выбор имен файлов

			}
			for ($i=0; $i<count($path)-1; $i++)
			{
				$nextPath .= $nextPathArr[$i];

			}
				
			if ($nextPath == $curPath)
			{				
				$flag = false;
			} else {
				$flag = true;
			}

			foreach ($path as $pathKey => $string)
			{
				self::$i ++;
				self::$currentVal[self::$i] = $string;

					
				if (self::$currentVal[self::$i] != self::$previousVal[self::$i])
				{
					
					if(strpos($string, '.php'))
					{
						
							//Смотрим, есть ли у файла CVS переменная
							if ($data['CVS_Version'] == 'YES')
							{
								//Синенькое
								//$color = '#00008B';
								$td3='<td width=30px>&nbsp;</td>';
								$hidden1='';
							} else {
								//Розовенькое))
								//$color = '#8B008B';
								$td3='<td width=30px ><font color="FF8C00">&nbsp;-V</font></td>';
								$hidden1='<input type="hidden" id="withoutVar'.$indexVar.'" name="withoutVar[]" value='.$file.'>';
								$indexVar++;
							}
							
							//Смотрим, есть ли у файла номер версии
							if ($data['Version'] != 'UNDEFINED' && $data['Version'] != 'NO')
							{
								//Розовенькое))
								$version = "V.".$data['Version'];
							} else {
								//Синенькое
								$version = '';
							}

							//собираем инфу по файлу

							if(key_exists('isCVS', $data))
							{
								$td1='<td width=30px ><font color="9370DB">&nbsp;-L</font></td>';
								$hidden2='<input type="hidden" id="notLocal'.$indexCVS.'" name="notLocal[]" value='.$file.'>';
								$indexCVS++;
							}	
							else 
							{
								$td1='<td width=30px>&nbsp;</td>';
								$hidden2='';
							}
							if(key_exists('isLocal', $data))
							{
								$td2='<td width=30px ><font color="#2E8B57">&nbsp;-C</font</td>';
								$hidden3='<input type="hidden" name="notCVS[]" id="notCVS'.$indexLoc.'" value='.$file.'>';
								$indexLoc++;
							}	
							else 
							{
								$td2='<td width=30px>&nbsp;</td>';
								$hidden3='';
							}
							if(key_exists('CVS_time', $data))
							{
								$td4="<td width='170px'>".self::$myFinalTree[$file]['CVS_time']."</td>";
								$td5="<td width='170px'>".self::$myFinalTree[$file]['local_time']."</td>";
								$hidden4='<input type="hidden" name="differ[]" id="differ'.$indexDif.'" value='.$file.'>';
								$myLink="<a href='ComparisonVersion.php?ShowDifference=true&pathToDeveloperVersion=".self::$pathToDeveloperVersion."&pathToCVSVersion=".self::$pathToCVSVersion."&file=$file' target='_blank'>";
								$myLinkClose="</a>";
								$indexDif++;
							}	
							else 
							{
								$td4='<td width=170px>&nbsp;</td>';
								$td5='<td width=170px>&nbsp;</td>';
								$hidden4='';
								$myLink='';
								$myLinkClose='';
							}
						
						if($flag == true)
						{
							
							echo "
					
							<table border=0 cellspacing=0 cellpadding=0 ><tr><td width='700px' style='border-bottom:1px solid'><table border=0 cellspacing=0 cellpadding=0 valign=middle><tr><td nowrap>$nbsp<img src='../images/navigate/lastnode.gif' width=16 height=22></td><td><img src='../images/navigate/doc.gif' alt='Opens in right frame' border=0></td><td nowrap ><font size=-1 face='Arial, Helvetica'><font color=$color>$myLink $string $myLinkClose <b>$version (".date ("Y-m-d H:i:s", $data['time']).") </b></font>&nbsp;</td></tr></table></td><td><input type='checkbox' name='$file'></td><td style='border-bottom:1px solid'><table border=0 cellspacing=0 cellpadding=0 ><tr><td>$td1 $td2 $td3 $td4 $td5 $hidden1 $hidden2 $hidden3 $hidden4</td></tr></table></td></tr></table>";
						} else {
								echo "<table border=0 cellspacing=0 cellpadding=0 valign=middle><tr><td nowrap width='700px' style='border-bottom:1px solid'><table  border=0 cellspacing=0 cellpadding=0 ><tr><td>$nbsp<img src='../images/navigate/node.gif' width=16 height=22></td><td><img src='../images/navigate/doc.gif' alt='Opens in right frame' border=0><td nowrap><font size=-1 face='Arial, Helvetica'><font color=$color>$string <b>$version (".date ("Y-m-d H:i:s", $data['time']).") </b></font></font>&nbsp;</td></tr></table></td><td><input type='checkbox' name='$file'></td><td style='border-bottom:1px solid'><table border=0 cellspacing=0 cellpadding=0 ><tr><td> $td1 $td2 $td3 $td4 $td5 $hidden1 $hidden2 $hidden3 $hidden4</td></tr></table></td></tr></table>";
						}
					} else {

						//рисуем
						if($flag == false)
						{
							echo "<table border=0 cellspacing=0 cellpadding=0>
									<tr>
									<td width='700px' style='border-bottom:1px solid'><table border=0 cellspacing=0 cellpadding=0><tr>
									<td valign = middle nowrap>$nbsp<img src='../images/navigate/lastnode.gif' width=16 height=22><img src=../images/navigate/openfolder.gif width=24 height=22 border=noborder></a><td valign=middle align=left nowrap><font size=-1 face='Arial, Helvetica'>$string</font></td></tr></table></td><td style='border-bottom:1px solid black'><table border=0 cellspacing=0 cellpadding=0 ><tr><td>hrer$td1 $td2 $td3 $td4 $td5 $hidden1 $hidden2 $hidden3 $hidden4</td></tr></table></td></tr></table>";
						} else {
							echo "<table border=0 cellspacing=0 cellpadding=0>
									<tr><td valign = middle nowrap width='700px'  style='border-bottom:1px solid'><table border=0 cellspacing=0 cellpadding=0>
									<tr>
									<td  nowrap>$nbsp<img src='../images/navigate/node.gif' width=16 height=22><img src=../images/navigate/openfolder.gif width=24 height=22 border=noborder></a><td valign=middle align=left nowrap><font size=-1 face='Arial, Helvetica'>$string</font></td></tr></table><td style='border-bottom:1px solid'><table><tr><td width=30px>&nbsp;</td><td width=30px>&nbsp;</td><td width=30px>&nbsp;</td><td width=170px>&nbsp;</td><td width=170px>&nbsp;</td></tr></table></td></td></tr></table>";
						}
						}
				} 
				
				
				self::$previousVal[self::$i] = $string;

				
				if ($curPath != '')
				{
					$nbsp .= "<img src='../images/navigate/vertline.gif' width=16 height=22>";
				}
			}
			self::$i = 0;
			$nbsp = '';
		}
		
		echo "<br>";
		echo "<br>";
	}	
	
	static private function _outDifferenceFiles($tree)
	{
		//ksort($tree);
		$nbsp = "";
		self::$i = 0;
		$counFiles = 0; //Счетчик файлов
		self::$pathArr[0] = '/';
		
		
		echo "<table border=1 bgcolor='#669999' bordercolor='#669999'>";
		
		echo "<tr><th>";
		echo "File";
		echo "</th>";
		
		
		echo "<th>";
		echo "CVS Data";
		echo "</th>";
		
		echo "<th>";
		echo "Local Data";
		echo "</th>";
		
		echo "<th>";
		echo "Time delta";
		echo "</th>";
		
		echo "</tr>";
		
		foreach ($tree as $file => $data)
		{
			$path = explode("/", $file);

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
						} elseif ($data['CVS_Version'] == "NO")
						{
							$color = "#FFFFCC";
						} else 
						{
							$color = "#CCFFE6";
						}
						
						echo "<tr bgcolor=$color><td>";
						echo "$nbsp<a href='ComparisonVersion.php?ShowDifference=true&pathToDeveloperVersion=".self::$pathToDeveloperVersion."&pathToCVSVersion=".self::$pathToCVSVersion."&file=$file' target='_blank'>$string</a>";
						echo "</td>";
						
						echo "<td>";
						echo date ("Y-m-d H:i:s", $data['CVS_time']);
						echo "</td>";
						
						echo "<td align=center>";
						echo date ("Y-m-d H:i:s",$data['local_time']);
						echo "</td>";
						
						echo "<td align='right'>";
						$maxData = abs($data['local_time'] - $data['CVS_time']);
					
						echo self::format_time($maxData);
						echo "</td>";
						
						echo "</tr>";
						
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
	
		echo "<br>";
		echo "<br>";
	}
	
	static  function format_time($sec) {
		$a = array(
		86400 => ' day', // количество секунд в сутках
		3600  => ' hour', // количество секунд в часе
		60    => ' min', // количество секунд в минуте
		1     => ' sec' // количество секунд в секунде
		);
		$out = '';
		foreach ($a as $k=>$v) {
			$tmp = floor($sec/$k);
			$out .= ($tmp?$tmp.$v.' ':'');
			$sec -= $tmp*$k;
		}
		return $out;
	}
	
	private function _putInFile()
	{
		$myFile=$_SERVER['DOCUMENT_ROOT'].'/ds/utils/files/fileList.txt';
		$fileHandler=fopen($myFile, 'wt');
		$myString=$_REQUEST['addresses'];
		$myString=ereg_replace(' ', "\r\n", $myString);
		if($fileHandler)
		{
			fwrite($fileHandler, $myString);
		}
		else 
		{
			echo "Error: could not write into $myFile.";
		}
	}

}
?>