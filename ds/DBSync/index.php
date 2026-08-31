<?php
ini_set('display_errors', true);
ini_set('memory_limit', '50M');

session_start();

echo "cur op = ".$_SESSION['CurrentOperation']."<br>";
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<HTML>
 <HEAD>
  <META http-equiv="Content-Type" content="text/html; charset=windows-1251">
  <TITLE> Макрос сравнения баз данных </TITLE>
 </HEAD>
 <BODY>  

<?php
include_once ("config/config.cfg.php");

echo "<h1>Database Synchronization</h1>";

$options = array(
				0  => array("val" => "CompareTables", "description" => "  Сравнить таблицы двух баз"),
				1  => array("val" => "CompareColumns", "description" => " Сравнить столбцы таблиц двух баз"),
    			2  => array("val" => "CompareUniqueConstraint", "description" => " Сравнить UNIQUE ограничения  двух баз"),
   				3  => array("val" => "ComparePrimaryKeyConstraint", "description" => " Сравнить Primary Keys таблиц двух баз"),
   				4  => array("val" => "CompareCheckConstraint", "description" => " Сравнить Check ограничения таблиц двух баз"),
   				5  => array("val" => "CompareForeignKeyConstraint", "description" => " Сравнить Foreign Keys таблиц двух баз"),
   				6  => array("val" => "CompareFunction", "description" => " Сравнить функции двух баз"),
   				7  => array("val" => "CompareIndex", "description" => " Сравнить индексы таблиц двух баз"),
   				8  => array("val" => "CompareTrigger", "description" => " Сравнить триггеры двух баз"),
   				9  => array("val" => "CompareData", "description" => " Сравнить данные таблиц двух баз"),
				);

//Если есть имя баз в сессии
if (isset($_SESSION['MasterBaseChoise']))
{
	$currentMasterBaseChoise = $_SESSION['MasterBaseChoise'];
} else {
	$currentMasterBaseChoise = "";
}

if (isset($_SESSION['SlaveBaseChoise']))
{
	$currentSlaveBaseChoise = $_SESSION['SlaveBaseChoise'];
}else {
	$currentSlaveBaseChoise = "";
}

//Если есть операция, котороя была завершена, переходим к следующей
if (isset($_SESSION['CurrentOperation']))
{
	$LastOperation = $_SESSION['CurrentOperation'];
	
	switch ($LastOperation)
	{
		case 'CompareTables':
			$currentOperation = 'CompareColumns';
			break;
		case 'CompareColumns':
			$currentOperation = 'CompareUniqueConstraint';
			break;
		case 'CompareUniqueConstraint':
			$currentOperation = 'ComparePrimaryKeyConstraint';
			break;
		case 'ComparePrimaryKeyConstraint':
			$currentOperation = 'CompareCheckConstraint';
			break;
		case 'CompareCheckConstraint':
			$currentOperation = 'CompareForeignKeyConstraint';
			break;
		case 'CompareForeignKeyConstraint':
			$currentOperation = 'CompareFunction';
			break;
		case 'CompareFunction':
			$currentOperation = 'CompareIndex';
			break;
		case 'CompareIndex':
			$currentOperation = 'CompareTrigger';
			break;
		case 'CompareTrigger':
			$currentOperation = 'CompareColumns';
			break;
		case 'CompareData':
			$currentOperation = 'CompareData';
			break;
	}
}

//Заполняем форму
echo "<FORM ACTION='compareresult.php' NAME='Choiseform' METHOD='GET'>";
echo "<H3>Выберите операцию над базами</H3>";
echo "<SELECT NAME=OperationChoise>";
foreach ($options  as $key => $arr)
{
	if ($arr['val'] == $currentOperation)
	{
		$selected = ' selected ';
	}
	else 
	{
		$selected = "";
	}
	
	echo "<OPTION VALUE=".$arr['val']."$selected> ".$arr['description'];
}

assert(array_key_exists('List_of_Bases', $GLOBALS));
$baseList = $GLOBALS['List_of_Bases'];

		
echo "</SELECT>";
echo "<H3>Выберите первую базу (master)</H3>";
echo "<SELECT NAME=MasterBaseChoise>";
foreach ($baseList as $name => $data)
{
	if ($name == $currentMasterBaseChoise)
	{
		$selected = ' selected ';
	}
	else 
	{
		$selected = "";
	}
	
	echo "<OPTION VALUE='$name' $selected> $name";
}
echo "</SELECT>";
echo "<H3>Выберите вторую базу (slave)</H3>";
echo "<SELECT NAME=SlaveBaseChoise>";
foreach ($baseList as $name => $data)
{
	if ($name == $currentSlaveBaseChoise)
	{
		$selected = ' selected ';
	}
	else 
	{
		$selected = "";
	}
	echo "<OPTION $selected VALUE='$name'> $name";
}
echo "</SELECT> <br> <br> <br>";
echo "<H3>Показывать одинаковые объекты   <INPUT NAME='ShowEqualElement' TYPE='checkbox'> </H3> <br>";
echo "<INPUT NAME='OKButton1' TYPE='submit' VALUE='Выполнить'>";
echo "</FORM>";

$_SESSION = "";

?>

 </BODY>
</HTML>