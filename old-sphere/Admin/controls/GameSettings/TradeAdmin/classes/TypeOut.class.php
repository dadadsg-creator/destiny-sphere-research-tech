<?php

class TypeOut
{
	static public function OutMainScena($worldsData, $typeData, $currentWorldID, $currentDBID)
	{
		assert(is_array($worldsData) && count($worldsData) > 0);
		assert(is_array($typeData));
		assert(is_int($currentWorldID) && $currentWorldID >= 0);
		assert(is_int($currentDBID) && $currentDBID >= 0);

		$out = self::_outHeader();

		$out .= self::_outWorldSelect($worldsData, $currentWorldID, $currentDBID);

		$out .= self::_outTable($typeData, $currentWorldID, $currentDBID);

		$out .= self::_outFooter();

		return $out;
	}

	static private function _outWorldSelect($worldsData, $currentWorldID, $currentDBID)
	{
		assert(is_array($worldsData) && count($worldsData) > 0);
		assert(is_int($currentWorldID) && $currentWorldID >= 0);
		assert(is_int($currentDBID) && $currentDBID >= 0);

		$out = "<form>\n";
		$out .= "<b><font color=#F8F8FF>World:</b> </font><select name=world_id>\n";
		foreach($worldsData as $k=>$v)
		{
			$id = $v['dbid']."_".$v['id_world'];

			if($currentDBID == $v['dbid'] && $currentWorldID == $v['id_world'])
			{
				$selected = "SELECTED";
			}else{
				$selected = "";
			}

			$out .= "<option value=$id $selected>".$v['worldname']."</option>\n";
		}
		$out .= "</select>\n";
		$out .= "<input type=hidden name=action value=show_full_list>\n";

		$out .= "<input type=submit class='Submit' value='Select new world'>\n";
		$out .= "</form>\n\n";

		return $out;
	}

	static private function _outTable($typeData, $currentWorldID, $currentDBID)
	{
		assert(is_array($typeData));
		assert(is_int($currentWorldID) && $currentWorldID >= 0);
		assert(is_int($currentDBID) && $currentDBID >= 0);

		if(count($typeData)==0)
		{
			return '';
		}

		foreach($typeData['LEFT_TITLE'] as $k1=>$v1)
		{
			$out .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
			$out .= "<font color=#F8F8FF><input type='submit' class='Submit' value='&nbsp;&nbsp;EDIT ".$v1['CATEGORY_NAME']." COURSES&nbsp;&nbsp;'></font>";
			$out .= "<br>";
			$out .= "<br>";
			
			$out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>\n";

		$rowLength = 1;
		//-------------- Формирование шапки таблицы ----------------------
		$out .="<tr>\n";
		//$out .= "<th class=th_2 rowspan=2 >&nbsp</th>";
		$out .= "<th class=th_3>&nbsp</th>";
		foreach($typeData['UP_TITLE'] as $k=>$v)
		{
			$out .= "<th class=th_3 colspan=".count($v['TYPES'])."><font color=#F8F8FF>".$v['CATEGORY_NAME']."</font></th>";
			$rowLength += count($v['TYPES']);
		}
		$out .="</tr>\n";

		$out .="<tr>\n";
		$out .= "<td class=th_3 align=rith><font color=#F8F8FF>".$v1['CATEGORY_NAME']."</font></td>";
		foreach($typeData['UP_TITLE'] as $k=>$v)
		{
			foreach($v['TYPES'] as $k=>$v)
			{
				$out .= "<th class=th_3><font color=#F8F8FF>".$v['TYPE_NAME']."</font></th>";
			}
		}
		$out .="</tr>\n";

			/*$out .="<tr>\n";
			$out .="<td bgcolor=#2E4370 align=left colspan=$rowLength><b><font color=#F8F8FF>".$v1['CATEGORY_NAME']."</font></b></td>\n";
			$out .="</tr>\n";
			$out .="<tr>\n";
			$out .= "<td bgcolor=#2E4370 align=left colspan=$rowLength><b><font color=#F8F8FF><input type='submit' class='Submit' value='&nbsp;&nbsp;EDIT ".$v1['CATEGORY_NAME']." COURSES&nbsp;&nbsp;'></td>";
			$out .="</tr>\n";*/
			
			foreach($v1['TYPES'] as $k2=>$v2)
			{
				$out .="<tr>\n";
				$out .="<td bgcolor='#2A3E66' align=right><font color=#F8F8FF>".$v2['TYPE_NAME']."</font></td>\n";

				$categoryID = (int)$v1['CATEGORY_ID'];
				assert($categoryID > 0);

				$typeID = (int)$v2['TYPE_ID'];
				assert($typeID > 0);


				foreach($typeData['DATA'][$categoryID."_".$typeID] as $kT=>$vT)
				{
					if(count($vT) == 0)
					{
						$bgColor = '#F8F8FF';
						$content = "<input type=hidden name=type[".$categoryID."_".$typeID."/".$kT."][is_allowed] value=f>";
						$content .= "Trade: -<input type=text   size='1' name=type[".$categoryID."_".$typeID."/".$kT."][min_price_delta]>&nbsp;&nbsp;";
						$content .= "<input type=text  size='1' name=type[".$categoryID."_".$typeID."/".$kT."][max_price_delta] ><br>";
						$content .= "Tax:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=text  size='6' name=type[".$categoryID."_".$typeID."/".$kT."][tax] value='".$vT[0]['tax']."'><br>";
						$content .= "IsAllowed:&nbsp;&nbsp;&nbsp;<input type=checkbox name=type[".$categoryID."_".$typeID."/".$kT."][is_allowed] value=t><br>";
					}else{

						if($vT[0]['is_allowed'] == 't')
						{
							$bgColor = '#F0FFFF';
							$selected = ' checked ';
						}else{
							$bgColor = '#FFF0F5';
							$selected = '';
						}

						$content = "<span style=font-size:10pt>".$vT[0]['add_date']."</span><br>";
						$content .= "<input type=hidden name=type[".$categoryID."_".$typeID."/".$kT."][is_allowed] value=f>";
						$content .= "Trade:<br>-<input type=text   size='4' name=type[".$categoryID."_".$typeID."/".$kT."][min_price_delta] value='".abs($vT[0]['min_price_delta'])."'>&nbsp;&nbsp;";
						$content .= "<input type=text  size='4' name=type[".$categoryID."_".$typeID."/".$kT."][max_price_delta] value='".$vT[0]['max_price_delta']."'><br>";
						$content .= "Tax:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=text  size='6' name=type[".$categoryID."_".$typeID."/".$kT."][tax] value='".$vT[0]['tax']."'><br>";
						$content .= "IsAllowed:&nbsp;&nbsp;&nbsp;<input type=checkbox name=type[".$categoryID."_".$typeID."/".$kT."][is_allowed] value=t $selected><br>";
						$content .= "&nbsp;&nbsp;<a target='_blank' href=".$_SERVER['PHP_SELF']."?action=history&id=".$vT[0]['id_tradetype']."&world_id=".$currentDBID."_".$currentWorldID."><font color=#708090>History</font></a>";
					}

					$out .= "<td align=center bgcolor=$bgColor>$content</td>\n";
				}

				$out .="</tr>\n";
			}

			$out .= '<input type="hidden" name="action" value="update_exec">';
			$out .= '<input type="hidden" name="world_id" value="'.$currentDBID."_".$currentWorldID.'">';
		
			$out .= '</form>';

			$out .="<tr>";
			$out .= "<td bgcolor='#B0C4DE'><font color=#00008B><b>EDIT ALL COLUMN</b></font></td>";

			foreach($typeData['UP_TITLE'] as $k1=>$v1)
			{
				foreach($v1['TYPES'] as $k2=>$v2)
				{
					//$out .= "<td bgcolor='#B0C4DE'><font color=#00008B>".$v2['TYPE_NAME']."</font></td>";
					$out .= "<td  align=center bgcolor='#B0C4DE'><font color=#00008B>";
					$out .= "<form action='".$_SERVER['PHP_SELF']."' method='POST'>\n";
					$content = "<input type=hidden name=type[".$categoryID."/".$v1['CATEGORY_ID']."_".$v2['TYPE_ID']."][is_allowed] value=f>";
					$content .= "Trade:<br>";
					$content .= " -<input type=text   size='4' name=type[".$categoryID."/".$v1['CATEGORY_ID']."_".$v2['TYPE_ID']."][min_price_delta]>&nbsp;&nbsp;";
					$content .= "<input type=text  size='4' name=type[".$categoryID."/".$v1['CATEGORY_ID']."_".$v2['TYPE_ID']."][max_price_delta]><br>";
					$content .= "Tax:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=text  size='6' name=type[".$categoryID."/".$v1['CATEGORY_ID']."_".$v2['TYPE_ID']."][tax] ><br>";
					$content .= "IsAllowed:&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name=type[".$categoryID."/".$v1['CATEGORY_ID']."_".$v2['TYPE_ID']."][is_allowed] value=t><br>";
					$out .= "$content\n";
					$out .= "<input type=hidden name=action value=edit_column>\n";
					$out .= '<input type="hidden" name="world_id" value="'.$currentDBID."_".$currentWorldID.'">';
					$out .= "<br><input type='submit' class='Submit' value='&nbsp;&nbsp;Edit&nbsp;&nbsp;'>";
					$out .= "</form>";
					$out .= "</font></td>\n";
				}
			}
			$out .="</tr>";
					$out .= "</table>
					</td>
				</tr>
			</table>";
					$out .= "<br>";
					$out .= "<br>";
		}
		return $out;
	}

	static public function _outHistoryForm($historyData, $DBID, $worldID, $ID)
	{
		assert(is_array($historyData));
		$out = '';

		$out .= '<table width=60% border=1>';
		$out .= '<tr bgcolor=#B0B0B0>
					<td align=center><b>date</b></td>
					<td width="20%" align=center><b>min trand</b> %</td>
					<td width="20%" align=center><b>max trand</b> %</td>
					<td width="20%" align=center><b>tax</b> %</td>
					<td width="10%" align=center><b>allowed</b></td>
				</tr>
				';
		foreach($historyData as $k => $data)
		{
			if($data['is_current'] == 't')
			{
				$color = '#BBBBFF';
			} else {
				$color = '#FFFFFF';
			}
			$out .= '<tr bgcolor='.$color.'>';
			$out .= '<td align=center>'.$data['add_date'].'</td>';
			$out .= '<td align=center>'.$data['min_price_delta'].'</td>';
			$out .= '<td align=center>'.$data['max_price_delta'].'</td>';
			$out .= '<td align=center>'.$data['tax'].'</td>';
			if($data['is_allowed'] == 't')
			{
				$color = '#F0FFFF';
				$data = '&bull;';
			} else {
				$color = '#FFF0F5';
				$data = '&nbsp;';
			}
			$out .= '<td align=center bgcolor='.$color.'>'.$data.'</td>';
			$out .= '</tr>';
		}

		$out .= '</table><br>';
		$out .= '<a href="javascript:window.close()">close window</a>';

		return $out;
	}

	static private function _outHeader()
	{
		$out = "<h1>Edit trade types</h1>\n\n";
		
		$out .= '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=TradeType><font size=3><b>HELP</b></font></a></p>';

		return $out;
	}

	static private function _outFooter()
	{
		return $out;
	}
}

?>