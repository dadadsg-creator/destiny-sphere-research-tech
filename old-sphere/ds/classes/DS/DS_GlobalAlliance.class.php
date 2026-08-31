<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_GlobalAlliance extends DS_Base 
{
	static public function GetNextAllianceID()
	{
		$common = self::getCommon();
		
		return self::getNextSeqVal($common, 'ds_global_alliance_id_global_alliance_seq');
	}
	
	static public function GetUserGlobalAlliance(DE_User $user)
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				u.id_global_alliance
			FROM
				ds_global_alliance_users u
			WHERE
				u.id_user = ".$user->getID()."
			";
	
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if (count($res) > 0)
		{
			return (int)$res[0]['id_global_alliance'];
		}
		
		return;
	}
	
	static public function IsUserLeaderAlliance(DE_User $user)
	{
		$common = DS_Common::CreateForCurentDB(0);
		$sql = "
			SELECT
				id_global_alliance
			FROM
				ds_global_alliance
			WHERE
				id_leader = ".$user->getID()."
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function AddUserToGlobalAlliance($userId, $globalAllianceId)
	{
		assert($userId >= 0);
		assert($globalAllianceId >= 0);
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "INSERT
			INTO
				ds_global_alliance_users
					(id_global_alliance, id_user)
			VALUES
					($globalAllianceId, $userId)
				";
		
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		return;
	}
	
	static public function DeleteUserFromGlobalAlliance($userId, $globalAllianceId)
	{
		assert($userId >= 0);
		assert($globalAllianceId >= 0);
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "DELETE
			FROM
				ds_global_alliance_users
			WHERE
				 id_global_alliance = $globalAllianceId
				AND
				 id_user = $userId
				";
		
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		return;
	}
	
	//static public function CanJoinAlliance($allianceId, DE_User $user)
	static public function IsUserInBlackList($allianceId, $userId)
	{
		assert($userId >= 0);
		assert($allianceId >= 0);
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				   *
				FROM ds_global_alliance_user_black_list
				WHERE
					id_global_alliance = $allianceId
					AND
					id_user =  ".$userId."
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return true;
		}
		
		return false;
	}
	
	static public function GetDataById($id)
	{
		assert(is_int($id) && $id > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				ga.*,
                (
				SELECT 
					 u.username FROM ds_user as u
					WHERE 
					 u.id_user = ga.id_leader
				) as leader_name
			FROM
				ds_global_alliance as ga
			WHERE
				ga.id_global_alliance = $id
			";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		assert(count($res) <= 1);	
		
		if(count($res) > 0)
		{
			return $res[0];
		}
		
		return $res;
	}
	
	static public function GetByName($allianceName)
	{
		assert(!empty($allianceName));
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				*
			FROM
				ds_global_alliance
			WHERE
				alliance_name = '$allianceName'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return $res[0];
		}
		
		return $res;
	}
	
	/*static public function GetByTag($allianceTag)
	{
		assert(!empty($allianceTag));
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				*
			FROM
				ds_global_alliance
			WHERE
				short_name = '$allianceTag'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return $res[0];
		}
		
		return $res;
	}*/
	
	static public function GetUserAlliance(DE_User $user, DS_Common $common)
	{
		$sql = "
			SELECT
				al.id_alliance,
				al.alliancename,
				al.id_world,
				al.alliancetag,
				COALESCE(uas.statusname, 'NO') as status,
				w.worldname,
				al.id_global_alliance
			FROM
				ds_alliance AS al
				INNER JOIN ds_userhasalliance AS uha ON (al.id_alliance = uha.id_alliance)
				LEFT JOIN ds_userhasalliancestatus AS uhas ON (al.id_alliance = uhas.id_alliance)
				LEFT JOIN ds_useralliancestatus AS uas ON (uas.id_status = uhas.id_status)
				INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = uha.id_userinstance)
				INNER JOIN ds_world AS w ON (w.id_world = al.id_world)
			WHERE
				ui.id_user = ".$user->getID()."
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	/*static public function InsertAlliance(DE_GlobalAlliance $alliance)
	{
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			INSERT INTO ds_global_alliance
				(id_global_alliance, alliance_name, short_name, id_leader, join_password, join_payment, join_confirmation, creation_date)
			VALUES
				(
					'".$alliance->getID()."',
					'".$alliance->getName()."',
					'".$alliance->getShortName()."',
					'".$alliance->getLeader()->getID()."',
					'".$alliance->getPassword()."',
					'".$alliance->getJoinPayment()."',
					'".$alliance->getConfirmation('pg')."',
					'".$alliance->getCreationDate()->getPgFormat()."'
				)
			";
		
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}*/
	
	static public function GetUserLocalAllianceByUserInstance($dbId, $userInstance)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert($userInstance >= 0);
		
		$common = self::getCommon();
		$common->setDbId($dbId);
		
		$sql = "SELECT
					id_alliance
				FROM
					ds_userhasalliance
				WHERE
					id_userinstance = $userInstance
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0]['id_alliance'];
	}
	
	static public function InsertLocalAlliance(DE_GlobalAlliance $alliance, $dbId, $worldId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		$common = self::getCommon();
		$common->setDbId($dbId);
		
		$allianceId = self::getNextSeqVal($common, 'ds_global_alliance_id_global_alliance_seq');
		
		$sql = "
			INSERT INTO ds_alliance
				(id_alliance, alliancename, alliancetag, joinpassword, creationdate, id_world, members_amount, id_global_alliance)
			VALUES
				(
					$allianceId,
					'".$alliance->getName()."',
					'[".$alliance->getShortName()."]',
					'".$alliance->getPassword()."',
					'".$alliance->getCreationDate()->getPgFormat()."',
					$worldId,
					0,
					'".$alliance->getID()."'
				)
			";
		
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	
		return $allianceId;
	}
	
	/*static public function UpdateAlliance(DE_GlobalAlliance $alliance)
	{
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			UPDATE 
				ds_global_alliance
			SET
				alliance_name = '".$alliance->getName()."',
				short_name = '".$alliance->getShortName()."',
				id_leader = ".$alliance->getLeader()->getID().",
				join_password = '".$alliance->getPassword()."',
				join_payment = ".$alliance->getJoinPayment().",
				join_confirmation = '".$alliance->getConfirmation('pg')."'
			WHERE
				id_global_alliance = ".$alliance->getID()."
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}*/
	
	static public function GetGlobalAlliances(DE_User $user)
	{
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
			SELECT
			    ga.*,
			    COALESCE(gubl.id_global_alliance, 1) as cant_join
			FROM ds_global_alliance as ga
			LEFT JOIN ds_global_alliance_user_black_list as gubl ON (gubl.id_user = ".$user->getID().")
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}
	
	static public function GetUserInstaceByUserIdAndWorldId($userId, $dbId, $worldId)
	{
		assert($userId >= 0);
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);

		$common = self::getCommon();
		$common->setDbId($dbId);
		
		$sql = "SELECT
				id_userinstance
			FROM
				ds_userinstance			
			WHERE
				id_user = $userId
				AND
				id_world = $worldId";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res[0]['id_userinstance'];
	}
	
	static public function HaseUserEmbassy($userInstanceId, $dbId)
	{
		assert($userInstanceId >= 0);
		assert(is_int($dbId) && $dbId >= 0);

		$common = self::getCommon();
		$common->setDbId($dbId);
		
		$sql = "SELECT
						b.buildingname
				FROM
					ds_comb as c
			LEFT JOIN ds_attachedbuilding ab ON (ab.id_comb = c.id_comb)
			LEFT JOIN ds_building b ON (ab.id_building = b.id_building)
				WHERE
					c.id_userinstance = $userInstanceId
					AND
					b.buildingname = 'ALLIANCE_CONNECTOR'";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if (count($res) > 0 )
		{
			return true;
		} else {
			return false;
		}
	}
	
	static public function GetCountMembers($allianceId)
	{
		assert(is_int($allianceId) && $allianceId > 0);

		$membersAmmount = 0;
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$worlds = self::GetWordList();
	
		foreach ($worlds as $key => $data)		
		{
			$common->setDbId((int)$data['dbid']);
			
			$sql = "
				SELECT
					a.members_amount
				FROM
					ds_alliance as a
			
				WHERE
					a.id_global_alliance = $allianceId
					AND
					a.id_world = ".$data['id_world']."
				";
		
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			$membersAmmount += $res[0]['members_amount'];
		
		}
		return $membersAmmount;
	}
	
	static public function GetBonusByWorlds($glabalAllianceId)
	{
		assert(is_int($glabalAllianceId) && $glabalAllianceId > 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		foreach ($GLOBALS['dbWorldAccess'] as $dbId => $dbData)		
		{
			$common->setDbId((int)$dbId);
			
			//Выбираем список локальных альянсов, в данной базе, соответствующих глобальному альянсу
			$sql = "SELECT a.id_alliance, w.worldname
						FROM
						ds_alliance as a
						LEFT JOIN ds_world as w ON (w.id_world  = a.id_world)
						WHERE a.id_global_alliance = $glabalAllianceId";
			$local_allinces = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));

			foreach ($local_allinces as $key => $alliance)
			{
				$sql = "
					SELECT 	an.attributename, 
							sum(ahb.value)
						FROM ds_artefactattachedalliance as aaa
						LEFT JOIN ds_artefactinstance as ai ON (ai.id_artefactinstance = aaa.id_artefact)
						LEFT JOIN ds_artefacthasbonus as ahb ON (ai.id_artefact = ahb.id_artefact)
						LEFT JOIN ds_attribute as a ON (a.id_attribute = ahb.id_attribute)
						LEFT JOIN ds_attributename as an ON (an.id_attributename = a.id_attributename)
						 					WHERE
						                     aaa.id_alliance = ".$alliance['id_alliance']."
						                     GROUP BY an.attributename, a.id_attribute
					";

				$res = $common->getResultArray("", $sql, "", "", "");
				assert(empty($common->errormsg));


				foreach ($res as $k => $v)
				{
					$bonus[$alliance['worldname']][$v['attributename']] = $v['sum'];
				}
			}
		}
		return $bonus;
	}
	
	static public function GetMembersAmmountByWorlds($allianceId)
	{
		assert(is_int($allianceId) && $allianceId > 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$worlds = self::GetWordList();
	
		foreach ($worlds as $key => $data)		
		{
			$common->setDbId((int)$data['dbid']);
			
			$sql = "
				SELECT
					a.members_amount,
					COALESCE(ar.totalpoints, 0) as totalpoints,
					COALESCE(ar.averagepoints, 0) as averagepoints
				FROM
					ds_alliance as a
				LEFT JOIN ds_allianceranking ar ON (ar.id_alliance = a.id_alliance)
				WHERE
					a.id_global_alliance = $allianceId
					AND
					a.id_world = ".$data['id_world']."
				";
	
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			if (!empty($res))
			{
				$membersAmmount[$data['worldname']]['id_world'] =  $data['id_world'];
				$membersAmmount[$data['worldname']]['members_amount'] =  $res[0]['members_amount'];
				$membersAmmount[$data['worldname']]['dbid'] =   $data['dbid'];
				$membersAmmount[$data['worldname']]['totalpoints'] =   $res[0]['totalpoints'];
				$membersAmmount[$data['worldname']]['averagepoints'] =   $res[0]['averagepoints'];
			}
		}
		
		return $membersAmmount;
	}
		
	static public function GetLocalAllianceByGlobalAllianceAndWorldID($globalAllianceId, $dbId, $worldId)
	{
		$common = self::getCommon();
		$common->setDbId($dbId);
		
		$sql = "
				SELECT
				id_alliance
			FROM
				ds_alliance		
			WHERE
				 id_global_alliance = $globalAllianceId
				AND
				 id_world = $worldId
				";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0]['id_alliance'];
	}
			
	static public function AddUserToExpectation($userId, $globalAllianceId, $comments)
	{
		assert($globalAllianceId >= 0);
		assert($userId >= 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
				INSERT INTO ds_alliance_new_members 
					 (id_global_alliance, id_user, comments)
					VALUES 
					 ($globalAllianceId, $userId, E'".pg_escape_string(urldecode($comments))."')		
				";
		
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function CheckUserInExpectation($userId)
	{
		assert($userId >= 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
				SELECT ga.alliance_name
					FROM
						ds_alliance_new_members as anm
					LEFT JOIN ds_global_alliance as ga ON (ga.id_global_alliance = anm.id_global_alliance)
					WHERE 		
						anm.id_user = $userId
				";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0]['alliance_name'];
	}
	
	static public function HasUserInstance($userId)
	{
		assert($userId >= 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
				SELECT u.id_userinstance
					FROM
						ds_userinstance as u
							WHERE 		
						u.id_user = $userId
				";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if (count($res) > 0 )
		{
			return true;
		} else {
			return false;
		}
	}	
	
	static public function UnExpectationAlliance($userId)
	{
		assert($userId >= 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
				DELETE 
					FROM  
						ds_alliance_new_members 
					WHERE 		
						id_user = $userId
				";

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function GetListingRequest($globalAllianceId)
	{
		assert(is_int($globalAllianceId) && $globalAllianceId >= 0);
				
		$common = self::getCommon();
		$common->setDbId(0);
		
		$sql = "
				SELECT 
				  anm.*,
				  u.username
				FROM ds_alliance_new_members as anm
				LEFT JOIN ds_user u ON (u.id_user = anm.id_user)
 					WHERE
                    anm.id_global_alliance = $globalAllianceId
			";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}
	
	static public function DeleteOrder($globalAllianceId, $id_user)
	{
		assert(is_int($globalAllianceId) && $globalAllianceId >= 0);
				
		$common = self::getCommon();
		$common->setDbId(0);
		assert($id_user >= 0);

		$sql = "
				DELETE
				FROM ds_alliance_new_members
				 			WHERE
				                  id_user = $id_user
				                 AND
				                  id_global_alliance = $globalAllianceId
				";

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}

	static public function GetUserWorlds($userId)
	{
		assert($userId >= 0);
				
		$common = self::getCommon();
		$common->setDbId(0);

		$sql = "SELECT
					uic.dbname,
					uic.id_world,
					w.worldname
				FROM
					ds_userinstancecollection as uic
					LEFT JOIN ds_world as w ON (w.id_world = uic.id_world)
				WHERE
					uic.id_user = $userId
				";
	
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function InsertUserToBlackList($globalAllianceId, $id_user, $comments)
	{
		assert(is_int($globalAllianceId) && $globalAllianceId >= 0);
				
		$common = self::getCommon();
		$common->setDbId(0);
		assert($id_user >= 0);

		$sql = "
				INSERT INTO  ds_global_alliance_user_black_list 
						 (id_user, id_global_alliance, comments)
						VALUES 
						 ($id_user, $globalAllianceId, E'".pg_escape_string(urldecode($comments))."')
				";

		$common->executeQuery($sql);		
		assert(empty($common->errormsg));
	}
	
	static public function GetWordStat($dbId, $worldId, $globalAllianceId)
	{
		
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(is_int($globalAllianceId) && $globalAllianceId >= 0);

		$common = self::getCommon();
		$common->setDbId($dbId);
		
		
		$sql = "
				SELECT 	u.username,
			        	an.attributename,
        				sum(ahb.value),
        				count(ahb.value)
					FROM 
						ds_artefactattachedalliance as aaa
					LEFT JOIN ds_artefactinstance as ai ON (ai.id_artefactinstance = aaa.id_artefact)
					LEFT JOIN ds_artefacthasbonus as ahb ON (ai.id_artefact = ahb.id_artefact)
					LEFT JOIN ds_attribute as a ON (a.id_attribute = ahb.id_attribute)
					LEFT JOIN ds_attributename as an ON (an.id_attributename = a.id_attributename)
					LEFT JOIN ds_artefactattachedbuilding as aab ON (aab.id_artefact = ai.id_artefactinstance)
					LEFT JOIN ds_attachedbuilding as ab ON (ab.id_attachedbuilding = aab.id_attachedbuilding)
					LEFT JOIN ds_comb as c ON (c.id_comb = ab.id_comb)
					LEFT JOIN ds_userinstance as ui ON (ui.id_userinstance = c.id_userinstance AND ui.id_world = $worldId)
					LEFT JOIN ds_user as u ON (u.id_user = ui.id_user)
						 					WHERE
						                     aaa.id_alliance = (SELECT 	a.id_alliance 
						                     						FROM 
						                     							ds_alliance as a 
						                     						WHERE 
						                     							a.id_world = $worldId
                                             							AND 
                                             							a.id_global_alliance = $globalAllianceId)
      				GROUP BY  ab.id_comb, an.attributename, u.username
      				ORDER BY u.username
				";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
	
		$stat[$res[0]['username']]['count'] = 0;
		
		foreach ($res as $k => $data)
		{
			$stat[$data['username']]['count'] += $data['count'];
			$stat[$data['username']][$data['attributename']] = $data['sum'];
			//$stat['Total'][$data['attributename']] += $data['sum'];
		}
		
		return $stat;
	}
	
	static public function DeleteUserFromBlackList($globalAllianceId, $id_user)
	{
		assert(is_int($globalAllianceId) && $globalAllianceId >= 0);
				
		$common = self::getCommon();
		$common->setDbId(0);
		assert($id_user >= 0);

		$sql = "
				DELETE 
					FROM  
						ds_global_alliance_user_black_list 
					WHERE
						id_user = $id_user
						AND
						id_global_alliance = $globalAllianceId
				";

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function GetWordList()
	{
		$common = self::getCommon();

		$out = array();

		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			foreach($res as $k=>$v)
			{
				$v['dbid'] = $dbid;
				$out[] = $v;
			}
		}
		return $out;
	}
		
	static public function GetUserIdByName($userName)
	{
		$common = self::getCommon();

		$common->setDbId(0);

		$sql = "SELECT id_user
					FROM
					    ds_user
					   
					WHERE
						username = '$userName'
					";
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));

		return $res[0]['id_user'];
	}
	
	static public function GetUserInstance($userId)
	{
		assert(is_int($userId) && $userId > 0);

		$common = self::getCommon();
		$common->setDbId(0);

		$sql = "SELECT 	id_world,
						dbname,
						id_userinstance
					FROM 
						ds_userinstancecollection
					WHERE 
						id_user = $userId";
				
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		
		return $res;
	}
		
	static public function SendMessageToChoosenWorld($userInsnanceId, $dbId, $worldId, $subject, $messagetext, $globalAllianceId)
	{
		assert($userInsnanceId >= 0);
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(!empty($subject));
		assert(!empty($messagetext));
		assert(is_int($globalAllianceId) && $globalAllianceId >= 0);

		$common = self::getCommon();

		$common->setDbId($dbId);

		$sql = "select nextval('ds_message_id_message_seq') as id";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg) && count($res) > 0);

		$messageID = (int)$res[0]['id'];
		assert($messageID > 0);

		$sql = "INSERT INTO ds_message
				(
					id_message,
					id_messagescope,
					id_from_userinstance,
					subject,
					messagetext,
					messagedate
				) VALUES (
					".$messageID.",
					4,
					".$userInsnanceId.",
					E'".pg_escape_string(urldecode($subject))."',
					E'".pg_escape_string(urldecode($messagetext))."',
					now() 
				)";
		$res = $common->getResultArray("", $sql, "", "", "");
		//assert(empty($common->errormsg));

		settype($messageID, 'integer');
		assert($messageID > 0);

		//Выбираем всех членов альянся в этом мире
		$sql = "SELECT uha.id_userinstance
				FROM
				    ds_userhasalliance  as uha
				LEFT JOIN ds_alliance as a ON  (uha.id_alliance  = a.id_alliance)
				WHERE
				 a.id_global_alliance = $globalAllianceId
				AND
			 	 a.id_world = $worldId
			 	AND
				 uha.id_userinstance != $userInsnanceId";

		$usersTo = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		foreach ($usersTo as $kye => $userInstance)
		{
			$sql = "INSERT INTO ds_userhasmessage
					(id_message, id_userinstance )
					VALUES 
					( $messageID, ".$userInstance['id_userinstance']." )";
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));			
		}
	}
	
	static public function SendAllianceMessageToUser($userInstanceFrom, $userInstanceTo, $worId, $dbName, $subject, $messagetext, $globalAllianceId)
	{
		assert($userInstanceFrom >= 0);
		assert($userInstanceTo > 0);
		assert($worId > 0);
		assert(!empty($subject));
		assert(!empty($messagetext));
		assert($globalAllianceId >= 0);

		$common = self::getCommon();
		$dbId = $common->getDBIDByName($dbName);

		$out = array();

		$common->setDbId((int)$dbId);

		$sql = "select nextval('ds_message_id_message_seq') as id";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg) && count($res) > 0);

		$messageID = (int)$res[0]['id'];
		assert($messageID > 0);

		$sql = "INSERT INTO ds_message
				(
					id_message,
					id_messagescope,
					id_from_userinstance,
					subject,
					messagetext,
					messagedate
				) VALUES (
					".$messageID.",
					4,
					".$userInstanceFrom.",
					E'".pg_escape_string(urldecode($subject))."',
					E'".pg_escape_string(urldecode($messagetext))."',
					now() 
				)";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		settype($messageID, 'integer');
		assert($messageID > 0);

		$sql = "INSERT INTO ds_userhasmessage
					(id_message, id_userinstance )
					VALUES 
					( $messageID, ".$userInstanceTo." )";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
	}
	
	static public function GetUserRunking($dbName, $worId, $userId)
	{
		assert(!empty($dbName));
		assert($worId > 0);
		assert($userId > 0);
		
		$common = self::getCommon();
		$dbId = $common->getDBIDByName($dbName);

		$common->setDbId((int)$dbId);

		$sql = "SELECT 
				       COALESCE(fr.thefinal, 0) as runking,
				       COALESCE(be.value, 0) as battlexp
					FROM 
						ds_finalranking fr
					LEFT JOIN ds_battlexp be ON (be.id_userinstance = fr.id_userinstance)
				WHERE 
						fr.id_user = $userId
						AND
						fr.id_world = $worId 
				";
		$res = $common->getResultArray("", $sql, "", "", "");
		
		return $res;
	}
	
	static public function SendGlobalMessageToChoosenWorld($userId, $worlds, $subject, $messagetext)
	{
		assert(is_array($worlds));
		assert(!empty($subject));
		assert(!empty($messagetext));
		//assert(is_int($globalAllianceId) && $globalAllianceId >= 0);

		$common = self::getCommon();

		$out = array();

		foreach($worlds as $key => $value)
		{
			$tempArr = explode('_', $value);
			$dbId = (int)$tempArr[0];
			$worldId = (int)$tempArr[1];

			assert(is_int($dbId) && $dbId >= 0);
			assert(is_int($worldId) && $worldId > 0);

			$common->setDbId((int)$dbId);

			$sql = "select nextval('ds_message_id_message_seq') as id";
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg) && count($res) > 0);

			$messageID = (int)$res[0]['id'];
			assert($messageID > 0);

			//Выбираем userinstance
			$sql = "SELECT
				id_userinstance
			FROM
				ds_userinstance			
			WHERE
				id_user = $userId
				AND
				id_world = $worldId";
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			$id_user_from = $res[0]['id_userinstance'];


			$sql = "INSERT INTO ds_message
				(
					id_message,
					id_messagescope,
					id_from_userinstance,
					subject,
					messagetext,
					messagedate
				) VALUES (
					".$messageID.",
					9,
					".$id_user_from.",
					E'".pg_escape_string(urldecode($subject))."',
					E'".pg_escape_string(urldecode($messagetext))."',
					now() 
				)";
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));

			settype($messageID, 'integer');
			assert($messageID > 0);

			//Выбираем всех игроков в этом мире
			$sql = "select id_userinstance
							from ds_userinstance
							where id_world = $worldId
							and id_userinstance != $id_user_from";

			$usersTo = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));

			foreach ($usersTo as $kye => $userInstance)
			{
				$sql = "INSERT INTO ds_userhasmessage
					(id_message, id_userinstance )
					VALUES 
					( $messageID, ".$userInstance['id_userinstance']." )";
				$res = $common->getResultArray("", $sql, "", "", "");
				assert(empty($common->errormsg));
			}
		}
	}
	
	static public function IsUserInAlliance($userId, $allianceId)
	{
		assert(is_int($userId) && $userId >= 0);
		assert(is_int($allianceId) && $allianceId >= 0);

		$worldList = self::GetWordList();
	
		$common = self::getCommon();

		foreach($worldList as $key=> $worldData)
		{
			$worldId = (int)$worldData['id_world'];
			$dbId = (int)$worldData['dbid'];
	
			assert(is_int($dbId) && $dbId >= 0);
			assert(is_int($worldId) && $worldId > 0);
		
			$common->setDbId((int)$dbId);

			//Выбираем userinstance
			$sql = "SELECT
				id_userinstance
			FROM
				ds_userinstance			
			WHERE
				id_user = $userId
				AND
				id_world = $worldId";
			
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			if (count($res) == 0)
			{
				continue;
			}
			
			$userInctance = $res[0]['id_userinstance'];
			
			$sql = "SELECT *
						FROM ds_userhasalliance as uha
						LEFT JOIN ds_alliance as a ON (uha.id_alliance = a.id_alliance)
						WHERE 
						uha.id_userinstance = $userInctance
						AND a.id_global_alliance = $allianceId";

			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			if (count($res) > 0 )
			{
				return true;
			}
		}
		
		return false;
	}
		
	static public function CheckUniqAllianceName($allianceName)
	{
		assert(!empty($allianceName));

		$common = self::getCommon();
		$common->setDbId(0);
		
		//Выбираем ID глобального альянса с данным именем
		$sql = "SELECT 
					a.id_global_alliance 
				FROM 
					ds_global_alliance a 
				WHERE 
					a.alliance_name = '$allianceName'";
			
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if (count($res) > 0 )
		{
			return 'GLOBAL';
		}
		
		$worlds = self::GetWordList();
		
		foreach ($worlds as $key => $data)		
		{
			$common->setDbId((int)$data['dbid']);

			//Выбираем ID альянса с данным именем
			$sql = "SELECT 
						a.id_alliance 
					FROM 
						ds_alliance a 
					WHERE 
						a.alliancename = '$allianceName'
						AND 
						a.id_world = ".$data['id_world'];
			
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			
			if (count($res) > 0 )
			{
				return $data['worldname'];
			}
		}
		
		return false;
	}
		
	static public function CheckUniqAllianceTag($allianceTag)
	{
		assert(!empty($allianceTag));

		$common = self::getCommon();
		$common->setDbId(0);
		
		//Выбираем ID глобального альянса с данным именем
		$sql = "SELECT 
					id_global_alliance 
				FROM 
					ds_global_alliance 
				WHERE 
					short_name = '$allianceTag'";
			
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if (count($res) > 0 )
		{
			return $res[0]['id_global_alliance'];
		}
		
		$worlds = self::GetWordList();
		
		foreach ($worlds as $key => $data)		
		{
			$common->setDbId((int)$data['dbid']);

			//Выбираем ID альянса с данным именем
			$sql = "SELECT 
						a.id_alliance 
					FROM 
						ds_alliance a 
					WHERE 
						a.alliancetag = '[$allianceTag]'
						AND 
						a.id_world = ".$data['id_world'];
			
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			
			if (count($res) > 0 )
			{
				return $data['worldname'];
			}
		}
		
		return false;
	}	
	
	static public function RenameAlliance($allianceName, $shotAllianceName, $glabalAllianceId)
	{
		assert(!empty($allianceName));
		assert(!empty($shotAllianceName));
		assert(is_int($glabalAllianceId) && $glabalAllianceId >= 0);

		$common = self::getCommon();
		
		foreach ($GLOBALS['dbWorldAccess'] as $dbId => $dbData)		
		{
			$common->setDbId((int)$dbId);

			//Выбираем ID альянса с данным именем
			$sql = "UPDATE  
						ds_alliance
					SET 
						alliancename = '$allianceName',
						alliancetag = '[$shotAllianceName]'
					WHERE
						id_global_alliance = $glabalAllianceId 
					";
			
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			
			if (count($res) > 0 )
			{
				return true;
			}
		}
		
		return false;
	}	
	
	/*static public function GetMembers($glabalAllianceId)
	{
		assert(is_int($glabalAllianceId) && $glabalAllianceId >= 0);

		$worldList = self::GetWordList();
	
		$common = self::getCommon();

		foreach($worldList as $key=> $worldData)
		{
			$worldId = (int)$worldData['id_world'];
			$dbId = (int)$worldData['dbid'];
			$worldName = $worldData['worldname'];
	
			assert(is_int($dbId) && $dbId >= 0);
			assert(is_int($worldId) && $worldId > 0);
		
			$common->setDbId((int)$dbId);

			//Выбираем всех пользователей альянса
			$sql = "SELECT 	u.username, 
						COALESCE(fr.thefinal, 0) as runking,
						COALESCE(be.value, 0) as battlexp,
						a.id_alliance,
						ui.id_userinstance
					FROM ds_userhasalliance uha
					LEFT JOIN ds_alliance a ON (a.id_alliance = uha.id_alliance)
					LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = uha.id_userinstance)
					LEFT JOIN ds_user u ON (u.id_user = ui.id_user)	
					LEFT JOIN ds_finalranking fr ON (fr.id_userinstance = ui.id_userinstance)
					LEFT JOIN ds_battlexp be ON (be.id_userinstance = ui.id_userinstance)					
					WHERE 
						 a.id_global_alliance = $glabalAllianceId
						AND
                         a.id_world = $worldId";
		
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			$member[$worldName] = $res;
		}
		return $member;
	}*/
	
	static public function GetGlobalAllianceUsers($glabalAllianceId)
	{
		assert($glabalAllianceId >= 0);

		$common = self::getCommon(0);

		//Выбираем всех пользователей альянса
		$sql = "SELECT
					gau.id_user,
					u.username
				FROM
					ds_global_alliance_users gau
				LEFT JOIN ds_user u ON (u.id_user  = gau.id_user)
				WHERE 
					id_global_alliance = $glabalAllianceId";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res;
	}
		
	static public function GetMembersInWorld($glabalAllianceId, $dbId, $worldId)
	{
		assert(is_int($glabalAllianceId) && $glabalAllianceId >= 0);
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);

		$common = self::getCommon();
		$common->setDbId((int)$dbId);

		//Выбираем всех пользователей альянса
		$sql = "SELECT 	u.username, 
						COALESCE(fr.thefinal, 0) as runking,
						COALESCE(be.value, 0) as battlexp
					FROM ds_userhasalliance uha
					LEFT JOIN ds_alliance a ON (a.id_alliance = uha.id_alliance)
					LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = uha.id_userinstance)
					LEFT JOIN ds_user u ON (u.id_user = ui.id_user)	
					LEFT JOIN ds_finalranking fr ON (fr.id_userinstance = ui.id_userinstance)
					LEFT JOIN ds_battlexp be ON (be.id_userinstance = ui.id_userinstance)					
					WHERE 
						 a.id_global_alliance = $glabalAllianceId
						AND
                         a.id_world = $worldId";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;

	}
		
	static public function GetMembersListInWorld($glabalAllianceId, $dbId, $worldId)
	{
		assert(is_int($glabalAllianceId) && $glabalAllianceId >= 0);
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);

		$common = self::getCommon();
		$common->setDbId((int)$dbId);

		//Выбираем всех пользователей альянса
		$sql = "SELECT 	u.username
					FROM ds_userhasalliance uha
					LEFT JOIN ds_alliance a ON (a.id_alliance = uha.id_alliance)
					LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = uha.id_userinstance)
					LEFT JOIN ds_user u ON (u.id_user = ui.id_user)					
					WHERE 
						 a.id_global_alliance = $glabalAllianceId
						AND
                         a.id_world = $worldId";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;

	}
	
	static public function AddAvatar($glabalAllianceId, $fileName)
	{
		assert(is_int($glabalAllianceId) && $glabalAllianceId >= 0);
		assert(!empty($fileName));

		$worldList = self::GetWordList();
	
		$common = self::getCommon();

		foreach($worldList as $key=> $worldData)
		{
			$worldId = (int)$worldData['id_world'];
			$dbId = (int)$worldData['dbid'];
			$worldName = $worldData['worldname'];
			
			$localAlliance = self::GetLocalAllianceByGlobalAllianceAndWorldID($glabalAllianceId, $dbId, $worldId);
	
			assert(is_int($dbId) && $dbId >= 0);
			assert(is_int($worldId) && $worldId > 0);
		
			$common->setDbId((int)$dbId);
			
			//Вставляем сведения об аватарки в базу данных
			$sql = "INSERT INTO
				 ds_alliance_has_image
				 	(id_alliance, image_url)
				 VALUES 
				 	($localAlliance, '$fileName')";

			$common->executeQuery($sql);
			assert(empty($common->errormsg));
		}
		
		return;
	}
		
	static public function UpdateAvatar($glabalAllianceId, $fileName)
	{
		assert(is_int($glabalAllianceId) && $glabalAllianceId >= 0);
		assert(!empty($fileName));

		$worldList = self::GetWordList();
	
		$common = self::getCommon();

		foreach($worldList as $key=> $worldData)
		{
			$worldId = (int)$worldData['id_world'];
			$dbId = (int)$worldData['dbid'];
			$worldName = $worldData['worldname'];
			
			$localAlliance = self::GetLocalAllianceByGlobalAllianceAndWorldID($glabalAllianceId, $dbId, $worldId);
	
			assert(is_int($dbId) && $dbId >= 0);
			assert(is_int($worldId) && $worldId > 0);
		
			$common->setDbId((int)$dbId);
			
			//Вставляем сведения об аватарки в базу данных
			$sql = "UPDATE ds_alliance_has_image 
						SET  
							image_url = '$fileName'
						WHERE
							id_alliance = $localAlliance";

			$common->executeQuery($sql);
			assert(empty($common->errormsg));
		}
		
		return;
	}
		
	static public function GetLeaderLeader($glabalAllianceId)
	{
		assert(is_int($glabalAllianceId) && $glabalAllianceId >= 0);

		$common = self::getCommon();

		$common->setDbId(0);

		//Проверяем, кто лидер
		$sql = "SELECT id_leader
					FROM
				 		ds_global_alliance
					WHERE
						id_global_alliance = $glabalAllianceId";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		return $res[0]['id_leader'];
	}
	
	static public function GetBlackList($glabalAllianceId)
	{
		assert(is_int($glabalAllianceId) && $glabalAllianceId >= 0);

		$common = self::getCommon();

		$common->setDbId(0);

		//Проверяем, кто лидер
		$sql = "SELECT 	u.username, 
						g.*
					FROM
				 		ds_global_alliance_user_black_list as g
		 			LEFT JOIN ds_user u ON (u.id_user = g.id_user)
					WHERE
						g.id_global_alliance = $glabalAllianceId";

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}	
	
	static public function GetAllianceAvatar($glabalAllianceId)
	{
		assert(is_int($glabalAllianceId) && $glabalAllianceId >= 0);

		$worldList = self::GetWordList();
	
		$common = self::getCommon();

		foreach($worldList as $key=> $worldData)
		{
			$worldId = (int)$worldData['id_world'];
			$dbId = (int)$worldData['dbid'];
			$worldName = $worldData['worldname'];
			
			$localAlliance = self::GetLocalAllianceByGlobalAllianceAndWorldID($glabalAllianceId, $dbId, $worldId);
	
			assert(is_int($dbId) && $dbId >= 0);
			assert(is_int($worldId) && $worldId > 0);
		
			$common->setDbId((int)$dbId);
			
			//Вставляем сведения об аватарки в базу данных
			$sql = "SELECT image_url
						FROM
				 			ds_alliance_has_image
					 	WHERE
				 			id_alliance = $localAlliance";

			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			
			return $res[0]['image_url'];
		}				
	}
	
	static public function DeleteAlliance(DE_GlobalAlliance $globalAlliance)
	{
		$common = self::getCommon();

		$common->setDbId(0);

		$sql = "DELETE FROM ds_alliance_new_members
					WHERE
						id_global_alliance = ".$globalAlliance->getID();

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		
		$sql = "DELETE FROM ds_global_alliance_user_black_list
					WHERE
						id_global_alliance = ".$globalAlliance->getID();

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		$sql = "DELETE FROM ds_global_alliance_users
					WHERE
						id_global_alliance = ".$globalAlliance->getID();

		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		$sql = "DELETE FROM ds_global_alliance
					WHERE
						id_global_alliance = ".$globalAlliance->getID();
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
	
	}	
	
	static public function DeleteLocalAlliance($globalAllianceId, $dbId, $worldId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		assert(is_int($globalAllianceId) && $globalAllianceId >= 0);
		
		$common = self::getCommon();

		$common->setDbId($dbId);

		$sql = "DELETE FROM 
					ds_alliance
				WHERE 
					id_global_alliance = $globalAllianceId
					AND
					id_world = $worldId";

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}

}
?>