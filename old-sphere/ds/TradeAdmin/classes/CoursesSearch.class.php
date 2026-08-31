<?php

class CoursesSearch
{
	static public function GetCourses($DBID, $worldID)
	{
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "SELECT
					r.id_attributename,
                    an.attributename,
				    tc.id_article,
				    tc.id_tradecategory,
				    CASE
				        WHEN tca.categoryname = 'RESOURCE' THEN an2.attributename
				        WHEN tca.categoryname = 'MONEY' THEN m.money_name
				        ELSE NULL
				    END AS artyicle_name,
				    trcd.add_date,
				    trcd.value
				
				FROM
				    ds_resource AS r
				    INNER JOIN ds_attributename AS an ON (an.id_attributename = r.id_attributename)
				    INNER JOIN ds_trade_course AS tc ON (1 = 1)
				    INNER JOIN ds_trade_category AS tca ON (tca.id_tradecategory = tc.id_tradecategory)
				    LEFT JOIN ds_attributename AS an2 ON (an2.id_attributename = tc.id_tradecategory)
				    LEFT JOIN ds_money AS m ON (m.id_money = tc.id_tradecategory)
				    LEFT JOIN ds_trade_resorce_course_data AS trcd ON
				    (
				        trcd.id_resource = r.id_attributename
				        AND
				        trcd.id_tradecourse = tc.id_tradecourse
				        AND
				        trcd.is_current = true
				        AND
				        trcd.id_world = $worldID
				    )
				WHERE
				    r.trade_resources = true
				ORDER BY r.id_attributename, tc.id_tradecourse";	
			
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) > 0);

		return $res;
	}
	
	static public function GetTitle($DBID, $worldID)
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
			
			$table[] = array
			(
				'CATEGORY_NAME' => $categoryName,
				'CATEGORY_ID' => $categoryID,
				'TYPES' => $tmp
			);
		}
		return $table;
	}
	
	static public function SetCourse($DBID, $worldID, $resourceID, $id_article, $id_tradecategory, $tradeCourse)
	{
		assert(is_int($DBID) && $DBID>=0);
		assert(is_int($worldID) && $worldID>0);
		assert($resourceID>0);
		assert($id_article>0);
		assert($id_tradecategory>0);
		assert(is_float($tradeCourse) && $tradeCourse > 0);	
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "SELECT insert_resource_trade_course(
											$worldID,
							               $resourceID,
							               $id_tradecategory,
							               $id_article,
							               $tradeCourse)";
//echo $sql;
//exit;	

		$common->executeQuery($sql);	
		assert(empty($common->errormsg));		
	}
}

?>