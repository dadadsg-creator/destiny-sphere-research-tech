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
		$out .= "<input type=submit value=Update>\n";
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
	
	static private function _outTable($typeData, $title, $currentWorldID, $currentDBID)
	{
/*echo "<xmp>";
print_r($typeData);
print_r($title);
echo "</xmp>";
exit;*/		
		assert(is_array($typeData));
		assert(is_array($title));
		assert(is_int($currentWorldID) && $currentWorldID >= 0);
		assert(is_int($currentDBID) && $currentDBID >= 0);
		
		if(count($typeData)==0)
		{
			return '';
		}
		
		$worldID = $currentDBID."_".$currentWorldID;

/*echo "<xmp>";
print_r($typeData);
echo "</xmp>";
exit;*/
		
		$out = "<form method=POST action=".$_SERVER['PHP_SELF']." > 
		<table border=1 cellpadding=2>\n";	

		$rowLength = 1;
		//-------------- Формирование шапки таблицы ----------------------
		$out .="<tr>\n";
		$out .= "<th rowspan=2 >&nbsp</th>";				
		foreach($title as $k=>$v)
		{					
			$out .= "<th colspan=".count($v['TYPES']).">".$v['CATEGORY_NAME']."</th>";
			$rowLength += count($v['TYPES']);
		}		
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
						$tit = 'NK';
						break;
					case 'STASIS_ENERGY':
						$tit = 'SE';
						break;
					case 'FOOD':
						$tit = 'F';
						break;
					case 'PRODIUM':
						$tit = 'P';
						break;
					case 'SILVER_CREDIT':
						$tit = 'SC';
						break;
					case 'GOLD_CREDIT':
						$tit = 'GC';
						break;
				}
				$out .= "<th>".$tit."</th>";
			}			
		}		
		$out .="</tr>\n";

		//------------- Формирование данных таблицы -----------------------------	
		$i = 0;
		
		foreach($typeData as $key=>$valueArr)
		{
			if ($i == 0)
			{
				$out .="<tr>\n";
				/*$out .="<td>";
				$out .="data";
				$out .="</td>";*/
			}
			if ($i % 7 == 0)
			{
				$out .="</tr>\n";
				$out .="<tr>";
				$out .="<td>\n";
				$out .= $valueArr['attributename'];
				$out .="</td>\n";
			}
			$out .="<td align='center' bgcolor='#B5B5B5'>";
			$out .="<input type=text name=data[".$valueArr['id_attributename']."/".$valueArr['id_article']."/".$valueArr['id_tradecategory']."] value=".number_format($valueArr['value'], 5, ',', ' ').">";	
//$out .= "<br>";
//$out .= $valueArr['add_date'];
//$out .= "<br>";
			$timestamp = strtotime($valueArr['add_date']);
//$out .= "+".$timestamp."+";
//$out .= "<br>";		
			$data = date('d-m-Y', $timestamp);
			$out .= "<br>";
			$time = date('G:i:s', $timestamp);
			$out .= $data."<br>";
			$out .= $time;
			$out .="</td>\n";
			$i ++;
		}
		$out .="<tr>\n";

		$out .= "</table><br>";
		
		$out .= "<input type=hidden value=$worldID name=world_id>\n";	
		$out .= "<input type=hidden value=update_exec name=action>\n";	
		
		$out .= "<input type=submit ></form>\n";	
		
		return $out; 
	}
	
	static private function _outHeader()
	{
		$out = "<h1>Edit Resource courses</h1>\n\n";
		
		return $out;
	}
	
	static private function _outFooter()
	{
		$out = "<hr>";
		
		return $out; 
	}
}

?>