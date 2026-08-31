<?php

class MoneyLogOut
{
	static public function outIndex($worlds)
	{
		assert(is_array($worlds));

		$out = '<h1>Trade Logs</h1>';
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

	static public function outLogSearch($selected, $world)
	{
		assert(is_array($selected));

		$out = '<form action="" method="POST">';
		$out .= '<input type="hidden" name="action" value="trade_log">';
		$out .= '<input type="hidden" name="world" value="'.$world.'">';
		$out .= '<b>User name: </b><input type="text" name="user" size="15" value="'.$selected['user'].'">&nbsp;&nbsp;<br><br>';
		$out .= '<b>date from: </b><select name="from_y">';
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

		$out .= '<b>date to: </b><select name="to_y">';
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
		$out .= '</select>&nbsp;&nbsp;';

		$out .= '<input type="submit" value="search"></form><hr>';

		return $out;
	}

	static public function outMoneyLog($tradelog, $pageCount, $selected, $limit, $world, $totalGC, $totalSC)
	{
		$out = self::outLogSearch($selected, $world);
		
		if($pageCount > 1)
		{
			$out .= '<form action="" method="POST">';
			$out .= '<input type="hidden" name="action" value="trade_log">';
			$out .= '<input type="hidden" name="world" value="'.$world.'">';
			$out .= '<input type="hidden" name="user" value="'.$selected['user'].'">';
			$out .= '<input type="hidden" name="from_y" value="'.$selected['from_y'].'">';
			$out .= '<input type="hidden" name="from_m" value="'.$selected['from_d'].'">';
			$out .= '<input type="hidden" name="from_d" value="'.$selected['from_m'].'">';
			$out .= '<input type="hidden" name="to_y" value="'.$selected['to_y'].'">';
			$out .= '<input type="hidden" name="to_m" value="'.$selected['to_m'].'">';
			$out .= '<input type="hidden" name="to_d" value="'.$selected['to_d'].'">';
			
			/*
			$out .= '<b>Page</b>: <select name=offset>';
			for($i=0; $i<$pageCount; $i++)
			{
				if($i == $selected['page'])
				{
					$s = ' selected=1';
				} else {
					$s = '';
				}
				$out .= '<option value='.$i*$limit.' '.$s.'>';
				
				$out .= ($i+1);
			}
			$out .= '</select>'&nbsp;&nbsp;<input type="submit" value="select"></form><hr>';
			*/
		}
		
		$out .= "<table width=100% border=1 style=\"font-size: 10pt\" cellpadding=2>";

		$out .= "
			<tr>
				<td align=center><b>id list</b></td>
				<td align=center><b>user from</b></td>
				<td align=center><b>user to</b></td>
				<td align=center><b>date</b></td>
				<td align=center><b>action</b></td>
				<td align=center><b>gc</b></td>
				<td align=center><b>sc</b></td>
			</tr>";
		
		foreach($tradelog as $k => $trade)
		{
			$out .= "<tr>";

			$out .= "<td align=center>".$trade['ID_LIST']."</td>";
			$out .= "<td align=center>".$trade['USER_FROM']."</td>";
			$out .= "<td align=center>".$trade['USER_TO']."</td>";
			$out .= "<td align=center>".$trade['DATE']."</td>";
			$out .= "<td>".$trade['ACTION']."</td>";
			$out .= "<td align=right>".number_format($trade['GC'], null, null, ' ')."</td>";
			$out .= "<td align=right>".number_format($trade['SC'], null, null, ' ')."</td>";
			
			$out .= "</tr>";
		}
		
		$out .= "
			<tr>
				<td align=center colspan=4>OPERATIONS: <b>".number_format(count($tradelog), null, null, ' ')."</b></td>
				<td align=right>SUMM:</td>
				<td align=right><b>".number_format($totalGC, null, null, ' ')."</b></td>
				<td align=right><b>".number_format($totalSC, null, null, ' ')."</b></td>
			</tr>";

		$out .= "</table>";
		
		return $out;
	}
}

?>