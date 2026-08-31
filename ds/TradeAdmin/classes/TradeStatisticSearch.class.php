<?php
class TradeStatisticSearch
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

		static public function GetScopeList()
		{
			$common = DS_Common::CreateForCurentDB(0);

			$out = array();

			$sql = "SELECT ts.id_tradescope, ts.tradescopename
       					 FROM ds_trade_scope as ts";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			return $res;
		}
		
	static public function GetStat($subject, $fromDate, $toDate, $worldArr, $scopeID)
	{
		assert(!empty($subject));
		assert((is_int($scopeID) && $scopeID >= 0) ||  $scopeID == 'ALL');
		assert(!empty($worldArr));
		//assert(is_array($worldArr));
		//assert(is_int($DBID) && $DBID >= 0);
		//assert(is_int($worldID) && $worldID > 0);
//print_r($worldArr);
		$whereWorld = '';
		$whereIDCategory = '';
		
		$arr = explode('_', $worldArr);
		$DBID = (int)$arr[0];
		$worldID = (int)$arr[1];
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		$whereWorld .= " hist.world_id = $worldID ";

		$common = DS_Common::CreateForCurrentDB($DBID);
		/*$whereWorld = ' ( ';
		
		foreach ($worldArr as $key => $value)
		{
			$arr = explode('_', $value);
			$dbID = (int)$arr[0];
			$worldID = (int)$arr[1];
			assert(is_int($dbID) && $DBID >= 0);
			assert(is_int($worldID) && $worldID > 0);
			switch ($key)
			{
				case 0:
					$whereWorld .= " hist.world_id = $worldID ";
					break;
				default:
					$whereWorld .= " OR hist.world_id = $worldID ";
			}
		}
		$whereWorld .= ' ) ';
	//echo "1";	
	//exit;*/
		if($subject == 'artefact')
		{
			$whereIDCategory = ' AND hist.id_from_category = 3';

			$condition = "
							 (
				          SELECT
				           ltl.text
				          FROM
						ds_artefact art,
           				ds_localizedtextlanguage ltl
                		WHERE
	                 		hist.id_from_article = art.id_artefact
	                 		AND 
	                 		art.id_localizedname = ltl.id_localizedtext
	                 			          AND 
				          ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            ) AS goods_from
			";
		}
		
		if($scopeID == 'ALL')
		{
			$where = '';
			$groupby = '';
		}else 
		{
			$where = "AND
							hist.id_trade_scope = $scopeID";
			$groupby = ', hist.id_trade_scope';
		}
		if($subject == 'unit')
		{
			$whereIDCategory = ' AND hist.id_from_category = 2';

			$condition = "
						 (
				          SELECT
				           ltl.text
				          FROM
						ds_unit uni,
           				ds_localizedtextlanguage ltl
                		WHERE
	                 		hist.id_from_article = uni.id_unit
	                 		AND 
	                 		uni.id_localizedname = ltl.id_localizedtext
	                 			          AND 
				          ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            ) AS goods_from";
		}
		
		if($subject == 'resource')
		{
			$whereIDCategory = ' AND hist.id_from_category = 1';

			$condition = "  (
				          SELECT
				           ltl.text
				          FROM
           				 ds_attributename an,
           				 ds_localizedtextlanguage ltl
               			 WHERE
	                 	   	hist.id_from_article = an.id_attributename
                			 AND 
                			 an.id_localizedname = ltl.id_localizedtext
                			 	          AND 
				          ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            ) AS goods_from";
		}
		
		if($subject == 'ALL')
		{
			$whereIDCategory = '';
			$condition = "
           				CASE
     -- 1 ???????
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
        -- 2 ?????
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
        -- 3 ?????????
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
        ELSE
         c_from.categoryname
    END AS goods_from";
		}
		
		$sql = "
		SELECT
			sum (hist.from_ammount) AS ammounf_from,
			sum (hist.to_ammount) AS ammount_to,
          $condition,

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
    END AS goods_to

FROM
    ds_trade_history AS hist
    INNER JOIN ds_trade_category AS c_from ON (c_from.id_tradecategory = hist.id_from_category)
    INNER JOIN ds_trade_category AS c_to ON (c_to.id_tradecategory = hist.id_to_category)
WHERE
	hist.start_date > to_timestamp($fromDate)
	AND 
	hist.end_date < to_timestamp($toDate)
	AND
	$whereWorld 
	$whereIDCategory
	$where	
GROUP BY goods_from, hist.id_to_category, hist.id_to_article, c_to.id_tradecategory, c_to.categoryname $groupby
ORDER BY goods_from, hist.id_to_category, hist.id_to_article
		";
/*echo "<xmp>";		
echo $sql;
echo "</xmp>";
exit;*/
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) == 0)
		{
			return array();
		}
		
		return $res;
	}
	
	
	
	static public function GetStat2($subject, $fromDate, $toDate, $worldArr, $scopeID)
	{
		assert(!empty($subject));
		assert((is_int($scopeID) && $scopeID >= 0) ||  $scopeID == 'ALL');
		assert(is_array($worldArr));
		
		
		foreach ($worldArr as $key => $worldData)
		{
		$whereWorld = '';
		$whereIDCategory = '';
		
		$arr = explode('_', $worldData[0]);
		$DBID = (int)$arr[0];
		$worldID = (int)$arr[1];
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		$whereWorld .= " hist.world_id = $worldID ";

		$common = DS_Common::CreateForCurrentDB($DBID);
		
		if($subject == 'artefact')
		{
			$whereIDCategory = ' AND hist.id_from_category = 3';

			$condition = "
							 (
				          SELECT
				           ltl.text
				          FROM
						ds_artefact art,
           				ds_localizedtextlanguage ltl
                		WHERE
	                 		hist.id_from_article = art.id_artefact
	                 		AND 
	                 		art.id_localizedname = ltl.id_localizedtext
	                 			          AND 
				          ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            ) AS goods_from
			";
		}
		
		if($scopeID == 'ALL')
		{
			$where = '';
			$groupby = '';
		}else 
		{
			$where = "AND
							hist.id_trade_scope = $scopeID";
			$groupby = ', hist.id_trade_scope';
		}
		if($subject == 'unit')
		{
			$whereIDCategory = ' AND hist.id_from_category = 2';

			$condition = "
						 (
				          SELECT
				           ltl.text
				          FROM
						ds_unit uni,
           				ds_localizedtextlanguage ltl
                		WHERE
	                 		hist.id_from_article = uni.id_unit
	                 		AND 
	                 		uni.id_localizedname = ltl.id_localizedtext
	                 			          AND 
				          ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            ) AS goods_from";
		}
		
		if($subject == 'resource')
		{
			$whereIDCategory = ' AND hist.id_from_category = 1';

			$condition = "  (
				          SELECT
				           ltl.text
				          FROM
           				 ds_attributename an,
           				 ds_localizedtextlanguage ltl
               			 WHERE
	                 	   	hist.id_from_article = an.id_attributename
                			 AND 
                			 an.id_localizedname = ltl.id_localizedtext
                			 	          AND 
				          ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
			            ) AS goods_from";
		}
		
		if($subject == 'ALL')
		{
			$whereIDCategory = '';
			$condition = "
           				CASE
     -- 1 ???????
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
        -- 2 ?????
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
        -- 3 ?????????
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
        ELSE
         c_from.categoryname
    END AS goods_from";
		}
		
		$sql = "
		SELECT
			sum (hist.from_ammount) AS ammounf_from,
			sum (hist.to_ammount) AS ammount_to,
          $condition,

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
    END AS goods_to

FROM
    ds_trade_history AS hist
    INNER JOIN ds_trade_category AS c_from ON (c_from.id_tradecategory = hist.id_from_category)
    INNER JOIN ds_trade_category AS c_to ON (c_to.id_tradecategory = hist.id_to_category)
WHERE
	hist.start_date > to_timestamp($fromDate)
	AND 
	hist.end_date < to_timestamp($toDate)
	AND
	$whereWorld 
	$whereIDCategory
	$where	
GROUP BY goods_from, hist.id_to_category, hist.id_to_article, c_to.id_tradecategory, c_to.categoryname $groupby
ORDER BY goods_from, hist.id_to_category, hist.id_to_article
		";
/*echo "<xmp>";		
echo $sql;
echo "</xmp>";
exit;*/
		$res[] = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		/*if(count($res) == 0)
		{
			return array();
		}*/
		}	
		return $res;
	}
}
?>