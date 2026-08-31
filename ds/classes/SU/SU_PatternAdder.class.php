<?php
class SU_patternAdder
{
	private $noDelFile;
	private $favorFile;
	private $request;
	
	function show($request)
	{

		$this->request = $request;
		$favFile=$_SERVER['DOCUMENT_ROOT'].'/ds/utils/files/favorit.txt';
		$noDelFile=$_SERVER['DOCUMENT_ROOT'].'/ds/utils/files/noDelete.txt';
		//$this->request='';
		if(!empty($request) && key_exists('favorit', $request) || key_exists('noDel', $request))
		{

			$noDelete=$this->getParam('noDel');
			$favorit=$this->getParam('favorit');
		
			$this->putInFile($favFile, $favorit);
			$this->putInFile($noDelFile, $noDelete);	
		}

		$out="<form method='post' action=".$_SERVER['PHP_SELF']."?".$sendParam." name='myForm'><table border=0>";

		$out.="<tr><h1>Pattern add or delete</td></h1></tr>
		<tr><td> <a href='LogView.php'>GO TO LOG VIEWER.</a></td></tr>
		<tr><td style='font-weight:bold'>Add favorite file pattern:</tr></td>
		<td><input type='text' id='favorit' name='favorit'/><input type='hidden' id='favHid' name='favorit' value=''> </td>
		</tr><tr><td  style='font-weight:bold'>Check for deleting:</td>";	
	
		$fileList=$this->takeFromFile($favFile);

		if(!empty($fileList))
		{
			$i=0;
			foreach ($fileList as $d => $fileName)
			{
				if($fileName!==" " && !empty($fileName))
				{
					$out.="<td colspan=2><input type='checkbox' id='1".$i."' name=".$fileName." class=".$fileName."> ";
					$fileName=str_replace('slashslash', "\\", $fileName);
					$out.=$fileName;
					$out.="</td></tr><tr><td>&nbsp;</td>";
					$i++;
				}
			}
		}
		$fileList='';
		
		$out.="
		</tr><tr ><td style='padding-top:30px'  style='font-weight:bold'>Add non-deletable file pattern:&nbsp;&nbsp;&nbsp;</tr></td>
		<td style='padding-top:30px'><input type='text' id='noDel' name='noDel'/><input type='hidden' id='noDelHid' name='noDel' value=''> </td>
		</tr><tr><td style='font-weight:bold'>Check for deleting:</td>";	
		
		$fileList=$this->takeFromFile($noDelFile);
		if(!empty($fileList))
		{
			$i=0;
			foreach ($fileList as $d => $fileName)
			{
				if($fileName!==" " && !empty($fileName)){
					$fileName=str_replace('slashslash', '\\', $fileName);
					$out.="<td colspan=2>&nbsp;<input type='checkbox' id='2".$i."' name=".$fileName." class=".$fileName.">";
					$fileName=str_replace('slashslash', "\\", $fileName);
					$out.= $fileName;
					$out.="</td></tr><tr><td>&nbsp;</td>";
					$i++;
				}
			}
		}
				
		$out.="<tr><td colspan=3 align='right'><input type='submit' value='&nbsp;Submit&nbsp;' name='Submit all' id='Delete' onClick='delChecked()'></td> </tr>";
		$out.="</table>
		</form>";
		
		$out.="<script language='javascript'>
		<!--
			var param='';
			function formAddress(key)
			{
				var test1;
				var test2;
				param='';
				var escaped;
				
				for(i=0; i<1000; i++)
				{
					i=String(i);
					thisKey=key+i;
					i=parseInt(i);
				
					
					if(document.getElementById(thisKey) != null && document.getElementById(thisKey).checked == false)
					{
					 	param +=document.getElementById(thisKey).name+' ';
					}
					 else if(document.getElementById(thisKey) == null)
					{
						i=1000;
					}
				}
			}
			
			function delChecked()
			{
				var i=0;
				var key='1'+i;
				var address='';
				var newFavorit='';
				
				formAddress(1);
				address=param + document.getElementById('favorit').value;
				document.getElementById('favHid').value=address;
				formAddress(2);
				address='';
				address+=param+document.getElementById('noDel').value;
				document.getElementById('noDelHid').value=address;
				//document.location.href='".$_SERVER['PHP_SELF']."' +'?'+ address;
			}
					
		//-->
		</script>
		";
		echo $out;
	}
	
	private function putInFile($file, $myString)
	{	

		//@assert(file_exists($file));

		$fileHandler=fopen($file, 'w');
		
		$myString=stripslashes($myString);
		$myString=str_replace("\\", 'slashslash', $myString);
	
		if($fileHandler)
		{
			@fwrite($fileHandler, $myString);
		}
		else 
		{
			echo "Error: could not write into file $file.<br>";
		}
	}

	private function takeFromFile($file)
	{

		if(file_exists($file) && !empty($file));
		{
			$fileHandler=fopen($file, 'r');
	
			if($fileHandler && filesize($file)!==0)
			{
				$fileList=fread($fileHandler, filesize($file));
				$fileList=explode(' ', $fileList);
			}
			else if(!($fileHandler))
			{
				echo "Error: could not read from file $file.<br>";
			}
		}
		return $fileList;
		
	}

	private function getParam($name)
	{
		assert(is_array($this->request));
		assert(!empty($name));
		
		if(key_exists($name, $this->request) && !empty($this->request[$name]))
		{
			$this->request[$name]=$this->request[$name].' ';
			return $this->request[$name];
		}else{
			return '';
		}
	}
	
}
?>

		
		
		