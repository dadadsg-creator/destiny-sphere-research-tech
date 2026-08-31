<?php

class UserStatOut
{
	//static public function OutIndex(array $worlds, array $from, array $to, $selectedWorlds, $detail)
	static public function OutIndex(array $from, array $to, $selectedWorlds, $detail)
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

		/*//Выбор миров
		$out .= "
				<tr>
					<td>
					";	
		$out .= "<br>
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
				</tr>
					";*/

		$out .= "<br>";
		$out .= "<br>";
		$out .= "<br>";
			
		//Выводим менюшку выбора детализации
		$out .= '<tr>
					<td>
							<font color=#F8F8FF>
								Detail: 
							</font>';
		
		$out .= "	
				<select name='detail'>	
			";
				
		$dateParts = array(
				0 => 'year',
				1 => 'month',
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
			$out .= ">$v";
		}
		
		$out .= "
				</select>
			</td>
			
		</tr>
		";
	
		$out .= "<br>";
 		$out .= "</table>";
		
		$out .= "<br><input type='submit' class='Submit' value='&nbsp;&nbsp;&nbsp;&nbsp;Show Stat&nbsp;&nbsp;&nbsp;&nbsp;'>";
 		$out .= "</form>";

		echo $out;
	}
	
	static public function OutStat(array $stat, array $min_max, $total)
	{
		$out = "<table border=1 width=500px>";
		
		$head = true;
		
		$detail = '';
		
		$count = 0;
		
		foreach ($stat as $k => $s)
		{
			if($head)
			{	
				$out .= "<tr class=th_2>\n";
				$count = count($s) + 1;
				foreach ($s as $par => $val) 
				{
					if($par != 'count')
					{
						$detail = $par;
					}
					$out .= "<td align='center' width='50px'><b>".$par."</b></td>\n";
				}
				$out .= "<td>&nbsp;</tr>\n";
				$out .= "</tr>\n";
				$head = false;
			}
			
			if(key_exists('day', $s))
			{
				$weekDay = date('D', mktime(1, 0, 0, $s['month'], $s['day'], $s['year']));
			} else {
				$weekDay = '';
			}
			
			if(!empty($weekDay) && ($weekDay == 'Sun' || $weekDay == 'Sat'))
			{
				$bgColor = 'A08888';
			} else {
				$bgColor = '8888A0';
			}
			
			$out .= "<tr>";
			foreach ($s as $par => $val) 
			{
				if($par == 'count')
				{
					$w = round(100.0 * (int)$val / (int)$min_max['max']);
					if($val == $min_max['max'])
					{
						$val = "<span style='color: #00dd00'><b>".$val."</b></span>";	
					} elseif($val == $min_max['min']) {
						$val = "<span style='color: red'><b>".$val."</b></span>";	
					}
					
					$out .= "<td align='center' bgcolor='#$bgColor'>".$val."</td>\n";
					$out .= "<td bgcolor='#$bgColor'><img src='http://defo.destinysphere.ru/ds/drowimage.php?width=$w&ws=1.5' alt='$w% from max'></td>\n";
				} elseif($par == 'day') {
					
					$out .= "<td align='center' bgcolor='#$bgColor'>".$val.' ('.$weekDay.")</td>\n";
				} else {
					$out .= "<td align='center' bgcolor='#$bgColor'>".$val."</td>\n";
				}
			}
			$out .= "</tr>\n";
		}
		
		$countData = count($stat);
		$avg = round($total / $countData, 2);
				
		$avgWMax = min(2 * ($avg - $min_max['min']) / max(1, $min_max['max'] - $min_max['min']), 1);
		$avgWMin = min(2 * ($min_max['max'] - $avg) / max(1, $min_max['max'] - $min_max['min']), 1);
		
		$avgW = ($avg - $min_max['min']) / ($min_max['max'] - $min_max['min']);
		
		$colW = 1 / $avgW;
		
		$avgCR = round($avgWMin * 221);
		$avgCG = round($avgWMax * 221);
		
		if($avgCR == 0 && $avgCG == 0)
		{
			$avgCG = 221;
		}
		
		$avgColor = "rgb($avgCR, $avgCG, 0)";
		
		$out .= "<tr class=th_2><td colspan=$count>Total: <b>$total</b> for ".$countData." {$detail}s&nbsp;&nbsp;&nbsp;&nbsp;Min: <span style='color: red'><b>".$min_max['min']."</b></span>&nbsp;&nbsp;&nbsp;&nbsp;Max: <span style='color: #00dd00'><b>".$min_max['max']."</b></span>&nbsp;&nbsp;&nbsp;&nbsp;Average: <span style='color: $avgColor'><b>$avg</b></span> per $detail</td></tr>";
		
		$out .= "</table>";
		
		echo $out;
	}
	
}

?>