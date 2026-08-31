<?php

/**
 * Класс заселения требуемого кол-ва ботов на кольцо
 *
 */
class SU_PopulateNPC
{
	/**
	 * Список шаблонов сот
	 *
	 * @var array
	 */
	static private $templates = array(
			// формат: раса => уровни
			1 => array(
					1 => '1lvlvoraner_race1',
					2 => '2lvlvoraner_race1',
					3 => '3lvlvoraner_race1',
				),
			2 => array(
					1 => 'LiensuMan1_race2',
					2 => 'LiensuMan2_race2',
					3 => 'LiensuMan3_race2',
				),
			3 => array(
					1 => 'psolao1lvl_race3',
					2 => 'psolao2lvl_race3',
					3 => 'psolao3lvl_race3',
				),
		);
		
	/**
	 * Название сот
	 * уровень => название
	 *
	 * @var array
	 */
	static private $combNames = array(
			1 => 'Sleeping_threat',
			2 => 'Sleeping_death',
			3 => ''
		);
		
	static private $NPC_password = '(PgS_nPc_#1)';
	
	static private $dbId;
	
	static private $worldID;
	
	static private $targetRace;
	
	static private $tplRace;
	
	static private $tplLevel;
	
	static private $count;
	
	static public function CreateUsers($level, $count, $raceID)
	{
		assert(is_int($raceID) && $raceID > 0 && $raceID <= 4);
		assert(is_int($level) && $level > 0 && $level < 4);
		assert(is_int($count) && $count > 0);
		
		$common = DS_Common::CreateForCurentDB(0);
		
		for($i=0;$i<$count;$i++)
		{
			$login = GL_GenerateNPCCombName::generate($raceID);
			$counter = 0;
			while(!VD_UserName::Validate($login, true, $common) && $counter < 20)
			{
				$login = GL_GenerateNPCCombName::generate($raceID);
				$counter++;
			}
			
			echo "Login: ".$login."\n";
			flush();
			
			self::_createNPCUser($login, $level, $raceID);
		}
	}
	
	static public function Populate($dbId, $worldID, $targetRace, $tplRace, $tplLevel, $count)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($targetRace) && $targetRace > 0 && $targetRace < 4);
		assert(is_int($tplRace) && $tplRace > 0 && $tplRace <= 4);
		assert(is_int($tplLevel) && $tplLevel > 0 && $tplLevel < 4);
		assert(is_int($count) && $count > 0);
		
		$_SESSION['dbId'] = $dbId;
		$_SESSION['world'] = $worldID;
		
		self::$dbId = $dbId;
		
		self::$worldID = $worldID;
		
		self::$targetRace = $targetRace;
		
		self::$tplLevel = $tplLevel;
		
		self::$tplRace = $tplRace;
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$combs = GL_GetCombForNPC::Get(self::$worldID, $targetRace, $count);
		if(count($combs) != $count)
		{
			echo "WARNING! not enought free combs!\n";
			echo count($combs)." combs will be created\n";
			$count = count($combs);
		}
		
		$uids = self::_getNPCUIDs($count, $tplLevel, self::$targetRace);
		if(count($uids) == 0)
		{
			echo "ERROR: no free npc user accounts\n";
			return;
		} elseif(count($uids) < $count) {
			echo "WARNING: not enought free npc user accounts\n";
			echo count($uids)." combs will be created\n";
			$count = count($uids);
		}
		
print_r($combs);
print_r($uids);
		
		for($i=0;$i<$count;$i++)
		{
			$c = DE_Comb::CreateFromID((int)$combs[$i]);
			echo "Comb: ".$c->getFormatedAddress()."\n";
			flush();
			
			$common = DS_Common::CreateForCurentDB();
			echo "DB: ".$common->dbId."\n";
			
			$user = DS_User::GetByID($uids[$i]);
			if(!$user)
			{
				echo "WARNING: no data for user ".$uids[$i]."\n";
				continue;
			}
			
			assert(key_exists('username', $user));
			$login = $user['username'];
			
			self::_addTradeBan($uids[$i]);
			
			$uiid = self::_applyTemplate($login, $uids[$i], (int)$combs[$i]);
			
			self::_addToCollection($uids[$i], $uiid);
		}
	}
	
	static private function _getNPCUIDs($count, $level, $raceID)
	{
		assert(is_int($raceID) && $raceID > 0 && $raceID <= 4);
		assert(is_int($level) && $level > 0 && $level < 4);
		assert(is_int($count) && $count > 0);
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				id_user
			FROM
				ds_npc_user as nu
			WHERE
				npc_level = $level
				AND
				id_race = $raceID
				AND
				NOT EXISTS (
					SELECT
						*
					FROM
						ds_userinstancecollection
					WHERE
						id_user = nu.id_user
						AND
						dbname = '".$GLOBALS['dbWorldAccess'][self::$dbId]['server']."'
						AND
						id_world = ".self::$worldID."
				)
			LIMIT $count
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		$uids = array();
		foreach ($res as $k => $u) 
		{
			$uids[$k] = (int)$u['id_user'];
		}
		
		return $uids;
	}
	
	static private function _createNPCUser($login, $level, $raceID)
	{	
		assert(is_int($raceID) && $raceID > 0 && $raceID <= 4);
		assert(is_int($level) && $level > 0 && $level < 4);
		assert(VD_UserName::Validate($login));
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$common->startTrans();
		
		$uid = (int)DS_Base::getNextSeqVal($common, 'ds_user_id_user_seq');
		assert($uid > 0);
		
		$sql = "
			INSERT INTO ds_user
				(id_user, id_language, username, password, accountactive, activate_mail, email, registrationdate)
			VALUES
				($uid, '".constant('DEFAULT_LANGUAGE')."', '$login', md5('".self::$NPC_password."'), 't', 1, '".$login."@npc.com', now())
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		$sql = "
			INSERT INTO ds_npc_user
				(id_user, npc_level, id_race)
			VALUES
				($uid, $level, $raceID)
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		$common->closeTrans('COMMIT');
		
		return $uid;
	}
	
	static private function _addTradeBan($uid)
	{
		assert(is_int($uid) && $uid > 0);
		$common = DS_Common::CreateForCurentDB();
		$sql = "
			INSERT INTO ds_trade_user_ban
				(id_user, id_ban_reason, id_world)
			VALUES
				($uid, 1, ".self::$worldID.")
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
		
	static private function _applyTemplate($login, $uid, $combId)
	{
		if(key_exists(self::$tplLevel, self::$combNames) && !empty(self::$combNames[self::$tplLevel]))
		{
			$combName = self::$combNames[self::$tplLevel];
		} else {
			$combName = $login;
		}
		
		// чтобы ничего не выводилось перехватываем все
		ob_start();
		$newUIID = SI_ControlApplyTemplate::Apply($login, self::$templates[self::$tplRace][self::$tplLevel], self::$dbId, $uid, $combName, self::$worldID, self::$targetRace, $combId);
		ob_clean();
		
		return $newUIID;
	}
	
	static private function _addToCollection($uid, $uiid)
	{
		ob_start();
		// добавляем инстанс в коллекцию
		$collectWorlds = new SU_CollectWorlds();
		$userData['id_user'] = $uid;
		$userData['id_userinstance'] = $uiid;
		$userData['id_world'] = self::$worldID;
		$userData['admin'] = 0;		
		
		$serverName = $GLOBALS['dbWorldAccess'][self::$dbId]['server'];
		
		$collectWorlds->collectUserInstance($userData, $serverName);
		// чистим буфер вывода
		ob_clean();
	}
}


?>