<?php
/**
 * Конверт юнитов в артефакты
 * Вызов в файле ConvertCholeraToUnits.php
 *
 */
class TMP_ConvertCholeraToUnits
{
	static private $dbID;
	
	static private $worldID;
	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $replaceArtefactsArr = array(
			1084 => 86,
			1085 => 84,
			1086 => 85,
			1087 => 87
		);
		
	static private $replaceArtefacts;
		
	static private $logFile = 'CholeraConvert.log';
	
	static public function Exec($dbID, $worldID)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		self::$dbID = $dbID;
		self::$worldID = $worldID;
		
		$_SESSION['world'] = $worldID;
		$_SESSION['dbId'] = $dbID;
		
		self::$common = DS_Common::CreateForCurentDB(self::$dbID);
	
		foreach (self::$replaceArtefactsArr as $artID => $unitID)
		{
			self::$replaceArtefacts[$artID] = DE_Unit::CreateFromID($unitID);
		}
		
		$artefactList = self::_getCombsWithArtefacts();
		
		self::_log();
		self::_log("---==== START CONVERT ====---");
		
		self::$common->startTrans();
		
		self::_log('found '.count($artefactList).' combs with arts');
		
		foreach ($artefactList as $k => $art)
		{
			self::_replace((int)$art['id_comb'], (int)$art['id_artefact'], (float)$art['count']);
		}
		
		GB_SingletonObjectStorage::CommitAllCommitable();
		
		self::$common->closeTrans('COMMIT');
		
		self::_log("---====     DONE      ====---");
		self::_log();
	}
	
	static private function _replace($combID, $artefactID, $count)
	{ 
		assert(is_int($combID) && $combID > 0);
		assert(is_int($artefactID) && $artefactID > 0);
		assert(is_float($count) && $count > 0);
		
		$unit = self::$replaceArtefacts[$artefactID];
		
		$homeArmy = DE_Army::CreateHomeArmyByCombID($combID);
		
		if ($homeArmy->getUserInstance()->getUser()->getName() == 'dada') {
			return ;
		}
		
		self::_log("Add $count ".$unit->getName()." to comb ".$homeArmy->getBaseComb()->getFullName()." of user ".$homeArmy->getUserInstance()->getUser()->getName());

		$homeUnitGroup = $homeArmy->getUnitsGroupCollection()->GetUnitGroupByUnit($unit);
		if($homeUnitGroup === false)
		{
			$homeUnitGroup = DE_UnitsGroup::CreateInDB($homeArmy, $unit, $count);
			$homeArmy->getUnitsGroupCollection()->AddUnitGroup($homeUnitGroup);
		} else {
			$homeUnitGroup->deltaAmount($count);
		}
		
		self::_deleteArtefact($combID, $artefactID);
	}
	
	static private function _deleteArtefact($combID, $artefactID)
	{
		assert(is_int($combID) && $combID > 0);
		assert(is_int($artefactID) && $artefactID > 0);
		
		$sql = "
			SELECT
				ARRAY_TO_STRING(
					ARRAY(
						SELECT 
							ai.id_artefactinstance
						FROM 
							ds_combhasartefact AS cha
							INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
						WHERE
							cha.id_comb = $combID
							AND
							ai.id_artefact = $artefactID
					),
					','
				) as artefacts
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg) && count($res) == 1);
		
		$artefactList = $res[0]['artefacts'];
		
		$sql = "DELETE FROM ds_combhasartefact WHERE id_artefact IN ($artefactList)";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		$sql = "DELETE FROM ds_artefactinstance WHERE id_artefactinstance IN ($artefactList)";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	static private function _log($msg = '')
	{
		if(!empty($msg))
		{
			$msg = date('Y-d-m H:i:s')." - ".$msg;
		}
		
		$msg .= "\n";
		
		echo $msg;
		
		SU_DebugTools::WriteToLog($msg, self::$logFile);
	}
	
	static private function _getCombsWithArtefacts()
	{
		$sql = "
			SELECT
				cha.id_comb,
				ai.id_artefact,
				count(*) as count
			FROM
				ds_combhasartefact AS cha
				INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
			WHERE
				ai.id_artefact IN (".implode(',', array_keys(self::$replaceArtefacts)).")
			GROUP BY cha.id_comb, ai.id_artefact
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
}

?>