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
	
	//static private $minPriceDelta;	
	//static private $maxPriceDelta;	
	//static private $tax;
	//static private $isAllowed;
	
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
			default:
				ds_error('Unknown action name = '.self::$action);
		}
	}
	
	static private function _execUpdate()	
	{
		foreach (self::$type as $id => $data)
		{
			if($data['min_price_delta'] > 100)
			{
				$data['min_price_delta'] = 100;
			}
			
			if($data['max_price_delta'] > 100)
			{
				$data['max_price_delta'] = 100;
			}
			
			//ƒќбавл€ем минус так как это нижн€ граница
			$data['min_price_delta'] = -$data['min_price_delta'];
			
			self::$oldData = TypeSearch::GetTradeTypeDataByTYpeIDAndWorldID(self::$DBID, self::$worldID, $id);

				if ($data['tax'] == self::$oldData['tax'] AND $data['max_price_delta'] == self::$oldData['max_price_delta'] AND $data['min_price_delta'] == self::$oldData['min_price_delta'] AND 
$data['is_allowed'] == self::$oldData['is_allowed'])
				{
					continue;
				}

if ($data['max_price_delta'] > 100)
{
	$data['max_price_delta'] = 100;
}
if (abs($data['min_price_delta']) > 100)
{
	$data['min_price_delta'] = -100;
}
			TypeSearch::SetTradeTypeDataAsOld(self::$DBID, self::$worldID, $id);
			TypeSearch::InsertTradeTypeData(self::$DBID, self::$worldID, $id, $data['is_allowed'], $data['max_price_delta'], $data['min_price_delta'], $data['tax']);			
		}
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