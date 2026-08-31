<?php $CVS_Version = '$Revision$ ; $Date$';
class ME_CancelUpgradeBuilding
{
	static private $common;

	static public function Cancel($attachedBuildingID)
	{
		assert(is_int($attachedBuildingID) && $attachedBuildingID > 0);

		self::$common = DS_Common::CreateForCurentDB();

		$worldSettings = DE_WorldSettings::CreateForCurentWorld();

		$sql ="SELECT * FROM ds_attachedbuilding WHERE id_attachedbuilding = ".$attachedBuildingID;
		$res = self::$common->getResultArray ( "", $sql, "", "", "" );
		assert(empty(self::$common->errormsg));
		assert(sizeof($res) > 0);

		$combID = (int)$res[0]['id_comb'];
		$buildID = (int)$res[0]['id_building'];
		$cellID = (int)$res[0]['id_cell'];
		$raceID = (int)$_SESSION['race'];

		// здание апгрейдится
		$sql = "SELECT
					bcbu.id_to_building,
					ab.ID_AttachedBuilding,
					abbeq.ID_EventQueue
				FROM
					DS_AttachedBuilding ab,
					ds_building b,
					ds_buildingcanbeupgraded bcbu,
					DS_AttachedBuildingBindEventQueue abbeq,
					DS_EventQueue eq,
					DS_Event e,
					ds_buildingcanbuildbyrace bcbbr
				WHERE
						ab.ID_AttachedBuilding = abbeq.ID_AttachedBuilding
					AND ab.id_building = b.id_building
					AND b.id_building = bcbu.id_from_building
					AND abbeq.ID_EventQueue = eq.ID_EventQueue
					AND eq.ID_Event = e.ID_Event
					AND bcbbr.id_building = bcbu.id_to_building
					AND eq.isTrash = false
					AND ab.inupgrade is true
					AND e.EventName = 'UPGRADE_BUILDING'
					AND ab.ID_AttachedBuilding = ".$attachedBuildingID."
					AND ab.id_building = ".$buildID."
					AND ab.id_cell = ".$cellID."
		 			AND bcbbr.id_race = ".$raceID;
		$res2 = self::$common->getResultArray( "", $sql, "", "", "" );
		assert(empty(self::$common->errormsg));
		assert(sizeof($res2) == 1);

		$bindeqid = (int)$res2[0]['id_eventqueue'];
		$tobuild = (int)$res2[0]['id_to_building'];

		$eventOptions = DE_EventQueueAttributes::CreateFromEventID($bindeqid);

		//возвращаем комбу атрибуты предидущего здания
		$combAttributes = DE_CombAttributes::CreateFromCombID($combID);
		$buildingFromAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$eventOptions->getAttribute('ID_BuildingFrom'));


		//Если в нем есть люди то этот атрибут оставляем
		if($buildingFromAttributes->haveAttribute('MAX_AMOUNT', 'WORKER'))
		{
			$combAttributes->deltaAttribute('MAX_AMOUNT', 'WORKER', -$buildingFromAttributes->getAttribute('MAX_AMOUNT', 'WORKER'));
		}

		//Убираем атрибуты сторого здания
		ME_AddBuildingAttributesToComb::AddAttributes($buildingFromAttributes, $combAttributes, 'plus');

		//Убираем арты старого здания из бонусов на период апдейта
		ME_AddBuildingAttributesToComb::AddArtefactAttributes($attachedBuildingID, $combAttributes, 'plus');		

		// отменяем апгрейд
		$sql = sprintf ( "update only DS_AttachedBuilding
				set inupgrade = false
				where ID_AttachedBuilding = %d",
				$attachedBuildingID );

		$res = self::$common->executeQuery ( $sql );
		assert(empty(self::$common->errormsg));

		// процент завершенности
		$sql = sprintf ( "select getProgress(%d) as prog", $bindeqid );

		$res = self::$common->getResultArray ( "progress_id_eventqueue_".$bindeqid, $sql, "", "", "LOCAL_CACHE" );
		assert(empty(self::$common->errormsg));

		$prog = (int)$res[0]['prog'];


		$minlost = $worldSettings->get('MIN_RES_LOST_ON_CANCEL_BUILDING') * 100;

		if ( (int)$prog < (int)$minlost )
		{
			$prog = $minlost;
		}

		$devisor = $worldSettings->get('UPGRADE_COSTS_DIVISOR');

		// возвращаем часть ресурсов
		$buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$tobuild);
		$attributeNames = $buildingAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');

		foreach ($attributeNames as $k=>$attributeName)
		{
			if ( $attributeName == 'CONSTRUCTION_TIME')
			{
				continue;
			}

			$value = $buildingAttributes->getAttribute('PRODUCTION_COSTS', $attributeName);

			if($attributeName != 'WORKER')
			{
				$delta = round(($value-($value*$prog)/100)/$devisor);
			}else{
				$delta = round($value / $devisor);
			}
			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, $delta);
		}

		// прибиваем эвент				
		$event = DE_EventQueue::CreateFromID((int)$bindeqid);
		$event->setIsTrash(true);

		return true;

	}

}

?>