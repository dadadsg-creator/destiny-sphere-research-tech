<?php $CVS_Version = '$Revision$ ; $Date$';
/*

формат записи

"имя игрока" "ид базы из" "мир из" "ид базы в" "мир в"

*/

class SU_WorldUnionUserinstanceList
{
	static private $common;
	
	static private $dbId_from = array();
	
	static private $world_from = array();
	
	static private $world_from_id = array();
	
	static private $dbId_to;
	
	static private $world_to;
	
	static private $file;
	
	static private $doubleInstances = array();
	
	static public function Get($outFileName)
	{
		self::_getDbIDs();
		
		assert(!empty($outFileName));
		
		self::$file = fopen($outFileName, "w+");
		
		foreach(self::$dbId_from as $k => $dbId)
		{
			self::$common->setDbId($dbId);
			self::_processWorld($k);
		}
		
		fclose(self::$file);
	}
	
	static private function _getDbIDs()
	{
		self::$dbId_to = (int)$GLOBALS['WORLDS_UNION_DBID'];
		self::$common = DS_Common::CreateForCurentDB(self::$dbId_to);
		self::$world_to = self::_getWorldIdentifier((int)$GLOBALS['WORLDS_UNION_ID_WORLD']);
		
		foreach($GLOBALS['WORLDS_UNION'] as $k => $db)
		{
			list($dbName, $worldId) = explode('_', $db);
			
			$dbId = (int)self::$common->getDbIdByName($dbName);
			
			self::$common->setDbId($dbId);
			
			$world = self::_getWorldIdentifier((int)$worldId);
			
			if($world === false)
			{
				continue;
			}
			
			self::$world_from[$k] = $world;
			self::$world_from_id[$k] = (int)$worldId;
			self::$dbId_from[$k] = $dbId;
		}
	}
	
	static private function _processWorld($num)
	{
		$instances = self::_getUserInstances(self::$world_from_id[$num]);
		
		self::$common->setDbId(self::$dbId_from[$num]);
		
		self::_out("WORLD ".self::$world_from[$num], true);		
		self::_out("selected ".count($instances)." users (excluding ".count(self::$doubleInstances)." users)");
		self::_out("**************************");
		
		foreach($instances as $k => $instance)
		{	
			$userInstances = array();
			
			$userInstances[$num] = $instance;
			
			foreach(self::$dbId_from as $l => $dbId)
			{
				if($l == $num)
				{
					continue;
				}
				$userInst = self::_getUserInstance($dbId, self::$world_from_id[$l], $instance['username']);
				if(count($userInst) == 1)
				$userInstances[$l] = $userInst[0];
				self::$common->setDbId(self::$dbId_from[$num]);
			}
			
			self::_out("User ".$instance['username']." : ".count($userInstances)." instances");
					
			// смотрим не выбран ли инстанс игроком
			$userInfo = self::_getWorldUnionInfo((int)$instance['id_user']);
			
			// если есть инстансы в разных мирах
			if(count($userInstances) > 1)
			{
				// если выбран, то берем его
				if(count($userInfo) == 1)
				{
					foreach($userInstances as $m => $inst)
					{
						if($inst['id_userinstance'] == $userInfo[0]['id_userinstance'] && 
						   $userInfo[0]['server_name'] == $GLOBALS['dbWorldAccess'][self::$dbId_from[$m]]['server'] &&
						   $userInfo[0]['id_world'] == self::$world_from_id[$m])
						{
							$instanceToMove = $userInstances[$m];
							$moveFrom = $m;
							
							self::_out("User made choise : ".self::$world_from[$m], false);
							
							break;
						}
					}
				} else {
				// иначе берем инстанс с бОльшим рейтингом
					$moveFrom = 0;
					$ranking = 0;
					foreach($userInstances as $m => $inst)
					{
						if($inst['thefinal'] > $ranking)
						{
							$ranking = $inst['thefinal'];
							$instanceToMove = $userInstances[$m];
							$moveFrom = $m;
						}
					}
					
					self::_out("Max ranking : ".self::$world_from[$moveFrom], false);
					self::_insertWorldUnionUserInfo($instanceToMove, $moveFrom);
				}
			} else {
				$instanceToMove = $userInstances[$num];
				$moveFrom = $num;
				if(count($userInfo) == 0)
				{
					self::_insertWorldUnionUserInfo($instanceToMove, $moveFrom);
				}
			}	
			
			self::$doubleInstances[] = $instanceToMove['id_user'];	
			
			self::_addInstanceToFile($instanceToMove['username'], $moveFrom);
		}
	}
	

	static private function _addInstanceToFile($username, $numFrom)
	{
		$str = '"'.$username.'";"'.self::$dbId_from[$numFrom].'";"'.self::$world_from[$numFrom].'";"'.self::$dbId_to.'";"'.self::$world_to.'";"RESERVE"'."\n";
		fwrite(self::$file, $str);
	}
	
	static private function _out($str, $withDate = true)
	{
		$msg = $str."\n";
		
		if($withDate)
		{
			$msg = date("H:m:i")."  -  ".$msg;
		}
		
		echo $msg;
		flush();
	}
	
	static private function _getWorldUnionInfo($userId)
	{
		assert(is_int($userId) && $userId > 0);
		
		self::$common->setDbId(self::$dbId_to);
		
		$sql = "
			SELECT
				*
			FROM
				ds_worldunionuserinfo AS wuui
			WHERE
				id_user = $userId	
			";
		
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	
	static private function _getWorldIdentifier($wordlId)
	{
		assert(is_int($wordlId) && $wordlId > 0);
		
		$sql = "
				SELECT
					world_identifier
				FROM
					ds_world
				WHERE
					id_world = $wordlId
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		if(!count($res))
		{
			return false;
		}
		
		return $res[0]['world_identifier'];
	}
	
	static private function _getUserInstances($worldId)
	{
		assert(is_int($worldId) && $worldId > 0);
		
		if(count(self::$doubleInstances))
		{
			$doubleInst = "AND u.id_user NOT IN (".implode(', ', self::$doubleInstances).")";
		} else {
			$doubleInst = "";
		}
		
		$sql = "
			SELECT
				ui.id_userinstance,
				u.id_user,
				u.username,
				fr.thefinal
			FROM
				ds_userinstance AS ui
				INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
				INNER JOIN ds_finalranking AS fr ON (fr.id_userinstance = ui.id_userinstance)
			WHERE
				ui.id_world = ".$worldId."
				".$doubleInst."
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getUserInstance($dbId, $worldId, $userName)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(!empty($userName));
	
		self::$common->setDbId($dbId);
		
		$sql = "
			SELECT
				ui.id_userinstance,
				u.id_user,
				u.username,
				fr.thefinal
			FROM
				ds_userinstance AS ui
				INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
				INNER JOIN ds_finalranking AS fr ON (fr.id_userinstance = ui.id_userinstance)
			WHERE
				ui.id_world = ".$worldId."
				AND
				u.username = '".$userName."'
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static public function _checkUser($id_user, $numFrom)
	{
		self::$common->setDbId(self::$dbId_to);
		$sql = "SELECT id_user FROM ds_user WHERE id_user = $id_user";
		$res = self::$common->getResultArray("", $sql, "", "", "");	
		assert(empty(self::$common->errormsg));
	
		if(count($res) > 0)
		{
			return true;
		}
		
		self::$common->setDbId(self::$dbId_from[$numFrom]);
		$sql = "SELECT * FROM ds_user WHERE id_user = $id_user";
		$res = self::$common->getResultArray("", $sql, "", "", "");	
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);
		
		$user = $res[0];
		
		self::$common->setDbId(self::$dbId_to);
		$sql = "
			INSERT INTO ds_user
				(
					id_user, 
					id_language, 
					username, 
					email, 
					lastrefresh, 
					registrationdate, 
					vacationmode, 
					admin,
					accountactive,
					selfdescription,
					password,
					time_delta,
					activate_mail
				)
			VALUES
				(
					 ".$user['id_user'].",
					 ".$user['id_language'].",
					'".$user['username']."',
					'".$user['email']."',
					'".$user['lastrefresh']."',
					'".$user['registrationdate']."',
					'".$user['vacationmode']."',
					'".$user['admin']."',
					'".$user['accountactive']."',
					'".$user['selfdescription']."',
					'".$user['password']."',
					 ".$user['time_delta'].",
					 ".$user['activate_mail']."
				)
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	static private function _insertWorldUnionUserInfo($instance, $numFrom)
	{
		assert(is_int($numFrom));
		assert(is_array($instance));
		
		self::_checkUser($instance['id_user'], $numFrom);
		
		$sql = "
			INSERT INTO ds_worldunionuserinfo
				(id_user, id_userinstance, id_world, server_name, used_free_transfer)
			VALUES
				(".$instance['id_user'].", ".$instance['id_userinstance'].", ".self::$world_from_id[$numFrom].", '".$GLOBALS['dbWorldAccess'][self::$dbId_from[$numFrom]]['server']."', false)
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
}
?>
