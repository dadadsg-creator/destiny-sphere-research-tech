<?php

class TransferStatOut
{
	static public function OutIndex(array $worlds, array $from, array $to, $selectedWorlds, $flag, $detail)
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
		if ($flag == true) {
			$c = 'checked';
		} else {
		$c = '';
		}
		$out .= '
					<td valign="top">
						<input type=checkbox name="showDetail" '.$c.'>
							<font color=#F8F8FF>
								Show Detail: 
							</font><br>';
		
		$out .= "	
				<select name='detail'>	
			";
				
		$dateParts = array(
				0 => 'year',
				1 => 'month',
				//2 => 'week',
				2 => 'day',
				3 => 'hour',
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
		
		$out .= "<input type='submit' name='TransferToWorlds' class='Submit' value='&nbsp;&nbsp;&nbsp;&nbsp;Show Stat&nbsp;&nbsp;&nbsp;&nbsp;'>";
 		$out .= "</form>";

		echo $out;
	}
	
	static public function OutStat($stat)
	{
		$countTransfer = 0;

		$out = "<br><br>
				";

		$out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                    <tr>
                       <td>
                          <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";	
		$out .= "<tr>
					<th class=th_3>
						<font color=#F8F8FF>
							World
						</font>
					</th>
					<th class=th_3 >
						Transfer From World
					</th>	
					<th class=th_3 >
						Transfer To World
					</th>
				</tr>
				";
		foreach ($stat as $world => $count)
		{
			$out .= "<tr>
						<td bgcolor='#444E6C' align='right'>
							<font color=#F8F8FF>
								$world
							</font>
						</td>	
						<td bgcolor='#444E6C' align='right'>
							<font color=#F8F8FF>
								".$count['from']."
							</font>
						</td>
						<td bgcolor='#444E6C' align='right'>
							<font color=#F8F8FF>
								".$count['to']."
							</font>
						</td>	
					</tr>";
			
			//Увеличиваем  счетчики переездов
			$countTransferFrom += $count['from'];
			$countTransferTo += $count['to'];
		}
		
		//Выводим количество переездов
		$out .= "
				<tr>
						<td bgcolor='#2A3E66' align='right'>
							<font color=#F8F8FF>
								<b>
								Total:
								</b>
							</font>
						</td>
						<td bgcolor='#2A3E66' align='right'>
							<font color=#F8F8FF>
							 	<b>
									$countTransferFrom
								</b>
							</font>
						</td>		
						<td bgcolor='#2A3E66' align='right'>
							<font color=#F8F8FF>
							 	<b>
									$countTransferTo
								</b>
							</font>
						</td>	
				</tr>
				";

		$out .= "</table>
                              </td>
                       </tr>
                </table>";
		
		echo $out;
	}
	
	static public function OutStatWithDetail($stat, $header, $detail)
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
		$countFrom = 0;
		$countTo = 0;
		$totalCount = array();
		$totalCountFrom = 0;
		$totalCountTo = 0;
		
		$out .= "<br><br>
				";

		$out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                    <tr>
                       <td>
                          <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";

		$out .= "<tr>
					<th bgcolor='#2A3E66' colspan='2'>
						<font color=#F8F8FF>World</font>
					</th>";
		foreach ($header as $key => $data)
		{
			$out .= "<td bgcolor='#2A3E66'>
						<div class=verticaltext>
							<nobr>
								<b><font color=#F8F8FF>
									$data
									</font>
								</b>
							</nobr>
						</div>
					</td>";
			$totalCount[$data]['from'] = 0;
			$totalCount[$data]['to'] = 0;
		}
		$out .= "<th bgcolor='#2A3E66'>
						<font color=#F8F8FF>Summ</font>
				</th>";
		$out .= "<th bgcolor='#2A3E66'>
						<font color=#F8F8FF>More</font>
				</th>";
		$out .= "	</tr>
				";

		foreach ($stat as $world => $data)
		{
			$tempArr = explode('_', $world);
			$worldId = $tempArr[0];
			$dbId = $tempArr[1];
			$worldName = $tempArr[2];

			$out .= "<tr>
						<td bgcolor='#444E6C' rowspan='2'>
							<font color=#F8F8FF>
								$worldName
							</font>
						</td>
						<td bgcolor='#444E6C'>
							<font color=#F8F8FF>
								Уехало
							</font>
						</td>";
			
			foreach ($header as $headerKey => $headerDate)
			{
				if (key_exists($headerDate, $data['from'])) 
				{
					$out .= "<td bgcolor='#444E6C'>
								<font color=#F8F8FF>
									".$data['from'][$headerDate]."
								</font>
							</td>";
					$countFrom += $data['from'][$headerDate];
					$totalCount[$headerDate]['from'] += $data['from'][$headerDate];
				} else {
					$out .= "<td bgcolor='#444E6C'>
								<font color=#F8F8FF>
									-
								</font>					
							</td>";
				}
			}
			
			//Виводим сумму уехавших
			$out .= "<td bgcolor='#2A3E66'>
								<font color=#F8F8FF>
									<b>
										$countFrom
									</b>
								</font>
					</td>";
			//Выводим кнопку "показать откуда приехали"
				$out .= "<td bgcolor='#444E6C' align='center'>
								<font color=#F8F8FF>
										<a target='_blank' href='?action=showFrom&worldAndDbId=".$worldId."_".$dbId."_".$worldName."&detail=$detail&showDetail=yes&direction=from'>
											Куда
										</a>
								</font>
						</td>";
			$out .= "</tr>";
			
			$out .= "<tr>
						<td bgcolor='#444E6C'>
							<font color=#F8F8FF>
								Приехало
							</font>	
						</td>";
			
			foreach ($header as $headerKey => $headerDate)
			{
				if (key_exists($headerDate, $data['to'])) 
				{
					$out .= "<td bgcolor='#444E6C'>
								<font color=#F8F8FF>
									".$data['to'][$headerDate]."
								</font>
							</td>";
					$countTo += $data['to'][$headerDate];
					$totalCount[$headerDate]['to'] += $data['to'][$headerDate];
				} else {
					$out .= "<td bgcolor='#444E6C'>
								<font color=#F8F8FF>
									-
								</font>
							</td>";
				}
			}
			
			//Виводим сумму приехавших
			$out .= "<td bgcolor='#2A3E66'>
								<font color=#F8F8FF>
									<b>
										$countTo
									</b>
								</font>
							</td>";
			//Выводим кнопку "показать куда переехали"
			$out .= "<td bgcolor='#444E6C'>
							<font color=#F8F8FF>
								<a target='_blank' href='?action=showFrom&worldAndDbId=".$worldId."_".$dbId."_".$worldName."&detail=$detail&showDetail=yes&direction=to'>
									Откуда
								</a>
							</font>
						</td>";
			
			$out .= "</tr>";
			
			$out .= "<tr>
						<td bgcolor='#444E6C' colspan='".$countColumn."'>
							&nbsp;
						</td>
					</tr>";	
			
			//Обнуляем счетчики
			$countFrom = 0;
			$countTo = 0;
		}

		//Выводим количество переездов
		$out .= "<tr>
						<td bgcolor='#2A3E66' rowspan='2'>
							<font color=#F8F8FF>
								<b>
									Total:
								</b>
							</font>
						</td>
						<td bgcolor='#2A3E66'>
							<font color=#F8F8FF>
								<b>
									Уехало
								</b>
							</font>
						</td>";
		foreach ($header as $key => $date)
		{
			$out .= "
						<td bgcolor='#2A3E66'>
							<font color=#F8F8FF>
								<b>
									".$totalCount[$date]['from']."
								</b>
							</font>
						</td>";
			$totalCountFrom += $totalCount[$date]['from'];
		}
		$out .= "
						<td bgcolor='#2A3E66'>
							<font color=#F8F8FF>
								<b>
									$totalCountFrom
								</b>
							</font>
						</td>";
		$out .= "
						<td bgcolor='#2A3E66'>
							&nbsp;
						</td>";
		$out .= "</tr>";
		$out .= "<tr>
						<td bgcolor='#2A3E66'>
								<font color=#F8F8FF>
									<b>
										Приехало
									</b>
								</font>	
						</td>";
		foreach ($header as $key => $date)
		{
			$out .= "
						<td bgcolor='#2A3E66'>
							<font color=#F8F8FF>
								<b>
									".$totalCount[$date]['to']."
								</b>
							</font>
						</td>";
			$totalCountTo += $totalCount[$date]['to'];
		}
		$out .= "
						<td bgcolor='#2A3E66'>
							<font color=#F8F8FF>
								<b>
									$totalCountTo
								</b>
							</font>
						</td>";
		$out .= "
						<td bgcolor='#2A3E66'>
							&nbsp;
						</td>";
		$out .= "</tr>";

		$out .= "</table>
			             </td>
			          </tr>
			     </table>";
		echo $out;
	}
	
	static public function OutStatFrom($stat, $header, $worldName, $direction)
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

		$out = "<br><br>
				";

		if ($direction == 'from')
		{
			$out .= "<h1>Уехало из мира $worldName в миры:</h1>";	
		}
		if ($direction == 'to')
		{
			$out .= "<h1>Приехало в мир $worldName из миров:</h1>";	
		}
		
		
		$out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                    <tr>
                       <td>
                          <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";

		$out .= "<tr>
					<th bgcolor='#2A3E66'>
						<font color=#F8F8FF>World</font>
					</th>";
		foreach ($header as $key => $data)
		{
			$out .= "<td bgcolor='#2A3E66'>
						<div class=verticaltext>
							<nobr>
								<b>
									<font color=#F8F8FF>$data</font>
								</b>
							</nobr>
						</div>
					</td>";
			$totalCount[$data] = 0;
		}
		$out .= "<th bgcolor='#2A3E66'>
						<font color=#F8F8FF>Summ</font>
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
			
			//Виводим сумму уехавших
			$out .= "<td bgcolor='#2A3E66'>
								<font color=#F8F8FF>
									<b>
										$count
									</b>
								</font>
					</td>";
			$out .= "</tr>";
			
			$out .= "<tr>
						<td bgcolor='#444E6C' colspan='".$countColumn."'>
							&nbsp;
						</td>
					</tr>";	
			
			//Обнуляем счетчики
			$count = 0;
		}

		//Выводим количество переездов
		$out .= "<tr>
						<td bgcolor='#2A3E66'>
							<font color=#F8F8FF>
								<b>
									Total:
								</b>
							</font>
						</td>";
						
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