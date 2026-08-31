<?php

/**
 * Замена определенных квестовых юнитов на ресурсы
 *
 */
class TMP_ReplaceUnitsWithResource
{
	// названия юнитов, которые меняем
	static private $units = array(
			'ELEMENT_OF_ENERGY' => 63,  // энервитриума
			'ELEMENT_OF_EAT' => 19,		// виталое
			'ELEMENT_OF_KADERIUM' => 24,// санкторума
			'ELEMENT_OF_NANO' => 93,	// неомортана
			'ELEMENT_OF_PRODIUM' => 63,	// терминуса
			'MIMICRY_DRONE' => 6,
			'MIMICRY_SERVANT' => 104,
		);
		
	// ид юнитов, полученные из названий
	static private $unitIDs = array();
	
	// ресурс на который меняем
	static private $replaceResource = 'PRODIUM';
	
	// юнит на который меняем
	static private $replaceUnit = 'HIEROPHANT'; // вроде как патриарх
	
	// коеффициент перевода из стазиса
	static private $replaceResourceWieght = 0.075; // 0.1 * 0.75, т.е. 75% от веса
		
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $dbID;
	
	static private $worldID;
	
	static private $logFile = 'QuestUnitsConvert.log';
	
	static public function Exec($dbID, $worldID)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		self::$dbID = $dbID;
		self::$worldID = $worldID;
		
		self::$common = DS_Common::CreateForCurentDB($dbID);
		
		self::$common->startTrans();
		
		self::_log();
		self::_log(' ----===== START CONVERSION =====---');
		
		self::_initUnitsIDs();
		
		$armies = self::_getUnitContainersWithUnits();
		
		self::_log('FOUND '.count($armies).' AMRIES WITH QUEST UNITS ');
		self::_log();
		
		$replacementLog = array();
		
		foreach ($armies as $k => $v)
		{
			$army = DE_Army::CreateFromID((int)$v['id_army']);
			
			self::_log('START PROCESS ARMY '.$army->getName().' OF USER '.$army->getUserInstance()->getUser()->getName());
			
			if($army->getInAction())
			{
				self::_log('ARMY IN ACTION. CONTINUE.');
				continue;
			}
			
			$battles = DS_Battle::NotEndedBattlesOnComb($army->getBaseComb());
			if(count($battles) > 0)
			{
				self::_log('BATTLE ON COMB. CONTINUE.');
				continue;
			}
			
			$unitsCollection = $army->getUnitsGroupCollection();
			$unitsCollection->resetNext();
			while($unitGroup = $unitsCollection->next())
			{
				$pos = $unitsCollection->getCurrentPosition();
				
				if(!in_array($unitGroup->getUnit()->getID(), self::$unitIDs))
				{
					continue;
				}
				
				self::_log("\t found ".$unitGroup->getAmount()." of ".$unitGroup->getUnit()->getName());
				
				if(self::_checkReplaceForResource($army->getUserInstance()->getUser()))
				{
					// замена на продиум
					self::_replaceForResource($unitGroup, $army, $replacementLog);
				} else {
					// замена на юниты
					self::_replaceForUnit($unitGroup, $army, $replacementLog);
				}				
				
				$unitGroup->setAmount((float)0);
				$unitGroup->delete();		
				
				$unitsCollection->setPointerToPosition($pos);
			}
			
			if($army->getArmyPower() == 0 && !$army->getIsDefault())
			{
				$army->delete();
				self::_log("\t army deleted");
			}
			
			self::_log('ARMY DONE');
			self::_log();
		}
		
		self::_log('START SEND MESSAGES');

		self::_sendMessages($replacementLog);
		
		self::_log(count($replacementLog).' MESSAGES SEND');
		
		GB_SingletonObjectStorage::CommitAllCommitable();
		
		self::$common->closeTrans('COMMIT');
		
		self::_log(' ----===== CONVERSION DONE =====---');
		self::_log();
	}
	
	
	static private function _checkReplaceForResource(DE_User $user)
	{
		// вопрос 6, ответы 22 и 23 - смена квестовых юнитов на ресурсы
		self::$common->setDbId(0);
		$sql = "SELECT id_answer FROM ds_poll_result WHERE id_question = 6 AND id_user = ".$user->getID();
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		self::$common->setDbId(self::$dbID);	
		
		if(count($res) <= 0)
		{
			return true;
		}
		
		if($res[0]['id_answer'] == 22 || $res[0]['id_answer'] == 23)
		{
			return true;
		}
		
		return false;
	}
	
	static private function _sendMessages(array $replacementLog)
	{
		foreach ($replacementLog as $UIID => $data)
		{
			$messageText = 'На ваших сотах были заменены следующие квестовые юниты:'."\n";
			foreach ($data['UNIT'] as $k => $unit)
			{
				$messageText .= "\t\tВ армии <b>\"".$unit['ARMY'].'"</b> '.$unit['COUNT'].' x '.$unit['NAME']."\n";
			}
			
			if($data['REPLACE'] == 'RESOURCE')
			{
				$messageText .= "\n".'Вы получили <b>'.number_format($data['SUM'], null, '.', ' ').'</b> продиума.';
			} else {
				$messageText .= "\n".'Вы получили <b>'.number_format($data['SUM'], null, '.', ' ').'</b> патриархов.';
			}
			
			$messageID = DS_Message::CreateMessage(GL_EncodeGameMessage::Encode($messageText), 'Замена квестовых юнитов', 'DestinySphere', $data['USER'], 10, 0);
			DS_Message::AddMessageToUser($messageID, $UIID);
		}
	}
	
	static private function _replaceForResource(DE_UnitsGroup $unitGroup, DE_Army $army, array &$replacementLog)
	{
		$unitsCount = $unitGroup->getAmount();
		$unitsWeight = $unitGroup->getUnit()->getStazis();
		
		$countResource = ceil($unitsCount * $unitsWeight * self::$replaceResourceWieght);
		
		$combAttributes = $army->getBaseComb()->getAttributes();
		$combAttributes->deltaAttribute('CURRENT_AMOUNT', self::$replaceResource, $countResource);
				
		self::_log("\t replaced with ".GB_Integer::Format($countResource)." of ".self::$replaceResource);
		
		if(!key_exists($army->getUserInstance()->getID(), $replacementLog))
		{
			$replacementLog[$army->getUserInstance()->getID()]['SUM'] = 0;
			$replacementLog[$army->getUserInstance()->getID()]['USER'] = $army->getUserInstance()->getUser()->getName();
			$replacementLog[$army->getUserInstance()->getID()]['UNIT'] = array();
			$replacementLog[$army->getUserInstance()->getID()]['REPLACE'] = 'RESOURCE';
		}
				
		$replacementLog[$army->getUserInstance()->getID()]['SUM'] += $countResource;
		$replacementLog[$army->getUserInstance()->getID()]['UNIT'][] = array('ARMY' => $army->getName(), 'NAME' => $unitGroup->getUnit()->getLocalizedName(), 'COUNT' => $unitsCount);
	}
	
	static private function _replaceForUnit(DE_UnitsGroup $unitGroup, DE_Army $army, array &$replacementLog)
	{
		$unitsCount = $unitGroup->getAmount();
		$replaceCount = self::$units[$unitGroup->getUnit()->getName()] * ceil($unitsCount);
		
		$homeArmy = DE_Army::CreateHomeArmyByCombID($army->getBaseCombID());
		
		$replaced = false;
		
		$unitsCollection = $homeArmy->getUnitsGroupCollection();
		$unitsCollection->resetNext();
		while($homeUnitGroup = $unitsCollection->next())
		{
			$pos = $unitsCollection->getCurrentPosition();

			if($homeUnitGroup->getUnit()->getName() == self::$replaceUnit)
			{
				$homeUnitGroup->deltaAmount((float)$replaceCount);
				$replaced = true;
				break;
			}
			
			$unitsCollection->setPointerToPosition($pos);
		}
		
		if(!$replaced)
		{
			$homeUnitGroup = DE_UnitsGroup::CreateInDB($homeArmy, DE_Unit::CreateFromName(self::$replaceUnit), $replaceCount);
			$homeArmy->getUnitsGroupCollection()->AddUnitGroup($homeUnitGroup);
		}
		
		if(!key_exists($army->getUserInstance()->getID(), $replacementLog))
		{
			$replacementLog[$army->getUserInstance()->getID()]['SUM'] = 0;
			$replacementLog[$army->getUserInstance()->getID()]['USER'] = $army->getUserInstance()->getUser()->getName();
			$replacementLog[$army->getUserInstance()->getID()]['UNIT'] = array();
			$replacementLog[$army->getUserInstance()->getID()]['REPLACE'] = 'UNIT';
		}
		
		$replacementLog[$army->getUserInstance()->getID()]['SUM'] += $replaceCount;
		$replacementLog[$army->getUserInstance()->getID()]['UNIT'][] = array('ARMY' => $army->getName(), 'NAME' => $unitGroup->getUnit()->getLocalizedName(), 'COUNT' => $unitsCount);
		
		self::_log("\t replaced with ".GB_Integer::Format($replaceCount)." of ".self::$replaceUnit);
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
	
	static private function _initUnitsIDs()
	{
		$sql = "
			SELECT
				id_unit
			FROM
				ds_unit
			WHERE
				unitname IN ('".implode("','", array_keys(self::$units))."')
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		foreach ($res as $k => $unit)
		{
			self::$unitIDs[] = (int)$unit['id_unit'];
		}
	}
	
	static private function _getUnitContainersWithUnits()
	{
		$sql = "
			SELECT
				DISTINCT id_army
			FROM
				ds_unitcontainerhasunit
			WHERE
				id_unit IN (".implode(',', self::$unitIDs).")
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));		
		
		return $res;
	}
}

?>