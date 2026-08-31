<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/10/24 10:39:41 $';

	session_start();
 	$_SESSION['SQLQuery']=NULL;

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<HTML>
 <HEAD>
  <TITLE> Результат сравнения элементов баз данных</TITLE>
 </HEAD>
 <BODY>
 <SCRIPT LANGUAGE='JavaScript'>   
  <!-- 
  var I;
   function select_all_script()
   {
   		Count=document.forms[1].elements.length;
   		if (Count)
   		{
   			for (I=0;I<Count;I++)
   			{
   				if (document.forms[1].elements[I].type=='checkbox')
   				{
   					document.forms[1].elements[I].checked=true;
   				}   				
   			}
   		} 
   };
   function unselect_all_script() 
   {
   		Count=document.forms[1].elements.length;
   		if (Count)
   		{
   			for (I=0;I<Count;I++)
   			{
   				if (document.forms[1].elements[I].type=='checkbox')
   				{
   					document.forms[1].elements[I].checked=false;
   				}   				
   			}
   		}
   };
  //--> 
 </SCRIPT> 
  
 <?php

 include_once ("config/config.cfg.php");
 include_once ("classes/Errors.class.php");
 include_once ("classes/Connection.class.php");
 include_once ("classes/Comparison.class.php");
 include_once ('../classes/SU/SU_ExecSQLforComparition.class.php');
 include_once('../classes/SU/SU_CompareDataConnection.class.php');
 
  $HTMLText = "<form action='' method=POST>";
  $HTMLText .= "<input type=submit value=' Back ' name='backToStart'>";
  $HTMLText .= "</form>";
 
 $DBWork=Connection::SingleTone();
if(isset($_REQUEST['backToStart']))
{
	echo "<script language='javascript'>document.location.href='http://unlim/ds/DBSync';</script>";
}

if(isset($_REQUEST['backOneStep']))
{
	unset($_REQUEST['AcceptScript']);
	if($_REQUEST['action']=='executeSQL')
	{
		unset($_REQUEST['action']);
	}
}

 
 if (!$DBWork)
 {
  	 Errors::ErrorMessage("Не могу создать объект работы с базой");	
 }

 if (array_key_exists('ShowEqualElement',$_GET))
 {
 	$ShowEqualElement=TRUE;
 }
 else
 {
 	$ShowEqualElement=FALSE;
 }

 if (isset($_REQUEST['MasterBaseChoise']))
 {
	 $MasterTableName=$_REQUEST['MasterBaseChoise'];
	  $_SESSION['MasterBaseChoise'] = $_REQUEST['MasterBaseChoise'];
 }
  if (isset($_REQUEST['OperationChoise']))
 {
	 $currentOperation=$_REQUEST['OperationChoise'];
	  $_SESSION['CurrentOperation'] = $currentOperation;
 }
 
 if (isset($_SESSION['MasterBaseChoise']))
 {
	 $MasterTableName=$_SESSION['MasterBaseChoise'];
 }
 
 if (isset($_SESSION['SlaveBaseChoise']))
 {
	 $SlaveTableName= $_SESSION['SlaveBaseChoise'];
 } 
 
 
 if (isset($_REQUEST['SlaveBaseChoise']))
 {
	 $SlaveTableName=$_REQUEST['SlaveBaseChoise'];
	 $_SESSION['SlaveBaseChoise'] = $_REQUEST['SlaveBaseChoise'];
 }

 
 if (isset($_SESSION['CurrentOperation']))
 {
	 $currentOperation=$_SESSION['CurrentOperation'];
 }
 
  if (!(array_key_exists('OperationChoise',$_SESSION) or array_key_exists('MasterBaseChoise',$_SESSION) or 
 	   array_key_exists('SlaveBaseChoise',$_SESSION)))
 {
 	 Errors::ErrorMessage("От пользователя получен неполный набор данных1");  	
 }
 //print_r($_SESSION);
 if (!isset($_SESSION['CurrentOperation']) or !isset($_SESSION['SlaveBaseChoise']) or !isset($_SESSION['MasterBaseChoise']))
 {
 	Errors::ErrorMessage("От пользователя получен недопустимый набор данных22");
 }
  echo "currentOperation = $currentOperation<br>";
  
 if (key_exists('AcceptScript', $_REQUEST))//Если скрипт имеется, вызываем класс по его исполнению 
 {
 //print_r($_SESSION);

	 $execSQL= new SU_ExecSQLforComparition;
	 $execSQL->showSQL($_SESSION['Script']);
	 //unset($_REQUEST['AcceptScript']);
 }
 elseif ($_REQUEST['action']=='executeSQL')//Если скрипт имеется, вызываем класс по его исполнению 
 {

 	$execSQL= new SU_ExecSQLforComparition;
	$execSQL->executeSQL($_SESSION['Script']);
	//unset($_REQUEST['action']);
	 
 }
 else 
 {

	 switch ($currentOperation)
	 {
	 	case 'CompareTables':
	 		$SQLQuery=Comparison::CompareTables($DBWork, $MasterTableName, $SlaveTableName);
	 		$SQLQueryResultTableCaption="таблиц";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	case 'CompareColumns':
	 		$SQLQuery=Comparison::CompareColumns($DBWork, $MasterTableName, $SlaveTableName);
	 		$SQLQueryResultTableCaption="столбцов таблиц";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	case 'CompareUniqueConstraint':
	 		$SQLQuery=Comparison::CompareTableConstraints($DBWork, $MasterTableName, $SlaveTableName, "u");
	 		$SQLQueryResultTableCaption="Unique ограничений таблиц";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	case 'ComparePrimaryKeyConstraint':
	 		$SQLQuery=Comparison::CompareTableConstraints($DBWork, $MasterTableName, $SlaveTableName, "p");
	 		$SQLQueryResultTableCaption="PrimaryKey ограничений таблиц";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	case 'CompareCheckConstraint':
	 		$SQLQuery=Comparison::CompareTableConstraints($DBWork, $MasterTableName, $SlaveTableName, "c");
	 		$SQLQueryResultTableCaption="Check ограничений таблиц";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	case 'CompareForeignKeyConstraint':
	 		$SQLQuery=Comparison::CompareTableConstraints($DBWork, $MasterTableName, $SlaveTableName, "f");
	 		$SQLQueryResultTableCaption="ForeignKey ограничений таблиц";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	case 'CompareFunction':
	 		$SQLQuery=Comparison::CompareTableFunction($DBWork, $MasterTableName, $SlaveTableName);
	 		$SQLQueryResultTableCaption="функций";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	case 'CompareIndex':
	 		$SQLQuery=Comparison::CompareTableIndexes ($DBWork, $MasterTableName, $SlaveTableName);
	 		$SQLQueryResultTableCaption="индексов таблиц";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	case 'CompareTrigger':
	 		$SQLQuery=Comparison::CompareTableTriggers($DBWork, $MasterTableName, $SlaveTableName);
	 		$SQLQueryResultTableCaption="триггеров таблиц";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	case 'CompareData':
	 		$SQLQuery=Comparison::CompareTableData($DBWork, $MasterTableName, $SlaveTableName);
	 		$SQLQueryResultTableCaption="данных таблиц";
	 		$_SESSION['Script']=$SQLQuery['Script'];
	 		break;
	 	default:
	 		Errors::ErrorMessage("От пользователя получен недопустимый тип операции");
	 }
	
	 if (count($SQLQuery) == 0)
	 {
	 	Errors::ErrorMessage("Корректно выполнить сравнение не удалось");
	 }
	 else
	 {
	    if ((!array_key_exists('Comment', $SQLQuery)) 
	                 or (!array_key_exists('Script', $SQLQuery)) or (count($SQLQuery['Comment'])!=count($SQLQuery['Script'])))
	    {
	    	Errors::ErrorMessage("Массив с результатами сравнения имеет недопустимый формат");
	    }
	 	
	    $_SESSION['SQLQuery']=$SQLQuery;
	    
	    $EmptyTableFlag=TRUE;
	    $SQLScriptGeneration=FALSE;
	    
	    if (array_key_exists('Color', $SQLQuery))
	    {
	    	$ColorFlag=TRUE;
	    }
	    else
	    {
	    	$ColorFlag=FALSE;
	    }
	    
	    if (array_key_exists('Header', $SQLQuery))
	    {
	    	$HeaderFlag=TRUE;
	    }
	    else
	    {
	    	$HeaderFlag=FALSE;
	    }
	    
	    $HTMLText .= "<b>".$_SESSION['CurrentOperation']."</b><br>";
	    
	    $HTMLText .= "<FORM ACTION='' NAME='Scriptform' METHOD='POST'>";
	 	$HTMLText .= "<TABLE BORDER='2' WIDTH=100%>";
	 	$HTMLText .= "<TR>";
	 	$HTMLText .= "<TH COLSPAN='4' ALIGN='center'> <B> Результаты сравнения $SQLQueryResultTableCaption баз $MasterTableName (Master) и $SlaveTableName (Slave) </B> </TH>";
	 	$HTMLText .= "</TR>";
		$HTMLText .= "<TR>";
		$HTMLText .= "<TD ALIGN='center'> <B> № </B> </TD>";
		$HTMLText .= "<TD ALIGN='center'> <B> Комментарий </B> </TD>";
		$HTMLText .= "<TD ALIGN='center'> <B> SQL скрипт </B> </TD>";
		$HTMLText .= "<TD ALIGN='center'> <B> Добавить <br> скрипт </B> </TD>";
		$HTMLText .= "</TR>";
	 	$IndexC=1;	
	 	for ($Index=0; $Index<count($SQLQuery['Comment']); $Index++)
	 	{ 	
	 		if ($HeaderFlag)
	 		{
	 			if ($SQLQuery['Header'][$Index])
	 			{
	 				$HTMLText .= "<TR>";
	 				$HTMLText .= "<TH COLSPAN='4' ALIGN='center'> <B> <I>".$SQLQuery['Comment'][$Index]."</I> </B> </TH>";
	 				$HTMLText .= "</TR>";
	 			}
	 			else
	 			{ 	
	 				 if ($ShowEqualElement)
	 				{
	 					$EmptyTableFlag=FALSE;
	 					$HTMLText .= "<TR>";
	 					if ($ColorFlag)
	 					{
	 						$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'> $IndexC </FONT> </TD>";
	 					}
	 					else
	 					{
	 						$HTMLText .= "<TD> $IndexC </TD>";
	 					}
	 					$IndexC++;
	 					if ($ColorFlag)
	 					{ 			
	 						$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'>".$SQLQuery['Comment'][$Index]."</FONT> </TD>";
	 					}
	 					else
	 					{
	 						$HTMLText .= "<TD>".$SQLQuery['Comment'][$Index]."</TD>";
	 					}
	 					if ($SQLQuery['Script'][$Index] == NULL)
	 					{
	 						if ($ColorFlag)
	 						{
	 							$HTMLText .= "<TD ALIGN='center'> <FONT COLOR='".$SQLQuery['Color'][$Index]."'> нет </FONT> </TD> <TD ALIGN='center'> <FONT COLOR='".$SQLQuery['Color'][$Index]."'> нет </FONT> </TD>";
	 						}
	 						else
	 						{
	 							$HTMLText .= "<TD ALIGN='center'> нет </TD> <TD ALIGN='center'> нет </TD>";
	 						}
	 					}
	 					else
	 					{
	 						$SQLScriptGeneration=TRUE;
	 						if ($ColorFlag)
	 						{
	 							if ($OperationChoise == 'CompareFunction')
	 							{
	 							$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'><pre>".$SQLQuery['Script'][$Index]."</pre></FONT> </TD>";
	 							}
	 							else {
	 							$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'>".$SQLQuery['Script'][$Index]."</FONT> </TD>";
	 							}
	 						}
	 						else
	 						{
	 							if ($OperationChoise == 'CompareFunction')
	 							{
	 								$HTMLText .= "<TD><pre>".$SQLQuery['Script'][$Index]."</pre></TD>";
	 							}
	 							else {
	 								$HTMLText .= "<TD>".$SQLQuery['Script'][$Index]."</TD>";
	 							}
	 						}
	 						$HTMLText .= "<TD ALIGN='center'> <INPUT NAME='AcceptScript[$Index]' TYPE='checkbox' CHECKED </TD>";
	 					}
	 					$HTMLText .= "</TR>";
	 				}
	 				else
	 				{
	 					if ($SQLQuery['Script'][$Index]!=NULL)
	 					{
	 
	  						$EmptyTableFlag=FALSE;
	 						$SQLScriptGeneration=TRUE;
	 						$HTMLText .= "<TR>";
	 						if ($ColorFlag)
	 						{
	 							
	 							$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'> $IndexC </FONT> </TD>";
	 						}
	 						else
	 						{
	 							$HTMLText .= "<TD >$IndexC </TD>";
	 						}
	 						$IndexC++;
	 						if ($ColorFlag)
	 						{
	 							$HTMLText .= "<TD ><FONT COLOR='".$SQLQuery['Color'][$Index]."'>".$SQLQuery['Comment'][$Index]."</FONT></TD>";
	 						}
	 						else
	 						{
	 							$HTMLText .= "<TD >".$SQLQuery['Comment'][$Index]."</TD>";
	 						}
	 						if ($ColorFlag)
	 						{
	 							$HTMLText .= "<TD ><FONT COLOR='".$SQLQuery['Color'][$Index]."'>";
	 							if ($OperationChoise == 'CompareFunction')
	 							{
	 	 						$HTMLText .= "<pre>".$SQLQuery['Script'][$Index]."</pre>";
	 							}
	 							else {
	 							$HTMLText .= $SQLQuery['Script'][$Index];
	 							}
	 							$HTMLText .= "</FONT></TD>";
	  						}
	 						else
	 						{
	 							if ($OperationChoise == 'CompareFunction')
	 							{
	 								$HTMLText .= "<TD><pre>".$SQLQuery['Script'][$Index]."</pre></TD>";
	 							}
	 							else {
	 								$HTMLText .= "<TD>".$SQLQuery['Script'][$Index]."</TD>";
	 							}
	 						}
	 						$HTMLText .= "<TD  ALIGN='center'><INPUT NAME='AcceptScript[$Index]' TYPE='checkbox' CHECKED </FONT> </TD>"; 						
	 						$HTMLText .= "</TR>";
	 					}
	 				}
	 			} 			
	 		}
	 		else
	 		{
	 			if ($ShowEqualElement)
	 			{
	 				$EmptyTableFlag=FALSE;
	 				$HTMLText .= "<TR>";
	 				if ($ColorFlag)
	 				{
	 					$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'> $IndexC </FONT> </TD>";
	 				}
	 				else
	 				{
	 					$HTMLText .= "<TD> $IndexC </TD>";
	 				}
	 				$IndexC++;
	 				if ($ColorFlag)
	 				{ 			
	 					$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'>".$SQLQuery['Comment'][$Index]."</FONT> </TD>";
	 				}
	 				else
	 				{
	 					$HTMLText .= "<TD>".$SQLQuery['Comment'][$Index]."</TD>";
	 				}
	 				if ($SQLQuery['Script'][$Index] == NULL)
	 				{
	 					if ($ColorFlag)
	 					{
	 						$HTMLText .= "<TD ALIGN='center'> <FONT COLOR='".$SQLQuery['Color'][$Index]."'> нет </FONT> </TD> <TD ALIGN='center'> <FONT COLOR='".$SQLQuery['Color'][$Index]."'> нет </FONT> </TD>";
	 					}
	 					else
	 					{
	 						$HTMLText .= "<TD ALIGN='center'> нет </TD> <TD ALIGN='center'> нет </TD>";
	 					}
	 				}
	 				else
	 				{
	 					$SQLScriptGeneration=TRUE;
	 					if ($ColorFlag)
	 					{
	 						$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'>".$SQLQuery['Script'][$Index]."</FONT> </TD>";
	 					}
	 					else
	 					{
	 						$HTMLText .= "<TD>".$SQLQuery['Script'][$Index]."</TD>";
	 					}
	 					$HTMLText .= "<TD ALIGN='center'> <INPUT NAME='AcceptScript[$Index]' TYPE='checkbox' CHECKED </TD>";
	 				}
	 				$HTMLText .= "</TR>";
	 			}
	 			else
	 			{
	 				if ($SQLQuery['Script'][$Index]!=NULL)
	 				{
	 					$EmptyTableFlag=FALSE;
	 					$SQLScriptGeneration=TRUE;
	 					$HTMLText .= "<TR>";
	 					if ($ColorFlag)
	 					{
	 						$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'> $IndexC </FONT> </TD>";
	 					}
	 					else
	 					{
	 						$HTMLText .= "<TD> $IndexC </TD>";
	 					}
	 					$IndexC++;
	 					if ($ColorFlag)
	 					{
	 						$HTMLText .= "<TD> <FONT COLOR='".$SQLQuery['Color'][$Index]."'>".$SQLQuery['Comment'][$Index]."</FONT> </TD>";
	 					}
	 					else
	 					{
	 						$HTMLText .= "<TD>".$SQLQuery['Comment'][$Index]."</TD>";
	 					}
	 					if ($ColorFlag)
	 					{
	 						if ($OperationChoise == 'CompareFunction')
	 						{
	 						$HTMLText .= "<TD><FONT COLOR='".$SQLQuery['Color'][$Index]."'><pre>".$SQLQuery['Script'][$Index]."</pre></FONT></TD>";
	 						}
	 						else{
	 						$HTMLText .= "<TD><FONT COLOR='".$SQLQuery['Color'][$Index]."'>".$SQLQuery['Script'][$Index]."</FONT> </TD>";
	 						}
	 					}
	 					else
	 					{
	 						if ($OperationChoise == 'CompareFunction')
	 						{
	 						$HTMLText .= "<TD><pre>".$SQLQuery['Script'][$Index]."</TD>".$SQLQuery['Script'][$Index]."</pre></TD>";
	 						}
	 						else{
	 						$HTMLText .= "<TD>".$SQLQuery['Script'][$Index]."</TD>".$SQLQuery['Script'][$Index]."</TD>";
	 						}
	 					}
	 					$HTMLText .= "<TD  ALIGN='center'> <INPUT NAME='AcceptScript[$Index]' TYPE='checkbox' CHECKED </FONT> </TD>";
	 					$HTMLText .= "</TR>";
	 				}
	 			}
	 		}
	 	}
	
	 	$HTMLText .= "</TABLE> <br> <br>";
	 	if ($EmptyTableFlag)
	 	{
	 		$HTMLText = "<form action=index.php method=POST>";
	 		$HTMLText .= "<input type=hidden name=CurrentOperation value=$currentOperation>";
	 		$HTMLText .= "<input type=submit value=' Back '>";
	 		$HTMLText .= "</form>";
	 		$HTMLText .= "Нет SQL скриптов для отображения <br> <br>";
	 	}
	 	if (array_key_exists('Error', $SQLQuery))
	 	{
	 		if (count($SQLQuery['Error']))
	 		{
	 			$HTMLText .= "<TABLE BORDER='2'>";
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
						Errors::ErrorMessage("В массиве ошибок сравнения встречено недопустимое значение");
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
	 	}
	 	if (!$EmptyTableFlag)
	 	{
	 		if ($SQLScriptGeneration)
	 		{
	 			$OperationChoise = $_REQUEST['OperationChoise'];
	 			$HTMLText .= "<input type='hidden' value='showSQL' name='action'>";
	 			$HTMLText .= "<INPUT NAME='OKButton2' TYPE='submit' VALUE='Принять'> \n";
	 			$HTMLText .= "<INPUT NAME='OKButton1' TYPE='button' onClick='select_all_script()' VALUE='Выбрать все'> \n";
				$HTMLText .= "<INPUT NAME='OKButton1' TYPE='button' onClick='unselect_all_script()' VALUE='Отказ от всех'> <br>";
	 		}
	 		$HTMLText .= "</FORM>";
	 	} 	
	 	echo "$HTMLText <br> <br>";
	 } 
}
 
 
 ?>
 
 </BODY>
</HTML>