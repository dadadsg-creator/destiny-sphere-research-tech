<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/10/24 10:39:41 $';
 session_start();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<HTML>
 <HEAD>
  <TITLE> Итоговый скрипт </TITLE>
 </HEAD>
 <BODY>
 
 <?php
 include_once ("classes/Errors.class.php");
 include_once ("classes/ExecSQL.class.php");

 $sepasator = "-- -- -- -- -- -- -- -- -- --";
 
 $SlaverDaseName = $_SESSION['SlaveBaseChoise'];
 assert(!empty($SlaverDaseName));

 //Кнопка назад 
  echo "<form action=compareresult.php method=GET>";
  echo "<input type=submit value=' Back to Result of Comparison '>";
  echo "</form>";
  
  //Кнопка на индекс
  echo "<form action=index.php method=POST>";
  echo "<input type=submit value=' Back to index '>";
  echo "</form>";
  
 if ($_POST['action'] == 'showSQL')
 {
 if (!array_key_exists('SQLQuery', $_SESSION))
 {
 	Errors::ErrorMessage("Не удалось передать массив с SQL скриптом между скриптами");
 }

 if (array_key_exists('AcceptScript', $_POST))
 {
 	echo "Были выбраны следующие SQL скрипты: <br> <br>";

 	$OutputText=NULL;
	echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>";
 	echo "<table cellSpacing='0' cellPadding='5'>";
 	echo "<tr>";
 	echo "<td>";
 	
 	echo "<textarea name='query' rows='30' cols='110'>";
 	
 	foreach ($_POST['AcceptScript'] as $Key => $Value)
 	{
 		$OutputText = $_SESSION['SQLQuery']['Script'][$Key]."\r\n\r\n$sepasator\r\n\r\n";
 		echo $OutputText;
 	}
 	echo "</textarea>";
 	echo "</td>";
 	echo "</tr>";
 	echo "</table>";
 	echo "<input type='hidden' value='execSQL' name='action'>";
 	echo "<input type='submit' value='Execute' name='submit'>";
 	echo "</form>";
 }
 else
 {
 	echo "Не было выбрано ни одного SQL скрипта";
 }
 }
 if ($_POST['action'] == 'execSQL')
 {
 	//Кнопка назад
 	echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>";
 	echo "<input type='hidden' name='AcceptScript'>";
 	echo "<input type='hidden' value='showSQLSecond' name='action'>";
 	echo "<input type='submit' value='BACK' name='submit'>";
 	echo "</form>";
	
 	$sql =  $_POST['query'];
	$sql = stripcslashes($sql);
 	$sqlQueries = explode("$sepasator", $sql);
 	unset($_SESSION['NotExecutedSQL']);
 	
 	
 	for ($i=0; $i<(count($sqlQueries)-1); $i++)
 	{
 		$_SESSION['NotExecutedSQL'][] = $sqlQueries[$i];
 	}
 		
 	foreach ($sqlQueries as $key =>  $sqlQuery)
 	{
 		ExecSQL::Exec($sqlQuery, $SlaverDaseName);
 		unset($_SESSION['NotExecutedSQL'][$key]);
 	}
 	
 	echo "<br>_____________________________________END____________________________________________<br>";	
 	
 	//Кнопка в самое начало
 	echo "<form>";
	echo "<input type='button' value='Back to index' onClick=location.href='index.php?LastOperation=".$LastOperation."'>";
 	echo "</form>";

 }
if ($_POST['action'] == 'showSQLSecond')
{
	echo "Были выбраны следующие SQL скрипты: <br> <br>";
	
	$sql = $_SESSION['NotExecutedSQL'];

	$OutputText=NULL;
	echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>";
 	echo "<table cellSpacing='0' cellPadding='5'>";
 	echo "<tr>";
 	echo "<td>";
 	echo "<textarea name='query' rows='30' cols='110'>";
	
 	foreach ($sql as $Key => $Value)
 	{
 		$OutputText = $Value."$sepasator";
 		echo $OutputText;
 	}
 	echo "</textarea>";
 	echo "</td>";
 	echo "</tr>";
 	echo "</table>";
 	echo "<input type='hidden' value='execSQL' name='action'>";
 	echo "<input type='submit' value='Execute' name='submit'>";
 	echo "</form>";
}
  ?>
 
 </BODY>
</HTML>