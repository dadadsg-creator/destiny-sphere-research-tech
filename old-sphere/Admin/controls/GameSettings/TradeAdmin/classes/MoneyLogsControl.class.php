<?php

class MoneyLogsControl
{
	static private $out = '';

	static private $action = 'search';

	static private $userName = '';

	static private $actionTypes = '';

	static private $startDate = '';

	static private $endDate = '';

	static private $offset = 0;

	static private $limit = 50;

	static private $_SESSION = array();

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
				self::_showMoneyLog();
				break;

			case 'import_csv':
				self::_showSearchMenu();
				self::_importCsv();
				break;

			default:
				break;
		}
	}

	static private function _showSearchMenu()
	{
		$worlds = MoneyLogSearch::GetAllWorldsList();
		$actionTypes = MoneyLogSearch::getActionType(0);

		self::$out = MoneyLogOut::outLogSearch($worlds, $actionTypes);
	}

	static private function _showMoneyLog()
	{
		//Если у нас не выбран ни один action type
		if (empty($_SESSION['actionTypes'])) {
			echo '<h2><font color="Red">Select Actions!!</font></h2>';
			return;
		}
		if (empty($_SESSION['worlds'])) {
			echo '<h2><font color="Red">Select Worldss!!</font></h2>';
			return;
		}
		
		/* $pageCount = MoneyLogSearch::getMoneyLogsCount();
		$pageCount = ceil($pageCount / $_SESSION['limit']);*/

		$moneyLog = MoneyLogSearch::getMoneyLogs(self::$offset);

		//Сортируем лог по дате
		$moneyLog = self::sortArreyByDate($moneyLog);

		//Получаем текущее состояние счета
		$currentAccount = MoneyLogSearch::getCurrentAccount();		

		if ($_SESSION['limit'] == 'All')
		{
			$pageCount = 1;
		}
		$pageCount = count($moneyLog);
		$pageCount = ceil($pageCount / $_SESSION['limit']);

		self::$out .= MoneyLogOut::outMoneyLog($currentAccount, $moneyLog, $pageCount, self::$offset);
	}

	static private function sortArreyByDate($moneyLog)
	{
		foreach ($moneyLog as $key => $data)
		{
			$moneyLog1[strtotime($data['add_date'])][] = $data;
		}

		//Сортируем массив по кдючу
		krsort($moneyLog1);

		foreach ($moneyLog1 as $key => $data)
		{
			foreach ($data as $k => $v)
			{
				$moneyLog2[] = $v;
			}
		}

		return $moneyLog2;
	}

	static private function _importCsv()
	{
		//Получаем текущее состояние счета
		$currentAccount = MoneyLogSearch::getCurrentAccount();
		
		//Получаем мани лог
		$moneyLog = MoneyLogSearch::getMoneyLogs(self::$offset);

		//Сортируем лог по дате
		$moneyLog = self::sortArreyByDate($moneyLog);

		/* Пишем в файл */
		$fileurl = "/Admin/controls/GameSettings/Localization/Export_CVS/data/MoneyLogs.csv";
		$file = $_SERVER['DOCUMENT_ROOT'].$fileurl;
		$mode = 'w';                                                                  

		if(!file_exists($file))
		{
			$mode = 'w+';
		} else {
			unlink($file);
		}

		if(!$fp = fopen($file, $mode))
		{
			echo "Не могу открыть файл $file";
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
		$fildName = array('World', 'User from', 'User to', 'Date', 'Action ', 'GC', 'SC', 'Detail');
		fputcsv($fp, $fildName, ";");

		foreach ($moneyLog as $key => $arr)
		{
			$line = array($arr['worldname'], $arr['user_from'], $arr['user_to'], $arr['add_date'], $arr['action_name'], number_format($arr['gc_delta'], 0, ',', " "), number_format($arr['sc_delta'], 0, ',', " "), $arr['detail']);
			fputcsv($fp, $line, ";");
		}

		fclose($fp);
		$pageCount = count($moneyLog);
		$pageCount = ceil($pageCount / $_SESSION['limit']);

		self::$out .= MoneyLogOut::outMoneyLog($currentAccount, $moneyLog, $pageCount, self::$offset);

		//self::$out .= MoneyLogOut::outMoneyLog($currentAccount, $finalStat, $pageCount, $_SESSION['limit'], self::$offset, $_SESSION['world'], $totalGC, $totalSC);
	}


	static private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}

		if(key_exists('worlds', $data))
		{
			$_SESSION['worlds'] = $data['worlds'];
		}

		if(key_exists('user', $data))
		{
			self::$userName = pg_escape_string(trim($data['user']));
			$_SESSION['user'] = self::$userName;
		}

		if(key_exists('from_y', $data) && key_exists('from_m', $data) && key_exists('from_d', $data))
		{
			$_SESSION['from_y'] = (int)$data['from_y'];
			$_SESSION['from_m'] = (int)$data['from_m'];
			$_SESSION['from_d'] = (int)$data['from_d'];

			self::$startDate = mktime(0, 0, 0, (int)$data['from_m'], (int)$data['from_d'], (int)$data['from_y']);
			$_SESSION['startDate'] = mktime(0, 0, 0, (int)$data['from_m'], (int)$data['from_d'], (int)$data['from_y']);
		}

		if(key_exists('to_y', $data) && key_exists('to_m', $data) && key_exists('to_d', $data))
		{
			$_SESSION['to_y'] = (int)$data['to_y'];
			$_SESSION['to_m'] = (int)$data['to_m'];
			$_SESSION['to_d'] = (int)$data['to_d'];

			self::$endDate = mktime(23, 59, 59, (int)$data['to_m'], (int)$data['to_d'], (int)$data['to_y']);
			$_SESSION['endDate'] = mktime(23, 59, 59, (int)$data['to_m'], (int)$data['to_d'], (int)$data['to_y']);
		}

		if(key_exists('offset', $data))
		{
			self::$offset = (int)$data['offset'];
			$_SESSION['page'] = (int)self::$offset/$_SESSION['limit'];
			assert(self::$offset >= 0);
		} else {
			self::$offset = 0;
		}

		if(key_exists('actionTypes', $data))
		{
			self::$actionTypes = $data['actionTypes'];
			$_SESSION['actionTypes'] = self::$actionTypes;
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
	}
}

?>
