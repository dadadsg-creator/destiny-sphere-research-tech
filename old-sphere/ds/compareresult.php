<?php

 	session_start();
 	$_SESSION['SQLQuery']=NULL;

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<HTML>
 <HEAD>
  <TITLE> Результат сравнения элементов баз данных</TITLE>
 </HEAD>
 <BODY>
 
 <?php
 
 include_once ("config/config.cfg.php");
 include_once ("classes/Errors.class.php");
 include_once ("classes/Сonnection.class.php");
 include_once ("classes/Comparison.class.php");
 
 $DBWork=Connection::SingleTone();

 if (!$DBWork)
 {
  	 Errors::ErrorMessage("Не могу создать объект работы с базой");	
 }
 
 if (!(array_key_exists('OperationChoise',$_GET) or array_key_exists('MasterBaseChoise',$_GET) or 
 	   array_key_exists('SlaveBaseChoise',$_GET)))
 {
 	 Errors::ErrorMessage("От пользователя получен неполный набор данных");  	
 }
 
 if (($_GET['OperationChoise']=='') or ($_GET['SlaveBaseChoise']=='') or ($_GET['MasterBaseChoise']==''))
 {
 	Errors::ErrorMessage("От пользователя получен недопустимый набор данных");
 }

 if (array_key_exists('ShowEqualElement',$_GET))
 {
 	$ShowEqualElement=TRUE;
 }
 else
 {
 	$ShowEqualElement=FALSE;
 }
 
 $MasterTableName=$_GET['MasterBaseChoise'];
 $SlaveTableName=$_GET['SlaveBaseChoise'];
 
 switch ($_GET['OperationChoise'])
 {
 	case 'CompareTables':
 		$SQLQuery=Comparison::CompareTables($DBWork, $MasterTableName, $SlaveTableName);
 		$SQLQueryResultTableCaption="таблиц";
 		break;
 	case 'CompareColumns':
 		$SQLQuery=Comparison::CompareColumns($DBWork, $MasterTableName, $SlaveTableName);
 		$SQLQueryResultTableCaption="столбцов таблиц";
 		break;
 	case 'CompareUniqueConstraint':
 		break;
 	case 'ComparePrimaryKeyConstraint':
 		break;
 	case 'CompareCheckConstraint':
 		break;
 	case 'CompareForeignKeyConstraint':
 		break;
 	case 'CompareFunction':
 		break;
 	case 'CompareIndex':
 		break;
 	case 'CompareTrigger':
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
    if ((!array_key_exists('Comment', $SQLQuery)) or (!array_key_exists('Script', $SQLQuery)) or (count($SQLQuery['Comment'])!=count($SQLQuery['Script'])))
    {
    	Errors::ErrorMessage("Массив с результатами сравнения имеет недопустимый формат");
    }
 	
    $_SESSION['SQLQuery']=$SQLQuery;
    
    $EmptyTableFlag=TRUE;
    $SQLScriptGeneration=FALSE;
    
    $HTMLText = "<FORM ACTION='scriptresult.php' NAME='Scriptform' METHOD='POST'>";
 	$HTMLText .= "<TABLE BORDER='2'>";
 	$HTMLText .= "<TH COLSPAN='4' ALIGN='center'> <B> Результаты сравнения $SQLQueryResultTableCaption баз $MasterTableName (Master) и $SlaveTableName (Slave) </B> </TH>";
	$HTMLText .= "<TR>";
	$HTMLText .= "<TD ALIGN='center'> <B> № </B> </TD>";
	$HTMLText .= "<TD ALIGN='center'> <B> Комментарий </B> </TD>";
	$HTMLText .= "<TD ALIGN='center'> <B> SQL скрипт </B> </TD>";
	$HTMLText .= "<TD ALIGN='center'> <B> Добавить <br> скрипт </B> </TD>";
	$HTMLText .= "</TR>";
 	$IndexC=1;	
 	for ($Index=0; $Index<count($SQLQuery['Comment']); $Index++)
 	{ 		
 		
 		if ($ShowEqualElement)
 		{
 			$EmptyTableFlag=FALSE;
 			$HTMLText .= "<TR>";
 			$HTMLText .= "<TD> $IndexC </TD>"; 
 			$IndexC++;		
 			$HTMLText .= "<TD>".$SQLQuery['Comment'][$Index]."</TD>";
 			if ($SQLQuery['Script'][$Index] == NULL)
 			{
 				$HTMLText .= "<TD ALIGN='center'> нет </TD> <TD ALIGN='center'> нет </TD>";
 			}
 			else
 			{
 				$SQLScriptGeneration=TRUE;
 				$HTMLText .= "<TD>".$SQLQuery['Script'][$Index]."</TD>";
 				$HTMLText .= "<TD ALIGN='center'> <INPUT NAME='AcceptScript[$Index]' TYPE='checkbox' </TD>";
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
 				$HTMLText .= "<TD> $IndexC </TD>"; 
 				$IndexC++;		
 				$HTMLText .= "<TD>".$SQLQuery['Comment'][$Index]."</TD>";
 				$HTMLText .= "<TD>".$SQLQuery['Script'][$Index]."</TD>";
 				$HTMLText .= "<TD ALIGN='center'> <INPUT NAME='AcceptScript[$Index]' TYPE='checkbox' </TD>";
 				$HTMLText .= "</TR>";
 			}
 		}	
 	}
 	
 	$HTMLText .= "</TABLE> <br> <br>";
 	if ($EmptyTableFlag)
 	{
 		$HTMLText="Нет SQL скриптов для отображения <br> <br>";
 	}
 	if (array_key_exists('Error', $SQLQuery))
 	{
 		if (count($SQLQuery['Error']))
 		{ 		
 			$HTMLText .= "<TABLE BORDER='2'>";
 			$HTMLText .= "<TH COLSPAN='2' ALIGN='center'> <B> Ошибки, выявленные при сравнени $SQLQueryResultTableCaption баз $MasterTableName (Master) и $SlaveTableName (Slave) </B> </TH>";
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
 			$HTMLText .= "<INPUT NAME='OKButton2' TYPE='submit' VALUE='Принять'> <br>";
 		}
 		$HTMLText .= "</FORM>";
 	} 	
 	echo "$HTMLText <br> <br>";
 }
 ?>
 
 </BODY>
</HTML>