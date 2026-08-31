<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *
 */
class BG_UserStatistics extends BG_Common
{
	public function __construct($data)
	{
		parent::__construct($data);
	}

	public function getTradeAndVisitStatistics()
	{
		$id_user = intval($this->incoming_info['id_user']);
		$common = DS_Common::CreateForCurentDB(0);
		$result = array('trade' => array(), 'visit' => array());

		// используем логику из пакета GE
		GE_SearchStart::SetCommon($common);
		$worlds = GE_SearchStart::GetWorldsWhereUserIsRegistered($id_user);
		
		
		//$worldsDump = print_r($worlds, true);
		//SU_DebugTools::LogAll('user id: ' . $id_user . ', worlds: ' . $worldsDump . "\n");

		foreach ($worlds as $w) {
			$common->setDbId($common->getDbIdByName($w['dbname']));
			$common->startTrans();
			$id_world = intval($w['id_world']);

			$sql = 'SET STATEMENT_TIMEOUT to 240000;
			SELECT
	(
		SELECT
			u_from.username
		FROM
		    ds_user AS u_from
		WHERE
		    u_from.id_user = hist.from_user_id
	) AS seller,

	(
		SELECT
			u_to.username
		FROM
		    ds_user AS u_to
		WHERE
		    u_to.id_user = hist.to_user_id
	) AS customer,

			    
	(
		SELECT
   			lh1.ip
   		FROM
   			ds_user use1,
            ds_loginhistory lh1
        WHERE
        	use1.id_user = hist.from_user_id
            AND use1.id_user = lh1.id_user
            AND lh1.lastvisit < hist.start_date
		ORDER BY lh1.lastvisit DESC
       	LIMIT 1
	) as seller_ip,
	
	(
		SELECT
   			lh1.ip
   		FROM
   			ds_user use2,
            ds_loginhistory lh1
        WHERE
        	use2.id_user = hist.to_user_id
            AND use2.id_user = lh1.id_user
            AND lh1.lastvisit < hist.start_date
		ORDER BY lh1.lastvisit DESC
       	LIMIT 1
	) as customer_ip,
			    
	CASE
		-- 1 recource
		WHEN c_from.id_tradecategory = 1 THEN
		(
			SELECT
				ltl.text
			FROM
			    ds_attributename an,
			    ds_localizedtextlanguage ltl
			WHERE
			    hist.id_from_article = an.id_attributename
			    AND an.id_localizedname = ltl.id_localizedtext
			    AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)
		
		-- 2 units
		WHEN c_from.id_tradecategory = 2 THEN
		(
			SELECT
				ltl.text
			FROM
			    ds_unit uni,
			    ds_localizedtextlanguage ltl
			WHERE
			    hist.id_from_article = uni.id_unit
			    AND uni.id_localizedname = ltl.id_localizedtext
			    AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)

		-- 3 arts
		WHEN c_from.id_tradecategory = 3 THEN
		(
			SELECT
				ltl.text
			FROM
			    ds_artefact art,
			    ds_localizedtextlanguage ltl
			WHERE
			    hist.id_from_article = art.id_artefact
			    AND art.id_localizedname = ltl.id_localizedtext
			    AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)
		
		-- 4 money
		WHEN c_from.id_tradecategory = 4 THEN
		(
			SELECT
				ltl.text
			FROM
				ds_money mon,
			    ds_localizedtextlanguage ltl
			WHERE
			    hist.id_from_article = mon.id_money
			    AND mon.id_locolizedname = ltl.id_localizedtext
			    AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)
		
		ELSE
			c_from.categoryname
		END AS goods_from,			    			 
			
	hist.from_ammount,
	tax_summ,
		
	CASE
		-- 1 recource
		WHEN c_from.id_tradecategory = 1 THEN
		(
			SELECT
		    	ltl.text
		    FROM
		        ds_attributename an,
		        ds_localizedtextlanguage ltl
		    WHERE
		        hist.id_from_article = an.id_attributename
		        AND an.id_localizedname = ltl.id_localizedtext
		        AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)
		
		-- 2 units
		WHEN c_from.id_tradecategory = 2 THEN
		(
			SELECT
		    	ltl.text
		    FROM
		        ds_unit uni,
		        ds_localizedtextlanguage ltl
		    WHERE
		        hist.id_from_article = uni.id_unit
		        AND uni.id_localizedname = ltl.id_localizedtext
		        AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)
		
		-- 3 arts
		WHEN c_from.id_tradecategory = 3 THEN
		(
			SELECT
		    	ltl.text
		    FROM
		        ds_artefact art,
		        ds_localizedtextlanguage ltl
		    WHERE
		        hist.id_from_article = art.id_artefact
		        AND art.id_localizedname = ltl.id_localizedtext
		        AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)
		
		-- 4 money
		WHEN c_from.id_tradecategory = 4 THEN
		(
			SELECT
		    	ltl.text
		    FROM
		        ds_money mon,
		        ds_localizedtextlanguage ltl
		    WHERE
		        hist.id_from_article = mon.id_money
		        AND mon.id_locolizedname = ltl.id_localizedtext
		        AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
			)
		
	ELSE
		c_from.categoryname
	END AS goods_from,
			
	hist.from_ammount,

	CASE
		-- 1 recource
		WHEN c_to.id_tradecategory = 1 THEN
        (
			SELECT
		    	ltl.text
		    FROM
		        ds_attributename an,
		        ds_localizedtextlanguage ltl
		    WHERE
		        hist.id_to_article = an.id_attributename
		        AND an.id_localizedname = ltl.id_localizedtext
		        AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)
			
		-- 2 units
		WHEN c_to.id_tradecategory = 2 THEN
	    (
	    	SELECT
		    	ltl.text
		    FROM
		        ds_unit uni,
		        ds_localizedtextlanguage ltl
		    WHERE
		        hist.id_to_article = uni.id_unit
		        AND uni.id_localizedname = ltl.id_localizedtext
		        AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)

		-- 3 arts
		WHEN c_to.id_tradecategory = 3 THEN
        (
	        SELECT
		        ltl.text
		    FROM
		        ds_artefact art,
		        ds_localizedtextlanguage ltl
		    WHERE
		        hist.id_to_article = art.id_artefact
		        AND art.id_localizedname = ltl.id_localizedtext
		        AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)

		-- 4 money
		WHEN c_to.id_tradecategory = 4 THEN
        (
	        SELECT
		        ltl.text
		    FROM
		        ds_money mon,
		        ds_localizedtextlanguage ltl
		    WHERE
		        hist.id_to_article = mon.id_money
		        AND mon.id_locolizedname = ltl.id_localizedtext
		        AND ltl.id_language = '.constant("DEFAULT_LANGUAGE").'
		)
		
	ELSE
		c_from.categoryname
	END AS goods_to,

    hist.from_ammount,
    
    hist.to_ammount,
    
    hist.start_date,
    hist.end_date

FROM
	ds_trade_history AS hist
	INNER JOIN ds_trade_category AS c_from ON (c_from.id_tradecategory = hist.id_from_category)
	INNER JOIN ds_trade_category AS c_to ON (c_to.id_tradecategory = hist.id_to_category)
	LEFT JOIN ds_trade_scope AS ts ON (ts.id_tradescope = hist.id_trade_scope)
WHERE
	(hist.from_user_id = '.$id_user.'
	OR
	hist.to_user_id = '.$id_user.')
	  AND 
	(hist.start_date > now() - \'10 DAY\'::interval)
	  AND
	hist.world_id = '.$id_world.'
ORDER BY
    hist.end_date DESC
			';
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));
			
			if (count($res) > 0) {
				$worldInfo = array('world_name' => $w['worldname'], 'data' => $res);
				array_push($result['trade'], $worldInfo);
			}
			$common->closeTrans('ROLLBACK');
		}
		
		$common->setDbId(0);
		// статистика по заходам на аккаунт
		$sql = '
			select
			  id_loginhistory, lastvisit, ip
			from
			  ds_loginhistory
			where
			  id_user = ' . $id_user . ' and
			  (lastvisit > now() - \'10 DAY\'::interval)
			order by lastvisit desc
		';
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		$result['visit'] = $res;

		$serialized = serialize($result);
		$serialized = urlencode($serialized);
		$this->addToResult('serialized_data', $serialized);

		$this->outputAnswer();
	
		return;
	}
}

?>