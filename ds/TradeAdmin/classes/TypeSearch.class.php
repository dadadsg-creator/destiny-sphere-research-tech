<?php

class TypeSearch
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
	
	static public function GetTableLeftColumnCategorys($DBID)
	{
		assert(is_int($DBID) && $DBID>=0);		
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "SELECT *
				FROM ds_trade_category AS tc
				WHERE can_trade_on = true
				ORDER BY tc.id_tradecategory";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetTableUpRowsCategorys($DBID)
	{
		assert(is_int($DBID) && $DBID>=0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "SELECT *
				FROM ds_trade_category AS tc
				WHERE can_trade_by = true
				ORDER BY tc.id_tradecategory";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
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
      						an.attributename AS name
						FROM
    						ds_resource AS r
    						INNER JOIN ds_attributename AS an ON (an.id_attributename = r.id_attributename)
						WHERE r.trade_resources = true
						ORDER BY r.id_attributename";
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
	
	static public function GetTradeTypeDataByAllID($DBID, $worldID, $tradeOnCatID, $tradeOnTypeID, $tradeByCatID, $tradeByTypeID)
	{
		assert(is_int($DBID) && $DBID>=0);
		assert(is_int($worldID) && $worldID>0);
		assert(is_int($tradeOnCatID) && $tradeOnCatID>0);
		assert(is_int($tradeOnTypeID) && $tradeOnTypeID>0);
		assert(is_int($tradeByCatID) && $tradeByCatID>0);
		assert(is_int($tradeByTypeID) && $tradeByTypeID>0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			SELECT
      			tt.id_tradetype,
      			ttd.add_date,
      			ttd.is_allowed,
      			ttd.max_price_delta,
      			ttd.min_price_delta,
      			ttd.tax
			FROM
    			ds_trade_type_data AS ttd
    			INNER JOIN ds_trade_type AS tt ON (tt.id_tradetype = ttd.id_tradetype AND ttd.is_current = true)
			WHERE
     			tt.id_trade_on_category = $tradeOnCatID
     			AND
     			tt.id_trade_on_type = $tradeOnTypeID
     			AND
     			tt.id_trade_by_category = $tradeByCatID
     			AND
     			tt.id_trade_by_type = $tradeByTypeID
     			AND
     			ttd.id_world = $worldID";
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetTradeTypeDataByTYpeIDAndWorldID($DBID, $worldID, $tradeTypeID)
	{
		assert(is_int($DBID) && $DBID>=0);
		assert(is_int($worldID) && $worldID>0);
		assert(is_int($tradeTypeID) && $tradeTypeID>0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "
			SELECT
      			tt.*,
      			ttd.*,
      			tcon.categoryname AS category_on_name,
      			tcby.categoryname AS category_by_name
			FROM
    			ds_trade_type_data AS ttd
    			INNER JOIN ds_trade_type AS tt ON (tt.id_tradetype = ttd.id_tradetype AND ttd.is_current = true)
    			INNER JOIN ds_trade_category AS tcon ON (tcon.id_tradecategory = tt.id_trade_on_category)
    			INNER JOIN ds_trade_category AS tcby ON (tcby.id_tradecategory = tt.id_trade_by_category)
			WHERE
     			tt.id_tradetype = $tradeTypeID
     			AND
     			ttd.id_world = $worldID
			";
		$res = $common->getResultArray ( "", $sql, "", "", "");	
			
		assert(empty($common->errormsg) && count($res) == 1);

		return $res[0];
	}
	
	static public function SetTradeTypeDataAsOld($DBID, $worldID, $tradeTypeID)
	{
		assert(is_int($DBID) && $DBID>=0);
		assert(is_int($worldID) && $worldID>0);
		assert(is_int($tradeTypeID) && $tradeTypeID>0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "UPDATE ds_trade_type_data
				SET is_current = false
				WHERE 
					id_tradetype = ".$tradeTypeID."
					AND
					id_world = $worldID";

		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));		
	}
	
	static public function InsertTradeTypeData($DBID, $worldID, $tradeTypeID, $isAllowed, $maxDelta, $minDelta, $tax)
	{
		assert(is_int($DBID) && $DBID>=0);
		assert(is_int($worldID) && $worldID>0);
		assert(is_int($tradeTypeID) && $tradeTypeID>0);
		assert($isAllowed == 't' || $isAllowed == 'f');
		assert(is_int($tradeTypeID) && $tradeTypeID>0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		
		$sql = "INSERT INTO ds_trade_type_data
				(id_tradetype, is_allowed, max_price_delta, min_price_delta, tax, id_world)
				VALUES
				($tradeTypeID, '$isAllowed', $maxDelta, $minDelta, $tax, $worldID)";
		
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
	}
	
	static public function GetTradeTypeHistoryData($DBID, $worldID, $tradeTypeID)
	{
		assert(is_int($DBID) && $DBID>=0);
		assert(is_int($worldID) && $worldID>0);
		assert(is_int($tradeTypeID) && $tradeTypeID>0);
		
		$common = DS_Common::CreateForCurentDB($DBID);
		$sql = "
			SELECT
				ttd.*
			FROM
				ds_trade_type_data AS ttd
			WHERE
				ttd.id_tradetype = $tradeTypeID
				AND
				ttd.id_world = $worldID
			ORDER BY ttd.add_date DESC
			";
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
}

?>