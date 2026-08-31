<?php
echo "<input type='hidden name='id_building' value='".$data['id_race']."/".$building['id_building']."'>";

class BildingLocalizationSearch
{
	static public function GetRaces()
	{
		//assert(is_int($DBID) && $DBID>=0);		
		
		$common = DS_Common::CreateForCurentDB();
		
			$sql = "SELECT
               							r.id_race,
               							ltl.text
         							FROM
                						ds_localizedtext lt,
                						ds_localizedtextlanguage ltl,
    							     	ds_race r
         							WHERE
         							 lt.id_localizedtext = r.id_localizedname
         							AND
        							 lt.id_localizedtext = ltl.id_localizedtext
         							AND
                                     ltl.id_language = ".constant('DEFAULT_LANGUAGE')."
      							";

//exit($sql);		
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
			    add_date,
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
				ORDER BY name, number";
		
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
			$typeData = self::GetTypesByCategoryName($DBID, $categoryName);
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
	static public function GetArtefactNameByID($dbID, $id_artefact)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($id_artefact) && $id_artefact > 0);
		
		$common = DS_Common::CreateForCurentDB($dbID);
		
		$sql = "
			SELECT
			COALESCE(ltl.text, a.artefactname) as name
			 FROM ds_artefact AS a
			LEFT JOIN ds_localizedtextlanguage AS ltl ON (
							ltl.id_localizedtext = a.id_localizedname
				AND
				ltl.id_language = ".constant("DEFAULT_LANGUAGE").")
			WHERE
				a.id_artefact = $id_artefact
			";
	
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) == 0)
		{
			return 0;
		}
		
		return $res[0]['name'];
	}
	static public function GetWorldNameByID($dbID, $worldID)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldID) && $worldID >= 0);
		
		$common = DS_Common::CreateForCurentDB($dbID);
		
		$sql = "
				SELECT worldname
				FROM ds_world
				WHERE id_world = $worldID
			";
	
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) == 0)
		{
			return 0;
		}
		
		return $res[0]['worldname'];
	}
	static public function GetNameByArticleAndTradecategory($dbID, $worldID, $id_tradecategory, $id_article)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldID) && $worldID >= 0);
		assert($id_article>0);
		assert($id_tradecategory>0);

		$common = DS_Common::CreateForCurentDB($dbID);
		switch ($id_tradecategory)
		{
			case '4':
				$sql = "SELECT
			           mon.money_name as name
			         FROM
			           ds_money mon
			         WHERE
                		mon.id_money = $id_article";
				break;
			case '1':
				$sql = "SELECT
			            an.attributename as name
			          FROM
			            ds_attributename an
			         WHERE
                		an.id_attributename = $id_article";
				break;
		}
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if(count($res) == 0)
		{
			return 0;
		}

		return $res[0]['name'];
	}
		static public function GetTypesByCategoryName($DBID, $categoryName)
	{
		assert(is_int($DBID) && $DBID>=0);
		assert(!empty($categoryName));

		$common = DS_Common::CreateForCurentDB($DBID);

		switch($categoryName)
		{
			case 'RESOURCE':
				$sql = "SELECT
      						r.id_attributename AS id,
      						an.attributename AS name,
      						 CASE
					     WHEN
					      r.id_attributename = 5
					        THEN
					          (
					           SELECT
					            1
					            )
					       WHEN
					      r.id_attributename = 2
					        THEN
					          (
					           SELECT
					            2
					            )
					              WHEN
					     r.id_attributename = 1
					        THEN
					          (
					           SELECT
					            3
					            )
					
					                  WHEN
					      r.id_attributename = 4
					        THEN
					          (
					           SELECT
					            4
					            )
					
					                  WHEN
					      r.id_attributename = 3
					        THEN
					          (
					           SELECT
					            5
					            )
					 WHEN
					      r.id_attributename = 1
					        THEN
					          (
					           SELECT
					            6
					            )
					
					                  WHEN
					      r.id_attributename = 2
					        THEN
					          (
					           SELECT
					            7
					            )
					    END AS number

						FROM
    						ds_resource AS r
    						INNER JOIN ds_attributename AS an ON (an.id_attributename = r.id_attributename)
						WHERE r.trade_resources = true
						ORDER BY number";
				break;
			case 'UNIT':
				$sql = "SELECT
							uc.id_unitclass AS id,
							uc.classname AS name
						FROM ds_unitclass AS uc
						ORDER BY uc.id_unitclass";
				break;
			case 'ARTEFACT':
				$sql = "SELECT
     						e.id_artefactprobability AS id,
     						e.prefix AS name 
						FROM ds_artefactprobability AS e
						ORDER BY e.id_artefactprobability";
				break;
			case 'MONEY':
				$sql = "SELECT
     						m.id_money  AS id,
     						m.money_name AS name
						FROM
    						ds_money AS m
						ORDER BY m.id_money";
				break;
			default:
				ds_error("Unknown category name = ".$categoryName);
		}
		$res = $common->getResultArray ( "", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}
}
?>