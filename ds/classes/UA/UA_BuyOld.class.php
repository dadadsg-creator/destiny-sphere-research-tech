<?php $CVS_Version = '$Revision$ ; $Date$';

// команда купить товар на аукционе
class UA_Buy extends UA_Action 
{
	private $id_trade;

	private $newprice;

	private $wrongtrade;

	private $wrongnewprice;

	private $wrongprice;

	private $alreadysoled;

	private $too_low_newprice;

	private $trevelTimeCount = '';

	private $upkeepValueCount = '';

	private $periodFormat = '';

	function __construct() 
	{
		parent::UA_Action ();				
	}

	// проверяем пришедшие параметры
	function checkLocalParams () 
	{

		// id аукциона
		$this->id_trade = (int)$this->root->get_attribute ( "id_trade" );
		assert($this->id_trade > 0);
		

		// ставка
		$this->newprice = (double)$this->root->get_attribute("newprice");
		if($this->newprice <= 0)
		{
			ds_error("New price must be bigger then 0", false);
		}elseif($this->newprice > 2400000000){
			ds_error("New price can't be bigger then 2.400.000.000", false);
		}
		
		return true;
	}

	// проверяем допустимость действия
	function checkAction () 
	{

		global $common;
		global $params;

		// торг уже закончен, идет доставка
		$sql = "SELECT id_trade
				FROM ds_trade
				WHERE 
					id_trade = ".$this->id_trade."
				AND delivery = true";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(sizeof($res)> 0) 
		{
			$this->alreadysoled = 1;
			return true;
		}

		// пробует купить сам у себя
		$sql = "SELECT 
					id_trade
				FROM ds_trade
				WHERE 
						id_trade = ".$this->id_trade."
					AND id_seller = ".$params["uiid"];
		$res = $common->getResultArray ( "", $sql, "", "", "" );
		if($res === false) 
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Buy::checkAction2";
			return false;
		}

		if(sizeof($res) > 0) 
		{
			$this->wrongtrade = 1;
			return true;
		}

		// зона видимости акуциона, немедлненный или нет
		$sql = "SELECT 
					isimmediate,
					id_tradescope,
					id_tradeby
				FROM 
					ds_trade
				WHERE 
					id_trade = ".$this->id_trade;
		$res = $common->getResultArray ( "isimmediate_id_trade_".$this->id_trade, $sql, "", "", "GLOBAL_CACHE" );
		if($res === false) 
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Buy::checkAction3";
			return false;
		}
		$tradescope = $res[0]['id_tradescope'];
		$id_tradeby = $res[0]['id_tradeby'];

		// доступен ли этот tradescope для этой соты
		$sql = 	"SELECT 
					id_tradescope
				 FROM 
				 	ds_comballowedtradescope
				 WHERE 
				 		id_comb = ".$this->comb."
					AND id_tradescope = ".$tradescope;
		$res2 = $common->getResultArray ( "", $sql, "", "", "" );
		if($res2 === false) 
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Buy::checkAction4";
			return false;
		}

		if(sizeof($res2)  == 0) 
		{
			$this->errormsg .= " SYSTEM ERROR - UA_Buy::checkAction5";
			return false;
		}

		if ( $res[0]['isimmediate'] == 'f') 
		{ // не немедленный аукцион

			// берем начальную и текущую цены
			$sql = sprintf ( "
						SELECT 
							startprice,
							currentprice
						FROM 
							ds_trade
						WHERE 
							id_trade = %d",
						$this->id_trade );
			$res = $common->getResultArray ( "startprice_currentprice_id_trade_".$this->id_trade, $sql, "", "", "" );
			if(!$res) 
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Buy::checkAction6";
				return false;
			}

			$currentprice = (int)number_format ( $res[0]['currentprice'], 0, "", "" );
			$startprice = (int)number_format ( $res[0]['startprice'], 0, "", "" );

			if ( $currentprice == "" ) $currentprice = 0;

			// если ставка меньше текущей или начальной цены
			if ( $currentprice >= $this->newprice || $startprice >= $this->newprice) {
				$this->wrongprice = 1;
				return true;
			}

			// модификатор - шаг увеличения ставки
			$mbs = $this->worldSettings->get('MIN_BID_STEP');

			// ставка не должна быть меньше текущей цены + шаг увеличения
			if ( $this->newprice < round($currentprice + $currentprice*$mbs) ) {
				$this->too_low_newprice = round($currentprice + $currentprice*$mbs);
				return true;
			}

		} else { // немедленный аукцион

			$sql = sprintf ( "select startprice
					from ds_trade
					where id_trade = %d",
			$this->id_trade );
			$res = $common->getResultArray ( "startprice_id_trade_".$this->id_trade, $sql, "", "", "" );
			if(!$res) 
			{
				if( isset( $_SESSION['profi_game'] ) && ( $_SESSION['profi_game'] == true ) )
				{
					$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><status />";
					$doc = domxml_open_mem ( $xml );
					$root = $doc->document_element ();
					$elem = $doc->create_text_node ( '2' );
		
					if( empty($SESSION['s_login']) )
					{
		
						$SESSION['s_login'] = DE_UserInstance::CreateFromID((int)$this->uiid)->getUser()->getName();
					}

					$root->set_attribute( "newname", $SESSION['s_login'] );
					$root->append_child ( $elem );
					$GLOBALS['oh']->addString( $doc->dump_mem() , 'helper' );
				}
				$this->alreadysoled = 1;
				return true;


				//ds_error("Cant find this trade. (".$this->id_trade.")");
				//return false;
			}

			$startprice = (int)$res[0]['startprice'];

			$this->newprice = $startprice;

			$currentprice = $startprice;

			// ставка и текущая цена = начальная цена
		}

		// хватает ли на ставку ресурса на соте
		$attributeName = ST_Attributes::Create()->getAttributeNameByAttributeNameID((int)$id_tradeby);
		$combAttribues = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		
		if($combAttribues->haveAttribute('CURRENT_AMOUNT', $attributeName))
		{
			$chr = $combAttribues->getAttribute('CURRENT_AMOUNT', $attributeName);
		}else {
			ds_error('Uncorrect attribute name = '.$attributeName);
		}
		
				
		if ( $chr < $this->newprice ) 
		{
			$this->wrongnewprice = 1;
			return true;
		}

		return true;
	}

	// выполняем команду
	function performAction()
	{
		global $params;
		global $common;

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
		
		// посылаем системное сообщение и выходим
		if ( $this->alreadysoled == 1 ) {
			$messparams[0] = "";
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'ALREADY_SOLED', $messparams, 0, 'YELLOW');			

			if ( !$this->refresh ( 2 ) ) {
				return false;
			}

			if( isset( $_SESSION['profi_game'] ) && ( $_SESSION['profi_game'] == true ) ){
				$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><status />";
				$doc = domxml_open_mem ( $xml );
				$root = $doc->document_element ();
				$elem = $doc->create_text_node ( '2' );
				$root->append_child ( $elem );
				$GLOBALS['oh']->addString( $doc->dump_mem() , 'helper' );
			}

			return true;
		}

		// посылаем системное сообщение и выходим
		if ( $this->wrongtrade == 1 ) {
			$messparams[0] = "";
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'WRONG_TRADE_PARTICIPANT', $messparams, 0, 'YELLOW');			

			if( isset( $_SESSION['profi_game'] ) && ( $_SESSION['profi_game'] == true ) ){

				$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><status />";
				$doc = domxml_open_mem ( $xml );
				$root = $doc->document_element ();
				$elem = $doc->create_text_node ( '0' );
				$root->append_child ( $elem );
				$GLOBALS['oh']->addString( $doc->dump_mem() , 'helper' );
			}

			if ( !$this->refresh ( 2 ) )
			{
				return false;
			}

			return true;
		}

		// посылаем системное сообщение и выходим
		if ( $this->wrongprice == 1 ) {
			$messparams[0] = "";
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'WRONG_PRICE', $messparams, 0, 'YELLOW' );				
			
			if ( !$this->refresh ( 2 ) ) {
				return false;
			}

			if( isset( $_SESSION['profi_game'] ) && ( $_SESSION['profi_game'] == true ) ){

				$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><status />";
				$doc = domxml_open_mem ( $xml );
				$root = $doc->document_element ();
				$elem = $doc->create_text_node ( '0' );
				$root->append_child ( $elem );
				$GLOBALS['oh']->addString( $doc->dump_mem() , 'helper' );
			}

			return true;
		}

		// посылаем системное сообщение и выходим
		if ( $this->wrongnewprice == 1 ) {
			$messparams[0] = "";
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'WRONG_NEWPRICE', $messparams, 0, 'YELLOW' );
				

			if ( !$this->refresh ( 2 ) ) {
				return false;
			}

			if( isset( $_SESSION['profi_game'] ) && ( $_SESSION['profi_game'] == true ) ){

				$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><status />";
				$doc = domxml_open_mem ( $xml );
				$root = $doc->document_element ();
				$elem = $doc->create_text_node ( '0' );
				$root->append_child ( $elem );
				$GLOBALS['oh']->addString( $doc->dump_mem() , 'helper' );
			}

			return true;
		}

		// посылаем системное сообщение и выходим
		if($this->too_low_newprice) 
		{
			$messparams[0] = "{b} ".$this->too_low_newprice."{/b}";
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TOO_LOW_NEWPRICE', $messparams, 0, 'YELLOW' );
				

			if ( !$this->refresh ( 2 ) ) {
				return false;
			}

			if( isset( $_SESSION['profi_game'] ) && ( $_SESSION['profi_game'] == true ) ){

				$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><status />";
				$doc = domxml_open_mem ( $xml );
				$root = $doc->document_element ();
				$elem = $doc->create_text_node ( '0' );
				$root->append_child ( $elem );
				$GLOBALS['oh']->addString( $doc->dump_mem() , 'helper' );
			}

			return true;
		}

		if(SU_Locker::CerateForCurrentDB(__CLASS__)->isLocked($this->id_trade))
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $this->id_trade, __CLASS__);
		}else{
			SU_Locker::CerateForCurrentDB(__CLASS__)->lock($this->id_trade);
		};

		settype ( $this->id_trade, 'integer' );
		assert ( $this->id_trade > 0 );

		// получаем id_eventqueue
		$sql = 	"SELECT 
					id_eventqueue
				 FROM 
				 	ds_tradebindeventqueue
				 WHERE 
				 	id_trade = ".$this->id_trade;
		$res = $common->getResultArray ( "", $sql, "", "", "" );
		assert(empty($common->errormsg) && is_array($res) && count ($res) > 0);
		
		$eq = (int)$res[0]['id_eventqueue'];		
		assert ( $eq > 0 );

		// получаем всю информацию об аукционе
		$sql = "SELECT *
				FROM 
					ds_trade t,
					ds_tradecategory tc
				WHERE 
					    t.id_tradecategory = tc.id_tradecategory
					AND t.id_trade = ".$this->id_trade;		 
		$res = $common->getResultArray ( "trade_id_trade_".$this->id_trade, $sql, "", "", "" );
		assert(empty($common->errormsg) && is_array($res) && count ($res) > 0);		

		$id_trade = (int)$res[0]['id_trade'];		
		assert($id_trade>0);

		$id_currentcustomer = (int)$res[0]['id_currentcustomer'];				
		$id_customercomb = (int)$res[0]['id_customercomb'];		
		$currentprice = (int)$res[0]['currentprice'];		
		$startprice = (int)$res[0]['startprice'];		
		assert($startprice > 0);

		$isimmediate = $res[0]['isimmediate'];
		
		$tradeStartDate = GB_TypeFactory::CreateConstant('GB_Date', $res[0]['tradestartdate']);
		$tradeEndDate = GB_TypeFactory::CreateConstant('GB_Date', $res[0]['tradeenddate']);
		
		$tradePeriod = GB_TypeFactory::CreateConstant('GB_TimePeriod', $tradeEndDate->value - $tradeStartDate->value);
		$tradePeriodLeft = GB_TypeFactory::CreateConstant('GB_TimePeriod', time() - $tradeStartDate->value);
		
		$id_tradecategory = (int)$res[0]['id_tradecategory'];		
		assert($id_tradecategory > 0);

		$id_article = (int)$res[0]['id_article'];		
		assert($id_article > 0);

		$amount = (int)$res[0]['amount'];		
		assert ( $amount > 0 );

		$id_tradeby = $res[0]['id_tradeby'];
		settype ( $id_tradeby, 'integer' );
		assert ( $id_tradeby > 0 );

		$id_seller = $res[0]['id_seller'];
		settype ( $id_seller, 'integer' );
		assert ( $id_seller > 0 );

		$id_sellercomb = $res[0]['id_sellercomb'];
		settype ( $id_sellercomb , 'integer' );
		assert ( $id_sellercomb  > 0 );

		$categoryname = $res[0]['categoryname'];
		$messparams[0] = "{b} ".$amount."{/b}";

		// вычитаем ставку
		$attributeName = ST_Attributes::Create()->getAttributeNameByAttributeNameID((int)$id_tradeby);
		DE_CombAttributes::CreateFromCombID((int)$this->comb)->deltaAttribute('CURRENT_AMOUNT', $attributeName, -(float)$this->newprice);
		
		// Апкип на перемещение юнитов
		if($categoryname == "UNIT")
		{
			$combFrom = DE_Comb::CreateFromID((int)$id_sellercomb);
			$unit = DE_Unit::CreateFromID((int)$id_article);
			$this->unit = $unit;
			$amount = (int)$amount;
			$speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1);
			
			if($isimmediate == 'f')
			{
				$event = DE_EventQueue::CreateFromID($eq);
				$tradeEndDate = $event->getEndDate();

				if(isset($id_customercomb) && $id_customercomb > 0)
				{
					// Возвращаем ресурсы игроку, сделавшему предыдущую ставку
					$combTo = DE_Comb::CreateFromID($id_customercomb);
					$combToAttributes = $combTo->getAttributes();
		
					$upkeepValue = GL_FullUnitsMovementCost::CountBuyUnits($combFrom, $combTo, $unit, $amount, $tradeEndDate);
				
					foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
					{
						$combToAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)$upkeepValue->getPay($resourceName));
					}
				}
			} else {
				$tradeEndDate = GB_TypeFactory::CreateBase('GB_Date', time());
			}
			
			$combTo = DE_Comb::CreateFromID((int)$this->comb);
			$combToAttributes = $combTo->getAttributes();
			$unit = DE_Unit::CreateFromID((int)$id_article);
			
			$upkeepValue = GL_FullUnitsMovementCost::CountBuyUnits($combFrom, $combTo, $unit, $amount, $tradeEndDate);
			$this->upkeepValue = $upkeepValue;
			//Снимаем с покупателя ресурсы за доставку
			foreach (GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
			{
				if($combToAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true) >= $upkeepValue->getPay($resourceName))
				{					
					$combToAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)(-$upkeepValue->getPay($resourceName)));
				} else {
					ds_error("UA_Buy: Not enough $resourceName to buy this item.", 1, 1, 1, 0, 'BUY_NOT_ENOUGTH');					
				}
			}
		}
		
		// получаем название товара
		switch($categoryname)
		{
			case "RESOURCE":

			$sql = sprintf ( "select ltl.text as localizedname
						from ds_localizedtextlanguage ltl,
						ds_attributename atn 
						where atn.id_localizedname = ltl.id_localizedtext 
						and atn.id_attributename = %d 
						and ltl.id_language = %d",
			$id_article,
			$params["lang"] );

			$res = $common->getResultArray ( "resource_localizedname_id_article_id_language_".$id_article."_".$params["lang"], $sql, "", "", "GLOBAL_CACHE" );
			if ( !$res ) {
				$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction5";

				return false;
			}

			$messparams[1] = "{b} ".$res[0]['localizedname']."{/b}";

			break;

			case "ARTEFACT":

			$sql = sprintf ( "select ltl.text as localizedname
						from ds_localizedtextlanguage ltl,
						ds_artefact ar,
						ds_artefactinstance ari
						where ar.id_localizedname = ltl.id_localizedtext 
						and ar.id_artefact = ari.id_artefact 
						and ari.id_artefactinstance = %d 
						and ltl.id_language = %d",
			$id_article,
			$params["lang"] );

			$res = $common->getResultArray ( "artefact_localizedname_id_article_id_language_".$id_article."_".$params["lang"], $sql, "", "", "GLOBAL_CACHE" );
			if ( !$res ) {
				$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction6";

				return false;
			}

			$messparams[1] = "{b} ".$res[0]['localizedname']."{/b}";

			break;

			case "UNIT":

			$sql = sprintf ( "select ltl.text as localizedname
						from ds_localizedtextlanguage ltl,
						ds_unit u
						where u.id_localizedname = ltl.id_localizedtext
						and u.id_unit = %d
						and ltl.id_language = %d",
			$id_article,
			$params["lang"] );

			$res = $common->getResultArray ( "unit_localizedname_id_article_id_language_".$id_article."_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res ) {
				$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction7";

				return false;
			}

			$messparams[1] = "{b} ".$res[0]['localizedname']."{/b}";
			break;
		}

		// СООБЩЕНИ ПО ТОРГОВЛЕ //
		if($isimmediate == 't')
		{
			//Есди немедленная покупка
			$combFrom = DE_Comb::CreateFromID((int)$id_sellercomb);
			$combTo = DE_Comb::CreateFromID((int)$this->comb);
			$speed = GB_TypeFactory::CreateConstant('GB_MoveSpeed', (float)1);
			$traveltime = GL_TravelTime::Count($combFrom, $combTo, $speed);
			$this->endtime = time() + $traveltime->value;
			
			if($categoryname == 'UNIT')
			{									
				//Возвращаем продовцу не растраченное
				$seller = DE_UserInstance::CreateFromID((int)$id_seller);
				$returnUpkeep = GL_FullUnitsMovementCost::CountBayUnitReturnPart($seller, $tradePeriod, $tradePeriodLeft, $this->unit, $amount);
				$sellerCombAttributes = DE_CombAttributes::CreateFromCombID((int)$id_sellercomb);
				
				foreach (GB_ResourcesList::$unitsLiveUpkeepResources as $k => $resourceName)
				{
					$sellerCombAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)($returnUpkeep->getReturn($resourceName)));					
				}
				
				$messparams[2] = "{b} ".$this->upkeepValue->getShortPayList()."{/b}";			
				$messageType = 'BET_TRADING_MESS1';
			} else {				
				$messageType = 'BET_TRADING_MESS2';
			}
						
			DE_SystemMessages::AddMessageFromAction(__CLASS__, $messageType, $messparams, 0, 'YELLOW');
		} else {//Еслиствка на аукционе
		
			// добавляем сообщение что сделана ставка
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "BET_TRADING_MESS", $messparams, 0, 'YELLOW');
		}



		//если немедленный аукцион - сразу покупает
		if($isimmediate == 't') 
		{
			$id_currentcustomer = $params['uiid'];			

			// пишем в лог
			$sql = sprintf ( "insert into ds_tradehistory
					(trade_date, id_tradecategory, id_article, amount, id_seller, id_customer, id_tradeby, price )
					values ( now(), %d, %d, %d, %d, %d, %d, %d )",
			$id_tradecategory,
			$id_article,
			$amount,
			$id_seller,
			$id_currentcustomer,
			$id_tradeby,
			$startprice );
			$res = $common->executeQuery($sql);			
			if ( $res === false ) 
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction8";
				return false;
			}
			
			// создаем эвенты доставки
			$this->eventparams["ID_Seller"] = $id_seller;
			$this->eventparams["ID_SellerComb"] = $id_sellercomb;
			$this->eventparams["ID_CustomerComb"] = $this->comb;
			$this->eventparams["ID_Customer"] = $params['uiid'];
			$this->eventparams["ID_Category"] = $id_tradecategory;
			$this->eventparams["ID_Article"] = $id_article;
			$this->eventparams["Amount"] = $amount;
			$this->eventparams["Price"] = $this->newprice;
			$this->eventparams["ID_TradeBy"] = $id_tradeby;
			$this->eventparams["ID_Language"] = $params['lang'];
			$this->eventparams["ID_World"] = $this->world;
			
			$participants[] = (int)$id_seller;
			$this->createEvent ('TRADING_DELIVERY_PAYMENT', (int)$id_seller);
			
			$this->createEvent ('TRADING_DELIVERY_GOOD');
						
			// стираем аукцион
			$sql = sprintf ( "delete from ds_tradebindeventqueue
					where id_trade = %d",
			$id_trade );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction27";

				return false;
			}

			$sql = sprintf ( "delete from ds_trade
					where id_trade = %d",
			$id_trade );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction28";

				return false;
			}

			// прибиваем старый эвент
			$sql = sprintf ( "update ds_eventqueue
					set istrash = true
					where id_eventqueue = %d",
			$eq );

			$res = $common->executeQuery ( $sql );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction29";

				return false;
			}

			// если аукцион не немедленный - сделать новую ставку
		} else {
			// добавляем сообщение что сделана ставка
			$sql = sprintf ( "update ds_trade
					set currentprice = %d,
					id_currentcustomer = %d,
					id_customercomb = %d
					where id_trade = %d",
			$this->newprice,
			$params["uiid"],
			$this->comb,
			$this->id_trade );

			$res = $common->executeQuery ( $sql );
			if($res === false)
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction30";
				return false;
			}

			if(!@$id_currentcustomer)
			{ 
				// первая ставка
				// добавляем покупателя в участники эвента
				$sql = sprintf ( "insert into ds_eventparticipant
							( id_eventqueue, id_userinstance )
							values ( %d, %d )",
					$eq,
					$params["uiid"] );

				$res = $common->executeQuery ( $sql );
				if($res === false) 
				{
					$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction31";
					return false;
				}
			} else { // перебивает ставку
				// возвращаем ставку предыдущему покупателю
				$attributeName = ST_Attributes::Create()->getAttributeNameByAttributeNameID((int)$id_tradeby);
				DE_CombAttributes::CreateFromCombID((int)$id_customercomb)->deltaAttribute('CURRENT_AMOUNT', $attributeName, (float)$currentprice);
			
				// меняем участника эвента на нового покупателя
				$sql = sprintf ( "update ds_eventparticipant
						set id_userinstance = %d
						where id_eventqueue = %d
						and id_userinstance = %d",
					$params["uiid"],
					$eq,
					$id_currentcustomer );

				$res = $common->executeQuery($sql);
				
				if($res === false)
				{
					$this->errormsg .= " SYSTEM ERROR - UA_Buy::performAction33";
					return false;
				}
			}
		}

		$res = "";
		$sql = "";
		$res2 = "";

		// рефреш с актуализацией информации о ресурсах соты
		if(!$this->refresh(2, "", "", 1))
		{
			return false;
		}

		if(isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == true))
		{
			$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><status />";
			$doc = domxml_open_mem($xml);
			$root = $doc->document_element();
			$elem = $doc->create_text_node('1');

			if(empty($SESSION['s_login']))
			{
				$SESSION['s_login'] = DE_UserInstance::CreateFromID((int)$this->uiid)->getUser()->getName();
			}

			$root->set_attribute("newname", $SESSION['s_login']);
			$root->append_child($elem);
			$GLOBALS['oh']->addString($doc->dump_mem(), 'helper');
		}

		return true;
	}
	
	public function createEventParams()
	{
	}
}

?>