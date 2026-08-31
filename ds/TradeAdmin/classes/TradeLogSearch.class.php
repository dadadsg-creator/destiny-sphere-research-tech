<?php

class TradeLogSearch
{
	static public function getTradesLogsCount($userName, $fromDate, $toDate,$DBID, $worldID)
	{
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		$common = DS_Common::CreateForCurrentDB($DBID);

		$join = '';		
		$where = ' TRUE';
		
		if(!empty($userName))
		{
			$where .= " AND u.username = '$userName'";
			$join = " INNER JOIN ds_user AS u ON (u.id_user = hist.from_user_id OR u.id_user = hist.to_user_id) ";
		}
		
		if(!empty($fromDate))
		{
			$where .= " AND hist.start_date > to_timestamp($fromDate)";
		}
		
		if(!empty($toDate))
		{
			$where .= " AND hist.end_date < to_timestamp($toDate)";
		}
		
		/*$sql = "
			SELECT
    			count(hist.id_trade)
			FROM
				ds_trade_history AS hist
				INNER JOIN ds_user AS u ON (u.id_user = hist.from_user_id OR u.id_user = hist.to_user_id)
			WHERE
				$where
			";	*/	
			
		$sql = "
			SELECT
    			count(hist.id_trade)
			FROM
				ds_trade_history AS hist
			$join
			WHERE
				$where
			";	

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0]['count'];
	}
	
	static public function getTradesLogs($userName, $fromDate, $toDate, $limit, $offset, $DBID, $worldID)
	{
		assert(is_int($DBID) && $DBID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($limit) && $limit >= 0);
		assert(is_int($offset) && $offset >= 0);
		
		$common = DS_Common::CreateForCurrentDB($DBID);
		
		$where = ' TRUE';
		//$where = '';
		
		if(!empty($userName))
		{
			$where .= " AND u.username ILIKE '$userName'";
		}
		
		if(!empty($fromDate))
		{
			$where .= " AND hist.start_date > to_timestamp($fromDate)";
		}
		
		if(!empty($toDate))
		{
			$where .= " AND hist.end_date < to_timestamp($toDate)";
		}
		
		if($limit > 0)
		{
			$limit = " OFFSET $offset LIMIT $limit";
		}
		
		$sql = "SELECT

 DISTINCT hist.id_trade,

    hist.start_date,
    hist.end_date,

    -- ??? ????????
    (
        SELECT
      u_from.username
     FROM
      ds_user AS u_from
         WHERE
         u_from.id_user = hist.from_user_id
    ) AS seller,

    -- ???????? ? ?????????? ???? ????????
   (
     SELECT
      COALESCE(cn.combname, 'empty comb')||'
('||r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber||')'
     FROM
      ds_comb AS c
      INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex =
c.id_combcomplex)
      INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
      LEFT JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
     WHERE
      c.id_comb = hist.from_comb_id
    ) as comb_from,

 -- ??? ??????????
 (
  SELECT
      u_to.username
     FROM
      ds_user AS u_to
        WHERE
         u_to.id_user = hist.to_user_id
    ) AS customer,

    -- ???????? ? ?????????? ???? ??????????
    (
     SELECT
      COALESCE(cn.combname, 'empty comb')||'
('||r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber||')'
     FROM
      ds_comb AS c
      INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex =
c.id_combcomplex)
      INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
      LEFT JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
     WHERE
      c.id_comb = hist.to_comb_id
    ) as comb_to,

 -- ???????? ????, ??? ?????????
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
        -- 4 ???????
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

    hist.from_ammount,

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

    hist.to_ammount,

    tax_summ,

    (
        SELECT tradescopename
        FROM ds_trade_scope
        WHERE id_tradescope = hist.id_trade_scope
    ) AS trade_scope

FROM
    ds_trade_history AS hist
    INNER JOIN ds_user AS u ON (u.id_user = hist.from_user_id OR u.id_user = hist.to_user_id)
    INNER JOIN ds_trade_category AS c_from ON (c_from.id_tradecategory = hist.id_from_category)
    INNER JOIN ds_trade_category AS c_to ON (c_to.id_tradecategory = hist.id_to_category)
WHERE 
	$where
	AND
	hist.world_id = $worldID
ORDER BY 
	hist.start_date DESC
$limit
		";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) == 0)
		{
			return array();
		}
		
		return $res;
	}
}

?>