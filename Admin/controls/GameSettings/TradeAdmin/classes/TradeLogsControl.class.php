<?php

class TradeLogsControl
{
	static private $out = '';
	static private $action = 'search';
	static private $worldID;
	static private $dbID;
	static private $scopes;
	static private $catecories;
	static private $recources;
	static private $money;
	static private $courseRecount;
	static private $catecoriesfor;
	static private $userName = '';
	static private $artName = '';
	static private $startDate = '';
	static private $endDate = '';
	static private $offset = 0;
	static private $limit = 30;
	static private $idTrade;
	static private $ipAddress;
	
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
			case 'search':
				self::_showSearchMenu();
				break;

			case 'trade_log':
				self::_showSearchMenu();
				self::_showTradeLog();
				break;

			case 'edit_recount':
				self::_editRecount();
				self::_showSearchMenu();
				self::_showTradeLog();
				break;

			case 'import_csv':
				self::_showSearchMenu();
				self::_importCsv();
				self::_showTradeLog();
				break;

			case 'show_bid':
				self::_showBid();
				break;

			default:
				break;
		}
	}

	static private function _showSearchMenu()
	{
		$worlds = TypeSearch::GetAllWorldsList();

		$scopes = TypeSearch::GetAllScopeList();

		$catecories = TypeSearch::GetTableLeftColumnCategorys(0);

		$catecoriesfor = TypeSearch::GetTableUpRowsCategorys(0);		

		//Если у нас нет текущего мира, то выбираем первый из списка миров
		if (empty(self::$dbID))
		{
			self::$dbID = $worlds[0]['dbid'];
		}

		$races = TypeSearch::getUnitRaces(0);
		$classes = TypeSearch::getUnitClasses(0);
		
		$artscopes = TradeLogSearch::getArtefactScopes(0);
		$sizes = TradeLogSearch::getArtefactSizes(0);

		$recources = TradeLogSearch::GetAllResources(self::$dbID);
		$money = TradeLogSearch::GetAllManey(0);
		self::$out = TradeLogOut::outLogSearch($worlds, $_SESSION['world'], $scopes, $catecories, $catecoriesfor, $recources, $money, $races, $classes, $artscopes, $sizes);
	}

	static private function _showTradeLog()
	{
		$pageCount = TradeLogSearch::getTradesLogsCount();
		$pageCount = ceil($pageCount / $_SESSION['limit']);
		
		if (!empty($_SESSION['user']))
		{
			$pageCount = $pageCount/2;
		}
		
		$tradeLog = TradeLogSearch::getTradesLogs($_SESSION['limit'], self::$offset);

		self::$out .= TradeLogOut::outTradeLog($tradeLog, $pageCount, $_SESSION['limit']);
	}

	static private function _editRecount()
	{
		$log = '';

		foreach(self::$courseRecount as $idTrade => $state)
		{
			if (!key_exists('state', $state))
			{
				$state['state'] = 'f';
			}

			$oldSate = TradeLogSearch::getStateOfCourseRecountByIdTrade($_SESSION["dbId"], $idTrade);

			if($state['state'] == $oldSate)
			{
				continue;
			}

			TradeLogSearch::changeStateOfCourseRecountByIdTrade($_SESSION["dbId"], $idTrade, $state['state']);

			$log .= "Set value=".$state['state']." where Id trade=$idTrade<br>";
		}

		//Логируем действие
		if ($log != '')
		{
			actionLog( "Edit Course recount", $log);
		}
	}

	static private function _importCsv()
	{
		//$tradeLog = TradeLogSearch::getTradesLogs($_SESSION['user'], $_SESSION['startDate'], $_SESSION['endDate'], 0, self::$offset,  $_SESSION["dbId"], $_SESSION['worldID'], $_SESSION['scopes'], $_SESSION['catecories'], $_SESSION['catecoriesfor'],  $_SESSION['recources'],  $_SESSION['money']);
		$tradeLog = TradeLogSearch::getTradesLogs($_SESSION['user'], $_SESSION['startDate'], $_SESSION['endDate'], 0, self::$offset,  $_SESSION["dbId"], $_SESSION['worldID'], $_SESSION['scopes'], $_SESSION['catecories'], $_SESSION['catecoriesfor'], $_SESSION['recources'], $_SESSION['recourcesfor'], $_SESSION['money'], $_SESSION['class'], $_SESSION['race'], $_SESSION['unitName'], $_SESSION['artscope'], $_SESSION['size']);

		$file = $_SERVER['DOCUMENT_ROOT']."/ds/utils/files/TradeLog.csv";

		if (!$fp = fopen($file, 'w'))
		{
			echo "Не могу открыть файл ($file)";
			exit;
		}

		if ($_SESSION['user'] != "")
		{
			$a = "for user ".$_SESSION['user'];
		}else {
			$a = "";
		}

		//Записываем в файл заголовок
		$title = array('Data '.$a.' from '.date('Y-d-m',$_SESSION['startDate']).' to '.date('Y-d-m',$_SESSION['endDate']));
		
		fputcsv($fp, $title, ";");

		//Записываем в файл имена полей
		$fildName = array('Start date', 'End date', 'Customer', 'Customer comb', 'Seller ', 'Seller comb', 'Amount Sell', 'Sell', 'Amount sell for', 'Sell for', 'Tax', 'Scope', 'Course', 'Course difference', 'Course recount');
		fputcsv($fp, $fildName, ";");

		foreach ($tradeLog as $key => $arr)
		{
			$combToArr = explode("\n",$arr['comb_to']);			
			
			$combFromArr = explode("\n",$arr['comb_from']);			
			$combTo = trim($combToArr['1']);
			$combFrom = trim($combFromArr['1']);
			$couse = $arr['to_ammount']/$arr['from_ammount'];
			$courseDif = $arr['to_ammount']/$arr['from_ammount'] - $arr['cource'];

			$line = array($arr['start_date'], $arr['end_date'], $arr['customer'], $combTo, $arr['seller'], $combFrom, number_format($arr['from_ammount'], 0, ',', " "), $arr['goods_from'], number_format($arr['to_ammount'], 0, ',', " "), $arr['goods_to'], number_format($arr['tax_summ'], 5, ',', ""), $arr['tradescopename'], number_format($couse, 5, ",", " "), number_format($courseDif, 5, ",", " "), $arr['course_recount']);

			fputcsv($fp, $line, ";");
		}

		fclose($fp);
	}	
	
	static private function _showBid()
	{
		assert(is_int(self::$idTrade) && self::$idTrade > 0);
		$bidLog =  TradeLogSearch::GetBits(self::$idTrade);
		
		self::$out = TradeLogOut::outLogBid($bidLog);
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
			$_SESSION['world'] = self::$dbID.'_'.self::$worldID;
			$_SESSION["dbId"] = self::$dbID;
			$_SESSION['worldID'] = self::$worldID;
		} 

		if(key_exists('user', $data))
		{
			self::$userName = pg_escape_string(trim($data['user']));
			$_SESSION['user'] = pg_escape_string(trim($data['user']));
		} 

		if(key_exists('from_y', $data) && key_exists('from_m', $data) && key_exists('from_d', $data))
		{
			$_SESSION['from_y'] = (int)$data['from_y'];
			$_SESSION['from_m'] = (int)$data['from_m'];
			$_SESSION['from_d'] = (int)$data['from_d'];
			
			$_SESSION['startDate'] = mktime(0, 0, 0, (int)$data['from_m'], (int)$data['from_d'], (int)$data['from_y']);
			self::$startDate = mktime(0, 0, 0, (int)$data['from_m'], (int)$data['from_d'], (int)$data['from_y']);
		}

		if(key_exists('to_y', $data) && key_exists('to_m', $data) && key_exists('to_d', $data))
		{
			$_SESSION['to_y'] = (int)$data['to_y'];
			$_SESSION['to_m'] = (int)$data['to_m'];
			$_SESSION['to_d'] = (int)$data['to_d'];

			$_SESSION['endDate'] = mktime(23, 59, 59, (int)$data['to_m'], (int)$data['to_d'], (int)$data['to_y']);
			self::$endDate = mktime(23, 59, 59, (int)$data['to_m'], (int)$data['to_d'], (int)$data['to_y']);
		} 
		
		if(key_exists('offset', $data))
		{
			self::$offset = (int)$data['offset'];
			$_SESSION['page'] = (int)self::$offset/$_SESSION['limit'];

			assert(self::$offset >= 0);
		} else {
			self::$offset = 0;
		}

		if(key_exists('scopes', $data))
		{
			self::$scopes = $data['scopes'];
			$_SESSION['scopes'] = $data['scopes'];
		} 

		if(key_exists('catecories', $data))
		{
			self::$catecories = $data['catecories'];
			$_SESSION['catecories'] = $data['catecories'];
		} 

		if(key_exists('catecoriesfor', $data))
		{
			self::$catecoriesfor = $data['catecoriesfor'];
			$_SESSION['catecoriesfor'] = $data['catecoriesfor'];
		} 

		if(key_exists('courseRecount', $data))
		{
			self::$courseRecount = $data['courseRecount'];
		}

		if(key_exists('limit', $data))
		{
			$_SESSION['limit'] = $data['limit'];
			
			if ($data['limit'] == 'All')
			{
				$data['limit'] = 0;
				$_SESSION['limit'] = 0;
			}
			self::$limit = $data['limit'];
		}
		
		if(key_exists('recources', $data))
		{
			self::$recources = $data['recources'];
			$_SESSION['recources'] = $data['recources'];
		}
		
		if(key_exists('recourcesfor', $data))
		{
			self::$recources = $data['recourcesfor'];
			$_SESSION['recourcesfor'] = $data['recourcesfor'];
		} 
		
		if(key_exists('money', $data))
		{
			self::$money = $data['money'];
			$_SESSION['money'] = $data['money'];
		} 
		
		if(key_exists('unitName', $data))
		{
			self::$money = $data['unitName'];
			$_SESSION['unitName'] = $data['unitName'];
		} 
		
		if(key_exists('artName', $data))
		{
			self::$artName = $data['artName'];
			$_SESSION['artName'] = $data['artName'];
		} 
		
		if(key_exists('class', $data))
		{
			self::$money = $data['class'];
			$_SESSION['class'] = $data['class'];
		} 
		
		if(key_exists('race', $data))
		{
			self::$money = $data['race'];
			$_SESSION['race'] = $data['race'];
		} 
		
		if(key_exists('artscope', $data))
		{
			self::$money = $data['artscope'];
			$_SESSION['artscope'] = $data['artscope'];
		} 
		
		if(key_exists('size', $data))
		{
			self::$money = $data['size'];
			$_SESSION['size'] = $data['size'];
		}
		
		if(key_exists('lt', $data))
		{
			self::$money = $data['lt'];
			$_SESSION['lt'] = $data['lt'];
		}

		if(key_exists('gt', $data))
		{
			self::$money = $data['gt'];
			$_SESSION['gt'] = $data['gt'];
		} 
		
		if(key_exists('firstSign', $data))
		{
			self::$money = $data['firstSign'];
			$_SESSION['firstSign'] = $data['firstSign'];
		} 
		
		if(key_exists('secondSign', $data))
		{
			self::$money = $data['secondSign'];
			$_SESSION['secondSign'] = $data['secondSign'];
		} 
			
		if(key_exists('condition', $data))
		{
			self::$money = $data['condition'];
			$_SESSION['condition'] = $data['condition'];
		} 
		
		if(key_exists('ipAddress', $data))
		{
			self::$ipAddress = $data['ipAddress'];
			$_SESSION['ipAddress'] = $data['ipAddress'];
		} 
		
		if(key_exists('idTrade', $data))
		{
			self::$idTrade = (int)$data['idTrade'];
		} 
		
		if(key_exists('showSameIp', $data))
		{
			$_SESSION['showSameIp'] = 1;
		} else {
			$_SESSION['showSameIp'] = 0;
		}
		
		if(key_exists('AuctionOnly', $data))
		{
			$_SESSION['AuctionOnly'] = 1;
		} else {
			$_SESSION['AuctionOnly'] = 0;
		}
	}
}

?>
									