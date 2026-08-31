<?php
class SU_TradeGraphicsSearch
{
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
	
	static public function GetAllResources($DBID)
	{
		assert(is_int($DBID) && $DBID >= 0);

		$common = DS_Common::CreateForCurrentDB($DBID);

		$sql = "SELECT
      				r.id_attributename AS id,
      				an.attributename AS name
				FROM
    				ds_resource AS r
    			INNER JOIN ds_attributename AS an ON (an.id_attributename = r.id_attributename)
				WHERE 
					r.trade_resources = true";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}
	
	static public function GetUnitIdByName($DBID, $unitName)
	{
		assert(is_int($DBID) && $DBID >= 0);

		$common = DS_Common::CreateForCurrentDB($DBID);

		$sql = "SELECT 
					u.id_unit 
				FROM 
					ds_unit u
				LEFT JOIN ds_localizedtextlanguage ltl ON (ltl.id_localizedtext = u.id_localizedname)
				WHERE 
					ltl.text = '".pg_escape_string($unitName)."'";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if (count($res > 0)) {
			return $res[0]['id_unit'];
		} else {
			return false;
		}
	}
	
	static public function GetData($DBID, $worldId, $idArticle, $idCategory, $idArticleFor, $idCategoryFor, $dateFrom, $dateTo, $flag, $detalization)
	{
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldId) && $worldId >= 0);
		assert(is_int($idArticle) && $idArticle > 0);
		//assert(is_int($idCategory) && $idCategory > 0);
		assert(is_int($idArticleFor) && $idArticleFor > 0);
		assert(is_int($idCategoryFor) && $idCategoryFor > 0);
		assert(is_int($idCategoryFor) && $idCategoryFor > 0);

		$common = DS_Common::CreateForCurrentDB($DBID);

		if ($flag == 0) {
			$where = ' hist.id_from_category IN ('.$idCategory.')';
		} else {
			$where = ' hist.id_from_category = '.$idCategory.' ';
		}
//SU_DebugTools::LogAll($detalization, 'Annita.log');		

		$selectData = "
							extract(year from start_date) as year, ";
		$orderBy =  "  year";
		if ($detalization != 'year') {
			$selectData .= "
	                        extract(month from start_date) as month, ";
			$orderBy .= " , month ";
			if ($detalization != 'month') {
				$selectData .= "
	                        extract(day from start_date) as day, ";
				$orderBy .= " , day ";
				if ($detalization != 'day') {
					$selectData .= "
	                        extract(hour from start_date) as hour, ";
					$orderBy .= " , hour ";
					if ($detalization != 'hour') {
						$selectData .= "
	                        extract(minute from start_date) as minute, ";
						$orderBy .= " , minute ";

					}
				}
			}
		}
		
		$sql = "
		SELECT
                       ".$selectData."
                        SUM(hist.to_ammount)/ sum(hist.from_ammount) as course,
                        count(hist.start_date) as count,
                        CASE
                    WHEN hist.id_from_category = 1
                        THEN
                        (
                        SELECT a.value FROM ( SELECT *            
                        FROM ds_trade_resorce_course_data as trcd
                        WHERE     trcd.id_resource = hist.id_from_article
                                AND trcd.id_tradecourse = (
                                    SELECT t.id_tradecourse
                                    FROM ds_trade_course as t
                                    WHERE t.id_tradecategory = hist.id_to_category
                                          AND
                                          t.id_article = hist.id_to_article)
                                AND
                                   trcd.add_date < max(hist.start_date)
                                AND
                                trcd.id_world = ".$worldId."
                                ORDER BY trcd.add_date DESC
                                 ) as a
                                LIMIT 1
                       )
                            
                    WHEN hist.id_from_category = 2
                        THEN
                        (
                        SELECT a.value FROM ( SELECT *
                        FROM ds_trade_units_cours_data as trucd
                        WHERE     trucd.id_unit = hist.id_from_article
                                AND
                                trucd.id_tradecourse = (
                                    SELECT t.id_tradecourse
                                    FROM ds_trade_course as t
                                    WHERE     t.id_tradecategory = hist.id_to_category
                                            AND
                                            t.id_article = hist.id_to_article)
                                AND
                            trucd.add_date < max(hist.start_date)
                                AND
                                trucd.id_world = ".$worldId."
                                ORDER BY trucd.add_date DESC
                             ) as a
                                LIMIT 1
                    )
                                        
                    WHEN hist.id_from_category = 3
                        THEN
                        (
                            SELECT a.value FROM ( SELECT *
                        FROM ds_trade_artefact_course_data as tracd
                        WHERE tracd.id_artefact = hist.id_from_article
                                AND tracd.id_tradecourse = (
                                    SELECT t.id_tradecourse
                                    FROM ds_trade_course as t
                                    WHERE     t.id_tradecategory = hist.id_to_category
                                            AND
                                            t.id_article = hist.id_to_article)
                                AND
                                 tracd.add_date < max(hist.start_date)
                                AND
                                tracd.id_world = ".$worldId."
                                ORDER BY tracd.add_date DESC
			                    ) as a
			                    LIMIT 1
			               )				
				END AS current_cource,
                (SELECT 
                    min_price_delta 
                FROM 
                    ds_trade_type_data ttd
                WHERE
                    ttd.id_tradetype = hist.id_trade_type
                    AND
                    ttd.add_date < max(hist.start_date)
                ORDER BY ttd.add_date DESC
                LIMIT 1) as min_price,
                (SELECT 
                    max_price_delta 
                FROM 
                    ds_trade_type_data ttd
                WHERE
                    ttd.id_tradetype = hist.id_trade_type
                    AND
                    ttd.add_date < max(hist.start_date)
                ORDER BY ttd.add_date DESC
                LIMIT 1) as max_price
            FROM
                ds_trade_history AS hist
            WHERE 
                -- hist.start_date > to_timestamp(1216670400) AND hist.end_date < to_timestamp(1217361599)
                -- AND
                
                hist.start_date > '".GB_TypeFactory::CreateBase('GB_Date', $dateFrom)->getPgFormat()."' AND hist.end_date < '".GB_TypeFactory::CreateBase('GB_Date', $dateTo)->getPgFormat()."'
                AND 
                hist.world_id = ".$worldId."                                
           		 AND
                hist.id_from_article = ".$idArticle."
                 AND
                ".$where."
                 AND
                hist.id_to_category = ".$idCategoryFor."
                 AND
                hist.id_to_article = ".$idArticleFor."
                GROUP BY hist.id_from_category,
          hist.id_from_article, hist.id_to_category, hist.id_to_article, hist.id_trade_type, $orderBy
		  ORDER BY ".$orderBy;
	

		/*$sql = "SELECT
                        hist.start_date,
                        hist.to_ammount/hist.from_ammount as cource
            
            FROM
                ds_trade_history AS hist
                INNER JOIN ds_trade_category AS c_from ON (c_from.id_tradecategory = hist.id_from_category)
                INNER JOIN ds_trade_category AS c_to ON (c_to.id_tradecategory = hist.id_to_category)
            WHERE 
                -- hist.start_date > to_timestamp(1216670400) AND hist.end_date < to_timestamp(1217361599)
                -- AND
                hist.world_id = ".$worldId."                                
           		 AND
                hist.id_from_category = ".$idCategory."
                 AND
                hist.id_from_article = ".$idArticle."
                 AND
                hist.id_to_category = ".$idCategoryFor."
                 AND
                hist.id_to_article = ".$idArticleFor;*/
		

		$res = $common->getResultArray("", $sql, "", "", "");
//SU_DebugTools::LogAll($sql, 'Annita.log');		
/*echo "<pre>";
print_r($sql);
exit;*/
		assert(empty($common->errormsg));

		return $res;
	}
	
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
	
	static public function getArtefactIdBySizeAndScope($DBID, $artScopes, $artSizes)
	{
		assert(is_int($DBID) && $DBID>=0);

		$common = DS_Common::CreateForCurentDB($DBID);

		$sql = "
			 SELECT 
			 	id_artefact
			 FROM 
			 	ds_artefact
			 WHERE
  				id_artefactscope = ".$artScopes." 
  				 AND 
  				id_artefactprobability = ".$artSizes."
			";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}
}
?>