<?php

class TMP_CheckDoubleCombs
{
	static private $freezDouble = false;
	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $countDouble = 0;
	
	static private $doubleCombs = array();
	
	static private $logFile = 'DoubleCombs.log';
	
	static public function Check($freez = false)
	{
		assert(is_bool($freez));
		self::$freezDouble = $freez;
		
		self::$common = DS_Common::CreateForCurentDB(0);
		
		$data = self::_getTransfers();
//print_r($data);
		self::_log("COUNT TRANSFERS: ".count($data));
		
		foreach ($data as $k => $v)
		{
			self::_checkDstAndSrcComb($v);
		}
		
		self::_log();
		
		self::_log(print_r(self::$doubleCombs, true));
		
		self::_log();
		
		self::_log("COUNT USERS WITH OLD AND NEW COMB: ".self::$countDouble);
	}
	
	static private function _log($msg = '')
	{
		if(!empty($msg))
		{
			$msg = /*date('Y-m-d H:i:s').'  -  '.*/$msg;
		}
		
		$msg .= "\n";
		
		SU_DebugTools::WriteToLog($msg, self::$logFile);
		
		echo $msg;
	}
	
	static private function _getTransfers()
	{
		$sql = "
			SELECT
				*
			FROM
				ds_worldtransfer_stat
			--WHERE
			--	NOT transfer_date IS NULL
			ORDER BY transfer_date DESC
			LIMIT 1000
			-- OFFSET 1000
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}

	static private function _checkDstAndSrcComb(array $data)
	{
		assert(key_exists('db_from', $data));
		assert(key_exists('id_world_from', $data));
		assert(key_exists('db_to', $data));
		assert(key_exists('id_world_to', $data));
		assert(key_exists('id_user', $data));
		
		$userID = (int)$data['id_user'];
		assert(is_int($userID) && $userID > 0);
		
		$haveOldComb = self::_userHaveCombInWorld($userID, (int)$data['id_world_from'], $data['db_from']);
		
		$haveNewComb = self::_userHaveCombInWorld($userID, (int)$data['id_world_to'], $data['db_to']);
		
		if($haveOldComb && $haveNewComb)
		{
			self::$countDouble++;
			self::$doubleCombs[] = DE_User::CreateFromID($userID)->getName();
		}
	}
	
	static private function _userHaveCombInWorld($userID, $worldID, $dbname)
	{
		assert(is_int($userID) && $userID > 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(!empty($dbname));
		$dbID = self::$common->getDBIDByName($dbname);
		
		self::$common->setDbId($dbID);
		$sql = "
			SELECT
				*
			FROM
				ds_userinstance
			WHERE
				id_user = $userID
				AND
				id_world = $worldID
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		if(count($res) > 0)
		{
			return true;
		}
		
		return false;
	}
	
	static public function Check2()
	{
		self::$common = DS_Common::CreateForCurentDB(0);
		
		$userList = self::_getUsersWith2Combs();
		$c = 0;
		$c2 = 0;
		
		self::_log('TOTAL USERS: '.count($userList));
		
		foreach ($userList as $k => $user)
		{
			$haveCombWithSameName = false;
			
			$userID = (int)$user['id_user'];
			$instances = self::_getUserInstances($userID);
			
			$dbName = array();
			$combName = array();
			$creationDate = array();
			$vocation = array();
			
			foreach ($instances as $k => $instance)
			{
				$dbName[] = $instance['dbname'];
				$DBID = (int)self::$common->getDBIDByName($instance['dbname']);
				$uiData = self::_getUserCombName((int)$instance['id_userinstance'], $DBID);
				if(count($uiData) > 0)
				{
					$combName[] = $uiData['combname'];
					$creationDate[] = $uiData['creation_date'];
					$vocation[] = $uiData['vocation'];
				}
			}
			
			$prevName = '';
			foreach ($combName as $k => $cn)
			{
				if($cn == $prevName)
				{
					$c2++;
					$haveCombWithSameName = true;
					break;
				}
				
				$prevName = $cn;
			}
			
			$transfer = self::_getUserTransfer($userID, $dbName);
			
			if($haveCombWithSameName)
			{
				self::_log($user['username'].' HAVE COMBS IN WORLDS: '.implode(',', $dbName));
			
				self::_log('COMB NAMES: '.implode(',', $combName));
				self::_log('CREATION DATES: '.implode(',', $creationDate));
				self::_log('VOCATION MODE: '.implode(',', $vocation));
			
				if(count($transfer) > 0)
				{
					foreach ($transfer as $k => $t)
					{
						self::_log('TRANSFERS: '.$t['db_from'].' => '.$t['db_to']);
					}
					$c++;
				}
				self::_log();
			}
		}
		
		self::_log('TOTAL USERS WITH TRANSFER: '.$c);
		self::_log('TOTAL USERS WITH SAME COMBNAME : '.$c2);
	}
	
	static private function _getUserCombName($UIID, $DBID)
	{
		assert(is_int($UIID) && $UIID > 0);
		assert(is_int($DBID) && $DBID >= 0);
		
		self::$common->setDbId($DBID);
		
		$sql = "
			SELECT
				cn.combname,
				ui.creation_date,
				COALESCE(vm.password, ' - ') as vocation
			FROM
				ds_combname AS cn
				INNER JOIN ds_comb AS c USING (id_comb)
				INNER JOIN ds_combcomplex AS cc USING (id_combcomplex)
				INNER JOIN ds_ring AS r USING (id_ring)
				INNER JOIN ds_userinstance AS ui USING (id_userinstance)
				LEFT JOIN ds_vocationmode AS vm USING (id_userinstance)
			WHERE
				c.id_userinstance = $UIID
				AND
				r.ringnumber != 4
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));		
		
		self::$common->setDbId(0);
		
		if(count($res) == 0)
		{
			return array();
		}
		
		return $res[0];
	}
	
	static private function _getUserTransfer($userID, array $dbList)
	{
		assert(is_int($userID) && $userID > 0);
		
		$sql = "
			SELECT
				*
			FROM
				ds_worldtransfer_stat
			WHERE
				id_user = $userID
				AND
				(
					db_from IN ('".implode("','", $dbList)."')
					OR
					db_to IN ('".implode("','", $dbList)."')
				)
			ORDER BY transfer_date DESC
			LIMIT 1
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getUserInstances($userID)
	{
		assert(is_int($userID) && $userID > 0);
		
		$sql = "
			SELECT
				*
			FROM
				ds_userinstancecollection
			WHERE
				id_user = $userID
				AND
				dbname != 'premium'
				AND
				dbname != 'hell'
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getUsersWith2Combs()
	{
		$sql = "
			select
    			*
			from
    			ds_user as u
			where
    			u.id_user
    			in (
        			select
            			uic.id_user
        			from
            			ds_userinstancecollection AS uic
        			where
            			uic.dbname != 'premium'
            			AND
						uic.dbname != 'hell'
        			group by id_user
        			having count(*) > 1
    			)
    			and
    			not u.admin
    			and
    			not exists (
        			select
            			*
        			from
            			ds_npc_user
        			where
            			id_user = u.id_user
    			)
    			and
    			not exists (
        			select
            			*
        			from
            			ds_usersallowedcollection
        			where
            			id_user = u.id_user
    			)
    			and
    			not exists (
        			select
        	    		*
        			from
            			ds_multiworldaccess
        			where
            			id_user = u.id_user
    			)";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
}


?>