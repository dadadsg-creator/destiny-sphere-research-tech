<?php

class SU_Lotery
{
	static private $dbID;
	
	static private $worldId;
	
	static private $loteryArtId = 1;
	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $users;
	
	static private $arts = array();
	
	static public function Exec($dbID, $worldId, array $arts)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		self::$dbID = $dbID;
		
		self::$worldId = $worldId;
		
		self::$common = DS_Common::CreateForCurentDB($dbID);
		
		self::$arts = array(
				0 => array('count' => 10, 'scope' => 'COMB', 'probability' => 'SUPERIOR', 'arts' => array(23, 9, 30, 37, 16)),
				1 => array('count' => 10, 'scope' => 'COMB', 'probability' => 'GREAT', 	  'arts' => array(58, 44, 65, 72, 51)),
				2 => array('count' => 20, 'scope' => 'COMB', 'probability' => 'MAJOR',    'arts' => array(93, 79, 100, 107, 86)),
			);
			
		self::_getUsersWithLoteryArt();
		
		self::$common->startTrans();
		
		foreach (self::$arts as $k => $type)
		{
			self::_addArtefactType((int)$type['count'], $type);
		}
		
		self::$common->closeTrans('ROLLBACK');
	}
	
	static private function _addArtefactType($count, array $type)
	{
		assert(is_int($count) && $count > 0);
		assert(key_exists('scope', $type));
		assert(key_exists('probability', $type));
		
		if(key_exists('arts', $type) && count($type['arts']) > 0)
		{
			foreach ($type['arts'] as $k => $art)
			{
				self::_addArtefacts((int)$art, $count);
			}
		} else {
			$arts = self::_getArtefactsByScopeAndProbability($type['scope'], $type['probability']);
					
			foreach ($arts as $k => $art)
			{
				self::_addArtefacts((int)$art['id_artefact'], $count);
			}
		}
	}
	
	static private function _addArtefacts($id_artefact, $countArts)
	{
		assert(is_int($countArts) && $countArts > 0);
		$users = self::_getRandomUsersForPresent($countArts);
		
		foreach ($users as $k => $userIdx)
		{
			$user = self::$users[$userIdx];
			
			$artData = self::_getArtefactForReplace((int)$user['id_userinstance']);
		
			echo "User ".$user['username']." get artefact ".$id_artefact."<br>\n";
			
			$data = array(
					'id_artefactinstance' => $artData[0]['id_artefact'], 
					'id_artefact' => $id_artefact, 
					'id_comb' => $artData[0]['id_comb'], 
					'id_user' => (int)$user['id_user']
				);
			
			self::_replace($data);
			
			unset(self::$users[$userIdx]);
		}
	}
	
	static private function _getRandomUsersForPresent($count)
	{
		assert(is_int($count) && $count > 0);
		assert(is_array(self::$users));		
		
		$count = min($count, count(self::$users));	
		
		if($count <= 0)
		{
			return array();
		}
		
		$randomUsers = array_rand(self::$users, $count);
		
		if(!is_array($randomUsers))
		{
			$randomUsers = array($randomUsers);
		}
		
		return $randomUsers;
	}
	
	static private function _replace(array $data)
	{
		assert(key_exists('id_artefactinstance', $data));
		assert(key_exists('id_artefact', $data));
		
		$sql = "
			UPDATE 
				ds_artefactinstance
			SET
				id_artefact = ".$data['id_artefact']."
			WHERE
				id_artefactinstance = ".$data['id_artefactinstance']."
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		self::_logPresent($data);
	}
	
	static private function _getArtefactsByScopeAndProbability($scope, $prob)
	{
		assert(!empty($scope));
		assert(!empty($prob));
		
		$sql = "
			SELECT	
				id_artefact
			FROM
				ds_artefact AS a
				INNER JOIN ds_artefactscope AS acp USING (id_artefactscope)
				INNER JOIN ds_artefactprobability AS ap USING (id_artefactprobability)
			WHERE
				acp.scopename = '$scope'
				AND
				ap.prefix = '$prob'			
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getArtefactForReplace($id_userinstance)
	{
		assert(is_int($id_userinstance) && $id_userinstance > 0);
		
		$sql = "
			SELECT
				cha.id_artefact,
				cha.id_comb
			FROM
				ds_combhasartefact AS cha
				INNER JOIN ds_comb AS c USING (id_comb)
				INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
			WHERE
				c.id_userinstance = ".$id_userinstance."
				AND
				ai.id_artefact = ".self::$loteryArtId."
			LIMIT 1
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getUsersWithLoteryArt()
	{
		$sql = "
			SELECT DISTINCT
				ui.id_userinstance,
				u.id_user,
				u.username
			FROM
				ds_combhasartefact AS cha
				INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
				INNER JOIN ds_comb AS c USING (id_comb)
				INNER JOIN ds_userinstance AS ui USING (id_userinstance)
				INNER JOIN ds_user AS u USING (id_user)
			WHERE
				ai.id_artefact = ".self::$loteryArtId."
				AND
				ui.id_world = ".self::$worldId."
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		self::$users = $res;
		
		return count($res);
	}
	
	static private function _logPresent(array $data)
	{
		assert(key_exists('id_artefactinstance', $data));
		assert(key_exists('id_artefact', $data));
		assert(key_exists('id_comb', $data));
		assert(key_exists('id_user', $data));
		
		$sql = "
			INSERT INTO ds_lotery_artefacts
				(id_artefactinstance, id_comb, id_user, id_artefact_present, date)
			VALUES
				(".$data['id_artefactinstance'].", ".$data['id_comb'].", ".$data['id_user'].", ".$data['id_artefact'].", now())
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));	
	}
}

?>