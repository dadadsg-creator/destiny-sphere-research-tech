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
		$out .= "<b>World:</b> <select name=world_id>\n";		
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
	
		$out .= "<input type=submit value='Select new world'>\n";
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
		
		$out = "<table border=1 cellpadding=2>\n";		
		
		$rowLength = 1;
		//-------------- Формирование шапки таблицы ----------------------
		$out .="<tr>\n";
		$out .= "<th rowspan=2 >&nbsp</th>";				
		foreach($typeData['UP_TITLE'] as $k=>$v)
		{					
			$out .= "<th colspan=".count($v['TYPES']).">".$v['CATEGORY_NAME']."</th>";
			$rowLength += count($v['TYPES']);
		}		
		$out .="</tr>\n";		
		
		$out .="<tr>\n";		
		foreach($typeData['UP_TITLE'] as $k1=>$v1)
		{
			foreach($v1['TYPES'] as $k2=>$v2)
			{
				$out .= "<th>".$v2['TYPE_NAME']."</th>";	
			}			
		}		
		$out .="</tr>\n";		
		
		//------------- Формирование данных таблицы -----------------------------	
		
		$out .= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
		$out .= '<input type="hidden" name="action" value="update_exec">';
		$out .= '<input type="hidden" name="world_id" value="'.$currentDBID."_".$currentWorldID.'">';
		
		foreach($typeData['LEFT_TITLE'] as $k1=>$v1)
		{
			$out .="<tr>\n";
			$out .="<td align=left colspan=$rowLength><b>".$v1['CATEGORY_NAME']."</b></td>\n";
			$out .="</tr>\n";
			foreach($v1['TYPES'] as $k2=>$v2)
			{
				$out .="<tr>\n";
				$out .="<td align=right>".$v2['TYPE_NAME']."</td>\n";
				
				$categoryID = (int)$v1['CATEGORY_ID'];
				assert($categoryID > 0);
				
				$typeID = (int)$v2['TYPE_ID'];
				assert($typeID > 0);
				
				
				foreach($typeData['DATA'][$categoryID."_".$typeID] as $kT=>$vT)
				{					
					if(count($vT) == 0)
					{
						$content = "EMPTY";
					}else{
						
						if($vT[0]['is_allowed'] == 't')
						{
							$bgColor = '#BBFFBB';
							$selected = ' checked ';
						}else{
							$bgColor = '#FFBBBB';							
							$selected = '';
						}
						
						$content = $vT[0]['add_date']."<br>";
						$content .= "<input type=hidden name=type[".$vT[0]['id_tradetype']."][is_allowed] value=f>";
						$content .= "Trade: -<input type=text   size='1' name=type[".$vT[0]['id_tradetype']."][min_price_delta] value='".abs($vT[0]['min_price_delta'])."'>&nbsp;";
						$content .= "<input type=text  size='1' name=type[".$vT[0]['id_tradetype']."][max_price_delta] value='".$vT[0]['max_price_delta']."'><br>";	
						$content .= "Tax:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=text  size='7' name=type[".$vT[0]['id_tradetype']."][tax] value='".$vT[0]['tax']."'><br>";
						$content .= "IsAllowed: <input type=checkbox name=type[".$vT[0]['id_tradetype']."][is_allowed] value=t $selected><br>";
						$content .= "&nbsp;&nbsp;<a target='_blank' href=".$_SERVER['PHP_SELF']."?action=history&id=".$vT[0]['id_tradetype']."&world_id=".$currentDBID."_".$currentWorldID.">History</a>";
					}

					$out .= "<td align=center bgcolor=$bgColor>$content</td>\n";
				}
				
				$out .="</tr>\n";
			}
		}
		$out .= '<input type="submit" value="&nbsp;&nbsp;Edit&nbsp;&nbsp;">';
		$out .= '</form>';
		
		$out .= "</table>\n";	
		return $out; 
	}
	
	static public function _outHistoryForm($historyData, $DBID, $worldID, $ID)
	{
		assert(is_array($historyData));
		$out = '';
		//$out .= '<a href="TradeTypes.php?world_id='.$DBID.'_'.$worldID.'&action=show_full_list">BACK</a><br><br>';

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
				$color = '#BBFFBB';
				$data = '&bull;';
			} else {
				$color = '#FFBBBB';
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
		
		return $out;
	}
	
	static private function _outFooter()
	{
		$out = "<hr>";
		
		return $out; 
	}
}

?>