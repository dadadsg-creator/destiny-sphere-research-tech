<?php $CVS_Version = '$Revision$ ; $Date$';
/**
		 * Класс формирует таблицу из массва лога 
		 *
		 * @return unknown
		 */
class SU_RequestStatOutLog
{

	static public function ShowLog($table, $startdate, $enddate, $user, $world, $database)
	{

		assert(!empty($startdate));
		assert(!empty($enddate));

		echo "<style>";
		echo "<!--";
		echo ".verticaltext {";
		echo "writing-mode: tb-rl;";
		echo "filter: flipv fliph;";
		echo "}";
		echo "-->";
		echo "</style>";

		echo "<body text = '#8B5A2B'>";

		echo "<h1>Request Log</h1>";

		echo "<form action=''><input type=submit value=Back ></form>" ;

		if (empty($table))
		{
			exit("<h2>По Вашему запросу данных нет</h2>");
		}

		echo "<h3>From $startdate to $enddate<h3>";
		if ($user != '')
		{
			echo "User: ".$user;
			echo "<br>";
		}

		if ($world != '')
		{
			echo "World: ".$world;
			echo "<br>";
		}

		if (!empty($database))
		{
			echo "DataBase: ".$database;
			echo "<br>";
		}

		$key = 0;
		$sumtagday = 0;
		$sumtag = array();
		$sumtagall = array();

		$startdate = strtotime($startdate);
		$starttimestamp = getdate($startdate);
		$endtimestamp = getdate($enddate);

		$enddate = strtotime($enddate);
		$endtimestamp = getdate($enddate);
		$endtimestamp = getdate($enddate);

		$startday1 = $starttimestamp['yday'];
		$endday1 = $endtimestamp['yday'];

		echo "<br>";
		echo "<br>";
		echo "<table border=2 color='#FDF5E6' bordercolor='#FFDEAD'>";
		echo "<tr align='center'>";

		$unictagname = array();
		foreach ($table as $k=>$tagn)
		{
			if  (!in_array($tagn['tagname'], $unictagname))
			{
				$unictagname[] = $tagn['tagname'];
			}
		}
		sort($unictagname);

		//Инициализируем массивы
		foreach ($unictagname as $k=>$un)
		{
			$sumtag [$un] = 0;
			$sumtagall [$un] = 0;
		}

		$countunictagname = count($unictagname);

		$time = getdate($startdate);

		// Цикл по дням
		for ($day1 = $startday1; $day1 <= $endday1; $day1++)
		{
			$day = $time['mday'];

			$sum = array();

			echo "<tr align='center'>";

			echo "<td><b>";
			echo "Day";
			echo "</b></td>";

			echo "<td><b>";
			echo "Hour";
			echo "</b></td>";

			//выводим все названия<br>
			foreach ($unictagname as $k=>$un)
			{
				echo "<td><font face=arial>";
				echo "<div class='verticaltext'>";
				echo "$un";
				echo "</div>";
				echo "</font>";
				echo "</td>";
			}

			echo "<td><b>";
			echo "Hour";
			echo "</b></td>";

			echo "<td>";
			echo "<div class='verticaltext'>";
			echo "Request count";
			echo "</div>";
			echo "</td>";

			echo "<td><b>";
			echo "Graph";
			echo "</b></td>";

			echo "</tr>";


			echo "<tr align='center'>";
			echo "<td rowspan=25>";
			echo "$day";
			echo "</td>";


			// Первый цикл по часам с целью посчитать суммарное количество
			//запросов за день

			//Записываем во временную переменную значение $key
			$dumpkey = $key;

			//Обнуляем счетчик запросов за день
			$sumday = 0;

			for ($hour = 00; $hour <= 23; $hour++)
			{
				// Цикл по имени
				for ($s = 0; $s < $countunictagname; $s++)
				{
					if ($table[$key]['day'] == $day and $table[$key]['hour'] == $hour and $table[$key]['tagname'] == $unictagname[$s])
					{
						$sumday += $table[$key]['count'];
						//Обнуляем массив со счетчиком запросов по часам
						$key ++;
					}
				}
			}

			//Присваиваем переменной $key значение,
			//которое было до первого цикла по времени
			$key= $dumpkey;

			// Второй цикл по часам с целью вывести табличку с графиками
			for ($hour = 00; $hour <= 23; $hour++)
			{
				$sum[$hour] = 0;

				//Если $hour  четное, меням цвет
				if (($hour % 2) == 0)
				{
					echo "<tr bgcolor='#FFF8DC' align='center'>"	;
				}
				else
				{
					echo "<tr align='center'>";
				}

				if ($hour<=9)
				{
					echo "<td>";
					echo "0".$hour;
					echo "</td>";
				}

				else
				{
					echo "<td>";
					echo $hour;
					echo "</td>";
				}

				// Цикл по имени
				for ($s = 0; $s < $countunictagname; $s++)
				{
					if ($table[$key]['day'] == $day and $table[$key]['hour'] == $hour and $table[$key]['tagname'] == $unictagname[$s])
					{
						echo "<td>";
						echo number_format($table[$key]['count']);
						$sum [$hour] += $table[$key]['count'];
						$sumtag[$unictagname[$s]] += $table[$key]['count'];
						$key ++;
						echo "</td>";
					}
					else
					{
						echo "<td>";
						echo "0";
						echo "</td>";
					}
				}

				if ($hour<=9)
				{
					echo "<td>";
					echo "0".$hour;
					echo "</td>";
				}

				else
				{
					echo "<td>";
					echo $hour;
					echo "</td>";
				}

				echo "<td>";
				echo "<b>".number_format($sum[$hour])."</br>";
				echo "</td>";
				$sumtagday += $sum[$hour];

				echo "<td align=left nowrap>";

				//Рисуем график
				if ($sumday != 0)
				{
					$persum = ($sum[$hour]/$sumday)*100;
				}
				else {
					$persum = 0;
				}
				echo "<img src='http://defo.destinysphere.ru/ds/drowimage.php?width=$persum'>";
				echo " (".round($persum, 1)."%)";

				echo "</td>";

				echo "</tr>";
				$sum[$hour] = 0;
			}


			echo "</tr>";
			echo "<tr bgcolor='#D2B48C' align='center'>";
			echo "<td colspan='2' align=left><b>Total for a day</b></td>";
			foreach ($sumtag as $k=>$st)
			{
				echo "<td><b>";
				echo number_format($st);
				echo "</b></td>";
				$sumtagall[$k] += $sumtag[$k];
				$sumtag[$k]=0;
			}

			echo "<td>";
			echo "</td>";

			echo "<td><b>";
			echo number_format($sumtagday);
			echo "</b></td>";

			echo "<td>";
			echo "</td>";

			echo "</tr>";
			$sumtagday = 0;

			$timestamp = $time['0']  + 86400;
			$time = getdate($timestamp);
		}

		echo "<tr align='center'>";
		echo "<td colspan='2'><b>Total for a period</b></td>";

		foreach ($sumtagall as $k=>$sta)
		{
			echo "<td><b>";
			echo number_format($sta);
			echo "</b></td>";
			$sumall += $sta;
		}

		echo "<td>";
		echo "-";
		echo "</td>";

		echo "<td><b>";
		echo "$sumall";
		echo "</b></td>";

		echo "<td>";
		echo "-";
		echo "</td>";

		echo "</tr>";
		echo "</table>";

		echo "<br>";
		echo "<br>";

		echo "<h2>TOTAL: ".number_format($sumall)." </h2>";
		//echo "<h2>TOTAL:$sumall</h2>";


		echo "</body>";

	}

	static public function ShowLogAllWorld($table, $startdate, $enddate)
	{
		assert(!empty($startdate));
		assert(!empty($enddate));

		echo "<style>";
		echo "<!--";
		echo ".verticaltext {";
		echo "writing-mode: tb-rl;";
		echo "filter: flipv fliph;";
		echo "}";
		echo "-->";
		echo "</style>";

		echo "<body text = '#8B5A2B'>";

		echo "<h1>Request Log</h1>";

		echo "<form action=''><input type=submit value=Back ></form>" ;

		if (empty($table))
		{
			exit("<h2>По Вашему запросу данных нет</h2>");
		}

		echo "<h3>From $startdate to $enddate<h3>";

		echo "<h3>All worlds </h3>";

		$key = 0;
		$sumtagday = 0;
		$sumtag = array();
		$sumtagall = array();

		$startdate = strtotime($startdate);
		$starttimestamp = getdate($startdate);
		$endtimestamp = getdate($enddate);

		$enddate = strtotime($enddate);
		$endtimestamp = getdate($enddate);
		$endtimestamp = getdate($enddate);

		$startday1 = $starttimestamp['yday'];
		$endday1 = $endtimestamp['yday'];

		sort($table);

		echo "<br>";
		echo "<br>";
		echo "<table border=2 color='#FDF5E6' bordercolor='#FFDEAD'>";

		$unicworldname = array();

		foreach ($table as $k=>$tagn)
		{
			if (!in_array($tagn['world_identifier'], $unicworldname))
			{
				//$unicworldname[$tagn['world_identifier']] = $tagn['worldname'];
				//$unicworldname[] = $tagn['world_identifier'];
				$unicworldname[$tagn['world_identifier']] = $tagn['worldname'];
			}
		}
		ksort($unicworldname);

		//Инициализируем массивы
		foreach ($unicworldname as $k=>$un)
		{
			$sumtag [$k] = 0;
			$sumtagall [$k] = 0;
		}


		$countunictagname = count($unicworldname);

		$time = getdate($startdate);

		// Цикл по дням
		for ($day1 = $startday1; $day1 <= $endday1; $day1++)
		{
			$day = $time['mday'];
			$sum = array();

			echo "<tr align='center'>";

			echo "<th rowspan=2>";
			echo "Day";
			echo "</th>";

			echo "<th rowspan=2>";
			echo "Hour";
			echo "</th>";


			//выводим все названия<br>
			foreach ($unicworldname as $k=>$un)
			{
				echo "<th colspan=2>";
				echo $un;
				echo "</th>";
			}

			echo "<th rowspan=2>";
			echo "Hour";
			echo "</th>";

			echo "<td rowspan=2>";
			echo "<div class='verticaltext'>";
			echo "Request count";
			echo "</div>";
			echo "</td>";

			echo "<td rowspan=2>";
			echo "<div class='verticaltext'>";
			echo "Request in a second";
			echo "</div>";
			echo "</td>";

			echo "<th rowspan=2><b>";
			echo "Graph";
			echo "</b></th>";

			echo "</tr>";

			echo "<tr align='center'>";
			foreach ($unicworldname as $k=>$un)
			{
				echo "<td>";
				echo "<div class='verticaltext'>";
				echo "Count request";
				echo "</div>";
				echo "</td>";
				echo "<td>";
				echo "<div class='verticaltext'>";
				echo "% from request in hour ";
				echo "</div>";
				echo "</td>";
			}
			echo "</tr>";

			echo "<tr align='center'>";
			echo "<td rowspan=25>";
			echo "$day";
			echo "</td>";


			// Первый цикл по часам с целью посчитать суммарное количество
			//запросов за день

			//Записываем во временную переменную значение $key
			$dumpkey = $key;

			//Обнуляем счетчик запросов за день
			$sumday = 0;

			for ($hour = 00; $hour <= 23; $hour++)
			{

				$sum[$hour] = 0;
				// Цикл по имени
				foreach ($unicworldname as $worldid => $worldname)
				{
					if ($table[$key]['day'] == $day and $table[$key]['hour'] == $hour and $table[$key]['world_identifier'] == $worldid)
					{
						$sumday += $table[$key]['count'];
						$sum [$hour] += $table[$key]['count'];

						//Обнуляем массив со счетчиком запросов по часам
						$key ++;
					}

				}
			}

			//Присваиваем переменной $key значение,
			//которое было до первого цикла по времени
			$key= $dumpkey;

			// Второй цикл по часам с целью вывести табличку с графиками
			for ($hour = 00; $hour <= 23; $hour++)
			{
				//Если $hour  четное, меням цвет
				if (($hour % 2) == 0)
				{
					echo "<tr bgcolor='#FFF8DC' align='center'>"	;
				}
				else
				{
					echo "<tr align='center'>";
				}

				if ($hour<=9)
				{
					echo "<td>";
					echo "0".$hour;
					echo "</td>";
				}

				else
				{
					echo "<td>";
					echo $hour;
					echo "</td>";
				}

				// Цикл по имени
				foreach ($unicworldname as $worldid => $worldname)
				{
					if ($table[$key]['day'] == $day and $table[$key]['hour'] == $hour and $table[$key]['world_identifier'] == $worldid)
					{
						echo "<td>";
						echo number_format($table[$key]['count']);
						//$sum [$hour] += $table[$key]['count'];
						$sumtag[$worldid] +=  $table[$key]['count'];

						echo "</td>";
						echo "<td>";
						echo round(($table[$key]['count']/$sum [$hour])*100, 2);
						echo "</td>";
						$key ++;
					}
					else
					{
						echo "<td>";
						echo "0";
						echo "</td>";
						echo "<td>";
						echo "0";
						echo "</td>";
					}
				}

				if ($hour<=9)
				{
					echo "<td>";
					echo "0".$hour;
					echo "</td>";
				}

				else
				{
					echo "<td>";
					echo $hour;
					echo "</td>";
				}

				echo "<td>";
				echo "<b>".number_format($sum[$hour])."</br>";
				echo "</td>";

				echo "<td>";
				echo "<b>".round($sum[$hour]/3600, 2)."</br>";
				echo "</td>";

				$sumtagday += $sum[$hour];

				echo "<td align=left nowrap>";

				//Рисуем график
				if ($sumday != 0)
				{
					$persum = ($sum[$hour]/$sumday)*100;
				}
				else {
					$persum = 0;
				}
				echo "<img src='http://defo.destinysphere.ru/ds/drowimage.php?width=$persum'>";
				echo " (".round($persum, 1)."%)";

				echo "</td>";

				echo "</tr>";
				$sum[$hour] = 0;
			}


			echo "</tr>";
			echo "<tr bgcolor='#D2B48C' align='center'>";
			echo "<td colspan='2' align=left><b>Total for a day</b></td>";
			foreach ($sumtag as $k=>$st)
			{
				echo "<td><b>";
				echo number_format($st);
				echo "</b></td>";
				echo "<td>";
				echo "-";
				echo "</td>";
				$sumtagall[$k] += $sumtag[$k];
				$sumtag[$k]=0;
			}

			echo "<td>";
			echo "</td>";

			echo "<td><b>";
			echo number_format($sumtagday);
			echo "</b></td>";

			echo "<td>";
			echo "-";
			echo "</td>";

			echo "<td>";
			echo "-";
			echo "</td>";

			echo "</tr>";
			$sumtagday = 0;


			$timestamp = $time['0']  + 86400;
			$time = getdate($timestamp);
		}

		echo "<tr align='center'>";
		echo "<td colspan='2'><b>Total for a period</b></td>";

		foreach ($sumtagall as $k=>$sta)
		{
			echo "<td><b>";
			echo number_format($sta);
			echo "</b></td>";
			echo "<td>";
			echo "-";
			echo "</td>";
			$sumall += $sta;
		}

		echo "<td>";
		echo "-";
		echo "</td>";

		echo "<td><b>";
		echo number_format($sumall);
		echo "</b></td>";

		echo "<td>";
		echo "-";
		echo "</td>";


		echo "<td>";
		echo "-";
		echo "</td>";


		echo "</tr>";
		echo "</table>";

		echo "<br>";
		echo "<br>";

		echo "<h2>TOTAL: ".number_format($sumall)."</h2>";

		echo "</body>";
	}


	static public function ShowQueryLog($table, $startdate, $enddate, $user, $world, $database)
	{
		assert(!empty($startdate));
		assert(!empty($enddate));

		echo "<body text = '#8B5A2B'>";

		echo "<h1>Request Log</h1>";

		echo "<form action=''><input type=submit value=Back ></form>" ;

		if (empty($table))
		{
			exit("<h2>По Вашему запросу данных нет</h2>");
		}

		echo "<h3>From $startdate to $enddate<h3>";
		if ($user != '')
		{
			echo "User: ".$user;
			echo "<br>";
		}

		if ($world != '')
		{
			echo "World: ".$world;
			echo "<br>";
		}

		if (!empty($database))
		{
			echo "DataBase: ".$database;
			echo "<br>";
		}

		echo "<br>";
		echo "<br>";
		echo "<table border=2 color='#FDF5E6' bordercolor='#FFDEAD'>";
		echo "<tr align='center'>";
		echo "	<th>
					Date
				</th>";

		echo "	<th>
					Query
				</th>";
		echo "</tr>";


		foreach ($table as $key => $data)
		{
			echo "<tr>";

			echo "<td>";
			echo "&nbsp;".$data['add_date']."&nbsp;";
			echo "</td>";

			if (empty($data['xmldata']))
			{
				echo "<td>";
				echo $data['tagname'];
				echo "</td>";
			} else {
				echo "<td>";
				echo "<xmp>";
				echo $data['xmldata'];
				echo "</xmp></td>";
			}

			echo "</tr>";

		}

		echo "</table>";
		echo "</body>";

	}
}
?> 