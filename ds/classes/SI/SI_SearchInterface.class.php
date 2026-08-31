<?php $CVS_Version = '$Revision$ ; $Date$';
class SI_SearchInterface extends SI_Base
{
	/**
	 * Получение списка всех миров
	 *
	 * @return unknown
	 */
	static public function GetWorldList()
	{		
		self::$common->setDbId(0);
		$sql = "
			SELECT
				wc.id_world AS id,
				wc.worldname AS name
			FROM
				ds_world AS wc
			ORDER BY
				wc.id_world
			";
		
		$res = self::$common->getMultyArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function GetUserDataByStoreUserId($id_storeuserdata)
	{
		assert($id_storeuserdata);
		
		$sql = "
			SELECT
				*
			FROM
				ds_storeuserdata AS sud
				INNER JOIN ds_user AS u ON u.id_user = sud.id_user
			WHERE
				sud.id_storeuserdata = $id_storeuserdata
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		
		assert(empty(self::$common->errormsg));
		
		return $res[0];
	}
	
	static public function GetStoreUserCombs($id_userdata)
	{
		assert($id_userdata);
		self::$common->setDbId(0);
		$sql = "
			SELECT
				suc.comb_name_old AS name,
				suc.coordinates_old AS coord,
				suc.id_storeusercombs AS id
			FROM
				ds_storeusercombs AS suc
			WHERE
				suc.id_storeuserdata = ".$id_userdata."
			";
		
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function GetWorldNameByUIID($UIID)
	{
		assert($UIID);
		
		self::$common->setDbId(0);
		$sql = "
			SELECT 
				wc.worldname
			FROM
				ds_worldcollection AS wc
				INNER JOIN ds_userinstance AS ui ON ui.id_world = wc.id_world
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res[0]['worldname'];
	}
	
	static public function GetDbNameAndWorldNameByWorldId($worldId, $dbId = 0)
	{
		settype($worldId, 'int');
		settype($dbId, 'int');
		assert($worldId > 0);		
		
		self::$common->setDbId($dbId);
		$sql = "
			SELECT 
				w.worldname,
				w.id_world
			FROM
				ds_world AS w
			WHERE
				w.id_world = $worldId
			";
		
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		$res[0]['dbname'] = $GLOBALS['dbWorldAccess'][$dbId]['server'];
		
		return $res[0];
	}
	
	/**
	 * Список всех причин сохранения инстанса
	 *
	 * @return unknown
	 */
	static public function GetReasonList()
	{
		self::$common->setDbId(0);
		
		$sql = "
			SELECT
				sur.id_storeuserreason,
				sur.reason_name
			FROM
				ds_storeuserreason AS sur
			ORDER BY
				sur.id_storeuserreason			
			";
		
		$res = self::$common->getResultArray("", $sql, "", "", "");	
		assert(empty(self::$common->errormsg));
		
		foreach($res as $k => $reason)
		{
			$reasonList[$reason['id_storeuserreason']] = $reason['reason_name'];
		}
		
		$reasonList[0] = 'all';
		
		return $reasonList;
	}
	
	/**
	 * Получение списка инстансов по имени юзера
	 *
	 * @param unknown_type $userName
	 * @return unknown
	 */
	static public function GetUserInfoByUserName($userName)
	{
		assert(!empty($userName));
		
		self::$common->setDbId(0);
		
		$sql = "
			SELECT
				ui.id_userinstance,
				u.id_user,
				u.admin,
				ui.id_world,
				w.worldname,
				u.accountactive
			FROM
				ds_userinstance AS ui
				INNER JOIN ds_user AS u ON u.id_user = ui.id_user
				INNER JOIN ds_world AS w ON w.id_world = ui.id_world
			WHERE
				upper(u.username) = upper('$userName')
			";
		
		$user = self::$common->getMultyArray("", $sql, "", "", "");

		if(!$user)
		{
			return false;
		}
		
		return $user;
	}
	
	/**
	 * Получение списка комбов инстанса
	 *
	 * @param unknown_type $UIID
	 * @param unknown_type $dbIdFrom
	 * @return unknown
	 */
	static public function GetCombsNameByUIID($UIID, $dbIdFrom)
	{	
		assert($UIID);
		assert(is_int($dbIdFrom));
		
		self::$common->setDbId($dbIdFrom);
		
		$sql = "
			SELECT
				cn.combname
			FROM
				ds_comb AS c 
				INNER JOIN ds_combname AS cn ON cn.id_comb = c.id_comb
			WHERE
				c.id_userinstance = $UIID
			";
		
		$res = self::$common->getResultArray("", $sql, "", "", "");		
		
		if(!$res)
		{
			return false;			
		}
		
		foreach($res as $k => $comb)
		{
			$name[$k] = $comb['combname'];
		}
		return $name;
	}
	
	static public function GetWorldsList()
	{		
		$sql = "
			SELECT
      			*
			FROM
    			ds_world";
		$res = self::$common->getMultyArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
	
		return $res;
	}
	
	static public function GetWorldsByID($worldID)
	{		
		settype($worldID, 'integer');
		assert($worldID > 0);
		$sql = "
			SELECT
      			*
			FROM
    			ds_world
    		WHERE id_world = ".$worldID;
		$res = self::$common->getMultyArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
	
		assert(count($res) > 0);
		return $res[0];
	}
	
	static public function GetWorldByName($name)
	{		
		assert(!empty($name));
		
		$sql = "
			SELECT
      			*
			FROM
    			ds_world
    		WHERE world_identifier = '".$name."'";
		$res = self::$common->getMultyArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
	
		assert(count($res) > 0);
		return $res[0];
	}
	
	static public function GetCombCoordsAndUIIDByUserNameAndWorldName($username, $worldName)
	{				
		assert(!empty($username));
		assert(!empty($worldName));
		$sql = "
				SELECT
      				ui.id_userinstance,
      				cn.combname,
      				r.ringnumber,
      				cc.complexnumber,
      				c.combnumber,
      				u.id_user,
      				ui.id_world,
      				c.id_comb
				FROM
    				ds_world as w
    				INNER JOIN ds_userinstance AS ui ON (ui.id_world = w.id_world)
    				INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
    				INNER JOIN ds_comb AS c ON (c.id_userinstance = ui.id_userinstance)
    				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
    				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
    				INNER JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
				WHERE
     				w.world_identifier = '$worldName'
     				AND
     				u.username = '$username'
     			ORDER BY r.ringnumber, cc.complexnumber, c.combnumber";
		$res = self::$common->getMultyArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));	
		return $res;
	}
	
	static public function GetInstacesByUserName($userName)
	{		
		assert(!empty($userName));
	
		$sql = "
			SELECT
      			*,
      			(
      				SELECT count(*)
      				FROM ds_comb
      				WHERE id_userinstance = ui.id_userinstance
      			) as combscount
			FROM
    			ds_user as u    			
    			INNER JOIN ds_userinstance as ui on (ui.id_user = u.id_user)
    			INNER JOIN ds_world as w on w.id_world = ui.id_world
    			INNER JOIN ds_race as r on r.id_race = ui.id_race    			
			WHERE
     			u.username = '".$userName."'";     			

		$res = self::$common->getMultyArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));

		return $res;
	}
	
	static public function GetCombsList($uiid)
	{				
		assert((int)$uiid > 0);
		
		$sql = "
				SELECT
      				--cn.combname||' '||r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber as combname
      				cn.combname as combname
				FROM
    				ds_comb as c
    				INNER JOIN ds_combname as cn on (cn.id_comb = c.id_comb)
    				INNER JOIN ds_combcomplex as cc on (cc.id_combcomplex = c.id_combcomplex)
    				INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
				WHERE c.id_userinstance = ".$uiid."
				ORDER BY r.ringnumber";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		$out = array();
		
		foreach($res as $k=>$v)
		{
			$out[] = $v['combname'];
		}
		
		return $out;
	}
	
	static public function GetCombCoordsStringByName($combName)
	{				
		assert(!empty($combName));
		
		$sql = "
				SELECT
      				r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber as coord      				
				FROM
    				ds_comb as c
    				INNER JOIN ds_combname as cn on (cn.id_comb = c.id_comb)
    				INNER JOIN ds_combcomplex as cc on (cc.id_combcomplex = c.id_combcomplex)
    				INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
				WHERE cn.combname = '".$combName."'";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);
		
		return $res[0]['coord'];
	}
	
	static public function GetCombCoordinatesByName($combName)
	{				
		assert(!empty($combName));
		
		$sql = "
				SELECT
      				r.ringnumber as ring,
      				cc.complexnumber as complex,
      				c.combnumber as comb
				FROM
    				ds_comb as c
    				INNER JOIN ds_combname as cn on (cn.id_comb = c.id_comb)
    				INNER JOIN ds_combcomplex as cc on (cc.id_combcomplex = c.id_combcomplex)
    				INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
				WHERE cn.combname = '".$combName."'";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		if(count($res) == 1)
		{
			return $res[0];	
		}else{
			return '';
		}
	}
	
	static public function GetRandomCombIDForPlacementOnRing($ringNumber, $worldID, $existsComb)
	{
		settype($ringNumber, 'integer');
		assert($ringNumber > 0);
		
		settype($worldID, 'integer');
		assert($worldID > 0);
		
		assert(is_array($existsComb));				
		
		if(count($existsComb) > 0)
		{
			$existsCombAddon = " AND c.id_comb NOT IN (".implode(', ', $existsComb).")";
		}else{
			$existsCombAddon = "";
		}
		
		$sql = "
			SELECT
      			c.id_comb AS id_comb
			FROM
    			ds_comb AS c
    			INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
    			INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
			WHERE
     			c.id_userinstance IS NULL
     			AND
     			r.id_world = $worldID
     			AND
     			r.ringnumber = $ringNumber
	 			AND
     			NOT EXISTS (
         			SELECT id_comb
		 			FROM ds_reservedcomb
		 			WHERE id_comb = c.id_comb
     			) 
     			".$existsCombAddon."
			LIMIT 100
			";
		$res = self::$common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty(self::$common->errormsg));
		return (int)$res[rand(0, (count($res)-1))]['id_comb'];
	}
	
	static public function GetMinCombIDForPlacementOnRing($ringNumber, $worldID, $existsComb)
	{
		settype($ringNumber, 'integer');
		assert($ringNumber > 0);
		
		settype($worldID, 'integer');
		assert($worldID > 0);
		
		assert(is_array($existsComb));				
		
		if(count($existsComb) > 0)
		{
			$existsCombAddon = " AND c.id_comb NOT IN (".implode(', ', $existsComb).")";
		}else{
			$existsCombAddon = "";
		}
		
		$sql = "
			SELECT
      			MIN(c.id_comb) AS id_comb
			FROM
    			ds_comb AS c
    			INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
    			INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
			WHERE
     			c.id_userinstance IS NULL
     			AND
     			r.id_world = $worldID
     			AND
     			r.ringnumber = $ringNumber
	 			AND
     			NOT EXISTS (
         			SELECT id_comb
		 			FROM ds_reservedcomb
		 			WHERE id_comb = c.id_comb
     			) 
     			AND
     			NOT EXISTS (
     				SELECT id_comb
		 			FROM ds_combname
		 			WHERE id_comb = c.id_comb
     			) ".$existsCombAddon;
		$res = self::$common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty(self::$common->errormsg));
		return (int)$res[0]['id_comb'];
	}
	
	static public function GetUserReservedCombsInWorld($userName, $worldId)
	{
		$sql = "
				SELECT
					*
				FROM
					ds_reservedcombs AS rc
					INNER JOIN ds_comb AS c ON (c.id_comb = rc.id_comb)
					INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
					INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				WHERE
					r.id_world = ".$worldId."
					AND
					rc.username = ".$userName."
			";
		
		$res = self::$common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty(self::$common->errormsg));
		return $res;
	}
	
	static public function ClearUserReserve($userName, $worldId)
	{
		self::$common->startTrans();
		$sql = "
			DELETE FROM ds_reservedcombs
			WHERE
				id_comb IN SELECT
				(
					SELECT
						*
					FROM
						ds_reservedcombs AS rc
						INNER JOIN ds_comb AS c ON (c.id_comb = rc.id_comb)
						INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
						INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
					WHERE
						r.id_world = ".$worldId."
						AND
						rc.username = ".$userName."
				)
			
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		self::$common->closeTrans('ROLLBACK');
	}
}
?>