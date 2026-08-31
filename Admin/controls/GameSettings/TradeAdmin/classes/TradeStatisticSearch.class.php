<?php
class TradeStatisticSearch
{
	static public function GetStat()
	{
		$user = $_SESSION['user'];
		$fromDate = $_SESSION['startDate'];
		$toDate = $_SESSION['endDate'];
		$dbID = $_SESSION["dbId"];
		$worldID = $_SESSION['worldID'];
		$scopes = $_SESSION['scopes'];
		$catecories = $_SESSION['catecories'];
		$catecoriesfor = $_SESSION['catecoriesfor'];
		$recources = $_SESSION['recources'];
		$recourcesfor = $_SESSION['recourcesfor'];
		$money = $_SESSION['money'];
		$class = $_SESSION['class'];
		$race = $_SESSION['race'];
		$unitName = $_SESSION['unitName'];
		$artName = $_SESSION['artName'];
		$artscope = $_SESSION['artscope'];
		$size = $_SESSION['size'];
	
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_array($scopes));
		assert(is_array($catecories));
		assert(is_array($catecoriesfor));
		assert(is_array($recources));
		assert(is_array($money));
		assert(is_array($race));
		assert(is_array($class));
		
		$whereWorld = '';
		$whereIDCategory = '';
		
		$whereWorld .= " hist.world_id = $worldID ";
		$common = DS_Common::CreateForCurrentDB($dbID);

		//Фильтр по скопам
		$whereScope = 'AND
						( ';
		$or = "";
		foreach ($scopes as $k => $scope)
		{
			$whereScope .= "$or
							hist.id_trade_scope = ".$scope." ";
			$or = " OR ";
		}
		$whereScope .= " )";
		
		if(!empty($user))
		{
			$where .= " AND 
							(u_from.username LIKE '$user'
							OR
							u_to.username LIKE '$user')";
		}
		
		if(!empty($fromDate))
		{
			$where .= " AND hist.start_date > to_timestamp($fromDate)";
		}

		if(!empty($toDate))
		{
			$where .= " AND hist.end_date < to_timestamp($toDate)";
		}

		$whrereSell = "";
		$or = "";
		
		//Фильтр по тому, что покупали
		foreach ($catecories as $k => $cat)
		{
			//Если это ресурсы
			if ($cat == '1')
			{

				foreach ($recources as $k => $res)
				{
					$whrereSell .= " $or
							 (t.id_from_category = 1  AND filtr1=$res)";
					$or = " OR ";
				}
			}

			//Если это юниты
			if ($cat == '2')
			{				
				//Если введено имя юнита
				if ($unitName != "")
				{
					$whrereSell .= " $or
								(t.goods_from = '$unitName')";
					$or = "OR";

				} else {
					
					$whrereSell .= "$or
						(   t.id_from_category = 2
						    AND ";
					//Где расса юнита
					$whereRace = "(";
					$or = "";

					foreach ($race as $k => $unitrace)
					{
						$whereRace .= "
									$or
									t.filtr1= '$unitrace' ";
						$or = "OR";
					}
					$whereRace .= ")";

					$whrereSell .= $whereRace;
					$whrereSell .= "  AND ";

					//Где класс юнита
					$whereClass = "(";
					$or = "";

					foreach ($class as $k => $unitclass)
					{
						$whereClass .= "
									$or
									t.filtr2= '$unitclass' ";
						$or = "OR";
					}
					$whereClass .= ")";

					$whrereSell .= $whereClass;
					
					$whrereSell	.= ")";
					$or = "OR";
				}
			}

			//Если это артефакты
			if ($cat == '3')
			{
							
				//Если введено имя арта
				if ($artName != "")
				{
					$whrereSell .= " $or
								(t.goods_from = '$artName')";
					$or = "OR";

				} else {
					
					$whrereSell .= "$or
							( t.id_from_category = 3 AND ";
				
				//Где скоп артефакта
				$whereArtscope = "(";
				$or = "";

				foreach ($artscope as $k => $s)
				{
					$whereArtscope .= "
									$or
									t.filtr1= '$s' ";
					$or = "OR";
				}
				$whereArtscope .= ")";

				$whrereSell .= $whereArtscope;
				$whrereSell .= "  AND ";

				//Где размер артефакта
				$whereSize = "(";
				$or = "";

				foreach ($size as $k => $ss)
				{
					$whereSize .= "
									$or
									t.filtr2= '$ss' ";
					$or = "OR";
				}
				$whereSize .= ")";

				$whrereSell .= $whereSize;

				$whrereSell	.= ")";
			}
			}
		}

		$andcourese = "";
		
		if ($whrereSell != "") {
			$whrereSell = "(".$whrereSell.")";
			$andcourese = " AND ";
		}
		
		
		//Фильтр по тому, за что покупали
		$whereCellFor = 'AND
						( ';
		$or = "";
		foreach ($catecoriesfor as $k => $cat)
		{
			if ($cat == '1')
			{
				foreach ($recourcesfor as $k => $res)
				{
					$whereCellFor .= "$or
							 (hist.id_to_category=$cat  AND hist.id_to_article=$res)";
					$or = " OR ";
				}
			}

			if ($cat == '4')
			{
				foreach ($money as $k => $mon)
				{
					$whereCellFor .= "$or
							 (hist.id_to_category=$cat  AND hist.id_to_article=$mon)";
					$or = " OR ";
				}
			}
		}

		$whereCellFor .= " )";

		$sql = "SELECT *
					FROM (
		SELECT
			count(*) as trade_count,
			sum (hist.from_ammount) AS ammounf_from,
			sum (hist.to_ammount) AS ammount_to,
			hist.id_from_category,
			hist.id_from_article,
    CASE
     WHEN
      c_to.id_tradecategory = 1
        THEN
          (
           SELECT
            an.attributename
           FROM
            ds_attributename an
                WHERE
                 hist.id_to_article = an.id_attributename
            )
        WHEN
         c_to.id_tradecategory = 4
        THEN
         (
          SELECT
           mon.money_name
          FROM
           ds_money mon
                WHERE
                 hist.id_to_article = mon.id_money
            )
        ELSE
         c_to.categoryname
    END AS goods_to,
    
      CASE
			     -- 1 recource
			     WHEN
			      c_from.id_tradecategory = 1
			        THEN
			          (
			           SELECT
			            ltl.text
			           FROM
			            ds_attributename an,
			            ds_localizedtextlanguage ltl
			                WHERE
			                 hist.id_from_article = an.id_attributename
			                 AND an.id_localizedname = ltl.id_localizedtext
			                    AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            )
			        -- 2 units
			        WHEN
			         c_from.id_tradecategory = 2
			        THEN
			         (
			          SELECT
			           ltl.text
			          FROM
			           ds_unit uni,
			           ds_localizedtextlanguage ltl
			                WHERE
			                 hist.id_from_article = uni.id_unit
			                 AND uni.id_localizedname = ltl.id_localizedtext
			                    AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            )
			        -- 3 arts
			        WHEN
			         c_from.id_tradecategory = 3
			        THEN
			         (
			          SELECT
			           ltl.text
			          FROM
			           ds_artefact art,
			           ds_localizedtextlanguage ltl
			                WHERE
			                 hist.id_from_article = art.id_artefact
			                 AND art.id_localizedname = ltl.id_localizedtext
			                    AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            )
			        -- 4 money
			        WHEN
			         c_from.id_tradecategory = 4
			        THEN
			         (
			          SELECT
			           ltl.text
			          FROM
			           ds_money mon,
			           ds_localizedtextlanguage ltl
			                WHERE
			                 hist.id_from_article = mon.id_money
			                 AND mon.id_locolizedname = ltl.id_localizedtext
			                    AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            )
			        ELSE
			         c_from.categoryname
			    END AS goods_from,
			    
			    CASE
					WHEN hist.id_from_category = 1
						THEN
						(
						SELECT a.value FROM ( SELECT *			
						FROM ds_trade_resorce_course_data as trcd
						WHERE 	trcd.id_resource = hist.id_from_article
								AND trcd.id_tradecourse = (
									SELECT t.id_tradecourse
			                        FROM ds_trade_course as t
			                        WHERE t.id_tradecategory = hist.id_to_category
			                              AND
			                              t.id_article = hist.id_to_article)
								AND
							   	trcd.add_date < now()
							    AND
			                    trcd.id_world = ".$worldID."
			                    ORDER BY trcd.add_date DESC
			                     ) as a
			                    LIMIT 1
			           )
							
			        WHEN hist.id_from_category = 2
						THEN
						(
						SELECT a.value FROM ( SELECT *
						FROM ds_trade_units_cours_data as trucd
						WHERE 	trucd.id_unit = hist.id_from_article
								AND
								trucd.id_tradecourse = (
									SELECT t.id_tradecourse
									FROM ds_trade_course as t
									WHERE 	t.id_tradecategory = hist.id_to_category
					    					AND
											t.id_article = hist.id_to_article)
								AND
							trucd.add_date < now()
								AND
			                    trucd.id_world = ".$worldID."
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
									WHERE 	t.id_tradecategory = hist.id_to_category
						    				AND
											t.id_article = hist.id_to_article)
								AND
								 tracd.add_date < now()
								AND
			                    tracd.id_world = ".$worldID."
			                    ORDER BY tracd.add_date DESC
			                    ) as a
			                    LIMIT 1
			               )				
				END AS cource,
				
				CASE
					WHEN hist.id_from_category = 1
						THEN
						(
						SELECT a.add_date FROM ( SELECT *			
						FROM ds_trade_resorce_course_data as trcd
						WHERE 	trcd.id_resource = hist.id_from_article
								AND trcd.id_tradecourse = (
									SELECT t.id_tradecourse
			                        FROM ds_trade_course as t
			                        WHERE t.id_tradecategory = hist.id_to_category
			                              AND
			                              t.id_article = hist.id_to_article)
								AND
							   	trcd.add_date < now()
							    AND
			                    trcd.id_world = ".$worldID."
			                    ORDER BY trcd.add_date DESC
			                     ) as a
			                    LIMIT 1
			           )
							
			        WHEN hist.id_from_category = 2
						THEN
						(
						SELECT a.add_date FROM ( SELECT *
						FROM ds_trade_units_cours_data as trucd
						WHERE 	trucd.id_unit = hist.id_from_article
								AND
								trucd.id_tradecourse = (
									SELECT t.id_tradecourse
									FROM ds_trade_course as t
									WHERE 	t.id_tradecategory = hist.id_to_category
					    					AND
											t.id_article = hist.id_to_article)
								AND
							trucd.add_date < now()
								AND
			                    trucd.id_world = ".$worldID."
			                    ORDER BY trucd.add_date DESC
 							) as a
			                    LIMIT 1
			        )
							
					WHEN hist.id_from_category = 3
						THEN
						(
							SELECT a.add_date FROM ( SELECT *
						FROM ds_trade_artefact_course_data as tracd
						WHERE tracd.id_artefact = hist.id_from_article
								AND tracd.id_tradecourse = (
									SELECT t.id_tradecourse
									FROM ds_trade_course as t
									WHERE 	t.id_tradecategory = hist.id_to_category
						    				AND
											t.id_article = hist.id_to_article)
								AND
								 tracd.add_date < now()
								AND
			                    tracd.id_world = ".$worldID."
			                    ORDER BY tracd.add_date DESC
			                    ) as a
			                    LIMIT 1
			               )				
				END AS  update_time,
			    
    				  CASE
			     -- 1 recource
			     WHEN
			      c_from.id_tradecategory = 1
			        THEN
			          (
			           SELECT
			           		hist.id_from_article
			            )
			        -- 2 units
			        WHEN
			         c_from.id_tradecategory = 2
			        THEN
			         (
			          SELECT
			           ucbr.id_race
			          FROM
			           ds_unitcanbebuildbyrace ucbr			
			                WHERE
			                 hist.id_from_article = ucbr.id_unit
			           LIMIT 1			
			            )
			        -- 3 arts
			        WHEN
			         c_from.id_tradecategory = 3
			        THEN
			         (
			          SELECT
			           art.id_artefactscope
			          FROM
			           ds_artefact art			
			                WHERE
			                 hist.id_from_article = art.id_artefact			
			            )
			        ELSE
			         0
			    END AS filtr1,
			    
			    CASE
			     -- 1 recource
			     WHEN
			      c_from.id_tradecategory = 1
			        THEN
			          (
			           SELECT
			            0
			            )
			        -- 2 units
			        WHEN
			         c_from.id_tradecategory = 2
			        THEN
			         (
			          SELECT
			           uhc.id_unitclass
			          FROM
			           ds_unithasclass uhc			
			                WHERE
			                 hist.id_from_article = uhc.id_unit			
			            )
			        -- 3 arts
			        WHEN
			         c_from.id_tradecategory = 3
			        THEN
			         (
			          SELECT
			           art.id_artefactprobability
			          FROM
			           ds_artefact art			
			                WHERE
			                 hist.id_from_article = art.id_artefact			
			            )
			        ELSE
			         0
			    END AS filtr2

FROM
    ds_trade_history AS hist
    INNER JOIN ds_trade_category AS c_from ON (c_from.id_tradecategory = hist.id_from_category)
    INNER JOIN ds_trade_category AS c_to ON (c_to.id_tradecategory = hist.id_to_category)
    LEFT JOIN ds_user AS u_from ON (hist.from_user_id = u_from.id_user)
    LEFT JOIN ds_user AS u_to ON (hist.to_user_id = u_to.id_user)
WHERE
	hist.start_date > to_timestamp($fromDate)
	AND 
	hist.end_date < to_timestamp($toDate)
	AND
	$whereWorld 
	$whereIDCategory
	$where	
	$whereScope
	$whereCellFor
		--GROUP BY id_from_category,goods_from, filtr1, filtr2, hist.id_to_category, hist.id_to_article, c_to.id_tradecategory, c_to.categoryname
		GROUP BY id_from_category,goods_from, filtr1, filtr2, hist.id_to_category, hist.id_to_article, c_to.id_tradecategory, hist.id_from_article, c_to.categoryname
ORDER BY goods_from, hist.id_to_category, hist.id_to_article) as t
		WHERE
		$whrereSell
		";
//echo "<pre>".$sql."<br>";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if(count($res) == 0)
		{
			return array();
		}

		return $res;
	}

	static public function Recount($idFromArticle, $idFromCategory, $id_tradecategory, $id_article, $cource)
	{
		assert(is_int($idFromArticle) && $idFromArticle > 0);
		assert(is_int($idFromCategory) && $idFromCategory > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_float($cource) && $cource > 0);
		
		$dbID = $_SESSION["dbId"];
		$worldID = $_SESSION['worldID'];
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurentDB($dbID);
		
		switch ($idFromCategory)
				{
					// Ресы
					case 1:
						$sql = "SELECT insert_resource_trade_course(
											$worldID,
							               $idFromArticle,
							               $id_tradecategory,
							               $id_article,
							               $cource)";
						break;
						
					// Юниты
					case 2:
						$sql = "SELECT insert_unit_trade_course($worldID, $idFromArticle, $id_tradecategory, $id_article, $cource) AS res";
						break;
						
					// Арты
					case 3:
						$sql = "SELECT insert_artefact_trade_course($worldID, $idFromArticle, $id_tradecategory, $id_article, $cource) AS res";
						break;
						
					default:
						echo "Wrong Data!<br>";
						exit;
				}
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return true;
	}
}
?>