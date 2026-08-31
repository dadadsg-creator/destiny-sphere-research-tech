<? $CVS_Version = '$Revision$ ; $Date$';

class SU_CompareDataController
{

	private $DBWork;

	public function __construct()
	{
		$this->DBWork=SU_CompareDataConnection::SingleTone();
		
		if (!$this->DBWork)
		{
			self::ErrorMessage("Не могу создать объект работы с базой");
		}
		
		//session_start();
 		$_SESSION['Script']=NULL;

	}
	public function exec()
	{			
		if (!(array_key_exists('MasterBaseChoise',$_REQUEST) or
		array_key_exists('SlaveBaseChoise',$_REQUEST)))
		{
			self::ErrorMessage("От пользователя получен неполный набор данных");
		}

		if (($_REQUEST['SlaveBaseChoise']=='') or ($_REQUEST['MasterBaseChoise']==''))
		{
			self::ErrorMessage("От пользователя получен недопустимый набор данных");
		}
		
		if (isset($_REQUEST['tables']))
		{
			$_SESSION['tables'] = $_REQUEST['tables'];
		}

		if (!isset($_SESSION['tables']))
		{
			if (!is_array($_REQUEST['tables']) || count($_REQUEST['tables']) == 0)
			{
				self::ErrorMessage("Не выбраны таблицы для сравнения");
			}
		} else {
			$tables = $_SESSION['tables'];	
		}
		
		$MasterTableName=$_REQUEST['MasterBaseChoise'];
		$SlaveTableName=$_REQUEST['SlaveBaseChoise'];
		

		$SQLQuery=SU_CompareDataComparison::CompareTableData($this->DBWork, $MasterTableName, $SlaveTableName, $tables);
		$SQLQueryResultTableCaption=" данных таблиц";

		if (count($SQLQuery) == 0)
		{
			self::ErrorMessage("Корректно выполнить сравнение не удалось");
		}
		else
		{
			$EmptyTableFlag=TRUE;
			$SQLScriptGeneration=FALSE;

			echo " <SCRIPT LANGUAGE='JavaScript'>   
				  <!-- 
				  var I;
				   function select_all_script()
				   {
				   		Count=document.Scriptform.elements.length;
				   		if (Count)
				   		{
				   			for (I=0;I<Count;I++)
				   			{
				   				if (document.Scriptform.elements[I].type=='checkbox')
				   				{
				   					document.Scriptform.elements[I].checked=true;
				   				}   				
				   			}
				   		} 
				   };
				   function unselect_all_script() 
				   {
				   	
				   		Count=document.Scriptform.elements.length;
				   		if (Count)
				   		{
				   			for (I=0;I<Count;I++)
				   			{
				   				if (document.Scriptform.elements[I].type=='checkbox')
				   				{
				   					document.Scriptform.elements[I].checked=false;
				   				}   				
				   			}
				   		}
				   };
				  //--> 
				 </SCRIPT>";
			
			//Выводим заголовок
			$HTMLText.="<a href='#dawn' style=''><input type=button value='Go down'></a>";
			$HTMLText .= "<center><B> Результаты сравнения $SQLQueryResultTableCaption баз $MasterTableName (Master) и $SlaveTableName (Slave) </B></center><br>";
			flush();
			$HTMLText .= "<table border=0 bgcolor='#1C1C1C' cellpadding='2' cellspacing='2'>";
			
			
			//Выводим список всех таблиц
			foreach ($SQLQuery as $tableName => $tableData)
			{
				$HTMLText .= "<tr>
								<td bgcolor='#E8E8E8'>";
				if ($tableData['countUpdate'] == 0 && $tableData['countDelete'] == 0 && $tableData['countInsert'] == 0)
				{
					$HTMLText .= "$tableName";
				} else {
					$HTMLText .= "<a href='#$tableName'>$tableName</a>";
				}
				
				$HTMLText .= "</td>
							  <td bgcolor='#E8E8E8'>";
				
				//Выводим количество полученных запросов
				if ($tableData['countUpdate'] != 0)
				{
					$HTMLText .= " <font color='#0000FF'>Update(".$tableData['countUpdate'].")<font> ";
				}
				
				if ($tableData['countDelete'] != 0)
				{
					$HTMLText .= " <font color='#FF0000'>Delete(".$tableData['countDelete'].")<font> ";
				}
				
				if ($tableData['countInsert'] != 0)
				{
					$HTMLText .= " <font color='#009900'>Insert(".$tableData['countInsert'].")<font> ";
				}
				$HTMLText .= "</tr>";
				flush();	

			}
			$HTMLText .= "</table>";

			$HTMLText .= "<FORM ACTION='' NAME='Scriptform' METHOD='POST'>";
			$HTMLText .= "<input type=hidden name=MasterBaseChoise value=$MasterTableName >";
			$HTMLText .= "<input type=hidden name=SlaveBaseChoise value=$SlaveTableName >";
			
			$Index = 0;
			
			foreach ($SQLQuery as $tableName => $tableData)
			{
				$HTMLText .= "<table border='0' bgcolor='#1C1C1C' cellpadding='1' cellspacing='1' width='100%'>";
				
				//Имя таблицы
				$HTMLText .= "<tr>
								<td bgcolor='#E8E8E8' align='center'>
									<b><i><a name='$tableName'>$tableName</a><br></i></b>
								</td>
							</tr>
							</tr>
								<td>
									<table border='0' width='100%' height='100%' cellpadding='2' cellspacing='2'>";
				
				//Список столбцов
				$HTMLText .= "<tr>
									<td bgcolor='#E8E8E8' width='150'>
										&nbsp;
									</td>";
				foreach ($tableData['fields'] as $key => $fieldName)
				{
					$HTMLText .= "
									<td bgcolor='#E8E8E8' align='center'>
										<font color=''>$fieldName</font>
									</td>";
				}
				$HTMLText .= "</tr>";
				
				/*
				foreach ($SQLQuery as $tableName => $tableData)
				{
				echo "<table border='1'>";
				//Значения столбцов
				foreach ($tableData['Data'] as $arKey => $arData)
				{
					echo "<tr>";
						echo "<td>";
							echo 3;
						echo "</td>";
						echo "<td>";
							echo $arData['data']['id_localizedtext'];
						echo "</td>";
						echo "<td>";
							echo $arData['data']['text'];
						echo "</td>";
					echo "</tr>";
					
					echo "<tr>";
						echo "<td>";
							echo 3;
						echo "</td>";
						echo "<td>";
							echo $arData['data']['id_localizedtext'];
						echo "</td>";
						echo "<td>";
							echo $arData['oldData']['text'];
						echo "</td>";
					echo "</tr>";
				}
				}
				echo "</table>";
				
				*/
				//Значения столбцов
				foreach ($tableData['Data'] as $arKey => $arData)
				{
					if ($arData['action'] == 'UPDATE')
					{
						$rowspan = 'rowspan="2"';
					} else {
						$rowspan = '';
					}
						
					$HTMLText .= "<tr>
										<td bgcolor='#E8E8E8' width='120' align='center' $rowspan>
											<font color='".$arData['color']."'>".$arData['action']."</FONT><br><INPUT NAME='AcceptScript[$Index]' TYPE='checkbox' CHECKED></font>
										</td>";
					
					//Кладем скрипт в сессию
					$EmptyTableFlag=FALSE;
					$SQLScriptGeneration=true;
					$_SESSION['Script'][$Index] = $arData['script'];
					$Index ++;
					
					$bopen = "";
					$bclose = "";
						
					if ($arData['action'] == 'UPDATE')
					{
						foreach ($tableData['fields'] as $key => $fieldName)
						{
							if (strlen($arData['oldData'][$fieldName]) > 600)
							{
								//Если значение столбца больше 50 символов, то выводим его 50 первых символов
								$str = "<div title='".substr($arData['oldData'][$fieldName], 0, 600)."'>".substr(htmlspecialchars($arData['oldData'][$fieldName]), 0, 600)."...</div>";
							} elseif (strlen($arData['oldData'][$fieldName]) == 0)
							{
								//Если строка пустая, то выводим пробел
								$str = "&nbsp;";
							} else {
								$str = htmlspecialchars($arData['oldData'][$fieldName]);
							}
							
							$HTMLText .= "<td bgcolor='#FFFFFF' align='center'>
												<font color='".$arData['color']."'> $str </font>
										</td>";
						}
						$HTMLText .= "</tr>";
						$HTMLText .= "<tr>";
						
						$bopen = "<b>";
						$bclose = "</b>";
					} 
					
					foreach ($tableData['fields'] as $key => $fieldName)
					{
						
						if (strlen($arData['data'][$fieldName]) > 600)
						{
							//Если значение столбца больше 50 символов, то выводим его 50 первых символов
							$str = "<div title='".substr($arData['data'][$fieldName], 0, 600)."'>".substr(htmlspecialchars($arData['data'][$fieldName]), 0, 600)."...</div>";
						} elseif (strlen($arData['data'][$fieldName]) == 0)
						{
							//Если строка пустая, то выводим пробел
							$str = "&nbsp;";
						} else {
							$str = htmlspecialchars($arData['data'][$fieldName]);
						}
						
						$HTMLText .= "<td bgcolor='#E8E8E8' align='center'>
											<font color='".$arData['color']."'>$bopen $str $bclose</font>
									</td>";
					}
					$HTMLText .= "</tr>";
				}
				
				$HTMLText .= "</table>
								</td>
							</tr>
						</table>";
				$HTMLText .= "<br><br>";
				flush();
				
			}
			
			if ($EmptyTableFlag == false)
			{
				$HTMLText .= "</table>";
			}

			$HTMLText .= "</TABLE> <br> <br>";
			
			if ($EmptyTableFlag)
			{
				$HTMLText = "Нет SQL скриптов для отображения <br> <br>";
			}
			
			//Если есть, то выводим ошибки
			if (array_key_exists('Error', $SQLQuery))
			{
				if (count($SQLQuery['Error']))
				{
					$HTMLText .= "<TABLE BORDER='1'>";
					$HTMLText .= "<TR>";
					$HTMLText .= "<TH COLSPAN='2' ALIGN='center'> <B> Ошибки, выявленные при сравнении $SQLQueryResultTableCaption баз $MasterTableName (Master) и $SlaveTableName (Slave) </B> </TH>";
					$HTMLText .= "</TR>";
					$HTMLText .= "<TR>";
					$HTMLText .= "<TD ALIGN='center'> <B> № </B> </TD>";
					$HTMLText .= "<TD ALIGN='center'> <B> Описание ошибки </B> </TD>";
					$HTMLText .= "</TR>";
					for ($Index=0; $Index<count($SQLQuery['Error']); $Index++)
					{
						if ($SQLQuery['Error'][$Index]==NULL)
						{
							self::ErrorMessage("В массиве ошибок сравнения встречено недопустимое значение");
						}
						else
						{
							$HTMLText .= "<TR>";
							$IndexC=$Index+1;
							$HTMLText .= "<TD> $IndexC </TD>";
							$HTMLText .= "<TD> <FONT COLOR='FF0000'>".$SQLQuery['Error'][$Index]."</FONT> </TD>";
							$HTMLText .= "</TR>";
						}
					}
					$HTMLText .= "</TABLE> <br> <br>";
				}
				flush();
			}

			if (!$EmptyTableFlag)
			{
				if ($SQLScriptGeneration)
				{
					
					$HTMLText .= "<input type='hidden' value='showSQL' name='action'>";
					$HTMLText .= "<a name='dawn'><INPUT NAME='OKButton2' TYPE='submit' VALUE='Принять'></a> \n";
					$HTMLText .= "<INPUT NAME='OKButton1' TYPE='button' onClick='select_all_script()' VALUE='Выбрать все'> \n";
					$HTMLText .= "<INPUT NAME='OKButton1' TYPE='button' onClick='unselect_all_script()' VALUE='Отказ от всех'> <br>";
				}
				$HTMLText .= "</FORM>";
				
			}
			echo "$HTMLText <br> <br>";
		}
	}
	
	public function ErrorMessage ($Message)
	{
		assert(is_string($Message) && ($Message!=''));
		
		echo $Message, "<br>\n", "<br>\n", "<br>\n";
		
		exit;
	}
}

?>