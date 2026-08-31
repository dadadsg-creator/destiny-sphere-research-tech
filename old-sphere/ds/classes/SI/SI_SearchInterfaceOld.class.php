<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_SearchInterfaceOld extends SI_Base 
{
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
      				c.combnumber
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
     				u.username = '$username'";
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
     			) ".$existsCombAddon;
		$res = self::$common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty(self::$common->errormsg));
		return (int)$res[0]['id_comb'];
	}
	
	
	
}		


?>