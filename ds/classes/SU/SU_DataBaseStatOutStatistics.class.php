<?php $CVS_Version = "$Revision: 1.7 $ ; $Date: 2007/04/23 14:13:43 $";

class SU_DataBaseStatOutStatistics
{
	function  OutStatistics($statArr, $dataBase, $ShowCountOfRows, $tables, $action)
	{

		$countall = array();
		$colorFlag = 1;

		if (empty($statArr))
		{
			echo "<h2>Empty Set</h2>";
		}

		echo "<table border=1 bordercolor='#2F4F4F'>";
		echo "<tr bgcolor='#F0FFF0'>";

		echo "<th>";
		echo "Таблица";
		echo "</th>";

		if ($ShowCountOfRows == 'yes')
		{
			echo "<th>";
			echo "Количество записей в таблице" ;
			echo "</th>";
		}

		echo "<th>";
		echo "SELECT";
		echo "</th>";

		echo "<th>";
		echo "Количество строк, выведенных <br> запросами SELECT";
		echo "</th>";
		echo "<th>";
		echo "INSERT";
		echo "</th>";

		echo "<th>";
		echo " UPDATE";
		echo "</th>";

		echo "<th>";
		echo "DELETE";
		echo "</th>";

		echo "<th>";
		echo "Суммарное количество изменений";
		echo "</th>";

		echo "</tr>";

		echo "<tr align=center bgcolor='#F0FFF0'>";

		self::OutButton('SortTab', $dataBase, $tables, $ShowCountOfRows);

		if ($ShowCountOfRows == 'yes')
		{
			self::OutButton('SortRow', $dataBase, $tables, $ShowCountOfRows);
		}

		self::OutButton('SortSel', $dataBase, $tables, $ShowCountOfRows);

		echo "<th>";
		echo "-";
		echo "</th>";

		self::OutButton('SortIns', $dataBase, $tables, $ShowCountOfRows);

		self::OutButton('SortUp', $dataBase, $tables, $ShowCountOfRows);

		self::OutButton('SortDel', $dataBase, $tables, $ShowCountOfRows);

		self::OutButton('SortSi', $dataBase, $tables, $ShowCountOfRows);

		echo "</tr>";

			foreach ($statArr as $key => $arr)
			{
				if (($colorFlag % 2) == 0)
				{
					echo "<tr bgcolor='#F0FFF0'>"	;
				} 
				else {
				echo "<tr>";
				}
				
				echo "<td>";
				echo $arr['relname'];
				echo "</td>";

				if ($ShowCountOfRows == 'yes')
				{
					echo "<td>";
					echo number_format($arr['rows_count']);
					echo "</td>";
					$countall['rows_count'] += $arr['rows_count'];
				}

				echo "<td>";
				echo number_format($arr['sel']);
				echo "</td>";
				$countall['sel'] += $arr['sel'];

				echo "<td>";
				echo number_format($arr['selq']);
				echo "</td>";
				$countall['selq'] += $arr['selq'];

				echo "<td>";
				echo number_format($arr['ins']);
				echo "</td>";
				$count += $arr['ins'];
				$countall['ins'] += $arr['ins'];


				echo "<td>";
				echo number_format($arr['upd']);
				echo "</td>";
				$count += $arr['upd'];
				$countall['upd'] += $arr['upd'];

				echo "<td>";
				echo number_format($arr['del']);
				echo "</td>";
				$count += $arr['del'];
				$countall['del'] += $arr['del'];

				echo "<td>";
				echo "<b>".number_format($arr['si'])."</b>";
				echo "</td>";
				$countall['coun'] += $arr['si'];

				$count = 0;

				echo "</tr>";
				
				$colorFlag ++;
			}

			echo "<tr bgcolor='#2E8B57'>";
			echo "<td><b>";
			echo "Total";
			echo "</b></td>";
			foreach ($countall as $key => $val)
			{
				echo "<td><b>";
				echo number_format($val);
				echo "</b></td>";
			}
			echo "</tr>";

		echo "</table>";
	}

	private function OutButton($action, $dataBase, $tables, $ShowCountOfRows)
	{
		echo "<td>";
		echo "<form action='".$_SERVER['PHP_SELF']."' method='POST'>";
		echo "<input type=hidden name=ShowCountOfRows value='$ShowCountOfRows'>";
		echo "<input type=hidden name=action value='$action'>
				<input type=hidden name=dataBase value='$dataBase'>
				<input type=hidden name=tables value='$tables'>
				<input type=submit name=SUBMIT value='   Sort   '></form>
				</td>";
	}
}
?>