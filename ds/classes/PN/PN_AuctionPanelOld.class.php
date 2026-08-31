<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка аукционов (торговля)
class PN_AuctionPanel extends UA_GetBuildMenu
{

	public $travelTime = '';

	public $upkeepValue = '';

	public $localization = '';

	function __construct()
	{
		UA_GetBuildMenu::UA_GetBuildMenu();
		$this->localization = DE_Localization::CreateForCurentLang();
	}

	function getPanel()
	{
		global $params;
		global $common;

		// сколько лотов на страницу
		$limit = 10;

		if($this->page == "" || $this->page <= 0)
		{
			$this->page = 1;
		}

		$offset = $limit * ($this->page - 1);

		if($offset < 0)
		{	
			$offset = 0;
		}

		$delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();

		// начинаем собирать запрос

		// выводить торги
		$what1 = "
			SELECT
				tr.id_trade,
				tr.id_tradecategory,
				tr.id_article,
				tr.amount,
				tr.startprice,
				tr.currentprice,
				tr.isimmediate,
				tr.id_tradescope,
				tr.id_currentcustomer,
				tr.id_sellercomb,
				to_char ( tradeenddate  + '".$delta." hour'::interval, 'DD.MM HH24:MI') AS enddate,
				atn.attributename as tradeby,
				u.username as seller ";

		// определить кол-во лотов
		$what2 = "SELECT count(tr.id_trade) AS count ";

		// все трейдскопы
		$sql = sprintf ( "
				SELECT
					ts.id_tradescope,
					ltl.text,
					ts.tradescopename
				FROM
					ds_localizedtextlanguage ltl,
					ds_tradescope ts
				WHERE
					ltl.id_localizedtext = ts.id_localizedname
					AND 
					ltl.id_language = %d",
			$params['lang'] );

		$res = $common->getResultArray ( "id_tradescope_localizedname_id_language_tradescopename_".$this->sort_place, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if(!$res)
		{
			$this->errormsg = "SYSTEM ERROR AuctionPanel::getPanel1";
			return false;
		}

		for($i=0; $i<sizeof($res); $i++)
		{
			$tradescope[$res[$i]['id_tradescope']] = $res[$i]['text'];
			$tradescopename[$res[$i]['tradescopename']] = $res[$i]['id_tradescope'];
		}

		// все категории
		$sql = "
			SELECT 
				id_tradecategory,
				categoryname
			FROM 
				ds_tradecategory";

		$res = $common->getResultArray("id_alliance_id_useinstance_".$params['uiid'], $sql, "", $params["uiid"], "LOCAL_CACHE");
		if(!$res)
		{
			$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel2";
			return false;
		}

		for($i=0; $i<sizeof($res); $i++)
		{
			$tc[$res[$i]['categoryname']] = $res[$i]['id_tradecategory'];
			$tcn[$res[$i]['id_tradecategory']] = $res[$i]['categoryname'];
		}

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><auction />";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		//$root->set_attribute ( "c", $this->cellnum );

		// сортировки
		if(empty($this->sort_place)) 
		{
			$this->sort_place = 'COMB_COMPLEX_TRADING';
		}
		if(empty($this->sort_type)) 
		{
			$this->sort_type = 'RESOURCES';
		}
		if(empty($this->sort_name)) 
		{
			$this->sort_name = 'ALL';
		}

		if(!empty($this->immediate))
		{
			$this->immediate = " AND isimmediate = true ";
			$immediate = 1;
		} else {
			$this->immediate = "";
			$immediate = 0;
		}

		if($this->order == "amount_asc")
		{
			$ord = " tr.amount ASC";
		} elseif($this->order == "amount_desc") {
			$ord = " tr.amount DESC";
		} elseif($this->order == "resource_asc") {
			$ord = " tr.id_article ASC";
		} elseif($this->order == "resource_desc") {
			$ord = " tr.id_article DESC";
		} elseif($this->order == "enddate_asc") {
			$ord = " tr.tradeenddate ASC";
		} elseif($this->order == "enddate_desc") {
			$ord = " tr.tradeenddate DESC";
		} elseif($this->order == "by_resource_asc") {
			$ord = " tr.id_tradeby ASC";
		} elseif($this->order == "by_resource_desc") {
			$ord = " tr.id_tradeby DESC";
		} elseif($this->order == "price_asc") {
			$ord = " tr.currentprice ASC, tr.startprice ASC";
		} elseif($this->order == "price_desc") {
			$ord = " tr.currentprice DESC, tr.startprice DESC";
		} elseif($this->order == "seller_asc") {
			$ord = " u.username ASC";
		} elseif($this->order == "seller_desc") {
			$ord = " u.username DESC";
		} else {
			$ord = " tr.tradeenddate DESC";
		}

		// показать только свои выставленные та торги товары
		if($this->own_trades != "")
		{
			$sql = "
				FROM 
					ds_attributename atn,
					ds_trade tr,
					ds_userinstance ui,
					ds_user u
				WHERE 
					tr.id_tradeby = atn.id_attributename
					AND 
					tr.id_seller = ui.id_userinstance
					AND 
					ui.id_user = u.id_user
					AND 
					tr.id_seller = ".$params['uiid'];
		} elseif ( $this->own_bets != "" ) {
			// показать только торги где юзер сделал ставку
			$sql = "
				FROM 
					ds_attributename atn,
					ds_trade tr,
					ds_userinstance ui,
					ds_user u
				WHERE 
					tr.id_tradeby = atn.id_attributename
					AND 
					tr.id_seller = ui.id_userinstance
					AND 
					ui.id_user = u.id_user
					AND 
					tr.id_currentcustomer = ".$params['uiid'];
		} else {
			$ts = '';
			$sql = sprintf("
					SELECT 
						ts.id_tradescope
					FROM 
						ds_tradescope ts
					WHERE 
						ts.tradescopename = '%s'",
				$this->sort_place );

			$res = $common->getResultArray ( "id_tradescope_tradescopename_".$this->sort_place, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if(!$res)
			{
				$this->errormsg = "SYSTEM ERROR AuctionPanel::getPanel3";
				return false;
			}

			$ts = $res[0]['id_tradescope'];

			// продолжаем собирать запрос, выбираем товары по месту проведения торга
			if($this->sort_place == 'ALLIANCE_TRADING')
			{
				$sql = sprintf("
						SELECT 
							id_alliance
						FROM 
							ds_userhasalliance
						WHERE 
							id_userinstance = %d",
					$params['uiid'] );

				$res = $common->getResultArray ( "id_alliance_id_useinstance_".$params['uiid'], $sql, "", $params["uiid"], "LOCAL_CACHE" );
				if($res === false)
				{
					$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel3";
					return false;
				}

				if(sizeof($res) == 0)
				{
					$id_alliance = -1;
				} else {
					$id_alliance = $res[0]['id_alliance'];
				}
			}

			$sql = "
				FROM 
					ds_attributename atn,
					ds_trade tr,
					ds_userinstance ui,
					ds_user u
				WHERE 
					tr.id_tradeby = atn.id_attributename
					AND 
					tr.id_seller = ui.id_userinstance
					AND 
					ui.id_user = u.id_user
					AND 
					tr.id_tradescope = $ts";

			if($this->sort_place == 'COMB_COMPLEX_TRADING')
			{
				$sql2 = "
					AND EXISTS
						( 
							SELECT 
								id_combcomplex
							FROM 
								ds_comb
							WHERE 
								id_comb = tr.id_sellercomb
								AND 
								id_combcomplex = ".$this->combcomplex."
						)";

			} elseif($this->sort_place == 'RING_TRADING') {
				$sql2 = "
					AND EXISTS
						( 
							SELECT 
								cc.id_ring
							FROM 
								ds_combcomplex cc,
								ds_comb co
							WHERE 
								cc.id_combcomplex =co.id_combcomplex
								AND 
								co.id_comb = tr.id_sellercomb
								AND 
								cc.id_ring = ".$this->ring."
						)";

			} elseif($this->sort_place == 'INTERRING_TRADING') {
				$sql2 = "
					AND EXISTS
						( 
							SELECT 
								ri.id_world
							FROM 
								ds_ring ri,
								ds_combcomplex cc,
								ds_comb co
							WHERE  
								ri.id_ring = cc.id_ring
								AND 
								cc.id_combcomplex =co.id_combcomplex
								AND 
								co.id_comb = tr.id_sellercomb
								AND 
								ri.id_world = ".$this->world."
						)";

			} elseif($this->sort_place == 'ALLIANCE_TRADING') {
				$sql2 = "
					AND EXISTS
						(
							SELECT 
								uha.id_alliance
							FROM 
								ds_userhasalliance uha
							WHERE 
								uha.id_userinstance = tr.id_seller
								AND 
								uha.id_alliance = ".$id_alliance."
						)";
			} else {
				$sql2 = '';
			}

			$sql .= $sql2;
			$sql2 = '';

			// сортировка по типу ресурса за который идет торг
			if($this->buy_by != "")
			{
				$sql2 = "
					AND 
					tr.id_tradeby =
					(
						SELECT 
							id_attributename
						FROM 
							ds_attributename
						WHERE 
							attributename = '".$this->buy_by."')";

				$sql .= $sql2;
				$sql2 = '';
			}

			switch($this->sort_type)
			{
				case "RESOURCES":

				$cat = " AND tr.id_tradecategory = ".$tc['RESOURCE'];
				if($this->sort_name == "ALL") 
				{
					$article = '';
				} else {
					$article = sprintf("
						 AND 
						 tr.id_article =
								(
									SELECT 
										id_attributename
									FROM 
										ds_attributename
									WHERE 
										attributename = '%s'
								) ",
					$this->sort_name );
				}
				break;

				case "UNITS":

				$cat = " AND tr.id_tradecategory = ".$tc['UNIT'];
				if($this->sort_name == "ALL")
				{
					$article = '';
				} else {
					$article = sprintf(" 
						AND EXISTS
							(
								SELECT 
									ucbr.id_unit
								FROM 
									ds_unitcanbebuildbyrace ucbr,
									ds_race r
								WHERE 
									ucbr.id_race = r.id_race
									AND 
									r.racename = '%s'
									AND 
									ucbr.id_unit = tr.id_article
							) ",
					$this->sort_name );
				}

				break;

				case "ARTEFACTS";

				$cat = " and tr.id_tradecategory = ".$tc['ARTEFACT'];
				if($this->sort_name == "ALL")
				{
					$article = '';
				} else {
					$article = sprintf("
						AND EXISTS
							(
								SELECT 
									ai.id_artefactinstance
								FROM 
									ds_artefactinstance ai,
									ds_artefact a,
									ds_artefactscope ars
								WHERE 
									ai.id_artefact = a.id_artefact
									AND 
									a.id_artefactscope = ars.id_artefactscope
									AND 
									ars.scopename = '%s'
									AND 
									ai.id_artefactinstance = tr.id_article
							) ",
						$this->sort_name );
				}
				break;

				default:
				$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel4";
				return false;
				break;
			}

			$sql .= $cat;
			$sql .= $article;
			$sql .= $this->immediate;
		}

		// отсеить закончившиеся торги
		$sql .= " 
				AND 
				tr.tradestartdate < now() 
				AND 
				tr.tradeenddate > now() 
				AND 
				delivery = false";

		// определяем кол-во товаров соответствующих всем критериям отбора
		$sql3 = $what2.$sql;
		//Проверка для RVR мира
		if(key_exists('worldtype', $_SESSION))
		{
			if($_SESSION["worldtype"] == 'RACE_VS_RACE')
			{
				$sql3 .= " AND ui.id_race =  ".$_SESSION['race'];
			}
		}

		$common->setTimeOut(constant('DB_EXTENDED_TIMEOUT'));
		$res = $common->getResultArray ( "all_allowed_goods_count_".$this->sort_place."_".$this->sort_type."_".$this->sort_name."_".$this->immediate."_".$this->comb."_".$this->combcomplex."_".$this->ring."_".$this->world, $sql3, "", $params["uiid"], "LOCAL_CACHE" );
		if($res === false)
		{
			$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel5";
			return false;
		}
		$common->setTimeOut(constant('DB_DEFAULT_TIMEOUT'));

		$count = $res[0]['count'];
		$pages = ceil($count/$limit);

		$root->set_attribute("pages", $pages);
		//$root->set_attribute ( "current", $this->page );

		if($count > 0)
		{ // если такие товары есть, организуем постраничный вывод
			$sql = $what1.$sql;

			//Проверка для RVR мира
			if(key_exists('worldtype', $_SESSION))
			{
				if($_SESSION["worldtype"] == 'RACE_VS_RACE')
				{
					$sql .= " AND ui.id_race = ".$_SESSION['race'];
				}
			}

			$sql .= " ORDER BY $ord";

			$sql .= " LIMIT $limit OFFSET $offset";
//SU_DebugTools::logAll($sql, 'AuctionPanel.log');
			$common->setTimeOut(constant('DB_EXTENDED_TIMEOUT'));
			$res = $common->getResultArray ( "all_allowed_goods_id_".$this->sort_place."_".$this->sort_type."_".$this->sort_name."_".$this->immediate."_".$this->comb."_".$this->combcomplex."_".$this->ring."_".$this->world, $sql, "", $params["uiid"], "LOCAL_CACHE" );
			if($res === false)
			{
				$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel6";
				return false;
			}

			$common->setTimeOut(constant('DB_DEFAULT_TIMEOUT'));
		} else {
			$res = array ();
		}

		// формируем ответ клиенту, с выбранными лотами
		for($i=0; $i<sizeof($res); $i++)
		{
			if($this->sort_type == 'UNITS')
			{
				if($this->comb == $res[$i]['id_sellercomb'])
				{
					$messageParams[0] = 0;
					$messageParams[1] = 0;
					$messageParams[2] = 0;

				} else {
					$combTo = DE_Comb::CreateFromID($this->comb);
					$combFrom = DE_Comb::CreateFromID((int)$res[$i]['id_sellercomb']);
					$unit = DE_Unit::CreateFromID((int)$res[$i]['id_article']);
					$amount = (int)$res[$i]['amount'];
					$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);

					$travelTime = GL_TravelTime::Count($combFrom, $combTo, $speed);

					$upkeepValue = GL_FullUnitsMovementCost::CountBuyUnits($combFrom, $combTo, $unit, $amount);

					$messageParams[0] = $travelTime->getDSFormat();
					$messageParams[1] = $upkeepValue->getShortPayList();
				}

				$getPrice = $this->localization->get(30000, $messageParams);
			} else {
				if($this->comb == $res[$i]['id_sellercomb'])
				{
					$messageParams[0] = 0;
				} else {

					$travelTime = GL_TravelTime::Count(DE_Comb::CreateFromID($this->comb), DE_Comb::CreateFromID((int)$res[$i]['id_sellercomb']), GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1));
					$messageParams[0] = $travelTime->getDSFormat();
				}
				$getPrice = $this->localization->get(30001, $messageParams);
			}

			$ch = $root->new_child("au");
			$ch->set_attribute("id", $res[$i]['id_trade']);
			$ch->set_attribute("enddate", $res[$i]['enddate']);
			$ch->set_attribute("amount", $res[$i]['amount']);
			$ch->set_attribute("getprice", $getPrice);
			$ch->set_attribute("startprice", number_format($res[$i]['startprice'], 0, ".", ""));

			if($res[$i]['currentprice'] != "")
			{
				$correntPrice = number_format($res[$i]['currentprice'], 0, ".", "") ;
			} else {
				$correntPrice = number_format($res[$i]['startprice'], 0, ".", "");
			}

			$ch->set_attribute("currentprice", $correntPrice);

			$ch->set_attribute("seller", $res[$i]['seller']);

			if($res[$i]['id_currentcustomer'] != "")
			{

				$sql = sprintf("
						SELECT 
							u.username
						FROM 
							ds_user u,
							ds_userinstance ui
						WHERE 
							u.id_user = ui.id_user
							AND 
							ui.id_userinstance = %d",
				$res[$i]['id_currentcustomer'] );

				$res2 = $common->getResultArray ( "username_id_userinstance_".$res[$i]['id_currentcustomer'], $sql, "", "", "GLOBAL_CACHE" );
				if ( $res2 === false ) {
					$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel7";

					return false;
				}

				$ch->set_attribute("currentcustomer", $res2[0]['username']);
			}

			$ch->set_attribute("tradeby", $res[$i]['tradeby']);

			$ch->set_attribute("tradescope", urlencode($tradescope[$res[$i]['id_tradescope']]));

			if($res[$i]['isimmediate'] == 't')
			{
				$ch->set_attribute("immediate", "1");
			}

			switch($tcn[$res[$i]['id_tradecategory']])
			{
				case "RESOURCE":

				$sql = sprintf("
							SELECT 
								atn.attributename
							FROM 
								ds_attributename atn
							WHERE 
								atn.id_attributename = %d",
				$res[$i]['id_article'] );

				$res2 = $common->getResultArray ( "attributename_id_article_".$res[$i]['id_article'], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );

				if(!$res2) 
				{
					$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel8";
					return false;
				}
				$ch->set_attribute("libname", $res2[0]['attributename']);
				break;

				case "ARTEFACT":

				$sql = sprintf("
							SELECT 
								ap.prefix||':'||ars.scopename||':'||at.typename as libname
							FROM 
								ds_artefact a,
								ds_artefactprobability ap,
								ds_artefactscope ars,
								ds_artefacttype at,
								ds_artefactinstance ai
							WHERE 
								a.id_artefact = ai.id_artefact
								AND 
								a.id_artefactprobability = ap.id_artefactprobability
								AND 
								a.id_artefactscope = ars.id_artefactscope
								AND 
								a.id_artefacttype = at.id_artefacttype
								AND 
								ai.id_artefactinstance = %d",
				$res[$i]['id_article'] );

				$res2 = $common->getResultArray ( "artefactname_id_article_".$res[$i]['id_article'], $sql, "", "", "GLOBAL_CACHE" );


				if(!$res2)
				{
					$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel9";
					return false;
				}

				$ch->set_attribute("libname", trim($res2[0]['libname']));

				$sql = sprintf("
							SELECT 
								atn.attributename,
								att.typename,
								ahb.value
							FROM 
								ds_artefacthasbonus ahb,
								ds_artefact a,
								ds_artefactinstance ai,
								ds_attribute at,
								ds_attributename atn,
								ds_attributetype att
							WHERE 
								ahb.id_attribute = at.id_attribute
								AND 
								ahb.id_artefact = a.id_artefact
								AND 
								a.id_artefact = ai.id_artefact
								AND 
								at.id_attributename = atn.id_attributename
								AND 
								at.id_attributetype = att.id_attributetype
								AND 
								ai.id_artefactinstance = %d",
				$res[$i]['id_article'] );

				$res2 = $common->getResultArray ( "artefactbonus_id_artefactinstance_".$res[$i]['id_article'], $sql, "", "", "GLOBAL_CACHE" );

				if($res2 === false)
				{
					$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel10";
					return false;
				}

				for($e=0; $e<sizeof ( $res2 ); $e++)
				{
					$bonus = (float)$res2[$e]['value'];

					if($res2[$e]['typename'] == 'BONUS_RELATIVE')
					{
						$bonus = $bonus*100;
						$bonus .= "%25";
					}
					$bonus = "%2B".$bonus;
					$ch->set_attribute ( "bonusname", trim ( $res2[$e]['attributename'] ) );
					$ch->set_attribute ( "bonusvalue", urlencode ( $bonus ) );
				}
				break;

				case "UNIT":

				$sql = sprintf("
							SELECT 
								unitname
							FROM 
								ds_unit
							WHERE 
								id_unit = %d",
						$res[$i]['id_article'] );

				$res2 = $common->getResultArray ( "unitname_id_article_".$res[$i]['id_article'], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );

				if(!$res2)
				{
					$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel11";
					return false;
				}
				$ch->set_attribute ( "libname", $res2[0]['unitname'] );
				break;
			}

			$comb = DE_Comb::CreateFromID($this->comb);
			$combAttributes = $comb->getAttributes();
			$resourceName = $res[$i]['tradeby'];

			if ($combAttributes->haveAttribute('CURRENT_AMOUNT', $resourceName))
			{
				if ($correntPrice > $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName) )
				{
					$ch->set_attribute ( "allow", "0" );
				}
			}
		}

		// данные для построения комбо-боксов сортировки
		$sql = sprintf ( "
				SELECT 
					aus.id_sort,
					aus.sortname,
					ltl.text as localizedname
				FROM 
					ds_auctionsort aus,
					(
						SELECT 
							trs.tradescopename
						FROM 
							ds_tradescope trs,
							ds_comballowedtradescope cat
						WHERE 
							trs.id_tradescope = cat.id_tradescope
							AND 
							cat.id_comb = %d
					) t,
					ds_localizedtextlanguage ltl
				WHERE 
					aus.id_localizedname = ltl.id_localizedtext
					AND 
					aus.id_parent is null
					AND 
					ltl.id_language = %d
					AND NOT EXISTS
						(
							SELECT 
								id_sort
							FROM 
								ds_auctionsort
							WHERE 
								id_parent = aus.id_sort
						)
					AND 
					t.tradescopename = aus.sortname
				ORDER BY 
					id_sort",
		$this->comb,
		$params["lang"] );

		$res = $common->getResultArray ( "auction_sort_place_id_comb_id_language_".$this->comb."_".$params["lang"], $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if(!$res)
		{
			$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel12";
			return false;
		}

		for($i=0; $i<sizeof ( $res ); $i++)
		{

			$ch = $root->new_child ( "sort_place" );
			$ch->set_attribute ( "sortname", $res[$i]['sortname'] );
			$ch->set_attribute ( "lname", urlencode ( $res[$i]['localizedname'] ) );
			if ( $res[$i]['sortname'] == $this->sort_place ) $ch->set_attribute ( "cur", "1" );
		}

		$sql = sprintf("
				SELECT 
					aus.id_sort,
					aus.sortname,
					ltl.text as localizedname
				FROM 
					ds_auctionsort aus,
					ds_localizedtextlanguage ltl
				WHERE 
					aus.id_localizedname = ltl.id_localizedtext
					AND 
					aus.id_parent is null
					AND 
					ltl.id_language = %d
					AND EXISTS
						(
							SELECT 
								id_sort
							FROM 
								ds_auctionsort
							WHERE 
								id_parent = aus.id_sort
						)
				ORDER BY 
					id_sort",
		$params["lang"] );

		$res = $common->getResultArray ( "auction_sort_type_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );

		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel13";

			return false;
		}

		for ( $i=0; $i<sizeof ( $res ); $i++ ) {

			$ch = $root->new_child ( "sort_type" );
			$ch->set_attribute ( "sortname", $res[$i]['sortname'] );
			$ch->set_attribute ( "lname", urlencode ( $res[$i]['localizedname'] ) );
			if ( $res[$i]['sortname'] == $this->sort_type ) $ch->set_attribute ( "cur", "1" );

			$sql = sprintf ( "
					SELECT 
						aus.id_sort,
						aus.sortname,
						ltl.text AS localizedname
					FROM 
						ds_auctionsort aus,
						ds_localizedtextlanguage ltl
					WHERE 
						aus.id_localizedname = ltl.id_localizedtext
						AND 
						ltl.id_language = %d
						AND 
						id_parent = %d
					ORDER BY id_sort",
			$params["lang"],
			$res[$i]['id_sort'] );

			$res2 = $common->getResultArray ( "auction_sort_name_id_language_id_sort_type_".$params["lang"]."_".$res[$i]['id_sort'], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );

			if(!$res2) 
			{
				$this->errormsg .= " SYSTEM ERROR AuctionPanel::getPanel14";
				return false;
			}

			for($e=0; $e<sizeof($res2); $e++) 
			{
				$ch2 = $ch->new_child("sort_name");
				$ch2->set_attribute("sortname", $res2[$e]['sortname']);
				$ch2->set_attribute("lname", urlencode($res2[$e]['localizedname']));
				if($res2[$e]['sortname'] == $this->sort_name)
				{
					$ch2->set_attribute("cur", "1");
				}
			}
		}

		if($this->immediate == "true") 
		{
			$ch = $root->new_child("sort_immediate");
		}
		if($this->own_trades != "") 
		{
			$ch = $root->new_child('own_trades');
		}
		if($this->own_bets != "") 
		{
			$ch = $root->new_child("own_bets");
		}

		$this->getBuildingTabs($root, $this->bid, $params["lang"], $this->tab);

		//$panel = "xmldata=";
		//$panel .= $doc->dump_mem();

		//$this->logResponse ( $panel );

		// print $panel;

		SU_OutputHandler::Singleton()->addString($doc->dump_mem() , 'xmldata');

		return true;
	}
}

?>