<?php

class InitTradeCourses
{
	static private $action = 'index';	
	
	static public function Exec(array $data)
	{
		self::_getParams($data);
		
		switch (self::$action) {
			case 'index':
				self::_outMenu();
				break;
		
			default:
				break;
		}
	}
	
	static private function _getParams(array $data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}
	}
	
	static private function _getWorldList()
	{
		$common = DS_Common::CreateForCurentDB(0);
		$sql = "	
			SELECT
				*
			FROM
				ds_worldcollection AS wc
			WHERE
				wc.world_identifier != 'UC'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static private function _outMenu()
	{
		$worlds = self::_getWorldList();
		
		foreach ($worlds as $k => $w)
		{
			foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
			{
				if($db['server'] == $w['dbname'])
				{
					break;
				}
			}
		}
	}
	
	static private function _init($world, $db)
	{
		assert(is_int($world) && $world > 0);
		assert(is_int($db) && $db >= 0);

		$worlds = array();
		$worlds[0] = $world;

		$common = DS_Common::CreateForCurentDB($db);

		$common->getResultArray('', 'BEGIN', '', '');
		assert(empty($common->errormsg));

		$resTmp = $common->getResultArray('', 'DELETE FROM ds_trade_type_data', '', '');
		assert(empty($common->errormsg));

		$resTmp = $common->getResultArray('', 'DELETE FROM ds_trade_type', '', '');
		assert(empty($common->errormsg));

		$sql1 = "SELECT * FROM ds_trade_category WHERE can_trade_on = true";
		$resCategoryFrom = $common->getResultArray('', $sql1, '', '');
		assert(empty($common->errormsg));

		echo $sql1."\n-----------------------------------\n";

		foreach ($resCategoryFrom as $r=>$categoryFrom)
		{
			echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> FROM CATEGORY NAME: ".$categoryFrom['categoryname']."\n";
			switch ($categoryFrom['categoryname'])
			{
				case 'RESOURCE':
					$sql2 = "SELECT
      							r.id_attributename AS id_trade_on_type
							FROM
    							ds_resource AS r
    						WHERE 
    							r.trade_resources = true";
					break;
				case 'UNIT':
					$sql2 = "SELECT
     							r.id_unitclass AS id_trade_on_type
							FROM
    							ds_unitclass AS r";
					break;
				case 'ARTEFACT':
					$sql2 = "SELECT
    							r.id_artefactprobability AS id_trade_on_type
							FROM
    							ds_artefactprobability AS r";
					break;
				case 'MONEY':
					$sql2 = "SELECT
    							r.id_money AS id_trade_on_type
							FROM
    							ds_money AS r";
					break;
			}

			$resTypeFrom = $common->getResultArray('', $sql2, '', '');
			assert(empty($common->errormsg));
			echo $sql2."\n-----------------------------------\n";
			foreach($resTypeFrom as $k => $typeFrom)
			{

				$sql3 = "SELECT * FROM ds_trade_category WHERE can_trade_by = true";
				$resCategoryBy = $common->getResultArray('', $sql3, '', '');
				assert(empty($common->errormsg));
				echo $sql3."\n-----------------------------------\n";

				foreach($resCategoryBy as $k => $categoryBy)
				{

					echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> TO CATEGORY NAME: ".$categoryBy['categoryname']."\n";
					switch ($categoryBy['categoryname'])
					{
						case 'RESOURCE':
							$sql4 = "SELECT
      									r.id_attributename AS id_trade_on_type
									FROM
    									ds_resource AS r
    								WHERE 
    									r.trade_resources = true";
							break;
						case 'UNIT':
							$sql4 = "SELECT
     									r.id_unitclass AS id_trade_on_type
									FROM
    									ds_unitclass AS r";
							break;
						case 'ARTEFACT':
							$sql4 = "SELECT
    									r.id_artefactprobability AS id_trade_on_type
									FROM
    									ds_artefactprobability AS r";
							break;
						case 'MONEY':
							$sql4 = "SELECT
    									r.id_money AS id_trade_on_type
									FROM
    									ds_money AS r";
							break;
					}

					$resTypeTo = $common->getResultArray('', $sql4, '', '');
					assert(empty($common->errormsg));

					echo $sql4."\n-----------------------------------\n";

					foreach($resTypeTo as $k=>$typeTo)
					{
						$tradeOnCategoryID = $categoryFrom['id_tradecategory'];
						$tradeOnTypeID = $typeFrom['id_trade_on_type'];
						$tradeByCategoryID = $categoryBy['id_tradecategory'];
						$tradeByTypeID = $typeTo['id_trade_on_type'];

						$sql5 = "
								INSERT INTO ds_trade_type
									(id_trade_on_category, id_trade_on_type, id_trade_by_category, id_trade_by_type)						
								VALUES
									($tradeOnCategoryID, $tradeOnTypeID, $tradeByCategoryID, $tradeByTypeID)
								";
						$resTmp = $common->getResultArray('', $sql5, '', '');
						assert(empty($common->errormsg));
						echo $sql5."\n-----------------------------------\n";
						$sqlTmp = "SELECT max(id_tradetype) as id_tradetype FROM ds_trade_type";
						$resTmp = $common->getResultArray('', $sqlTmp, '', '');
						assert(empty($common->errormsg));
						$tardeTypeID = (int)$resTmp[0]['id_tradetype'];

						foreach($worlds AS $k => $worldID)
						{
							$sql6 = "
								INSERT INTO ds_trade_type_data
						 			(id_tradetype, add_date, is_allowed, max_price_delta, min_price_delta, tax, is_current, id_world)
						 		VALUES
						 			($tardeTypeID, now(), true, 20, -20, 10, true, $worldID)";
							$common->getResultArray('', $sql6, '', '');
							assert(empty($common->errormsg));

							echo $sql6."\n-----------------------------------\n";

							$sql7 = "
								INSERT INTO ds_trade_type_data
						 			(id_tradetype, add_date, is_allowed, max_price_delta, min_price_delta, tax, is_current, id_world)
						 		VALUES
						 			($tardeTypeID, '2005-07-12 12:02:02', true, 20, -20, 10, false, $worldID)";
							$common->getResultArray('', $sql7, '', '');
							assert(empty($common->errormsg));
							echo $sql7."\n-----------------------------------\n";
						}
					}
				}
			}
		}

		$resCategoryFrom = $common->getResultArray('', 'COMMIT', '', '');
		assert(empty($common->errormsg));
	}
}

?>