<?php

class TypeControl
{
	static private $action;

	static private $DBID;

	static private $worldID;

	static private $ID;

	static private $out;

	static private $type = array();

	static private $oldData = array();

	static public function Exec($data)
	{
		assert(is_array($data));

		self::_getParams($data);

		self::_execAction();

		return self::$out;
	}

	static private function _execAction()
	{
		assert(!empty(self::$action));

		switch(self::$action)
		{
			case 'show_empty_list':
				self::_showEmptyList();
				break;
			case 'show_full_list':
				self::_showFullList();
				break;
			case 'history':
				self::_showHistoryForm();
				break;
			case 'update_exec':
				self::_execUpdate();
				self::_showFullList();
				break;
			case 'edit_column':
				self::_editColumn();
				self::_showFullList();
				break;
			default:
				ds_error('Unknown action name = '.self::$action);
		}
	}

	static private function _execUpdate()
	{
		$log = '';

		foreach (self::$type as $id => $data)
		{

			if (empty($data[min_price_delta]) AND empty($data[max_price_delta]) AND empty($data[tax]))
			{
				continue;
			}
			$tipeArr = explode("/", $id);
			$tipeArrOn = explode("_", $tipeArr[0]);
			$tipeArrBy = explode("_", $tipeArr[1]);

			$tradeOnCatID = (int)$tipeArrOn[0];
			$tradeOnTypeID = (int)$tipeArrOn[1];
			$tradeByCatID = (int)$tipeArrBy[0];
			$tradeByTypeID = (int)$tipeArrBy[1];

			//Добавляем минус так как это нижня граница
			$data['min_price_delta'] = -$data['min_price_delta'];

			//Налог не может быть больше 100
			if ($data['tax'] > 100)
			{
				$data['tax'] = 100;
			}

			//self::$oldData = TypeSearch::GetTradeTypeDataByTYpeIDAndWorldID(self::$DBID, self::$worldID, $id);
			self::$oldData = TypeSearch::GetTradeTypeDataByAllID(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID, $tradeByCatID, $tradeByTypeID);

			//Если старых данных нет
			if (empty(self::$oldData))
			{
				$id = '';
				//Получаем id_tradetype
				$id = TypeSearch::GetIdTradeTypeByAllID(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID, $tradeByCatID, $tradeByTypeID);
				$tradeTypeID = (int) $id[0]['id_tradetype'];

				$categoryOn = TypeSearch::GetNameByCategory(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID);
				$categoryBy = TypeSearch::GetNameByArticleAndTradecategory(self::$DBID, self::$worldID, $tradeByCatID, $tradeByTypeID);

				$log .= 'Set '.$categoryOn.'/'.$categoryBy.' values:<br>min price delta='.$data['min_price_delta'].', max price delta='.$data['max_price_delta'].', tax='.$data['tax'].'<br>';

				//Добавляем новые
				TypeSearch::AddTradeTypeData(self::$DBID, self::$worldID, $tradeTypeID, $data['is_allowed'], $data['max_price_delta'], $data['min_price_delta'], $data['tax']);
			}
			else {
				//Если старые данные есть, смотрим, изменились ли они
				if ($data['tax'] == self::$oldData[0]['tax'] AND $data['max_price_delta'] == self::$oldData[0]['max_price_delta'] AND $data['min_price_delta'] == self::$oldData[0]['min_price_delta'] AND
				$data['is_allowed'] == self::$oldData[0]['is_allowed'])
				{
					continue;
				}
				$id = '';
				//Получаем id_tradetype
				$id = TypeSearch::GetIdTradeTypeByAllID(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID, $tradeByCatID, $tradeByTypeID);
				$tradeTypeID = (int) $id[0]['id_tradetype'];


				$categoryOn = TypeSearch::GetNameByCategory(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID);
				$categoryBy = TypeSearch::GetNameByArticleAndTradecategory(self::$DBID, self::$worldID, $tradeByCatID, $tradeByTypeID);

				$log .= 'Set '.$categoryOn.'/'.$categoryBy.' values:<br>min price delta='.$data['min_price_delta'].', max price delta='.$data['max_price_delta'].', tax='.$data['tax'].'<br>';

				TypeSearch::SetTradeTypeDataAsOldANDInsertTradeTypeData(self::$DBID, self::$worldID, $tradeTypeID, $data['is_allowed'], $data['max_price_delta'], $data['min_price_delta'], $data['tax']);
				//TypeSearch::SetTradeTypeDataAsOld(self::$DBID, self::$worldID, $tradeTypeID);
				//TypeSearch::InsertTradeTypeData(self::$DBID, self::$worldID, $tradeTypeID, $data['is_allowed'], $data['max_price_delta'], $data['min_price_delta'], $data['tax']);
			}
		}

		$worldName = TypeSearch::GetWorldNameByID(self::$DBID, self::$worldID);

		//Логируем действие
		actionLog( "Edit trade types in world '$worldName'", "$log");
	}

	static private function _showEmptyList()
	{
		$worldsData = TypeSearch::GetAllWorldsList();

		$typeData = array();

		self::$out = TypeOut::OutMainScena($worldsData, $typeData, 0, 0);
	}

	static private function _showFullList()
	{
		assert(is_int(self::$worldID) && self::$worldID > 0);
		assert(is_int(self::$DBID) && self::$DBID >= 0);

		$worldsData = TypeSearch::GetAllWorldsList();

		$typeData = TypeFormTableExec::Exec(self::$DBID, self::$worldID);

		self::$out = TypeOut::OutMainScena($worldsData, $typeData, self::$worldID, self::$DBID);
	}

	static private function _showHistoryForm()
	{
		assert(is_int(self::$worldID) && self::$worldID > 0);
		assert(is_int(self::$DBID) && self::$DBID >= 0);
		assert(is_int(self::$ID) && self::$ID > 0);

		$historyData = TypeSearch::GetTradeTypeHistoryData(self::$DBID, self::$worldID, self::$ID);

		self::$out = TypeOut::_outHistoryForm($historyData, self::$DBID, self::$worldID, self::$ID);
	}
	static private function _editColumn()
	{
		assert(is_int(self::$worldID) && self::$worldID > 0);
		assert(is_int(self::$DBID) && self::$DBID >= 0);

		$log = '';

		foreach (self::$type as $id => $newData)
		{
			$tipeArr = explode("/", $id);

			//$tradeOnCatID - тип(ресы/1, арты/2 или юниты/3)
			$tradeOnCatID = (int)$tipeArr[0];

			//$tipeArrBy - тип(ресы/1, арты/2 или юниты/3)
			$tipeArrBy = explode("_", $tipeArr[1]);

			//$tradeByCatID - тип(ресы/1 или деньги/4)
			$tradeByCatID = (int)$tipeArrBy[0];
			//$tradeByTypeID - id категорий(кад, нано или стазис и тд)
			$tradeByTypeID = (int)$tipeArrBy[1];

			$categoryName = TypeSearch::GetCategoryNameByCategoryID(self::$DBID, $tradeOnCatID);

			$dataArr = TypeSearch::GetTypesByCategoryName(self::$DBID, $categoryName);

			foreach ($dataArr as $k => $typeData)
			{
				//$tradeOnTypeID - id категорий(кад/1, нано/2 или стазис и тд)
				$tradeOnTypeID = (int)$typeData['id'];

				self::$oldData = TypeSearch::GetTradeTypeDataByAllID(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID, $tradeByCatID, $tradeByTypeID);

				//Если старых данных нет
				if (empty(self::$oldData))
				{
					$id = '';
					//Получаем id_tradetype
					$id = TypeSearch::GetIdTradeTypeByAllID(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID, $tradeByCatID, $tradeByTypeID);
					$tradeTypeID = (int) $id[0]['id_tradetype'];

					$categoryOn = TypeSearch::GetNameByCategory(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID);
					$categoryBy = TypeSearch::GetNameByArticleAndTradecategory(self::$DBID, self::$worldID, $tradeByCatID, $tradeByTypeID);

					//Если в форме какие-то поля были оставлены пустыми, тогда ставим эти переменные в значения по умолчанию
					if($newData['min_price_delta'] == '')
					{
						$newVal['min_price_delta'] = '20';
					}else
					{
						$newVal['min_price_delta'] = $newData['min_price_delta'];
					}

					if($newData['max_price_delta'] == '')
					{
						$newVal['max_price_delta'] = '20';
					}else {
						$newVal['max_price_delta'] = $newData['max_price_delta'];
					}

					if($newData['tax'] == '')
					{
						$newVal['tax'] = '10';
					}else
					{
						$newVal['tax'] = $newData['tax'];
					}

					//Если  min_price_delta>0, добавляем минус так как это нижня граница
					if($newVal['min_price_delta'] > 0)
					{
						$newVal['min_price_delta'] = -$newVal['min_price_delta'];
					}

					//Налог не может быть больше 100
					if ($newVal['tax'] > 100)
					{
						$newVal['tax'] = 100;
					}

					$log .= 'Set '.$categoryOn.'/'.$categoryBy.' values:<br>min price delta='.$newVal['min_price_delta'].', max price delta='.$newVal['max_price_delta'].', tax='.$newVal['tax'].'<br>';

					//Добавляем новые
					TypeSearch::AddTradeTypeData(self::$DBID, self::$worldID, $tradeTypeID, $newData['is_allowed'], $newVal['max_price_delta'], $newVal['min_price_delta'], $newVal['tax']);
				}
				else {
					//Смотрим, есть ли новые данные, если нет, то оставляем старые
					if($newData['min_price_delta'] == '')
					{
						$newVal['min_price_delta'] = self::$oldData[0]['min_price_delta'];
					}else
					{
						$newVal['min_price_delta'] = $newData['min_price_delta'];
					}

					if($newData['max_price_delta'] == '')
					{
						$newVal['max_price_delta'] = self::$oldData[0]['max_price_delta'];
					}else
					{
						$newVal['max_price_delta'] = $newData['max_price_delta'];
					}

					if($newData['tax'] == '')
					{
						$newVal['tax'] = self::$oldData[0]['tax'];
					}else
					{
						$newVal['tax'] = $newData['tax'];
					}


					//Если  min_price_delta>0, добавляем минус так как это нижня граница
					if($newVal['min_price_delta'] > 0)
					{
						$newVal['min_price_delta'] = -$newVal['min_price_delta'];
					}

					//Налог не может быть больше 100
					if ($newVal['tax'] > 100)
					{
						$newVal['tax'] = 100;
					}
					
					$id = '';
					//Получаем id_tradetype
					$id = TypeSearch::GetIdTradeTypeByAllID(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID, $tradeByCatID, $tradeByTypeID);
					$tradeTypeID = (int) $id[0]['id_tradetype'];


					$categoryOn = TypeSearch::GetNameByCategory(self::$DBID, self::$worldID, $tradeOnCatID, $tradeOnTypeID);
					$categoryBy = TypeSearch::GetNameByArticleAndTradecategory(self::$DBID, self::$worldID, $tradeByCatID, $tradeByTypeID);

					$log .= 'Set '.$categoryOn.'/'.$categoryBy.' values:<br>min price delta='.$newVal['min_price_delta'].', max price delta='.$newVal['max_price_delta'].', tax='.$newVal['tax'].'<br>';

					TypeSearch::SetTradeTypeDataAsOldANDInsertTradeTypeData(self::$DBID, self::$worldID, $tradeTypeID, $newData['is_allowed'], $newVal['max_price_delta'], $newVal['min_price_delta'], $newVal['tax']);
					//TypeSearch::SetTradeTypeDataAsOld(self::$DBID, self::$worldID, $tradeTypeID);
					//TypeSearch::InsertTradeTypeData(self::$DBID, self::$worldID, $tradeTypeID, $newData['is_allowed'], $newVal['max_price_delta'], $newVal['min_price_delta'], $newVal['tax']);
				}
			}
		}
		$worldName = TypeSearch::GetWorldNameByID(self::$DBID, self::$worldID);
		$log .= "World: $worldName";
//echo $log;		
		//Логируем действие
		//actionLog( "Edit trade types", "$log");
	}

	static private function _getParams($data)
	{
		assert(is_array($data));

		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}else{
			self::$action = 'show_empty_list';
		}
		assert(!empty(self::$action));

		if(key_exists('world_id', $data))
		{
			$tmpArray = explode('_', $data['world_id']);
			assert(count($tmpArray) == 2);

			self::$worldID = (int)$tmpArray[1];
			self::$DBID = (int)$tmpArray[0];

			assert(self::$worldID > 0);
			assert(self::$DBID >= 0);
		}

		if(key_exists('type', $data))
		{
			self::$type = $data['type'];
			assert(is_array(self::$type));
		}

		if(key_exists('id', $data))
		{
			self::$ID = (int)$data['id'];
			assert(self::$ID > 0);
		}

		if(key_exists('min_price_delta', $data))
		{
			self::$minPriceDelta = abs((float)$data['min_price_delta']);
		}

		if(key_exists('max_price_delta', $data))
		{
			self::$maxPriceDelta = abs((float)$data['max_price_delta']);
		}

		if(key_exists('tax', $data))
		{
			self::$tax = abs((float)$data['tax']);
		}

		if(key_exists('is_allowed', $data))
		{
			self::$isAllowed = $data['is_allowed'];
		}
	}
}


?>