<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Запросы связанные с действиями шпионов
 *
 */
class DS_SpyAction extends DS_Base 
{
	/**
	 * ИД типа действия по имени
	 *
	 * @param string $name
	 * @return int
	 */
	static public function GetSpyActionIDByName($name)
	{
		assert(preg_match('/^[A-Z_]+$/', $name));
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				id_spy_action
			FROM
				ds_spy_action_type
			WHERE
				action_name = '$name'
			";
		$res = $common->getResultArray(self::FormMark(__METHOD__, $name), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		
		return (int)$res[0]['id_spy_action'];
	}
	
	/**
	 * Данные о типе действия по ИД
	 *
	 * @param int $id
	 * @return array
	 */
	static public function GetSpyActionDataByID($id)
	{
		assert(is_int($id) && $id > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				*
			FROM
				ds_spy_action_type
			WHERE
				id_spy_action = $id
			";
		$res = $common->getResultArray(self::FormMark(__METHOD__, $id), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		
		return $res[0];
	}
	
	/**
	 * Список айдишников действий
	 *
	 * @return array
	 */
	static public function GetActionsID()
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				id_spy_action
			FROM
				ds_spy_action_type
			";
		$res = $common->getResultArray(self::FormMark(__METHOD__), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	/**
	 * Добавить новое действие
	 *
	 * @param GB_SpyActionCreationData $creationData
	 * @param DE_EventQueue $event
	 */
	static public function InsertSpyActionGroup(GB_SpyActionCreationData $creationData, DE_EventQueue $event)
	{
		$common = self::getCommon();
		
		if(is_null($creationData->getCombTo()))
		{
			$combTo = 'null';
		} else {
			$combTo = $creationData->getCombTo()->getCombID();
		}
		
		if(is_null($creationData->getLogID()))
		{
			$logID = 'null';
		} else {
			$logID = $creationData->getLogID();
		}
		
		$sql = "
			INSERT INTO ds_spy_group_action
				(id_spy_action, zonds_amount, id_comb_from, id_comb_to, id_eventqueue, id_unit, end_time, id_spy_log, one_round)
			VALUES
				(
					".$creationData->getActionType()->getID().", 
					".$creationData->getZondsAmount().", 
					".$creationData->getCombFrom()->getCombID().", 
					".$combTo.", 
					".$event->getID().",
					".$creationData->getUnit()->getID().",
					'".$event->getEndDate()->getPGFormat()."',
					".$logID.",
					".$creationData->getOneRound(true)."
				)
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	/**
	 * Список всех летающих сейчас групп
	 *
	 * @param DE_Comb $comb
	 * @param DE_Unit $unit
	 * @return array
	 */
	static public function GetAllSpyGroups(DE_Comb $comb, DE_Unit $unit)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				id_spy_group_action
			FROM
				ds_spy_group_action
			WHERE
				id_comb_from = ".$comb->getCombID()."
				AND
				id_unit = ".$unit->getID()."
			ORDER BY end_time
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	/**
	 * Данные о группе шпионов по ИД
	 *
	 * @param integer $id
	 * @return array
	 */
	static public function GetSpyGroupActionByID($id)
	{
		assert(is_int($id) && $id > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				id_spy_action,
				zonds_amount, 
				id_comb_from, 
				id_comb_to, 
				end_time,
				id_unit,
				id_spy_log,
				id_eventqueue,
				one_round
			FROM
				ds_spy_group_action
			WHERE
				id_spy_group_action = $id
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		
		return $res[0];
	}
	
	
	static public function UpdateSpyGroupAction(DE_SpyZondGroup $group)
	{
		$common = self::getCommon();
		
		$logID = $group->getLogID();
		if(is_null($logID))
		{
			$logID = 'null';
		}
		
		$sql = "
			UPDATE
				ds_spy_group_action
			SET
				zonds_amount = ".$group->getAmount().",
				id_spy_log = ".$logID."
			WHERE
				id_spy_group_action = ".$group->getID()."
			";
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	
	static public function GetSpyGroupsInAction(DE_Comb $comb, DE_Unit $unit, DE_SpyAction $action)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				id_spy_group_action
			FROM
				ds_spy_group_action
			WHERE
				id_comb_from = ".$comb->getCombID()."
				AND
				id_unit = ".$unit->getID()." 
				AND
				id_spy_action = ".$action->getID()."
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetSpyGroupIDByEventQueue(DE_EventQueue $eventQueue)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				id_spy_group_action
			FROM
				ds_spy_group_action
			WHERE
				id_eventqueue = ".$eventQueue->getID()."
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg) && count($res) == 1);
		
		return (int)$res[0]['id_spy_group_action'];
	}

	
	static public function GetAverageResourceValue(DE_Resource $resource, $type)
	{
		$worldID = (int)$_SESSION['world'];
		assert($type == 'CURRENT_AMOUNT' || $type == 'TURN_PRODUCTION');
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				value
			FROM
				ds_average_resources AS ar
				INNER JOIN ds_attribute AS at USING (id_attribute)
				INNER JOIN ds_attributetype AS att USING (id_attributetype)
				INNER JOIN ds_attributename AS an USING (id_attributename)				
			WHERE
				ar.id_world = $worldID
				AND
				ar.is_current
				AND
				att.typename = '$type'
				AND
				an.attributename = '".$resource->getName()."'
			";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		if(count($res) == 0)
		{
			return 1;
		}
		
		return (float)$res[0]['value'];
	}
}

?>