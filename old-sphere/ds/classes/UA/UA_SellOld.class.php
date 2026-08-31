<?php $CVS_Version = '$Revision$ ; $Date$';
// команда выставить товар на аукцион
class UA_Sell extends UA_Action
{
	public $id_tradescope;
	public $id_article;
	public $id_category;
	public $amount;
	public $categoryname;
	public $immediate;
	public $tradetime;
	public $tradeby;
	public $startprice;
	public $liveupkeep;
	public $combFrom;
	public $exists_resource = 0;

	function UA_Sell()
	{
		UA_Action::UA_Action ();
	}

	// проверяем параметры
	function checkLocalParams ()
	{
		// scope аукциона
		$this->id_tradescope = $this->root->get_attribute ( "id_tradescope" );
		if ( empty( $this->id_tradescope ) )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkLocalParams1";
			return false;
		}

		// id товара ( id_attribute для ресурсов, id_unit для юнитов id_artefactinstance для артефактов )
		$this->id_article = $this->root->get_attribute ( "id_article" );
		if ( empty( $this->id_article ) )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkLocalParams2";
			return false;
		}

		// id категории (ресурсы, юниты, артефакты)
		$this->id_category = $this->root->get_attribute ( "id_category" );
		if ( empty( $this->id_category ) )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkLocalParams3";
			return false;
		}

		// колличество
		$this->amount = ceil((float)$this->root->get_attribute("amount"));

		if($this->amount <= 0)
		{
			$this->haveProcessedErrors = true;
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'UA_SELL_ERROR_NEGATIVE_OR_EMPTY_AMOUNT', $messparams, 0, 'RED' );
			//ds_error("Amount must be bigger then 0", false);
		}

		if($this->amount > 2000000000)
		{
			$this->haveProcessedErrors = true;
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'UA_SELL_ERROR_BIGGER_AMOUNT', $messparams, 0, 'RED' );
			//ds_error("Amount can't be bigger then 2.000.000.000", false);
		}



		// id_attribute ресурса за который товар выставлен на торг
		$this->tradetime = (int)$this->root->get_attribute ( "tradetime" );
		if ( empty( $this->tradetime ) || $this->tradetime <= 0 )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkLocalParams5";
			return false;
		}

		// длительность аукциона в ходах
		if ( $this->tradetime > 99 ) $this->tradetime = 99;

		$this->tradeby = $this->root->get_attribute ( "tradeby" );
		if ( empty( $this->tradeby ) )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkLocalParams6";
			return false;
		}

		// начальная цена
		$this->startprice = round((float)$this->root->get_attribute("startprice"));
		if($this->startprice <= 0)
		{
			$this->haveProcessedErrors = true;
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'UA_SELL_ERROR_NEGATIVE_OR_EMPTY_PRICE', $messparams, 0, 'RED' );

			//ds_error("Start price must be bigger then 0", false);
		}

		if($this->startprice > 2000000000)
		{
			$this->haveProcessedErrors = true;
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'UA_SELL_ERROR_BIGGER_PRICE', $messparams, 0, 'RED' );

			//ds_error("Start price can't be bigger then 2.000.000.000", false);
		}

		// продажа товара первому сделавшему ставку
		$this->immediate = $this->root->get_attribute ( "immediate" );
		if($this->immediate != "")
		{
			$this->immediate = 'true';
		}else{
			$this->immediate = 'false';
		}

		$this->combFrom = DE_Comb::CreateFromID((int)$this->comb);
		return true;
	}


	// проверяем допустимость действия
	function checkAction ()
	{
		global $common;
		global $params;


		// рефреш с актуализацией ресурсов
		if ( $_SESSION['profi_game'] )
		{
			$noCellData = true;
		} else {
			$noCellData = false;
		}

		// доступен ли указанный tradescope
		$sql = sprintf ( "select id_tradescope
				from ds_comballowedtradescope
				where id_comb= %d
				and id_tradescope = %d",
		$this->comb,
		$this->id_tradescope );

		$res = $common->getResultArray ( "combhastradescope_id_comb_id_tradescope_".$this->comb."_".$this->id_tradescope, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkAction1";
			return false;
		}

		if ( sizeof ( $res ) == 0 )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkAction2";
			return false;
		}

		// получаем название категории
		$sql = sprintf ( "select categoryname
				from ds_tradecategory
				where id_tradecategory = %d",
		$this->id_category );

		$res = $common->getResultArray ( "categoryname_id_tradecategory_".$this->id_category, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkAction3";
			return false;
		}

		$this->categoryname = $res[0]['categoryname'];

		// проверяем наличие выставляемого на аукцион товара
		switch ( $this->categoryname )
		{
			case "RESOURCE":

			$attributeName =  ST_Attributes::Create()->getAttributeNameByAttributeNameID((int)$this->id_article);

			$chr = DE_CombAttributes::CreateFromCombID((int)$this->comb)->getAttribute('CURRENT_AMOUNT', $attributeName);

			if ( $chr < $this->amount )
			{
				$this->amount = $chr;
				//$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkAction5";
				//return false;
				//if ( !$this->refresh ( 2, "", "", 1, $noCellData ) )
				//{
//					return false;
				//}

			}
			break;
			case "ARTEFACT":
			$sql = sprintf ( "select id_artefact
						from ds_combhasartefact
						where id_artefact = %d
						and id_comb = %d",
			$this->id_article,
			$this->comb );

			$res = $common->getResultArray ( "combhasartefact_id_artefactinstance_id_comb_".$this->id_article."_".$this->comb, $sql, "", $params["uiid"], "LOCAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkAction6";
				return false;
			}
			break;
			case "UNIT":
			
			if(!GL_CanMoveUnitsFromComb::IsCan($this->combFrom))
			{
				ds_error('You cant sell units from comb during battle.');
			}
			
			$sql = sprintf ( "select uchu.amount
						from ds_unitcontainerhasunit uchu,
						ds_unitcontainer uc
						where uchu.id_army = uc.id_unitcontainer
						and uc.id_basecomb = %d
						and uc.isdefault = true
						and uchu.id_unit = %d
						and not exists
						(select id_battle
						from ds_battle
						where id_battlecomb = uc.id_basecomb
						and battleenddate is null)",
			$this->comb,
			$this->id_article );

			$res = $common->getResultArray ( "amount_id_comb_id_unit_".$this->comb."_".$this->id_article, $sql, "", $params["uiid"], "LOCAL_CACHE" );
			if ( $res === false )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkAction7";
				return false;
			}

			$chr = ceil((int)number_format($res[0]['amount'], 0, ".", "" ));
	
			if ( $chr < $this->amount )
			{
				$this->amount = $chr;
			}

			$unit = DE_Unit::CreateFromID((int)$this->id_article);
			$userInstance = DE_UserInstance::CreateFromID((int)$_SESSION["uiid"]);
			$combAttributes = $this->combFrom->getAttributes();

			$sellPeriodTime = $this->tradetime * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
			$sellPeriod = GB_TypeFactory::CreateConstant('GB_TimePeriod', $sellPeriodTime);

			$this->liveupkeep = GL_FullUnitsMovementCost::CountSellUnit($userInstance, $sellPeriod, $unit, (int)$this->amount);

			//Атеперь определяем хватит ли ресурса чтобы выставить лот им снимаем ресурсы
			foreach (GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
			{
				$needValue = (float)$this->liveupkeep->getPay($resourceName);
				$haveValue = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);
				
				if ($combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName) >= $needValue)
				{
					$this->exists_resource = 1;
					$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -$needValue);
				}else{
					DS_error("Not ehought resouces for sell this units. RESOURCE: $resourceName HAVE: $haveValue NEED: $needValue");
				}
			}

			break;
		}

		// проверяем корректность id_tradeby
		$sql = sprintf ( "select id_attributename
				from ds_resource
				where id_attributename = %d
				and exchangerate != 0",
		$this->tradeby );

		$res = $common->getResultArray ( "check_tradeby_".$this->tradeby, $sql, "", "", "" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::checkAction10";
			return false;
		}
		return true;
	}



	// выполняем действие
	function performAction ()
	{
		global $params;
		global $common;

		if ($this->haveProcessedErrors == true)
		{
			return true;
		}
		
		if(SU_Locker::CerateForCurrentDB(__CLASS__)->isLocked($_SESSION["uiid"]))
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $_SESSION["uiid"], __CLASS__);
		}else{
			SU_Locker::CerateForCurrentDB(__CLASS__)->lock($_SESSION["uiid"]);
		};
		
		if(SU_Locker::CerateForCurrentDB('EVENT_MONITOR')->isLocked($_SESSION["uiid"]))
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $_SESSION["uiid"], 'EVENT_MONITOR');
		}else{
			SU_Locker::CerateForCurrentDB('EVENT_MONITOR')->lock($_SESSION["uiid"]);
		};

		if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
		{
			$this->_vocationModeAlert();			
			return true;
		}
		
		if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
		{
			$this->_shildModeAlert();			
			return true;
		}
		
		if ( !$this->getEventEndTime () )
		{
			return false;
		}
		//Дефолтное значение, если вдруг не будет выбрат текст для сообщения
		$this->message_text = "START_TRADING_MESS";

		// создаем аукцион
		$sql = sprintf ( "insert into ds_trade ( id_tradecategory, id_article, id_tradescope, id_tradeby,
				id_seller, amount, startprice, tradestartdate, tradeenddate, isimmediate, id_sellercomb, delivery )
				values ( %d, %d, %d, %d, %d, %d, %d, '%s', '%s', %s, %d, false )",
		$this->id_category,
		$this->id_article,
		$this->id_tradescope,
		$this->tradeby,
		$params["uiid"],
		$this->amount,
		$this->startprice,
		$this->starttime,
		$this->endtime,
		$this->immediate,
		$this->comb );

		$res = $common->executeQuery ( $sql );
		assert(empty($common->errormsg));

		$sql = sprintf ( "select max ( id_trade ) as id_trade
				from ds_trade
				where id_seller = %d",
		$params["uiid"] );

		$res = $common->getResultArray ( "max_id_trade_is_seller_".$params["uiid"], $sql, "", "", "" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::performAction2";
			return false;
		}

		$id_trade = $res[0]['id_trade'];

		$messparams[0] = "{b} ".$this->amount."{/b}";

		//Дописываем на сколько турнов создавался лот
		$messparams[2] = "{b} ".$this->tradetime."{/b}";


		// убираем товар с соты
		switch ( $this->categoryname )
		{
			case "RESOURCE":

			$attributeName = ST_Attributes::Create()->getAttributeNameByAttributeNameID((int)$this->id_article);
			$chr = DE_CombAttributes::CreateFromCombID((int)$this->comb)->deltaAttribute('CURRENT_AMOUNT', $attributeName, -(float)$this->amount);

			// название ресурса
			$sql = sprintf ( "select ltl.text as localizedname
						from ds_localizedtextlanguage ltl,
						ds_attributename atn
						where atn.id_localizedname = ltl.id_localizedtext
						and atn.id_attributename = %d
						and ltl.id_language = %d",
			$this->id_article,
			$params["lang"] );

			$res = $common->getResultArray ( "attributename_id_attributename_id_language_".$this->id_article."_".$params["lang"], $sql, "", "", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Sell::performAction4";
				return false;
			}

			$messparams[1] = "{b} ".$res[0]['localizedname']."{/b}";

			if ($this->immediate == 'true')
			{
				$this->message_text = "START_TRADING_MESS3";
			} else {
				$this->message_text = "START_TRADING_MESS4";
			}

			break;

			case "ARTEFACT":

			// название артефака
			$sql = sprintf ( "select ltl.text as artefactname
						from ds_localizedtextlanguage ltl,
						ds_artefact ar,
						ds_artefactinstance ari
						where ar.id_localizedname = ltl.id_localizedtext
						and ar.id_artefact = ari.id_artefact
						and ari.id_artefactinstance = %d
						and ltl.id_language = %d",
			$this->id_article,
			$params["lang"] );

			$res = $common->getResultArray ( "artefactname_id_attributename_id_language_".$this->id_article."_".$params["lang"], $sql, "", "", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Sell::performAction5";
				return false;
			}

			$messparams[1] = "{b} ".$res[0]['artefactname']."{/b}";

			$sql = sprintf ( "delete from ds_combhasartefact
						where id_artefact = %d",
			$this->id_article );

			$res = $common->executeQuery ( $sql );
			if ( $res === false )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Sell::performAction6";
				return false;
			}

			if ($this->immediate == 'true')
			{
				$this->message_text = "START_TRADING_MESS3";
			} else {
				$this->message_text = "START_TRADING_MESS4";
			}

			break;

			case "UNIT":
			// название юнита
			$sql = sprintf ( "select ltl.text as unitname
						from ds_localizedtextlanguage ltl,
						ds_unit u
						where u.id_localizedname = ltl.id_localizedtext
						and u.id_unit = %d
						and ltl.id_language = %d",
			$this->id_article,
			$params["lang"] );

			$res = $common->getResultArray ( "unitname_id_attributename_id_language_".$this->id_article."_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Sell::performAction7";
				return false;
			}

			$messparams[1] = "{b} ".$res[0]['unitname']."{/b}";

			$sql = sprintf ( "select id_unitcontainer
						from ds_unitcontainer
						where id_basecomb = %d
						and isdefault = true",
			$this->comb );

			$res = $common->getResultArray ( "id_default_army_id_comb_".$this->comb, $sql, "", "", "" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Sell::performAction8";
				return false;
			}

			$id_army = $res[0]['id_unitcontainer'];

			$sql = sprintf ( "select uchu.amount
						from ds_unitcontainerhasunit uchu
						where uchu.id_army = %d
						and uchu.id_unit = %d
						",
						$id_army,
						$this->id_article
					);
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));

			$combAmount = (float)$res[0]['amount'];

			if($combAmount < $this->amount)
			{
				$delta = $combAmount;
			}else{
				$delta = $this->amount;
			}

			if ($combAmount <= $delta )
			{
				$sql = sprintf ( "delete from ds_unitcontainerhasunit
							where id_army = %d
							and id_unit = %d",
				$id_army,
				$this->id_article );

			} else {
				$sql = sprintf ( "update ds_unitcontainerhasunit
							set amount = amount - %d
							where id_army = %d
							and id_unit = %d",
				$delta,
				$id_army,
				$this->id_article );
			}
			$res = $common->executeQuery ( $sql );
			if ( $res === false )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Sell::performAction10";
				return false;
			}

			$messparams[5] = "{b} ".$this->liveupkeep->getShortPayList()."{/b}";

			if ($this->immediate == 'true')
			{
				$this->message_text = "START_TRADING_MESS1";
			} else {
				$this->message_text = "START_TRADING_MESS2";
			}

			break;
		}

		// создаем эвент
		$this->createEvent ( 'TRADING' );


		$sql = sprintf ( "insert into ds_tradebindeventqueue
				( id_trade, id_eventqueue )
				values ( %d, %d )",
		$id_trade, $this->eqid );

		$res = $common->executeQuery ( $sql );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::performAction13";
			return false;
		}

		// название ресурса за который выставлен лот
		$sql = sprintf ( "select ltl.text as localizedname
						from ds_localizedtextlanguage ltl,
						ds_attributename atn
						where atn.id_localizedname = ltl.id_localizedtext
						and atn.id_attributename = %d
						and ltl.id_language = %d",
		$this->tradeby,
		$params["lang"] );

		$res = $common->getResultArray ( "attributename_id_attributename_id_language_".$this->tradeby."_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::performAction4";
			return false;
		}

		$messparams[3] = "{b} ".$this->startprice."{/b}";
		$messparams[4] = "{b} ".$res[0]['localizedname']."{/b}";

		if ($this->exists_resource == 1)
		{
			$this->combFrom->getAttributes()->commit();
		}

		// системное сообщение
		DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->message_text, $messparams, 0, 'YELLOW' );

		$res = "";
		$sql = "";

		// рефреш с актуализацией ресурсов
		if ( $_SESSION['profi_game'] )
		{
			$noCellData = true;
		} else {
			$noCellData = false;
		}

		if(!$this->refresh(2, "", "", 1, $noCellData))
		{
			return false;
		}
		
		
		if(isset($_SESSION['profi_game'] ) && ($_SESSION['profi_game'] == true))
		{
			$this->sendHalper();
		}
		
		return true;
	}

	function sendHalper()
	{
		$str = '<?xml version="1.0" encoding="utf-8" ?>'."\n".'<sellok><article id_category="'.$this->id_category.'" id="'.$this->id_article.'" amount="'.$this->amount.'" /><sellok/>';
		$GLOBALS['oh']->addString($str , 'helper');
	}
	
	// время окончания эвента
	function getEventEndTime()
	{
		global $common;
		global $params;

		$sql = sprintf ( "select now()+
				(select (text((".$this->worldSettings->get('TRADE_DELAY').")))::interval) as starttime,
				now()+
				(select (text((".$this->worldSettings->get('TURN_TIME')."*60*%d
				)+
				(".$this->worldSettings->get('TRADE_DELAY').")))::interval) as endtime",

		$this->tradetime
		);
		$res = $common->getResultArray ( "starttime_endtime", $sql, "", "", "" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Sell::getEventEndTime1";
			return false;
		}

		$this->endtime = $res[0]['endtime'];
		$this->starttime = $res[0]['starttime'];
		return true;
	}

	function createEventParams ()
	{
		global $params;

		$this->eventparams["ID_Seller"] = $params["uiid"];
		$this->eventparams["ID_TradeScope"] = $this->id_tradescope;
		$this->eventparams["ID_Category"] = $this->id_category;
		$this->eventparams["ID_Article"] = $this->id_article;
		$this->eventparams["Amount"] = $this->amount;
		$this->eventparams["StartPrice"] = $this->startprice;
		$this->eventparams["ID_Language"] = $params["lang"];
		$this->eventparams["ID_World"] = $this->world;

		return true;
	}
}

?>