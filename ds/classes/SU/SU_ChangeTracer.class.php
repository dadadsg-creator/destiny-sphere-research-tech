<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/10/24 10:18:50 $';

//Класс для запоминания изменений в файлах 

class SU_ChangeTracer
{
	private  $status=array('Development', 'Testing', 'Testing is over', 'Release');

	
	function putIn()
	{
		
		echo "
		<script language='javascript'>

		function myFunc()
		{
		    var myVar;
		    myVar = descrForm.style.display;
			if(myVar == 'none')
			{
				descrForm.style.display='inline';
			}
			else
			{
				descrForm.style.display='none';
			}
		}
		
		function myFuncFiles()
		{
		    var myVar;
		    myVar = filesForm.style.display;
			if(myVar == 'none')
			{
				filesForm.style.display='inline';
			}
			else
			{
				filesForm.style.display='none';
			}
		}
		
		function myFuncTables()
		{
		    var myVar;
		    myVar = tablesForm.style.display;
			if(myVar == 'none')
			{
				tablesForm.style.display='inline';
			}
			else
			{
				tablesForm.style.display='none';
			}
		}
		function myFuncsql()
		{
		    var myVar;
		    myVar = sqlForm.style.display;
			if(myVar == 'none')
			{
				sqlForm.style.display='inline';
			}
			else
			{
				sqlForm.style.display='none';
			}
		}
		</script>";
		
		
		echo "<form action='' method='post'>";
		echo "<table border=1>
					<tr>
						<td style='width:135px;'>";
		echo "				Feature name:
						</td>
						<td style='width:400px; text-align:left'> 
							<input type = 'text' name='featureName' style='width:400px;' value='".$_REQUEST['featureName']."' ><br>
						</td>";
				  echo "<td style='text-align:left; width:590px'>
							&nbsp;&nbsp;&nbsp;Status:&nbsp;&nbsp;";
				 	 echo "<select name='status'>";
		foreach ($this->status as $key=>$value)
		{
			if($value == $_REQUEST['status'])
			{
				echo "	<OPTION name='$value' value='$value' selected>&nbsp;$value&nbsp;";
			}
			else 
			{
				echo "	<OPTION name='$value' value='$value'>&nbsp;$value&nbsp;";
			}
		}
		
		echo "				</select>
						</td>
					</tr>";
		//descr
		echo "		<tr>
						<td colspan=3>";
		echo '				<div style="background-color :#FFE4B5; width:30px; display:inline" onClick="myFunc()" > >> </div>';
		echo "				<div id='descrForm' style='display:none'><input type='textarea' name='descr' style='width:800px; height:130px; vertical-align:top' value=".$_REQUEST['descr'].">&nbsp;<input type='submit' value='Submit' name='descr'></div>
		                </td>
		            </tr>";
	
		//files
		
		echo "      <tr>
						<td colspan=3>
							<table border=1> 
								<tr>
									<td>";
		
		if(isset($_REQUEST['files']))
		{
			foreach ($_REQUEST['files'] as $key=>$value)
			{
				$outFiles.= "			$value<br>";
			}
		}
		echo "							Files: <input type='textarea' style='width:400; height:200' name='files' value='$outFiles'><br>";
		echo '							<div style="background-color:#FFE4B5; width:30px; display:inline" onClick="myFuncFiles()" > >> </div>';
		echo "							<div id='filesForm' style='display:none'><input type='text' name='files' style='width:400px;' value=".$_REQUEST['files'].">&nbsp;<input type='submit' value='Add file/files' name='files'></div>";
		echo "						</td>
									<td>";
		
		//tables
		
		if(isset($_REQUEST['tables']))
		{
			foreach ($_REQUEST['tables'] as $key=>$value)
			{
				$outTables.= "			$value<br>";
			}
		}
		echo "							Tables: <input type='textarea' style='width:400; height:200' name='tables' value='$outTables'><br>";
		echo '							<div style="background-color:#FFE4B5; width:30px; display:inline" onClick="myFuncTables()" > >> </div>';
		echo "							<div id='tablesForm' style='display:none'><input type='text' name='tables' style='width:400px;' value=".$_REQUEST['tables'].">&nbsp;<input type='submit' value='Add table/tables' name='table'></div>";
		echo "						</td>
								</tr>
							</table>
						</td>
					</tr>";	
		
		//sql
		
		if(isset($_REQUEST['sql']))
		{
			foreach ($_REQUEST['comment'] as $key=>$value)
			{
				$number=$key+1;
				echo "<tr><td>SQL caption $number: 
					$value<br>
					<div style='background-color:#FFE4B5; width:30px; display:inline' onClick='myFuncsql()' > >> </div>;
					<div id='sqlForm' style='display:none'><input type='textarea' name='sql[]' style='width:800px; height:150px' value=".$_REQUEST['sql'].">&nbsp;<input type='submit' value='Add sql' name='sql'></div>
					</td></tr>";
			}
		}
		
	
		
			if($_REQUEST['submit']=='Add more SQL')
		{
			$count=count($_REQUEST['sql'])+5;
		}
		elseif(isset($_REQUEST['sql']))
		{
			$count = count($_REQUEST['sql']);
		}
		else 
		{
			$count=1;
		}
		
		for($i=0; $i<$count; $i++)
		{
			if($i==$count-1)
					{
						
						$button= "<br><input type='submit' name='submit' value='Add more SQL'>";
					}
					else 
					{
						$button='';
					}
			$number=$i+1;
			echo "	<tr>
						<td colspan=3>
							SQL caption $number <input type='text' style='width=800px' name='comment[]'><br>
							<div style='background-color:#FFE4B5; width:30px; display:inline' onClick='myFuncsql()' > >> </div>
							<div id='sqlForm' style='display:none'>SqQL $number<input type='textarea' name='sql[]' style='width:800px; height:150px' value=".$_REQUEST['sql'].">&nbsp;<input type='submit' value='submit sql' name='sql'></div>
						</td>
					</tr>";
			
		}
		echo "</table>";//end of big table
/*		echo "  		
							Feature description:
							</td>
							<td  colspan=2>
							<input type='textarea' name='descr' style='width:1000px; height:100px; vertical-align:top' value=".$_REQUEST['descr'].">
							</td>
						</tr>
					</table>
					</td>
				</tr>";
		
	
		echo "	<tr><td  id='withBor'><table><tr>
					<td style='width:135px;'>
					Author(s) name(s):
					</td>
					<td>
					<input type = 'text' name='authers' style='width:1000px; height:100px ' value='".$_REQUEST['authers']."'>
					</td>
				</tr>";
		echo "	<tr>
					<td>
					Feature files:
					</td>
					<td>
					<input type = 'text' name='files' style='width:1000px; height:100px;' value='".$_REQUEST['files']."'>
					</td>
				</tr>";
		echo "	<tr>
					<td>
					DB tables used:
					</td>
					<td>
					<input type = 'text' name='tables' style='width:1000px; height:100px' value='".$_REQUEST['tables']."'>
					</td>
				</tr></table></td></tr><tr><td  id='withBor'><table>";
		//echo "<tr><td>SQL used (comment and string):</td><td colspan=3><input type = 'text' name='sql' style='width:1000px; height:150px ' value='".$_REQUEST['sql']."'></td></tr>";
		if($_REQUEST['submit']=='Add more SQL')
		{
			$count=count($_REQUEST['sql'])+5;
		}
		elseif(isset($_REQUEST['sql']))
		{
			$count = count($_REQUEST['sql']);
		}
		else 
		{
			$count=3;
		}
		$this->showSQLField($count);
		
		
		echo "	</table></td></tr><tr>
					<td style='padding-left:150px; text-align:left'>
					&nbsp;<input type='submit' value='Submit'>
					</td>
					<td >
					
					</td>
				</tr></table>";
	}
	
	function haveALook($request)
	{
		$allFiles=explode(' ', $request['files']);
		
		$this->putIn();
		$dateTime=$this->getDateTime();
		
//		foreach ($allFiles as $key=>$value)	
//		{
//			echo "
//			<br><table border=1 >
//			<tr><td width=300px>".$request['authors']."</td><td width=700px>".$dateTime."</td></tr>
//			<tr><td colspan=2>".$value." on ".$request['server']."</td></tr>
//			<tr><td colspan=2>".$request['comment']."</td></tr>
//			</table>
//			";
//		}*/
	}
	
	function putToDB()
	{
		//
	}
	
	function getDateTime()
	{
		$date=date("d-m-Y H:i:s");
		return $date;
	}
	
	function showSQLField($count)
	{
			for($i=0; $i<$count; $i++)
		{
			if($i==$count-1)
					{
						
						$button= "<br><input type='submit' name='submit' value='Add more SQL'>";
					}
					else 
					{
						$button='';
					}
			$number=$i+1;
			echo "<tr>
					<td style='padding-top:10px; width:135px'>
					SQL caption ".$number.":
					</td>
					<td>
					<input type = 'text' name='sqlCaption[]' style='width:1000px;' value='".$_REQUEST['sqlCaption'][$i]."'>
					<td>
				</tr>
			
				<tr>
					<td>
					SQL query $number:
					$button
					</td>
					<td>
					<input type = 'text' name='sql[]' style='width:1000px; height:150px' value='".$_REQUEST['sql'][$i]."'>
					<td>
				</tr>";
		}
	}
}