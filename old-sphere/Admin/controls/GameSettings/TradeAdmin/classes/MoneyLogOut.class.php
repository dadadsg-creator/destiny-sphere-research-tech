<?php

class MoneyLogOut
{
	static public function outLogSearch($worlds, $actionTypes)
	{
		assert(is_array($worlds));

		$out = '<h1>Money Logs</h1>
				';
		
		$out .= "<SCRIPT LANGUAGE='JavaScript'>   
				   <!-- 
				   /**
					* Mark/unmark checklist
					* id = ID of parent container, name = name prefix, state = state [true/false]
					*/
					function marklist(id, name, state)
					{
						var parent = document.getElementById(id);
							if (!parent)
							{
								eval('parent = document.' + id);
							}
							
							if (!parent)
							{
								return;
							}
							
							var rb = parent.getElementsByTagName('input');
							
							for (var r = 0; r < rb.length; r++)
							{
								if (rb[r].name.substr(0, name.length) == name)
								{
								rb[r].checked = state;
								}
							}
					}
					//--> 
				 </SCRIPT> 
				";

		$out .= '
				<p align="right">
					<a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=MoneyLog>
						<font size=3><b>HELP</b></font>
					</a>
				</p>
				';

		$out .= '<form name="moneylogform" id="moneylogform" action="" method="POST">';
		$out .= '<input type="hidden" name="offset" value="0">';
		$out .= '<input type="hidden" name="action" value="trade_log">';

		$out .= '
			<table border=0>
				<tr>
					<td align="justify">
					';

		//Имя икрока
		$out .= '<b><font color=#F8F8FF>User name: </font></b><input type="text" name="user" value="'.$_SESSION['user'].'"><br><br>';

		//Количество строк на странице
		$out .= '<b><font color=#F8F8FF>Quantity of rows on page:&nbsp;&nbsp;</font></b>';

		$QuantityOfRows = array(30, 50, 70, 100, "All");

		$out .= '<select name=limit>';
		foreach ($QuantityOfRows as $k => $v)
		{
			if($v == $_SESSION['limit'])
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value="'.$v.'" '.$s.'>'.$v.'</option>';
		}
		$out .= '</select>';

		$out .= '</td>';

		$out .= '<td>';
		$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$out .= '</td>';

		$out .= '<td valign="top">';

		$nowY = date("Y");
		$nowM = date("n");
		$nowD = date("j");

		$week = strtotime("-1 week");

		if ($_SESSION['from_y'] == '')
		{
			$_SESSION['from_y'] = date('Y', $week);
		}

		if ($_SESSION['from_m'] == '')
		{
			$_SESSION['from_m'] = date('m', $week);
		}

		if ($_SESSION['from_d'] == '')
		{
			$_SESSION['from_d'] = date('d', $week);
		}

		if ($_SESSION['to_y'] == '')
		{
			$_SESSION['to_y'] = $nowY;
		}

		if ($_SESSION['to_m'] == '')
		{
			$_SESSION['to_m'] = $nowM;
		}

		if ($_SESSION['to_d'] == '')
		{
			$_SESSION['to_d'] = $nowD;
		}

		$out .= '<b><font color=#F8F8FF>Date from: </font></b><select name="from_y">';
		for($i=date('Y')-10; $i<date('Y')+1; $i++)
		{
			if($i == $_SESSION['from_y'])
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
			if($i == $_SESSION['from_m'])
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
			if($i == $_SESSION['from_d'])
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

		$out .= '<b><font color=#F8F8FF>Date to:</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b><select name="to_y">';
		for($i=date('Y'); $i>date('Y')-3; $i--)
		{
			if($i == $_SESSION['to_y'])
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
			if($i == $_SESSION['to_m'])
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
			if($i == $_SESSION['to_d'])
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			$out .= '<option value='.$i.$s.'>'.$i;
		}
		$out .= '</select>&nbsp;&nbsp;';

		$out .= '</td>';
		$out .= "</tr></table>";

		$out .= '<table border=0><tr><td valign=top>';
		$out .= '<tr><td valign=top>';

		//Список миров
		$out .= '<b><font color=#F8F8FF>Worlds: </font></b><br>';
		$out .= '</td><td valign=top>';

		//если в сесси нет миров, записываем туда первый мир
		if (empty($_SESSION['worlds']))
		{
			$_SESSION['worlds'][0] = $worlds[0]['dbid'].'_'.$worlds[0]['id_world'];
		}

		foreach($worlds as $k => $world)
		{
			if(in_array($world['dbid'].'_'.$world['id_world'], $_SESSION['worlds']))
			{
				$c = ' checked ';
			} else {
				$c = '';
			}
			$out .= '<input type=checkbox name="worlds[]" value='.$world['dbid'].'_'.$world['id_world'].' '.$c.'><font color=#F8F8FF> '.$world['worldname'].'</font><br>';
		}

		$out .= '</td><td valign=top>';
		$out .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		$out .= '</td><td valign=top>';


		//Список операций
		$out .= "<b><font color=#F8F8FF>Actions: </font></b><br>";

		$out .= '</td>';
		$out .= '<td valign=top>';
		
		$out .= "
				<INPUT TYPE='button' class='Submit' onClick=\"marklist('moneylogform','actionTypes',true)\" VALUE='&nbsp;&nbsp;Mark All&nbsp;&nbsp;&nbsp;'><br>
				<INPUT TYPE='button' class='Submit' onClick=\"marklist('moneylogform','actionTypes',false)\" VALUE='UnMark All'><br>";


		$countr = 0;
		if ($_SESSION['actionTypes'] != '')
		{
			foreach($actionTypes as $k => $actionType)
			{
				if(in_array($actionType['action_name'], $_SESSION['actionTypes']))
				{
					$c = ' checked ';
				} else {
					$c = '';
				}
				$out .= '<input  type=checkbox name="actionTypes[]" value='.$actionType['action_name'].' '.$c.'><font color=#F8F8FF> '.$actionType['action_name'].'</font>';
				$out .= '<br>';

				$countr ++;
				if ($countr == ceil(count($actionTypes)/2)-1)
				{

					$out .= '</td><td valign=top>';
				}
			}
		}
		else {
			foreach($actionTypes as $k => $actionType)
			{
				$out .= '<input  type=checkbox name="actionTypes[]" value='.$actionType['action_name'].' checked><font color=#F8F8FF> '.$actionType['action_name'].'</font>';
				$out .= '<br>';
				$countr ++;
				if ($countr == ceil(count($actionTypes)/2)-1)
				{
					$out .= '</td><td valign=top>';
				}
			}
		}

		$out .= '</td></tr></table>';
		$out .= '<br>';
		$out .= '<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"></form>';

		return $out;
	}

	static public function outMoneyLog($currentAccount, $moneyLog, $pageCount, $offset)
	{
		$totalGC = 0;
		$totalSC = 0;
		$limit = $_SESSION['limit'];

		if (!empty($currentAccount))
		{
			$out = "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                                <tr>
                                        <td>
                                                <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";
			$out .= "<tr><th class=th_3 colspan=3>";
			$out .= "Current state";
			$out .= "</th></tr>";

			$out .= "<tr><th class=th_3 >";
			$out .= "&nbsp;&nbsp;&nbsp;World&nbsp;&nbsp;&nbsp;";
			$out .= "</th><th class=th_3 >";
			$out .= "&nbsp;&nbsp;&nbsp;Gold credit&nbsp;&nbsp;&nbsp;";
			$out .= "</th>";
			$out .= "</th><th class=th_3 >";
			$out .= "&nbsp;&nbsp;&nbsp;Silver credit&nbsp;&nbsp;&nbsp;";
			$out .= "</th>";
			$out .= "</tr>";

			$out .= "<tr><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= "Kabinet";
			$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= number_format($currentAccount['USER']['GC'], 0, ',', ' ');
			$Sum['GC'] = $currentAccount['USER']['GC'];
			$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= number_format($currentAccount['USER']['SC'], 0, ',', ' ');
			$Sum['SC'] = $currentAccount['USER']['SC'];
			$out .= "</font></td>";
			$out .= "</tr>";

			foreach ($currentAccount['USER_INSTANCE'] as $worldName => $d)
			{
				$out .= "<tr><td bgcolor='#444E6C'><font color=#F8F8FF>";
				$out .= $worldName;
				$out .= "</td><td bgcolor='#444E6C'><font color=#F8F8FF>";
				$out .= number_format($d['GC'], null, null, ' ');
				$Sum['GC'] += $d['GC'];
				$out .= "</font></td>";
				$out .= "<td bgcolor='#444E6C'><font color=#F8F8FF>";
				$out .= number_format($d['SC'], null, null, ' ');
				$Sum['SC'] += $d['SC'];
				$out .= "</font></td></tr>";
			}

			$out .= "<tr><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= "<b>Sum</b>";
			$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= "<b>".number_format($Sum['GC'], null, null, ' ')."</b>";
			$out .= "</font></td><td bgcolor='#444E6C'><font color=#F8F8FF>";
			$out .= "<b>".number_format($Sum['SC'], null, null, ' ')."</b>";
			$out .= "</font></td>";
			$out .= "</tr>";
			$out .= "</table>
                                        </td>
                                </tr>
                        </table>";

			$out .= "<br>";
		}

		//Если выборка пуста
		if (empty($moneyLog))
		{
			return "<h2>NO DATA</h2>";
		}

		$out .= "<table width=100% border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                                <tr>
                                        <td>
                                                <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>\n";

		$out .= "
                        <tr>
                                <td class=th_3 align=center> World </td>
                                <td class=th_3 align=center> User from </td>
                                <td class=th_3 align=center> User to </td>
                                <td class=th_3 align=center> Date </td>
                                <td class=th_3 align=center> Action </td>
                                <td class=th_3 align=center> GC </td>
                                <td class=th_3 align=center> SC </td>
                                <td class=th_3 align=center> Detail </td>
                        </tr>";


		if ($limit == 'All')
		{
			$key = 0;
			$last = count($moneyLog);
		} else {

			$key = $offset;
			if  (count($moneyLog) > ($offset+$limit))
			{
				$last = $offset+$limit;
			} else {
				$last = count($moneyLog);
			}
		}

		for ($key; $key < $last; $key++)
		{
			$out .= "<tr>";

			$out .= "<td bgcolor=#444E6C align=center><font color=#F8F8FF>".$moneyLog[$key]['worldname']."</font></td>";
			$out .= "<td bgcolor=#444E6C align=center><font color=#F8F8FF>".$moneyLog[$key]['user_from']."</font></td>";
			$out .= "<td bgcolor=#444E6C align=center><font color=#F8F8FF>".$moneyLog[$key]['user_to']."</font></td>";
			$out .= "<td bgcolor=#444E6C align=center><font color=#F8F8FF>".$moneyLog[$key]['add_date']."</font></td>";
			$out .= "<td bgcolor=#444E6C align=center><font color=#F8F8FF>".$moneyLog[$key]['action_name']."</font></td>";		
			$out .= "<td bgcolor=#444E6C align=right><font color=#F8F8FF>".number_format($moneyLog[$key]['gc_delta'], null, null, ' ')."</font></td>";
			$out .= "<td bgcolor=#444E6C align=right><font color=#F8F8FF>".number_format($moneyLog[$key]['sc_delta'], null, null, ' ')."</font></td>";
			
			//Если есть детали, то выводим их
			if (!empty($moneyLog[$key]['detail'])) {

				if ($moneyLog[$key]['action_name'] == 'SEND_INTERWORLD_TRANSFER') {
					$det = 'To world ';
				} elseif ($moneyLog[$key]['action_name'] == 'RECIVE_INTERWORLD_TRANSFER') {
					$det = 'From world ';
				}elseif ($moneyLog[$key]['action_name'] == 'TRADE_PAYMENT' 
							OR 
							$moneyLog[$key]['action_name'] == 'PAY_TRADE_TAX' 
							OR 
							$moneyLog[$key]['action_name'] == 'MOVE_TO_RESERV' 
							OR 
							$moneyLog[$key]['action_name'] == 'MOVE_FROM_RESERV') {
					$det = 'Sell ';
				} else {
					$det = '';
				}

				$out .= "<td bgcolor=#444E6C align=left><font color=#F8F8FF>".$det.$moneyLog[$key]['detail']."</font></td>";
			} else {
				$out .= "<td bgcolor=#444E6C align=center><font color=#F8F8FF>-</font></td>";
			}

			$totalGC += $moneyLog[$key]['gc_delta'];
			$totalSC += $moneyLog[$key]['sc_delta'];

			$out .= "</tr>";
		}

		$out .= "
                        <tr>
                                <td bgcolor=#2A3E66 align=center colspan=4><font color=#F8F8FF>OPERATIONS: <b>".number_format(count($moneyLog), null, null, ' ')."</b></font></td>
                                <td bgcolor=#2A3E66 align=right><font color=#F8F8FF>SUMM:</td>
                                <td bgcolor=#2A3E66 align=right><font color=#F8F8FF><b>".number_format($totalGC, null, null, ' ')."</b></font></td>
                                <td bgcolor=#2A3E66 align=right><font color=#F8F8FF><b>".number_format($totalSC, null, null, ' ')."</b></font></td>
                                <td bgcolor=#2A3E66></td>
                        </tr>";

		$out .= "</table>";

		$out .= "</td>
               </tr>
             </table>";

		//Выводим меню выбора сраниц
		if($pageCount > 1)
		{
			$out .= "<table border=0><tr><td>";
			for($i=0; $i<$pageCount; $i++)
			{
				if ($_SESSION['page'] == $i)
				{
					$p = ($i+1);
					$out .= "<b>[$p]</b>&nbsp;&nbsp;&nbsp;";
				}
				else {
					$curOffset = $i*$limit;
					$p = ($i+1);
					$out .= "<a href=".$_SERVER['PHP_SELF']."?action=trade_log&offset=$curOffset><b>$p</b></a>&nbsp;&nbsp;&nbsp;";
				}

				if ((($i+1)%30) == 0)
				{
					$out .= "</td></tr><tr><td>";
				}else {
					$out .= "</td><td>";
				}
			}
			$out .= "</td></tr></table>";
		}


		$out .= "<hr>";

		//Выводим кнопку экспорта в csv
		$out .= "<table bgcolor=#444E6C border=0 cellspacing=6 cellpadding=1>";
		$out .= "<tr><td valign='top'>";

		$out .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
		$out .= '<input type="hidden" name="action" value="import_csv">';
		$out .= '<input type="hidden" name="offset" value="'.$offset.'">';
		$out .= '<input type="submit" class="Submit" value=" Export in csv "></form>';

		$out .= "</td><td valign='top'>";
		
		$fileurl = "/Admin/controls/GameSettings/Localization/Export_CVS/data/MoneyLogs.csv";
		//$scvFile = $_SERVER['DOCUMENT_ROOT'].$fileurl;
		$scvFile = $fileurl;

		$out .= '<a title="Save" href="'.$scvFile.'"><font size=4><b>Last saved csv file</b></font></a><br><br>';

		$out .= "</td></tr></table>";

		return $out;
	}
}

?>
