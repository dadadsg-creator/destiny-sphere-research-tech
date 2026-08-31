<?php

class UnitReincarnatePriceSearch
{
	static public function updateUnitReincarnatePrice($id_unit, $id_tradecategory, $id_article, $course, $DBID, $worldID)
	{
		assert(is_int($id_unit) && $id_unit > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_float($course) && $course > 0);

		$common = DS_Common::CreateForCurentDB($DBID);
		
		// Апдейтим старую цену
		$sql = "UPDATE 
					ds_unit_reincarnation_cost 
                SET 
                	value = $course 
                WHERE 
                	id_unit = $id_unit
	                AND
	                id_world = $worldID
	                AND
	                id_tradecourse = (
	                			SELECT 
	                				id_tradecourse 
	                			FROM 
	                				ds_trade_course 
	                			WHERE 
	                				id_tradecategory = $id_tradecategory 
	                				AND 
	                				id_article = $id_article)";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res[0]['res'];
	}	
	
	static public function insertUnitReincarnatePrice($id_unit, $id_tradecategory, $id_article, $course, $DBID, $worldID)
	{
		assert(is_int($id_unit) && $id_unit > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_float($course) && $course > 0);

		$common = DS_Common::CreateForCurentDB($DBID);
		
		// Апдейтим старую цену
		$sql = "INSERT INTO 
					ds_unit_reincarnation_cost 
                (id_world, id_unit, value, id_tradecourse)
              SELECT 
	                				$worldID, $id_unit, $course, id_tradecourse 
	                			FROM 
	                				ds_trade_course 
	                			WHERE 
	                				id_tradecategory = $id_tradecategory 
	                				AND 
	                				id_article = $id_article";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res[0]['res'];
	}	
	
	static public function getTitle($DBID, $worldID)
	{
		assert(is_int($DBID) && $DBID>=0);		
		assert(is_int($worldID) && $worldID>=0);		
				
		//--------------- Формирование верхнего заголовка таблицы -------------------------
		$upTitleCategoryes = TypeSearch::GetTableUpRowsCategorys($DBID);
		
		foreach ($upTitleCategoryes as $k1=>$v1)
		{
			$categoryName = $v1['categoryname'];
			assert(!empty($categoryName));
			
			$categoryID = (int)$v1['id_tradecategory'];
			assert($categoryID > 0);
			
			$tmp = array();
			$typeData = TypeSearch::GetTypesByCategoryName($DBID, $categoryName);
		
			foreach($typeData as $k2=>$v2)
			{
				if ($v2['name'] == 'GOLD_CREDIT') {
					continue;
				}
				$typeName = $v2['name'];
				assert(!empty($typeName));
				
				$typeID = (int)$v2['id'];
				assert($typeID > 0);
				
				$tmp[] = array
				(					
					'TYPE_NAME' => $typeName,
					'TYPE_ID' => $typeID
				);
			}
			
			$title[] = array
			(
				'CATEGORY_NAME' => $categoryName,
				'CATEGORY_ID' => $categoryID,
				'TYPES' => $tmp
			);
		}
			
		return $title;
	}

	/*static public function getUnitsByRaceAndClass($race, $class, $DBID, $worldID)
	{
		//assert(!empty($race));
		//assert(!empty($class));
		
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		if($race == 4)
		{
			$where = "ucbbr.id_race IS NULL";
		} else {
			$where = "ucbbr.id_race = ".$race;
		}
		
		$sql = "SELECT u.id_unit as id,
				        COALESCE(ltl.text, u.unitname) as name,
				        tuc.value,
				        tc.id_tradecategory,
				        tc.id_article,
				    	tca.categoryname AS tradecourse_name,
					    CASE
					    WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
					    WHEN tca.categoryname = 'MONEY' THEN m.money_name
					    ELSE NULL
					    END AS trade_artyicle_name
				FROM	ds_unit AS u
				INNER JOIN ds_unithasclass AS uhc ON (uhc.id_unit = u.id_unit)
				LEFT JOIN ds_unitcanbebuildbyrace AS ucbbr ON (ucbbr.id_unit = u.id_unit)
				LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = u.id_localizedname AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
				INNER JOIN ds_unit_reincarnation_cost AS tuc ON (tuc.id_unit = u.id_unit)
				INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tuc.id_tradecourse)
				INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
				LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
				LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article)
			WHERE  ";
				
		if ($race != 'all')
		{
			$sql .= " $where
				AND ";
		}

		$sql .= " tuc.id_world = $worldID
				$whereTrade ";

		
		if ($class != 'all')
		{	
		assert($class >= 0);			
		$sql .= " AND
				uhc.id_unitclass = $class";
		}
		
		$sql .= " ORDER BY ucbbr.id_race, u.id_unit, tc.id_tradecourse";
exit($sql);
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
	
		return $res;
	}*/
	
	
	static public function getUnitsByRaceAndClass($race, $class, $DBID, $worldID, $onlyForNano = false)
	{
		assert(!empty($race));
		assert(!empty($class));

		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);

		$common = DS_Common::CreateForCurentDB($DBID);

		//if($race == 4)
		//{
		//	$where = "ucbbr.id_race IS NULL";
		//} else {
			$where = "ucbbr.id_race = ".$race;
		//}
		
		$whereTrade= '';
		if ($onlyForNano == true) {
			$whereTrade = '
					AND
                     (tuc.id_tradecourse = 2
                        OR
                        tuc.id_tradecourse = 6
                        OR
                        tuc.id_tradecourse = 7
                        ) ';			
		}

		$sql = "SELECT  u.id_unit as id,  
				        COALESCE(ltl.text, u.unitname) as name,
				        tuc.value,
				        tc.id_tradecategory,
				        tc.id_article,
				    	tca.categoryname AS tradecourse_name,
					    CASE
					    WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
					    WHEN tca.categoryname = 'MONEY' THEN m.money_name
					    ELSE NULL
					    END AS trade_artyicle_name,
					    CASE
				     WHEN
				      tc.id_article = 5
				      AND
				      tc.id_tradecategory = 1
				        THEN
				          (
				           SELECT
				            1
				            )
				       WHEN
				      tc.id_article = 2
				       AND
				      tc.id_tradecategory = 1
				        THEN
				          (
				           SELECT
				            2
				            )
				              WHEN
				      tc.id_article = 1
				       AND
				      tc.id_tradecategory = 1
				        THEN
				          (
				           SELECT
				            3
				            )
				
				                  WHEN
				      tc.id_article = 4
				       AND
				      tc.id_tradecategory = 1
				        THEN
				          (
				           SELECT
				            4
				            )
				
				                  WHEN
				      tc.id_article = 3
				       AND
				      tc.id_tradecategory = 1
				
				        THEN
				          (
				           SELECT
				            5
				            )
				 WHEN
				      tc.id_article = 1
				       AND
				      tc.id_tradecategory = 4
				        THEN
				          (
				           SELECT
				            6
				            )
				
				                  WHEN
				      tc.id_article = 2
				       AND
				      tc.id_tradecategory = 4
				        THEN
				          (
				           SELECT
				            7
				            )
				    END AS number
				FROM	ds_unit AS u
				INNER JOIN ds_unithasclass AS uhc ON (uhc.id_unit = u.id_unit)
				LEFT JOIN ds_unitcanbebuildbyrace AS ucbbr ON (ucbbr.id_unit = u.id_unit)
				LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = u.id_localizedname AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
				INNER JOIN ds_unit_reincarnation_cost AS tuc ON (tuc.id_unit = u.id_unit)
				INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tuc.id_tradecourse)
				INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
				LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
				LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article)
			WHERE";

		if ($race != 'all')
		{
			$sql .= " $where
				AND ";
		}

		$sql .= " tuc.id_world = $worldID
				$whereTrade ";

		if ($class != 'all')
		{
			assert($class >= 0);
			$sql .= " AND
				uhc.id_unitclass = $class";
		}

		$sql .= " ORDER BY ucbbr.id_race, name, number";
/*echo "<xmp>";
echo $sql;
echo "</xmp>";
exit;*/
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}
}

?>