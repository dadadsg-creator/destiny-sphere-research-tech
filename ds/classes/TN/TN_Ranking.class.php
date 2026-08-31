<?php
/*
*
* Класс для вывода рейтинга завершенных турниров
*
*
*/
class TN_Ranking
{
	static private $admin = false;

	// Текущий турнир
	static private $turnirID = 0;

	// Текущая база
	static private $dbId = 0;
	
	// Количество игроков на странице
	static private $limit = 25;
	
	// Текущий игрок
	static private $username = "";
	
	// Выбранный игрок
	static private $playerSearch = "";
	
	// Смещение
	static private $offset = 0;
	
	// Показать страницу с данным игроком
	static private $showCurrentUser = true;
	
	// По какому полю сортировать
	static private $sort = 'battle_xp';
	
	static public function Exec(array $data)
	{
		self::_getParams($data);
		
		self::_show();
	}
	
	static private function _getParams(array $data)
	{
		// Текущий игрок
		if(isset($_SESSION["USERNAME"]) && !isset($data["player"]))
		{
			self::$showCurrentUser = true;
			self::$username = $_SESSION["USERNAME"];
		}

		// Выбранный игрок
		if(isset($data["player"]))
		{
			 self::$playerSearch = $data["player"];
		}

		// Турнирный мир
		if(isset($data["id_turnir"]))
		{
			self::$turnirID = (int)$data["id_turnir"];
		} 

		// Смещение
		if(isset($data["offset"]))
		{
			self::$showCurrentUser = false;
			self::$offset = (int)$data["offset"];
		} 
		
		// Сортировка
		if(isset($data["sort"]))
		{
			self::$sort = $data["sort"];
		} 
	}
	
	static private function _show()
	{
		// Получаем список всех завершенных турниров
		$worldListArr = TN_Search::GetFinishedTurnirs();
		foreach ($worldListArr as $k => $d)
		{
			$worldList[$d['id_turnir']] = $d;
		}
		
		// Получаем рейтинг
		$results = TN_Search::GetTurnirResults(self::$turnirID, self::$sort);	
		$count = sizeof($results);

		// Если у нас есть текущий игрок и не задано смещение
		if (!empty(self::$playerSearch) && self::$showCurrentUser === true)
		{
			$e = 0;
			$a = 0;
			for($i=0; $i<sizeof($results);$i++)
			{
				$e++;
				$c = 0;
				if($e == self::$limit)
				{
					$a++;
					$e = 0;
					$c = 1;
				}

				if($results[$i]['username'] == self::$playerSearch)
				{
					if($c == 1)
					{
						$a--;
					}

					self::$offset = ($a)*self::$limit;

					if(self::$offset < 0)
					{
						self::$offset = 0;
					}
				}
			}
		}
		// Получаем рейтинг с учетом смещения
		$results = TN_Search::GetTurnirResults(self::$turnirID, self::$sort, self::$limit, self::$offset);		
		$tpl = new Smarty;
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = constant('SMARTY_CACHE_PATH');
		$tpl->compile_check = true;
		$tpl->debugging = false;
	
		$tpl->assign('TURNIR_WORLDS', $worldList);
		$tpl->assign('CURRENT_WORLDNAME', $worldList[self::$turnirID]['turnir_name']);
		$tpl->assign('CURRENT_DBID', self::$dbId);
		$tpl->assign('CURRENT_TURNIRID', self::$turnirID);
		$tpl->assign('TURNIR_DURATION', "");
		if (!empty($worldList[self::$turnirID]['start_date'])) {
			$tpl->assign('TURNIR_DURATION', $worldList[self::$turnirID]['start_date']." - ".$worldList[self::$turnirID]['end_date']);
		} 
		$tpl->assign('CURRENT_USERNAME', self::$username);
		$tpl->assign('CURRENT_OFFSET', self::$offset);
		include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/ranking_turnir.lang");
		$tpl->assign('lang', $lang);
		
		/**
		 * ButtonLeft
		 */
		if(self::$offset - self::$limit >= 0)
		{
			$button_left = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?id_turnir=".self::$turnirID."&offset=".( self::$offset - self::$limit >= 0 ? self::$offset - self::$limit : 0 )."&sort=".self::$sort."&player=".self::$playerSearch."\">
			<img src='../img/buttons/btn_prev.gif' border=0></a>";
		} else {
			$button_left = "<img src='../img/buttons/btn_prev.gif' border=0>";
		}

		/**
		 * FirstPage
		 */
		if ( self::$offset - self::$limit >= 0 )
		{
			$fist_page = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?id_turnir=".self::$turnirID."&offset=0&player=".self::$playerSearch."\" class=wnd_gen_txt_large><img src=\"../img/elements/icon_minipost.gif\" width=12 height=9 border=0><b>1</b></a>";
		} else {
			$fist_page = "&nbsp;";
		}

		/**
		 * CurrentPage
		 */
		$current_page = "&nbsp;";
		$a = 0;
		for ($i=0; $i<=$count; $i=$i+self::$limit)
		{
			$a++;
			if ( $i == self::$offset )
			{
				$current_page = "<img src=\"../img/elements/icon_newest_reply.gif\" width=12 height=9 border=0>".$a;
			}
		}

		/**
		 * LastPage
		 */
		if (self::$offset + self::$limit < $count )
		{
			$last_page = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?id_turnir=".self::$turnirID."&offset=".((ceil($count/self::$limit)-1)*self::$limit)."&sort=".self::$sort."&player=".self::$playerSearch."\ class=wnd_gen_txt_large><img src=\"../img/elements/icon_minipost.gif\" width=12 height=9 border=0><b>".ceil($count/self::$limit)."</b></a>";
		} else {
			$last_page = "&nbsp;";
		}

		/**
		 * ButtonRight
		 */
		if ( self::$offset + self::$limit < $count )
		{
			$button_right = "<a href=\"".basename($_SERVER['SCRIPT_NAME'])."?id_turnir=".self::$turnirID."&offset=".( self::$offset + self::$limit >= $count ? self::$offset : self::$offset + self::$limit )."&sort=".self::$sort."&player=".self::$playerSearch."\"><img src='../img/buttons/btn_next.gif' border=\"0\"></a>";
		} else {
			$button_right = "<img src='../img/buttons/btn_next.gif' border=\"0\">";
		}
		
		$navigation = array(
			'button_left' => $button_left,
			'fist_page' => $fist_page,
			'current_page' => $current_page,
			'last_page' => $last_page,
			'button_right' => $button_right
		);
		$tpl->assign('NAVIGATION_ARRAY', $navigation);
			
		// Втавляем нумерацию строк
		$ofs = self::$offset;
		foreach ($results as $key => $val) {
			$ofs++;
			$results[$key]['row'] = $ofs;
		}	
		$tpl->assign('ROW_ELEMENT', $results);

		$tpl->display('ranking_turnir.tpl');
	}
}

?>