<?php

class BattlesOut
{
	static public function OutIndex(array $worlds, array $from, array $to, $selectedWorlds, $detail)
	{
		if(count($from) > 0)
		{
			$start = $from;
		} else {
			
			$week = strtotime("-1 week");
			
			$start[0] =  date('Y', $week);
			$start[1] = date('m', $week);
			$start[2] = date('d', $week);
			$start[3] = date('H', $week);
		}
		
		if(count($to) > 0)
		{
			$end = $to;
		} else {
			$end[0] = date('Y');
			$end[1] = date('m');
			$end[2] = date('d');
			$end[3] = date('H') + 1;
		}
		
		$out = <<<EOT
	<form action="" method="POST">
	<input type="hidden" name="action" value="showStat">
	<table border=0>
		<tr>
			<td>
				<font color=#F8F8FF>Start date:</font> 
				<table border=0>
					<tr class=th_2>
						<td>Year</td><td>month</td><td>day</td><td>hour</td>
					</tr>
					<tr>
						<td><input type='text' size=4 name='dateFrom[0]' value="{$start[0]}">&nbsp;-&nbsp;</td>
						<td><input type='text' size=2 name='dateFrom[1]' value="{$start[1]}">&nbsp;-&nbsp;</td>
						<td><input type='text' size=2 name='dateFrom[2]' value="{$start[2]}">&nbsp; &nbsp;</td>
						<td><input type='text' size=2 name='dateFrom[3]' value="{$start[3]}"></td>
					</tr>
				</table>
			</td>
			<td width=250px>
				<font color=#F8F8FF>End date:</font> 
				<table border=0>
					<tr class=th_2>
						<td>Year</td><td>month</td><td>day</td><td>hour</td>
					</tr>
					<tr>
						<td><input type='text' size=4 name='dateTo[0]' value="{$end[0]}">&nbsp;-&nbsp;</td>
						<td><input type='text' size=2 name='dateTo[1]' value="{$end[1]}">&nbsp;-&nbsp;</td>
						<td><input type='text' size=2 name='dateTo[2]' value="{$end[2]}">&nbsp; &nbsp;</td>
						<td><input type='text' size=2 name='dateTo[3]' value="{$end[3]}"></td>
					</tr>
				</table>
			</td>
		</tr>	
EOT;

		$out .= "
				<tr>
					&nbsp;
				</tr>";
		
		//Выбор миров
		$out .= "
				<tr>
					<td valign='top'>
					";	
		$out .= "
					<font color=#F8F8FF>Worlds:</font><br>
						";
		
		foreach ($worlds as $id => $worldName)
		{
			if (empty($selectedWorlds)) {
				$c = 'checked';
			} else {
				if (in_array($id, $selectedWorlds))
				{
					$c = 'checked';
				} else {
					$c = '';
				}
			}
			$out .= '<input type=checkbox name="worlds[]" value='.$id.' '.$c.'><font color=#F8F8FF>'.$worldName.'</font>
';
			$out .= '<br>';
		}
		$out .= "  </td>
					";

		//Выводим менюшку выбора детализации
		$out .= '
					<td valign="top">
							<font color=#F8F8FF>
								Detail: 
							</font><br>';
		
		$out .= "	
				<select name='detail'>	
			";
				
		$dateParts = array(
				2 => 'week',
				3 => 'day',
				4 => 'hour',
			);
		
		foreach ($dateParts as $k => $v) 
		{
			$out .= "<option value=$k";
			if($detail == $k)
			{
				$out .= ' selected ';
			}
			$out .= ">$v</option>";
		}
		
		$out .= "
				</select>
			</td>
			
		</tr>
		";
	
		$out .= "<br>";
 		$out .= "</table>";
		
		$out .= "<input type='submit' class='Submit' value='&nbsp;&nbsp;&nbsp;&nbsp;Show Stat&nbsp;&nbsp;&nbsp;&nbsp;'>";
 		$out .= "</form>";

		echo $out;
	}
	
	static public function OutStat(array $stat, array $header)
	{
		echo "<style>";
		echo "<!--";
		echo ".verticaltext {";
		echo "writing-mode: tb-rl;";
		echo "filter: flipv fliph;";
		echo "}";
		echo "-->";
		echo "</style>";
		
		$countColumn = count($header) + 4;
		$totalCount = array();
		$total = 0;
		$count = 0;
		
		$out .= "<br><br>
				";

		$out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                    <tr>
                       <td>
                          <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";

		$out .= "<tr>
					<th bgcolor='#2A3E66'>
						<font color=#F8F8FF>
							World
						</font>
					</th>";
		foreach ($header as $key => $data)
		{
			$out .= "<td bgcolor='#2A3E66'>
						<font color=#F8F8FF>
							<div class=verticaltext>
								<nobr>
									<b>
										$data
									</b>
								</nobr>
							</div>
						</font>
					</td>";
			$totalCount[$data] = 0;
		}
		$out .= "<th bgcolor='#2A3E66'>
					<font color=#F8F8FF>
						Summ
					</font>
				</th>";
		$out .= "	</tr>
				";

		foreach ($stat as $world => $data)
		{
			$out .= "<tr>
						<td bgcolor='#444E6C'>
							<font color=#F8F8FF>
								$world
							</font>
						</td>";
			
			foreach ($header as $headerKey => $headerDate)
			{
				if (key_exists($headerDate, $data)) 
				{
					$out .= "<td bgcolor='#444E6C'>
								<font color=#F8F8FF>
									".$data[$headerDate]."
								</font>
							</td>";
					$count += $data[$headerDate];
					$totalCount[$headerDate] += $data[$headerDate];
				} else {
					$out .= "<td bgcolor='#444E6C'>
								<font color=#F8F8FF>
									-
								</font>					
							</td>";
				}
			}
			
			//Виводим сумму воев
			$out .= "<td bgcolor='#2A3E66'>
								<font color=#F8F8FF>
									<b>
										$count
									</b>
								</font>
					</td>";
			$out .= "</tr>";
			
			//Обнуляем счетчик
			$count = 0;
		}
		
		//Выводим количество переездов
		$out .= "<tr>
						<td bgcolor='#2A3E66'>
							<font color=#F8F8FF>
								<b>
									Total:
								</b>
							</font>";
						
		foreach ($header as $key => $date)
		{
			$out .= "
						<td bgcolor='#2A3E66'>
							<font color=#F8F8FF>
								<b>
									".$totalCount[$date]."
								</b>
							</font>
						</td>";
			$total += $totalCount[$date];
		}
		
		$out .= "
						<td bgcolor='#2A3E66'>
							<font color=#F8F8FF>
								<b>
									$total
								</b>
							</font>
						</td>";

		$out .= "</tr>";
		
		$out .= "</table>
			             </td>
			          </tr>
			     </table>";
		echo $out;
	}
	
}

?>