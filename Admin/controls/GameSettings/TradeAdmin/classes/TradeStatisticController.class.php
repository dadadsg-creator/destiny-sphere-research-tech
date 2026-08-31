<?php

require_once "../classes/TypeSearch.class.php";

class TradeStatisticController
{
	static private $action = 'index';
	static private $out = '';

	//static private $idFromArticle;
	//static private $idFromCategory;
	//static private $goodsFrom;
	static private $cources = array();

	static public function Exec ($data)
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
				self::_showSearchMenu();
				break;

			case 'trade_log':
				self::_showSearchMenu();
				self::_showTradeStatistic();
				break;

			case 'recount':
				self::_recount();
				break;

			case 'exec_recount':
				self::_execRecount();
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
		if (empty($_SESSION['dbId']))
		{
			$_SESSION['dbId'] = $worlds[0]['dbid'];
		}
		$races = TypeSearch::getUnitRaces(0);
		$classes = TypeSearch::getUnitClasses(0);
		$artscopes = TypeSearch::getArtefactScopes(0);
		$sizes = TypeSearch::getArtefactSizes(0);
		$recources = TypeSearch::GetAllResources($_SESSION['dbId']);
		$money = TypeSearch::GetAllManey(0);

		self::$out = TradeStatisticOut::outLogSearch(   $worlds,
		$_SESSION['world'],
		$scopes,
		$catecories,
		$catecoriesfor,
		$recources,
		$money,
		$races,
		$classes,
		$artscopes,
		$sizes);

	}
	static private function _showTradeStatistic()
	{
		$tradeStat = array();
		$stat = array();

		$tradeStat = TradeStatisticSearch::GetStat();

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
				$stat[$data['goods_from']."/".$data['goods_to']]['trade_count'] = $data['trade_count'];
				if (!empty($data['update_time'])) {
					$stat[$data['goods_from']."/".$data['goods_to']]['update_time'] = GB_TypeFactory::CreateBase(GB_Date, $data['update_time'])->getPgFormat();
				} else {
					$stat[$data['goods_from']."/".$data['goods_to']]['update_time'] = '';
				}
				
				

				//Курсы
				$cource = $data['ammount_to']/$data['ammounf_from'];
				$cur_course = $data['cource'];
				$stat[$data['goods_from']."/".$data['goods_to']]['cource'] = $cource;
				$stat[$data['goods_from']."/".$data['goods_to']]['cur_cource'] = $cur_course;
				if ($cur_course > $cource) {
					$stat[$data['goods_from']."/".$data['goods_to']]['cource_dif'] = abs(100 - (100*$cur_course/$cource));
				} else {
					$stat[$data['goods_from']."/".$data['goods_to']]['cource_dif'] = abs(100 - (100*$cource/$cur_course));
				}

				$stat[$data['goods_from']."/".$data['goods_to']]['id_from_article'] = $data['id_from_article'];
				$stat[$data['goods_from']."/".$data['goods_to']]['id_from_category'] = $data['id_from_category'];
			}
		}

		foreach ($stat as $k => $data)
		{
			$OutStat[] = $data;
		}

		self::$out .= TradeStatisticOut::outStat($OutStat);
	}

	static private function _recount()
	{
		$worlds = TypeSearch::GetAllWorldsList();

		self::$out .= TradeStatisticOut::outRecount($worlds, self::$cources);
	}

	static private function _execRecount()
	{
		$title = array(
		'KADERIUM' => array(
		'id_tradecategory' => 1,
		'id_article' => 1,
		),
		'NANO_CRYSTAL' => array(
		'id_tradecategory' => 1,
		'id_article' => 2,
		),
		'STASIS_ENERGY' => array(
		'id_tradecategory' => 1,
		'id_article' => 3,
		),
		'FOOD' => array(
		'id_tradecategory' => 1,
		'id_article' => 4,
		),
		'PRODIUM' => array(
		'id_tradecategory' => 1,
		'id_article' => 5,
		),
		'SILVER_CREDIT' => array(
		'id_tradecategory' => 4,
		'id_article' => 1,
		),
		'GOLD_CREDIT' => array(
		'id_tradecategory' => 4,
		'id_article' => 1,
		),
		);

		//Проходимся по каждому товару
		foreach (self::$cources as $key => $data)
		{
			foreach ($title as $res => $resArr)
			{
				if (key_exists($res, $data['cources']) and !empty($data['cources'][$res]))
				{
					settype($data['cources'][$res], float);

					if ($data['cources'][$res] <= 0 ) {
						echo '<font color="Red">WRONG COURSE <b>['.$data['goods_from'].']</b> for <b>['.$res.']: <b>'.$data['cources'][$res].'</b></font>';
						return false;
					}
					TradeStatisticSearch::Recount((int)$data['id_from_article'], (int)$data['id_from_category'], $resArr['id_tradecategory'], $resArr['id_article'], $data['cources'][$res]);
				}
			}
		}
		
		self::$out .= TradeStatisticOut::OutOk(self::$cources);
		
		return;
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
			$_SESSION["dbId"] = (int)$arr[0];
			$_SESSION['worldID'] = (int)$arr[1];
			$_SESSION['world'] = $_SESSION["dbId"].'_'.$_SESSION['worldID'];
			$_SESSION['world_name'] = $arr[2];
			assert($_SESSION['dbId'] >= 0);
			assert($_SESSION['worldID'] > 0);
		}

		if(key_exists('scopes', $data))
		{
			$_SESSION['scopes'] = $data['scopes'];
			assert(!empty($_SESSION['scopes']));
		}

		if(key_exists('catecories', $data))
		{
			$_SESSION['catecories'] = $data['catecories'];
			assert(!empty($_SESSION['catecories']));
		}

		if(key_exists('catecoriesfor', $data))
		{
			$_SESSION['catecoriesfor'] = $data['catecoriesfor'];
			assert(!empty($_SESSION['catecoriesfor']));
		}

		if(key_exists('recources', $data))
		{
			$_SESSION['recources'] = $data['recources'];
			assert(!empty($_SESSION['recources']));
		}

		if(key_exists('recourcesfor', $data))
		{
			$_SESSION['recourcesfor'] = $data['recourcesfor'];
			assert(!empty($_SESSION['recourcesfor']));
		}

		if(key_exists('money', $data))
		{
			$_SESSION['money'] = $data['money'];
			assert(!empty($_SESSION['money']));
		}

		if(key_exists('race', $data))
		{
			$_SESSION['race'] = $data['race'];
			assert(!empty($_SESSION['race']));
		}

		if(key_exists('class', $data))
		{
			$_SESSION['class'] = $data['class'];
			assert(!empty($_SESSION['class']));
		}

		if(key_exists('artscope', $data))
		{
			$_SESSION['artscope'] = $data['artscope'];
			assert(!empty($_SESSION['artscope']));
		}

		if(key_exists('size', $data))
		{
			$_SESSION['size'] = $data['size'];
			assert(!empty($_SESSION['size']));
		}

		if(key_exists('unitName', $data))
		{
			$_SESSION['unitName'] = $data['unitName'];
		}

		if(key_exists('artName', $data))
		{
			$_SESSION['artName'] = $data['artName'];
		}

		if(key_exists('from_y', $data) && key_exists('from_m', $data) && key_exists('from_d', $data))
		{
			$_SESSION['from_y'] = (int)$data['from_y'];
			$_SESSION['from_m'] = (int)$data['from_m'];
			$_SESSION['from_d'] = (int)$data['from_d'];

			$_SESSION['startDate'] = mktime(0, 0, 0, (int)$data['from_m'], (int)$data['from_d'], (int)$data['from_y']);
		}

		if(key_exists('to_y', $data) && key_exists('to_m', $data) && key_exists('to_d', $data))
		{
			$_SESSION['to_y'] = (int)$data['to_y'];
			$_SESSION['to_m'] = (int)$data['to_m'];
			$_SESSION['to_d'] = (int)$data['to_d'];

			$_SESSION['endDate'] = mktime(23, 59, 59, (int)$data['to_m'], (int)$data['to_d'], (int)$data['to_y']);
		}

		if(key_exists('user', $data))
		{
			$_SESSION['user'] = pg_escape_string(trim($data['user']));
		}

		if(key_exists('cource', $data))
		{
			self::$cources = $data['cource'];
		}

	}
}
?>