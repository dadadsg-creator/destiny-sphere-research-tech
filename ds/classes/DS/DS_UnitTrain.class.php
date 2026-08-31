<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Запросы связанные с тренировкой юнитов
 *
 */
class DS_UnitTrain extends DS_Base
{
	/**
	 * Можно ли данный тип юнита строить с домике
	 *
	 * @param integer $unitID
	 * @param integer $buildingID
	 * @return bool
	 */
	static public function IsUnitProducableInBuilding($unitID, $buildingID)
	{
		assert(is_int($unitID) && $unitID > 0);
		assert(is_int($buildingID) && $buildingID > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT count(*) FROM ds_produceableunit WHERE id_building = ".$buildingID." AND id_unit = ".$unitID;
		$res = $common->getResultArray(self::FormMark(__METHOD__, $buildingID.'_'.$unitID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		
		if($res[0]['count'] > 0)
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Каких юнитов можно строить в здании
	 *
	 * @param int $attachedBuildingID
	 * @return int
	 */
	static public function GetProducableUnitByIdAttachedBuilding($attachedBuildingID)
	{
		assert(is_int($attachedBuildingID) && $attachedBuildingID > 0);
		
		$common = self::getCommon();
		
		$sql = 	"SELECT 
					pu.id_unit
				 FROM 
				 	ds_attachedbuilding AS ab
					INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)
					INNER JOIN ds_produceableunit AS pu ON (pu.id_building = b.id_building) 					
				 WHERE 
					ab.id_attachedbuilding = ".$attachedBuildingID;
		$res = $common->getResultArray("GET_UNITS_FOR_BUILD_IN_BILDING_BY_ID_ATTACHED_BULDING_".$attachedBuildingID, $sql, "", "", "GLOBAL_CACHE");
		assert(empty($common->errormsg) && count($res) > 0);
		
		return (int)$res[0]['id_unit'];
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DE_Unit $unit
	 * @param DE_Comb $comb
	 * @return integer
	 */
	static public function GetUnitTrainEventQueueID(DE_Unit $unit, DE_Comb $comb)
	{
		$common = self::getCommon();
		
		$eventID = DS_Event::GetEventIDByName('TRAIN_UNIT');		
		assert($eventID > 0);
		
		$sql = "
			SELECT 
				cbeq.id_eventqueue
			FROM 
				ds_combbindeventqueue cbeq,
				ds_eventqueue eq,
				ds_event e,
				ds_eventoption eo,
				ds_eventhasoption eho
			WHERE 
				cbeq.id_eventqueue = eq.id_eventqueue
				and eq.id_event = e.id_event
				and eq.isTrash = false
				and eho.id_eventqueue = cbeq.id_eventqueue
				and eho.id_eventoption = eo.id_eventoption
				and eo.optionname = 'ID_Unit'
				and eho.value = ".$unit->getID()."
				and e.id_event = ".$eventID."
				and cbeq.id_comb = ".$comb->getCombID()."
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return (int)$res[0]['id_eventqueue'];
		}
		
		return 0;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DE_Unit $unit
	 * @param DE_Comb $comb
	 * @return integer
	 */
	static public function GetCountUnitInProduction(DE_Unit $unit, DE_Comb $comb)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT 
				eho1.value
			FROM 
				ds_combbindeventqueue cbeq1,
				ds_eventqueue eq1,
				ds_event e1,
				ds_eventhasoption eho1
			WHERE 
				cbeq1.id_eventqueue = eq1.id_eventqueue
				and eho1.id_eventqueue = eq1.id_eventqueue
				and eq1.id_event = e1.id_event
				and eq1.istrash = false
				and eho1.id_eventoption = (
					SELECT 
						id_eventoption
					FROM 
						ds_eventoption
					WHERE 
						id_event = e1.id_event
						and optionname = 'CountToTrain'
				)
				and eq1.id_eventqueue = (
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
						and eho.value = ".$unit->getID()."
					)
				and id_comb = ".$comb->getCombID()."";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return (int)$res[0]['value'];
		}
		
		return 0;
	}
	
	/*
	!!!!!!!!!!!!!!!!!!!!!!!!УДАЛИТЬ!!!!!!!!!!!!!!!!!!!!!!!!
	МЕТОД ПЕРЕНЕСЕН В КЛАСС DS_ArmyWeight (GetUnitListToReincarnation)
	*
	 * хватает ли на юнит науки и зданий
	 *
	 * @param integer $uiid
	 * @param integer $unitId
	 * @param integer $combId
	 * @param integer $riceId
	 * @return boolean
	 */
	/*static public function GetUnitProductionAbility($uiid, $unitId, $combId, $riceId,)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
					t.id_unit,
					u.*
				FROM
					(
						SELECT
							pu.id_building,
							pu.id_unit,
							ucbbr.id_race,
							(
								SELECT
									(
										SELECT
											count(id_unit)
										FROM
											ds_unitneedsscience
										WHERE
											id_unit = pu.id_unit
									) = (
										SELECT
											count(uns.id_unit)
										FROM
											ds_unitneedsscience uns,
											ds_userhasscience uhs
										WHERE
											uns.id_science = uhs.id_science
											and uhs.inresearch = false
											and uhs.id_userinstance = $uiid
											and uns.id_unit = pu.id_unit
									)
							) as senough,
							(
								SELECT
									(
										SELECT
											count(id_unit)
										FROM
											ds_unitneedsbuilding unb
										WHERE
											id_unit = pu.id_unit
									) = (
										SELECT
											count(unb.id_unit)
										FROM
											ds_unitneedsbuilding unb,
											ds_attachedbuilding ab
										WHERE
											unb.id_building = ab.id_building
											and ab.active = true
							    			AND ab.underconstruction = 'f'
    										AND ab.inactivation = 'f'
											and ab.id_comb = $combId
											and id_unit = pu.id_unit
										)
							) as benough
						FROM
							ds_attachedbuilding ab2,
							ds_unitcanbebuildbyrace ucbbr
						WHERE
							pu.id_unit = ucbbr.id_unit
							and ab2.id_attachedbuilding = %d
							and ucbbr.id_race = %d
					) t,
					ds_unit u
				WHERE
					t.id_unit = u.id_unit
					and t.senough = true
					and t.benough = true
					and u.id_unit = $unitId";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return true;
		}
		
		return false;
	}*/
}

?>