<?php

class TMP_ClearDTFArtefacts
{
	static private $artefacts = array(
			'SIMPLE' => array('SMALL_QUEST_PHONEICON', 'MINOR_QUEST_MOUSEICON', 'MINOR_QUEST_JOYSTIKICON'),
			'BOX' => array('WHITE_QUEST_BOXIM'),
			'PRIZE' => array('GOLD_COMB_JOYSTIK2', 'GOLD_SPHERE_MOUSE', 'GOLD_SPHERE_MOBILEPHONE'),
		);
			
	static private $artefactsID = array();
	
	static private $prob = array(
				'GOLD_COMB_JOYSTIK2' => 5, // 5%
				'GOLD_SPHERE_MOBILEPHONE' => 35, // 30%
				'GOLD_SPHERE_MOUSE' => 100, // 65%
			);
	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $worldID;
	
	static public function Exec($dbID, $worldID)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		self::$worldID = $worldID;
		
		self::$common = DS_Common::CreateForCurentDB($dbID);
	
		self::_initArtefactsID();
		
		$users = self::_getUsersWithArtefacts();
		
		foreach($users as $k => $user)
		{
			self::_processUser($user);
		}
		
		self::_clearQuestArtefacts();
	}
	
	static private function _initArtefactsID()
	{
		foreach (self::$artefacts as $type => $names)
		{
			$IDs = self::_getArtefactsID($type);
		
			foreach($IDs as $k => $artID)
			{
				self::$artefactsID[$type][$artID['artefactname']] = $artID['id_artefact'];
			}
		}
	}
	
	static private function _processUser(array $data)
	{
		assert(key_exists('id_comb', $data));
		
		$prizeArts = self::_getPrizeArtefacts($data);
		
		self::$common->startTrans();
		
		foreach ($prizeArts as $k => $artID) 
		{
			self::_addArtefact((int)$data['id_comb'], (int)$artID);
		}
		
		self::$common->closeTrans('COMMIT');	
	}
	
	static private function _getPrizeArtefacts(array $data)
	{
		echo "\n".$data['username'].' count simple: '.$data['count_simple'].'; count box: '.$data['count_box']."\n";
		
		$countPrizeArts = floor($data['count_simple'] / 3) + $data['count_box'];
		$prizeArts = array();
		
		echo "Count prize: ".$countPrizeArts."\n";
		
		for($i=0; $i<$countPrizeArts; $i++)
		{
			$rnd = rand(0, 99);
			
			foreach (self::$prob as $art => $p)
			{
				if($rnd < $p)
				{
					$prizeArts[] = self::$artefactsID['PRIZE'][$art];
					break;
				}
			}
		}
		
		if(($data['count_simple'] % 3) > 0)
		{
			$prizeArts[] = self::$artefactsID['PRIZE']['GOLD_SPHERE_MOUSE'];
		}
//print_r($prizeArts);			
		return $prizeArts;
	}
	
	
	static private function _addArtefact($combID, $artefactID)
	{
		assert(is_int($combID) && $combID > 0);
		assert(is_int($artefactID) && $artefactID > 0);
		
		// Бессметный!!!!!!
		$sql = "SELECT add_artefact_to_comb($combID, $artefactID, 0)";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	static private function _clearQuestArtefacts()
	{
		$sql = "
			SELECT
				id_artefactinstance
			FROM
				ds_artefactinstance AS ai
				INNER JOIN ds_combhasartefact AS cha ON (cha.id_artefact = ai.id_artefactinstance)
			WHERE
				ai.id_artefact IN (".implode(',', array_merge(self::$artefactsID['SIMPLE'], self::$artefactsID['BOX'])).")
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		self::$common->startTrans();
		
		foreach ($res as $k => $art)
		{
			$sql1 = "
				DELETE FROM
					ds_combhasartefact
				WHERE
					id_artefact = ".$art['id_artefactinstance']."
			";
			self::$common->executeQuery($sql1);
			assert(empty(self::$common->errormsg));
			
			$sql2 = "
				DELETE FROM
					ds_artefactinstance
				WHERE
					id_artefactinstance = ".$art['id_artefactinstance']."
			";
			self::$common->executeQuery($sql2);
			assert(empty(self::$common->errormsg));
		}
		
		self::$common->closeTrans('COMMIT');	
	}
	
	
	static private function _getArtefactsID($type)
	{	
		assert(key_exists($type, self::$artefacts));
		
		$sql = "
			SELECT
				id_artefact,
				artefactname
			FROM
				ds_artefact
			WHERE
				artefactname IN ('".implode("','", self::$artefacts[$type])."')
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getUsersWithArtefacts()
	{
		$sql = "
			SELECT DISTINCT
				cha.id_comb,
				u.username,
				
				(
					SELECT
						count(*)
					FROM
						ds_combhasartefact AS cha2
						INNER JOIN ds_artefactinstance AS ai2 ON (ai2.id_artefactinstance = cha2.id_artefact)
					WHERE
						ai2.id_artefact IN (".implode(',', self::$artefactsID['SIMPLE']).")
						AND
						cha2.id_comb = cha.id_comb
				) as count_simple,
				
				(
					SELECT
						count(*)
					FROM
						ds_combhasartefact AS cha3
						INNER JOIN ds_artefactinstance AS ai3 ON (ai3.id_artefactinstance = cha3.id_artefact)
					WHERE
						ai3.id_artefact IN (".implode(',', self::$artefactsID['BOX']).")
						AND
						cha3.id_comb = cha.id_comb
				) as count_box
				
			FROM
				ds_combhasartefact AS cha
				INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
				INNER JOIN ds_comb AS c USING (id_comb)
				INNER JOIN ds_userinstance AS ui USING (id_userinstance)
				INNER JOIN ds_user AS u USING (id_user)
			WHERE
				ai.id_artefact IN (".implode(',', array_merge(self::$artefactsID['SIMPLE'], self::$artefactsID['BOX'])).")
				AND
				ui.id_world = ".self::$worldID."
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	
}

?>