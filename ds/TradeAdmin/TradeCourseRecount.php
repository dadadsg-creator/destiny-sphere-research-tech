<?php

require_once "../Main.php";

class TradeCourseRecountExec
{	
	
	static private $worldID;

	/**	 
	 *
	 * @var GB_Date
	 */
	static private $startDate;
	
	static private $courseGC;
	
	static public function Exec($DBID, $worldID)
	{
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		TradeCourseRecountSearch::$common = DS_Common::CreateForCurrentDB($DBID);
		
		self::$worldID = $worldID;
		
		self::$startDate = TradeCourseRecountSearch::GetLastRecountDate(self::$worldID);
		
		self::$courseGC = constant('GC_COURSE');
		
		self::_out("START RECOUNT TRADE COURSES DBID = $DBID WORLD_ID = $worldID");
		
		TradeCourseRecountSearch::$common->startTrans();
		
		{
			$tradesData = TradeCourseRecountSearch::GetResourceTrades(self::$worldID, self::$startDate);
			self::_recountCourse($tradesData, 'InsertResourceCourse', 'RESOURCES');
		
			$tradesData = TradeCourseRecountSearch::GetUnitTrades(self::$worldID, self::$startDate);
			self::_recountCourse($tradesData, 'InsertUnitsCourse', 'UNITS');
			
			$tradesData = TradeCourseRecountSearch::GetArtefactTrades(self::$worldID, self::$startDate);
			self::_recountCourse($tradesData, 'InsertArtefactCourse', 'ARTEFACTS');
		
			TradeCourseRecountSearch::InsertRecountDate(self::$worldID);
		}
		
		TradeCourseRecountSearch::$common->closeTrans('COMMIT');
		
		self::_out("RECOUNT COMPLIT");
	}	
	
		
	static private function _recountCourse($tradesData, $insertMetodName, $outName)
	{
		assert(is_int(self::$worldID) && self::$worldID > 0);
		assert(self::$startDate instanceof GB_Date);
		assert(is_string($insertMetodName) && !empty($insertMetodName));
		assert(is_string($outName) && !empty($outName));
		assert(is_array($tradesData));
		
		self::_out("Recount courses for [$outName] BEGIN");
		
		if(count($tradesData) == 0)
		{
			return;
		}
		
		$preCourseData = array();		
		foreach($tradesData as $k=>$tradeData)
		{
			$resourceID = (int)$tradeData['id_good'];
			assert($resourceID > 0);
			
			$tradeCategoryID = (int)$tradeData['id_tradecategory'];
			assert($tradeCategoryID > 0);
			
			$articleID = (int)$tradeData['id_article'];
			assert($articleID > 0);
			
			$summFrom = (float)$tradeData['sum_from_amount'];
			assert($summFrom > 0);
			
			$summTo = (float)$tradeData['sum_to_amount'];
			assert($summTo > 0);			
			
			if($tradeCategoryID == 4 && $articleID == 2)
			{
				$preCourseData[$resourceID][$tradeCategoryID][$articleID]['FROM'] += $summFrom;
				$preCourseData[$resourceID][$tradeCategoryID][$articleID]['TO'] += $summTo;					
			} else {
				$preCourseData[$resourceID][$tradeCategoryID][$articleID] = array(
					"FROM" => $summFrom,
					"TO" => $summTo,
				);
			}
		}		
		
		foreach($preCourseData AS $resourceID => $resourceData)
		{
			foreach($resourceData AS $tradeCategoryID => $articleData)
			{
				foreach($articleData AS $articleID => $courseData)
				{
					$course = $courseData['TO'] / $courseData['FROM'];
					
					TradeCourseRecountSearch::$insertMetodName(self::$worldID, $resourceID, $tradeCategoryID, $articleID, $course);
					
					if($tradeCategoryID == 4 && $articleID == 1)
					{
						$course = $course / self::$courseGC;
						
						TradeCourseRecountSearch::$insertMetodName(self::$worldID, $resourceID, $tradeCategoryID, 2, $course);			
					}
				}
			}			
		}		
		
		self::_out("Recount courses for [$outName] END");
		
	}
	
	static private function _out($out)
	{
		echo SU_DebugTools::GetInternalDate()." - ".$out."\n";
	}
}

class TradeCourseRecountSearch
{
	/**
	 * Enter description here...
	 *
	 * @var DS_Common;
	 */
	static public $common;
	
	static public function GetLastRecountDate($worldID)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(self::$common instanceof DS_Common);
		
		$sql = "
			SELECT max(tcr.add_date) AS start_date
			FROM ds_trade_course_recount AS tcr
			WHERE tcr.id_world = ".$worldID;
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		if(count($res) == 0)
		{
			$time = 1000;
		}else{
			$time = $res[0]["start_date"];
		}
		
		return GB_TypeFactory::CreateBase('GB_Date', $time);
	}
	
	static public function GetResourceTrades($worldID, GB_Date $endDate)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(self::$common instanceof DS_Common);
		
		$sql = "
			SELECT
	            r.id_attributename AS id_good,
            	tc.id_tradecategory,
            	tc.id_article,
            	sum(th.from_ammount) AS sum_from_amount,
            	sum(th.to_ammount) AS sum_to_amount
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
            	r.trade_resources = true
            	AND
            	th.world_id = ".$worldID."
            	AND
            	th.end_date > '".$endDate->getPgFormat()."'
        	GROUP BY
	            r.id_attributename,
            	tc.id_tradecategory,
            	tc.id_article";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function GetUnitTrades($worldID, GB_Date $endDate)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(self::$common instanceof DS_Common);
		
		$sql = "
			SELECT
	            r.id_unit AS id_good,
            	tc.id_tradecategory,
            	tc.id_article,
            	sum(th.from_ammount) AS sum_from_amount,
            	sum(th.to_ammount) AS sum_to_amount
        	FROM
	            ds_unit AS r
            	INNER JOIN ds_trade_course AS tc ON (1 = 1)
            	LEFT JOIN ds_trade_history AS th ON
            	(
	                th.id_from_article = r.id_unit
                	AND
                	th.id_to_category = tc.id_tradecategory
                	AND
                	th.id_to_article = tc.id_article
            	)
        	WHERE
	            th.id_from_category = 2
            	AND
            	th.world_id = ".$worldID."
            	AND
            	th.end_date > '".$endDate->getPgFormat()."'
        	GROUP BY
	            r.id_unit,
            	tc.id_tradecategory,
            	tc.id_article";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function GetArtefactTrades($worldID, GB_Date $endDate)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(self::$common instanceof DS_Common);
		
		$sql = "
			SELECT
	            r.id_artefact AS id_good,
            	tc.id_tradecategory,
            	tc.id_article,
            	sum(th.from_ammount) AS sum_from_amount,
            	sum(th.to_ammount) AS sum_to_amount
        	FROM
	            ds_artefact AS r
            	INNER JOIN ds_trade_course AS tc ON (1 = 1)
            	LEFT JOIN ds_trade_history AS th ON
            	(
	                th.id_from_article = r.id_artefact
                	AND
                	th.id_to_category = tc.id_tradecategory
                	AND
                	th.id_to_article = tc.id_article
            	)
        	WHERE
	            th.id_from_category = 3
            	AND
            	th.world_id = ".$worldID."
            	AND
            	th.end_date > '".$endDate->getPgFormat()."'
        	GROUP BY
	            r.id_artefact,
            	tc.id_tradecategory,
            	tc.id_article";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function InsertResourceCourse($worldID, $resourceID, $categoryID, $articleID, $course)
	{
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($resourceID) && $resourceID > 0);
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		assert(is_float($course) && $course > 0);
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
		assert(is_float($course) && $course > 0);
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
		assert(is_float($course) && $course > 0);
		assert(self::$common instanceof DS_Common);		
		
		$sql = "SELECT insert_artefact_trade_course($worldID, $resourceID, $categoryID, $articleID, ".number_format($course, 5, ".", "").")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));				
	}
	
	static public function InsertRecountDate($worldID)
	{
		assert(is_int($worldID) && $worldID > 0);	
		assert(self::$common instanceof DS_Common);
		
		$sql = "INSERT INTO ds_trade_course_recount (id_world) VALUES (".$worldID.")";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));				
	}
}

echo "<xmp>";
TradeCourseRecountExec::Exec(0, 2);
echo "</xmp>";

?>