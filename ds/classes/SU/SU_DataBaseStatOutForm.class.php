<?php $CVS_Version = "$Revision: 1.6 $ ; $Date: 2007/04/23 14:13:49 $";

class SU_DataBaseStatOutForm
{
	function OutForm ($action, $dataBase)
	{
		echo "<h1>Data Base Statistics</h1>";

		echo "<h3>Choose a database:</h3>";

		echo "<form action=".$_SERVER['PHP_SELF']." method='POST'>";

		echo "<select name='dataBase'>";


		switch($action)
		{
			case "":
				foreach ($GLOBALS['dbWorldAccess'] as $k=>$worldAccess)
				{
					echo "<option value=".$k."_".$worldAccess['server'].">";
					//echo "<option value=$k>";
					echo $worldAccess['server'];
					echo "</option>";
				}
				break;
				
			default:
				//$slecteDataBase = $params->get('dataBase');
				assert($dataBase != '');
				
				foreach ($GLOBALS['dbWorldAccess'] as $k=>$worldAccess)
				{
					if ($k."_".$worldAccess['server'] == $dataBase)
					{
						echo("<option value=".$k."_".$worldAccess['server']." selected>");
						echo $worldAccess['server'];
						echo "</option>";
					}
					else
					{
						echo "<option value=".$k."_".$worldAccess['server'].">";
						echo $worldAccess['server'];
						echo "</option>";
					}
				}
				break;

			
		}

		echo "</select>";

		echo "<h3>Enter a name of the table or several tables through a comma:</h3>";
		echo "<input type='text' name='tables' value='all tables'>";
		echo "<br>";
		echo "<br>";
		echo "<input type='checkbox' name='ShowCountOfRows' value='yes'>";
		echo "Show count of rows  ";
		echo "<br>";
		echo "<br>";
		echo "<input type='hidden' name='action' value='Show'>";
		echo "<input type='submit' name='SUBMIT' value='Show Stat'>";
		echo "</form>";
	}
}
		?>