<?php

class ArtefactPriceSearch
{
	static public function getArtefactScopes($DBID)
	{
		assert(is_int($DBID) && $DBID>=0);		
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			SELECT
    			ars.id_artefactscope as id,
    			COALESCE(ltl.text, ars.scopename) as name,
    			ars.scopename
			FROM
    			ds_artefactscope AS ars
			    LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ars.id_localizedscope AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
			ORDER BY ars.id_artefactscope
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function getArtefactSizes($DBID)
	{
		assert(is_int($DBID) && $DBID>=0);		
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			SELECT
    			ars.id_artefactprobability as id,
    			COALESCE(ltl.text, ars.prefix) as name,
    			ars.prefix
			FROM
    			ds_artefactprobability AS ars
    			LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ars.id_localizedprefix AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
			ORDER BY ars.id_artefactprobability
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function getArtefactsByScopeAndSize($scope, $size, $DBID, $worldID)
	{
		assert($scope > 0 || $scope == 'all');
		assert($size || $size == 'all');
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		if 	($scope	== 'all' AND $size == 'all')
			{
				$where = "";
			}else{
				if ($scope != 'all' AND $size != 'all')
				{
					$where = " WHERE
						id_artefactscope = $scope
						AND
						id_artefactprobability = $size ";
				}
				if ($scope == 'all')	
				{	
					$where = "WHERE id_artefactprobability = $size ";
				}
				if ($size == 'all')
				{
					$where = "WHERE id_artefactscope = $scope ";
				}
			}
		
		$sql = "
		    SELECT
				a.id_artefact,
			    COALESCE(ltl.text, a.artefactname) as name,
			    a.artefactpower as power,
			    COALESCE(tac.value, 0) as course,
			    tc.id_tradecategory,
    			tc.id_article,
			    tca.categoryname AS tradecourse_name,
			    CASE
			    WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
			    WHEN tca.categoryname = 'MONEY' THEN m.money_name
			    ELSE NULL
			    END AS trade_artyicle_name,
			    add_date
			FROM
				ds_artefact as a
				LEFT JOIN ds_localizedtextlanguage AS ltl ON (
							ltl.id_localizedtext = a.id_localizedname 
							AND 
							ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
				INNER JOIN ds_trade_artefact_course_data AS tac ON (
							tac.id_artefact = a.id_artefact 
							AND 
							tac.id_world = $worldID 
							AND 
							tac.is_current)
				INNER JOIN ds_trade_course as tc ON (
						    tc.id_tradecourse = tac.id_tradecourse)
				INNER JOIN ds_trade_category AS tca ON (
						    tca.id_tradecategory = tc.id_tradecategory)
				LEFT JOIN ds_attributename AS an ON (
						    an.id_attributename = tc.id_article)
				LEFT JOIN ds_money AS m ON (
						    m.id_money = tc.id_article)
				$where
				ORDER BY name, tc.id_tradecourse			";
		
//exit ($sql);
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
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
	
	static public function getRithTitle($DBID, $worldID, $scope, $size)
	{
		assert($scope > 0 || $scope == 'all');
		assert($size || $size == 'all');
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		if 	($scope	== 'all' AND $size == 'all')
			{
				$where = "";
			}else{
				if ($scope != 'all' AND $size != 'all')
				{
					$where = " WHERE
						id_artefactscope = $scope
						AND
						id_artefactprobability = $size ";
				}
				if ($scope == 'all')	
				{	
					$where = "WHERE id_artefactprobability = $size ";
				}
				if ($size == 'all')
				{
					$where = "WHERE id_artefactscope = $scope ";
				}
			}
		
		$sql = "
		    SELECT
			COALESCE(ltl.text, a.artefactname) as name,
			a.id_artefact
			 FROM ds_artefact AS a
			LEFT JOIN ds_localizedtextlanguage AS ltl ON (
							ltl.id_localizedtext = a.id_localizedname
				AND
				ltl.id_language = ".constant("DEFAULT_LANGUAGE").")
				$where
			ORDER BY name";
		
//exit ($sql);
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}
	
	static public function getArtefactIDByName($artName, $DBID)
	{
		assert(!empty($artName));
		assert(is_int($DBID) && $DBID >= 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			SELECT
				id_artefact
			FROM
				ds_artefact
			WHERE
				artefactname = $artName
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) == 0)
		{
			return 0;
		}
		
		return (int)$res[0]['id_artefact'];
	}
	
	static public function updateArtefactCourse($id_artefact, $id_tradecategory, $id_article, $course, $DBID, $worldID)
	{
		assert(is_int($id_artefact) && $id_artefact > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_float($course) && $course > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "SELECT insert_artefact_trade_course($worldID, $id_artefact, $id_tradecategory, $id_article, $course) AS res";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0]['res'];
	}
	
	static public function updateArtefactPower($artefactID, $power, $DBID, $worldID)
	{
		assert(is_int($artefactID) && $artefactID > 0);
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_float($power) && $power > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			UPDATE
				ds_artefact
			SET
				artefactpower = $power
			WHERE
				id_artefact = $artefactID
			";
		
		$res = $common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		return true;
	}
	
	
	static public function getArtefactCourseHistoryByID($artefactID, $DBID, $worldID)
	{
		assert(is_int($artefactID) && $artefactID > 0);
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			SELECT
				COALESCE(ltl.text, a.artefactname) as artefact_name,
				CASE
	            	WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
	            	WHEN tca.categoryname = 'MONEY' THEN m.money_name
	            	ELSE NULL
	            END AS trade_name,
                tac.add_date,
				tac.is_current,
				tac.value
				FROM
            ds_trade_artefact_course_data as tac
	       LEFT JOIN ds_artefact AS a ON (a.id_artefact = tac.id_artefact)
 			LEFT JOIN ds_localizedtextlanguage AS ltl ON (
				ltl.id_localizedtext = a.id_localizedname
				AND
				ltl.id_language = 3)
			INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tac.id_tradecourse)
			INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
			LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
			LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article)	
			WHERE
				tac.id_artefact = $artefactID
				AND
				id_world = $worldID
			ORDER BY add_date, tac.id_tradecourse
			";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function getArtefactCourseHistoryTitleByID($artefactID, $DBID, $worldID)
	{
		assert(is_int($artefactID) && $artefactID > 0);
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "SELECT
			DISTINCT tac.id_tradecourse,
                CASE
	            	WHEN tca.categoryname = 'RESOURCE' THEN an.attributename
	            	WHEN tca.categoryname = 'MONEY' THEN m.money_name
	            	ELSE NULL
	            END AS trade_name
            FROM
				ds_trade_artefact_course_data as tac
			INNER JOIN ds_trade_course as tc ON (tc.id_tradecourse = tac.id_tradecourse)
			INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
			LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article)
			LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article)	
			WHERE
				id_artefact = $artefactID
				AND
				id_world = $worldID
			ORDER BY tac.id_tradecourse
			";
///echo $sql;
//exit;		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
}
?>