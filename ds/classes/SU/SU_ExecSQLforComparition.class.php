<?php $CVS_Version = '$Revision$ ; $Date$';
session_start();
//compare data or db structure SQL execution

// Классу должны передаваться переменные сессии
//$_SESSION['MasterBaseChoise'] - база с которой сравнивают
//$_SESSION['SlaveBaseChoise'] - база, которую сравнивают
//$_SESSION['Script'] - SQL запросы
//$_SESSION['backOneStep'] - возврат к проводимому сравнению или другому шагу
//$_SESSION['backToStart'] - возврат к исходной точке
//$_Request['AcceptScript'] - может быть для проверки, все ли скрипты исполнять.


class SU_ExecSQLforComparition
{

	private $sepasator = "-- -- -- -- -- -- -- -- -- --";

	public function showSQL($data)
	{	
		$MasterTableName = $_SESSION['MasterBaseChoise'];
		$SlaveTableName = $_SESSION['SlaveBaseChoise'];

		//Кнопка назад
		echo "<form action='' method=POST>";
		echo "<input type=hidden name='MasterBaseChoise' value=$MasterTableName>";
		echo "<input type=hidden name='SlaveBaseChoise' value=$SlaveTableName>";
		echo "<input type=hidden name='action' value='compare'>";
		echo "<input type=submit value=' Back to Result of Comparision ' name='backOneStep'>";
		echo "</form>";

		//Кнопка на индекс
		echo "<form action='' method=POST>";
		echo "<input type=hidden name='MasterBaseChoise' value=$MasterTableName>";
		echo "<input type=hidden name='SlaveBaseChoise' value=$SlaveTableName>";
		echo "<input type=hidden name='action' value='back'>";
		echo "<input type=submit value=' Back to start ' name='backToStart'>";
		echo "</form>";

		if (!array_key_exists('Script', $_SESSION))
		{
			Errors::ErrorMessage("Не удалось передать массив с SQL скриптом между скриптами");
		}
		
		if(isset($_REQUEST['AcceptScript']))
		{
			foreach ($_SESSION['Script'] as $key=>$script)
			{
				if(!key_exists($key, $_REQUEST['AcceptScript']))
				{
					unset($_SESSION['Script'][$key]);
				}
			}
		}

		if (array_key_exists('Script', $_SESSION))
		{
			echo "Были выбраны следующие SQL скрипты: <br> <br>";

			$OutputText=NULL;
			echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>";
			echo "<input type=hidden name='MasterBaseChoise' value=$MasterTableName>";
			echo "<input type=hidden name='SlaveBaseChoise' value=$SlaveTableName>";
			echo "<table cellSpacing='0' cellPadding='5'>";
			echo "<tr>";
			echo "<td>";

			echo "<textarea type=text name='query' rows='30' cols='110'>";
			foreach ($_SESSION['Script'] as $key => $value)
			{
				if(isset($value))
				{
					//$OutputText = $_SESSION['SQLQuery'][$Key]."\r\n\r\n$this->sepasator\r\n\r\n";
					$OutputText=$value."\r\n\r\n$this->sepasator\r\n\r\n";
					echo $OutputText;
				}
			}
			echo "</textarea>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<input type='hidden' name='action'  value='executeSQL'>";
			echo "<input type='submit' value='Execute'>";
			echo "</form>";
		}
		else
		{
			echo "Не было выбрано ни одного SQL скрипта";
		}
	}

	public function executeSQL($data)
	{

		$LastOperation = $_SESSION['LastOperation'];
		$MasterTableName = $_SESSION['MasterBaseChoise'];
		$SlaveTableName = $_SESSION['SlaveBaseChoise'];


		//Кнопка назад
		echo "<form action='' method=POST>";
		echo "<input type=hidden name='MasterBaseChoise' value=$MasterTableName>";
		echo "<input type=hidden name='SlaveBaseChoise' value=$SlaveTableName>";
		echo "<input type='hidden' name='AcceptScript'>";
		echo "<input type=hidden name='action' value='showSQLsecond'>";
		echo "<input type='submit' value=' Back to Result of Comparison ' name='backOneStep'>";
		echo "</form>";

		//Кнопка на индекс
		echo "<form action='' method=POST>";
		echo "<input type=hidden name='MasterBaseChoise' value=$MasterTableName>";
		echo "<input type=hidden name='SlaveBaseChoise' value=$SlaveTableName>";
		echo "<input type=hidden name='action' value='back'>";
		echo "<input type=submit value=' Back to start ' name='backToStart'>";
		echo "</form>";

		$sql =$data;
		$sql=array_unique($sql);

		//$sql = stripcslashes($sql);
		//$sql = str_replace("\'", "'", $sql);
		//$sqlQueries = explode("$this->sepasator", $sql);
		unset($_SESSION['NotExecutedSQL']);


		for ($i=0; $i<(count($sql)-1); $i++)
		{
			$_SESSION['NotExecutedSQL'][] = $sql[$i];
		}

		foreach ($sql as $key =>  $sqlQuery)
		{
			if(isset($sqlQuery))
			{
				self::Exec($sqlQuery, $SlaveTableName);
				unset($_SESSION['NotExecutedSQL'][$key]);
			}
		}
		echo "<br>_____________________________________END____________________________________________<br>";
		
		//Кнопка на индекс
		echo "<form action='' method=POST>";
		echo "<input type=hidden name='MasterBaseChoise' value=$MasterTableName>";
		echo "<input type=hidden name='SlaveBaseChoise' value=$SlaveTableName>";
		echo "<input type=hidden name='action' value='back'>";
		echo "<input type=submit value=' Back to start ' name='backToStart'>";
		echo "</form>";

	}
	
	private function Exec($sqlQuery, $SlaveTableName)
	{

		$DBclass= new SU_CompareDataConnection;
		$DBWork = $DBclass->SingleTone();
		$startTime = microtime(true);
		$resultOfQuery = $DBclass->ExecWithErrorReporting($sqlQuery, $SlaveTableName);
		$endTime = microtime(true);
		$executionTime = $endTime - $startTime;
		
		if($resultOfQuery)
		{
			echo "<br><font color=#008B00>".$sqlQuery."</font>";
			echo "<br><font>OK</font><br>";
			echo "Execution Time: $executionTime  second<br>";
		}
		unset($_SESSION['Script']);
	}
	
	public function showSQLSecond()
	{
		echo "Были выбраны следующие SQL скрипты: <br> <br>";
		
		$sql = $_SESSION['NotExecutedSQL'];
		$MasterTableName = $_SESSION['MasterBaseChoise'];
		$SlaveTableName = $_SESSION['SlaveBaseChoise'];
	
		$OutputText=NULL;
		echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>";
		echo "<input type=hidden name='MasterBaseChoise' value=$MasterTableName>";
		echo "<input type=hidden name='SlaveBaseChoise' value=$SlaveTableName>";
				
	 	echo "<table cellSpacing='0' cellPadding='5'>";
	 	echo "<tr>";
	 	echo "<td>";
	 	echo "<textarea name='query' rows='30' cols='110'>";
		
	 	foreach ($sql as $Key => $Value)
	 	{
	 		$OutputText = $Value."$this->sepasator";
	 		echo $OutputText;
	 	}
	 	echo "</textarea>";
	 	echo "</td>";
	 	echo "</tr>";
	 	echo "</table>";
	 	echo "<input type='hidden' name='action'  value='executeSQL'>";
	 	echo "<input type='submit' value='Execute' name='submit'>";
	 	echo "</form>";
	}
}
?>