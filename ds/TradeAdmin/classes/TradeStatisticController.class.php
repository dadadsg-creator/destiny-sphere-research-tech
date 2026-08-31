<?php
class TradeStatisticController
{
	static private $out = '';

	static private $scopeID = '';

	static private $scopeName = '';

	static private $action = 'index';

	static private $worldID;

	static private $worldArr = array();

	static private $dbID;

	static private $startDate = '';

	static private $endDate = '';

	static private $subject = '';

	static private $selected = array();


	static public function Exec ($data)
	{
		assert(is_array($data));

		self::_getParams($data);

		self::_execAction();

		return self::$out;
	}

	static private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}

		if(key_exists('world', $data))
		{
			/*echo "<xmp>";
			print_r($data['world'])	;
			echo "</xmp>";*/
			self::$worldArr = $data['world'];

			//$arr = explode('_', $data['world']);
			//self::$dbID = (int)$arr[0];
			//assert(self::$dbID >= 0);

			//self::$worldID = (int)$arr[1];
			//assert(self::$worldID > 0);
		}

		if(key_exists('subject', $data))
		{
			self::$selected['subject'] = $data['subject'];
			self::$subject = $data['subject'];
		}

		if(key_exists('scope', $data))
		{
			if ($data['scope'] == 'ALL')
			{
				self::$scopeName = 'ALL';
				self::$scopeID = 'ALL';
			}else
			{
				$arr = explode('-', $data['scope']);
				self::$scopeID = (int)$arr[0];
				assert(self::$scopeID >= 0);

				self::$scopeName = $arr[1];
			}
			assert(!empty(self::$scopeName));
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
	}

	static private function _execAction()
	{
		switch (self::$action) {
			case 'index':
				self::_showSearchMenu();
				break;

			case 'trade_log':
				self::_showSearchMenu();
				self::_showTradeStatistic();
				break;

			default:
				break;
		}
	}

	static private function _showIndex()
	{
		$worlds = TradeStatisticSearch::GetAllWorldsList();

		self::$out = TradeStatisticOut::outIndex($worlds);
	}
	static private function _showSearchMenu()
	{
		$worlds = TradeStatisticSearch::GetAllWorldsList();

		$scopes = TradeStatisticSearch::GetScopeList();

		self::$out = TradeStatisticOut::outLogSearch($worlds, self::$selected, self::$worldArr, $scopes, self::$subject, self::$scopeName);
	}
	static private function _showTradeStatistic()
	{
		$tradeStat = array();
		$stat = array();
		echo '+';
print_r(self::$worldArr);
		echo '+';

		if (self::$worldArr == '')
		{
			echo "Choose world!";
			exit;
		}
		
		foreach (self::$worldArr as $key =>$world)
		{
			$tradeStat = TradeStatisticSearch::GetStat(self::$subject, self::$startDate, self::$endDate, $world, self::$scopeID);

			foreach ($tradeStat as $key => $data)
			{
				if(key_exists($data['goods_from']."/".$data['goods_to'], $stat))
				{
					$stat[$data['goods_from']."/".$data['goods_to']]['ammounf_from'] += $data['ammounf_from'];
					$stat[$data['goods_from']."/".$data['goods_to']]['ammount_to'] += $data['ammount_to'];
				}
				else {
					$stat[$data['goods_from']."/".$data['goods_to']]['ammounf_from'] = $data['ammounf_from'];
					$stat[$data['goods_from']."/".$data['goods_to']]['ammount_to'] = $data['ammount_to'];
					$stat[$data['goods_from']."/".$data['goods_to']]['goods_from'] = $data['goods_from'];
					$stat[$data['goods_from']."/".$data['goods_to']]['goods_to'] = $data['goods_to'];
				}
			}
		}

		foreach ($stat as $k => $data)
		{
			$OutStat[] = $data;
		}

		self::$out .= TradeStatisticOut::outStat($OutStat, self::$selected, self::$scopeName);
	}
}
?>