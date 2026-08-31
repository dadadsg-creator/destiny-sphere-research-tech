<?php

class TMP_NewYearQuest2008
{
	static private $common;
	
	static private $units = array(81, 82);
	
	static private $artefacts = array(
			1 => 1059,
			2 => 1061,
			3 => 1060,
		);
		
	static private $logFile = 'New_Year_Quest.log';
	
	static public function ExecDB($dbId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		$_SESSION['dbId'] = $dbId;
		self::$common = DS_Common::CreateForCurentDB($dbId);
		
		$userList = self::_getUsersListWithUnits();
		
		self::_log();
		self::_log("======= START CONVERT NEW YEAR UNITS TO ARTEFACTS ========");
		self::_log('FOUND '.count($userList).' ARMIES WITH UNITS');
		foreach ($userList as $k => $data)
		{
			$army = DE_Army::CreateFromID((int)$data['id_unitcontainer']);
			$userInstance = DE_UserInstance::CreateFromID((int)$data['id_userinstance']);
			$count = (int)$data['count'];
			
			self::_log("User: ".$userInstance->getUser()->getName()."; Count: ".$count);
			
			if(!self::_checkBattle($userInstance, $army->getBaseComb()))
			{
				self::_log("battle on comb... continue...");
				continue;
			}
			
			self::$common->startTrans();
	
			self::_addArtefacts($userInstance, $army->getBaseComb(), $count);
			self::_deleteUnits($army, $count);
			
			GB_SingletonObjectStorage::CommitAllCommitable();
			self::$common->closeTrans('ROLLBACK');
		}
		
		self::_log("======================== END =============================");
		self::_log();
	}
	
	static private function _log($msg = '')
	{
		if(!empty($msg))
		{
			$msg = date('Y-m-d H:i:s').' - '.$msg;
		}
		
		$msg .= "\n";
		
		echo $msg;
		
		SU_DebugTools::WriteToLog($msg, self::$logFile);
	}
	
	static private function _getUsersListWithUnits()
	{
		$sql = "
			SELECT
    			t.id_userinstance,
    			t.id_unitcontainer,
    			MIN(s) as count
			FROM
    			(
    				SELECT
        				uc.id_userinstance,
        				uc.id_unitcontainer,
        				id_unit,
    					SUM(uchu.amount) as s
    				FROM
        				ds_unitcontainer AS uc
    					INNER JOIN ds_unitcontainerhasunit AS uchu ON (uchu.id_army = uc.id_unitcontainer)
    				WHERE
        				uchu.id_unit IN (".implode(',', self::$units).")
        				AND
        				NOT uc.inaction
    				GROUP BY id_userinstance, id_unitcontainer, id_unit
    			) as t
			GROUP BY t.id_userinstance, t.id_unitcontainer
			HAVING COUNT(t.id_unit) > ".(count(self::$units) - 1).";
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
		
	static private function _checkBattle(DE_UserInstance $userInstance, DE_Comb $comb)
	{
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_battle
			WHERE
				id_battlecomb = ".$comb->getCombID()."
				AND
				battleenddate IS NULL
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		if($res[0]['count'] > 0)
		{
			return false;
		}
		
		return true;
	}
	
	static private function _addArtefacts(DE_UserInstance $userInstance, DE_Comb $comb, $count)
	{
		assert(is_int($count) && $count > 0);
		$sql = "SELECT add_artefact_to_comb(".$comb->getID().", ".self::$artefacts[$userInstance->getRace()->getID()].")";
		for($i=0; $i<$count; $i++)
		{
			self::$common->executeQuery($sql);
			assert(empty(self::$common->errormsg));
		}
	}
	
	static private function _deleteUnits(DE_Army $army, $count)
	{
		assert(is_int($count) && $count > 0);
		
		$unitsGroupCollection = DC_ArmyUnitGroups::CreateFromArmyID($army->getID());
		$unitsGroupCollection->resetNext();
		
		while($unitsGroup = $unitsGroupCollection->next())
		{
			if(in_array($unitsGroup->getUnit()->getID(), self::$units))
			{
				$oldAmount = $unitsGroup->getAmount();
				$newAmount = max(0, $oldAmount - $count);
				$unitsGroup->setAmount((float)$newAmount);
				if($newAmount == 0) 
				{
					$unitsGroup->delete();
				}
			}
		}
		
		if($army->getArmyPower() == 0 && !$army->getIsDefault())
		{
			$army->delete();
		}
	}
	
}

?>