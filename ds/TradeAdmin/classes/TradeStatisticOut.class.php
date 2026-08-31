<?php
class TradeStatisticOut
{
	function outIndex($worlds)
	{
		assert(is_array($worlds));
		$out = '<h1>Trade Statistic</h1>';
		$out .= '<form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="search">';

		$out .= '<b>World: </b><select name="world">';
		foreach($worlds as $k => $world)
		{
			$out .= '<option value='.$world['dbid'].'_'.$world['id_world'].'>'.$world['worldname'];
		}
		$out .= '</select>&nbsp;';
		$out .= '<input type="submit" value="select"></form>';

		return $out;
	}
	
	static public function outLogSearch($worlds, $selected, $selectedworld, $scopes, $selectedSubject, $selectedScope)
	{
		assert(is_array($worlds));
		assert(is_array($selected));
		assert(is_array($scopes));
/*echo "<xmp>";		
print_r($worlds);
print_r($selectedworld)	;
echo "</xmp>";*/

		$subjects = array(
							0 => array('value' => 'artefact', 'description' => ' Artefact statistic '),
							1 => array('value' => 'unit', 'description' => ' Unit statistic '),
							2 => array('value' => 'resource', 'description' => ' Resources statistic '),
							3 => array('value' => 'ALL', 'description' => ' ALL '),
							);
							
		$out = '<h1>Trade Statistic</h1>';
		$out .= '<form action="" method="POST">';
		$out .= "<table border=0><tr><td valign=top align=left>";
		$out .= '<input type="hidden" name="action" value="search">';

		$out .= '<b>Worlds: </b>';
		$out .= "</td>";
		$out .= "<td valign=top>";
	
		foreach($worlds as $k => $world)
		{
			if(in_array($world[dbid]."_".$world[id_world], $selectedworld))
			{
				$c = ' checked ';
			} else {
				$c = '';
			}
			$out .= '<input type=checkbox name="world[]" value='.$world['dbid'].'_'.$world['id_world'].' '.$c.'>'.$world['worldname'];
			$out .= '<br>';
			//$out .= '<option value='.$world['dbid'].'_'.$world['id_world'].'>'.$world['worldname'];
		}
		$out .= '<br>';

		$out .= "</td>";
		$out .= "<td valign=top>";
		
		$out .= '<input type="hidden" name="action" value="trade_log">';

		$out .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;Goods Type:&nbsp;&nbsp;</b>';
		$out .= '<select name="subject">';
		foreach ($subjects as $key => $val)
		{
			if($val['value'] == $selectedSubject)
			{
				$s = ' selected=1 ';
			} else {
				$s = '';
			}
			$out .= '<option name="subject" value="'.$val['value'].'"'.$s.'>'.$val['description'].'</option>';
		}
		$out .= '</select>';
//$out .= "</td></tr>";		
		$out .= '<br>';
		$out .= '<br>';
		
//$out .= "<tr><td>";
		$out .= "&nbsp;&nbsp;&nbsp;&nbsp;<b>Scope</b>:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$out .= '<select name="scope">';
		foreach ($scopes as $key => $val)
		{
			if($val['tradescopename'] == $selectedScope)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= "<option value=".$val['id_tradescope']."-".$val['tradescopename'].$s." >".$val['tradescopename']."</option>";
		}
		
		if($selectedScope == 'ALL')
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
		$out .= "<option value='ALL'".$s." >ALL</option>";
		
		$out .=	"</select>";
		$out .= '<br><br>';
		$out .= "</td>";
		$out .= "<td valign=top align=left>";

		$out .= '&nbsp;&nbsp;&nbsp;<b>Date</b>&nbsp;&nbsp;';
		//$out .= '<br>';
		$out .=  '<b>from: </b><select name="from_y">';
		for($i=date('Y')-2; $i<date('Y')+1; $i++)
		{
			if($i == $selected['from_y'])
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select> - ';
		$out .= '<select name="from_m">';
		for($i=1; $i<13; $i++)
		{
			if($i == $selected['from_m'])
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select> - ';
		$out .= '<select name="from_d">';
		for($i=1; $i<32; $i++)
		{
			if($i == $selected['from_d'])
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select>&nbsp;&nbsp;&nbsp;&nbsp;';

		$out .= '<br>';
		$out .= '<br>';

		$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$out .= '<b>to: </b><select name="to_y">';
		for($i=date('Y'); $i>date('Y')-3; $i--)
		{
			if($i == $selected['to_y'])
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select> - ';
		$out .= '<select name="to_m">';
		for($i=12; $i>0; $i--)
		{
			if($i == $selected['to_m'])
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select> - ';
		$out .= '<select name="to_d">';
		for($i=31; $i>0; $i--)
		{
			if($i == $selected['to_d'])
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select>';
$out .= "</td></tr>";				
$out .= "</table>";		
		$out .= '<input type="submit" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"></form><hr>';
//echo $out;		
//exit;
		return $out;
	}
	
	static public function outStat($tradeStat, $selected, $scopeName)
	{
		//assert(is_array($rithTitle));
		assert(is_array($tradeStat));
		assert(is_array($selected));
		assert(!empty($scopeName));
/*echo "<xmp>";
print_r($tradeStat)	;		
echo "</xmp>";
exit;*/		
		$title = array(KADERIUM, NANO_CRYSTAL, STASIS_ENERGY, FOOD, PRODIUM, SILVER_CREDIT, GOLD_CREDIT);

		$startDate = date("d-m-Y", mktime(0, 0, 0, (int)$selected['from_m'], (int)$selected['from_d'], (int)$selected['from_y']));
		$endDate = date("d-m-Y", mktime(0, 0, 0, (int)$selected['to_m'], (int)$selected['to_d'], (int)$selected['to_y']));


		$out = "<table width=100% border=1 style=\"font-size: 10pt\" cellpadding=2 bgcolor='#F0FFFF' bordercolor='#191970'>";
		//$out .= "<h3><font color='#191970'>Statistic from  $startDate to $endDate&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Scope: $scopeName</font></h3>";
		
		//$out .= "<h3><font color='#191970'>Scope: $scopeName</font></h3>";

		$out .= "<tr>
				<th rowspan=2>NAME</th>";
		//$out .= "<tr>";
		foreach ($title as $key => $val)
		{
			$out .= "<th colspan=2 >$val</th>";
		}
		//$out .= "<th rowspan=2>SCOPE</th>";
		$out .= "</tr>";
		
		$out .= "<tr>";
		
		foreach ($title as $key => $val)
		{
			$out .= "<th>ammount<br>from</th>";
			$out .= "<th>ammount<br>to</th>";
		}
			$out .= "</tr>";

		$count = 0;
		
		//Чтобы вдруг не зациклилось
		$i = 0;
		
		while ($count <= (count($tradeStat)-1) AND $i <= 200)
		{
			if (($i % 2) == 0)   
			{
				$bgcolor = '#F8F8FF';
			}
			else {
				$bgcolor = '#F0FFFF';
			}

			$out .= "<tr bgcolor='$bgcolor'>";
			$out .= '<td align="center">'.$tradeStat[$count]['goods_from'].'</td>';
			//$out .= "<td align='center'>".$tradeStat[$count]['trade_scope']."</td>";
			
			foreach($title as $key=>$val)
			{
				if ($tradeStat[$count]['goods_to'] == $val)
					{
						$out .= '<td align="center">';
						$out .= number_format($tradeStat[$count]['ammounf_from']);
						$out .= '</td>';
						$out .= '<td align="center">';
						$out .= number_format($tradeStat[$count]['ammount_to']);
						$out .= '</td>';
						$count ++;
						//$i ++;
					}
					else 
					{
						$out .= '<td align="center">-</td>';
						$out .= '<td align="center">-</td>';
					}
					//$i ++;
			}
			$out .= "</tr>";
			$i ++;
		}

		$out .= "</table>";
		
		return $out;
	}
}

?>