<?php

class MoneyLogsControl
{
	static private $out = '';
	
	static private $action = 'index';
	
	static private $worldID;
	
	static private $dbID;
	
	static private $userName = '';
	
	static private $startDate = '';
	
	static private $endDate = '';
	
	static private $offset = 0;
	
	static private $limit = 30;
	
	static private $selected = array();
	
	static public function Exec($data)
	{
		assert(is_array($data));
		
		self::_getParams($data);
		
		self::_execAction();
		
		return self::$out;
	}
	
	static private function _execAction()
	{
		switch (self::$action) {
			case 'index':
				self::_showIndex();
				break;
		
			case 'search':
				self::_showSearchMenu();
				break;
				
			case 'trade_log':
				self::_showMoneyLog();
				break;
				
			default:
				break;
		}
	}
	
	static private function _showIndex()
	{
		$worlds = MoneyLogSearch::GetAllWorldsList();

		self::$out = MoneyLogOut::outIndex($worlds);
	}
	
	static private function _showSearchMenu()
	{
		self::$out = MoneyLogOut::outLogSearch(self::$selected, self::$dbID.'_'.self::$worldID);
	}
	
	static private function _showMoneyLog()
	{
		$tradeLog = array();
		
		$pageCount = MoneyLogSearch::getMoneyLogsCount(self::$userName, self::$startDate, self::$endDate, self::$dbID, self::$worldID);
		$pageCount = ceil($pageCount / self::$limit);
		
		$moneyLog = MoneyLogSearch::getMoneyLogs(self::$userName, self::$startDate, self::$endDate, self::$limit, self::$offset, self::$dbID, self::$worldID);
		
		$actionHandlers = array
		(
			'ENTER_MONEY' 		=> 'UC_StatEnterMoney',
			'EXCHANGE_CREDITS' 	=> 'UC_StatExchange',
			'MOVE_FROM_RESERV' 	=> 'UC_StatNull',
			'MOVE_TO_RESERV' 	=> 'UC_StatNull',
			'PAY_TRADE_TAX' 	=> 'UC_StatTrade',
			'RECIVE_INTERWORLD_TRANSFER' 	=> 'UC_StatReciveFromWorld',
			'SEND_INTERWORLD_TRANSFER' 		=> 'UC_StatSendToWorld',
			'TRADE_PAYMENT' 	=> 'UC_StatTrade',
		);
		
		$finalStat = array();
		
		$totalGC = 0;
		$totalSC = 0;
		
		foreach($moneyLog as $k => $moneyList)
		{
			if(!key_exists($moneyList['action_name'], $actionHandlers))
			{
				$handlerClass = 'UC_StatNull';
			} else {
				$handlerClass = $actionHandlers[$moneyList['action_name']];
			}
			
			$handler = new $handlerClass($moneyList);
			$stat = $handler->formStat();

			if(is_array($stat) && count($stat))
			{
				$stat['USER_FROM'] = $moneyList['user_from'];
				$stat['USER_TO'] = $moneyList['user_to'];
				$stat['ID_LIST'] = $moneyList['id_list'];
				$stat['GC'] = $moneyList['gc_delta'];
				$totalGC += $moneyList['gc_delta'];
				$stat['SC'] = $moneyList['sc_delta'];
				$totalSC += $moneyList['sc_delta'];
				$finalStat[] = $stat;
			}
		}
		
		self::$out = MoneyLogOut::outMoneyLog($finalStat, $pageCount, self::$selected, self::$limit, self::$dbID.'_'.self::$worldID, $totalGC, $totalSC);
	}
	
	
	static private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}
		
		if(key_exists('world', $data))
		{
			$arr = explode('_', $data['world']);
			self::$dbID = (int)$arr[0];
			assert(self::$dbID >= 0);
			
			self::$worldID = (int)$arr[1];
			assert(self::$worldID > 0);
		}
		
		if(key_exists('user', $data))
		{
			self::$userName = pg_escape_string(trim($data['user']));
			self::$selected['user'] = self::$userName;
		} else {
			self::$selected['user'] = '';
		}
		
		if(key_exists('from_y', $data) && key_exists('from_m', $data) && key_exists('from_d', $data))
		{
			self::$selected['from_y'] = (int)$data['from_y'];
			self::$selected['from_m'] = (int)$data['from_m'];
			self::$selected['from_d'] = (int)$data['from_d'];
			
			self::$startDate = mktime(0, 0, 0, (int)$data['from_m'], (int)$data['from_d'], (int)$data['from_y']);
			// self::$startDate = " AND start_date > to_timestamp(".self::$startDate.")";
		} else {
			self::$selected['from_y'] = '';
			self::$selected['from_m'] = '';
			self::$selected['from_d'] = '';
		}
		
		if(key_exists('to_y', $data) && key_exists('to_m', $data) && key_exists('to_d', $data))
		{
			self::$selected['to_y'] = (int)$data['to_y'];
			self::$selected['to_m'] = (int)$data['to_m'];
			self::$selected['to_d'] = (int)$data['to_d'];
			
			self::$endDate = mktime(0, 0, 0, (int)$data['to_m'], (int)$data['to_d'], (int)$data['to_y']);
			// self::$startDate = " AND start_date > to_timestamp(".self::$startDate.")";
		} else {
			self::$selected['to_y'] = '';
			self::$selected['to_m'] = '';
			self::$selected['to_d'] = '';
		}
		
		if(key_exists('offset', $data))
		{
			self::$offset = (int)$data['offset'];
			self::$selected['page'] = (int)self::$offset/self::$limit;;
			assert(self::$offset >= 0);
		} else {
			self::$selected['page'] = 0;
		}

	}
}

?>