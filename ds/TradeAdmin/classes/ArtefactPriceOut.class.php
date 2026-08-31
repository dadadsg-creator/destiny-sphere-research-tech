<?php

class ArtefactPriceOut
{

	static public function outIndex($worlds)
	{
		assert(is_array($worlds));
		
		$out = '<h1>Edit Artefact Course</h1>';
		$out .= '<form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="art_search">';
		
		$out .= '<b>World: </b><select name="world">';
		foreach($worlds as $k => $world)
		{
			$out .= '<option value='.$world['dbid'].'_'.$world['id_world'].'>'.$world['worldname'];
		}
		$out .= '</select>&nbsp;';
		$out .= '<input type="submit" value="select"></form>';
		
		return $out;
	}
	
	static public function outArtSearch($filters, $selected, $worldID)
	{
		assert(is_array($filters));
		assert(is_array($selected));
		assert(count($filters) == count($selected));
		
		$out = '<h1>Edit Artefact Course</h1>';
		
		$out .= '<hr><form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="csv">';
		$out .= '<input type="hidden" name="world" value="'.$worldID.'">';
		$out .= '<b>Load from CSV file</b>: <input type="file" name="csv">';
		$out .= '&nbsp;&nbsp;<input type="submit" value="update courses">';
		$out .= '</form>';
		
		$out .= '<hr><form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="art_list">';
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
			//$out .= '<option value='.$filter['id'].$s.'>'.$filter['name'];
			$out .= '</select>&nbsp;&nbsp;';
		}
		
		$out .= '<input type="submit" value="select"></form>';
		
		$out .= '<hr>';
		
		return $out;
	}
	
	static public function outArtefactList($title, $rithTitle, $artefacts, $worldID, $scope, $size)
	{		
		$out = self::outBackButton();
		
		$out .= '<table border=1>';
		
		$rowLength = 1;
		
		//-------------- Формирование шапки таблицы ----------------------
		$out .= "<tr>
				<th rowspan=2>&nbsp;name</th>
				<th align=center rowspan=2>power</th>";

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
						$tit = 'KADERIUM';
						break;
					case 'NANO_CRYSTAL':
						$tit = 'NANO';
						break;
					case 'STASIS_ENERGY':
						$tit = 'STASIS';
						break;
					case 'FOOD':
						$tit = 'FOOD';
						break;
					case 'PRODIUM':
						$tit = 'PRODIUM';
						break;
					case 'SILVER_CREDIT':
						$tit = 'SILVER';
						break;
					case 'GOLD_CREDIT':
						$tit = 'GOLD';
						break;
				}
				$out .= "<th>".$tit."</th>";
			}			
		}	
		
		$out .= "</tr>\n";

		//------------- Формирование данных таблицы -----------------------------		
		
		$keyValue = 0;
		$key = 0;

		foreach ($rithTitle as $keyart => $artList)
		{
/*echo "<xmp>";
print_r($artList);
echo "</xmp>";*/
			
			$out .= '<form action="" method="POST">';
			$out .= '<input type="hidden" name="action" value="edit">';
			$out .= '<input type="hidden" name="scope" value="'.$scope.'">';
			$out .= '<input type="hidden" name="size" value="'.$size.'">';
			$out .= '<input type="hidden" name="world" value="'.$worldID.'">';
			$out .= '<tr><td>&nbsp;'.$artList['name'].'&nbsp;</td>';
			if ($artList['name'] ==  $artefacts[$key]['name'])
			{
			$out .= '<td><input style="text-align: right;" type=text name="art['.$artList['id_artefact'].'][power]"  size=11 value="'.number_format($artefacts[$key]['power'], '', '.', '').'"></td>';
			}else 
			{
				$out .= '<td><input style="text-align: right;" type=text name="art['.$artList['id_artefact'].'][power]"  size=11></td>';
			}
			//$out .= '<td>'.number_format($artefacts[$key]['power'], '', '.', '').'</td>';

			foreach($title as $k1=>$v1)
			{
				foreach($v1['TYPES'] as $k2=>$v2)
				{
					if ($artList['name'] ==  $artefacts[$key]['name'] AND $artefacts[$key]['trade_artyicle_name'] == $v2['TYPE_NAME'])
					{
						$out .= '<td bgcolor="#DCDCDC" align="center">';
						$out .= $artefacts[$key]['add_date']."<br>";
						$out .= '<input style="text-align: right;" type=text name="art['.$artList['id_artefact'].'/'.$v1['CATEGORY_ID'].'/'.$v2['TYPE_ID'].'][course]" size=11 value="'.$artefacts[$key]['course'].'"></td>';
						$key ++;
						}
					else 
					{
						$out .= '<td bgcolor="#EEE9E9" align="center"><input style="text-align: right;" type=text name="art['.$artList['id_artefact'].'/'.$v1['CATEGORY_ID'].'/'.$v2['TYPE_ID'].'][course]" size=11 ></td>';
					}
				}
			}
			
			$out .= '<td>&nbsp;&nbsp;<a target="_blank" href="?world='.$worldID;
			$out .= '&artefact='.$artList['id_artefact'];
			$out .= '&action=history">history</a>&nbsp;&nbsp;</td></tr>';
			$keyValue = $key;
		}
		
		$out .= '</table>';
		$out .= '<input type="submit" value="change courses"></form>';
		
		return $out;
	}
	
	static public function outArtefactEdit($artefact)
	{
		$out = '';
		
		return $out;
	}
	
	static public function outHistory($title, $history)
	{
		assert(is_array($title));
		assert(is_array($history));
		
		$out = "<h2>History of Artefact Course</h2>";
		
		$out .= "<h2>".$history[0]['artefact_name']."</h2>";
		
		if (count($history) == 0)
		{
		$out .= '<h2>No History on this artefact</h2>';
		}
		else {

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
		}
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