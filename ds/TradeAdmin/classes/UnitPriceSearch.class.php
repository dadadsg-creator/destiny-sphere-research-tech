<?php

class UnitPriceSearch
{
	static public function getUnitRaces($DBID)
	{
		assert(is_int($DBID) && $DBID>=0);		
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			SELECT
				id_race as id,
				racename as name
			FROM
				ds_race
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		$res[] = array('id' => '4', 'name' => 'QUEST');
		
		return $res;
	}
	
	static public function getUnitClasses($DBID)
	{
		assert(is_int($DBID) && $DBID>=0);		
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			SELECT
				id_unitclass as id,
				classname as name
			FROM
				ds_unitclass
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function getUnitsByRaceAndClass($race, $class, $DBID, $worldID)
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
					    END AS trade_artyicle_name,
					    tuc.add_date
				FROM	ds_unit AS u
				INNER JOIN ds_unithasclass AS uhc ON (uhc.id_unit = u.id_unit)
				LEFT JOIN ds_unitcanbebuildbyrace AS ucbbr ON (ucbbr.id_unit = u.id_unit)
				LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = u.id_localizedname AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
				INNER JOIN ds_trade_units_cours_data AS tuc ON (tuc.id_unit = u.id_unit)
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
				AND
				tuc.is_current IS TRUE ";
		
		if ($class != 'all')
		{	
		assert($class >= 0);			
		$sql .= " AND
				uhc.id_unitclass = $class";
		}
		
		$sql .= " ORDER BY u.id_unit, tc.id_tradecourse";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function updateUnitCourse($id_unit, $id_tradecategory, $id_article, $course, $DBID, $worldID)
	{
		assert(is_int($id_unit) && $id_unit > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_float($course) && $course > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "SELECT insert_unit_trade_course($worldID, $id_unit, $id_tradecategory, $id_article, $course) AS res";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0]['res'];
	}
	
	static public function getUnitCourseHistoryByID($unitID, $DBID, $worldID)
	{
		assert(is_int($unitID) && $unitID > 0);
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			SELECT
				CASE
		           	WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
		            WHEN tca.categoryname = 'MONEY' THEN m.money_name
		            ELSE NULL
		        END AS trade_name,
            	tu.add_date,
				tu.is_current,
				tu.value
			FROM
				ds_trade_units_cours_data as tu
			INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tu.id_tradecourse)
			INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
			LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
			LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article)	
			WHERE
				id_unit = $unitID
				AND
				id_world = $worldID
			ORDER BY add_date, tu.id_tradecourse
			";
		/*$sql = "
			SELECT
				COALESCE(ltl.text, u.unitname) as unit_name,
                CASE
		           	WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
		            WHEN tca.categoryname = 'MONEY' THEN m.money_name
		            ELSE NULL
		        END AS trade_name,
            	tu.add_date,
				tu.is_current,
				tu.value
			FROM
				ds_trade_units_cours_data as tu
		  LEFT JOIN ds_unit AS u ON (u.id_unit = tu.id_unit)
			LEFT JOIN ds_localizedtextlanguage AS ltl ON (
				ltl.id_localizedtext = u.id_localizedname
				AND
				ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
			INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tu.id_tradecourse)
			INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
			LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
			LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article)	
			WHERE
				tu.id_unit = $unitID
				AND
				id_world = $worldID
			ORDER BY add_date, tu.id_tradecourse";*/
//echo $sql;
//exit;			
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function initUnitTradeCourse($DBID, $worldID)
	{
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);

		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "	
			INSERT INTO ds_trade_units_cours_data
				(id_world, id_unit, value)
			SELECT
                $worldID AS id_world,
                u.id_unit,
                (
                	SELECT
                    	SUM(uha.value * tcd.value) AS stazis
                    FROM
                        ds_unithasattribute AS uha
                        INNER JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
                        INNER JOIN ds_attributename AS atn ON (a.id_attributename = atn.id_attributename)
                        INNER JOIN ds_attributetype AS att ON (a.id_attributetype = att.id_attributetype)
                        INNER JOIN ds_trade_course AS tc ON (tc.id_article = a.id_attributename)
                    	INNER JOIN ds_trade_course_data AS tcd ON (tcd.id_tradecourse = tc.id_tradecourse AND tcd.is_current = true)
                    WHERE
                        att.typename = 'PRODUCTION_COSTS'
                        AND
                    	atn.attributename != 'CONSTRUCTION_TIME'
                        AND                                        
                        uha.id_unit = u.id_unit
                        AND
                        tcd.id_world = $worldID
                        AND
                        tc.id_tradecategory = 1
                ) AS stazis
            FROM
            	ds_unit AS u
                INNER JOIN ds_unithasclass AS uhc ON (uhc.id_unit = u.id_unit)
                INNER JOIN ds_unitclass AS uc ON (uc.id_unitclass = uhc.id_unitclass)
        	";
				
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
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
	
	static public function getUnitCourseHistoryTitleByID($unitID, $DBID, $worldID)
	{
		assert(is_int($unitID) && $unitID > 0);
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
		SELECT
			DISTINCT tu.id_tradecourse,
                CASE
	            	WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
	            	WHEN tca.categoryname = 'MONEY' THEN m.money_name
	            	ELSE NULL
	            END AS trade_name
            FROM
				ds_trade_units_cours_data as tu
			INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tu.id_tradecourse)
			INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
			LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
			LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article)	
			WHERE
				id_unit = $unitID
				AND
				id_world = $worldID
			ORDER BY tu.id_tradecourse
			";
		
//echo $sql;
//exit;			
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
}

?>