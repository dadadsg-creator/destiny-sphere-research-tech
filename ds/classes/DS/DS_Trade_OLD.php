<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Trade extends DS_Base
{
	
	static function UpdateTradeLot(DE_TradeLot $lot)
	{
		$common = self::getCommon();	
		 
		$sql = "UPDATE ds_trade_lots
				SET 
					end_date = '".$lot->getEndDate()->getPgFormat()."',
					best_price = ".$lot->getBestPrice()."
				WHERE id_trade_lot = ".$lot->getID()."";				

		$res = $common->getResultArray( "", $sql);		
		assert(empty($common->errormsg));			
	}
	
	static function GetTradeCourseForResources(DE_TradeArticle $fromArticle, DE_TradeArticle $toArticle, DE_World $world)
	{
		$common = self::getCommon();	
		
		$sql = "
			SELECT *
			FROM
    			ds_trade_course AS tc
    			INNER JOIN ds_trade_resorce_course_data AS trcd ON (trcd.id_tradecourse = tc.id_tradecourse)
			WHERE
    			trcd.id_resource = ".$fromArticle->getArticleID()."
    			AND
    			trcd.is_current = true
    			AND
    			trcd.id_world = ".$world->getID()."
    			AND
    			tc.id_tradecategory = ".$toArticle->getTradeCategoryID()."
    			AND
    			tc.id_article = ".$toArticle->getArticleID()."";				

		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) == 1);				
		
		return $res[0];
	}
	
	static function GetTradeCourseForUnits(DE_TradeArticle $fromArticle, DE_TradeArticle $toArticle, DE_World $world)
	{
		$common = self::getCommon();	
		
		$sql = "
			SELECT *
			FROM
    			ds_trade_course AS tc
    			INNER JOIN ds_trade_units_cours_data AS trcd ON (trcd.id_tradecourse = tc.id_tradecourse)
			WHERE
    			trcd.id_unit = ".$fromArticle->getArticleID()."
    			AND
    			trcd.is_current = true
    			AND
    			trcd.id_world = ".$world->getID()."
    			AND
    			tc.id_tradecategory = ".$toArticle->getTradeCategoryID()."
    			AND
    			tc.id_article = ".$toArticle->getArticleID()."";	
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) == 1);				
		
		return $res[0];
	}
	
	static function GetTradeCourseForArtefacts(DE_TradeArticle $fromArticle, DE_TradeArticle $toArticle, DE_World $world)
	{
		$common = self::getCommon();	
		
		$sql = "
			SELECT *
			FROM
    			ds_trade_course AS tc
    			INNER JOIN ds_trade_artefact_course_data AS trcd ON (trcd.id_tradecourse = tc.id_tradecourse)
			WHERE
    			trcd.id_artefact = ".$fromArticle->getArticleID()."
    			AND
    			trcd.is_current = true
    			AND
    			trcd.id_world = ".$world->getID()."
    			AND
    			tc.id_tradecategory = ".$toArticle->getTradeCategoryID()."
    			AND
    			tc.id_article = ".$toArticle->getArticleID()."";	
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		
		assert(empty($common->errormsg) && count($res) == 1);				
		
		return $res[0];
	}
	
	static function TradeTypeDataByID($tradeTypeID, $worldID)
	{
		assert(is_int($tradeTypeID) && $tradeTypeID>0);
		assert(is_int($worldID) && $worldID>0);
		
		$common = self::getCommon();
		
		$sql = "SELECT *
				FROM
    				ds_trade_type AS tt
    				INNER JOIN ds_trade_type_data AS ttd ON (ttd.id_tradetype = tt.id_tradetype AND ttd.is_current = true)
				WHERE
     				tt.id_tradetype = $tradeTypeID
     				AND
     				ttd.id_world = $worldID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) == 1);				
		
		return $res[0];
	}
			
	static public function GetTradeCategoryResource($categoryID, $articleID, DE_World $world)
	{		
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT
					$categoryID AS category_id,					
					r.id_attributename AS type_id,
					r.id_attributename AS article_id,
					r.id_locolizedshortname AS localizedname_id,
					a.attributename AS internal_name					
				FROM
					ds_resource AS r
					INNER JOIN ds_attributename AS a ON (a.id_attributename = r.id_attributename)					
				WHERE 
					r.id_attributename = $articleID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) == 1);				
		
		return $res[0];
	}
	
	static public function GetTradeCategoryUnit($categoryID, $articleID, DE_World $world)
	{		
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		
		$common = self::getCommon();
				
		$sql = "
				SELECT
					2 AS category_id,					
					uc.id_unitclass AS type_id,
					u.id_localizedname as localizedname_id,
					u.id_unit AS article_id,					
					u.unitname AS internal_name			
				FROM
					ds_unit AS u
					INNER JOIN ds_unithasclass AS uhc ON (uhc.id_unit = u.id_unit)
    				INNER JOIN ds_unitclass AS uc ON (uc.id_unitclass = uhc.id_unitclass)    				
				WHERE
					u.id_unit = ".$articleID."";		
		$res = $common->getResultArray ( "", $sql, "", "", "");	
		assert(empty($common->errormsg) && count($res) > 0);
		
		return $res[0];
	}
	
	static public function GetTradeCategoryArtefact($categoryID, $articleID, DE_World $world)
	{		
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT 
					$categoryID AS category_id,
					--ac.value AS stazis,
					a.id_artefactprobability AS type_id,
					a.id_artefact AS article_id,
					a.id_localizedname as localizedname_id,
					a.artefactname AS internal_name
				FROM 
					ds_artefact AS a
					--INNER JOIN ds_trade_artefact_course_data AS ac ON (ac.id_artefact = a.id_artefact AND ac.is_current = true AND ac.id_world = ".$world->getID().")					
				WHERE 
					a.id_artefact = $articleID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) == 1);				
		return $res[0];
	}
	
	static public function GetTradeCategoryMoney($categoryID, $articleID, DE_World $world)
	{
		assert(is_int($categoryID) && $categoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT
					$categoryID AS category_id,					
					m.id_money AS type_id,
					m.id_money AS article_id,
					m.id_locolizedname AS localizedname_id,
					m.money_name AS internal_name					
				FROM
					ds_money AS m										
				WHERE  
					m.id_money = ".$articleID."";						
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) == 1);				
	
		return $res[0];
	}
	

	static function GetTradeTypeByTradeArticlesAndWorld(DE_TradeArticle $fromTradeArticle, DE_TradeArticle $toTradeArticle, DE_World $world)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT *
			FROM
    			ds_trade_type AS tt
    			INNER JOIN ds_trade_type_data AS ttd ON (ttd.id_tradetype = tt.id_tradetype AND ttd.is_current = true)
			WHERE
     			tt.id_trade_by_category = ".$toTradeArticle->getTradeCategoryID()."
     			AND
     			tt.id_trade_by_type = ".$toTradeArticle->getTypeCategoryID()."
     			AND
     			tt.id_trade_on_category = ".$fromTradeArticle->getTradeCategoryID()."
     			AND
     			tt.id_trade_on_type = ".$fromTradeArticle->getTypeCategoryID()."
     			AND
     			ttd.id_world = ".$world->getID()."";				
		$res = $common->getResultArray ("", $sql, "", "", "");
		assert(empty($common->errormsg) && count($res) == 1);				
		
		return $res[0];
	}
	
	static function CheckCombTradeScope(DE_Comb $comb, $tradeScopeID)
	{
		assert(is_int($tradeScopeID) && $tradeScopeID > 0);
		
		$common = self::getCommon();
		
		$sql = "
				SELECT * 
				FROM ds_comballowedtradescope 
				WHERE
					id_comb = ".$comb->getID()."
					AND
					id_tradescope = $tradeScopeID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));				
		
		if(count($res) > 0)
		{
			return true;
		}else{
			return false;
		}		
	}
	
	static function GetTradeCategoryNameByID($categoryID)
	{
		assert(is_int($categoryID) && $categoryID > 0);
		
		$common = self::getCommon();
		
		$sql = "
				SELECT * 
				FROM ds_trade_category 
				WHERE					
					id_tradecategory = $categoryID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) == 1);				
				
		return $res[0]['categoryname'];		
	}
	
	static function GetTradeCategoryIDByName($categoryName)
	{
		assert(preg_match('/^[A-Z_]+$/', $categoryName));

		$common = self::getCommon();
		
		$sql = "
				SELECT id_tradecategory 
				FROM ds_trade_category 
				WHERE					
					categoryname = '$categoryName'";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) == 1);				
				
		return (int)$res[0]['id_tradecategory'];	
	}
	
	static function GetNewTradeLotID()
	{
		$common = self::getCommon();
		
		return self::getNextSeqVal($common, 'ds_trade_lot_seq');
	}
	
	static function InsertTradeLot(DE_TradeLotPrototipe $tradeLotPrototipe, DE_EventQueue $eventQueue, DE_World $world)
	{
		
		$common = self::getCommon();
		
		$sql = "
				INSERT INTO ds_trade_lots
				(
					id_trade_lot,
					id_tradescope,
					id_article,
					amount,
					startprice,
					id_seller,
					id_seller_comb,
					is_auction,
					is_personal,
					person_name,
					person_password,
					start_date,
					end_date,
					id_tradetype,
					id_eventqueue,
					id_world,
					is_anonymous,
					seller_ip					
				)VALUES(
					".$tradeLotPrototipe->get_tradeLotID().",
					".$tradeLotPrototipe->get_tradeScopeID().",
					".$tradeLotPrototipe->get_realFromArticleID().",					
					
					".$tradeLotPrototipe->get_amount().",
					".$tradeLotPrototipe->get_price().",
					
					".$tradeLotPrototipe->get_fromComb()->getUserInstance()->getID().",
					".$tradeLotPrototipe->get_fromComb()->getID().",
					
					'".$tradeLotPrototipe->get_isAuction(true)."',
					'".$tradeLotPrototipe->get_isPersonal(true)."',
					
					'".$tradeLotPrototipe->get_personName()."',
					'".$tradeLotPrototipe->get_personPassword()."',
					'".$tradeLotPrototipe->get_startDate()->getPgFormat()."',
					'".$tradeLotPrototipe->get_endDate()->getPgFormat()."',
					".$tradeLotPrototipe->get_tradeType()->getTradeTypeID().",
					".$eventQueue->getID().",
					".$world->getID().",
					'".$tradeLotPrototipe->get_isAnonymous(true)."',
					'".$tradeLotPrototipe->get_sellerIP()."'
				)";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) );	
	}
	
	static function GetTradeLotByID($tradeLotID)
	{
		assert(is_int($tradeLotID) && $tradeLotID > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT 
					*
				FROM 
					ds_trade_lots AS tl
					LEFT JOIN ds_trade_bids AS tb ON (tb.id_trade_lot = tl.id_trade_lot)
				WHERE  
					tl.id_trade_lot = ".$tradeLotID;				
		$res = $common->getResultArray ( "", $sql);		
		assert(empty($common->errormsg) && (count($res == 0) || count($res) == 1));	
		
		if(count($res) == 0)
		{
			return array();
		}else{
			return $res[0];	
		}
	}
	
	static public function GetAllUserLots($UIID)
	{
		assert(is_int($UIID) && $UIID>0);
		
		$common = self::getCommon();
		
		$sql = "SELECT 					
					tl.id_trade_lot AS id,
					*
				FROM 
					ds_trade_lots AS tl
					LEFT JOIN ds_trade_bids AS tb ON (tb.id_trade_lot = tl.id_trade_lot)
				WHERE  
					tl.id_seller = $UIID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));				

		return $res;
	}
	
	static public function GetAllUserBids($UIID)
	{
		assert(is_int($UIID) && $UIID>0);
		
		$common = self::getCommon();
		
		$sql = "SELECT	
					tl.id_trade_lot AS id,
					*
				FROM 
					ds_trade_lots AS tl
					LEFT JOIN ds_trade_bids AS tb ON (tb.id_trade_lot = tl.id_trade_lot)
				WHERE  
					tb.id_userinstance_bid = $UIID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));				
		
		return $res;
	}
	
	static function GetTradeLotIDByEventQueueID($eventQueueID)
	{
		assert(is_int($eventQueueID) && $eventQueueID > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT id_trade_lot
				FROM ds_trade_lots
				WHERE  id_eventqueue = ".$eventQueueID;				
		$res = $common->getResultArray ( "", $sql, "", "", "");
		assert(empty($common->errormsg) && count($res) == 1);	
		
		
		assert(key_exists('id_trade_lot', $res[0]));
		
		return (int)$res[0]['id_trade_lot'];
	}
	
	static function DeleteTradeLotByID($id)
	{
		assert(is_int($id) && $id > 0);
		
		$common = self::getCommon();
		
		$sql = "DELETE FROM ds_trade_lots
				WHERE  id_trade_lot = ".$id;				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));	
	}
	
	static public function InsertTradeHistory(DE_TradeLot $tradeLot, GB_Date $bayDate, $ip, $currentCourse, $realCourse, $delta, $deltaCourse, $kv, $kn, $weightNano)
	{
		$common = self::getCommon();	
		
		assert(preg_match('/^([0-9]{1,3}\.?){4}$/', $ip));	
		assert(is_float($currentCourse) && $currentCourse > 0);
		assert(is_float($realCourse) && $realCourse > 0);
		assert($delta == 'l' || $delta == 'h' || $delta == 'e');
		assert(is_float($kv) && $kv >= 0);
		assert(is_float($kn) && $kn >= 0);
		assert($weightNano > 0);
		
		$fromTradeArticle = DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(
								$tradeLot->getTradeType()->getTradeFromCategoryID(), 
								$tradeLot->getArticleID()
							);
							
		$toTradeArticle = 	DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld(
								$tradeLot->getTradeType()->getTradeToCategoryID(), 
								$tradeLot->getTradeType()->getTradeToTypeID()
							);
		
		$fromPower = $fromTradeArticle->getStazis() * $tradeLot->getAmount();
		$toPower = $toTradeArticle->getStazis() * $tradeLot->getCurrentPrice();
		
		$isAuction = 
							
		$sql = "
			INSERT INTO ds_trade_history
			(
				from_user_id,
				world_id,
				to_user_id,
				id_from_category,
				id_from_article,
				from_ammount,
				id_to_category,
				id_to_article,
				to_ammount,
				start_date,
				end_date,
				from_comb_id,
				to_comb_id,
				tax_summ,
				id_trade, 
				id_trade_scope,
				from_power,
				to_power,
				is_auction,
				is_personal,
				id_eventqueue,
				start_price,
				from_ip,
				to_ip,
				real_course,
				current_course,
				delta,
				delta_course,
				kv,
				kn,
				weight_nano,
				id_trade_type
			)VALUES(
				".$tradeLot->getSeller()->getUserID().",
				".$tradeLot->getWorldID().",
				".$tradeLot->getCurrentCustomer()->getUserID().",
				".$tradeLot->getTradeType()->getTradeFromCategoryID().",
				".$tradeLot->getArticleID().",
				".$tradeLot->getAmount().",
				".$tradeLot->getTradeType()->getTradeToCategoryID().",
				".$tradeLot->getTradeType()->getTradeToTypeID().",
				".$tradeLot->getCurrentPrice().",
				'".$tradeLot->getStartDate()->getPgFormat()."',
				'".$bayDate->getPgFormat()."',
				".$tradeLot->getSellerComb()->getID().",
				".$tradeLot->getCurrentCustomerComb()->getID().",
				".$tradeLot->getTaxSumm().",
				".$tradeLot->getID().",
				".$tradeLot->getTradeScopeID().",
				".$fromPower.",
				".$toPower.",
				'".$tradeLot->getIsAuction(true)."',
				'".$tradeLot->getIsPersonal(true)."',
				".$tradeLot->getLotEvent()->getID().",
				".$tradeLot->getPrice().",
				'".$tradeLot->getSellerIP()."',
				'".$ip."',
				".$currentCourse.", 
				".$realCourse.", 
				'".$delta."', 
				".$deltaCourse.", 
				".$kv.", 
				".$kn.", 
				".$weightNano.",
				".$tradeLot->getTradeType()->getTradeTypeID()."					
			)";				
		$res = $common->getResultArray ( "", $sql, "", "", "");				
		assert(empty($common->errormsg));
	}	
	
	static public function DeleteBid(DE_TradeLot $tradeLot)
	{
		$common = self::getCommon();
		
		$sql = "DELETE FROM ds_trade_bids WHERE id_trade_lot = ".$tradeLot->getID();				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
	}
	
	static public function GetLotBid(DE_TradeLot $tradeLot)
	{
		$common = self::getCommon();
		
		$sql = "SELECT * FROM ds_trade_bids WHERE id_trade_lot = ".$tradeLot->getID();				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg) && count($res) == 0 || count($res) == 1);
		
		if(count($res) == 0)
		{
			return false;
		}else{
			return $res[0];	
		}
	}

	static public function DeleteUserFromTradeEvent(DE_TradeLot $tradeLot, DE_UserInstance $userInstance)
	{
		$common = self::getCommon();
		
		$sql = "DELETE FROM ds_eventparticipant 
				WHERE 
					id_userinstance = ".$userInstance->getID()."
					AND
					id_eventqueue = ".$tradeLot->getLotEvent()->getID();				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
	}
	
	static public function InsertNewBid(DE_TradeLot $tradeLot, DE_Comb $combFrom, GB_Date $bidDate, $newPrice)
	{
		assert(is_float($newPrice) && $newPrice > 0);
		
		$common = self::getCommon();
		
		$sql = "INSERT INTO ds_trade_bids
				(
					id_trade_lot,
					id_userinstance_bid,
					add_date_bid,
					price_bid,
					id_comb_bid
				)VALUES(
					".$tradeLot->getID().",
					".$combFrom->getUserInstance()->getID().",
					'".$bidDate->getPgFormat()."',
					".$newPrice.",
					".$combFrom->getID()."					
				)";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
	}	
	
	static public function InsertNewEventParticipant(DE_TradeLot $tradeLot, DE_UserInstance $userInstance)
	{
		$common = self::getCommon();
		
		$sql = "INSERT INTO ds_eventparticipant 
				(id_userinstance, id_eventqueue)
				VALUES
				(".$userInstance->getID().", ".$tradeLot->getLotEvent()->getID().")";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
	}	
	
	//-----------------------------------------------------------------------------------
	
	static public function AddNewArtefactInstanceToLot($tradeLotID, $artefactInstanceID)
	{
		assert(is_int($tradeLotID) && $tradeLotID > 0);
		assert(is_int($artefactInstanceID) && $artefactInstanceID > 0);
		
		$common = self::getCommon();
		
		$sql = "INSERT INTO ds_trade_lot_artefactinstances
				(id_artefactinstance, id_trade_lot)
				VALUES
				($artefactInstanceID, $tradeLotID)";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
	}
	
	static public function GettAllArtefactInstanceToLot($tradeLotID)
	{
		assert(is_int($tradeLotID) && $tradeLotID > 0);		
		
		$common = self::getCommon();
		
		$sql = "SELECT *
				FROM ds_trade_lot_artefactinstances
				WHERE id_trade_lot = $tradeLotID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}

	static public function AddArtefactToTradeHistory($tradeLotID, $artefactInstanceID)
	{
		assert(is_int($tradeLotID) && $tradeLotID > 0);
		assert(is_int($artefactInstanceID) && $artefactInstanceID > 0);
		
		$common = self::getCommon();
		
		$sql = "INSERT INTO ds_trade_history_artefactinstances
				(id_artefactinstance, id_trade)
				VALUES
				($artefactInstanceID, $tradeLotID)";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
	}
	
	static public function BindArtefactEvent($artefactInstanceID, $eventQueueID)
	{
		assert(is_int($eventQueueID) && $eventQueueID > 0);
		assert(is_int($artefactInstanceID) && $artefactInstanceID > 0);
		
		$common = self::getCommon();
		
		$sql = "INSERT INTO ds_trade_deliverygood_artefactinstances
				(id_artefactinstance, id_eventqueue)
				VALUES
				($artefactInstanceID, $eventQueueID)";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
	}
	
	static public function GetAllArtefactsFromEvent($eventQueueID)
	{
		assert(is_int($eventQueueID) && $eventQueueID > 0);		
		
		$common = self::getCommon();
		
		$sql = "SELECT * FROM ds_trade_deliverygood_artefactinstances
				WHERE  id_eventqueue = $eventQueueID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	//----------------------------------------------------------------------------------------------------------
	
	static public function LogSell(DE_TradeLot $lot)
	{
		
		$common = self::getCommon();
		
		$sql = "INSERT INTO ds_trade_history_sells
				(
					id_trade_lot,
					id_userinstance,
					id_category,
					id_article,
					amount, 
					id_trade_scope,
					is_personal,
					is_auction,
					for_person,
					id_comb,
					id_category_to,
					id_article_to,
					price,
					end_sell_date
				)
				VALUES
				(
					".$lot->getID().",
					".$lot->getSeller()->getID().",
					".$lot->getTradeType()->getTradeFromCategoryID().",
					".$lot->getArticleID().",
					".$lot->getAmount().",
					".$lot->getTradeScopeID().",
					'".$lot->getIsPersonal(true)."',
					'".$lot->getIsAuction(true)."',
					'".$lot->getPersonName()."',
					".$lot->getCurrentCustomerComb()->getID().",
					".$lot->getTradeType()->getTradeToCategoryID().",
					".$lot->getTradeType()->getTradeToTypeID().",
					".$lot->getPrice().",
					'".$lot->getEndDate()->getPgFormat()."'
				)";				
		$res = $common->getResultArray ( "", $sql);		
		assert(empty($common->errormsg));
	}
	
	
	static public function MarkBidLogAsCanceled(DE_TradeLot $lot, $IP)
	{
		assert(preg_match('/^([0-9]{1,3}\.?){4}$/', $IP));
		
		$common = self::getCommon();
		
		$sql = "
			UPDATE 
				ds_trade_history_bid
			SET
				cancel_date = now(),
				cancel_ip = '".$IP."'
			WHERE
				id_trade = ".$lot->getID()."
				AND
				cancel_date IS NULL
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function InsertBidLog(DE_TradeLot $lot, $IP)
	{
		assert(preg_match('/^([0-9]{1,3}\.?){4}$/', $IP));
		
		$common = self::getCommon();
		
		$sql = "
			INSERT INTO ds_trade_history_bid
				(id_trade, id_user, summ, bid_date, ip)
			VALUES
				(".$lot->getID().", ".$lot->getCurrentCustomer()->getUserID().", ".$lot->getCurrentPrice().", now(), '".$IP."')
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function GetCountUserLots($UIID)
	{
		assert(is_int($UIID) && $UIID>0);
		
		$common = self::getCommon();
		
		$sql = "SELECT
					count(*) as count
				FROM 
					ds_trade_lots
				WHERE  
					id_seller = $UIID";				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));				

		if (count($res) == 0) {
			return 0;
		} 
		
		return $res[0]['count'];
	}
		
	static public function GetTradeHistory($idFromCategory, $idFromArticle, $idToCategory, $idToArticle)
	{
		assert(is_int($idFromCategory) && $idFromCategory > 0);
		assert(is_int($idFromArticle) && $idFromArticle > 0);
		assert(is_int($idToCategory) && $idToCategory > 0);
		assert(is_int($idToArticle) && $idToArticle > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT
					*
				FROM
				    ds_trade_history AS hist
				WHERE
				    id_from_category = ".$idFromCategory."
				     AND
				    id_from_article = ".$idFromArticle."
					 AND
					id_to_category = ".$idToCategory."
				     AND
				    id_to_article = ".$idToArticle;				
		$res = $common->getResultArray ( "", $sql, "", "", "");		
		assert(empty($common->errormsg));				

		return $res;
	}
}

?>