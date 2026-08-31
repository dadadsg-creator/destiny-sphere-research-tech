<?php

/**
 * Конверт юнитов в артефакты
 * Вызов в файле ConvertUnitsToCholera.php
 *
 */
class TMP_ConvertUnitsToCholera
{
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $dbID;
	
	static private $worldID;
	
	static private $logFile = 'CholeraConvertToArt.log';	
	
	static private $eventToTrash = array();
	
	static private $proccedCount = 0;
	
	// что на что меняем; юнит (ИД) => артефакт (ИД)
	static private $replacement = array(
			84 => 1085,
			85 => 1086,
			86 => 1084,
			87 => 1087,
		);
		
	// юниты на которые подменяем в армии; раса (ИД) => юнит (ИД)
	static private $backupUnits = array(
			1 => 3,
			2 => 20,
			3 => 25
		);
		
	// ИД армий по расам
	static private $raceConversion = array(
			1 => array(),
			2 => array(),
			3 => array(),
		);
	
	static private $units;
		
	static public function Exec($dbID, $worldID)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		
		self::$dbID = $dbID;
		self::$worldID = $worldID;
		
		$_SESSION['dbId'] = $dbID;
		$_SESSION['world'] = $worldID;
		
		self::_log();
		self::_log('============= START =============');
		self::$common = DS_Common::CreateForCurentDB($dbID);
		self::$common->startTrans();
		
		// замена в трансфере
		self::_execTransfer();
		
		// замена в торговле
		self::_execTrade();
		
		// замена в армиях
		self::_execArmies();
		
		GB_SingletonObjectStorage::CommitAllCommitable();

		// заменяем квестовые юниты на обчные, чтоб не убивать контейнер
			// нужно делать после комита всех объектов
		self::_replaceUnits();
			
		self::$common->closeTrans('COMMIT');
		self::_log('PROCCED <b>'.self::$proccedCount.'</b> Units');
		self::_log('============== END ==============');
		self::_log();
	}
	
	
	static private function _execTransfer()
	{
		// трансфер + доставка товара
		$transfers = self::_getTransferEvents();
		
		self::_log('PROCCES TRANSFERS');
		
		foreach ($transfers as $k => $trans)
		{
			self::$eventToTrash[] = $trans['id_eventqueue'];
			self::_addArtefacts((int)$trans['id_comb'], (int)$trans['id_unit'], (int)$trans['count']);
			self::_log('add '.(int)$trans['count'].' artefacts to '.DE_Comb::CreateFromID((int)$trans['id_comb'])->getUserInstance()->getUser()->getName());
			
			self::$proccedCount += (int)$trans['count'];
		}
		
		// в треш евенты
		self::_setEventsToTrash();
		
		self::_log('OK');
		self::_log();
	}
	
	static private function _execTrade()
	{
		$trades = self::_getTades();
		
		self::_log('PROCCES TRADES');
		
		foreach ($trades as $k => $trade)
		{
			$tradeLot = DE_TradeLot::CreateFromID((int)$trade['id_trade_lot']);
			
			self::_addArtefacts($tradeLot->getSellerComb()->getCombID(), $tradeLot->getArticleID(), $tradeLot->getAmount());
			self::_log('add '.$tradeLot->getAmount().' artefacts to '.$tradeLot->getSeller()->getUser()->getName());
			
			self::$proccedCount += $tradeLot->getAmount();
			
			ME_TradeExecReturnUserBid::Exec($tradeLot, false);
			$tradeLot->delete();
			$tradeLot->getLotEvent()->setIsTrash(true);
		}
		
		self::_log('OK');
		self::_log();
	}
	
	static private function _execArmies()
	{
		self::_log('PROCCES ARMIES');
		
		foreach (self::$replacement as $unitID => $a)
		{
			self::$units[$unitID] = DE_Unit::CreateFromID($unitID);
		}
		
		$armies = self::_getArmiesWithUnits();
		
		foreach ($armies as $k => $data)
		{
			$army = DE_Army::CreateFromID((int)$data['id_unitcontainer']);
			$unitGroup = $army->getUnitsGroupCollection()->GetUnitGroupByUnit(self::$units[(int)$data['id_unit']]);
			if($unitGroup === false)
			{
				continue;
			}
					
			self::_addArtefacts($army->getBaseCombID(), $unitGroup->getUnitID(), ceil($unitGroup->getAmount()));
			self::_log('add '.ceil($unitGroup->getAmount()).' artefacts to '.$army->getUserInstance()->getUser()->getName());
			
			self::$proccedCount += ceil($unitGroup->getAmount());
			
			if($army->getUnitsGroupCollection()->countCollection() == 1)
			{
				self::$raceConversion[$army->getUserInstance()->getRaceID()][] = $army->getID();
			} else {
				$unitGroup->delete();
				$army->getUnitsGroupCollection()->deleteElement($unitGroup);
			}
		}
		
		
		
		self::_log('OK');
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
	
/************************** SQL ******************************/
	static private function _replaceUnits()
	{
		foreach (self::$raceConversion as $race => $armies)
		{
			if(count($armies) == 0)
			{
				continue;
			}
			
			$sql = "
				UPDATE 
					ds_unitcontainerhasunit 
				SET 
					id_unit = ".self::$backupUnits[$race].", 
					amount = 1 
				WHERE 
					id_army IN (".implode(',', $armies).")";
			self::$common->executeQuery($sql);
			assert(empty(self::$common->errormsg));
		}
	}	

	static private function _setEventsToTrash()
	{
		if(count(self::$eventToTrash) == 0)
		{
			return;
		}
		
		$sql = "UPDATE ds_eventqueue SET istrash = true WHERE id_eventqueue IN (".implode(',', self::$eventToTrash).")";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}


	static private function _addArtefacts($combID, $unitID, $count)
	{
		assert(is_int($combID) && $combID > 0);
		assert(is_int($unitID) && $unitID > 0);
		assert((is_int($count) || is_double($count))  && $count  > 0);
		assert(key_exists($unitID, self::$replacement));

		for($i=0; $i<$count; $i++)
		{

			$sql = "select nextval('ds_artefactinstance_id_artefactinstance_seq') as id";
			$res = self::$common->getResultArray ( "", $sql, "", "", "" );
			assert(empty(self::$common->errormsg) && count($res) == 1);

			$id_artefactinstance = (int)$res[0]['id'];
			assert($id_artefactinstance > 0);

			$sql =
			"INSERT INTO
		 	ds_artefactinstance
		 		(id_artefactinstance, id_artefact, immortal, birth_time, death_time)
       		VALUES 
       			($id_artefactinstance, ".self::$replacement[$unitID].", 't', NULL, NULL);
        
       		INSERT INTO 
       		ds_combhasartefact
       			(id_comb, id_artefact)
    		VALUES 
    			(".$combID.", $id_artefactinstance);
       	";
			self::$common->executeQuery($sql);
			assert(empty(self::$common->errormsg));
		}
	}
	
	static private function _getArmiesWithUnits()
	{
		$sql = "
			SELECT
				uchu.id_army as id_unitcontainer,
				uchu.id_unit
			FROM
				ds_unitcontainerhasunit AS uchu
			WHERE 
				uchu.id_unit IN (".implode(',', array_keys(self::$replacement)).")
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getTades()
	{
		$sql = "
			SELECT
				tl.id_trade_lot
			FROM
				ds_trade_lots AS tl
				INNER JOIN ds_trade_type AS tt USING (id_tradetype)
				INNER JOIN ds_trade_category AS tc ON (tc.id_tradecategory = tt.id_trade_on_category)
			WHERE
                tc.categoryname = 'UNIT'
                AND
                tl.id_article IN (".implode(',', array_keys(self::$replacement)).")
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getTransferEvents()
	{
		$sql = "
			SELECT
    			eq.id_eventqueue,
    			eho2.value AS id_unit,
    			eho3.value AS count,
    			eho4.value AS id_comb
			FROM
    			ds_eventqueue AS eq
    			INNER JOIN ds_event AS ev USING (id_event)
    
			    INNER JOIN ds_eventhasoption AS eho USING (id_eventqueue)
    			INNER JOIN ds_eventoption AS eo ON (eo.id_eventoption = eho.id_eventoption)
    
    			INNER JOIN ds_eventhasoption AS eho2 USING (id_eventqueue)
    			INNER JOIN ds_eventoption AS eo2 ON (eo2.id_eventoption = eho2.id_eventoption)
    
    			INNER JOIN ds_eventhasoption AS eho3 USING (id_eventqueue)
    			INNER JOIN ds_eventoption AS eo3 ON (eo3.id_eventoption = eho3.id_eventoption)
    
    			INNER JOIN ds_eventhasoption AS eho4 USING (id_eventqueue)
    			INNER JOIN ds_eventoption AS eo4 ON (eo4.id_eventoption = eho4.id_eventoption)
			WHERE
    			ev.eventname IN ('TRANSFER_RESOURCE', 'TRADING_DELIVERY_GOOD')
    			AND
    			NOT eq.istrash
    			AND
    			eo.optionname = 'ID_Category'
    			AND
    			eho.value = 2
    			AND
    			eo2.optionname = 'ID_Article'
    			AND
    			eho2.value IN (".implode(',', array_keys(self::$replacement)).")
    			AND
    			eo3.optionname = 'Amount'
    			AND
    			eo4.optionname = 'ID_CombTo'
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
}

?>