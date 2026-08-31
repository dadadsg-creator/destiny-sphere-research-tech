<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Запросы для менюшек шпионажа
 *
 */
class DS_SpyMenu extends DS_Base
{
	/**
	 * ID всех шпионских юнитов
	 *
	 */
	static public function GetSpyUnits()
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				u.id_unit,
				u.unitname
			FROM
				ds_unit AS u
				INNER JOIN ds_unitorcontainertype AS uct ON (u.id_unitcontainertype = uct.id_unitcontainertype)
			WHERE
				uct.containertypename = 'SPY'
			";
		$res = $common->getResultArray(self::FormMark(__METHOD__), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	/**
	 * Есть ли евент тренировки шпоинских юнитов, кроме данного юнита
	 *  нужно для запрещения паралельного строительства разных типов юнитов
	 * 
	 * @param DE_Comb $comb
	 * @param DE_Unit $unit
	 * @return bool
	 */
	static public function HaveSpyUnitsTrainingExceptUnit(DE_Comb $comb, DE_Unit $unit)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT 
				eq.id_eventqueue 
			FROM 
				ds_eventqueue eq,
				ds_combbindeventqueue cbeq2,
				ds_eventhasoption eho
			WHERE 
				eq.id_eventqueue = eho.id_eventqueue
				and eq.istrash = false
				and cbeq2.id_eventqueue = eq.id_eventqueue
				and cbeq2.id_comb = ".$comb->getCombID()."
				and eho.id_eventoption = 
				(
					SELECT 
						id_eventoption
					FROM 
						ds_eventoption eo,
						ds_event e
					WHERE 
						eo.id_event = e.id_event
						and e.eventname = 'TRAIN_UNIT'
						and eo.optionname = 'ID_Unit'
				)
				and eho.value IN (
					SELECT
						u.id_unit
					FROM
						ds_unit AS u
						INNER JOIN ds_unitorcontainertype AS uct ON (u.id_unitcontainertype = uct.id_unitcontainertype)
					WHERE
						uct.containertypename = 'SPY'
						AND
						u.id_unit != ".$unit->getID()."
					)
			";
		$res = $common->getResultArray(self::FormMark(__METHOD__), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Кол-во зондов занятых определенным делом
	 *
	 * @param DE_Comb $comb
	 * @param DE_Unit $unit
	 * @param DE_SpyAction $action
	 * @return int
	 */
	static public function GetCountZondsInAction(DE_Comb $comb, DE_Unit $unit, DE_SpyAction $action)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				sum(zonds_amount) as sum
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
		
		return (int)$res[0]['sum'];
	}
	
	/**
	 * Кол-во групп зондов занятых определенным делом
	 *
	 * @param DE_Comb $comb
	 * @param DE_Unit $unit
	 * @param DE_SpyAction $action
	 * @return int
	 */
	static public function GetCountGroupsInAction(DE_Comb $comb, DE_Unit $unit, DE_SpyAction $action)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				count(*) as count
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
		
		return (int)$res[0]['count'];
	}
}

?>