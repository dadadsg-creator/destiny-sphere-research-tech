<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_SearchEndOldTrades extends SI_Base
{
	static public function GetUserBids($UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		$sql = "
			SELECT 
				t.*,
				tbeq.id_eventqueue
			FROM 
				ds_trade t,
				ds_tradebindeventqueue tbeq
			WHERE 
				t.id_trade = tbeq.id_trade
				AND t.id_currentcustomer = $UIID
				AND t.delivery = false
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function ClearTradeCurrentCustomer($trade)
	{
		assert(is_int($trade) && $trade > 0);
		
		$sql = "
			UPDATE 
				ds_trade
			SET 
				id_currentcustomer = null,
				id_customercomb = null
			WHERE 
				id_trade = $trade
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	static public function DeleteFromEventParticipant($event, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		assert(is_int($event) && $event > 0);
		
		$sql = "
			DELETE FROM 
				ds_eventparticipant
			WHERE 
				id_eventqueue = $event
				AND 
				id_userinstance = $UIID
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	static public function GetUserTrades($UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		$sql = "
				SELECT 
					t.*,
					tc.categoryname,
					tbeq.id_eventqueue
				FROM 
					ds_trade t,
					ds_tradecategory tc,
					ds_tradebindeventqueue tbeq
				WHERE 
					t.id_tradecategory = tc.id_tradecategory
					AND 
					t.id_trade = tbeq.id_trade
					AND 
					t.delivery = false
					AND
					t.id_seller = $UIID
				";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function DeleteTrade($event, $trade)
	{
		assert(is_int($event) && $event > 0);
		assert(is_int($trade) && $trade > 0);
		
		$sql = "
			DELETE FROM 
				ds_tradebindeventqueue
			WHERE 
				id_eventqueue = $event
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));

		$sql = "
			DELETE FROM 
				ds_trade
			WHERE 
				id_trade = $trade
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
}

?>