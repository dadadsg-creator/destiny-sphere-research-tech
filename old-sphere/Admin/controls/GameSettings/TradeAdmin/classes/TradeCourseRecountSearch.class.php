<?php

class TradeCourseRecountSearch
{
	/**
	 * Enter description here...
	 *
	 * @var DS_Common;
	 */
	static public $common;

	static public function GetAllWorldsList()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$out = array();

		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			foreach($res as $k=>$v)
			{
				$v['dbid'] = $dbid;
				$out[] = $v;
			}
		}
		return $out;
	}

	static public function GetLastRecountDate($worldID)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "
			SELECT 
				max(tcr.add_date) AS start_date
			FROM 
				ds_trade_course_recount AS tcr
			WHERE 
				tcr.id_world = ".$worldID;
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		if(count($res) == 0 || empty($res[0]["start_date"]))
		{
			$time = 1000;
		}else{
			$time = $res[0]["start_date"];
		}
		return GB_TypeFactory::CreateBase('GB_Date', $time);
	}
	
	static public function InsertRecountDate($worldID)
	{
		assert(is_int($worldID) && $worldID > 0);	
		assert(self::$common instanceof DS_Common);
		
		$sql = "INSERT INTO ds_trade_course_recount (id_world) VALUES (".$worldID.")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));				
	}

	static public function GetResourceTrades($worldID, GB_Date $startDate, $resourseId)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(self::$common instanceof DS_Common);
		assert(is_int($resourseId) && $resourseId > 0);
		
			$sql = "
			SELECT
	            th.id_from_category,
                th.id_from_article,
                r.id_attributename AS id_good,
            	tc.id_tradecategory,
            	tc.id_article,
            	from_ammount,
                to_ammount,
                th.kv,
                th.kn,
                th.delta,
                th.weight_nano
        	FROM
	            ds_resource AS r
            	INNER JOIN ds_trade_course AS tc ON (1 = 1)
            	LEFT JOIN ds_trade_history AS th ON
            	(
	                th.id_from_article = r.id_attributename
                	AND
                	th.id_to_category = tc.id_tradecategory
                	AND
                	th.id_to_article = tc.id_article
            	)
        	WHERE
	            th.id_from_category = 1
                AND 
                th.id_from_article = ".$resourseId."
            	AND
            	r.trade_resources = true
            	AND
            	th.world_id = ".$worldID."
            	AND
            	th.end_date > '".$startDate->getPgFormat()."'";
			
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res;
	}
	
	static public function GetUnitTrades($worldID, GB_Date $startDate, $id_unit)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($id_unit) && $id_unit > 0);
		
		assert(self::$common instanceof DS_Common);

		$sql = "
			SELECT
	            th.id_from_category,
                th.id_from_article,
                u.id_unit AS id_good,
            	tc.id_tradecategory,
            	tc.id_article,
            	from_ammount,
                to_ammount,
                th.kv,
                th.kn,
                th.delta,
                th.weight_nano
            FROM
                ds_unit AS u
                INNER JOIN ds_trade_course AS tc ON (1 = 1)
                LEFT JOIN ds_trade_history AS th ON
                (
                    th.id_from_article = u.id_unit
                    AND
                    th.id_to_category = tc.id_tradecategory
                    AND
                    th.id_to_article = tc.id_article
                )
            WHERE
                th.id_from_category = 2
                AND 
                th.id_from_article = ".$id_unit."
            	AND
            	th.world_id = ".$worldID."
            	AND
            	th.end_date > '".$startDate->getPgFormat()."'";
		
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function GetArtefactTrades($worldID, GB_Date $startDate, $id_art)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($id_art) && $id_art > 0);
		
		assert(self::$common instanceof DS_Common);
		
		$sql = "
			SELECT
	            th.id_from_category,
                th.id_from_article,
                a.id_artefact AS id_good,
            	tc.id_tradecategory,
            	tc.id_article,
            	from_ammount,
                to_ammount,
                th.kv,
                th.kn,
                th.delta,
                th.weight_nano
            FROM
                ds_artefact AS a
                INNER JOIN ds_trade_course AS tc ON (1 = 1)
                LEFT JOIN ds_trade_history AS th ON
                (
                    th.id_from_article = a.id_artefact
                    AND
                    th.id_to_category = tc.id_tradecategory
                    AND
                    th.id_to_article = tc.id_article
                )
            WHERE
                th.id_from_category = 3
                 AND 
                th.id_from_article = ".$id_art."
                 AND
            	th.world_id = ".$worldID."
            	 AND
            	th.end_date > '".$startDate->getPgFormat()."'";

/*echo "<br><pre>";
print_r($sql);
echo "<br>";
exit;*/		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}	
	
	static public function GetResuorceName($articleID)
	{
		assert(is_int($articleID) && $articleID > 0);
		assert(self::$common instanceof DS_Common);


		$sql = "
				SELECT
		            l.text
		        FROM
		            ds_attributename a
		        INNER JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = a.id_localizedname)
		        WHERE
		            id_attributename = ".$articleID."
		             AND
		            l.id_language = ".constant('DEFAULT_LANGUAGE')."
					";

		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res[0]['text'];
	}

	static public function GetUnitName($articleID)
	{
		assert(is_int($articleID) && $articleID > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "
				SELECT
		            l.text
		        FROM
		            ds_unit u
		        INNER JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = u.id_localizedname)
		        WHERE
		            id_unit = ".$articleID."
		             AND
		            l.id_language = ".constant('DEFAULT_LANGUAGE')."
					";

		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res[0]['text'];
	}

	static public function GetArtefactName($articleID)
	{
		assert(is_int($articleID) && $articleID > 0);
		assert(self::$common instanceof DS_Common);

		$sql = "
				SELECT
		            l.text
		        FROM
		            ds_artefact a
		        INNER JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = a.id_localizedname)
		        WHERE
		            id_artefact = ".$articleID."
		             AND
		            l.id_language = ".constant('DEFAULT_LANGUAGE')."
					";

		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));

		return $res[0]['text'];
	}
	
	static public function InsertResourceCourse($worldID, $resourceID, $categoryID, $articleID, $course)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($resourceID) && $resourceID > 0);
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
//		assert(is_float($course) && $course > 0);
		assert(self::$common instanceof DS_Common);
		
		$sql = "SELECT insert_resource_trade_course($worldID, $resourceID, $categoryID, $articleID, ".number_format($course, 5, ".", "").")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));				
	}
	
	static public function InsertUnitsCourse($worldID, $resourceID, $categoryID, $articleID, $course)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($resourceID) && $resourceID > 0); 
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
//		assert(is_float($course) && $course > 0);
		assert(self::$common instanceof DS_Common);		
		
		$sql = "SELECT insert_unit_trade_course($worldID, $resourceID, $categoryID, $articleID, ".number_format($course, 5, ".", "").")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));				
	}
	
	static public function InsertArtefactCourse($worldID, $resourceID, $categoryID, $articleID, $course)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($resourceID) && $resourceID > 0); 
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
//		assert(is_float($course) && $course > 0);
		assert(self::$common instanceof DS_Common);		
		
		$sql = "SELECT insert_artefact_trade_course($worldID, $resourceID, $categoryID, $articleID, ".number_format($course, 5, ".", "").")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));				
	}
	
	static public function GetWorldNameById($worldId)
	{
		assert(is_int($worldId) && $worldId >0);
		assert(self::$common instanceof DS_Common);

		//Получаем имя мира по его id
		$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = ".$worldId;

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res[0]['worldname'];
	}	
	
	static public function GetAllResourses()
	{
		assert(self::$common instanceof DS_Common);

		//Получаем id  всех ресов
		$sql = "
				SELECT
					r.id_attributename, 
					an.attributename
				FROM 
					ds_resource r
               	LEFT JOIN ds_attributename an ON (an.id_attributename = r.id_attributename)
                WHERE 
                	r.trade_resources = 't' ";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
		
	static public function GetAllUnits()
	{
		assert(self::$common instanceof DS_Common);

		//Получаем id  всех боевых юнитов
		$sql = "
				SELECT
					u.id_unit
				FROM 
					ds_unit u 
               	WHERE
               		u.id_unitcontainertype = 1";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
		
	static public function GetAllArts()
	{
		assert(self::$common instanceof DS_Common);

		//Получаем id  всех боевых юнитов
		$sql = "
				SELECT
					a.id_artefact
				FROM 
					ds_artefact a";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function CurrentCourseForResource($resourceID, $worldID)
	{
		assert(is_int($resourceID) && $resourceID >0);
		assert(is_int($worldID) && $worldID > 0);
		
		assert(self::$common instanceof DS_Common);

		//Получаем курс ресурса за нану
		$sql = "
				SELECT 
                    trcd.value
                FROM
				    ds_trade_resorce_course_data AS trcd
				WHERE
					trcd.id_resource = ".$resourceID."
					 AND
					trcd.is_current = true
					 AND
					trcd.id_world = ".$worldID."
					 AND
					trcd.id_tradecourse = 2";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);
		
		return (float)$res[0]['value'];
	}
	
	static public function CurrentCourseForUnit($unitID, $worldID)
	{
		assert(is_int($unitID) && $unitID >0);
		assert(is_int($worldID) && $worldID > 0);
		
		assert(self::$common instanceof DS_Common);

		//Получаем курс ресурса за нану
		$sql = "
				SELECT 
                	tucd.value
                FROM
                	ds_trade_units_cours_data AS tucd
                WHERE
                	 tucd.id_unit = ".$unitID."
	                AND
	                tucd.is_current = true
	                 AND
	                tucd.id_world = ".$worldID."
	                 AND
	                tucd.id_tradecourse = 2";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);
		
		return (float)$res[0]['value'];
	}
	
	static public function CurrentCourseForArt($id_art, $worldID)
	{
		assert(is_int($id_art) && $id_art >0);
		assert(is_int($worldID) && $worldID > 0);
		
		assert(self::$common instanceof DS_Common);

		//Получаем курс ресурса за нану
		$sql = "
				SELECT 
                	tacd.value
                FROM
                	ds_trade_artefact_course_data AS tacd
                WHERE
                	  tacd.id_artefact = ".$id_art."
	                AND
	                tacd.is_current = true
	                 AND
	                tacd.id_world = ".$worldID."
	                 AND
	                tacd.id_tradecourse = 2
	             -- LIMIT 10";

		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		//assert(count($res) == 1);
		
		if (count($res) > 0) {
			assert(count($res) == 1);
			return (float)$res[0]['value'];
		} else {
			return false;
		}
		
	}
}

?>