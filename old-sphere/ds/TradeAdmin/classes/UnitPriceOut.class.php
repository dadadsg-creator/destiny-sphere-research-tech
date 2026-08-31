<?php

class UnitPriceOut
{
	static public function outIndex($worlds)
	{
		assert(is_array($worlds));
		
		$out = '<h1>Edit Unit Course</h1>';
		$out .= '<form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="unit_search">';
		
		$out .= '<b>World: </b><select name="world">';
		foreach($worlds as $k => $world)
		{
			$out .= '<option value='.$world['dbid'].'_'.$world['id_world'].'>'.$world['worldname'];
		}
		$out .= '</select>&nbsp;';
		$out .= '<input type="submit" value="select"></form>';
		
		return $out;
	}
	
	static public function outUnitSearch($filters, $selected, $worldID)
	{
		assert(is_array($filters));
		assert(is_array($selected));
		assert(count($filters) == count($selected));
		
		$out = '<h1>Edit Unit Course</h1>';
		
		/*
		$out .= '<hr><form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="csv">';
		$out .= '<input type="hidden" name="world" value="'.$worldID.'">';
		$out .= '<b>Load from CSV file</b>: <input type="file" name="csv">';
		$out .= '&nbsp;&nbsp;<input type="submit" value="update courses">';
		$out .= '</form>';
		*/
		
		$out .= '<hr><form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="init_course">';
		$out .= '<input type="hidden" name="world" value="'.$worldID.'">';
		$out .= '&nbsp;&nbsp;<input type="submit" value="set start courses">';
		$out .= '</form>';
		
		$out .= '<hr><form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="unit_list">';
		$out .= '<input type="hidden" name="world" value="'.$worldID.'">';

		foreach ($filters as $name => $filterData)
		{
			assert(key_exists($name, $selected));
			assert(is_array($filterData));
			$out .= '<b>'.$name.'</b>: <select name='.$name.'>';
			foreach($filterData as $k => $filter)
			{
				if($filter['id'] == $selected[$name])
				{
					$s = ' selected=1';
				} else {
					$s = '';
				}
				$out .= '<option value='.$filter['id'].$s.'>'.$filter['name'];
			}
			if ($selected[$name] == 'all')
			{
				$out .= '<option value=all selected>ALL';
			} else
			{
				$out .= '<option value=all>ALL';
			}
			$out .= '</select>&nbsp;&nbsp;';
		}

		$out .= '<input type="submit" value="select"></form>';
		$out .= '<hr>';

		return $out;
	}
	
	static public function outUnitList($units, $title, $worldID, $race, $class)
	{
		$out = self::outBackButton();
			
		$out .= '<table border=1>';
		
		$rowLength = 1;
		
		//-------------- Формирование шапки таблицы ----------------------
		$out .= "<tr>
				<th rowspan=2>&nbsp;name</th>";
				//<th align=center rowspan=2>power</th>";

		foreach($title as $k=>$v)
		{					
			$out .= "<th colspan=".count($v['TYPES']).">".$v['CATEGORY_NAME']."</th>";
			$rowLength += count($v['TYPES']);
		}		
		$out .= "<th align=center rowspan=2>history</th>";	
		$out .="</tr>\n";		
		
		$out .="<tr>\n";		
		foreach($title as $k1=>$v1)
		{
			foreach($v1['TYPES'] as $k2=>$v2)
			{
				switch ($v2['TYPE_NAME'])
				{
					case 'KADERIUM':
						$tit = 'K';
						break;
					case 'NANO_CRYSTAL':
						$tit = 'N_K';
						break;
					case 'STASIS_ENERGY':
						$tit = 'S_E';
						break;
					case 'FOOD':
						$tit = 'F';
						break;
					case 'PRODIUM':
						$tit = 'P';
						break;
					case 'SILVER_CREDIT':
						$tit = 'S_C';
						break;
					case 'GOLD_CREDIT':
						$tit = 'G_C';
						break;
				}
				$out .= "<th>".$tit."</th>";
			}			
		}	
		
		$out .= "</tr>\n";

		//------------- Формирование данных таблицы -----------------------------	

		$keyValue = 0;
		$key = 0;
		while ($key < count($units))
		{
			$out .= '<form action="" method="POST">';
			$out .= '<input type="hidden" name="action" value="edit">';
			$out .= '<input type="hidden" name="race" value="'.$race.'">';
			$out .= '<input type="hidden" name="class" value="'.$class.'">';
			$out .= '<input type="hidden" name="world" value="'.$worldID.'">';
			$out .= '<tr><td>&nbsp;'.$units[$key]['name'].'&nbsp;</td>';

			foreach($title as $k1=>$v1)
			{
				foreach($v1['TYPES'] as $k2=>$v2)
				{
					if ($units[$key]['trade_artyicle_name'] == $v2['TYPE_NAME'])
					{
						$out .= '<td bgcolor="#DCDCDC" align="center">';
			$timestamp = strtotime($units[$key]['add_date']);	
			$data = date('d-m-Y', $timestamp);
			$time = date('G:i:s', $timestamp);
			$out .= $data."<br>";
			$out .= $time."<br>";
						//$out .= $units[$key]['add_date']."<br>";
						$out .= '<input style="text-align: right;" type=text name="units['.$units[$key]['id'].'/'.$units[$key]['id_tradecategory'].'/'.$units[$key]['id_article'].'][course]" size=11 value="'.$units[$key]['value'].'"></td>';
						$key ++;
						}
					else 
					{
						$out .= '<td bgcolor="#E8E8E8" align="center"><input style="text-align: right;" type=text name="units['.$units[$keyValue]['id'].'/'.$v1['CATEGORY_ID'].'/'.$v2['TYPE_ID'].'][course]" size=11 ></td>';
					}
				}
			}
			
			$out .= '<td>&nbsp;&nbsp;<a target="_blank" href="?world='.$worldID;
			$out .= '&unit='.$units[$keyValue]['id'];
			$out .= '&action=history">history</a>&nbsp;&nbsp;</td></tr>';
			$keyValue = $key;
		}
		
		$out .= '</table>';
		$out .= '<input type="submit" value="change courses"></form>';
		
		return $out;
	}
	
	static public function outHistory($title, $history)
	{
		assert(is_array($title));
		assert(is_array($history));
		
		$out = "<h2>History of Unit Course</h2>";
		
		$out .= "<table border=1>
				<tr>
				<th>&nbsp;DATE&nbsp;</th>";
				foreach ($title as $k => $data)
				{
					$out .= "<th>".$data['trade_name']."</th>";
				}
		$out .= '</tr>';
		/*
		define("CHARTS_SOURCE", "../phpcharts/");  
		include(CHARTS_SOURCE."chartclasses.php");
		$objChart = new Chart(800, 200, "chart_art_history");
		*/
		
		$key = 0;
		$curdata = $history[$key]['add_date'];

		while ($key < count($history))
		{
			$out .= "<tr align='center'>
				<td><b>".$history[$key]['add_date']."</b></td>";
			foreach ($title as $k => $data)
			{
				if ($history[$key]['trade_name'] == $data['trade_name'] AND $history[$key]['add_date'] == $curdata)
				{
					if($history[$key]['is_current'] == 't')
					{
						$bo = '<b>';
						$bc = '</b>';
					} else {
						$bo = '';
						$bc = '';
					}
					$out .= "<td bgcolor='$color'>".$bo.$history[$key]['value'].$bc."</td>";
					$key ++;
					$curdata = $history[$key]['add_date'];
				}else {
					$out .= "<td>-</td>";
				}

			}
		}
		$out .= "</tr>";
		$out .= '</table>';
		
		/*
		$objChart->CreateChartImage(LINE_CHART);
		$out .= '<br><img src="chart_art_history.png"><br>';
		*/
		$out .= '<a href="javascript:window.close()">close window</a>';
		
		$out .= "<br><br>Current date is allocated by bold";

		return $out;
	}
	
	static public function outBackButton()
	{
		$out = '<form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="index">';
		$out .= '<input type="submit" value="Back to choose world">';
		$out .= '</form>';
		
		return $out;
	}
}

?>