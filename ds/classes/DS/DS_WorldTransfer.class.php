<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2008/02/26 08:01:42 $';

class DS_WorldTransfer extends DS_Base 
{
	static public function getPresenceInWorld($dbId, $worldId, $uid)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(is_int($uid) && $uid > 0);
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			SELECT
				uic.id_user
			FROM
				ds_worldcollection AS wc
				INNER JOIN ds_userinstancecollection AS uic ON (uic.id_worldcollection = wc.id_worldcollection)
			WHERE
				uic.id_user = ".$uid."
				AND
				wc.id_world = ".$worldId."
				AND
				wc.dbname = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return true;
		}
		
		return false;
	}
	
	static public function getWorldCollection()
	{
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			SELECT
				wc.id_world,
				wc.worldname,
				wc.description,
				wc.profi,
				wc.max_users,
				wc.id_worldtype,
				wc.dbname,
				wc.for_new_players,
				lt.text AS type,
				ld.text AS worldtype_descr
			FROM
				ds_worldcollection AS wc
				INNER JOIN ds_worldtype AS wt ON (wt.id_worldtype = wc.id_worldtype)
				INNER JOIN ds_localizedtextlanguage AS lt ON (lt.id_localizedtext = wt.id_localizedtype)
				INNER JOIN ds_localizedtextlanguage AS ld ON (ld.id_localizedtext = wt.id_localizeddescription)
			WHERE
				lt.id_language = ".constant('DEFAULT_LANGUAGE')."
				AND
				ld.id_language = ".constant('DEFAULT_LANGUAGE')."
				AND
				wc.world_identifier != 'UC'
			ORDER BY
				wc.sort_order DESC, wc.profi, wc.id_worldtype, wc.worldname
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
		
	static public function getLastWorldTransferStatByUserID($user_id)
	{
		$user_id = intval($user_id);
		assert($user_id > 0);
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
				SELECT
				      wts.*
				FROM
				    ds_worldtransfer_stat wts
				WHERE
				     wts.id_user = ".$user_id."
				ORDER BY wts.transfer_date DESC
				LIMIT 1
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0];
	}
	
	static public function getInterWorldTransferInfo($dbIdFrom, $worldIdFrom, $dbIdTo, $worldIdTo)
	{
		assert(is_int($dbIdFrom) && $dbIdFrom >= 0);
		assert(is_int($worldIdFrom) && $worldIdFrom > 0);
		assert(is_int($dbIdTo) && $dbIdTo >= 0);
		assert(is_int($worldIdTo) && $worldIdTo > 0);
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			SELECT
				wtwr.only_for_premium,
				wtwr.free
			FROM
				ds_worldtransfer_worlds_relation AS wtwr
				
				INNER JOIN ds_worldtransfer_worlds_list AS wtwl1 ON (wtwr.id_world_to = wtwl1.id_worldtransfer_world)
				INNER JOIN ds_worldcollection AS wc1 ON (wc1.world_identifier = wtwl1.world_identifier)
				
				INNER JOIN ds_worldtransfer_worlds_list AS wtwl2 ON (wtwr.id_world_from = wtwl2.id_worldtransfer_world)
				INNER JOIN ds_worldcollection AS wc2 ON (wc2.world_identifier = wtwl2.world_identifier)
			WHERE
				wc2.id_world = ".$worldIdFrom."
				AND
				wc2.dbname = '".$GLOBALS['dbWorldAccess'][$dbIdFrom]['server']."'
				AND
				wc1.id_world = $worldIdTo
				AND
				wc1.dbname = '".$GLOBALS['dbWorldAccess'][$dbIdTo]['server']."'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function getUserTransferToWorld($dbId, $worldId, $uid)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(is_int($uid) && $uid > 0);
		
		$common = self::getCommon();
		$common->setDbId(0);		
		
		$sql = "
			SELECT
				id_user,
				status
			FROM
				ds_worldtransfer
			WHERE
				db_to = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."'
				AND
				id_world_to = ".$worldId."
				AND
				id_user = ".$uid."
				AND
				status > 0
				AND
				transfer_type = 0 -- бесплатный переезд
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function isTransferFromWorldDisabled($dbId, $worldId, $uid)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(is_int($uid) && $uid > 0);
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			SELECT
				id_user
			FROM
				ds_worldtransfer
			WHERE
				db_from = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."'
				AND
				id_world_from = ".$worldId."
				AND
				status = 3
				AND
				id_user = ".$uid."
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) == 1)
		{
			return true;
		}
		
		return false;
	}
	
	static public function getTransferInfo($dbId, $worldId, $uid)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(is_int($uid) && $uid > 0);
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "		
			SELECT
				wt.*,
				wc.worldname
			FROM
				ds_worldtransfer AS wt
				INNER JOIN ds_worldcollection AS wc ON (wt.id_world_to = wc.id_world AND wt.db_to = wc.dbname)
			WHERE
				id_user = ".$uid."
				AND
				db_from = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."'
				AND
				id_world_from = ".$worldId."
				AND
				transfer_type = 0 -- бесплатный переезд
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function getTransferQuestInfo($dbId, $worldId, $uid)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(is_int($uid) && $uid > 0);
		
		$_SESSION['dbId'] = 0;
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "		
			SELECT
				wt.*,
				wc.worldname
			FROM
				ds_worldtransferquest AS wt
				INNER JOIN ds_worldcollection AS wc ON (wt.id_world_to = wc.id_world AND wt.db_to = wc.dbname)
			WHERE
				id_user = ".$uid."
				AND
				db_from = '".$GLOBALS['dbWorldAccess'][$dbId]['server']."'
				AND
				id_world_from = ".$worldId."
				AND
				transfer_type = 0 -- бесплатный переезд
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");

		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function checkFreeTransferTime($uid, $period)
	{
		assert(is_int($uid) && $uid > 0);
		assert(is_int($period) && $period > 0);
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_worldtransfer AS wt
			WHERE
				id_user = ".$uid."
				AND
				transfer_type = 0
				AND
				transfer_date > NOW() - '".$period." HOUR'::interval
				AND
				status = 2
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0]['count'];
	}
	
	
	static public function getCountUserCombs($dbId, $worldId, $uid)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(is_int($uid) && $uid > 0);
		
		$common = self::getCommon();
		$common->setDbId($dbId);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_comb AS c
				INNER JOIN ds_userinstance AS ui USING (id_userinstance)
				INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
			WHERE
				u.id_user = $uid
				AND
				ui.id_world = $worldId
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0]['count'];
	}
	
	static public function getUserColonies($uid, $worldId)
	{
		assert(is_int($uid) && $uid > 0);
		assert(is_int($worldId) && $worldId > 0);
		
		$common = self::getCommon();
		
		$sql = "
				SELECT
					ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber as coords,
					co.id_comb,
					(
					select 
						combname
					from 
						ds_combname
					where 
						id_comb = co.id_comb
					) AS combname,
					co.is_capture
				FROM 
					ds_comb co,
					ds_combcomplex cc,
					ds_ring ri,
					ds_userinstance ui
				WHERE 
					ri.id_ring = cc.id_ring
					 AND 
					cc.id_combcomplex = co.id_combcomplex
					 AND 
					co.id_userinstance = ui.id_userinstance
                     AND
                    ui.id_user = $uid
                     AND
                    ui.id_world = $worldId
					 AND 
					ri.ringnumber = 4
				order by ri.ringnumber, cc.complexnumber, co.combnumber";
		$res = $common->getResultArray ("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function IsUserHasColony($uid, $worldId, $combId)
	{
		assert(is_int($uid) && $uid > 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(is_int($combId) && $combId > 0);
		
		$common = self::getCommon();
		
		$sql = "
				SELECT
					co.id_comb
				FROM 
					ds_comb co,
					ds_combcomplex cc,
					ds_ring ri,
					ds_userinstance ui
				WHERE 
					ri.id_ring = cc.id_ring
					 AND 
					cc.id_combcomplex = co.id_combcomplex
					 AND 
					co.id_userinstance = ui.id_userinstance
                     AND
                    ui.id_user = $uid
                     AND
                    ui.id_world = $worldId
					 AND 
					ri.ringnumber = 4
					 AND 
					co.id_comb = $combId
				order by ri.ringnumber, cc.complexnumber, co.combnumber";
		$res = $common->getResultArray ("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if (count($res) > 0) {
			return true;
		} else {
			return false;
		}
	}
	
	static public function isUserAllowedWorldTransfer($dbId, $UID)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($UID) && $UID > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				wau.id_userinstance
			FROM
				ds_worldtransfer_allowed_user AS wau
				INNER JOIN ds_userinstance AS ui USING (id_userinstance)
			WHERE
				ui.id_user = ".$UID."
				AND
				NOT wau.transfer_done
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return true;
		}
		
		return false;
	}
	
	static public function getUserBaseRing($dbId, $UID)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($UID) && $UID > 0);
		
		$common = self::getCommon();
		$common->setDbId($dbId);
		
		$sql = "
			SELECT
				rbr.ringnumber
			FROM
				ds_userinstance AS ui
				INNER JOIN ds_racehasbasering AS rbr USING (id_race)
			WHERE
				ui.id_user = $UID
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) == 0)
		{
			return false;
		}
		
		return (int)$res[0]['ringnumber'];
	}
	
	static public function checkCombReservation($dbId, $combID)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($combID) && $combID > 0);
		
		$common = self::getCommon();
		$common->setDbId($dbId);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_reservedcomb
			WHERE
				id_comb = $combID
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if($res[0]['count'] == 0)
		{
			return true;
		}
		
		return false;
	}
	
	static public function getTransferFrequency($dbIdFrom, $worldIdFrom, $dbIdTo, $worldIdTo)
	{
		assert(is_int($dbIdFrom) && $dbIdFrom >= 0);
		assert(is_int($dbIdTo) && $dbIdTo >= 0);
		assert(is_int($worldIdFrom) && $worldIdFrom > 0);
		assert(is_int($worldIdTo) && $worldIdTo > 0);
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			SELECT
				wtwr.frequency
			FROM
				ds_worldtransfer_worlds_relation AS wtwr
				
				INNER JOIN ds_worldtransfer_worlds_list AS wtwl1 ON (wtwr.id_world_to = wtwl1.id_worldtransfer_world)
				INNER JOIN ds_worldcollection AS wc1 ON (wc1.world_identifier = wtwl1.world_identifier)
				
				INNER JOIN ds_worldtransfer_worlds_list AS wtwl2 ON (wtwr.id_world_from = wtwl2.id_worldtransfer_world)
				INNER JOIN ds_worldcollection AS wc2 ON (wc2.world_identifier = wtwl2.world_identifier)
			WHERE
				wc2.id_world = ".$worldIdFrom."
				AND
				wc2.dbname = '".$GLOBALS['dbWorldAccess'][$dbIdFrom]['server']."'
				AND
				wc1.id_world = $worldIdTo
				AND
				wc1.dbname = '".$GLOBALS['dbWorldAccess'][$dbIdTo]['server']."'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return (int)$res[0]['frequency'];
	}
	
	static public function checkTransferFrequency($uid, $period, $dbIdFrom, $worldIdFrom, $dbIdTo, $worldIdTo)
	{
		assert(is_int($uid) && $uid > 0);
		assert(is_int($period) && $period > 0);
		assert(is_int($dbIdFrom) && $dbIdFrom >= 0);
		assert(is_int($dbIdTo) && $dbIdTo >= 0);
		assert(is_int($worldIdFrom) && $worldIdFrom > 0);
		assert(is_int($worldIdTo) && $worldIdTo > 0);
		
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			SELECT
				*
			FROM
				ds_worldtransfer AS wt
			WHERE
				id_user = ".$uid."
				AND
				transfer_type = 0
				AND
				transfer_date > NOW() - '".$period." DAY'::interval
				AND
				status = 2
				AND
				wt.db_from = '".$GLOBALS['dbWorldAccess'][$dbIdFrom]['server']."'
				AND
				wt.id_world_from = '".$worldIdFrom."'
				AND
				wt.db_to = '".$GLOBALS['dbWorldAccess'][$dbIdTo]['server']."'
				AND
				wt.id_world_to = '".$worldIdTo."'
			";		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if (count($res) > 0) {
			return false;
		}
		
		$sql = "
			SELECT
				*
			FROM
				ds_worldtransfer AS wt
			WHERE
				id_user = ".$uid."
				AND
				transfer_type = 0
				AND
				transfer_date > NOW() - '".$period." DAY'::interval
				AND
				status = 2
				AND
				wt.db_from = '".$GLOBALS['dbWorldAccess'][$dbIdTo]['server']."'
				AND
				wt.id_world_from = '".$worldIdTo."'
				AND
				wt.db_to = '".$GLOBALS['dbWorldAccess'][$dbIdFrom]['server']."'
				AND
				wt.id_world_to = '".$worldIdFrom."'
			";		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if (count($res) > 0) {
			return false;
		}
		
		return true;
	}
}

?>