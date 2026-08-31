<?php

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
 
 if (!array_key_exists('SQLQuery', $_SESSION))
 { 	
 	Errors::ErrorMessage("Не удалось передать массив с SQL скриптом между скриптами");
 }
 
 if (array_key_exists('AcceptScript', $_POST))
 {
 	echo "Были выбраны следующие SQL скрипты: <br> <br>";
 	
 	$OutputText=NULL;
 	 	 	
 	foreach ($_POST['AcceptScript'] as $Key => $Value)
 	{
 		if ($OutputText==NULL)
 		{
 			$OutputText=$_SESSION['SQLQuery']['Script'][$Key]."<br>";
 		}
 		else
 		{
 			$OutputText.=$_SESSION['SQLQuery']['Script'][$Key]."<br>";
 		}
 	} 	
 	echo $OutputText;
 }
 else
 {
 	echo "Не было выбрано ни одного SQL скрипта";
 }
 
  ?>
 
 </BODY>
</HTML>