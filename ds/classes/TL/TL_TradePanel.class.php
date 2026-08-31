<?php $CVS_Version = '$Revision: 1.9 $ ; $Date: 2008/10/29 11:40:33 $';

class TL_TradePanel extends TL_BaseMenu
{
	static public function FormMenu(TI_TradePanel $incoming)
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><trade/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();

		$out = "<trade turn=\"".$incoming->turnTime."\" time2trade=\"".$incoming->time2trade."\" minquntity=\"".$incoming->minquntity."\" notrend=\"".$incoming->tradeFlags['TRADE_USE_TRANDS']."\" nonalog=\"".$incoming->tradeFlags['TRADE_USE_TAX']."\" nopersonaly=\"".$incoming->tradeFlags['TRADE_USE_PERSONAL_TRADES']."\" anonymous=\"".$incoming->tradeFlags['TRADE_ANONYMOUS_SELL']."\">";

		//Описавыем матрицу торговли
		$out .= "<matrix>";
		//Левый заголовок таблицы
		$out .= "<trades>";
		$tmpArray = array();
		foreach($incoming->matrixLeftTitles as $k=>$v)
		{
			$tmpArray[] = implode('_', $v);
		}
		$out .= implode(',', $tmpArray);
		$out .= "</trades>";

		//Верхний заголовок таблицы
		$out .= "<reses>";
		$tmpArray = array();
		foreach($incoming->matrixUpTitles AS $k=>$v)
		{
			$resourceName = $v[3];
			assert(!empty($resourceName));

			assert(key_exists($resourceName, $incoming->courses));
			$course = $incoming->courses[$resourceName];

			$tmpArray[] = $resourceName.":".$course.":".$v[0]."_".$v[1];
		}
		$out .= implode(",", $tmpArray);
		$out .= "</reses>";

		//Строки таблички
		$out .= "<data>";
		$tmpArray = array();
		foreach($incoming->matrixData as $k1=>$matrixRow)
		{
			foreach($matrixRow as $k2=>$matrixCell)
			{
				assert(key_exists(0, $matrixCell));
				$isAllowed = $matrixCell[0];

				if($isAllowed)
				{
					if($matrixCell[1] == 'X')
					{
						$cell_1 = 'X';
					}else{
						$cell_1 = abs($matrixCell[1]);
					}

					if($matrixCell[2] == 'X')
					{
						$cell_2 = 'X';
					}else{
						$cell_2 = abs($matrixCell[2]);
					}

					if($matrixCell[3] == 'X')
					{
						$cell_3 = 'X';
					}else{
						$cell_3 = abs($matrixCell[3]);
					}

					$tmpArray[] = $cell_1."-".$cell_2."-".$cell_3;
				}else{
					$tmpArray[] = 'X';
				}
			}
		}
		$out .= implode(',', $tmpArray);
		$out .= "</data>";
		$out .= "</matrix>";

		$out .= "<articles>";

		foreach($incoming->tradeFrom as $k => $artecleData)
		{
			assert(key_exists(0, $artecleData));
			$tradeCategoryID = (int)$artecleData[0];
			assert($tradeCategoryID > 0);

			assert(key_exists(1, $artecleData));
			$typeCategoryID = (int)$artecleData[1];
			assert($typeCategoryID > 0);

			assert(key_exists(2, $artecleData));
			$articleID = (int)$artecleData[2];
			assert($articleID > 0);

			assert(key_exists(3, $artecleData));
			$amount = (float)$artecleData[3];
			assert($amount >= 0);

			assert(key_exists(4, $artecleData));
			$power = $artecleData[4];

			assert(key_exists(5, $artecleData));
			$libName = $artecleData[5];

			assert(key_exists(6, $artecleData));
			$upkeepData = $artecleData[6];

			$unitlist = "";

			for($race=1; $race <= 3;$race++)
			{
				$arId = $race + 4;

				$units = DS_Unit::GetAllRaceUnits($race);
				foreach ($units as $unitData)
				{
					$unitlist .= "<article cat=\"{$arId}_1\" libname=\"{$unitData['unitname']}\" id=\"id_unit\" />";
				}
			}


			switch ($tradeCategoryID)
			{
				case '1': //RESOURCE
				$out .= "<article cat=\"".$tradeCategoryID."_".$typeCategoryID."\" id=\"$articleID\" libname=\"$libName\" amount=\"".GB_Integer::Format($amount)."\" power=\"$power\"/>";
				break;
				case '2': //UNIT
				assert(is_array($upkeepData) && count($upkeepData) >= 0);

				$arrayTmp = array();
				foreach($upkeepData as $resourceName => $upkeep)
				{
					$arrayTmp[] = substr($resourceName, 0, 1).":".number_format(round($upkeep, 10), 6, '.', '');
				}

				$upkeepString = implode(",", $arrayTmp);

				$out .= "<article cat=\"".$tradeCategoryID."_".$typeCategoryID."\" libname=\"$libName\" id=\"$articleID\" amount=\"".GB_Integer::Format($amount)."\" upkeep=\"$upkeepString\" power=\"$power\"/>";

				break;
				case '3'://ARTEFACT
				$datum = $artecleData[7] != '' ? $artecleData[7] : '';

				$out .= "<article cat=\"".$tradeCategoryID."_".$typeCategoryID."\" id=\"$articleID\" amount=\"".GB_Integer::Format($amount)."\" power=\"$power\" {$datum} />";
				break;
			}
		}

		$out .= $unitlist;
		$out .= "</articles>";

		$out .= "<scopes>";
		foreach($incoming->combAllowedTradeScopes as $k=>$tradeScopeData)
		{
			$out .= "<allow id_tradescope=\"".$tradeScopeData[0]."\" tradescopename=\"".$tradeScopeData[1]."\" />";
		}
		$out .= "</scopes>";

		// закладки
		self::GetBuildingTabs($root, $incoming->getBuildingID(), DE_Language::CreateForCurentLanguage()->getID(), $incoming->getTabName());
		$forOut = str_replace('<trade>', $out, $doc->dump_mem());
		return $forOut;
	}
}


?>