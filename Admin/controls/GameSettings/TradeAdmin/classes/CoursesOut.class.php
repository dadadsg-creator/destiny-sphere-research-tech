<?php

class CoursesOut
{
	static public function OutMainScena($worldsData, $typeData, $title, $currentWorldID, $currentDBID)
	{
		assert(is_array($worldsData) && count($worldsData) > 0);
		assert(is_array($typeData));
		assert(is_int($currentWorldID) && $currentWorldID >= 0);
		assert(is_int($currentDBID) && $currentDBID >= 0);
		
		$out = self::_outHeader();
		
		$out .= self::_outWorldSelect($worldsData, $currentWorldID, $currentDBID);
		
		$out .= self::_outTable($typeData, $title, $currentWorldID, $currentDBID);
		
		$out .= self::_outFooter();
		
		return $out;
	}
	
	static public function _outEdirForm($data, $currentDBID, $currentWorldID, $tradeTypeID)
	{
		assert(is_array($data) && count($data) > 0);
		assert(is_int($currentWorldID) && $currentWorldID > 0);
		assert(is_int($currentDBID) && $currentDBID >= 0);
		assert(is_int($tradeTypeID) && $tradeTypeID > 0);
		
		$out = self::_outHeader();

		if($data['is_allowed'] == 't')
		{
			$checked = 'CHECKED';
		}else{
			$checked = '';
		}
		
		$out .= "<form action=".$_SERVER['PHP_SELF']." method=post>\n";		
		$out .= "Min. delta:<br> <input type=text name=min_price_delta value=".abs($data['min_price_delta'])." size=5>%<br>\n";
		$out .= "Max. delta:<br> <input type=text name=max_price_delta value=".$data['max_price_delta']." size=5>%<br>\n";
		$out .= "Tax:<br> <input type=text name=tax value=".$data['tax']." size=5>%<br>\n";
		$out .= "<input type=hidden name=is_allowed value=f>\n";
		$out .= "IsAllowed: <input type=checkbox name=is_allowed value=t $checked><br>\n";
		$out .= "<input type=hidden name=world_id value=".$currentDBID."_".$currentWorldID.">\n";
		$out .= "<input type=hidden name=id value=".$tradeTypeID.">\n";
		$out .= "<input type=hidden name=action value=update_exec>\n";
		$out .= "<input type=submit class='Submit' value=Update>\n";
		$out .= "</form>\n";
		$out .= self::_outFooter();
		
		return $out;
	}
	
	static private function _outWorldSelect($worldsData, $currentWorldID, $currentDBID)
	{
		assert(is_array($worldsData) && count($worldsData) > 0);
		assert(is_int($currentWorldID) && $currentWorldID >= 0);
		assert(is_int($currentDBID) && $currentDBID >= 0);
		
		$out = "<form>\n";
		$out .= "<b><font color=#F8F8FF>World:</font></b> <select name=world_id>\n";		
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
	
	static private function _outTable($typeData, $title, $currentWorldID, $currentDBID)
	{	
		assert(is_array($typeData));
		assert(is_array($title));
		assert(is_int($currentWorldID) && $currentWorldID >= 0);
		assert(is_int($currentDBID) && $currentDBID >= 0);
		
		if(count($typeData)==0)
		{
			return '';
		}
		
		$worldID = $currentDBID."_".$currentWorldID;

		$out = "<form method=POST action=".$_SERVER['PHP_SELF'].">";
		$out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>\n";	

		$rowLength = 1;
		//-------------- Формирование шапки таблицы ----------------------
		$out .="<tr>\n";
		$out .= "<th class=th_3>&nbsp</th>";				
		foreach($title as $k=>$v)
		{					
			$out .= "<th class=th_3 colspan=".count($v['TYPES'])."><font color=#F8F8FF>".$v['CATEGORY_NAME']."</font></th>";
			$rowLength += count($v['TYPES']);
		}		
		$out .="</tr>\n";		
		
		$out .="<tr>\n";
		$out .= "<th class=th_3>&nbsp</th>";			
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
				$out .= "<th class=th_3>".$tit."</th>";
			}			
		}		
		$out .="</tr>\n";

		//------------- Формирование данных таблицы -----------------------------	
		$i = 0;
/*echo "<pre>";
print_r($typeData);
exit;*/	
		
		// Смотрим, как у нас считаются курсы
		$recountInNano = DE_WorldSettings::CreateForCurentWorld()->get('RECOUNT_COURSES_IN_NANO');
		// Если все курсы выражаются через нану
		if ($recountInNano == 1) {
			$coursesRes = TypeSearch::GetCoursesForNano($currentDBID, $currentWorldID);
			//$coursesRes = CoursesSearch::GetCoursesForNano($currentDBID, $currentWorldID);
			foreach ($coursesRes as $kkk => $ddd)
			{
				$coursesInNano[$ddd['id_resource']] = $ddd['value'];
			}
		}
/*echo "<pre>";
print_r($coursesInNano);
exit;*/		
		foreach($typeData as $key=>$valueArr)
		{
			if ($recountInNano == 1) {
				if ($i == 0)
				{
					$out .="<tr>\n";
				}
				if ($i % 7 == 0)
				{
					$out .="</tr>\n";
					$out .="<tr>";
					$out .="<td bgcolor='#2A3E66'>\n";
					$out .= "<font color=#F8F8FF>".$valueArr['attributename']."</font>";
					$out .="</td>\n";
				}
				$out .="<td align='center' bgcolor='#F8F8FF'>";

				// Если за этот рес можно устанавливать курс
				if (($valueArr['id_tradecategory'] == 1 && $valueArr['id_article'] == 2) ||
				($valueArr['id_tradecategory'] == 4 && $valueArr['id_article'] == 1) ||
				$valueArr['id_tradecategory'] == 4 && $valueArr['id_article'] == 2)
				{
					$timestamp = strtotime($valueArr['add_date']);

					$data = date('d-m-Y G:i:s', $timestamp);
					$out .= "<span style=font-size:10pt>".$data."</span><br>";

					$out .="<input type=text name=data[".$valueArr['id_attributename']."/".$valueArr['id_article']."/".$valueArr['id_tradecategory']."] value=".number_format($valueArr['value'], 8, '.', ' ').">";
				} else {
					$recCourse = $coursesInNano[$valueArr['id_attributename']]/$coursesInNano[$valueArr['id_article']];
					$out .= number_format($recCourse, 8, '.', ' ');
					//$out .= $coursesInNano[$valueArr['id_attributename']]."/".$coursesInNano[$valueArr['id_article']];
				}

				$out .="</td>\n";
				$i ++;
			} else {
				if ($i == 0)
				{
					$out .="<tr>\n";
				}
				if ($i % 7 == 0)
				{
					$out .="</tr>\n";
					$out .="<tr>";
					$out .="<td bgcolor='#2A3E66'>\n";
					$out .= "<font color=#F8F8FF>".$valueArr['attributename']."</font>";
					$out .="</td>\n";
				}
				$out .="<td align='center' bgcolor='#F8F8FF'>";
				$timestamp = strtotime($valueArr['add_date']);

				$data = date('d-m-Y G:i:s', $timestamp);
				$out .= "<span style=font-size:10pt>".$data."</span><br>";

				$out .="<input type=text name=data[".$valueArr['id_attributename']."/".$valueArr['id_article']."/".$valueArr['id_tradecategory']."] value=".number_format($valueArr['value'], 8, '.', ' ').">";
				$out .="</td>\n";
				$i ++;
			}

		}
		$out .="<tr>\n";

		$out .= "</table>
					</td>
				</tr>
			</table>";
		
		$out .= "<input type=hidden value=$worldID name=world_id>\n";	
		$out .= "<input type=hidden value=update_exec name=action>\n";	
		
		$out .= "<br><input type=submit class='Submit' value=' Change cources '></form>\n";	
		
				$scvFile = constant("GAME_HOST")."/ds/utils/files/ResourcePrace.csv";
		
		$out .= '<table>
					<tr>
						<td valign="top">
							<form action="" method="POST">
							<input type="hidden" name="world_id" value="'.$worldID.'">
							<input type="hidden" name="action" value="export_csv">
							<input type="submit" class="Submit" value="Export CSV">
							</form>
						</td>
						<td valign="top">
							<a title="Save" href="'.$scvFile.'"><font size=4><b>Last saved csv file</b></font></a>
						</td>
					</tr>
				</table>';
		
		$out .= '<table border = 1 cellpadding="6">
					<tr>
						<td>
							<form action="" method="POST" enctype="multipart/form-data">
							<font size=4 color=#F8F8FF>Import from csv file:<br></font><br>
							<input type="hidden" name="world_id" value="'.$worldID.'">
							<input type="file" size="20" name="csv_file"><br>
							<input type="hidden" name="action" value="import_csv"><br>
							<input type="submit" class="Submit" value="Import CSV">
							&nbsp;<a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=ResourcePrice><font size=3><b>HELP</b></font></a>
							</form>
						</td>
					</tr>
				</table>';

		
		return $out; 
	}
	
	
	
	static private function _outHeader()
	{
		$out = "<h1>Edit Resource Prices</h1>\n\n";
		$out .= '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=ResourcePrice><font size=3><b>HELP</b></font></a></p>';
		
		return $out;
	}
	
	static private function _outFooter()
	{
		//$out = "<hr>";
		
		return $out; 
	}
}

?>